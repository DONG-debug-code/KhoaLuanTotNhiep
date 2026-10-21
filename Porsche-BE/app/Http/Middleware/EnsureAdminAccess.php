<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminAccess
{
    private const STAFF_ROLES = [
        'super_admin',
        'executive',
        'branch_manager',
        'sales_manager',
        'sales_executive',
        'service_manager',
        'service_advisor',
        'lead_technician',
        'technician',
        'inventory_manager',
        'parts_keeper',
        'chief_accountant',
        'cashier',
        'customer_relation',
        'marketing_manager',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Bạn chưa đăng nhập.'], 401);
        }

        if (!($user instanceof Admin)) {
            return response()->json(['status' => false, 'message' => 'Chỉ tài khoản quản trị được phép truy cập.'], 403);
        }

        foreach (self::STAFF_ROLES as $role) {
            if (method_exists($user, 'hasRole') && $user->hasRole($role)) {
                return $next($request);
            }
        }

        return response()->json(['status' => false, 'message' => 'Bạn không có quyền truy cập khu vực quản trị.'], 403);
    }
}
