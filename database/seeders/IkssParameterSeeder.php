<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\IkssParameter;
use App\Models\IkssParameterDependency;
use App\Models\IkssParameterGroup;
use App\Models\LkjipTemplateBinding;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class IkssParameterSeeder extends Seeder
{
    private array $counts = [
        'groups' => 0,
        'parameters' => 0,
        'dependencies' => 0,
        'bindings' => 0,
    ];

    private array $masterIkssIds = [];
    /**
     * Run the database seeds.
     * Menerapkan 7-8 Tingkat Hierarki SAKIP Kejaksaan RI 2025-2029 secara Utuh.
     */
    public function run(): void
    {
        $this->command->info('Memulai sinkronisasi pohon kinerja SAKIP 7-8 tingkat...');

        $tree = [
            // =========================================================================
            // SASARAN STRATEGIS 1
            // =========================================================================
            [
                'name' => 'SS 1 - Terwujudnya Kelembagaan Hukum yang Transparan dan Adil',
                'formula_config' => ['type' => 'average'],
                'children' => [
                    [
                        'name' => 'IKSS 1.1 - Indeks Persepsi Publik terhadap Citra Kejaksaan RI',
                        'formula_config' => ['type' => 'ratio'], // Pembilang / Pembagi
                        'children' => [
                            [
                                'name' => '[AGREGAT PEMBILANG] Rata-rata Nilai Survei Kepuasan Masyarakat (SKM)',
                                'formula_config' => ['type' => 'average'],
                                'children' => [
                                    ['name' => 'Unsur 1: Persyaratan Pelayanan', 'is_input' => true],
                                    ['name' => 'Unsur 2: Sistem, Mekanisme, dan Prosedur', 'is_input' => true],
                                    ['name' => 'Unsur 3: Waktu Penyelesaian', 'is_input' => true],
                                    ['name' => 'Unsur 4: Biaya/Tarif Pelayanan', 'is_input' => true],
                                    ['name' => 'Unsur 5: Produk Spesifikasi Jenis Pelayanan', 'is_input' => true],
                                    ['name' => 'Unsur 6: Kompetensi Pelaksana', 'is_input' => true],
                                    ['name' => 'Unsur 7: Perilaku Pelaksana', 'is_input' => true],
                                    ['name' => 'Unsur 8: Penanganan Pengaduan, Saran, dan Masukan', 'is_input' => true],
                                    ['name' => 'Unsur 9: Sarana dan Prasarana', 'is_input' => true],
                                ]
                            ],
                            [
                                'name' => '[AGREGAT PEMBAGI] Target Perjanjian Kinerja (PK) SKM Tahunan',
                                'formula_config' => ['type' => 'sum'],
                                'children' => [
                                    ['name' => 'Nilai Target Baseline Perjanjian Kinerja', 'is_input' => true]
                                ]
                            ]
                        ]
                    ]
                ]
            ],

            // =========================================================================
            // SASARAN STRATEGIS 2
            // =========================================================================
            [
                'name' => 'SS 2 - Terwujudnya Efektivitas Penegakan Hukum dan Keadilan melalui Transformasi Sistem Penuntutan',
                'formula_config' => ['type' => 'average'],
                'children' => [
                    // --- IKSS 2.1 ---
                    [
                        'name' => 'IKSS 2.1 - Persentase Peningkatan Pengendalian Perkara',
                        'formula_config' => ['type' => 'average'],
                        'children' => [
                            
                            // LEVEL 3: K1 (Tingkat Keberhasilan Penanganan Perkara)
                            [
                                'name' => 'K1 - Tingkat Keberhasilan Penanganan Perkara (Rata-rata Gabungan Bidang)',
                                'formula_config' => ['type' => 'average'],
                                'children' => [
                                    
                                    // LEVEL 4: BIDANG PIDUM
                                    [
                                        'name' => 'Bidang Tindak Pidana Umum (PIDUM)',
                                        'formula_config' => ['type' => 'average'],
                                        'children' => [
                                            [
                                                'name' => 'Tahap Prapenuntutan (PIDUM)',
                                                'formula_config' => ['type' => 'ratio'],
                                                'children' => [
                                                    [
                                                        'name' => '[AGREGAT PEMBILANG] Jumlah Perkara yang Berhasil Diselesaikan pada Tahap Prapenuntutan',
                                                        'formula_config' => ['type' => 'sum'],
                                                        'children' => [
                                                            ['name' => 'Penyerahan tersangka dan barang bukti (Tahap II)', 'is_input' => true],
                                                            ['name' => 'SPDP dikembalikan melebihi 7 hari dari terbitnya Sprindik', 'is_input' => true],
                                                            ['name' => 'SPDP tanpa berkas yang dikembalikan kepada penyidik', 'is_input' => true],
                                                            ['name' => 'SPDP dan berkas perkara dikembalikan melebihi 30 hari dari terbitnya P-21A', 'is_input' => true],
                                                            ['name' => 'Berkas perkara yang dihentikan penyidikannya oleh penyidik', 'is_input' => true],
                                                        ]
                                                    ],
                                                    [
                                                        'name' => '[AGREGAT PEMBAGI] Total SPDP Perkara Tindak Pidana Umum yang Diterima dan Telah Diterbitkan P-16',
                                                        'formula_config' => ['type' => 'sum'],
                                                        'children' => [
                                                            ['name' => 'Jumlah berkas SPDP masuk dari POLRI/PPNS/Lainnya', 'is_input' => true]
                                                        ]
                                                    ]
                                                ]
                                            ],
                                            [
                                                'name' => 'Tahap Penuntutan (PIDUM)',
                                                'formula_config' => ['type' => 'ratio'],
                                                'children' => [
                                                    [
                                                        'name' => '[AGREGAT PEMBILANG] Jumlah Perkara yang Berhasil Diselesaikan pada Tahap Penuntutan',
                                                        'formula_config' => ['type' => 'sum'],
                                                        'children' => [
                                                            ['name' => 'Jumlah berkas perkara yang dilimpahkan oleh Jaksa ke Pengadilan', 'is_input' => true],
                                                            ['name' => 'Jumlah perkara yang dihentikan penuntutannya via Restorative Justice', 'is_input' => true],
                                                            ['name' => 'Jumlah perkara anak yang dihentikan penuntutannya via Diversi', 'is_input' => true],
                                                            ['name' => 'Jumlah pengenyampingan perkara demi kepentingan umum (Deponering) & Alasan Sah Lain', 'is_input' => true],
                                                        ]
                                                    ],
                                                    [
                                                        'name' => '[AGREGAT PEMBAGI] Jumlah Perkara Tindak Pidana Umum yang Telah Diterbitkan P-16A',
                                                        'formula_config' => ['type' => 'sum'],
                                                        'children' => [
                                                            ['name' => 'Jumlah Surat Perintah Penunjukan JPU (P-16A)', 'is_input' => true]
                                                        ]
                                                    ]
                                                ]
                                            ],
                                            [
                                                'name' => 'Tahap Eksekusi (PIDUM)',
                                                'formula_config' => ['type' => 'ratio'],
                                                'children' => [
                                                    [
                                                        'name' => '[AGREGAT PEMBILANG] Jumlah Terpidana Tindak Pidana Umum yang Telah Dieksekusi',
                                                        'formula_config' => ['type' => 'sum'],
                                                        'children' => [
                                                            ['name' => 'Jumlah eksekusi riil terpidana berdasarkan dokumen BA-17', 'is_input' => true]
                                                        ]
                                                    ],
                                                    [
                                                        'name' => '[AGREGAT PEMBAGI] Jumlah Terpidana Berdasarkan Putusan yang Berkekuatan Hukum Tetap (Inkracht)',
                                                        'formula_config' => ['type' => 'sum'],
                                                        'children' => [
                                                            ['name' => 'Jumlah putusan inkracht (Surat Perintah Pelaksanaan Putusan Pengadilan / P-48)', 'is_input' => true]
                                                        ]
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ],

                                    // LEVEL 4: BIDANG PIDSUS
                                    [
                                        'name' => 'Bidang Tindak Pidana Khusus (PIDSUS)',
                                        'formula_config' => ['type' => 'average'],
                                        'children' => [
                                            // Program 1: Korupsi & TPPU
                                            $this->buildPidsusSubProgram('Tindak Pidana Korupsi dan TPPU', true),
                                            // Program 2: Perpajakan
                                            $this->buildPidsusSubProgram('Tindak Pidana Perpajakan dan TPPU', false),
                                            // Program 3: Kepabeanan
                                            $this->buildPidsusSubProgram('Tindak Pidana Kepabeanan dan TPPU', false),
                                            // Program 4: Cukai
                                            $this->buildPidsusSubProgram('Tindak Pidana Cukai dan TPPU', false),
                                            // Program 5: Kerugian Perekonomian Negara
                                            $this->buildPidsusSubProgram('Tindak Pidana Kerugian Perekonomian Negara', true),
                                        ]
                                    ],

                                    // LEVEL 4: BIDANG PIDMIL (Dinamis Kondisional untuk level Kejati)
                                    [
                                        'name' => 'Bidang Pidana Militer (PIDMIL / Koneksitas)',
                                        'formula_config' => ['type' => 'average'],
                                        'children' => [
                                            $this->buildSimpleRatioStage('Tahap Penyelidikan Koneksitas', 'Jumlah perkara koneksitas dapat dilanjutkan ke penyidikan', 'Jumlah perkara koneksitas yang diselidik'),
                                            $this->buildSimpleRatioStage('Tahap Penyidikan Koneksitas', 'Jumlah tindakan hukum penyidikan disetujui Ankum/Papera', 'Jumlah perkara koneksitas yang disidik'),
                                            $this->buildSimpleRatioStage('Tahap Penuntutan Koneksitas', 'Jumlah perkara koneksitas memperoleh putusan min 2/3 tuntutan', 'Jumlah total perkara koneksitas dituntut'),
                                            $this->buildSimpleRatioStage('Tahap Eksekusi Koneksitas', 'Jumlah perkara koneksitas dieksekusi tepat waktu (7 hari)', 'Jumlah total perkara koneksitas berkekuatan hukum tetap'),
                                        ]
                                    ],

                                    // LEVEL 4: BIDANG DATUN (Khusus Penegakan Hukum di K1)
                                    [
                                        'name' => 'Bidang Perdata dan Tata Usaha Negara (DATUN - Penegakan Hukum)',
                                        'formula_config' => ['type' => 'ratio'],
                                        'children' => [
                                            [
                                                'name' => '[AGREGAT PEMBILANG] Jumlah Gugatan/Permohonan Penegakan Hukum Bidang DATUN yang Dikabulkan Hakim',
                                                'formula_config' => ['type' => 'sum'],
                                                'children' => [
                                                    ['name' => 'Jumlah putusan hakim yang mengabulkan gugatan', 'is_input' => true]
                                                ]
                                            ],
                                            [
                                                'name' => '[AGREGAT PEMBAGI] Jumlah Seluruh Gugatan/Permohonan Penegakan Hukum Bidang DATUN yang Ditangani',
                                                'formula_config' => ['type' => 'sum'],
                                                'children' => [
                                                    ['name' => 'Total perkara permohonan penegakan hukum masuk', 'is_input' => true]
                                                ]
                                            ]
                                        ]
                                    ]

                                ]
                            ],

                            // LEVEL 3: K2 (Mediasi Penal, Diskresi & Denda Damai)
                            [
                                'name' => 'K2 - Persentase Penanganan Perkara melalui Mediasi Penal, Diskresi Penuntutan, dan Denda Damai',
                                'formula_config' => ['type' => 'average'],
                                'children' => [
                                    [
                                        'name' => 'Persentase Penyelesaian Restorative Justice & Diversi (PIDUM)',
                                        'formula_config' => ['type' => 'ratio'],
                                        'children' => [
                                            [
                                                'name' => '[AGREGAT PEMBILANG] Jumlah Perkara Pidum Berhasil via RJ / Diversi',
                                                'formula_config' => ['type' => 'sum'],
                                                'children' => [
                                                    ['name' => 'Data riil perkara diselesaikan lewat keadilan restoratif/diversi', 'is_input' => true]
                                                ]
                                            ],
                                            [
                                                'name' => '[AGREGAT PEMBAGI] Jumlah Perkara Pidum Diusulkan via RJ / Diversi',
                                                'formula_config' => ['type' => 'sum'],
                                                'children' => [
                                                    ['name' => 'Total perkara memenuhi syarat formal yang diusulkan', 'is_input' => true]
                                                ]
                                            ]
                                        ]
                                    ],
                                    [
                                        'name' => 'Persentase Penyelesaian Denda Damai Berbasis Beneficiary Ownership (PIDSUS)',
                                        'formula_config' => ['type' => 'ratio'],
                                        'children' => [
                                            [
                                                'name' => '[AGREGAT PEMBILANG] Jumlah Perkara Pidsus Selesai via Denda Damai',
                                                'formula_config' => ['type' => 'sum'],
                                                'children' => [
                                                    ['name' => 'Data riil pemulihan denda damai korporasi/tersangka', 'is_input' => true]
                                                ]
                                            ],
                                            [
                                                'name' => '[AGREGAT PEMBAGI] Jumlah Perkara Pidsus Diusulkan Denda Damai',
                                                'formula_config' => ['type' => 'sum'],
                                                'children' => [
                                                    ['name' => 'Total perkara sektoral pidsus yang diusulkan denda damai', 'is_input' => true]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ],

                            // LEVEL 3: K3 (Alternatif Pemidanaan)
                            [
                                'name' => 'K3 - Persentase Penuntutan melalui Alternatif Pemidanaan',
                                'formula_config' => ['type' => 'ratio'],
                                'children' => [
                                    [
                                        'name' => '[AGREGAT PEMBILANG] Jumlah Perkara yang Dituntut dengan Alternatif Pemidanaan',
                                        'formula_config' => ['type' => 'sum'],
                                        'children' => [
                                            ['name' => 'Tuntutan non-penjara (Denda, Kerja Sosial, Pengawasan, Rehabilitasi)', 'is_input' => true]
                                        ]
                                    ],
                                    [
                                        'name' => '[AGREGAT PEMBAGI] Jumlah Perkara yang Memenuhi Syarat untuk Dituntut Alternatif',
                                        'formula_config' => ['type' => 'sum'],
                                        'children' => [
                                            ['name' => 'Total perkara pengguna murni/tindak ringan sesuai kriteria undang-undang', 'is_input' => true]
                                        ]
                                    ]
                                ]
                            ]

                        ]
                    ],

                    // --- IKSS 2.2 ---
                    [
                        'name' => 'IKSS 2.2 - Tingkat Keberhasilan Kegiatan dan Operasi Intelijen Penegakan Hukum',
                        'formula_config' => ['type' => 'ratio'],
                        'children' => [
                            [
                                'name' => '[AGREGAT PEMBILANG] Jumlah Kegiatan / Operasi Intelijen (LID/PAM/GAL) yang Berhasil Dilaksanakan',
                                'formula_config' => ['type' => 'sum'],
                                'children' => [
                                    ['name' => 'Operasi intelijen selesai dengan produk laporan intelijen penegakan hukum (LAPHIN)', 'is_input' => true]
                                ]
                            ],
                            [
                                'name' => '[AGREGAT PEMBAGI] Jumlah Seluruh Kegiatan / Operasi Intelijen yang Dilaksanakan (Sprint)',
                                'formula_config' => ['type' => 'sum'],
                                'children' => [
                                    ['name' => 'Total Surat Perintah (Sprint) operasi intelijen yang diterbitkan', 'is_input' => true]
                                ]
                            ]
                        ]
                    ],

                    // --- IKSS 2.3 ---
                    [
                        'name' => 'IKSS 2.3 - Tingkat Keberhasilan Pemulihan Aset Negara',
                        'formula_config' => ['type' => 'average'],
                        'children' => [
                            [
                                'name' => 'Sub-Indikator Keberhasilan Kegiatan Penelusuran Aset (Tracing)',
                                'formula_config' => ['type' => 'ratio'],
                                'children' => [
                                    [
                                        'name' => '[AGREGAT PEMBILANG] Nilai Realisasi Aset Diserahkan Kembali ke Pemohon / Negara',
                                        'formula_config' => ['type' => 'sum'],
                                        'children' => [
                                            ['name' => 'Nilai nominal hasil asset tracing yang disita/dikembalikan (Rp)', 'is_input' => true]
                                        ]
                                    ],
                                    [
                                        'name' => '[AGREGAT PEMBAGI] Nilai Total Taksiran Aset Hasil Penelusuran',
                                        'formula_config' => ['type' => 'sum'],
                                        'children' => [
                                            ['name' => 'Nilai pagu taksiran objek aset yang ditelusuri (Rp)', 'is_input' => true]
                                        ]
                                    ]
                                ]
                            ],
                            [
                                'name' => 'Sub-Indikator Keberhasilan Perampasan Aset (Seizure)',
                                'formula_config' => ['type' => 'ratio'],
                                'children' => [
                                    [
                                        'name' => '[AGREGAT PEMBILANG] Nilai Aktual Aset Dirampas Berdasarkan Berita Acara Penerimaan',
                                        'formula_config' => ['type' => 'sum'],
                                        'children' => [
                                            ['name' => 'Nilai buku aset sita eksekusi yang masuk gudang barang bukti (Rp)', 'is_input' => true]
                                        ]
                                    ],
                                    [
                                        'name' => '[AGREGAT PEMBAGI] Nilai Total Ekspektasi Aset yang Berhasil Dieksekusi',
                                        'formula_config' => ['type' => 'sum'],
                                        'children' => [
                                            ['name' => 'Target nilai aset berdasarkan amar putusan hakim (Rp)', 'is_input' => true]
                                        ]
                                    ]
                                ]
                            ],
                            [
                                'name' => 'Sub-Indikator Keberhasilan Pemulihan Aset via Likuidasi / Lelang',
                                'formula_config' => ['type' => 'ratio'],
                                'children' => [
                                    [
                                        'name' => '[AGREGAT PEMBILANG] Nilai Finansial Aset Selesai (Lelang, Hibah, PSP, Penyelesaian Lain)',
                                        'formula_config' => ['type' => 'sum'],
                                        'children' => [
                                            ['name' => 'Uang hasil lelang/penjualan bersih yang disetor ke kas negara (PNBP) (Rp)', 'is_input' => true]
                                        ]
                                    ],
                                    [
                                        'name' => '[AGREGAT PEMBAGI] Nilai Taksiran Penilaian Appraisal Kendali Aset',
                                        'formula_config' => ['type' => 'sum'],
                                        'children' => [
                                            ['name' => 'Nilai limit appraisal resmi dari KPKNL (Rp)', 'is_input' => true]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],

            // =========================================================================
            // SASARAN STRATEGIS 3
            // =========================================================================
            [
                'name' => 'SS 3 - Terwujudnya Efektivitas Pelaksanaan Kewenangan Advocaat Generaal',
                'formula_config' => ['type' => 'average'],
                'children' => [
                    [
                        'name' => 'IKSS 3.1 - Tingkat Efektivitas Pelaksanaan Kewenangan Advocaat Generaal',
                        'formula_config' => ['type' => 'average'],
                        'children' => [
                            
                            // COMPONENT P1: Litigasi & Non Litigasi
                            [
                                'name' => 'P1 - Tingkat Keberhasilan Penanganan Perkara Perdata dan Tata Usaha Negara',
                                'formula_config' => ['type' => 'average'],
                                'children' => [
                                    [
                                        'name' => 'Sub-Indikator Perdata Litigasi',
                                        'formula_config' => ['type' => 'ratio'],
                                        'children' => [
                                            [
                                                'name' => '[AGREGAT PEMBILANG] Perkara yang Selesai Berjalan di Pengadilan (SKK Substitusi)',
                                                'formula_config' => ['type' => 'sum'],
                                                'children' => [
                                                    ['name' => 'Jumlah putusan sidang perdata tingkat pertama/banding/kasasi selesai', 'is_input' => true]
                                                ]
                                            ],
                                            [
                                                'name' => '[AGREGAT PEMBAGI] Permasalahan Perdata Dimohonkan (SKK Litigasi Masuk)',
                                                'formula_config' => ['type' => 'sum'],
                                                'children' => [
                                                    ['name' => 'Total Surat Kuasa Khusus (SKK) litigasi dari instansi/BUMN', 'is_input' => true]
                                                ]
                                            ]
                                        ]
                                    ],
                                    [
                                        'name' => 'Sub-Indikator Perdata Non-Litigasi',
                                        'formula_config' => ['type' => 'ratio'],
                                        'children' => [
                                            [
                                                'name' => '[AGREGAT PEMBILANG] Permasalahan Non-Litigasi Selesai Terbit Laporan Akhir',
                                                'formula_config' => ['type' => 'sum'],
                                                'children' => [
                                                    ['name' => 'Jumlah negosiasi/mediasi/bantuan hukum luar pengadilan tuntas', 'is_input' => true]
                                                ]
                                            ],
                                            [
                                                'name' => '[AGREGAT PEMBAGI] Total Permasalahan Non-Litigasi Ditangani',
                                                'formula_config' => ['type' => 'sum'],
                                                'children' => [
                                                    ['name' => 'Total SKK non-litigasi masuk dari pemohon', 'is_input' => true]
                                                ]
                                            ]
                                        ]
                                    ],
                                    [
                                        'name' => 'Sub-Indikator Tata Usaha Negara (TUN) Litigasi',
                                        'formula_config' => ['type' => 'ratio'],
                                        'children' => [
                                            [
                                                'name' => '[AGREGAT PEMBILANG] Perkara TUN Selesai Diputus oleh Pengadilan',
                                                'formula_config' => ['type' => 'sum'],
                                                'children' => [
                                                    ['name' => 'Jumlah putusan sengketa TUN berkekuatan hukum tetap', 'is_input' => true]
                                                ]
                                            ],
                                            [
                                                'name' => '[AGREGAT PEMBAGI] Perkara TUN Ditangani (SKK TUN Litigasi)',
                                                'formula_config' => ['type' => 'sum'],
                                                'children' => [
                                                    ['name' => 'Total SKK TUN masuk menghadapi gugatan tata usaha negara', 'is_input' => true]
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ],

                            // COMPONENT P2: Layanan Hukum Pertimbangan
                            [
                                'name' => 'P2 - Tingkat Penjaminan Kualitas Pengajuan Pertimbangan Hukum',
                                'formula_config' => ['type' => 'average'],
                                'children' => [
                                    $this->buildSimpleRatioStage('Pendapat Hukum (Legal Opinion - LO)', 'Jumlah LO resmi diterbitkan dan diserahkan (Dokumen S-6)', 'Jumlah permohonan LO disetujui setelah telaah awal'),
                                    $this->buildSimpleRatioStage('Pendampingan Hukum (Legal Assistance - LA)', 'Jumlah pendampingan proyek selesai (Berita Acara penutupan)', 'Jumlah permohonan LA disetujui (Surat Perintah Kepala Satker)'),
                                    $this->buildSimpleRatioStage('Audit Hukum (Legal Audit)', 'Jumlah dokumen Laporan Hasil Audit Hukum (LHAH) terbit', 'Jumlah permintaan audit hukum institusi yang disetujui'),
                                    $this->buildSimpleRatioStage('Tindakan Hukum Lain (THL)', 'Jumlah tindakan penyelamatan keuangan via MoU/Negosiasi rampung', 'Jumlah mandat permohonan THL yang diterima resmi'),
                                ]
                            ]

                        ]
                    ]
                ]
            ],

            // =========================================================================
            // SASARAN STRATEGIS 4
            // =========================================================================
            [
                'name' => 'SS 4 - Terwujudnya Tata Kelola Organisasi yang Optimal, Transparan, dan Akuntabel',
                'formula_config' => ['type' => 'average'],
                'children' => [
                    [
                        'name' => 'IKSS 4.1 - Indeks Reformasi Birokrasi Kejaksaan RI',
                        'formula_config' => ['type' => 'average'],
                        'children' => [
                            [
                                'name' => 'Komponen Evaluasi Tata Kelola Birokrasi Satker (Top-Down Read-Only)',
                                'formula_config' => ['type' => 'average'],
                                'children' => [
                                    ['name' => 'Nilai SKM Layanan Publik Satker (Skala 0-100)', 'is_input' => true],
                                    ['name' => 'Nilai Indikator Kinerja Pelaksanaan Anggaran (IKPA) Kemenkeu', 'is_input' => true],
                                    ['name' => 'Nilai Hasil Evaluasi Sistem Akuntabilitas Kinerja (AKIP) Internal', 'is_input' => true],
                                    ['name' => 'Nilai Lembar Kerja Evaluasi Zona Integritas (LKE ZI) Tim Penilai', 'is_input' => true],
                                ]
                            ]
                        ]
                    ],
                    [
                        'name' => 'IKSS 4.2 - Tingkat Penerapan Etika Profesi Jaksa',
                        'formula_config' => ['type' => 'ratio'],
                        'children' => [
                            [
                                'name' => '[AGREGAT PEMBILANG] Jumlah Jaksa yang TIDAK Melakukan Pelanggaran Etika Profesi',
                                'formula_config' => ['type' => 'sum'],
                                'children' => [
                                    ['name' => 'Jumlah personil Jaksa bersih tanpa catatan hukuman disiplin / kode perilaku', 'is_input' => true]
                                ]
                            ],
                            [
                                'name' => '[AGREGAT PEMBAGI] Total Fungsional Jaksa pada Satuan Kerja',
                                'formula_config' => ['type' => 'sum'],
                                'children' => [
                                    ['name' => 'Jumlah keseluruhan riil PNS berstatus fungsional Jaksa aktif', 'is_input' => true]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        DB::transaction(function () use ($tree) {
            $this->masterIkssIds = $this->resolveMasterIkssIds();
            $this->seedTree($tree, null, null, null, []);

            Cache::store('file')->put(
                'ikss-parameter-catalog-version',
                (int) Cache::store('file')->get('ikss-parameter-catalog-version', 1) + 1
            );
        });

        $this->command->info(sprintf(
            'Selesai. Grup: %d, parameter: %d, dependency: %d, binding template: %d.',
            $this->counts['groups'],
            $this->counts['parameters'],
            $this->counts['dependencies'],
            $this->counts['bindings'],
        ));
    }

    /**
     * Engine Rekursif Pintar: Melakukan penanaman data berjenjang *N-tier infinite hierarchy*.
     */
    private function seedTree(array $nodes, ?int $parentId, ?string $logicalIkssId, ?IkssParameterGroup $group, array $path): array
    {
        $createdNodes = [];

        foreach ($nodes as $node) {
            $index = count($createdNodes) + 1;
            $nodePath = [...$path, $index];
            $name = (string) $node['name'];

            if ($this->extractSsCode($name) !== null) {
                $createdNodes = array_merge(
                    $createdNodes,
                    $this->seedTree($node['children'] ?? [], null, null, null, [])
                );

                continue;
            }

            $nodeLogicalIkssId = $this->extractIkssId($name) ?? $logicalIkssId;

            if ($nodeLogicalIkssId === null) {
                continue;
            }

            $masterIkssId = $this->masterIkssId($nodeLogicalIkssId);
            $currentGroup = $group ?? $this->ensureGroup($nodeLogicalIkssId, $masterIkssId);
            $isIkssRoot = $this->extractIkssId($name) !== null;
            $code = $this->parameterCode($nodeLogicalIkssId, $node, $nodePath, $isIkssRoot);
            $method = $this->calculationMethod($node);
            $isInput = (bool) ($node['is_input'] ?? false);

            $created = IkssParameter::query()->updateOrCreate(
                ['ikss_id' => $masterIkssId, 'code' => $code],
                $this->parameterPayload(
                    $node,
                    $masterIkssId,
                    $parentId,
                    $currentGroup->id,
                    $nodePath,
                    $method,
                    $isInput,
                    $isIkssRoot
                )
            );
            $this->counts['parameters']++;

            $children = [];

            if (!empty($node['children'])) {
                $children = $this->seedTree($node['children'], $created->id, $nodeLogicalIkssId, $currentGroup, $nodePath);
            }

            $this->syncDependencies($created, $children, $method);
            $createdNodes[] = $created;
        }

        return $createdNodes;
    }

    /**
     * Helper Generator Komponen PIDSUS per Sub-Program (Korupsi, Pajak, Bea Cukai, Cukai, Perekonomian).
     * Memotong replikasi kode yang terlalu panjang namun menghasilkan output hierarki yang masif dan presisi.
     */
    private function buildPidsusSubProgram(string $programName, bool $includeLidik): array
    {
        $stages = [];

        if ($includeLidik) {
            $stages[] = $this->buildSimpleRatioStage('Tahap Penyelidikan', "Jumlah perkara $programName diselesaikan tahap Lidik", "Jumlah perkara $programName ditangani tahap Lidik (P2)");
        }

        $stages[] = $this->buildSimpleRatioStage('Tahap Penyidikan', "Jumlah perkara $programName diselesaikan tahap Dik (Lanjut Tahap II/SP3/Lain)", "Jumlah perkara $programName ditangani tahap Dik (Sprindik P8)");
        $stages[] = $this->buildSimpleRatioStage('Tahap Prapenuntutan', "Jumlah perkara $programName diselesaikan tahap Prapenuntutan", "Jumlah perkara $programName ditangani tahap Prapenuntutan (SPDP Masuk)");
        $stages[] = $this->buildSimpleRatioStage('Tahap Penuntutan', "Jumlah perkara $programName diselesaikan tahap Penuntutan (Limpah Sidang/Henti)", "Jumlah perkara $programName ditangani tahap Penuntutan");
        $stages[] = $this->buildSimpleRatioStage('Tahap Eksekusi', "Jumlah terpidana $programName dieksekusi (Pidsus-38)", "Jumlah terpidana $programName berkekuatan hukum tetap (P-48)");

        return [
            'name' => "Program: $programName",
            'formula_config' => ['type' => 'average'],
            'children' => $stages
        ];
    }

    /**
     * Helper Generator Otomatis Pasangan Agregat Pembilang & Pembagi (Level 6 & 7).
     */
    private function buildSimpleRatioStage(string $stageName, string $pembilangInputName, string $pembagiInputName): array
    {
        return [
            'name' => $stageName,
            'formula_config' => ['type' => 'ratio'],
            'children' => [
                [
                    'name' => "[AGREGAT PEMBILANG] Keberhasilan Penyelesaian $stageName",
                    'formula_config' => ['type' => 'sum'],
                    'children' => [
                        ['name' => $pembilangInputName, 'is_input' => true]
                    ]
                ],
                [
                    'name' => "[AGREGAT PEMBAGI] Total Beban Kerja $stageName",
                    'formula_config' => ['type' => 'sum'],
                    'children' => [
                        ['name' => $pembagiInputName, 'is_input' => true]
                    ]
                ]
            ]
        ];
    }

    private function parameterPayload(
        array $node,
        string $ikssId,
        ?int $parentId,
        int $groupId,
        array $path,
        string $method,
        bool $isInput,
        bool $isIkssRoot
    ): array {
        $name = (string) $node['name'];
        $valueType = $this->valueTypeFromName($name, $method);
        $formulaConfig = $node['formula_config'] ?? [];

        if (in_array($method, ['ratio', 'percentage'], true)) {
            $formulaConfig['multiplier'] = $formulaConfig['multiplier'] ?? 100;
        }

        return [
            'parent_id' => $parentId,
            'group_id' => $groupId,
            'name' => $name,
            'description' => $this->descriptionFromPath($path, $method, $isInput),
            'parameter_role' => $isIkssRoot ? 'result' : $this->roleFromName($name),
            'input_mode' => 'scalar',
            'source_type' => $isInput ? 'manual' : 'formula',
            'source_reference' => null,
            'legacy_indicator_id' => null,
            'value_type' => $valueType,
            'unit' => $this->unitFromType($valueType, $method),
            'period_type' => 'quarterly',
            'calculation_method' => $isInput ? 'input' : $method,
            'aggregation_method' => $this->aggregationMethod($name, $method, $valueType),
            'aggregation_scope' => 'children',
            'entry_levels' => [3, 4],
            'aggregate_to_levels' => [2],
            'formula_config' => $formulaConfig === [] ? null : $formulaConfig,
            'decimal_places' => $valueType === 'integer' ? 0 : 2,
            'sort_order' => $this->sortOrder($path),
            'is_result' => $isIkssRoot,
            'is_required' => $isIkssRoot,
            'include_in_report' => true,
            'is_active' => true,
            'valid_from_year' => 2026,
            'valid_until_year' => null,
        ];
    }

    private function ensureGroup(string $logicalIkssId, string $masterIkssId): IkssParameterGroup
    {
        $code = 'ikss'.str_replace('-', '_', $logicalIkssId).'.tree';
        $group = IkssParameterGroup::query()->updateOrCreate(
            ['code' => $code],
            [
                'ikss_id' => $masterIkssId,
                'parent_id' => null,
                'name' => 'Pohon Parameter IKSS '.str_replace('-', '.', $logicalIkssId),
                'description' => 'Parameter hasil seeder hierarki SAKIP 2025-2029.',
                'section_code' => $masterIkssId,
                'group_type' => 'table',
                'settings' => [
                    'row_source' => 'fixed_parameters',
                    'columns' => ['No', 'Parameter', 'Nilai'],
                ],
                'sort_order' => $this->sortOrder(array_map('intval', explode('-', $logicalIkssId))),
                'is_active' => true,
            ]
        );
        $this->counts['groups']++;

        foreach ([2, 3, 4] as $templateLevel) {
            LkjipTemplateBinding::query()->updateOrCreate(
                ['template_level' => $templateLevel, 'binding_key' => 'group.'.$code],
                [
                    'template_level' => $templateLevel,
                    'binding_key' => 'group.'.$code,
                    'binding_type' => 'table',
                    'source_type' => 'group',
                    'source_key' => $code,
                    'marker' => '${table:'.$code.'}',
                    'formatter' => null,
                    'options' => ['anchors' => []],
                    'sort_order' => $group->sort_order,
                    'is_active' => true,
                ]
            );
            $this->counts['bindings']++;
        }

        return $group;
    }

    private function syncDependencies(IkssParameter $parameter, array $children, string $method): void
    {
        $parameter->dependencies()->delete();

        if ($children === [] || $parameter->calculation_method === 'input') {
            return;
        }

        foreach ($children as $index => $child) {
            IkssParameterDependency::query()->create([
                'parameter_id' => $parameter->id,
                'source_parameter_id' => $child->id,
                'role' => in_array($method, ['ratio', 'percentage'], true)
                    ? $this->ratioRoleFromName($child->name)
                    : 'component',
                'weight' => null,
                'sort_order' => $index,
            ]);
            $this->counts['dependencies']++;
        }
    }

    private function calculationMethod(array $node): string
    {
        if ((bool) ($node['is_input'] ?? false)) {
            return 'input';
        }

        return match ($node['formula_config']['type'] ?? 'average') {
            'ratio' => 'ratio',
            'sum' => 'sum',
            'weighted_average' => 'weighted_average',
            'min' => 'min',
            'max' => 'max',
            'latest' => 'latest',
            default => 'average',
        };
    }

    private function aggregationMethod(string $name, string $method, string $valueType): string
    {
        if ($method !== 'input') {
            return 'average';
        }

        $normalized = $this->normalize($name);

        if ($valueType === 'currency' || str_contains($normalized, 'jumlah') || str_contains($normalized, 'total')) {
            return 'sum';
        }

        return str_contains($normalized, 'nilai') || str_contains($normalized, 'indeks') || str_contains($normalized, 'skm')
            ? 'average'
            : 'sum';
    }

    private function valueTypeFromName(string $name, string $method): string
    {
        $normalized = $this->normalize($name);

        if (str_contains($normalized, 'rp') || str_contains($normalized, 'nominal') || str_contains($normalized, 'finansial')) {
            return 'currency';
        }

        if (in_array($method, ['ratio', 'percentage'], true) || str_contains($normalized, 'persentase')) {
            return 'percentage';
        }

        if (str_contains($normalized, 'nilai') || str_contains($normalized, 'indeks') || str_contains($normalized, 'skm')) {
            return 'number';
        }

        return 'integer';
    }

    private function unitFromType(string $valueType, string $method): ?string
    {
        return match ($valueType) {
            'percentage' => '%',
            'currency' => 'Rp',
            'number' => in_array($method, ['ratio', 'percentage'], true) ? '%' : 'nilai',
            default => 'unit',
        };
    }

    private function roleFromName(string $name): string
    {
        return match ($this->ratioRoleFromName($name)) {
            'numerator' => 'numerator',
            'denominator' => 'denominator',
            default => 'component',
        };
    }

    private function ratioRoleFromName(string $name): string
    {
        $normalized = $this->normalize($name);

        if (str_contains($normalized, 'pembilang')) {
            return 'numerator';
        }

        if (str_contains($normalized, 'pembagi') || str_contains($normalized, 'penyebut')) {
            return 'denominator';
        }

        return 'component';
    }

    private function parameterCode(string $logicalIkssId, array $node, array $path, bool $isIkssRoot): string
    {
        if (isset($node['code']) && filled($node['code'])) {
            return Str::limit((string) $node['code'], 100, '');
        }

        if ($isIkssRoot) {
            return 'ikss'.str_replace('-', '_', $logicalIkssId).'.result';
        }

        return Str::limit('ikss'.str_replace('-', '_', $logicalIkssId).'.n'.implode('_', $path), 100, '');
    }

    private function sortOrder(array $path): int
    {
        return (int) collect($path)
            ->take(4)
            ->reduce(fn (int $carry, int $segment) => ($carry * 100) + min($segment, 99), 0);
    }

    private function extractSsCode(string $name): ?string
    {
        return preg_match('/^SS\s*(\d+)/i', $name, $match) ? 'SS'.$match[1] : null;
    }

    private function extractIkssId(string $name): ?string
    {
        return preg_match('/^IKSS\s*(\d+)[\.\-](\d+)/i', $name, $match)
            ? $match[1].'-'.$match[2]
            : null;
    }

    private function masterIkssId(string $logicalIkssId): string
    {
        return $this->masterIkssIds[$logicalIkssId] ?? 'IKSS'.$logicalIkssId;
    }

    private function resolveMasterIkssIds(): array
    {
        $logicalIds = ['1-1', '2-1', '2-2', '2-3', '3-1', '4-1', '4-2'];
        $masterIds = Schema::hasTable('indikator_sastra')
            ? DB::table('indikator_sastra')->pluck('kode_indikator')->map(fn ($id) => (string) $id)
            : collect();

        return collect($logicalIds)->mapWithKeys(function (string $logicalId) use ($masterIds) {
            $prefixed = 'IKSS'.$logicalId;

            return [$logicalId => $masterIds->contains($prefixed)
                ? $prefixed
                : ($masterIds->contains($logicalId) ? $logicalId : $prefixed)];
        })->all();
    }

    private function descriptionFromPath(array $path, string $method, bool $isInput): string
    {
        $kind = $isInput ? 'input manual' : 'hasil formula '.$method;

        return 'Node '.implode('.', $path).' pada pohon parameter SAKIP; '.$kind.'.';
    }

    private function normalize(string $value): string
    {
        return Str::ascii(mb_strtolower($value, 'UTF-8'));
    }
}
