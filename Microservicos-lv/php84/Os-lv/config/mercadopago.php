<?php

return [
    'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
    'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET'),
    'base_url' => env('MERCADOPAGO_BASE_URL', 'https://api.mercadopago.com'),
];
