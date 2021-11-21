<?php

class ErrorUtils {
    static function parseException(Exception $e) {
        $file = str_replace(realpath(__DIR__.'/../..'), "", $e->getFile());
        array_push($_SESSION['errors'], $e->getMessage()." in ". $file ." #".$e->getLine());
    }
}