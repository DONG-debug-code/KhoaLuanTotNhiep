<?php

namespace App\Support;

class AccountRole
{
    public const ADMIN = 'admin';
    public const STAFF = 'staff';
    public const EXPERT = 'chuyen-gia';
    public const CUSTOMER = 'khach-hang';

    // Vai trò được suy ra từ nhãn đầu tiên của tên miền: @porsche.vn, @staff.vn, @tuner.vn, còn lại là khách hàng.
    public static function fromEmail(string $email): string
    {
        $domain = strtolower(substr(strrchr($email, '@') ?: '', 1));
        $label = explode('.', $domain)[0] ?? '';

        return match ($label) {
            'porsche' => self::ADMIN,
            'staff' => self::STAFF,
            'tuner' => self::EXPERT,
            default => self::CUSTOMER,
        };
    }

    public static function isReservedForInternal(string $email): bool
    {
        return self::fromEmail($email) !== self::CUSTOMER;
    }
}
