<?php

return [
    'seller' => [
        'ten' => env('SHOP_NAME', 'Porsche Tuning Showroom'),
        'dia_chi' => env('SHOP_ADDRESS', 'Đà Nẵng, Việt Nam'),
        'ma_so_thue' => env('SHOP_TAX_CODE', ''),
        'so_dien_thoai' => env('SHOP_PHONE', ''),
        'email' => env('SHOP_EMAIL', env('MAIL_FROM_ADDRESS', '')),
    ],
];
