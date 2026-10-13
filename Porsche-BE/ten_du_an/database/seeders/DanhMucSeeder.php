<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DanhMucSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        DB::table('danh_mucs')->insert([
            ['ten' => 'Sedan'],
            ['ten' => 'SUV'],
            ['ten' => 'Coupe'],
            ['ten' => 'Electric'],
        ]);
    }
}
