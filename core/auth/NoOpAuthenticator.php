<?php

/**
 * Empty fallback authenticator when no authenticator is set. Implement an Authenticator or use/override one of
 * @link DbTableAuthenticator
 * @link ExternalAuthenticator
 */
class NoOpAuthenticator extends Authenticator {

    public function isAuthenticated(): bool {
        return false;
    }

    function isAdmin(): bool {
        return false;
    }

    function getUserDetails(): array {
        return [];
    }

    function verifyPassword(string $userIdentifier, string $password): bool {
        return false;
    }

    function login(string $userIdentifier, string $password): bool {
        return false;
    }

    function register($userIdentifier, $password, array $additionalParams): bool {
        return false;
    }

    function logout() {

    }

    function changePassword($currentPw, $newPw): bool {
        return false;
    }
}
