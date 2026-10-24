<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $customerName,
        public int $orderId,
        public float $amount,
        public string $paymentIntentId
    ) {
    }

    public function build(): self
    {
        return $this->subject('Xác nhận thanh toán đơn hàng #' . $this->orderId)
            ->view('emails.payment_receipt');
    }
}
