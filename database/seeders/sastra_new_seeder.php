<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class sastra_new_seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       DB::table('sakip_sastra_new')->insert([
            [
                'id_sastra' => '1',
                'nama_sastra' => 'Indeks persepsi publik terhadap citra Kejaksaan RI',
                //'deskripsi' => '.',
            //    'created_at' => now(),
             //   'updated_at' => now(),
            ],
            [
                'id_sastra' => '2',
                'nama_sastra' => 'Terwujudnya efektivitas penegakan hukum dan keadilan melalui transformasi sistem penuntutan',
                //'deskripsi' => '.',
            //    'created_at' => now(),
             //   'updated_at' => now(),
            ],
            [
                'id_sastra' => '3',
                'nama_sastra' => 'Terwujudnya efektivitas pelaksanaan kewenangan Advocaat Generaal',
                //'deskripsi' => '.',
            //    'created_at' => now(),
             //   'updated_at' => now(),
            ],
            [
                'id_sastra' => '4',
                'nama_sastra' => 'Terwujudnya tata kelola organisasi yang optimal, transparan dan akuntabel',
                //'deskripsi' => '.',
            //    'created_at' => now(),
             //   'updated_at' => now(),
            ],
        ]);
    }
}
