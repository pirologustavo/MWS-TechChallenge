<?php

namespace App\Billing;

final class WebhookSignature
{
    public function valid(string $signature, string $requestId, string $id, string $secret): bool
    {
        if ($secret === '' || $requestId === '' || ! ctype_digit($id)) {
            return false;
        }
        $parts = [];
        foreach (explode(',', $signature) as $part) {
            $pair = explode('=', trim($part), 2);
            if (count($pair) === 2) {
                $parts[$pair[0]] = $pair[1];
            }
        }
        if (! ctype_digit($parts['ts'] ?? '') || ! isset($parts['v1'])) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', "id:{$id};request-id:{$requestId};ts:{$parts['ts']};", $secret), $parts['v1']);
    }
}
