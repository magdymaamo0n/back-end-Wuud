<?php

namespace App\Http\Middleware;

use Closure;
use Stichoza\GoogleTranslate\GoogleTranslate; // استدعاء الحزمة هنا 🎯

class TranslateSearchInput
{
    /**
     * Handle an incoming request.
     */
    public function handle($request, Closure $next)
    {
        // تشيك لو الطلب جواه حقل بحث واللغة اللي جاي بيها عربي
        if ($request->has('search') && $request->header('Accept-Language') === 'ar') {
            $originalSearch = $request->input('search');

            if (!empty($originalSearch) && is_string($originalSearch)) {
                try {
                    // إعداد المترجم ليحول من أي لغة (أو العربي) إلى الإنجليزي 'en'
                    $tr = new GoogleTranslate('en');

                    $translatedSearch = $tr->translate($originalSearch);

                    // استبدال كلمة البحث القديمة (العربي) بالمترجمة (الإنجليزي) جوه الـ request
                    $request->merge(['search' => $translatedSearch]);
                } catch (\Exception $e) {
                    // لو حصل أي مشكلة في الاتصال بمترجم جوجل، كمل عادي عشان الأبلكيشن ما يقفش
                }
            }
        }

        return $next($request);
    }
}
