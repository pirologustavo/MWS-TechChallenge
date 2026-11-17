<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApprovalSignature
{
    public function handle(Request $request, Closure $next)
    {
        $secret = (string) config('services.os_approval_secret');
        $signature = (string) $request->header('X-Approval-Signature', $request->query('signature', ''));
        abort_unless($secret !== '' && hash_equals(hash_hmac('sha256', (string) $request->route('id'), $secret), $signature), 403, 'Assinatura inválida.');

        return $next($request);
    }
}
