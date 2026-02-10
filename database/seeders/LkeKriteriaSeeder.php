<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class LkeKriteriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       DB::table('lke_kriteria')->insert([
            [
                'id_komponen'   => '1',
                'id_subkomponen'=> '1',
                'range_nilai'   => '0-1',
                'bentuk_bukti'  => 'Pedoman 4 Tahun 2024 tentang Penyelenggaraan SAKIP di Lingkungan Kejaksaan RI',
                'bobot'         => 1.00,
                'kriteria'      => 'Terdapat pedoman teknis perencanaan kinerja (penyelenggaraan SAKIP).',
            ],[
                'id_komponen'   => '1',
                'id_subkomponen'=> '1',
                'range_nilai'   => '0-1',
                'bentuk_bukti'  => 'Sehubungan dengan adanya Perpres 80 Tahun 2025 tentang Penyusunan Rencana Strategis dan Rencana Kerja K/L, maka RENSTRA Satuan Kerja ditetapkan paling lambat bulan November 2025 sehingga RENSTRA yang dijadikan subjek evaluasi AKIP adalah dokumen Rancangan Awal Rencana Strategis Tahun 2025-2029',
                'bobot'         => 1.00,
                'kriteria'      => 'Terdapat dokumen perencanaan kinerja jangka menengah (Renstra).',
            ],[
                'id_komponen'   => '1',
                'id_subkomponen'=> '1',
                'range_nilai'   => '0-1',
                'bentuk_bukti'  => 'Rencana Kerja (RENJA) Satuan Kerja ',
                'bobot'         => 1.00,
                'kriteria'      => 'Terdapat dokumen perencanaan kinerja jangka pendek (Renja)',
            ],
            [
                'id_komponen'   => '1',
                'id_subkomponen'=> '1',
                'range_nilai'   => '0-1',
                'bentuk_bukti'  => 'Rencana Aksi Kinerja Satuan Kerja',
                'bobot'         => 1.00,
                'kriteria'      => 'Terdapat dokumen perencanaan aktivitas yang mendukung kinerja.',
            ],[
                'id_komponen'   => '1',
                'id_subkomponen'=> '1',
                'range_nilai'   => '0-1',
                'bentuk_bukti'  => 'Kebutuhan Riil (TOR dan RAB) Satuan Kerja Dokumen DIPA Satuan Kerja.',
                'bobot'         => 1.00,
                'kriteria'      => 'Terdapat dokumen perencanaan anggaran yang mendukung kinerja',
            ],[
                'id_komponen'   => '1',
                'id_subkomponen'=> '1',
                'range_nilai'   => '0-1',
                'bentuk_bukti'  => 'Dokumen Perjanjian Kinerja Satuan Kerja',
                'bobot'         => 1.00,
                'kriteria'      => 'Setiap satuan kerja merumuskan dan menetapkan Perencanaan Kinerja ',
            ],[
                'id_komponen'   => '1',
                'id_subkomponen'=> '2',
                'range_nilai'   => '0-1',
                'bentuk_bukti'  => 'Penetapan/Keputusan Kepala Satker terkait:
                                    1. RENSTRA Satuan Kerja
                                    2. RENJA Satuan Kerja
                                    3. Indikator Kinerja Utama (IKU)  
                                    4. Perjanjian Kinerja',
                'bobot'         => 1.00,
                'kriteria'      => 'Dokumen Perencanaan Kinerja telah diformalkan',
            ],[
                'id_komponen'   => '1',
                'id_subkomponen'=> '2',
                'range_nilai'   => '0-1',
                'bentuk_bukti'  => 'Bukti publikasi dokumen:
                                        1. RENSTRA Satuan Kerja
                                        2. RENJA Satuan Kerja 
                                        3. Indikator Kinerja Utama (IKU) 
                                        4. Perjanjian Kinerja',
                'bobot'         => 1.00,
                'kriteria'      => 'Dokumen Perencanaan telah dipublikasikan tepat waktu',
            ],[
                'id_komponen'   => '1',
                'id_subkomponen'=> '2',
                'range_nilai'   => '0-1',
                'bentuk_bukti'  => 'Bukti publikasi dokumen:
                                        1. RENSTRA Satuan Kerja
                                        2. RENJA Satuan Kerja 
                                        3. Indikator Kinerja Utama (IKU) 
                                        4. Perjanjian Kinerja',
                'bobot'         => 1.00,
                'kriteria'      => 'Dokumen Perencanaan Kinerja telah menggambarkan kebutuhan atas kinerja sebenarnya yang perlu dicapai',
            ],[
                'id_komponen'   => '1',
                'id_subkomponen'=> '2',
                'range_nilai'   => '0-1',
                'bentuk_bukti'  => 'Dokumen : 
                                    RENSTRA Satuan Kerja',
                'bobot'         => 1.00,
                'kriteria'      => 'Kualitas Rumusan Hasil (Tujuan/Sasaran) telah jelas menggambarkan kondisi kinerja yang dicapai ',
            ],[
                'id_komponen'   => '1',
                'id_subkomponen'=> '2',
                'range_nilai'   => '0-1',
                'bentuk_bukti'  => 'Dokumen:
                                    RENSTRA Satuan Kerja 
                                    IKU/Penetapan Target Kinerja',
                'bobot'         => 1.00,
                'kriteria'      => 'Ukuran keberhasilan (indikator kinerja) telah memenuhi kriteria Specific, Measurable, Achievable, Relevant, Time-bound (SMART).',
            ],[
                'id_komponen'   => '1',
                'id_subkomponen'=> '2',
                'range_nilai'   => '0-1',
                'bentuk_bukti'  => 'Dokumen : 
                                    IKU/Penetapan Target Kinerja',
                'bobot'         => 1.00,
                'kriteria'      => 'Indikator Kinerja Utama (IKU) telah menggambarkan kondisi kinerja utama yang harus dicapai, tertuang secara berkelanjutan (sustainable-tidak sering diganti dalam 1 periode Perencanaan Strategis)',
            ],[
                'id_komponen'   => '1',
                'id_subkomponen'=> '2',
                'range_nilai'   => '0-1',
                'bentuk_bukti'  => 'Dokumen : 
                                    1. RENSTRA Satuan Kerja 
                                    2. Penetapan Target Kinerja 
                                    3. Perjanjian Kinerja',
                'bobot'         => 1.00,
                'kriteria'      => 'Target yang ditetapkan dalam perencanaan kinerja dapat dicapai (achievable), menantang, dan realistis',
            ],[
                'id_komponen'   => '1',
                'id_subkomponen'=> '2',
                'range_nilai'   => '0-1',
                'bentuk_bukti'  => 'Dokumen:
                                    1. RENSTRA Satuan 
                                    2. IKU/Penetapan Target Kinerja
                                    3. Perjanjian Kinerja',
                'bobot'         => 1.00,
                'kriteria'      => 'Setiap dokumen perencanaan kinerja menggambarkan hubungan yang berkesinambungan, serta selaras antara kondisi/Hasil yang akan dicapai di setiap level jabatan (Cascading)',
            ],[
                'id_komponen'   => '1',
                'id_subkomponen'=> '2',
                'range_nilai'   => '0-1',
                'bentuk_bukti'  => 'Dokumen:
                                    RENSTRA Satuan 
                                    Pohon Kinerja',
                'bobot'         => 1.00,
                'kriteria'      => 'Perencanaan Kinerja dapat memberikan informasi tentang hubungan kinerja, strategi, kebijakan, bahkan aktivitas antar bidang/dengan tugas dan fungsi lain yang berkaitan (Crosscutting).',
            ],[
                'id_komponen'   => '1',
                'id_subkomponen'=> '2',
                'range_nilai'   => '0-1',
                'bentuk_bukti'  => 'Dokumen: 
                                    Perjanjian Kinerja
                                    SKP Pegawai',
                'bobot'         => 1.00,
                'kriteria'      => 'Setiap pegawai merumuskan dan menetapkan perencanaan kinerja',
            ],[
                'id_komponen'   => '1',
                'id_subkomponen'=> '3',
                'range_nilai'   => '0-2',
                'bentuk_bukti'  => 'Dokumen:
                                    1. RENJA Satuan Kerja
                                    2. dokumen DIPA satuan kerja)',
                'bobot'         => 1.00,
                'kriteria'      => 'Anggaran yang ditetapkan mengacu pada kinerja yang ingin dicapai',
            ],[
                'id_komponen'   => '1',
                'id_subkomponen'=> '3',
                'range_nilai'   => '0-2',
                'bentuk_bukti'  => 'Dokumen:
                                    1. RENSTRA Satuan Kerja
                                    2. IKU
                                    3. Rencana Aksi Kinerja',
                'bobot'         => 1.00,
                'kriteria'      => 'AAktivitas yang dilaksanakan telah mendukung kinerja yang ingin dicapai',
            ],[
                'id_komponen'   => '1',
                'id_subkomponen'=> '3',
                'range_nilai'   => '0-2',
                'bentuk_bukti'  => 'Dokumen:
                                    1. RENSTRA Satuan Kerja
                                    2. Penetapan Target Kinerja Satuan Kerja
                                    3. Perjanjian Kinerja Satuan Kerja 
                                    4. Laporan Kinerja Satuan Kerja',
                'bobot'         => 1.00,
                'kriteria'      => 'Target yang ditetapkan dalam perencanaan kinerja telah dicapai dengan baik, atau setidaknya masih on the right track',
            ],[
                'id_komponen'   => '1',
                'id_subkomponen'=> '3',
                'range_nilai'   => '0-2',
                'bentuk_bukti'  => 'Dokumen:
                                    1. Perjanjian Kinerja Kepala Satuan Kerja
                                    2. Dokumen rencana aksi kinerja Satuan Kerja
                                    3. Laporan Kinerja Satuan Kerja
                                    4. Pengisian Serenata AKIP
                                    5. Laporan Rapat Staf EKA Satuan Kerja
                                    6. Laporan atau dokumentasi hasil pemantauan lainnya',
                'bobot'         => 1.00,
                'kriteria'      => 'Rencana aksi kinerja dapat berjalan dinamis karena capaian kinerja selalu dipantau secara berkala',
            ],[
                'id_komponen'   => '1',
                'id_subkomponen'=> '3',
                'range_nilai'   => '0-2',
                'bentuk_bukti'  => 'Dokumen:
                                    Penetapan target Kinerja Tahun Sebelumnya',
                'bobot'         => 1.00,
                'kriteria'      => 'Terdapat perbaikan/penyempurnaan dokumen perencanaan kinerja yang ditetapkan dari hasil analisis perbaikan kinerja sebelumnya dalam mewujudkan kondisi/hasil yang lebih baik',
            ],[
                'id_komponen'   => '1',
                'id_subkomponen'=> '3',
                'range_nilai'   => '0-2',
                'bentuk_bukti'  => 'Dokumen:
                                    Penetapan target Kinerja ',
                'bobot'         => 1.00,
                'kriteria'      => 'Terdapat perbaikan/penyempurnaan Dokumen Perencanaan Kinerja dalam mewujudkan kondisi/hasil yang lebih baik.',
            ],[
                'id_komponen'   => '1',
                'id_subkomponen'=> '3',
                'range_nilai'   => '0-2',
                'bentuk_bukti'  => 'Dokumen: 
                                    Perjanjian Kinerja seluruh pejabat eselon 
                                    Laporan Kinerja Satuan Kerja
                                    Laporan capaian kinerja Pejabat Struktural',
                'bobot'         => 1.00,
                'kriteria'      => 'setiap satuan kerja memahami dan peduli, serta berkomitmen dalam mencapai kinerja yang telah direncanakan',
            ],[
                'id_komponen'   => '1',
                'id_subkomponen'=> '3',
                'range_nilai'   => '0-2',
                'bentuk_bukti'  => 'Dokumen:
                                    Penilaian SKP ',
                'bobot'         => 1.00,
                'kriteria'      => 'Setiap Pegawai memahami dan peduli, serta berkomitmen dalam mencapai kinerja yang telah direncanakan.',
            ],[
                'id_komponen'   => '2',
                'id_subkomponen'=> '4',
                'range_nilai'   => '0-2',
                'bentuk_bukti'  => 'Dokumen:
                                    1. Pedoman 4 Tahun 2024 tentang Penyelenggaraan SAKIP di Lingkungan Kejaksaan RI
                                    2. Dokumen/Juknis/SOP pengumpulan data kinerja yang disusun secara mandiri oleh Satuan Kerja',
                'bobot'         => 1.00,
                'kriteria'      => 'Terdapat pedoman teknis pengukuran kinerja dan pengumpulan data kinerja',
            ],
            [
                'id_komponen'   => '2',
                'id_subkomponen'=> '4',
                'range_nilai'   => '0-2',
                'bentuk_bukti'  => 'Dokumen:
                                    Indikator Kinerja Utama (IKU) Satuan Kerja/Penetapan Target Kinerja Satuan Kerja ',
                'bobot'         => 1.00,
                'kriteria'      => 'Terdapat definisi operasional yang jelas atas kinerja dan cara mengukur indikator kinerja',
            ],
            [
                'id_komponen'   => '2',
                'id_subkomponen'=> '4',
                'range_nilai'   => '0-2',
                'bentuk_bukti'  => 'Dokumen:
                                    1. Pedoman 4 Tahun 2024 tentang Penyelenggaraan SAKIP di Lingkungan Kejaksaan RI
                                    2. Bukti pengisian Aplikasi SICANA AKIP
                                    3. dokumen terkait mekanisme mekanisme/alur/SOP terkait pengumpulan data kinerja yang disusun secara mandiri
                                    4. Nota Dinas Kepala Satuan Kerja kepada Pejabat Struktural dibawahnya',
                'bobot'         => 1.00,
                'kriteria'      => 'Terdapat mekanisme yang jelas terhadap pengumpulan data kinerja yang dapat diandalkan',
            ],[
                'id_komponen'   => '2',
                'id_subkomponen'=> '5',
                'range_nilai'   => '0-2',
                'bentuk_bukti'  => 'Dokumen:
                                    1. Dokumen/laporan yang menunjukkan bahwa Pimpinan terlibat langsung dalam setiap pengambilan keputusan dalam pengukuran kinerja 
                                    2. Persetujuan pimpinan terhadap hasil pengukuran kinerja yang menjadi dasar penyusunan pelaporan kinerja (Laporan Kinerja Satuan Kerja)
                                    3. Rapat Staf EKA',
                'bobot'         => 1.00,
                'kriteria'      => 'Pimpinan selalu terlibat sebagai pengambil keputusan (decision maker) dalam mengukur capaian kinerja',
            ],[
                'id_komponen'   => '2',
                'id_subkomponen'=> '5',
                'range_nilai'   => '0-2',
                'bentuk_bukti'  => 'Dokumen:
                                    1. Data Kinerja pada Laporan Kinerja maupun pada SICANA AKIP
                                    2. Perjanjian Kinerja',
                'bobot'         => 1.00,
                'kriteria'      => 'Data kinerja yang dikumpulkan telah relevan untuk mengukur capaian kinerja yang diharapkan dan mendukung capaian kinerja yang diharapkan',
            ],[
                'id_komponen'   => '2',
                'id_subkomponen'=> '5',
                'range_nilai'   => '0-2',
                'bentuk_bukti'  => 'Dokumen:
                                        1. Data Kinerja pada Laporan Kinerja maupun pada SICANA AKIP
                                        2. Perjanjian Kinerja',
                'bobot'         => 1.00,
                'kriteria'      => 'Data kinerja yang dikumpulkan telah mendukung capaian kinerja yang diharapkan.',
            ],[
                'id_komponen'   => '2',
                'id_subkomponen'=> '5',
                'range_nilai'   => '0-2',
                'bentuk_bukti'  => 'Dokumen:
                                1. Nota Dinas Pimpinan Satuan Kerja tentang permintaan data kinerja kepada setiap bidang yang dilakukan secara berkala
                                2. Nota Dinas masing-masing bidang terkait penyampaian data kinerja kepada pimpinan satuan kerja
                                3. Pengisian data kinerja pada SICANA AKIP
                                4. LKJIP',
                'bobot'         => 1.00,
                'kriteria'      => 'Pengukuran kinerja telah dilakukan secara berkala',
            ],[
                'id_komponen'   => '2',
                'id_subkomponen'=> '5',
                'range_nilai'   => '0-2',
                'bentuk_bukti'  => 'Dokumen:
                                    Hasil Pemantauan atas pengukuran capaian kinerja ',
                'bobot'         => 1.00,
                'kriteria'      => 'Setiap satuan kerja  melakukan pemantauan atas pengukuran capaian kinerja unit dibawahnya secara berjenjang',
            ],[
                'id_komponen'   => '2',
                'id_subkomponen'=> '5',
                'range_nilai'   => '0-2',
                'bentuk_bukti'  => 'Dokumen:
                                    Pengumpulan data kinerja pada Satuan Kerja telah memanfaatkan teknologi informasi melalui apliksi sicana',
                'bobot'         => 1.00,
                'kriteria'      => 'Pengumpulan data kinerja telah memanfaatkan Teknologi Informasi',
            ],[
                'id_komponen'   => '2',
                'id_subkomponen'=> '5',
                'range_nilai'   => '0-2',
                'bentuk_bukti'  => 'Pengisian aplikasi SICANA AKIP',
                'bobot'         => 1.00,
                'kriteria'      => 'Pengukuran capaian kinerja telah memanfaatkan Teknologi Informasi',
            ],[
                'id_komponen'   => '2',
                'id_subkomponen'=> '6',
                'range_nilai'   => '0-2',
                'bentuk_bukti'  => 'Pedoman 4 Tahun 2024 tentang Penyelenggaraan SAKIP di Lingkungan Kejaksaan RI 
                                    Dokumen pemberian penghargaan dan pembinaan yang telah dilakukan oleh internal Satuan Kerja yang berdasarkan pada capaian kinerja',
                'bobot'         => 1.00,
                'kriteria'      => 'Pegukuran kinerja telah menjadi dasar dalam pemberian penghargaan dan pembinaan',
            ],[
                'id_komponen'   => '2',
                'id_subkomponen'=> '6',
                'range_nilai'   => '0-2',
                'bentuk_bukti'  => 'SK Mutasi Lokal
                                    Surat Perintah Tugas',
                'bobot'         => 1.00,
                'kriteria'      => 'Pengukuran kinerja telah menjadi salah satu pertimbangan penempatan baik dalam jabatan struktural maupun fungsional.',
            ],[
                'id_komponen'   => '2',
                'id_subkomponen'=> '6',
                'range_nilai'   => '0-2',
                'bentuk_bukti'  => 'Dokumen:
                                    Laporan/notulensi pelaksanaan Rastaf EKA Satuan Kerja',
                'bobot'         => 1.00,
                'kriteria'      => 'Pengukuran Kinerja telah mempengaruhi penyesuaian strategi dalam mencapai Kinerja.',
            ],[
                'id_komponen'   => '2',
                'id_subkomponen'=> '6',
                'range_nilai'   => '0-2',
                'bentuk_bukti'  => 'Dokumen:
                                    Laporan hasil evaluasi pengukuran kinerja
                                    Usulan perubahan (penambahan/pengurangan)  kegiatan untuk periode selanjutnya
                                    atau dokumen pendukung lainnya (Rapat Staff)',
                'bobot'         => 1.00,
                'kriteria'      => 'Pengukuran Kinerja telah mempengaruhi penyesuaian kegiatan dalam mencapai kinerja',
            ],[
                'id_komponen'   => '2',
                'id_subkomponen'=> '6',
                'range_nilai'   => '0-2',
                'bentuk_bukti'  => 'Dokumen:
                                    1. Dokumen revisi anggaran, dimana revisi dilakukan berdasarkan hasil pengukuran kinerja
                                    2. Dokumen realisasi kinerja dan anggaran',
                'bobot'         => 1.00,
                'kriteria'      => 'Pengukuran kinerja telah mempengaruhi penyesuaian anggaran dalam mencapai kinerja',
            ],[
                'id_komponen'   => '2',
                'id_subkomponen'=> '6',
                'range_nilai'   => '0-2',
                'bentuk_bukti'  => 'Dokumen:
                                    Dokumen realisasi kinerja dan anggaran satuan kerja
                                    Laporan Kinerja Satuan Kerja',
                'bobot'         => 1.00,
                'kriteria'      => 'Terdapat efisiensi atas penggunaan anggaran dalam mencapai kinerja',
            ],[
                'id_komponen'   => '2',
                'id_subkomponen'=> '6',
                'range_nilai'   => '0-2',
                'bentuk_bukti'  => 'Dokumen:
                                    Notulensi Tindak lanjut atas hasil pengukuran kinerja',
                'bobot'         => 1.00,
                'kriteria'      => 'Setiap satuan kerja memahami dan peduli atas hasil pengukuran kinerja',
            ],[
                'id_komponen'   => '2',
                'id_subkomponen'=> '6',
                'range_nilai'   => '0-2',
                'bentuk_bukti'  => 'Dokumen:
                                    Penilaian SKP',
                'bobot'         => 1.00,
                'kriteria'      => 'Setiap pegawai memahami dan peduli atas hasil pengukuran kinerja',
            ],
            

        ]);
    }
}
