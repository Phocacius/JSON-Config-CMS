<?php

/**
 * A generic [SpecialDataType] that will not be saved in the database or shown in an edit form
 * Instead, during rendering, the [processSpecialField] method within [Route] will be called
 * allowing for custom behaviour like e.g. showing dependencies
 * @see SpecialDataType
 * @see Route::processSpecialField()
 */
class Special extends DataType implements SpecialDataType {

    public function __construct($config) {
        parent::__construct($config);
        $this->saveToDb = false;
    }

    function renderBackendForm(): string {
        return "";
    }

    public function renderBackendTable($value): string {
        return "";
    }

    public function renderBackendTableSpecial($allValues): string {
        return $this->parentRoute->processSpecialField($this->name, $allValues);
    }
}