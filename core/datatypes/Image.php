<?php

/**
 * Allows to upload an image and automatically scales it to customisable sizes
 *
 * Images will be saved on disk at `[DOCUMENT_ROOT]/img/[size]/[filename].[extension]`
 * `DOCUMENT_ROOT` is a constant that should be defined in your `config.php`.
 * `size` is the scaleString that was used to scale this image (see below)
 * `filename` is the original upload filename followed by an underscore and the upload timestamp (to avoid duplicates)
 * The database column should be varchar and contains just filename and extension
 *
 * Required configuration:
 * - `sizes` (array<string>): An array of sizes this image should be scaled to.
 * The following formats are supported:
 * - [size] (e.g. 500): scales the image to a square of [size] pixels. If the aspect ratio does not fit,
 *   the image will be center cropped to fit a square
 * - [width]x (e.g. 500x): scales the image to a width of [width] pixels. The height is adjusted to maintain
 *   the image's aspect ratio
 * - x[height] (e.g. x500): scales the image to a height of [width] pixels. The width is adjusted to maintain
 *   the image's aspect ratio
 * - [width]x[height] (e.g. 500x700): scales the image to a fixed pixel size. If the aspect ratio does not fit,
 *   the image will be center cropped to fit the given aspect ratio
 *
 * Note that when changing the configuration, you need to reupload all images, otherwise they won't be available in all sizes
 */
class Image extends DataType {

    function renderBackendForm(): string {
        $output = $this->renderBackendTable($this->value);
        if ($this->value) $output .= "<div><a class='image__delete' href='#' data-field='" . $this->name . "'>Bild löschen</a></div>";

        $required = $this->required ? "required" : "";
        return $output . "<input class=\"form-control\" type=\"file\" id=\"input-$this->name\" name=\"$this->name\" value=\"$this->value\" $required>\n";
    }

    public function renderBackendTable($value): string {
        $output = "";
        if (!$value) {
            $output .= "<div style='font-size: 10pt;'>Noch kein Bild ausgewählt</div>\n";
        } else {
            $dataDir = BASEURL . "/data/img/";
            if (is_array($this->config['sizes']) && count($this->config['sizes']) > 0) {
                $dataDir .= $this->config['sizes'][0] . "/";
            }
            $output .= "<div><img style='max-width: 150px; max-height: 150px;' src='" . $dataDir . $value . "'></div>\n";
        }

        return $output;
    }

    public function processValue($value) {
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

        $ext = null;
        switch ($value['type']) {
            case "image/jpeg":
                $ext = "jpg";
                break;
            case "image/png":
                $ext = "png";
                break;
            case "image/gif":
                $ext = "gif";
                break;
        }

        if (!$ext) throw new Exception("Ungültige Bild-Datei. Erlaubt sind jpg, png und gif-Bilder.");

        $filename = pathinfo($value['name'])['filename'] . "_" . time() . "." . $ext;

        if (!is_array($this->config['sizes']) || count($this->config['sizes']) == 0) {
            move_uploaded_file($value['tmp_name'], DOCUMENT_ROOT . "img/" . $filename);
            return $filename;
        }

        $scaler = new ImageScaler($value['tmp_name']);
        foreach ($this->config['sizes'] as $size) {
            $oldFile = DOCUMENT_ROOT . "img/" . $size . "/" . $this->value;
            if (is_file($oldFile)) unlink($oldFile);
            $scaler->scale($size, DOCUMENT_ROOT . "img/" . $size . "/$filename");
        }

        return $filename;
    }
}
