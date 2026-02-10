<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class LkeKomponenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('lke_komponen')->insert([
            [
                'komponen'   => 'Perencanaan Kienrja',
                'bobot'      => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'komponen'   => 'Pengukuran Kinerja',
                'bobot'      => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'komponen'   => 'Pelaporan Kinerja',
                'bobot'      => 15,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'komponen'   => 'Evaluasi',
                'bobot'      => 25,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

    }
}
