<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\indikator_sastra_new;
use Illuminate\Support\Facades\DB;
class indikator_sastra_new_seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
     DB::table('indikator_sastra')->insert([
            [
                'kode_sastra' => '1',
                'kode_indikator' => '1-1',
                'nama_indikator' => 'Indeks persepsi publik terhadap citra Kejaksaan RI',
                //'created_at' => now(),
   //             'updated_at' => now(),
            ],
            [
                'kode_sastra' => '2',
                'kode_indikator' => '2-1',
                'nama_indikator' => 'Persentase peningkatan pengendalian perkara',
                //'created_at' => now(),
   //             'updated_at' => now(),
            ],
            [
                'kode_sastra' => '2',
                'kode_indikator' => '2-2',
                'nama_indikator' => 'Tingkat keberhasilan kegiatan dan operasi intelijen penegakan hukum',
                //'created_at' => now(),
   //             'updated_at' => now(),
            ],
            [
                'kode_sastra' => '2',
                'kode_indikator' => '2-3',
                'nama_indikator' => 'Tingkat keberhasilan pemulihan aset negara',
                //'created_at' => now(),
   //             'updated_at' => now(),
            ],
            [
                'kode_sastra' => '3',
                'kode_indikator' => '3-1',
                'nama_indikator' => 'Tingkat efektivitas pelaksanaan kewenangan Advocaat Generaal',
                //'created_at' => now(),
   //             'updated_at' => now(),
            ],
            [
                'kode_sastra' => '4',
                'kode_indikator' => '4-1',
                'nama_indikator' => 'Indeks reformasi birokrasi',
                //'created_at' => now(),
   //             'updated_at' => now(),
            ],
            [
                'kode_sastra' => '4',
                'kode_indikator' => '4-2',
                'nama_indikator' => 'Tingkat penerapan etika profesi jaksa',
                //'created_at' => now(),
   //             'updated_at' => now(),
            ],
        ]);
    }
}
