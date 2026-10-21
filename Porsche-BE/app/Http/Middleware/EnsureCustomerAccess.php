<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCustomerAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()) {
            return response()->json(['status' => false, 'message' => 'Bạn chưa đăng nhập.'], 401);
        }

        if (!($request->user() instanceof User)) {
            return response()->json(['status' => false, 'message' => 'Chỉ tài khoản khách hàng được phép truy cập.'], 403);
        }

        return $next($request);
    }
}
