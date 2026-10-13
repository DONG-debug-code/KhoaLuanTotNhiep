<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 🔥 Admin + 1 user thật
        DB::table('users')->insert([
            [
                'ho_ten'        => 'Admin Porsche',
                'email'         => 'admin@porsche.vn',
                'so_dien_thoai' => '0900000001',
                'password'      => Hash::make('123456'),
                'dia_chi'       => 'Showroom Porsche, Đà Nẵng',
                'vai_tro_id'    => 1,
                'trang_thai'    => 1, // 👈 Bổ sung trạng thái
                'diem_tich_luy' => 0,
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'ho_ten'        => 'Nguyễn Văn A',
                'email'         => 'khach1@gmail.com',
                'so_dien_thoai' => '0900000002',
                'password'      => Hash::make('123456'),
                'dia_chi'       => '123 Đường Nguyễn Văn Linh, Đà Nẵng',
                'vai_tro_id'    => 3,
                'trang_thai'    => 1, // 👈 Bổ sung trạng thái
                'diem_tich_luy' => 100,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]
        ]);

        // 🔥 100 user fake
        for ($i = 1; $i <= 100; $i++) {
            DB::table('users')->insert([
                'ho_ten'        => 'Khách ' . $i,
                'email'         => "user$i@gmail.com",
                'so_dien_thoai' => '09' . rand(10000000, 99999999),
                'password'      => Hash::make('123456'),
                'dia_chi'       => "Địa chỉ khách hàng $i",
                'vai_tro_id'    => 3,
                'trang_thai'    => 1, // 👈 Bổ sung trạng thái
                'diem_tich_luy' => rand(0, 1000),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }
}