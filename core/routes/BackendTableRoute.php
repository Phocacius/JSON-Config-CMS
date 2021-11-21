<?php

abstract class BackendTableRoute extends BackendRoute {

    public function __construct($slug, $tableName) {
        parent::__construct();
        $this->slug = $slug;
        $this->tableName = $tableName;
    }

    /** VARIABLES AND API  */

    /**
     * The url part this route is accessible from (e.g. https://your.page/users -> users will be the slug)
     * @var string
     */
    public $slug;

    /**
     * The table name within your mysql database this route should operate on
     * @var string
     */
    public $tableName;

    /**
     * The id of the currently active field. Will be set while editing or viewing an entry,
     * will be null when the table is displayed or while creating a new entry
     * @var int|null
     */
    public $id;

    /**
     * contains the complete route, e.g. /admin/users/1/edit
     * @var string
     */
    protected $route;

    /**
     * SQL that is added to all select queries. Could e.g. be used to set a default order by. Always add a space first.
     * @var string
     */
    protected $selectSqlSuffix = "";

    /**
     * The field name that should be viewwd. Will only be set when viewing an entry
     * @var int|null
     */
    protected $fieldToBeViewed;

    /**
     * Override this if you allow a zip upload on your table.
     * This method will be called when after a zip was uploaded and unzipped into a temporary folder
     * Do not output anything here, but add to the $SESSION["messages"] or $SESSION["errors"] array for feedback
     * @param string $folder path to a temporary folder
     */
    protected function processFolder(string $folder) { }

    /**
     * this method will be called before data is saved to the database after editing an entry
     * override this if you require custom processing of the values
     * This can e.g. be used to check that a json is properly formatted or to send (email) notifications about updates
     * @param array $values the data that is about to be saved
     * @param array|null $previousData the previously saved version of this entry. May be null for newly created entries
     * @return array the processed data that will be saved to the databae
     */
    protected function processDataBeforeSaving(array $values, ?array $previousData): array {
        return $values;
    }

    /**
     * number of entries that are loaded per page. Can be changed with the GET parameter `pageSize`
     * A value of 0 means there will never be pagination, no matter how many entries there are
     * @var int
     */
    protected $pageSize = 100;


    /** API END */

    public function shouldRenderTemplate(): bool {
        if ($this->matchesAjax($this->route)) {
            BackendTableAjaxHelper::handleAjax($this);
            return false;
        }
        if ($this->matchesView($this->route)) {
            $this->renderView();
            return false;
        }
        return true;
    }

    public function renderBackend($route) {
        if ($this->matchesForm($this->route)) $this->renderBackendForm();
        if ($this->matchesEdit($this->route)) $this->renderBackendEdit();
    }

    /** MATCHING */

    public function matches($route): bool {
        $this->route = $route;
        return $this->matchesForm($route) || $this->matchesEdit($route) || $this->matchesAjax($route) || $this->matchesView($route);
    }

    private function matchesForm($route): bool {
        return in_array($route, [
            BACKEND_PREFIX . "/" . $this->slug,
            BACKEND_PREFIX . "/" . $this->slug . "/"]);
    }

    private function matchesEdit($route): bool {
        $doesMatch = preg_match("|" . BACKEND_PREFIX . "/" . $this->slug . "/([^/]+)/edit|", $route, $matches);
        if ($doesMatch) {
            $this->id = $matches[1];
            return true;
        }
        return in_array($route, [
            BACKEND_PREFIX . "/" . $this->slug . "/create",
            BACKEND_PREFIX . "/" . $this->slug . "create/"
        ]);
    }

    private function matchesView($route): bool {
        $doesMatch = preg_match("|" . BACKEND_PREFIX . "/" .$this->slug . "/([^/]+)/view/([^/]+)|", $route, $matches);
        if ($doesMatch) {
            $this->id = $matches[1];
            $this->fieldToBeViewed = $matches[2];
            return true;
        }
        return false;
    }

    function matchesAjax($route): bool {
        return in_array($route, [
            BACKEND_PREFIX . "/" . $this->slug . "/ajax",
            BACKEND_PREFIX . "/" . $this->slug . "ajax/"
        ]);
    }


    /** FORM */

