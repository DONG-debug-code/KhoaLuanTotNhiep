<?php

return [
    'seller' => [
        'ten' => env('SHOP_NAME', 'Porsche Tuning Showroom'),
        'dia_chi' => env('SHOP_ADDRESS', 'Đà Nẵng, Việt Nam'),
        'ma_so_thue' => env('SHOP_TAX_CODE', ''),
        'so_dien_thoai' => env('SHOP_PHONE', ''),
        'email' => env('SHOP_EMAIL', env('MAIL_FROM_ADDRESS', '')),
    ],

    // Xe không bán trực tuyến: khách trao đổi trực tiếp với nhân viên qua hotline.
    'hotline' => env('SHOP_HOTLINE', '0797306295'),
    'showroom_dia_chi' => env('SHOP_SHOWROOM_ADDRESS', '88 Nguyễn Hữu Cảnh, Quận 1, TP. Hồ Chí Minh'),
    'gio_lam_viec' => env('SHOP_WORKING_HOURS', '08:00 – 20:00 hằng ngày'),
];
