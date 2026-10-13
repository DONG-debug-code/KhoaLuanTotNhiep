<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class NhatKyKhoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Lấy ID sản phẩm và ID user (admin/staff) thực tế từ DB để tạo dữ liệu mẫu không lỗi khóa ngoại
        $sanPhamIds = DB::table('san_phams')->pluck('id')->toArray();
        $userIds = DB::table('users')->pluck('id')->toArray();

        if (empty($sanPhamIds) || empty($userIds)) {
            return;
        }

        $loaiList = ['nhap', 'xuat'];
        $lyDoList = ['Nhập hàng lưu kho định kỳ', 'Xuất kho hoàn thiện xe độ', 'Xuất giao hàng cho khách', 'Khách trả hàng hoàn kho'];
        $data = [];

        for ($i = 1; $i <= 100; $i++) {
            $data[] = [
                'user_id'     => $userIds[array_rand($userIds)],
                'san_pham_id' => $sanPhamIds[array_rand($sanPhamIds)],
                'loai'        => $loaiList[array_rand($loaiList)],
                'so_luong'    => rand(1, 10),
                'ly_do'       => $lyDoList[array_rand($lyDoList)],
                'created_at'  => Carbon::now()->subDays(rand(1, 30)),
                'updated_at'  => Carbon::now()->subDays(rand(1, 30)),
            ];
        }

        DB::table('nhat_ky_khos')->insert($data);
    }
}
