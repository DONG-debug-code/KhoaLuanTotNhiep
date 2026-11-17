<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LaiThuMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public object $laiThu, public string $tieuDe, public string $loiNhan = '')
    {
    }

    public function envelope(): Envelope
    {
        $replyTo = config('shop.seller.email');

        return new Envelope(
            subject: $this->tieuDe,
            replyTo: $replyTo ? [new Address($replyTo, config('shop.seller.ten'))] : [],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.lai_thu');
    }
}
