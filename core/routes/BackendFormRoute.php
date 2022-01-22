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

    public $slug;

    public $jsonFileName;

    protected function processDataBeforeSaving(array $values): array {
        return $values;
    }

    /** MATCHING */

    public function matches($route): bool {
        $this->route = $route;
        return in_array($route, [
            BACKEND_PREFIX . "/" . $this->slug,
            BACKEND_PREFIX . "/" . $this->slug . "/"
        ]) || $this->matchesAjax($route);
    }

    function matchesAjax($route): bool {
        return in_array($route, [
            BACKEND_PREFIX . "/" . $this->slug . "/ajax",
            BACKEND_PREFIX . "/" . $this->slug . "ajax/"
        ]);
    }

    protected function shouldRenderTemplate(): bool {
        if ($this->matchesAjax($this->route)) {
            BackendTableAjaxHelper::handleAjaxForm($this);
            return false;
        }
        return parent::shouldRenderTemplate();
    }

    /** FORM */

    function renderBackend($route) {
        if ($_SERVER['REQUEST_METHOD'] == "POST" && array_key_exists("form", $_POST) && $_POST['form'] == $this->jsonFileName) {
            $this->saveData();
        } else {
            $this->renderForm();
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