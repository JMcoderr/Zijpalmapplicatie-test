<?php
// Only lets Lief en Leed admins through.

namespace App\Http\Middleware\Charity;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIfCharityAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $magic = $request->attributes->get('magic');

        if (is_null($magic)) {
            return redirect()->route('charity.login');
        }

        if (! $magic['admin']['isAdmin']) {
            return redirect()->route('charity.requests');
        }

        return $next($request);
    }
}