    /**
     * renders a html table displaying all entries in the table
     */
    function renderBackendForm() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'zipupload') {
            $this->handleZipUpload();
        }

        $json = $this->loadData($this->tableName);
        $sortingFieldIndex = array_search("sortorder", array_column($json['fields'], 'type'));
        $parentIndex = array_search("parent", array_column($json['fields'], 'type'));

        $canBeSorted = $sortingFieldIndex !== false;
        $hasHierarchy = $parentIndex !== false;

        $isPaginated = false;
        $paginationInfo = [];

        if ($canBeSorted) {
            // no pagination for sorted fields, before sorting would not work then
            $sortingFieldName = $json['fields'][$sortingFieldIndex]['name'];
            $entries = DB::queryArray("SELECT * FROM " . $this->tableName . " ORDER BY $sortingFieldName" . $this->selectSqlSuffix);
        } else {
            $pageSizeManual = array_key_exists("pageSize", $_GET);
            $pageSize = $pageSizeManual ? ((int)$_GET["pageSize"]) : $this->pageSize;
            $page = array_key_exists("page", $_GET) ? ((int)$_GET["page"]) : 1;
            $offset = $pageSize * ($page - 1);
            $limitSql = $pageSize == 0 ? "" : " LIMIT $pageSize OFFSET $offset";
            $orderBy = array_key_exists("orderBy", $json) ? " ORDER BY " . $json['orderBy'] : "";
            $entries = DB::queryArray("SELECT * FROM " . $this->tableName . $orderBy . $this->selectSqlSuffix . $limitSql);

            if ($pageSize != 0 && ($page > 1 || count($entries) == $pageSize)) {
                $isPaginated = true;
                $entryCount = DB::queryArray("SELECT COUNT(*) AS ct FROM " . $this->tableName . $this->selectSqlSuffix)[0]["ct"];
                $paginationInfo = [
                    "page" => $page,
                    "pageSize" => $pageSize,
                    "pageSizeManual" => $pageSizeManual,
                    "entryCount" => $entryCount,
                    "entryDisplayedFrom" => ($page-1) * $pageSize + 1,
                    "entryDisplayedTo" => ($page-1) * $pageSize + count($entries),
                    "pageCount" => ceil($entryCount / $pageSize)
                ];
            }
        }

        if ($hasHierarchy) {
            $parentFieldName = $json['fields'][$parentIndex]['name'];
            $entries = $this->resolveChildrenRecursive($entries, $parentFieldName);
        }

        $modelClasses = array();
        foreach ($json['fields'] as $model) {
            $modelClasses[$model['name']] = $this->getDataType($model);
        }

        $entries = $this->renderBackendTableEntriesRecursive($entries, $modelClasses);

        $this->renderTemplate("admin-general-form.html", array(
            "entries" => $entries,
            "ajaxtarget" => BACKEND_PREFIX . "/" . $this->slug . "/ajax",
            "slug" => $this->slug,
            "json" => $json,
            "isPaginated" => $isPaginated,
            "paginationInfo" => $paginationInfo,
            "sortable" => $canBeSorted,
            "adminurl" => BASEURL . BACKEND_PREFIX
        ));
    }


    /** EDIT */

    /**
     * check for request method and then calls [saveData] or [renderEditForm]
     * @link saveData
     * @link renderEditForm
     */
    function renderBackendEdit() {
        if ($_SERVER['REQUEST_METHOD'] == "POST") {
            try {
                $this->saveData();
            } catch (Exception $e) {
                if (DEBUG) echo "<pre>" . $e . "</pre>";
                array_push($_SESSION['errors'], $e->getMessage());
                $this->renderEditForm();
            }
        } else {
            $this->renderEditForm();
        }
    }

    /**
     * renders the view mode of a single field of a single entry by calling [viewRaw] on the field's [DataType]
     * useful e.g. for data types like file or image to show the file contents
     * @link DataType::viewRaw()
     */
    protected function renderView() {
        $json = $this->loadData($this->tableName);
        foreach ($json['fields'] as $field) {
            if ($field['name'] == $this->fieldToBeViewed) {
                $datatype = $this->getDataType($field);

                $value = DB::queryArray("SELECT $this->fieldToBeViewed FROM $this->tableName WHERE id = $this->id")[0][$this->fieldToBeViewed];

                $datatype->viewRaw($value);
                return;
            }
        }
    }

    /**
     * renders an html form to edit an existing or create a new entry of this table
     */
    private function renderEditForm() {
        if ($this->id && !is_numeric($this->id)) {
            array_push($_SESSION['errors'], "Ungültige ID");
            $this->redirectToForm();
            return;
        }

        $values = array();
        if ($this->id) {
            $result = DB::queryArray("SELECT * FROM " . $this->tableName . " WHERE id = $this->id");
            if (count($result) > 0) {
                $values = $result[0];
            } else {
                array_push($_SESSION['errors'], "Eintrag mit ID $this->id konnte nicht gefunden werden.");
                $this->redirectToForm();
                return;
            }
        }

        foreach ($values as $key => $value) {
            if (array_key_exists($key, $_POST)) $values[$key] = $_POST[$key];
        }

        $form = $this->generateFormHtml($this->tableName, $values);

        $data = $this->loadData($this->tableName);
        $label = array_key_exists("label", $data) ? $data['label'] : (array_key_exists("title", $data) ? $data['title'] : "Eintrag");

        $this->renderTemplate("admin-general-edit.html", array(
            "ajaxtarget" => $this->slug . "/ajax",
            "label" => $label,
            "id" => $this->id,
            "form" => $form
        ));
    }

    /**
     * Loads entry data from POST and saves it to the database
     * The value is always processed by the [DataType]
     * If additional custom processing is required for your route, override [processDataBeforeSaving]
     * @link DataType::processValue()
     * @link processDataBeforeSaving
     */
    protected function saveData() {
        $currentData = $this->id ? DB::queryArray("SELECT * FROM " . $this->tableName . " WHERE id = $this->id")[0] : null;
        $values = $this->getValueArray($this->tableName, null, $currentData);
        unset($values['id']);
        $values = $this->processDataBeforeSaving($values, $currentData);

        if ($_POST['id']) {
            if (DB::update($this->tableName, $_POST['id'], $values)) {
                array_push($_SESSION['messages'], "Eintrag erfolgreich aktualisiert.");
            }
        } else {
            if (DB::insert($this->tableName, $values)) {
                array_push($_SESSION['messages'], "Eintrag erfolgreich erstellt.");
            }
        }
        $this->redirectToForm();
    }

    /**
     * Creates a hierarchy of entries where only entries without parent will be in the main array,
     * children will be sorted recursively in `_children`
     * @param array $allEntries the complete entry list that should be brought into a hierarchy
     * @param string $parentFieldName the name of the parent id field
     * @param array|null $entry the entry whose children should be found. Use `null` for the top-most level (default)
     * @return array
     */
    public function resolveChildrenRecursive(array $allEntries, string $parentFieldName, ?array $entry = null): array {
        $id = $entry === null ? null : $entry['id'];
        $children = array_values(array_filter($allEntries, function ($val) use ($id, $parentFieldName) { return $val[$parentFieldName] === $id; }));
        for ($i = 0; $i < count($children); $i++) {
            $children[$i]['_children'] = $this->resolveChildrenRecursive($allEntries, $parentFieldName, $children[$i]);
        }
        return $children;
    }

    /**
     * redirects to the edit form
     * @param bool $silent if set to true, the
     * @see Router::redirect()
     * @see Router::redirectSilently()
     */
    protected function redirectToForm(bool $silent = false) {
        if ($silent) {
            Router::getInstance()->redirectSilently(BACKEND_PREFIX . "/" . $this->slug);
        } else {
            Router::getInstance()->redirect(BACKEND_PREFIX . "/" . $this->slug);
        }
    }

    /**
     * @param array $entries array of all entries in the table (each entry being an associative array). Can be multidimensional with children in `_children`
     * @param array $modelClasses associative array with field name as keys and corresponding [DataType] model classes as values
     * @return array
     */
    protected function renderBackendTableEntriesRecursive(array $entries, array $modelClasses): array {
        for ($i = 0; $i < count($entries); $i++) {
            foreach ($entries[$i] as $key => $value) {
                if ($key === "_children") {
                    $entries[$i][$key] = $this->renderBackendTableEntriesRecursive($entries[$i][$key], $modelClasses);
                } elseif (array_key_exists($key, $modelClasses)) {
                    $this->id = $entries[$i]["id"];
                    $entries[$i][$key] = $modelClasses[$key]->renderBackendTable($value);
                }
            }
            foreach ($modelClasses as $modelClass) {
                if ($modelClass instanceof SpecialDataType && $modelClass instanceof DataType) {
                    $entries[$i][$modelClass->name] = $modelClass->renderBackendTableSpecial($entries[$i]);
                }
            }
        }
        return $entries;
    }

    /**
     * Processes an uploaded zip file. It creates a temporary directory and unzips the contents in there
     * Then, [processFolder] is called
     * @link processFolder
     */
    private function handleZipUpload() {
        $filename = $_FILES['zipfile']['name'];
        $info = pathinfo($filename);

        if ($info['extension'] !== 'zip') {
            array_push($_SESSION['errors'], "Die hochgeladene Datei ist kein ZIP-Archiv.");
            return;
        }

        $tmpDir = defined("TMP_DIR") ? TMP_DIR : DOCUMENT_ROOT . "tmp/" . uniqid();
        mkdir($tmpDir, 0777, true);

        $tmpFile = $_FILES['zipfile']['tmp_name'];
        $zip = new ZipArchive;
        if ($zip->open($tmpFile) !== TRUE) {
            unlink($tmpFile);
            rmdir($tmpDir);
            array_push($_SESSION['errors'], "Die hochgeladene Datei kann nicht als ZIP-Archiv geöffnet werden.");
            return;
        }
        $zip->extractTo($tmpDir);
        $zip->close();

        try {
            $this->processFolder($tmpDir);
        } catch (Exception $e) {
            ErrorUtils::parseException($e);
        }

        FileUtils::rrmdir($tmpDir);
    }

}