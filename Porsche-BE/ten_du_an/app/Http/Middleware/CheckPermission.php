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
        // 🌟 Sửa cách 1: Dùng Facade \Illuminate\Support\Facades\Auth trực tiếp
        // Cách này giúp VS Code nhận biết hàm check() cực kỳ chuẩn
        if (!\Illuminate\Support\Facades\Auth::check()) {
            abort(401, 'Bạn chưa đăng nhập.');
        }

        /** @var \App\Models\User $user */ // 🌟 Mẹo: Dòng này để báo cho VS Code biết $user chắc chắn là Model User của bạn
        $user = \Illuminate\Support\Facades\Auth::user();

        // Bây giờ gọi hàm thỏa thích, VS Code sẽ không bao giờ gạch đỏ nữa
        if (!$user->hasPermission($permission)) {
            abort(403, 'Không có quyền');
        }

        return $next($request);
    }
}
