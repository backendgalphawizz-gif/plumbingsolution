<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin panel field length limits
    |--------------------------------------------------------------------------
    | Used for server validation and HTML maxlength on forms.
    */
    'limits' => [
        'name' => 32,
        'shop_name' => 80,
        'owner_name' => 32,
        'product_name' => 100,
        'category_name' => 32,
        'slug' => 80,
        'email' => 255,
        'password' => 255,
        'mobile' => 10,
        'address' => 300,
        'gst_number' => 15,
        'skills' => 400,
        'service_area' => 200,
        'role_title' => 50,
        'reason' => 300,
        'notes' => 500,
        'description' => 2000,
        'sku' => 40,
        'search' => 80,
        'cms_title' => 100,
        'cms_content' => 10000,
        'setting_value' => 255,
        'quotation_details' => 1000,
        'faq_question' => 255,
        'faq_answer' => 2000,
        'max_name_words' => 5,
        'image_kb' => 20480,
    ],

];
