<?php

namespace App\Services;

class VnpayService
{
    public function isConfigured(): bool
    {
        return (bool) config('services.vnpay.tmn_code') && (bool) config('services.vnpay.hash_secret');
    }

    public function buildPaymentUrl(int $orderId, string $txnRef, int $amount, string $ipAddress): string
    {
        $timezone = 'Asia/Ho_Chi_Minh';
        $expireMinutes = max(5, (int) config('services.vnpay.expire_minutes'));
        $frontendUrl = rtrim((string) config('services.vnpay.frontend_url'), '/');

        $params = [
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'pay',
            'vnp_TmnCode' => (string) config('services.vnpay.tmn_code'),
            'vnp_Amount' => (string) ($amount * 100),
            'vnp_CreateDate' => now($timezone)->format('YmdHis'),
            'vnp_ExpireDate' => now($timezone)->addMinutes($expireMinutes)->format('YmdHis'),
            'vnp_CurrCode' => 'VND',
            'vnp_IpAddr' => filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? $ipAddress : '127.0.0.1',
            'vnp_Locale' => 'vn',
            'vnp_OrderInfo' => 'Thanh toan don hang ' . $orderId,
            'vnp_OrderType' => 'other',
            'vnp_ReturnUrl' => $frontendUrl . '/dat-hang-thanh-cong/' . $orderId,
            'vnp_TxnRef' => $txnRef,
        ];

        return config('services.vnpay.url') . '?' . $this->canonicalQuery($params)
            . '&vnp_SecureHash=' . $this->sign($params);
    }

    public function hasValidSignature(array $input): bool
    {
        $received = (string) ($input['vnp_SecureHash'] ?? '');
        if ($received === '' || !$this->isConfigured()) {
            return false;
        }

        $params = collect($input)
            ->filter(fn ($value, $key) => str_starts_with((string) $key, 'vnp_')
                && !in_array($key, ['vnp_SecureHash', 'vnp_SecureHashType'], true)
                && is_scalar($value))
            ->all();

        return hash_equals($this->sign($params), strtolower($received));
    }

    private function sign(array $params): string
    {
        return hash_hmac('sha512', $this->canonicalQuery($params), (string) config('services.vnpay.hash_secret'));
    }

    private function canonicalQuery(array $params): string
    {
        ksort($params);

        return collect($params)
            ->map(fn ($value, $key) => urlencode((string) $key) . '=' . urlencode((string) $value))
            ->implode('&');
    }
}
