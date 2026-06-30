<?php

namespace App\Seguranca\Application;

use GuzzleHttp\Client;

class AuthService
{
    public static function authenticate(): bool
    {
        $token = $_COOKIE['token'] ?? null;

        if (!$token) {
            return false;
        }

        $client = new Client(['base_uri' => 'http://funcionario-lv/']);

        try {
            // Dispara uma requisição teste usando o token do cookie no Header Bearer
            $response = $client->get('api/funcionario', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept'        => 'application/json',
                ]
            ]);

            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            // Se a API retornar 401 (token expirado/inválido), limpa o cookie e barra o acesso
            setcookie("token", "", time() - 3600, "/");
            return false;
        }
    }
}