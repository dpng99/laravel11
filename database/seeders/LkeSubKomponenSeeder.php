<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LkeSubKomponenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('lke_subkomponen')->insert([
            // Subkomponen untuk Komponen: Perencanaan (id_komponen = 1)
            [
                'id' => '1a',
                'id_komponen' => 1,
                'subkomponen' => 'Tersedianyan Dokumen Perencanaan Kinerja',
                'bobot'       => 6,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id' => '1b',
                'id_komponen' => 1,
                'subkomponen' => 'Dokumen Perencanaan kinerja telah memenuhi standar yang baik, yaitu untuk mencapai hasil dengan ukuran kinerja yang SMART, menggunakan penyelarasan (cascading) disetiap level secara logis, serta memperhatikan kinerja bidang lain (crosscutting)',
                'bobot'       => 9,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id' => '1c',
                'id_komponen' => 1,
                'subkomponen' => 'Perencanaan kinerja telah dimanfaatkan untuk mewujudkan hasil yang berkesinambungan',
                'bobot'       => 15,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],

            // Subkomponen untuk Komponen: Pelaksanaan (id_komponen = 2)
            [
                'id' => '2a',
                'id_komponen' => 2,
                'subkomponen' => 'Pengukuran Kinerja telah dilakukan',
                'bobot'       => 6,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id' => '2b',
                'id_komponen' => 2,
                'subkomponen' => 'Pengukuran Kinerja telah menjadi kebutuhan dalam mewujudkan Kinerja secara Efektif dan Efisien dan telah dilakukan secara berjenjang dan berkelanjutan',
                'bobot'       => 9,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id' => '2c',
                'id_komponen' => 2,
                'subkomponen' => 'Pengukuran Kinerja telah dijadikan dasar dalam pemberian reward dan punishment, serta penyesuaian strategi dalam mencapai kinerja yang efektif dan efisien',
                'bobot'       => 15,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],

            // Subkomponen untuk Komponen: Pelaporan (id_komponen = 3)
            [
                'id' => '3a',
                'id_komponen' => 3,
                'subkomponen' => 'Terdapat Dokumen Laporan yang menggambarkan Kinerja',
                'bobot'       => 3,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id' => '3b',
                'id_komponen' => 3,
                'subkomponen' => 'Dokumen Laporan Kinerja telah memenuhi Standar menggambarkan Kualitas atas Pencapaian Kinerja, informasi keberhasilan/kegagalan kinerja serta upaya perbaikan/penyempurnaannya',
                'bobot'       => 4.5,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id' => '3c',
                'id_komponen' => 3,
                'subkomponen' => 'Pelaporan Kinerja telah memerikan dampak yang besar dalam penyesuaian strategi/kebijakan dalam mencapai kinerja berikutnya',
                'bobot'       => 7.5,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],

            // Subkomponen untuk Komponen: Evaluasi (id_komponen = 4)
            [
                'id' => '4a',
                'id_komponen' => 4,
                'subkomponen' => 'Evaluasi Akuntabilitas Kinerja Internal telah dilaksanakan',
                'bobot'       => 5,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id' => '4b',
                'id_komponen' => 4,
                'subkomponen' => 'Evaluasi Akuntabilitas Kinerja Internal telah dilaksanakan secara berkualitas dengan Sumber Daya yang memadai',
                'bobot'       => 7.5,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'id' => '4c',
                'id_komponen' => 4,
                'subkomponen' => 'Implementasi SAKIP telah meningkat karena evaluasi Akuntabilitas Kinerja Internal sehingga memberikan kesan yang nyata (dampak) dalam efektifitas dan efisiensi kinerja',
                'bobot'       => 12.5,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);
    }
}
