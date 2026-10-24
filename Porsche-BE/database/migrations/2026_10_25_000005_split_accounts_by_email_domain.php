<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('admins')) {
            Schema::create('admins', function (Blueprint $table) {
                $table->id();
                $table->string('ho_ten');
                $table->string('email')->unique();
                $table->string('so_dien_thoai', 20)->nullable();
                $table->string('password');
                $table->string('avatar')->nullable();
                $table->tinyInteger('trang_thai')->default(1);
                $table->unsignedBigInteger('vai_tro_id')->index();
                $table->bigInteger('diem_tich_luy')->default(0);
                $table->string('remember_token', 100)->nullable();
                $table->timestamps();
            });
        }

        $now = now();
        $roleId = fn (string $code) => DB::table('vai_tros')->where('ma_vai_tro', $code)->value('id');
        $customerRoleId = $roleId('customer');

        // Tài khoản quản trị @porsche chuyển từ bảng users sang admins.
        foreach (DB::table('users')->where('email', 'like', '%@porsche.%')->get() as $user) {
            if (!DB::table('admins')->where('email', $user->email)->exists()) {
                DB::table('admins')->insert([
                    'ho_ten' => $user->ho_ten, 'email' => $user->email, 'so_dien_thoai' => $user->so_dien_thoai,
                    'password' => $user->password, 'avatar' => $user->avatar, 'trang_thai' => $user->trang_thai,
                    'vai_tro_id' => $roleId('super_admin') ?? $user->vai_tro_id,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
            DB::table('users')->where('id', $user->id)->delete();
        }

        // Khách hàng (email còn lại) luôn mang vai trò customer.
        if ($customerRoleId) {
            DB::table('users')->update(['vai_tro_id' => $customerRoleId]);
        }

        $staff = [
            ['ho_ten' => 'Cố vấn dịch vụ', 'email' => 'advisor@staff.vn', 'role' => 'service_advisor', 'phone' => '0901000001'],
            ['ho_ten' => 'Trưởng phòng kinh doanh', 'email' => 'sales@staff.vn', 'role' => 'sales_manager', 'phone' => '0901000002'],
            ['ho_ten' => 'Quản lý kho', 'email' => 'kho@staff.vn', 'role' => 'inventory_manager', 'phone' => '0901000003'],
        ];
        foreach ($staff as $member) {
            $id = $roleId($member['role']);
            if (!$id || DB::table('admins')->where('email', $member['email'])->exists()) {
                continue;
            }
            DB::table('admins')->insert([
                'ho_ten' => $member['ho_ten'], 'email' => $member['email'], 'so_dien_thoai' => $member['phone'],
                'password' => Hash::make(env('STAFF_DEFAULT_PASSWORD', 'Staff@123')), 'trang_thai' => 1,
                'vai_tro_id' => $id, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // Chuyên gia đăng nhập bằng email @tuner.
        if (Schema::hasTable('chuyen_gias')) {
            foreach (DB::table('chuyen_gias')->whereNotNull('email')->get() as $expert) {
                $local = explode('@', $expert->email)[0];
                $newEmail = $local . '@tuner.vn';
                if ($newEmail !== $expert->email && !DB::table('chuyen_gias')->where('email', $newEmail)->exists()) {
                    DB::table('chuyen_gias')->where('id', $expert->id)->update(['email' => $newEmail]);
                }
            }
        }
    }

    public function down(): void
    {
        // Giữ nguyên dữ liệu tài khoản khi rollback.
    }
};
