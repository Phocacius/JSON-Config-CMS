<?php

/**
 * Allows to enter a geographical location with lat/lon coordinates or by entering a location search
 * that will be resolved to coordinates by the Google service.
 * The field is stored as a json with keys `lat` and `lon` and requires a varchar database column.
 * To select the location on a Google Map, save your api key in the [Storage] under the key [Storage::KEY_MAPS_KEY]
 * @see Storage::KEY_MAPS_KEY
 *
 * Optional configuration:
 * - lat (double): initial latitude
 * - lon (double): initial longitude
 * - zoom (int): initial zoom level
 * - address-hint (string): hint for the locaiton search input field
 */
class GeoLocation extends DataType {

    public function registerScripts(ScriptLoader $scriptLoader) {
        $scriptLoader->addExternalScript("https://maps.googleapis.com/maps/api/js?key=".Storage::getInstance()->get(Storage::KEY_MAPS_KEY)."&amp;callback=initMap");
    }

    function renderBackendTable($value): string {
        $val = $value ? json_decode($value, true) : null;
        if($val === null) return "-";

        $lat = $val['lat'];
        $lon = $val['lon'];
        return abs($lat) . ($lat > 0 ? "N" : "S") ." / " . abs($lon) . ($lon > 0 ? "O" : "W");
    }

    function renderBackendForm(): string {
        $val = $this->value ? json_decode($this->value, true) : array();
        $lat = array_key_exists("lat", $val) ? $val['lat'] : (array_key_exists("lat", $this->config) ? $this->config['lat'] : 0);
        $lon = array_key_exists("lon", $val) ? $val['lon'] : (array_key_exists("lon", $this->config) ? $this->config['lon'] : 0);
        $value = json_encode(["lat" => $lat, "lon" => $lon]);
        $addressHint = array_key_exists("address-hint", $this->config) ? $this->config['address-hint'] : "";
        $zoom = array_key_exists("zoom", $this->config) ? $this->config['zoom'] : 13;

        return '
<div class="map-container">
    <input type="hidden" class="map-output-field" id="input-'.$this->name.'" name="'.$this->name.'" value="'.htmlentities($value).'">
    <div>Adresse suchen: 
    <div class="input-group" style="margin-bottom: 15px;"><input class="form-control map-search-field" data-address-hint="'.$addressHint.'" type="text"><div class="input-group-append"><button class="btn btn-outline-primary map-search-button">Suchen</button></div></div></div>
    <div class="googlemap" data-lat="'.$lat.'" data-lon="'.$lon.'" data-zoom="'.$zoom.'" data-marker="1"></div>
</div>';
    }

    public function processValue($value) {
        return $value;
    }

}