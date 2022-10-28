<?php

/**
 * Helper route to display a single form on your backend. If you
 * want to manage a table, use @link BackendTableRoute instead.
 * the form to be rendered should be saved in json/forms/yourName.json
 * while yourName is the $jsonFileName attribute in the constructor
 */
abstract class BackendFormRoute extends BackendRoute {

    public function __construct($slug, $jsonFileName) {
        parent::__construct();
        $this->slug = $slug;
        $this->jsonFileName = $jsonFileName;
        $this->jsonTableName = "forms";
    }

    public string $jsonFileName;

    /**
     * The field name that should be viewed. Will only be set when viewing an entry
     * @var int|null
     */
    protected $fieldToBeViewed;

    protected function processDataBeforeSaving(array $values): array {
        return $values;
    }

    /** MATCHING */

    public function matches(string $route): bool {
        $this->route = $route;
        return in_array($route, [
            BACKEND_PREFIX . "/" . $this->slug,
            BACKEND_PREFIX . "/" . $this->slug . "/"
        ]) || $this->matchesAjax($route) || $this->matchesView($route);
    }

    function matchesAjax($route): bool {
        return in_array($route, [
            BACKEND_PREFIX . "/" . $this->slug . "/ajax",
            BACKEND_PREFIX . "/" . $this->slug . "ajax/"
        ]);
    }

    private function matchesView($route): bool {
        $doesMatch = preg_match("|" . BACKEND_PREFIX . "/" .$this->slug . "/view/([^/]+)|", $route, $matches);
        if ($doesMatch) {
            $this->fieldToBeViewed = $matches[1];
            return true;
        }
        return false;
    }

    protected function shouldRenderTemplate(): bool {
        if ($this->matchesAjax($this->route)) {
            BackendTableAjaxHelper::handleAjaxForm($this);
            return false;
        }
        if ($this->matchesView($this->route)) {
            $this->renderView();
            return false;
        }
        return parent::shouldRenderTemplate();
    }

    /** FORM */

    function renderBackend(string $route) {
        if ($_SERVER['REQUEST_METHOD'] == "POST" && array_key_exists("form", $_POST) && $_POST['form'] == $this->jsonFileName) {
            $this->saveData();
        } else {
            $this->renderForm();
        }
    }

    /**
     * renders the view mode of a single field of a single entry by calling [viewRaw] on the field's [DataType]
     * useful e.g. for data types like file or image to show the file contents
     * @link DataType::viewRaw()
     */
    protected function renderView() {
        $json = $this->loadData($this->jsonFileName);
        foreach ($json['fields'] as $field) {
            if ($field['name'] == $this->fieldToBeViewed) {
                $datatype = $this->getDataType($field);
                $value = Storage::getInstance()->get($field["name"]);
                $datatype->viewRaw($value);
                return;
            }
        }
    }

    function saveData() {
        $values = $this->getValueArray($this->jsonFileName, null, Storage::getInstance()->getAll(false));
        $values = $this->processDataBeforeSaving($values);
        Storage::getInstance()->setAll($values);

        array_push($_SESSION['messages'], "Einstellungen erfolgreich gespeichert");
        $this->renderForm();
    }

    function renderForm() {
        $storage = Storage::getInstance();

        $data = $this->loadData($this->jsonFileName);
        $label = array_key_exists("label", $data) ? $data['label'] : (array_key_exists("title", $data) ? $data['title'] : "Eintrag");

        $this->renderTemplate("admin-general-edit.html", array(
            "label" => $label,
            "id" => true,
            "form" => $this->generateFormHtml($this->jsonFileName, $storage->getAll(false)),
            "ajaxtarget" => BACKEND_PREFIX . "/" . $this->slug . "/ajax",
        ));
    }
}