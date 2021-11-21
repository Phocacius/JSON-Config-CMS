<?php

/**
 * Authenticator that uses an external service for logging in and one local DB tables (authToken) for user verification
 * Usage in setup.php: `Authenticator::set(new ExternalAuthenticator($urls)` where the constructor argument is an array
 * with the keys "change_password" and "login" containing urls to the corresponding functions relative to the
 * constant BACKEND_URL that should be defined.
 */
class ExternalAuthenticator extends DbTableAuthenticator {

    private $urls = [
        "change_password" => "/user/password",
        "login" => "/auth/login",
    ];

    public function __construct(
        array $urls = null,
        string $userTableName = "users",
        string $userIdentifierField = "email"
    ) {
        parent::__construct($userTableName, $userIdentifierField);
        if ($urls !== null) $this->urls = $urls;
    }

    public function isAdmin(): bool {
        return self::ensureUser() && $GLOBALS['user']['isAdmin'] == '1';
    }


    public function verifyPassword($userIdentifier, $password): bool {
        $authToken = $this->createAuthToken($userIdentifier, $password);
        if (!$authToken) return false;

        DB::query("DELETE FROM authToken WHERE token = '" . DB::escape($authToken) . "'");
        return true;
    }

    public function login($userIdentifier, $password): bool {
        $authToken = $this->createAuthToken($userIdentifier, $password);
        if (!$authToken) return false;

        $expiry = time() + 86400 * 100;
        setcookie("authToken", $authToken, $expiry, BASEURL);
        $GLOBALS['user'] = $this->getUserByToken($authToken);
        return true;
    }

    public function changePassword($currentPw, $newPw): bool {
        $opts = array(
            'http' => array(
                'ignore_errors' => true,
                'method' => "POST",
                'header' => ['Content-Type: application/json; charset=UTF-8', 'Accept: application/json', 'Authorization: Token ' . $_COOKIE['authToken']],
                'content' => json_encode(['oldPassword' => $currentPw, 'password' => $newPw])
            )
        );
        $context = stream_context_create($opts);
        $message = @file_get_contents(BACKEND_URL . $this->urls["change_password"], false, $context);
        if (substr($http_response_header[0], 9, 3) == "200") return true;

        array_push($_SESSION['errors'], json_decode($message, true)['message']);
        return false;
    }

    private function createAuthToken(string $email, string $password) {
        $opts = array(
            'http' => array(
                'method' => "POST",
                'header' => ['Content-Type: application/json; charset=UTF-8', 'Accept: application/json'],
                'content' => json_encode(['email' => $email, 'password' => $password])
            )
        );
        $context = stream_context_create($opts);
        $verify = @file_get_contents(BACKEND_URL . $this->urls["login"], false, $context);
        return json_decode($verify, true)['token'];
    }

}