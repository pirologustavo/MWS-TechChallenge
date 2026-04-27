<?php

namespace App\Seguranca\Application;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthService
{
    public static function authenticate()
    {
        $token = $_COOKIE['token'] ?? null;

        if (!$token) return null;

        try {
            $decoded = JWT::decode($token, new Key($_ENV['JWT_KEY'], 'HS256'));
            return (bool) $decoded;
        } catch (\Exception $e) {
            return false;
        }
    }
}