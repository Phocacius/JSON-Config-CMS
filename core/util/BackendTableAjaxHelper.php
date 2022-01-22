<?php

class BackendTableAjaxHelper {
    public static function handleAjax(BackendTableRoute $route) {
        $action = $_POST['action'];
        switch ($action) {
            case "set":
                $id = (int)$_POST['id'];
                $field = DB::escape($_POST['field']);
                $value = DB::escape($_POST['value']);
                DB::query("UPDATE $route->tableName SET $field = '$value' WHERE id = $id");
                echo '{"message": "Eintrag aktualisiert."}';
                break;
            case "delete":
                $id = $_POST['id'];
                DB::delete($route->tableName, $id);
                echo '{"message": "Eintrag gelöscht."}';
                break;
            case "updateSorting":
                $updates = $_POST['updates'];
                $decoded = json_decode($updates, true);
                $json = $route->loadData($route->tableName);
                $sortingFieldIndex = array_search("sortorder", array_column($json['fields'], 'type'));
                if ($sortingFieldIndex === false) {
                    http_response_code(400);
                    echo '{"message": "Kein Sortierungsfeld vorhanden."}';
                    break;
                }
                $sortingField = $json['fields'][$sortingFieldIndex]['name'];

                foreach ($decoded as $pair) {
                    $id = (int)$pair['id'];
                    $index = (int)$pair['index'];
                    DB::query("UPDATE $route->tableName SET $sortingField = '$index' WHERE id = $id");
                }
                echo '{"message": "Sortierung gespeichert."}';
                break;
            default:
                http_response_code(400);
                echo '{"message": "Aktion nicht bekannt oder nicht angegeben."}';
        }
    }

    public static function handleAjaxForm(BackendFormRoute $route) {
        $action = $_POST['action'];
        switch ($action) {
            case "set":
                $field = DB::escape($_POST['field']);
                $value = DB::escape($_POST['value']);
                Storage::getInstance()->set($field, $value, true);
                echo '{"message": "Eintrag aktualisiert"}';
                break;
            case "delete":
                $field = DB::escape($_POST['field']);
                Storage::getInstance()->set($field, null, true);
                echo '{"message": "Eintrag gelöscht."}';
                break;
            default:
                http_response_code(400);
                echo '{"message": "Aktion nicht bekannt oder nicht angegeben."}';
        }
    }
}
