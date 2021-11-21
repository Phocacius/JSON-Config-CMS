<?php

/**
 * Class that handles everything related to authentication
 * Set the authenticator in setup.php using [Authenticator::set()]
 * You can use one of the following or extend from them:
 * @link DbTableAuthenticator Use a user and authToken table of the MySQL database
 * @link ExternalAuthenticator Store the authToken locally, but use an external login service
 */
abstract class Authenticator {

    /**
     * @var null|Authenticator
     */
    private static $instance;


    /**
     * Always use this global instance in your code when accessing an Authenticator
     * @return Authenticator
     */
    public static function getInstance(): Authenticator {
        if (self::$instance == null) {
            array_push($_SESSION['errors'], "No authenticator set. Call <code>Authenticator::set</code> in your setup.php file");
            self::$instance = new NoOpAuthenticator();
        }
        return self::$instance;
    }

    public static function set(Authenticator $auth) {
        self::$instance = $auth;
    }

    /**
     * checks whether a user is currently logged in independent on the user rights
     * @return bool
     */
    abstract public function isAuthenticated(): bool;

    /**
     * checks whether a user with admin rights is currently logged in
     * @return bool
     */
    abstract function isAdmin(): bool;

    /**
     * returns an associative array containing the user details of the currently logged in user
     * @return null|array
     */
    abstract function getUserDetails(): array;

    /**
     * checks whether a password is correct, but does not save the user login
     * @param string $userIdentifier might be email address
     * @param string $password
     * @return bool
     */
    abstract function verifyPassword(string $userIdentifier, string $password): bool;

    /**
     * tries to login a user and save the auth token (or similar) in a cookie
     * @param string $userIdentifier might be email address
     * @param string $password
     * @return bool true if the login was successful
     */
    abstract function login(string $userIdentifier, string $password): bool;

    /**
     * registers a new user. AdditionalParams may contain user name, address etc.
     * @param $userIdentifier
     * @param $password
     * @param array $additionalParams
     * @return bool
     */
    abstract function register($userIdentifier, $password, array $additionalParams): bool;

    /**
     * clears the user's session and cookies
     * @return void
     */
    abstract function logout();

    /**
     * Change the password of the currently logged in user
     * @param $currentPw
     * @param $newPw
     * @return bool true if the password change worked
     */
    abstract function changePassword($currentPw, $newPw): bool;

    /**
     * called when the user model was modified externally
     */
    function updateCachedUser() {

    }

    public static function generatePassword($length = 10): string {
        $keyspace = "0123456789abcdefghijklmnopqrstuvwxzyABCDEFGHIJKLMNOPQRSTUVWXZY.-_,/*";
        $pieces = array();
        $max = mb_strlen($keyspace, '8bit') - 1;
        for ($i = 0; $i < $length; ++$i) {
            try {
                array_push($pieces, $keyspace[random_int(0, $max)]);
            } catch (Exception $e) {
                return uniqid();
            }
        }
        return implode('', $pieces);
    }

}