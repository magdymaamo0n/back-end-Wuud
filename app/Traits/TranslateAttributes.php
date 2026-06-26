<?php

namespace App\Traits;

use Stichoza\GoogleTranslate\GoogleTranslate;

trait TranslateAttributes
{
    public function toArray()
    {
        $array = parent::toArray();
        $lang = app()->getLocale();

        if ($lang === 'ar') {
            $tr = new GoogleTranslate('ar');

            // أسامي الحقول اللي محتاجة ترجمة تلقائية
            $fieldsToTranslate = [
                'title',
                'category',
                'description',
                'message',
                'product_names',
                'total_price',
                'phone',
                'country',
                'comment',
                'city',
                'About',
                'name',
                'content',
                'address'
            ];

            foreach ($fieldsToTranslate as $field) {
                if (isset($array[$field]) && is_string($array[$field]) && !empty($array[$field])) {
                    if (!filter_var($array[$field], FILTER_VALIDATE_URL)) {
                        try {
                            $array[$field] = $tr->translate($array[$field]);
                        } catch (\Exception $e) {
                            continue;
                        }
                    }
                }
            }
        }

        return $array;
    }
}
