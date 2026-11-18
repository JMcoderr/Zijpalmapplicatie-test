<?php
// Only lets super admins through, for example for the SEPA export.

namespace App\Http\Middleware\Charity;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIfSuper
{
    public function handle(Request $request, Closure $next): Response
    {
        $magic = $request->attributes->get('magic');

        if (is_null($magic)) {
            return redirect()->route('charity.login');
        }

        if (! $magic['admin']['isSuper']) {
            return redirect()->route('charity.admin.requests.index');
        }

        return $next($request);
    }
}
