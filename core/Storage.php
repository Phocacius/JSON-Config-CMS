<?php

/**
 * helper class to store tiny amounts of data that should persist across sessions
 * If custom placeholder replacements are needed, defined a constant `STORAGE_CLASS_NAME` 
 * with the name of a class that inherits from this class and override `replacePlaceholders` in it
 */
class Storage {
    const KEY_SITE_NAME = 'sitename';
    const KEY_MAPS_KEY = 'mapskey';

    private static $instance;

    public static function getInstance() {
        if (!self::$instance) {
            if (defined("STORAGE_CLASS_NAME")) {
                $className = STORAGE_CLASS_NAME;
                self::$instance = new $className();
            } else {
                self::$instance = new Storage();
            }
        }
        return self::$instance;
    }

    private $data;
    private $storage;

    private function __construct($storage = DOCUMENT_ROOT . 'global.json') {
        $this->storage = $storage;
        $fileContent = is_file($this->storage) ? file_get_contents($this->storage) : false;
        $this->data = $fileContent === false ? array() : json_decode($fileContent, true);
    }

    public function get($key, $default = null, $replacePlaceHolders = true) {
        $value = array_key_exists($key, $this->data) ? $this->data[$key] : $default;
        return $replacePlaceHolders ? $this->replacePlaceholders($value) : $value;
    }

    public function set($key, $value, $instantSave = false) {
        $this->data[$key] = $value;
        if ($instantSave) {
            $this->persist();
        }
    }

    public function setArray($key, $value, $instantSave = false) {
        $value = json_encode($value);
        $this->set($key, $value, $instantSave);
    }

    public function getArray($key, $default = "[]") {
        $key = $this->get($key, $default);
        return json_decode($key, true);
    }

    public function getAll($replacePlaceholders = true) {
        if (!$replacePlaceholders) return $this->data;
        $output = array();
        foreach ($this->data as $key => $value) {
            $output[$key] = $this->replacePlaceholders($value);
        }
        return $output;
    }

    public function persist() { file_put_contents($this->storage, json_encode($this->data, JSON_PRETTY_PRINT)); }

    public function setAll($values) {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
        $this->persist();
    }

    public function replacePlaceholders($value) {
        return str_replace(
            ["%BASEURL%", "%YEAR%"], 
            [BASEURL, date("Y")], 
            $value
        );
    }
}