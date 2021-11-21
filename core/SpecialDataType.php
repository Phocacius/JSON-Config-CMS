<?php

/**
 * DataTypes implementing this interface will not be saved in the database or shown in an edit form
 * Instead, during rendering, the [processSpecialField] method within [Route] will be called
 * allowing for custom behaviour like e.g. showing dependencies
 * @see Route::processSpecialField()
 */
interface SpecialDataType {
    public function renderBackendTableSpecial($allValues): string;
}