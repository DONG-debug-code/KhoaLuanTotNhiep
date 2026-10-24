<?php

namespace App\Http\Controllers;

use App\Mail\PaymentReceiptMail;
use App\Services\VnpayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class VnpayController extends Controller
{
    public function __construct(private readonly VnpayService $vnpay)
    {
    }

    // VNPay gọi server-to-server; chỉ endpoint này và confirmReturn mới được ghi nhận thanh toán.
    public function ipn(Request $request): JsonResponse
    {
        $input = $request->query();
        if (!$this->vnpay->hasValidSignature($input)) {
            return response()->json(['RspCode' => '97', 'Message' => 'Invalid signature']);
        }

        try {
            $outcome = $this->process($input);
        } catch (Throwable $e) {
            Log::error('Không thể xử lý IPN VNPay.', ['error' => $e->getMessage()]);
            return response()->json(['RspCode' => '99', 'Message' => 'Unknown error']);
        }

        return match ($outcome['result']) {
            'not_found' => response()->json(['RspCode' => '01', 'Message' => 'Order not found']),
            'invalid_amount' => response()->json(['RspCode' => '04', 'Message' => 'Invalid amount']),
            'already_confirmed' => response()->json(['RspCode' => '02', 'Message' => 'Order already confirmed']),
            default => response()->json(['RspCode' => '00', 'Message' => 'Confirm Success']),
        };
    }

    // Trang kết quả của frontend gọi để xác nhận khi IPN chưa tới được máy chủ (ví dụ môi trường local).
    public function confirmReturn(Request $request): JsonResponse
    {
        $input = $request->query();
        if (!$this->vnpay->hasValidSignature($input)) {
            return response()->json(['status' => false, 'message' => 'Chữ ký VNPay không hợp lệ.'], 400);
        }

        $payment = DB::table('vnpay_payments')->where('txn_ref', (string) ($input['vnp_TxnRef'] ?? ''))->first();
        $order = $payment ? DB::table('don_hangs')->where('id', $payment->don_hang_id)->first() : null;
        if (!$order || (int) $order->users_id !== (int) $request->user()->id) {
            return response()->json(['status' => false, 'message' => 'Không tìm thấy giao dịch thanh toán.'], 404);
        }

        try {
            $outcome = $this->process($input);
        } catch (Throwable $e) {
            Log::error('Không thể xác nhận kết quả thanh toán VNPay.', ['error' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Không thể xác nhận thanh toán.'], 500);
        }

        if ($outcome['result'] === 'invalid_amount') {
            return response()->json(['status' => false, 'message' => 'Số tiền thanh toán không khớp.'], 422);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'trang_thai_thanh_toan' => DB::table('don_hangs')->where('id', $order->id)->value('trang_thai_thanh_toan'),
            ],
        ]);
    }

    private function process(array $input): array
    {
        $payment = DB::table('vnpay_payments')->where('txn_ref', (string) ($input['vnp_TxnRef'] ?? ''))->first();
        if (!$payment) {
            return ['result' => 'not_found'];
        }

        if ((int) ($input['vnp_Amount'] ?? -1) !== (int) $payment->amount * 100) {
            return ['result' => 'invalid_amount'];
        }

        $succeeded = ($input['vnp_ResponseCode'] ?? null) === '00'
            && ($input['vnp_TransactionStatus'] ?? null) === '00';

        if (!$succeeded) {
            $status = ($input['vnp_ResponseCode'] ?? null) === '24' ? 'cancelled' : 'failed';
            app(DonHangController::class)->cancelUnpaidCardOrder((int) $payment->don_hang_id, $status);

            return ['result' => 'failed'];
        }

        $alreadyPaid = DB::transaction(function () use ($payment, $input) {
            $locked = DB::table('vnpay_payments')->where('id', $payment->id)->lockForUpdate()->first();
            if ($locked->status === 'paid') {
                return true;
            }
            if ($locked->status !== 'pending') {
                return null;
            }

            DB::table('vnpay_payments')->where('id', $locked->id)->update([
                'status' => 'paid',
                'transaction_no' => $input['vnp_TransactionNo'] ?? null,
                'bank_code' => $input['vnp_BankCode'] ?? null,
                'paid_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('don_hangs')->where('id', $locked->don_hang_id)->update([
                'trang_thai_thanh_toan' => 'da_thanh_toan',
                'updated_at' => now(),
            ]);

            return false;
        });

        if ($alreadyPaid === null) {
            return ['result' => 'failed'];
        }

        $this->sendReceipt((int) $payment->id);

        return ['result' => $alreadyPaid ? 'already_confirmed' : 'confirmed'];
    }

    private function sendReceipt(int $paymentId): void
    {
        $payment = DB::table('vnpay_payments')->where('id', $paymentId)->first();
        if (!$payment || $payment->receipt_sent_at) {
            return;
        }

        $order = DB::table('don_hangs')->where('id', $payment->don_hang_id)->first();
        if (!$order) {
            return;
        }

        try {
            Mail::to($order->email_nguoi_nhan)->send(new PaymentReceiptMail(
                $order->ten_nguoi_nhan,
                (int) $order->id,
                (float) $order->thanh_tien,
                (string) ($payment->transaction_no ?? $payment->txn_ref)
            ));

            DB::table('vnpay_payments')->where('id', $payment->id)->update([
                'receipt_sent_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::error('Không thể gửi email xác nhận thanh toán VNPay.', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
