<?php

/**
 * Allows to upload an image and automatically scales it to customisable sizes
 *
 * Images will be saved on disk at `[IMG_ROOT]/[size]/[filename].[extension]` or `[DOCUMENT_ROOT]/img/[size]/[filename].[extension]`
 * `DOCUMENT_ROOT` and `DOCUMENT_ROOT` are constants defined in your `config.php`.
 * `IMG_ROOT` has a higher priority but is optional, `DOCUMENT_ROOT` will be used as fallback.
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
 * - sw[smallest-width]: scaled the image to a height or width of [smallest-width] pixels, whatever is smaller.
 *   The other dimension is adjusted to be at least as big as the [smallest-width], maintaining aspect ratio
 * - lw[largest-width]: scaled the image to a height or width of [largest-width] pixels, whatever is larger.
 *   The other dimension is adjusted to be at most as big as the [largest-width], maintaining aspect ratio
 * - [width]x[height] (e.g. 500x700): scales the image to a fixed pixel size. If the aspect ratio does not fit,
 *   the image will be center cropped to fit the given aspect ratio
 *
 * Note that when changing the configuration, you need to reupload all images, otherwise they won't be available in all sizes
 */
class Image extends DataType {

    function renderBackendForm(): string {
        $output = $this->renderBackendTable($this->value);
        if ($this->value && ($this->parentRoute instanceof BackendRoute)) {
            $output .= "<div><a href='" . $this->routeToView() . "?mode=overview' target='_blank'>Alle Größen anzeigen</a></div>";
        }
        if ($this->value) {
            $output .= "<div><a class='image__delete' href='#' data-field='" . $this->name . "'>Bild löschen</a></div>";
        }

        $required = $this->required ? "required" : "";
        return $output . "<input class=\"form-control\" type=\"file\" id=\"input-$this->name\" name=\"$this->name\" value=\"$this->value\" $required>\n";
    }

    public function renderBackendTable($value): string {
        $output = "";
        if (!$value) {
            $output .= "<div style='font-size: 10pt;'>Noch kein Bild ausgewählt</div>\n";
        } else {
            $output .= "<div><img style='max-width: 150px; max-height: 150px;' src='" . $this->routeToView() . "'></div>\n";
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

        $root = $this->getRootDir();
        if (!is_array($this->config['sizes']) || count($this->config['sizes']) == 0) {
            move_uploaded_file($value['tmp_name'], $root . $filename);
            return $filename;
        }

        $scaler = new ImageScaler($value['tmp_name']);
        foreach ($this->config['sizes'] as $size) {
            $oldFile = $root . $size . "/" . $this->value;
            if (is_file($oldFile)) unlink($oldFile);
            $scaler->scale($size, $root . $size . "/$filename");
        }

        return $filename;
    }

    function viewRaw($value) {
        $mode = array_key_exists("mode", $_GET) ? $_GET["mode"] : "single";
        if ($mode === "overview") {
            $this->showOverviewPage();
            return;
        }

        $root = $this->getRootDir();
        $size = array_key_exists("s", $_GET) ? str_replace("/", "", $_GET["s"]) : $this->config["sizes"][0];
        $filename = $root . $size . "/" . $value;

        FileUtils::passThroughFile($filename, $value);
    }

    /**
     * displays an overview page showing each size identifier and its corresponding picture
     * @return void
     */
    private function showOverviewPage() {
        foreach ($this->config["sizes"] as $size) {
            echo "<p>" . $size . "</p>";
            echo "<img src='?s=" . $size . "'>";
        }
    }

    /**
     * calculates the path for viewing the picture
     * when called from a FrontendRoute, this will link to the image file on the server's file system. Note that
     *   this only works, when the image storage location is accessible from the web server
     * when called from a BackendRoute, the file will be offered and cached by php, which allows the files to be anywhere
     *   in the file system. The link can be extended with ?mode=overview which will show a HTML page with all available
     *   resolution and ?s=resolutionString to show a specific resolution
     * @return string path
     */
    private function routeToView(): string {
        if ($this->parentRoute instanceof BackendRoute) {
            $route = BASEURL . BACKEND_PREFIX . "/" . $this->parentRoute->slug;
            if ($this->parentRoute instanceof BackendTableRoute) {
                $route .= "/" . $this->parentRoute->id;
            }
            return $route . "/view/" . $this->name;
        } else {
            return BASEURL . str_replace(getcwd(), "", $this->getRootDir()) . $this->config["sizes"][0] . "/" . $this->value;
        }

    }

    /**
     * gets the directory, where images are stored
     * @return string path
     */
    private function getRootDir(): string {
        return defined("IMG_ROOT") ? IMG_ROOT : (DOCUMENT_ROOT . "img/");
    }
}
