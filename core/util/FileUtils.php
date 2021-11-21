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
}