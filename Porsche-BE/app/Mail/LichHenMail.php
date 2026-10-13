<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LichHenMail extends Mailable
{
    use Queueable, SerializesModels;

    public $lichHen;
    public $tieuDe;

    public function __construct($lichHen, $tieuDe)
    {
        $this->lichHen = $lichHen;
        $this->tieuDe = $tieuDe;
    }

    public function build()
    {
        return $this->subject($this->tieuDe)
            ->html("
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <meta charset='utf-8'>
                        <title>{$this->tieuDe}</title>
                    </head>
                    <body style='margin: 0; padding: 0; background-color: #f9f9f9; font-family: \"Times New Roman\", Times, serif; -webkit-font-smoothing: antialiased;'>
                        <table width='100%' border='0' cellspacing='0' cellpadding='0' style='background-color: #f9f9f9; padding: 40px 0;'>
                            <tr>
                                <td align='center'>
                                    <!-- Card Container -->
                                    <table width='600' border='0' cellspacing='0' cellpadding='0' style='background-color: #111111; border-radius: 4px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.15); border: 1px solid #c5a880;'>

                                        <!-- Header / Brand Identity -->
                                        <tr>
                                            <td align='center' style='padding: 45px 40px 35px 40px; border-bottom: 1px solid rgba(197, 168, 128, 0.2);'>
                                                <h1 style='color: #ffffff; font-size: 24px; font-weight: 300; letter-spacing: 6px; margin: 0; text-transform: uppercase;'>PORSCHE WORKSHOP</h1>
                                                <p style='color: #c5a880; font-size: 11px; letter-spacing: 3px; margin: 5px 0 0 0; text-transform: uppercase; font-weight: 400;'>The Art of Performance</p>
                                            </td>
                                        </tr>

                                        <!-- Body Content -->
                                        <tr>
                                            <td style='padding: 50px 55px 40px 55px; background-color: #ffffff;'>
                                                <p style='color: #111111; font-size: 17px; line-height: 1.8; margin: 0 0 25px 0; font-style: italic; font-weight: 500;'>
                                                    Kính gửi Quý khách " . ($this->lichHen->ten_khach_hang ?? 'Bách gia') . ",
                                                </p>
                                                <p style='color: #444444; font-size: 14px; line-height: 1.8; margin: 0 0 35px 0; letter-spacing: 0.5px;'>
                                                    Lời đầu tiên, Porsche Workshop xin gửi lời chào trân trọng và lời chúc thịnh vượng nhất đến Quý khách. Chúng tôi vô cùng vinh hạnh khi được tiếp tục đồng hành cùng Quý khách trong hành trình tối ưu và gìn giữ những giá trị độc bản trên tạo tác chuyển động của mình.
                                                </p>

                                                <!-- Session Info Grid -->
                                                <table width='100%' border='0' cellspacing='0' cellpadding='0' style='margin-bottom: 35px; border-top: 1px solid #eeeeee; border-bottom: 1px solid #eeeeee; padding: 20px 0;'>
                                                    <tr>
                                                        <td style='padding: 8px 0; color: #777777; font-size: 13px; text-transform: uppercase; letter-spacing: 1px;' width='35%'>Mã đặc quyền</td>
                                                        <td style='padding: 8px 0; color: #111111; font-size: 14px; font-weight: bold; letter-spacing: 1px;'>{$this->lichHen->ma_dat_lich}</td>
                                                    </tr>
                                                    <tr>
                                                        <td style='padding: 8px 0; color: #777777; font-size: 13px; text-transform: uppercase; letter-spacing: 1px;'>Ngày hẹn</td>
                                                        <td style='padding: 8px 0; color: #111111; font-size: 14px; font-weight: 600;'>" . \Carbon\Carbon::parse($this->lichHen->ngay_dat)->format('d / m / Y') . "</td>
                                                    </tr>
                                                    <tr>
                                                        <td style='padding: 8px 0; color: #777777; font-size: 13px; text-transform: uppercase; letter-spacing: 1px;'>Thời gian</td>
                                                        <td style='padding: 8px 0; color: #111111; font-size: 14px; font-weight: 600;'>{$this->lichHen->gio_dat}</td>
                                                    </tr>
                                                    <tr>
                                                        <td style='padding: 8px 0; color: #777777; font-size: 13px; text-transform: uppercase; letter-spacing: 1px;'>Tác vụ yêu cầu</td>
                                                        <td style='padding: 8px 0; color: #444444; font-size: 14px; font-style: italic; line-height: 1.6;'>" . ($this->lichHen->ghi_chu ?: 'Kiểm tra và tinh chỉnh hiệu năng tổng thể.') . "</td>
                                                    </tr>
                                                </table>

                                                <p style='color: #444444; font-size: 14px; line-height: 1.8; margin: 0 0 20px 0; letter-spacing: 0.5px;'>
                                                    Mọi sự chuẩn bị về không gian riêng tư, kỹ sư chuyên trách và trang thiết bị chuyên dụng đã được tối ưu hóa để sẵn sàng chào đón Quý khách cùng chiếc xe của mình.
                                                </p>
                                                <p style='color: #444444; font-size: 14px; line-height: 1.8; margin: 0; letter-spacing: 0.5px;'>
                                                    Nếu có bất kỳ thay đổi nào về lịch trình, xin Quý khách vui lòng thông báo trước để chúng tôi được phục vụ chu đáo nhất.
                                                </p>
                                            </td>
                                        </tr>

                                        <!-- Footer / Signature -->
                                        <tr>
                                            <td style='padding: 35px 40px; background-color: #111111; border-top: 1px solid rgba(197, 168, 128, 0.1);' align='center'>
                                                <p style='color: #c5a880; font-size: 12px; letter-spacing: 2px; margin: 0 0 10px 0; text-transform: uppercase;'>Porsche Service Specialist Workshop</p>
                                                <p style='color: #666666; font-size: 11px; margin: 0; letter-spacing: 0.5px;'>Đây là thư gửi tự động từ hệ thống chăm sóc đặc quyền khách hàng.</p>
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </body>
                    </html>
                    ");
    }
}
