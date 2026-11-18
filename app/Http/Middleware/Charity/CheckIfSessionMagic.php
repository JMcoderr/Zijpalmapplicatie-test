<?php
// Checks that there is a valid magic link session for the Lief en Leed module.

namespace App\Http\Middleware\Charity;

use App\Models\Charity\CharityAdmin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIfSessionMagic
{
    public function handle(Request $request, Closure $next): Response
    {
        $magic = session()->get('magic');

        if (is_null($magic) || now()->greaterThan($magic['expires_at'])) {
            session()->forget('magic');

            return redirect()->route('charity.login');
        }

        // Look this up on every request, so revoked rights take effect right away.
        $admin = CharityAdmin::query()->where('employee', strtolower($magic['email']))->first();

        $magic['admin'] = [
            'isAdmin' => (bool) $admin,
            'isSuper' => $admin && (bool) $admin->super,
        ];

        session()->put('magic', $magic);
        $request->attributes->add(['magic' => $magic]);

        return $next($request);
    }
}
