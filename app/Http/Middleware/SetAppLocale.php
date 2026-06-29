<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetAppLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = strtolower(trim($request->header('Accept-Language')));

        if (in_array($locale, ['ar', 'en'])) {
            \Illuminate\Support\Facades\App::setLocale($locale);
        } else {
            \Illuminate\Support\Facades\App::setLocale('en');
        }

        return $next($request);
    }
}
