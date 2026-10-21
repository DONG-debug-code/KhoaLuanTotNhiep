<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission  <-- Thêm tài liệu cho tham số permission
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Bạn chưa đăng nhập.'], 401);
        }

        if (!method_exists($user, 'hasPermission') || !$user->hasPermission($permission)) {
            return response()->json(['status' => false, 'message' => 'Bạn không có quyền thực hiện chức năng này.'], 403);
        }

        return $next($request);
    }
}
