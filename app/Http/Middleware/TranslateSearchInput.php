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
        dd([
            'all_inputs' => $request->all(),
            'search_value' => $request->input('search'),
            'accept_language_header' => $request->header('Accept-Language'),
        ]);
        // 1. جلب لغة الموقع من الـ Header وتحويلها لحروف صغيرة
        $lang = strtolower($request->header('Accept-Language', ''));

        // 2. التحقق من أن الكلمة موجودة في الطلب وأن اللغة تحتوي على 'ar'
        if ($request->has('search') && str_contains($lang, 'ar')) {

            // استخدمنا input() لجلب القيمة سواء كانت مبعوتة كـ Query string أو Form-Data/JSON
            $originalSearch = $request->input('search');

            if (!empty($originalSearch) && is_string($originalSearch)) {
                try {
                    // المترجم الذكي يحول تلقائياً من العربي إلى الإنجليزي 'en'
                    $tr = new GoogleTranslate('en');
                    $translatedSearch = $tr->translate($originalSearch);

                    // دمج القيمة المترجمة داخل الـ Request ليعتمدها الباك إند بالكامل
                    $request->merge(['search' => $translatedSearch]);
                } catch (\Exception $e) {
                    // في حالة فشل الاتصال بجوجل كمل الطلب عادي بالكلمة الأصلية
                }
            }
        }

        return $next($request);
    }
}
