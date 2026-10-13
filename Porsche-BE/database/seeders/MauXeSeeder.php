<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MauXeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Tìm ID của hãng xe Porsche trong bảng hang_xes
        $hangXe = DB::table('hang_xes')->where('ten', 'like', '%Porsche%')->first();

        // Nếu tìm thấy thì lấy ID của nó, nếu không tìm thấy (dự phòng) thì mặc định lấy ID đầu tiên là 1
        $hangXeId = $hangXe ? $hangXe->id : 1;

        // 2. Chèn dữ liệu mẫu xe đi kèm với mã hãng xe tương ứng
        DB::table('mau_xes')->insert([
            ['ten' => 'Porsche 911 Carrera', 'hang_xe_id' => $hangXeId],
            ['ten' => 'Porsche Taycan',       'hang_xe_id' => $hangXeId],
            ['ten' => 'Porsche Cayenne',      'hang_xe_id' => $hangXeId],
            ['ten' => 'Porsche Macan',        'hang_xe_id' => $hangXeId],
        ]);
    }
}
