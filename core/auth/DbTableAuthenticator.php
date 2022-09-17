<?php

/**
 * Authenticator that uses two DB tables (user and authToken) for user verification
 * Usage in setup.php: `Authenticator::set(new DbTableAuthenticator("users")` where the constructor argument is the user table name
 * Per default, this class
 * - requires $additionalParams["name"] during registration (override [checkRegistrationParameters] if that's not desired)
 * - The $userIdentifier is treated as an email address and also checked for validity (override [checkRegistrationParameters] if that's not desired)
 *
 * This authenticator works with the following tables:
 *
 * CREATE TABLE `users` (
 *   `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
 *   `email` varchar(255) NOT NULL,
 *   `password` varchar(255) NOT NULL,
 *   `name` varchar(255) NOT NULL,
 *   `role` varchar(20) NOT NULL
 *   ) ENGINE=InnoDB;
 *
 * CREATE TABLE `authToken` (
 *    `token` char(23) NOT NULL PRIMARY KEY,
 *    `user` int(11) NOT NULL,
 *    `expiry` int(11) NOT NULL
 *    ) ENGINE=InnoDB;
 */
class DbTableAuthenticator extends Authenticator {

    protected $userTableName = "users";
    protected $authTokenTableName = "authToken";
    protected $userIdentifierField = "email";
    protected $authTokenTableUserIdField = "user";

    public function __construct(
        string $userTableName = "users",
        string $userIdentifierField = "email",
        string $authTokenTableName = "authToken",
        string $authTokenTableUserIdField = "user"
    ) {
        $this->userTableName = $userTableName;
        $this->userIdentifierField = $userIdentifierField;
        $this->authTokenTableName = $authTokenTableName;
        $this->authTokenTableUserIdField = $authTokenTableUserIdField;
    }

    public function isAuthenticated(): bool {
        return self::ensureUser();
    }

    public function isAdmin(): bool {
        return self::ensureUser() && $GLOBALS['user']['role'] === 'Administrator';
    }

    protected function ensureUser(): bool {
        if (!array_key_exists('user', $GLOBALS)) {
            if (!array_key_exists('authToken', $_COOKIE)) return false;
            $authToken = $_COOKIE['authToken'];
            $user = $this->getUserByToken($authToken);
            if (!is_array($user)) return false;
            $GLOBALS['user'] = $user;
        }
        return true;
    }

    public function verifyPassword(string $userIdentifier, string $password): bool {
        $result = DB::queryArray($this->getVerifyPasswordSql($userIdentifier));
        if (!$result || count($result) == 0) {
            return false;
        }
        if (!password_verify($password, $result[0]['password'])) {
            return false;
        }
        return true;
    }

    public function login(string $userIdentifier, string $password): bool {
        $result = DB::queryArray($this->getVerifyPasswordSql($userIdentifier));
        if (!$result || count($result) == 0) {
            return false;
        }
        if (!password_verify($password, $result[0]['password'])) {
            return false;
        }
        $expiry = time() + 86400 * 100;
        $authToken = uniqid("", true);
        DB::insert($this->authTokenTableName, array("token" => $authToken, $this->authTokenTableUserIdField => $result[0]['id'], "expiry" => $expiry));
        setcookie("authToken", $authToken, $expiry, BASEURL);

        $GLOBALS['user'] = $result[0];
        return true;
    }

    public function logout() {
        $GLOBALS['user'] = null;
        $authToken = $_COOKIE['authToken'];
        if ($authToken) {
            DB::query("DELETE FROM ".$this->authTokenTableName." WHERE token = '" . DB::escape($authToken) . "'");
            setcookie("authToken", "expired", time() - 86401, BASEURL);
            unset($_COOKIE['authToken']);
        }
    }

    public function changePassword($currentPw, $newPw): bool {
        if (!self::isAuthenticated()) {
            return false;
        }
        if (!$this->verifyPassword($GLOBALS['user'][$this->userIdentifierField], $currentPw)) {
            array_push($_SESSION['errors'], "Aktuelles Passwort falsch.");
            return false;
        }
        if (strlen($newPw) < 7) {
            array_push($_SESSION['errors'], "Das gewählte Passwort ist zu kurz. Verwenden Sie mindestens 7 Zeichen.");
            return false;
        }
        return (boolean)DB::query("UPDATE " . $this->userTableName . " SET password = '" . password_hash($newPw, PASSWORD_BCRYPT) . "' WHERE id = " . $GLOBALS['user']['id']);
    }

    public function register($userIdentifier, $password, array $additionalParams): bool {
        if (!$this->checkRegistrationParameters($userIdentifier, $additionalParams)) {
            return false;
        }

        $result = DB::queryArray("SELECT id FROM " . $this->userTableName . " WHERE " . $this->userIdentifierField . " = '" . DB::escape($userIdentifier) . "'");

        if (is_array($result) && count($result) > 0) {
            array_push($_SESSION['errors'], "E-Mail-Adresse bereits registriert.");
            return false;
        }
        DB::insert($this->userTableName, array_merge($additionalParams, array(
            $this->userIdentifierField => $userIdentifier,
            "password" => password_hash($password, PASSWORD_BCRYPT)
        )));
        return true;
    }

    public function updateCachedUser() {
        unset($GLOBALS['user']);
        self::ensureUser();
    }

    function getUserDetails(): array {
        $this->ensureUser();
        return $GLOBALS["user"];
    }

    protected function getVerifyPasswordSql(string $userIdentifier): string {
        return "SELECT * FROM " . $this->userTableName . " WHERE " . $this->userIdentifierField . " = '" . DB::escape(trim(strtolower($userIdentifier))) . "'";
    }

    protected function checkRegistrationParameters($userIdentifier, $additionalParams): bool {
        if (strlen($additionalParams["name"]) < 2) {
            array_push($_SESSION['errors'], "Bitte geben Sie einen Namen an");
            return false;
        }
        if (!filter_var($userIdentifier, FILTER_VALIDATE_EMAIL)) {
            array_push($_SESSION['errors'], "E-Mail-Adresse ungültig.");
            return false;
        }

        return true;
    }

    protected function getUserByToken($authToken) {
        $tokenUser = DB::queryArray("SELECT * FROM ".$this->authTokenTableName." WHERE token = '" . DB::escape($authToken) . "' AND expiry > " . time());
        if (count($tokenUser) != 1) return null;

        $user = DB::queryArray("SELECT * from ".$this->userTableName." WHERE id = " . $tokenUser[0][$this->authTokenTableUserIdField]);
        if (count($user) != 1) return null;

        return $user[0];
    }
}