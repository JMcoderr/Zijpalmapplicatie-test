<?php
// Sends already logged in users away from the login screen.

namespace App\Http\Middleware\Charity;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckIfGuest
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session()->has('magic')) {
            return $next($request);
        }

        return redirect()->route('charity.admin.requests.index');
    }
}
