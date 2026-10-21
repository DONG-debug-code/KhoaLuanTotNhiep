<?php

namespace App\Http\Middleware;

use App\Models\ChuyenGia;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureChuyenGiaAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return response()->json(['status' => false, 'message' => 'Bạn chưa đăng nhập.'], 401);
        }

        if (!($request->user() instanceof ChuyenGia)) {
            return response()->json(['status' => false, 'message' => 'Chỉ tài khoản chuyên gia được phép truy cập.'], 403);
        }

        return $next($request);
    }
}
