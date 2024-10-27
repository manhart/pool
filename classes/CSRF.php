<?php

declare(strict_types = 1);

use pool\classes\Core\Input\Session;
use pool\classes\Core\PoolObject;
use Random\RandomException;

/**
 * Class CSRF
 * This class provides methods to generate and validate CSRF tokens.
 */
class CSRF extends PoolObject
{
    /**
     * Generates a CSRF token and stores it in the session.
     * This method generates a random CSRF token using `random_bytes` and converts it to a hexadecimal string.
     * The generated token is then stored in the session under the key 'csrf_token'.
     *
     * @param Session $session The session object where the CSRF token will be stored.
     * @return string The generated CSRF token.
     * @throws RandomException If an appropriate source of randomness cannot be found.
     */
    public static function generateToken(Session $session): string
    {
        $token = bin2hex(random_bytes(42));
        $session->setVar('csrf_token', $token);
        return $token;
    }

    /**
     * Checks if the provided CSRF token matches the token stored in the session.
     * This method retrieves the CSRF token stored in the session and compares it with the provided token
     * using `hash_equals` to prevent timing attacks.
     *
     * @param Session $session The session object where the CSRF token is stored.
     * @param string $token The CSRF token to be checked.
     * @return bool Returns true if the tokens match, false otherwise.
     */
    public static function checkToken(Session $session, string $token): bool
    {
        $sessionToken = $session->getVar('csrf_token');
        if (isset($sessionToken) && hash_equals($sessionToken, $token)) {
            return true;
        }
        return false;
    }
}