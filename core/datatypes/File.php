<?php

/**
 * References a file somewhere on the filesystem
 *
 * Mandatory configuration:
 * - storageDirectory (string) : the directory where the files are going to be stored. Either absolute (when starting with a /) or relative to the root directory of the php installation (where the index.php resides).
 *
 * Optional configuration:
 * - keepFileNames (boolean): if set to true, the original file name during upload gets preserved, otherwise a random name is chosen when uploading. Default to true
 * - allowOverwrite (boolean): if set to true, files with the same filename will be overwritten. If set to false, a timestamp will be added after the filename for disambiguation. Only relevant when [keepFileNames] is set to true. Defaults to false
 */
class File extends DataType {

    private $storageDir;

    public function __construct($config) {
        parent::__construct($config);
        if (!array_key_exists("storageDirectory", $config)) {
            throw new Exception("Missing storage directory for file field!");
        }

        $dir = $config['storageDirectory'];
        if (substr($dir, 0, 1) !== '/') {
            if (defined('DOCUMENT_ROOT')) {
                $dir = DOCUMENT_ROOT . $dir;
            }
            if (substr($dir, 0, 1) !== '/') {
                $dir = getcwd() . "/" . $dir;
            }
        }
        if (substr($dir, -1) !== '/') {
            $dir .= "/";
        }
        $this->storageDir = $dir;
    }

    function renderBackendForm(): string {
        $output = $this->renderBackendTable($this->value);
        if ($this->value) $output .= "<div><a class='image__delete' href='#' data-field='" . $this->name . "'>Datei löschen</a></div>";

        $required = $this->required ? "required" : "";
        return $output . "<input class=\"form-control\" type=\"file\" id=\"input-$this->name\" name=\"$this->name\" value=\"$this->value\" $required>\n";
    }

    public function renderBackendTable($value): string {
        if (!$value) {
            return "<div style='font-size: 10pt;'>Keine Datei ausgewählt</div>\n";
        }

        $filepath = $this->storageDir . $value;
        $output = "<div>" . $value;
        if (!file_exists($filepath)) {
            $output .= " (fehlend)";
        } elseif($this->parentRoute instanceof BackendTableRoute) {
            $output .= " (" . FileUtils::formattedFileSize($filepath) . ") <a href=\"" . BASEURL . (defined('BACKEND_PREFIX') ? BACKEND_PREFIX : '') . "/" . $this->parentRoute->slug . "/" . $this->parentRoute->id . "/view/" . $this->name . "\" target=\"_blank\">💾</a>";
        }
        return "</div>" . $output;
    }

    public function processValue($value, bool $checkForUploadedFile = true) {
        if (is_array($value) && array_key_exists("tmp_name", $value) && !$value['tmp_name']) return $this->value;
        if (!$value) return $this->value;

        if (is_string($value)) {
            $value = [
                "tmp_name" => realpath($value),
                "error" => null,
                "type" => mime_content_type($value),
                "name" => basename($value)
            ];
        }

        if ($value['error']) {
            return null;
        }

        $keepFileNames = $this->readBooleanConfigValue("keepFileNames", true);
        $allowOverwrite = $this->readBooleanConfigValue("allowOverwrite");

        $pathinfo = pathinfo($value['name']);
        if ($keepFileNames) {
            $filename = $value['name'];
        } else {
            $filename = FileUtils::generateRandomString() . "." . $pathinfo['extension'];
        }

        if (file_exists($this->storageDir . $filename) && !$allowOverwrite) {
            $filename = $pathinfo['filename'] . "_" . time() . "." . $pathinfo['extension'];
        }

        if($checkForUploadedFile) {
            move_uploaded_file($value['tmp_name'], $this->storageDir . $filename);
        } else {
            rename($value['tmp_name'], $this->storageDir . $filename);
        }

        return $filename;
    }

    function viewRaw($value) {
        $filename = $this->storageDir . $value;
        FileUtils::passThroughFile($filename, $value);
    }
}
