<?php

/**
 * A route that encapsulates one functionality of your website.
 * Do not inherit directly but use @link FrontendRoute
 * or @link BackendRoute to extend from
 */
abstract class Route {

    public function __construct() {}

    /**
     * Renders the html content (use echo, do not return the value)
     * Use [renderTemplate] to render a template using Twig
     * @link renderTemplate
     */
    abstract function render($route);

    /**
     * Determines whether this route should render the template header and footer. Return false for actions like ajax
     * calls where rendering the admin html is not desired
     * Override [renderFrontend] or [renderBackend] to render your content
     * @return bool if this method returns true, the header and footer will be rendered.
     */
    protected function shouldRenderTemplate(): bool {
        return true;
    }

    /**
     * Indicates whether this Route can handle the given path (relative to BASEURL)
     * @param string $route
     * @return bool
     */
    abstract function matches(string $route): bool;

    /**
     * the default folder the json configuration files relevant for this route are contained in, relative to the json folder.
     * Usually "forms" or "tables"
     * @var string
     */
    protected $jsonTableName = "tables";


    /**
     * Retrieve the values for all fields defined in a json configuration file (form or table entry) from a given
     * values array or POST data
     * @param string $tableName the json configuration file name excluding the extension
     * @param array|null $values values array to extract the data from or null (then POST data will be used)
     * @param array|null $previousValues if the values array of POST data does not contain all fields, these values will be used as fallback
     * @param string|null $jsonTableName the folder the json file is in, relative to the json folder. Usually "forms" or "tables". Defaults to @link $this->jsonTableName
     * @return array associative array having the field name as key and the processed values as value
     */
    public function getValueArray($tableName, $values = null, $previousValues = null, $jsonTableName = null) {
        if (!$jsonTableName) $jsonTableName = $this->jsonTableName;
        $models = $this->loadData($tableName, $jsonTableName)["fields"];
        $output = [];
        foreach ($models as $model) {
            $modelClass = $this->getDataType($model);

            $existsInValues = $values !== null && array_key_exists($model['name'], $values);
            $existsInPost = array_key_exists($model['name'], $_POST);
            $existsInFiles = array_key_exists($model['name'], $_FILES);
            if ($existsInValues || $existsInPost || $existsInFiles) {
                if ($existsInValues) {
                    $value = $values[$model['name']];
                } else if ($existsInPost) {
                    $value = $_POST[$model['name']];
                } else {
                    $value = $_FILES[$model['name']];
                }
                $modelClass->value = is_array($previousValues) ? $previousValues[$model['name']] : null;

                if ($modelClass->saveToDb) {
                    $output[$model['name']] = $modelClass->processValue($value);
                } else {
                    /** @noinspection PhpExpressionResultUnusedInspection */
                    $modelClass->processValue($value);
                }
            } else {
                if ($modelClass->saveToDb) {
                    $output[$model['name']] = $modelClass->processValueNotContainedInPost();
                } else {
                    /** @noinspection PhpExpressionResultUnusedInspection */
                    $modelClass->processValueNotContainedInPost();
                }

            }
        }
        return $output;
    }


    /**
     * Creates an instance of [DataType]  depending on the model. Model should one field as read from a json config
     * file using [loadData]
     * @param array $model associative array containing at least a `type`, a `name` and usually also a `config` array
     * @link loadData
     * @link DataType
     */
    protected function getDataType(array $model): DataType {
        $input = DataTypeFactory::create($model);

        if (array_key_exists("required", $model) && $model['required'] === true) {
            $input->required = true;
        }
        $input->name = $model['name'];
        $input->value = array_key_exists("value", $model) ? $model['value'] : null;
        $input->parentRoute = $this;
        return $input;
    }

    /**
     * Loads a json config file
     * @param string $tableName the file name (without extension) of the json config file
     * @param string|null $jsonTableName the folder the json file is in, relative to the json folder. Usually "forms" or "tables". Defaults to @link $this->jsonTableName
     * @return array
     */
    public function loadData($tableName, $jsonTableName = null): array {
        if (!$jsonTableName) $jsonTableName = $this->jsonTableName;
        $tableName = JSON_DIR . $jsonTableName . "/$tableName.json";
        if (!file_exists($tableName)) throw new RuntimeException("Ungültiger Tabellenname $tableName");
        return json_decode(file_get_contents($tableName), true);
    }

    /**
     * searches through a list of fields for a field with the given name
     * @param array $fields as returned by @see loadData()["fields"]
     * @param string $name the name to look for
     * @return array|null
     */
    public function findFieldByName(array $fields, string $name): ?array {
        foreach ($fields as $field) {
            if(is_array($field) && $field["name"] === $name) {
                return $field;
            }
        }
        return null;
    }

    /**
     * Renders a twig template (https://twig.symfony.com/) with the given arguments
     * @param string $template name of the template file (including extension) within the `templates` folder
     * @param array $values arguments for the twig template. baseurl will be automatically added
     * @param bool $showErrors if set to true, errors and messages will be added via the `messages` and `errors` keys and afterwards resetted.
     */
    public function renderTemplate($template, $values = array(), $showErrors = true) {
        $values["baseurl"] = BASEURL;
        if ($showErrors) {
            $values["messages"] = $_SESSION['messages'];
            $values["errors"] = $_SESSION['errors'];
        }

        echo $GLOBALS['templating']->render($template, $values);
        if ($showErrors) {
            $_SESSION['messages'] = array();
            $_SESSION['errors'] = array();
        }
    }

    /**
     * Generates and returns a html rendering of the contents of an edit form using the fields of the given json configuration file
     * @param array $fields fields from the json configuration obtained by loadData()["fields"]
     * @param array|null $values current value array (e.g. read from database), can be empty e.g. when creating a new entry
     * @param ScriptLoader $scriptLoader a scriptLoader instance where data types my register required external scripts
     * @return string html rendering
     */
    protected function generateFormCode($fields, $values, ScriptLoader $scriptLoader): string {
        $output = "";
        foreach ($fields as $field) {
            if (array_key_exists("label", $field)) {
                $output .= "<div class=\"input-wrapper\" id=\"input-wrapper-" . $field['name'] . "\"><label for=\"input-" . $field['name'] . "\">" . $field['label'] . "</label>";
            }
            if (array_key_exists("note", $field)) {
                $output .= "<div class=\"note\" id=\"input-wrapper-" . $field['name'] . "\"'>" . $field['note'] . "</div>";
            }
            if (array_key_exists($field['name'], $values)) {
                $field['value'] = $values[$field['name']];
            }
            $dataType = $this->getDataType($field);
            $dataType->registerScripts($scriptLoader);
            $output .= $dataType->renderBackendForm();
            if (array_key_exists("label", $field)) {
                $output .= "</div>";
            }
        }
        return $output;
    }

    /**
     * Fields implementing the interface [SpecialDataType] do not provide any rendering on its own. Instead, it is a placeholder for custom
     * behaviour. When being rendered in the backend table, this method will be called for each entry in the table
     * @param string $fieldName the name of the special field
     * @param array $entry array containing all fields of an entry
     * @return string html rendering of the special field for the backend table
     * @link SpecialDataType
     */
    public function processSpecialField(string $fieldName, array $entry): string {
        return "";
    }
}