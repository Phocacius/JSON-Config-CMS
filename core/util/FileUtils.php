<?php

class FileUtils {
    /**
     * removes a directory and recursively all its (file and directory) contents
     * @param string $dir
     */
    static function rrmdir(string $dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object))
                        self::rrmdir($dir . "/" . $object);
                    else
                        unlink($dir . "/" . $object);
                }
            }
            rmdir($dir);
        }
    }

    /**
     * Get the file size of a file on disk in a human readable way (i.e. returning KB, MB etc depending on the file size)
     * @param string $path
     * @param int $decimals (default 1) number of decimals to be displayed
     * @return string|null the formatted file size or `null` if the file doesn't exist
     */
    static function formattedFileSize(string $path, int $decimals = 1): ?string {
        if (!file_exists($path)) return null;
        $bytes = filesize($path);
        $sz = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = min(floor(log($bytes, 1024)), count($sz));
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
    }

    static function generateRandomString($length = 16) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    static function is_cli() {
        if (defined('STDIN')) return true;

        if (empty($_SERVER['REMOTE_ADDR']) and !isset($_SERVER['HTTP_USER_AGENT']) and count($_SERVER['argv']) > 0) {
            return true;
        }

        return false;
    }

    /**
     * Offer a file from the file system for the user to view (only for images, pdfs etc) or download
     * The program will respond with the status code 200, 304 Not Modified or 404 Not Found
     * @note the program will exit in any case after calling this function
     * @param string $path path to the file that should be passed through to the user
     * @param string|null $filename the filename that will be exposed to the user. Defaults to the file's name on filesystem if omitted
     * @param bool $forceDownload even viewable files like images will be downloaded to the user's computer instead of viewed in the browser
     * @return void
     */
    static function passThroughFile(string $path, ?string $filename = null, bool $forceDownload = false) {
        if (!file_exists($path)) {
            http_response_code(404);
            exit;
        }

        $dateFormat = 'D, d M Y H:i:s e';
        $serverLastModifiedTime = filemtime($path);
        $serverLastModifiedString = gmdate($dateFormat, $serverLastModifiedTime) . ' GMT';

        if (!$forceDownload && array_key_exists("HTTP_IF_MODIFIED_SINCE", $_SERVER)) {

            $clientLastModifiedString = $_SERVER["HTTP_IF_MODIFIED_SINCE"];
            $clientLastModifiedTime = date_parse_from_format($clientLastModifiedString, $dateFormat);
            if($clientLastModifiedTime >= $serverLastModifiedTime) {
                http_response_code(304);
                header('Last-Modified: ' . $serverLastModifiedString);
                header('Cache-Control: no-cache');
                exit;
            }
        }

        $fp = fopen($path, 'rb');
        header("Content-Type: " . mime_content_type($path));
        header("Content-Length: " . filesize($path));
        header("Content-Disposition: ".($forceDownload ? "attachment" : "inline")."; filename=" . ($filename ?? basename($path)));
        header('Last-Modified: ' . $serverLastModifiedString);
        header('Cache-Control: no-cache');

        fpassthru($fp);
        exit;
    }
}