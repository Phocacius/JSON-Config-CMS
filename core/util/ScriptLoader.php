<?php

/**
 * Helper class that collects all external JS files that are needed for a backend form
 * Each DataType can register a script, they will later be added on bulk
 * @link DataType ->registerScripts
 */
class ScriptLoader {
    private $scriptUrls = array();

    private $scripts = array();

    /** registers a required external script */
    public function addExternalScript($url) {
        if(!in_array($url, $this->scriptUrls)) {
            array_push($this->scriptUrls, $url);
        }
    }

    /** registers a local script, will be skipped if the identifier already exists */
    public function addScript($identifier, $code) {
        if(!array_key_exists($identifier, $this->scripts)) {
            $this->scripts[$identifier] = $code;
        }
    }

    public function generateCode() {

        $output = "";
        foreach ($this->scriptUrls as $url) {
            $output .= "\n".'<script type="text/javascript" src="'.$url.'"></script>';
        }

        if(count($this->scripts) !== 0) {
            $output .= '<script type="text/javascript">';
            $output .= implode("\n", $this->scripts);
            $output .= '</script>';
        }
        return $output;
    }
}