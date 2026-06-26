<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetAppLocale
{
    public function handle(Request $request, Closure $next)
    {
        // اقرأ الهيدر وحول الحروف لـ صغيرة (lowercase) شيل أي مسافات
        $locale = strtolower(trim($request->header('Accept-Language')));

        if (in_array($locale, ['ar', 'en'])) {
            \Illuminate\Support\Facades\App::setLocale($locale);
        } else {
            // لو ملقطش الهيدر صح، خليه يقلب عربي كـ افتراضي
            \Illuminate\Support\Facades\App::setLocale('ar');
        }

        return $next($request);
    }
}
