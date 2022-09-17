<?php

/**
 * A base class that can be used for a backend page that represents a single database table and is assigned
 * to a specific entry within another table (e.g. a picture within a gallery)
 */
abstract class BackendSubTableRoute extends BackendTableRoute {

    private $parentTable;
    private $refColumnName;
    private $parentSlug;
    private $parentId;

    public function __construct($slug, $tableName, $parentTable, $parentTableRefColumnName, $parentSlug) {
        parent::__construct($slug, $tableName);
        $this->parentTable = $parentTable;
        $this->refColumnName = $parentTableRefColumnName;
        $this->parentSlug = $parentSlug;
    }

    protected function getTitleFromParentEntry($entry) {
        if (array_key_exists("title", $entry)) {
            return $entry["title"];
        }
        if (array_key_exists("name", $entry)) {
            return $entry["name"];
        }
        return $entry["id"];
    }

    protected function getDefaultValues(): array {
        if (array_key_exists("id", $_GET)) {
            return [$this->refColumnName => (int)$_GET['id']];
        }
        return parent::getDefaultValues();
    }

    private function requireParentId() {
        if (array_key_exists("id", $_GET)) {
            $this->parentId = (int)$_GET['id'];
        }

        if (!$this->parentId) {
            echo "Invalid id";
            exit;
        }
    }

    protected function redirectToForm(bool $silent = false) {
        if ($silent) {
            Router::getInstance()->redirectSilently(BACKEND_PREFIX . "/" . $this->slug . "?id=" . $this->parentId);
        } else {
            Router::getInstance()->redirect(BACKEND_PREFIX . "/" . $this->slug . "?id=" . $this->parentId);
        }
    }

    public function getWhereClausesForBackendEntries(): array {
        $this->requireParentId();
        return [$this->refColumnName." = ".$this->parentId];
    }

    public function getDataForBackendForm(): array {
        $this->requireParentId();
        $parentData = parent::getDataForBackendForm();

        $parentEntry = DB::queryArray("SELECT * FROM " . $this->parentTable . " WHERE id = $this->parentId")[0];
        $parentData["json"]['title'] .= " für \"" . $this->getTitleFromParentEntry($parentEntry) . "\"";

        $parentData["titlePrefix"] = "<a href='" . BASEURL . BACKEND_PREFIX . "/" . $this->parentSlug . "'>&larr;</a> ";
        $parentData["createargs"] = "?id=" . $this->parentId;
        return $parentData;
    }

    protected function processDataBeforeSaving(array $values, ?array $previousData = null): array {
        if (array_key_exists("id", $_GET)) {
            $id = (int)$_GET['id'];
            $values[$this->refColumnName] = $id;
        }

        $this->parentId = $values[$this->refColumnName];

        if (!array_key_exists($this->refColumnName, $values) || ((int)$values[$this->refColumnName]) < 1) {
            throw new Exception("Dieser Eintrag darf nicht für sich alleine stehen.");
        }

        return $values;
    }

}