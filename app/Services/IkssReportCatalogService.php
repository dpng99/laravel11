<?php

namespace App\Services;

use App\Models\IkssParameter;
use App\Models\IkssParameterGroup;
use App\Models\LkjipTemplateBinding;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class IkssReportCatalogService
{
    private array $dependencyDefinitions = [];
    private array $ikssIds = [];

    public function seedExamples(): array
    {
        return DB::transaction(function () {
            $strategicMaster = $this->syncStrategicMaster();
            $this->ikssIds = $this->resolveMasterIkssIds();
            $this->normalizeCatalogIkssIds();
            $this->dependencyDefinitions = [];

            $groups = $this->seedGroups();
            $parameters = $this->seedParameters($groups);
            $dependencies = $this->seedDependencies($parameters);
            $bindings = $this->seedBindings($groups);
            $deactivated = $this->deactivateObsoleteDefinitions();

            IkssParameter::query()
                ->where('code', 'like', 'legacy\_%')
                ->update([
                    'parameter_role' => 'result',
                    'input_mode' => 'scalar',
                    'source_type' => 'legacy',
                ]);

            Cache::store('file')->put(
                'ikss-parameter-catalog-version',
                (int) Cache::store('file')->get('ikss-parameter-catalog-version', 1) + 1
            );

            return [
                'strategic_master' => $strategicMaster,
                'groups' => count($groups),
                'parameters' => count($parameters),
                'dependencies' => $dependencies,
                'template_bindings' => $bindings,
                'obsolete_definitions_deactivated' => $deactivated,
            ];
        });
    }

    private function seedGroups(): array
    {
        $definitions = [
            $this->group('1-1', 'ikss1.skm.services', 'Survei Kepuasan Masyarakat per Pelayanan', 10, 'Daftar pelayanan dan nilai SKM satuan kerja.', 'table', [
                'row_source' => 'parameter_items',
                'parameter_code' => 'skm.nilai_satker',
                'columns' => ['No', 'Nama Pelayanan', 'Nilai SKM'],
                'include_average' => true,
                'binding_anchors' => ['No Nama Pelayanan Nilai Survei Kepuasan Masyarakat'],
            ]),
            $this->group('1-1', 'ikss1.skm.region', 'Rekapitulasi SKM Satuan Kerja di Wilayah Kejaksaan Tinggi', 15, 'Daftar nilai SKM seluruh Kejari dan Cabjari dalam wilayah Kejati.', 'table', [
                'row_source' => 'regional_satkers',
                'parameter_code' => 'skm.nilai_satker',
                'columns' => ['No', 'Satuan Kerja', 'Rata-rata Nilai SKM'],
                'include_average' => true,
                'template_levels' => [2],
                'binding_anchors' => ['No Satuan Kerja Rata-rata Nilai Survei Kepuasan Masyarakat'],
            ]),
            $this->group('1-1', 'ikss1.skm.formula', 'Capaian Indeks Persepsi Publik', 20, null, 'table', [
                'binding_anchors' => ['Indeks Persepsi Publik terhadap Citra Kejaksaan RI kontribusi satuan kerja terhadap citra Kejaksaan RI'],
            ]),
            $this->group('1-1', 'ikss1.context', 'Assessment IKSS 1.1', 90),

            $this->group('2-1', 'ikss2.pidum', 'Pidana Umum', 100),
            $this->group('2-1', 'ikss2.pidsus.korupsi', 'Pidana Khusus - Korupsi dan TPPU', 110),
            $this->group('2-1', 'ikss2.pidsus.perpajakan', 'Pidana Khusus - Perpajakan dan TPPU', 120),
            $this->group('2-1', 'ikss2.pidsus.kepabeanan', 'Pidana Khusus - Kepabeanan dan TPPU', 130),
            $this->group('2-1', 'ikss2.pidsus.cukai', 'Pidana Khusus - Cukai dan TPPU', 140),
            $this->group('2-1', 'ikss2.pidsus.perekonomian', 'Pidana Khusus - Kerugian Perekonomian Negara dan TPPU', 150),
            $this->group('2-1', 'ikss2.pidmil', 'Pidana Militer / Koneksitas', 160),
            $this->group('2-1', 'ikss2.datun.penegakan', 'Penegakan Hukum Perdata dan TUN', 170),
            $this->group('2-1', 'ikss2.pengendalian.k1', 'K1 - Keberhasilan Penanganan Perkara', 180),
            $this->group('2-1', 'ikss2.pengendalian.k2', 'K2 - Mediasi Penal, Diskresi Penuntutan, dan Denda Damai', 190),
            $this->group('2-1', 'ikss2.pengendalian.k3', 'K3 - Alternatif Pemidanaan', 200),
            $this->group('2-1', 'ikss2.pengendalian.result', 'Capaian Persentase Peningkatan Pengendalian Perkara', 210),
            $this->group('2-1', 'ikss2.pengendalian.context', 'Assessment IKSS 2.1', 290),

            $this->group('2-2', 'ikss2.intelijen', 'Kegiatan dan Operasi Intelijen Penegakan Hukum', 300),
            $this->group('2-2', 'ikss2.intelijen.context', 'Assessment IKSS 2.2', 390),

            $this->group('2-3', 'ikss2.aset.core', 'Penelusuran, Perampasan, dan Pemulihan Aset', 400),
            $this->group('2-3', 'ikss2.aset.keuangan', 'Penyelamatan, Pengembalian, dan Pembayaran Denda', 410),
            $this->group('2-3', 'ikss2.aset.context', 'Assessment IKSS 2.3', 490),

            $this->group('3-1', 'ikss3.datun.perkara', 'Penanganan Perkara Perdata dan Tata Usaha Negara', 500),
            $this->group('3-1', 'ikss3.datun.layanan', 'Pendapat, Pendampingan, Audit, dan Tindakan Hukum Lain', 510),
            $this->group('3-1', 'ikss3.datun.result', 'Capaian Kewenangan Advocaat Generaal', 520),
            $this->group('3-1', 'ikss3.datun.context', 'Assessment IKSS 3.1', 590),

            $this->group('4-1', 'ikss4.rb.components', 'Komponen Indeks Reformasi Birokrasi', 600),
            $this->group('4-1', 'ikss4.rb.dipa', 'Alokasi DIPA per Program', 605, 'Pagu DIPA terbaru menurut program.', 'table', [
                'row_source' => 'budget_allocations',
                'columns' => ['No', 'Program', 'Anggaran (Rp)'],
                'binding_anchors' => ['No Program Anggaran (Rp)'],
            ]),
            $this->group('4-1', 'ikss4.rb.ikpa', 'Rincian Indikator Kinerja Pelaksanaan Anggaran', 610),
            $this->group('4-1', 'ikss4.rb.sakip', 'Rincian Nilai SAKIP Internal', 620),
            $this->group('4-1', 'ikss4.rb.anggaran', 'Realisasi Anggaran dan Prioritas Nasional', 630, null, 'table', [
                'row_source' => 'budget_realization',
                'columns' => ['No', 'Program', 'Anggaran (Rp)', 'Realisasi (Rp)', 'Persen (%)'],
                'binding_anchors' => ['No Program Anggaran (Rp) Realisasi (Rp) Persen (%)'],
            ]),
            $this->group('4-1', 'ikss4.rb.context', 'Assessment IKSS 4.1', 690),

            $this->group('4-2', 'ikss4.etika', 'Penerapan Etika Profesi Jaksa', 700),
            $this->group('4-2', 'ikss4.etika.context', 'Assessment IKSS 4.2', 790),
        ];
        $groups = [];

        foreach ($definitions as $definition) {
            $definition['ikss_id'] = $this->masterIkssId($definition['ikss_id']);
            $definition['section_code'] = $definition['ikss_id'];
            $groups[$definition['code']] = IkssParameterGroup::query()->updateOrCreate(
                ['code' => $definition['code']],
                $definition + ['is_active' => true]
            );
        }

        return $groups;
    }

    private function seedParameters(array $groups): array
    {
        $definitions = [];

        $this->addInput($definitions, '1-1', 'ikss1.skm.services', 'skm.nilai_satker', 'Rata-rata nilai SKM satuan kerja', [
            'parameter_role' => 'result',
            'input_mode' => 'table',
            'value_type' => 'number',
            'unit' => 'nilai',
            'aggregation_method' => 'average',
            'decimal_places' => 2,
            'is_result' => true,
            'is_required' => true,
            'formula_config' => ['minimum' => 0, 'maximum' => 100],
        ]);
        $this->addTargetAssessment($definitions, '1-1', 'ikss1.skm.formula', 'skm', 'skm.nilai_satker', 20);
        $this->addNarratives($definitions, '1-1', 'ikss1.context', 'skm', 900);

        $k1Results = [];
        $this->addPidumParameters($definitions, $k1Results);
        $this->addPidsusParameters($definitions, $k1Results);
        $this->addPidmilParameters($definitions, $k1Results);

        $this->addRatio(
            $definitions,
            '2-1',
            'ikss2.datun.penegakan',
            'datun.penegakan',
            'Tingkat keberhasilan penanganan perkara pada Bidang Perdata dan Tata Usaha Negara',
            'Jumlah gugatan/permohonan penegakan hukum bidang DATUN yang dikabulkan oleh hakim',
            'Jumlah gugatan/permohonan penegakan hukum bidang DATUN',
            800
        );
        $k1Results[] = 'datun.penegakan.tingkat_keberhasilan';

        $this->addDerived($definitions, '2-1', 'ikss2.pengendalian.k1', 'pengendalian.k1', 'K1 - Tingkat keberhasilan penanganan perkara', 'average', $k1Results, [
            'parameter_role' => 'component',
            'value_type' => 'percentage',
            'unit' => '%',
            'decimal_places' => 2,
            'is_required' => true,
            'sort_order' => 900,
        ]);

        $this->addRatio(
            $definitions,
            '2-1',
            'ikss2.pengendalian.k2',
            'pidum.restorative',
            'Persentase perkara pidana umum yang diselesaikan melalui mediasi penal / restorative justice',
            'Jumlah perkara tindak pidana umum yang diselesaikan melalui mediasi penal / restorative justice',
            'Jumlah perkara tindak pidana umum yang memenuhi syarat mediasi penal / restorative justice',
            910
        );
        $this->addRatio(
            $definitions,
            '2-1',
            'ikss2.pengendalian.k2',
            'pidsus.denda_damai',
            'Persentase perkara pidana khusus yang diselesaikan melalui denda damai',
            'Jumlah perkara tindak pidana khusus yang diselesaikan melalui denda damai',
            'Jumlah perkara tindak pidana khusus yang memenuhi syarat denda damai',
            920
        );
        $this->addDerived($definitions, '2-1', 'ikss2.pengendalian.k2', 'pengendalian.k2', 'K2 - Persentase penanganan melalui mediasi penal, diskresi penuntutan, dan denda damai', 'average', [
            'pidum.restorative.tingkat_keberhasilan',
            'pidsus.denda_damai.tingkat_keberhasilan',
        ], [
            'parameter_role' => 'component',
            'value_type' => 'percentage',
            'unit' => '%',
            'decimal_places' => 2,
            'is_required' => true,
            'sort_order' => 930,
        ]);

        $this->addRatio(
            $definitions,
            '2-1',
            'ikss2.pengendalian.k3',
            'pidum.alternatif_pemidanaan',
            'K3 - Persentase penuntutan melalui alternatif pemidanaan',
            'Jumlah perkara yang dituntut dengan alternatif pemidanaan',
            'Jumlah perkara yang memenuhi syarat untuk dituntut dengan alternatif pemidanaan',
            940
        );

        $this->addDerived($definitions, '2-1', 'ikss2.pengendalian.result', 'pengendalian.capaian', 'Persentase peningkatan pengendalian perkara', 'average', [
            'pengendalian.k1',
            'pengendalian.k2',
            'pidum.alternatif_pemidanaan.tingkat_keberhasilan',
        ], [
            'parameter_role' => 'result',
            'value_type' => 'percentage',
            'unit' => '%',
            'decimal_places' => 2,
            'is_result' => true,
            'is_required' => true,
            'sort_order' => 950,
        ]);
        $this->addTargetAssessment($definitions, '2-1', 'ikss2.pengendalian.result', 'pengendalian', 'pengendalian.capaian', 960);
        $this->addNarratives($definitions, '2-1', 'ikss2.pengendalian.context', 'pengendalian', 990);

        $this->addRatio(
            $definitions,
            '2-2',
            'ikss2.intelijen',
            'intelijen.operasi',
            'Tingkat keberhasilan kegiatan dan operasi intelijen penegakan hukum',
            'Jumlah kegiatan/operasi intelijen penegakan hukum (LID/PAM/GAL) yang berhasil dilaksanakan',
            'Jumlah seluruh kegiatan/operasi intelijen penegakan hukum (LID/PAM/GAL) yang dilaksanakan',
            1000,
            ['is_result' => true, 'is_required' => true]
        );
        $this->addTargetAssessment($definitions, '2-2', 'ikss2.intelijen', 'intelijen', 'intelijen.operasi.tingkat_keberhasilan', 1010);
        $this->addNarratives($definitions, '2-2', 'ikss2.intelijen.context', 'intelijen', 1090);

        $assetResults = [];
        foreach ([
            ['aset.penelusuran', 'Tingkat keberhasilan kegiatan penelusuran aset', 'Nilai taksiran aset yang diserahkan kepada pemohon', 'Nilai taksiran aset hasil penelusuran'],
            ['aset.perampasan', 'Tingkat keberhasilan perampasan aset hasil tindak pidana', 'Nilai aset hasil tindak pidana yang dirampas berdasarkan berita acara penerimaan aset', 'Nilai aset hasil tindak pidana yang berhasil dieksekusi'],
            ['aset.pemulihan', 'Tingkat keberhasilan pemulihan aset hasil tindak pidana', 'Nilai aset hasil tindak pidana yang diselesaikan melalui lelang, PSP, hibah, dan mekanisme lainnya', 'Nilai aset hasil tindak pidana yang dinilai'],
        ] as $index => [$prefix, $name, $numerator, $denominator]) {
            $this->addRatio($definitions, '2-3', 'ikss2.aset.core', $prefix, $name, $numerator, $denominator, 1100 + ($index * 10));
            $assetResults[] = $prefix.'.tingkat_keberhasilan';
        }
        $this->addDerived($definitions, '2-3', 'ikss2.aset.core', 'aset.capaian', 'Tingkat keberhasilan pemulihan aset negara', 'average', $assetResults, [
            'parameter_role' => 'result',
            'value_type' => 'percentage',
            'unit' => '%',
            'decimal_places' => 2,
            'is_result' => true,
            'is_required' => true,
            'sort_order' => 1140,
        ]);
        $this->addAssetFinancialContext($definitions);
        $this->addTargetAssessment($definitions, '2-3', 'ikss2.aset.core', 'aset', 'aset.capaian', 1180);
        $this->addNarratives($definitions, '2-3', 'ikss2.aset.context', 'aset', 1190);

        $this->addDatunParameters($definitions);
        $this->addRbParameters($definitions);
        $this->addEthicsParameters($definitions);

        $parameters = [];
        foreach ($definitions as $definition) {
            $groupCode = $definition['group'];
            unset($definition['group']);
            $definition['ikss_id'] = $this->masterIkssId($definition['ikss_id']);
            if (($definition['source_type'] ?? null) === 'target' && isset($definition['source_reference'])) {
                $definition['source_reference'] = $this->masterIkssId($definition['source_reference']);
            }
            $definition = array_merge($this->parameterDefaults(), $definition, [
                'group_id' => $groups[$groupCode]->id,
            ]);

            $parameters[$definition['code']] = IkssParameter::query()->updateOrCreate(
                ['ikss_id' => $definition['ikss_id'], 'code' => $definition['code']],
                $definition
            );
        }

        return $parameters;
    }

    private function addPidumParameters(array &$definitions, array &$k1Results): void
    {
        $this->addRatio($definitions, '2-1', 'ikss2.pidum', 'pidum.prapenuntutan', 'Tingkat keberhasilan penanganan perkara pidana umum hingga prapenuntutan', 'Jumlah perkara yang berhasil diselesaikan pada tahap prapenuntutan', 'Jumlah SPDP yang diterima dan telah diterbitkan P-16', 100, [
            'numerator_code' => 'pidum.prapenuntutan.perkara_diselesaikan',
            'denominator_code' => 'pidum.prapenuntutan.spdp_diterima',
        ]);
        $this->addInputs($definitions, '2-1', 'ikss2.pidum', [
            ['pidum.prapenuntutan.penyerahan_tersangka_barang_bukti', 'Jumlah perkara yang telah dilakukan penyerahan tersangka dan barang bukti kepada Jaksa'],
            ['pidum.prapenuntutan.spdp_dikembalikan_lebih_7_hari', 'Jumlah SPDP yang dikembalikan karena melebihi 7 hari dari terbitnya Sprindik'],
            ['pidum.prapenuntutan.spdp_tanpa_berkas_dikembalikan', 'Jumlah SPDP yang tidak diikuti berkas perkara dan dikembalikan kepada penyidik'],
            ['pidum.prapenuntutan.spdp_berkas_dikembalikan_lebih_30_hari', 'Jumlah SPDP dan berkas perkara yang dikembalikan karena belum dilakukan penyerahan tersangka dan barang bukti melebihi 30 hari'],
            ['pidum.prapenuntutan.berkas_dihentikan_penyidikannya', 'Jumlah berkas perkara yang dihentikan penyidikannya'],
        ], 110);

        $this->addRatio($definitions, '2-1', 'ikss2.pidum', 'pidum.penuntutan', 'Tingkat keberhasilan penanganan perkara pidana umum hingga penuntutan', 'Jumlah perkara yang berhasil diselesaikan pada tahap penuntutan', 'Jumlah surat perintah penunjukan Jaksa Penuntut Umum (P-16A)', 200);
        $this->addInputs($definitions, '2-1', 'ikss2.pidum', [
            ['pidum.penuntutan.restorative', 'Jumlah perkara yang dihentikan penuntutannya melalui pendekatan keadilan restoratif'],
            ['pidum.penuntutan.diversi', 'Jumlah perkara anak yang dihentikan penuntutannya melalui diversi'],
            ['pidum.penuntutan.alasan_sah_lain', 'Jumlah perkara yang dihentikan penuntutannya dengan alasan sah lainnya'],
            ['pidum.penuntutan.dilimpahkan_pengadilan', 'Jumlah berkas perkara yang dilimpahkan oleh Jaksa kepada Pengadilan'],
        ], 210);

        $this->addRatio($definitions, '2-1', 'ikss2.pidum', 'pidum.eksekusi', 'Tingkat keberhasilan penanganan perkara pidana umum yang berkekuatan hukum tetap dan telah dieksekusi', 'Jumlah terpidana perkara pidana umum yang telah dieksekusi', 'Jumlah terpidana perkara pidana umum berdasarkan putusan berkekuatan hukum tetap (P-48)', 300);

        $k1Results = array_merge($k1Results, [
            'pidum.prapenuntutan.tingkat_keberhasilan',
            'pidum.penuntutan.tingkat_keberhasilan',
            'pidum.eksekusi.tingkat_keberhasilan',
        ]);
    }

    private function addPidsusParameters(array &$definitions, array &$k1Results): void
    {
        $programs = [
            'korupsi' => [
                'label' => 'tindak pidana korupsi dan TPPU',
                'group' => 'ikss2.pidsus.korupsi',
                'stages' => ['penyelidikan', 'penyidikan', 'prapenuntutan', 'penuntutan', 'eksekusi'],
            ],
            'perpajakan' => [
                'label' => 'tindak pidana perpajakan dan TPPU',
                'group' => 'ikss2.pidsus.perpajakan',
                'stages' => ['prapenuntutan', 'penuntutan', 'eksekusi'],
            ],
            'kepabeanan' => [
                'label' => 'tindak pidana kepabeanan dan TPPU',
                'group' => 'ikss2.pidsus.kepabeanan',
                'stages' => ['prapenuntutan', 'penuntutan', 'eksekusi'],
            ],
            'cukai' => [
                'label' => 'tindak pidana cukai dan TPPU',
                'group' => 'ikss2.pidsus.cukai',
                'stages' => ['prapenuntutan', 'penuntutan', 'eksekusi'],
            ],
            'perekonomian' => [
                'label' => 'tindak pidana yang menyebabkan kerugian perekonomian negara dan TPPU',
                'group' => 'ikss2.pidsus.perekonomian',
                'stages' => ['penyelidikan', 'penyidikan', 'prapenuntutan', 'penuntutan', 'eksekusi'],
            ],
        ];
        $sort = 400;

        foreach ($programs as $program => $config) {
            foreach ($config['stages'] as $stage) {
                $prefix = "pidsus.{$program}.{$stage}";
                $stageLabel = $this->stageLabel($stage);
                $denominator = match ($stage) {
                    'penyelidikan' => "Jumlah perkara {$config['label']} yang diterima pada tahap penyelidikan",
                    'penyidikan' => "Jumlah perkara {$config['label']} yang diterima pada tahap penyidikan",
                    'prapenuntutan' => "Jumlah perkara {$config['label']} yang ditangani pada tahap prapenuntutan (SPDP)",
                    'penuntutan' => "Jumlah perkara {$config['label']} yang ditangani pada tahap penuntutan",
                    'eksekusi' => "Jumlah terpidana perkara {$config['label']} yang telah memiliki putusan berkekuatan hukum tetap",
                };
                $numerator = match ($stage) {
                    'eksekusi' => "Jumlah terpidana perkara {$config['label']} yang telah dieksekusi",
                    default => "Jumlah perkara {$config['label']} yang diselesaikan pada tahap {$stageLabel}",
                };

                $this->addRatio($definitions, '2-1', $config['group'], $prefix, "Tingkat penyelesaian perkara {$config['label']} pada tahap {$stageLabel}", $numerator, $denominator, $sort);
                $k1Results[] = $prefix.'.tingkat_keberhasilan';
                $sort += 10;

                if (in_array($stage, ['penyidikan', 'prapenuntutan', 'penuntutan'], true)) {
                    $details = match ($stage) {
                        'penyidikan' => [
                            ["{$prefix}.dihentikan_kepentingan_umum", 'Jumlah perkara yang dihentikan penyidikannya untuk kepentingan umum'],
                            ["{$prefix}.dilanjutkan_tahap_ii", 'Jumlah perkara yang dilanjutkan ke tahap II'],
                            ["{$prefix}.dilimpahkan_instansi_lain", 'Jumlah perkara yang dilimpahkan pada instansi lain'],
                        ],
                        'prapenuntutan' => [
                            ["{$prefix}.dilanjutkan_tahap_ii", 'Jumlah perkara yang dilanjutkan ke tahap II'],
                            ["{$prefix}.pengembalian_spdp", 'Jumlah pengembalian SPDP'],
                        ],
                        'penuntutan' => [
                            ["{$prefix}.dilimpahkan_pengadilan", 'Jumlah perkara yang dilimpahkan ke pengadilan'],
                            ["{$prefix}.dihentikan_penuntutan", 'Jumlah perkara yang dihentikan pada tahap penuntutan'],
                        ],
                    };
                    $this->addInputs($definitions, '2-1', $config['group'], $details, $sort);
                    $sort += count($details) + 1;
                }
            }
        }
    }

    private function addPidmilParameters(array &$definitions, array &$k1Results): void
    {
        $sort = 700;
        foreach (['penyelidikan', 'penyidikan', 'prapenuntutan', 'eksekusi'] as $stage) {
            $prefix = "pidmil.koneksitas.{$stage}";
            $stageLabel = $this->stageLabel($stage);
            $numerator = $stage === 'eksekusi'
                ? 'Jumlah perkara koneksitas yang dieksekusi sesuai isi putusan secara tepat waktu'
                : "Jumlah perkara koneksitas yang berhasil diselesaikan pada tahap {$stageLabel}";
            $denominator = $stage === 'eksekusi'
                ? 'Jumlah total perkara koneksitas yang telah berkekuatan hukum tetap'
                : "Jumlah perkara koneksitas yang ditangani pada tahap {$stageLabel}";
            $this->addRatio($definitions, '2-1', 'ikss2.pidmil', $prefix, "Tingkat keberhasilan penanganan perkara koneksitas pada tahap {$stageLabel}", $numerator, $denominator, $sort);
            $k1Results[] = $prefix.'.tingkat_keberhasilan';
            $sort += 10;
        }
    }

    private function addAssetFinancialContext(array &$definitions): void
    {
        $this->addInputs($definitions, '2-3', 'ikss2.aset.keuangan', [
            ['aset.denda.korupsi_putusan', 'Nilai kerugian keuangan negara (pembayaran uang pengganti) dalam perkara tindak pidana korupsi yang berhasil dikembalikan'],
            ['aset.denda.perpajakan_dibayar', 'Jumlah denda yang telah dibayar dalam perkara perpajakan dan TPPU'],
            ['aset.denda.kepabeanan_dibayar', 'Jumlah denda yang telah dibayar dalam perkara kepabeanan dan TPPU'],
            ['aset.denda.cukai_dibayar', 'Jumlah denda yang telah dibayar dalam perkara cukai dan TPPU'],
            ['aset.penyelamatan.sebelum_inkracht', 'Penyelamatan keuangan negara sebelum putusan inkracht'],
            ['aset.penyelamatan.setelah_inkracht', 'Penyelamatan keuangan negara setelah putusan inkracht'],
        ], 1150, ['value_type' => 'currency', 'unit' => 'Rp', 'decimal_places' => 2]);

        $this->addRatio($definitions, '2-3', 'ikss2.aset.keuangan', 'aset.perdata.litigasi', 'Tingkat keberhasilan penyelamatan keuangan negara melalui jalur perdata litigasi', 'Nilai keuangan negara yang berhasil diselamatkan melalui jalur perdata litigasi', 'Nilai potensi ancaman kerugian negara yang ditargetkan melalui jalur perdata litigasi', 1160);
        $this->addRatio($definitions, '2-3', 'ikss2.aset.keuangan', 'aset.perdata.non_litigasi', 'Tingkat keberhasilan pemulihan keuangan negara melalui jalur perdata non-litigasi', 'Nilai keuangan negara yang berhasil dipulihkan melalui jalur perdata non-litigasi', 'Nilai keuangan negara yang memungkinkan untuk dipulihkan melalui jalur perdata non-litigasi', 1170);
    }

    private function addDatunParameters(array &$definitions): void
    {
        $p1 = [];
        foreach ([
            ['datun.perdata.litigasi', 'Tingkat keberhasilan penanganan perkara perdata melalui jalur litigasi', 'Jumlah proses penanganan permasalahan perdata yang berjalan/ditangani di pengadilan pada semua tingkatan (SKK Substitusi)', 'Jumlah permasalahan perdata yang dimohonkan melalui jalur litigasi (Jumlah SKK Litigasi tahun berjalan)'],
            ['datun.perdata.non_litigasi', 'Tingkat keberhasilan penanganan perkara perdata melalui jalur non-litigasi', 'Jumlah permasalahan perdata non-litigasi yang diselesaikan (Laporan Akhir Penyelesaian)', 'Jumlah permasalahan perdata yang ditangani melalui jalur non-litigasi dalam tahun berjalan (SKK non-Litigasi tahun berjalan)'],
            ['datun.tun.litigasi', 'Tingkat keberhasilan penanganan perkara tata usaha negara melalui jalur litigasi', 'Jumlah perkara TUN yang diputus oleh pengadilan pada semua tingkatan (Putusan)', 'Jumlah perkara TUN yang ditangani melalui jalur litigasi dalam tahun berjalan (SKK TUN Litigasi)'],
        ] as $index => [$prefix, $name, $numerator, $denominator]) {
            $this->addRatio($definitions, '3-1', 'ikss3.datun.perkara', $prefix, $name, $numerator, $denominator, 1200 + ($index * 10));
            $p1[] = $prefix.'.tingkat_keberhasilan';
        }
        $this->addDerived($definitions, '3-1', 'ikss3.datun.perkara', 'datun.p1', 'P1 - Tingkat keberhasilan penanganan perkara perdata dan tata usaha negara', 'average', $p1, [
            'parameter_role' => 'component',
            'value_type' => 'percentage',
            'unit' => '%',
            'decimal_places' => 2,
            'is_required' => true,
            'sort_order' => 1240,
        ]);

        $p2 = [];
        foreach ([
            ['datun.pendapat_hukum', 'Persentase penyelesaian pendapat hukum', 'Jumlah pendapat hukum yang diselesaikan dengan terbitnya dokumen LO/Pendapat Hukum', 'Jumlah permohonan pendapat hukum yang disetujui setelah telaahan'],
            ['datun.pendampingan_hukum', 'Persentase penyelesaian pendampingan hukum', 'Jumlah pendampingan hukum yang diselesaikan dengan terbitnya laporan akhir', 'Jumlah permohonan pendampingan hukum yang disetujui setelah telaahan'],
            ['datun.audit_hukum', 'Persentase penyelesaian audit hukum', 'Jumlah audit hukum yang diselesaikan dengan terbitnya laporan akhir', 'Jumlah permohonan audit hukum yang disetujui setelah telaahan'],
            ['datun.tindakan_hukum_lain', 'Persentase penyelesaian tindakan hukum lain', 'Jumlah tindakan hukum lain yang diselesaikan dengan terbitnya laporan akhir', 'Jumlah permohonan tindakan hukum lain yang disetujui setelah telaahan'],
        ] as $index => [$prefix, $name, $numerator, $denominator]) {
            $this->addRatio($definitions, '3-1', 'ikss3.datun.layanan', $prefix, $name, $numerator, $denominator, 1250 + ($index * 10));
            $p2[] = $prefix.'.tingkat_keberhasilan';
        }
        $this->addDerived($definitions, '3-1', 'ikss3.datun.layanan', 'datun.pendampingan_audit', 'Rata-rata capaian pendampingan hukum dan audit hukum', 'average', [
            'datun.pendampingan_hukum.tingkat_keberhasilan',
            'datun.audit_hukum.tingkat_keberhasilan',
        ], [
            'parameter_role' => 'component',
            'value_type' => 'percentage',
            'unit' => '%',
            'decimal_places' => 2,
            'sort_order' => 1290,
        ]);
        $this->addDerived($definitions, '3-1', 'ikss3.datun.layanan', 'datun.p2', 'P2 - Tingkat penjaminan kualitas pengajuan pertimbangan hukum', 'average', [
            'datun.pendapat_hukum.tingkat_keberhasilan',
            'datun.pendampingan_audit',
            'datun.tindakan_hukum_lain.tingkat_keberhasilan',
        ], [
            'parameter_role' => 'component',
            'value_type' => 'percentage',
            'unit' => '%',
            'decimal_places' => 2,
            'is_required' => true,
            'sort_order' => 1300,
        ]);

        $this->addDerived($definitions, '3-1', 'ikss3.datun.result', 'datun.advocaat_generaal.capaian', 'Tingkat efektivitas pelaksanaan kewenangan Advocaat Generaal', 'average', ['datun.p1', 'datun.p2'], [
            'parameter_role' => 'result',
            'value_type' => 'percentage',
            'unit' => '%',
            'decimal_places' => 2,
            'is_result' => true,
            'is_required' => true,
            'sort_order' => 1310,
        ]);
        $this->addTargetAssessment($definitions, '3-1', 'ikss3.datun.result', 'datun.advocaat_generaal', 'datun.advocaat_generaal.capaian', 1320);
        $this->addNarratives($definitions, '3-1', 'ikss3.datun.context', 'datun.advocaat_generaal', 1390);
    }

    private function addRbParameters(array &$definitions): void
    {
        $this->addDerived($definitions, '4-1', 'ikss4.rb.components', 'rb.skm', 'Rata-rata Survei Kepuasan Masyarakat di wilayah satuan kerja', 'average', ['skm.nilai_satker'], [
            'parameter_role' => 'component',
            'value_type' => 'number',
            'unit' => 'nilai',
            'decimal_places' => 2,
            'is_required' => true,
            'sort_order' => 1400,
        ]);
        foreach ([
            ['rb.ikpa', 'Rata-rata Indikator Kinerja Pelaksanaan Anggaran (IKPA) di wilayah satuan kerja'],
            ['rb.sakip_internal', 'Rata-rata hasil evaluasi AKIP internal di wilayah satuan kerja'],
            ['rb.lke_zi', 'Rata-rata nilai LKE Zona Integritas oleh TPD di wilayah satuan kerja'],
        ] as $index => [$code, $name]) {
            $this->addInput($definitions, '4-1', 'ikss4.rb.components', $code, $name, [
                'parameter_role' => 'component',
                'value_type' => 'number',
                'unit' => 'nilai',
                'aggregation_method' => 'average',
                'decimal_places' => 2,
                'is_required' => true,
                'formula_config' => ['minimum' => 0, 'maximum' => 100],
                'sort_order' => 1410 + ($index * 10),
            ]);
        }
        $this->addDerived($definitions, '4-1', 'ikss4.rb.components', 'rb.capaian', 'Indeks Reformasi Birokrasi Kejaksaan RI', 'average', ['rb.skm', 'rb.ikpa', 'rb.sakip_internal', 'rb.lke_zi'], [
            'parameter_role' => 'result',
            'value_type' => 'number',
            'unit' => 'nilai',
            'decimal_places' => 2,
            'is_result' => true,
            'is_required' => true,
            'sort_order' => 1450,
        ]);
        $this->addTargetAssessment($definitions, '4-1', 'ikss4.rb.components', 'rb', 'rb.capaian', 1460);

        $this->addInputs($definitions, '4-1', 'ikss4.rb.ikpa', [
            ['rb.ikpa.revisi_dipa', 'Revisi DIPA'],
            ['rb.ikpa.deviasi_hal_iii_dipa', 'Deviasi Halaman III DIPA'],
            ['rb.ikpa.penyerapan_anggaran', 'Penyerapan Anggaran'],
            ['rb.ikpa.belanja_kontraktual', 'Belanja Kontraktual'],
            ['rb.ikpa.penyelesaian_tagihan', 'Penyelesaian Tagihan (SPM)'],
            ['rb.ikpa.pengelolaan_up_tup', 'Pengelolaan UP dan TUP'],
            ['rb.ikpa.dispensasi_spm', 'Dispensasi SPM'],
            ['rb.ikpa.capaian_output', 'Capaian Output'],
        ], 1470, ['value_type' => 'number', 'unit' => 'nilai', 'aggregation_method' => 'average', 'decimal_places' => 2]);

        $this->addInputs($definitions, '4-1', 'ikss4.rb.sakip', [
            ['rb.sakip.perencanaan', 'Nilai perencanaan kinerja'],
            ['rb.sakip.pengukuran', 'Nilai pengukuran kinerja'],
            ['rb.sakip.pelaporan', 'Nilai pelaporan kinerja'],
            ['rb.sakip.evaluasi_internal', 'Nilai evaluasi internal'],
            ['rb.sakip.akuntabilitas', 'Tingkat akuntabilitas kinerja'],
        ], 1490, ['value_type' => 'number', 'unit' => 'nilai', 'aggregation_method' => 'average', 'decimal_places' => 2]);

        foreach ([
            ['rb.anggaran.dukungan_pagu', 'Pagu Program Dukungan Manajemen', 'dipa.id_dukman', 1510],
            ['rb.anggaran.penegakan_pagu', 'Pagu Program Penegakan dan Pelayanan Hukum', 'dipa.id_gakyankum', 1512],
            ['rb.anggaran.total_pagu', 'Total pagu anggaran', 'dipa.id_pagu', 1514],
        ] as [$code, $name, $reference, $sort]) {
            $this->addInput($definitions, '4-1', 'ikss4.rb.anggaran', $code, $name, [
                'parameter_role' => 'context',
                'source_type' => 'system',
                'source_reference' => $reference,
                'value_type' => 'currency',
                'unit' => 'Rp',
                'entry_levels' => [2, 3, 4],
                'aggregate_to_levels' => [],
                'decimal_places' => 2,
                'sort_order' => $sort,
            ]);
        }
        foreach ([
            ['rb.anggaran.dukungan_realisasi', 'Realisasi Program Dukungan Manajemen', 1511],
            ['rb.anggaran.penegakan_realisasi', 'Realisasi Program Penegakan dan Pelayanan Hukum', 1513],
            ['rb.anggaran.total_realisasi', 'Total realisasi anggaran', 1515],
        ] as [$code, $name, $sort]) {
            $this->addInput($definitions, '4-1', 'ikss4.rb.anggaran', $code, $name, [
                'parameter_role' => 'context',
                'value_type' => 'currency',
                'unit' => 'Rp',
                'decimal_places' => 2,
                'sort_order' => $sort,
            ]);
        }
        $this->addRatio($definitions, '4-1', 'ikss4.rb.anggaran', 'rb.anggaran.persentase', 'Persentase realisasi anggaran', 'Total realisasi anggaran', 'Total pagu anggaran', 1530, [
            'numerator_code' => 'rb.anggaran.total_realisasi',
            'denominator_code' => 'rb.anggaran.total_pagu',
            'create_inputs' => false,
        ]);
        $this->addInput($definitions, '4-1', 'ikss4.rb.anggaran', 'rb.prioritas_nasional', 'Rincian Output Prioritas Nasional yang dilaksanakan', [
            'parameter_role' => 'context',
            'input_mode' => 'table',
            'value_type' => 'currency',
            'unit' => 'Rp',
            'aggregation_method' => 'sum',
            'sort_order' => 1540,
        ]);
        $this->addNarratives($definitions, '4-1', 'ikss4.rb.context', 'rb', 1590);
    }

    private function addEthicsParameters(array &$definitions): void
    {
        $this->addRatio(
            $definitions,
            '4-2',
            'ikss4.etika',
            'etika.jaksa',
            'Tingkat penerapan etika profesi jaksa',
            'Jumlah Jaksa yang tidak melakukan pelanggaran etika profesi jaksa',
            'Jumlah total Jaksa',
            1600,
            ['is_result' => true, 'is_required' => true, 'numerator_code' => 'etika.jaksa.tidak_melanggar', 'denominator_code' => 'etika.jaksa.total']
        );
        $this->addInput($definitions, '4-2', 'ikss4.etika', 'etika.jaksa.melanggar', 'Jumlah Jaksa yang melakukan pelanggaran etika profesi jaksa', [
            'parameter_role' => 'context',
            'sort_order' => 1610,
        ]);
        $this->addTargetAssessment($definitions, '4-2', 'ikss4.etika', 'etika', 'etika.jaksa.tingkat_keberhasilan', 1620);
        $this->addNarratives($definitions, '4-2', 'ikss4.etika.context', 'etika', 1690);
    }

    private function addTargetAssessment(array &$definitions, string $ikssId, string $group, string $prefix, string $resultCode, int $sort): void
    {
        $targetCode = "{$prefix}.target_pk";
        $achievementCode = "{$prefix}.capaian_terhadap_target";
        $this->addInput($definitions, $ikssId, $group, $targetCode, 'Target IKSS berdasarkan Perjanjian Kinerja', [
            'parameter_role' => 'denominator',
            'source_type' => 'target',
            'source_reference' => $ikssId,
            'value_type' => 'number',
            'unit' => 'nilai',
            'aggregation_method' => 'latest',
            'entry_levels' => [2, 3, 4],
            'aggregate_to_levels' => [],
            'decimal_places' => 2,
            'sort_order' => $sort,
        ]);
        $this->addDerived($definitions, $ikssId, $group, $achievementCode, 'Capaian IKSS terhadap target PK', 'ratio', [
            ['code' => $resultCode, 'role' => 'numerator'],
            ['code' => $targetCode, 'role' => 'denominator'],
        ], [
            'parameter_role' => 'result',
            'value_type' => 'percentage',
            'unit' => '%',
            'decimal_places' => 2,
            'sort_order' => $sort + 1,
        ]);
    }

    private function addNarratives(array &$definitions, string $ikssId, string $group, string $prefix, int $sort): void
    {
        $this->addInput($definitions, $ikssId, $group, "{$prefix}.faktor", 'Faktor atau kondisi yang memengaruhi capaian kinerja', [
            'parameter_role' => 'narrative',
            'value_type' => 'text',
            'unit' => null,
            'aggregation_method' => 'latest',
            'entry_levels' => [2, 3, 4],
            'aggregate_to_levels' => [],
            'sort_order' => $sort,
        ]);
        $this->addInput($definitions, $ikssId, $group, "{$prefix}.optimalisasi", 'Upaya atau langkah strategis optimalisasi periode selanjutnya', [
            'parameter_role' => 'narrative',
            'value_type' => 'text',
            'unit' => null,
            'aggregation_method' => 'latest',
            'entry_levels' => [2, 3, 4],
            'aggregate_to_levels' => [],
            'sort_order' => $sort + 1,
        ]);
    }

    private function addRatio(
        array &$definitions,
        string $ikssId,
        string $group,
        string $prefix,
        string $resultName,
        string $numeratorName,
        string $denominatorName,
        int $sort,
        array $options = []
    ): void {
        $numeratorCode = $options['numerator_code'] ?? "{$prefix}.diselesaikan";
        $denominatorCode = $options['denominator_code'] ?? "{$prefix}.ditangani";
        $resultCode = "{$prefix}.tingkat_keberhasilan";
        $createInputs = $options['create_inputs'] ?? true;

        if ($createInputs) {
            $this->addInput($definitions, $ikssId, $group, $numeratorCode, $numeratorName, [
                'parameter_role' => 'numerator',
                'sort_order' => $sort,
            ]);
            $this->addInput($definitions, $ikssId, $group, $denominatorCode, $denominatorName, [
                'parameter_role' => 'denominator',
                'sort_order' => $sort + 1,
            ]);
        }

        $this->addDerived($definitions, $ikssId, $group, $resultCode, $resultName, 'ratio', [
            ['code' => $numeratorCode, 'role' => 'numerator'],
            ['code' => $denominatorCode, 'role' => 'denominator'],
        ], [
            'parameter_role' => 'result',
            'value_type' => 'percentage',
            'unit' => '%',
            'decimal_places' => 2,
            'is_result' => (bool) ($options['is_result'] ?? false),
            'is_required' => (bool) ($options['is_required'] ?? false),
            'sort_order' => $sort + 2,
        ]);
    }

    private function addInputs(array &$definitions, string $ikssId, string $group, array $inputs, int $sort, array $options = []): void
    {
        foreach ($inputs as $index => [$code, $name]) {
            $this->addInput($definitions, $ikssId, $group, $code, $name, $options + [
                'parameter_role' => 'context',
                'sort_order' => $sort + $index,
            ]);
        }
    }

    private function addInput(array &$definitions, string $ikssId, string $group, string $code, string $name, array $options = []): void
    {
        $definitions[] = array_merge([
            'ikss_id' => $ikssId,
            'group' => $group,
            'code' => $code,
            'name' => $name,
        ], $options);
    }

    private function addDerived(
        array &$definitions,
        string $ikssId,
        string $group,
        string $code,
        string $name,
        string $method,
        array $sources,
        array $options = []
    ): void {
        $definitions[] = array_merge([
            'ikss_id' => $ikssId,
            'group' => $group,
            'code' => $code,
            'name' => $name,
            'parameter_role' => 'result',
            'input_mode' => 'scalar',
            'source_type' => 'formula',
            'calculation_method' => $method,
            'aggregation_method' => 'average',
            'entry_levels' => [3, 4],
            'aggregate_to_levels' => [2],
        ], $options);

        $this->dependencyDefinitions[$code] = collect($sources)
            ->map(fn ($source) => is_array($source)
                ? ['source' => $source['code'], 'role' => $source['role'] ?? 'component', 'weight' => $source['weight'] ?? null]
                : ['source' => $source, 'role' => 'component', 'weight' => null])
            ->all();
    }

    private function parameterDefaults(): array
    {
        return [
            'description' => null,
            'parent_id' => null,
            'legacy_indicator_id' => null,
            'parameter_role' => 'component',
            'input_mode' => 'scalar',
            'source_type' => 'manual',
            'source_reference' => null,
            'value_type' => 'integer',
            'unit' => 'perkara',
            'period_type' => 'quarterly',
            'calculation_method' => 'input',
            'aggregation_method' => 'sum',
            'aggregation_scope' => 'children',
            'entry_levels' => [3, 4],
            'aggregate_to_levels' => [2],
            'formula_config' => ['minimum' => 0],
            'decimal_places' => 0,
            'sort_order' => 0,
            'is_result' => false,
            'is_required' => false,
            'include_in_report' => true,
            'is_active' => true,
            'valid_from_year' => 2026,
            'valid_until_year' => null,
        ];
    }

    private function seedDependencies(array $parameters): int
    {
        $count = 0;

        foreach ($this->dependencyDefinitions as $parameterCode => $dependencies) {
            if (! isset($parameters[$parameterCode])) {
                continue;
            }

            $parameter = $parameters[$parameterCode];
            $parameter->dependencies()->delete();

            foreach ($dependencies as $index => $dependency) {
                if (! isset($parameters[$dependency['source']])) {
                    continue;
                }

                $parameter->dependencies()->create([
                    'source_parameter_id' => $parameters[$dependency['source']]->id,
                    'role' => $dependency['role'],
                    'weight' => $dependency['weight'],
                    'sort_order' => $index,
                ]);
                $count++;
            }
        }

        return $count;
    }

    private function seedBindings(array $groups): int
    {
        $count = 0;

        foreach (array_values($groups) as $index => $group) {
            $settings = $group->settings ?? [];
            $levels = $settings['template_levels'] ?? [2, 3, 4];
            $bindingKey = 'group.'.$group->code;

            foreach ($levels as $level) {
                LkjipTemplateBinding::query()
                    ->where('template_level', $level)
                    ->where('binding_type', 'table')
                    ->where('source_type', 'group')
                    ->where('source_key', $group->code)
                    ->where('binding_key', '!=', $bindingKey)
                    ->update(['is_active' => false]);

                LkjipTemplateBinding::query()->updateOrCreate(
                    ['template_level' => $level, 'binding_key' => $bindingKey],
                    [
                        'template_level' => $level,
                        'binding_key' => $bindingKey,
                        'binding_type' => 'table',
                        'source_type' => 'group',
                        'source_key' => $group->code,
                        'marker' => '${table:'.$group->code.'}',
                        'formatter' => null,
                        'options' => ['anchors' => $settings['binding_anchors'] ?? []],
                        'sort_order' => $index,
                        'is_active' => true,
                    ]
                );
                $count++;
            }

            LkjipTemplateBinding::query()
                ->where('binding_key', $bindingKey)
                ->whereNotIn('template_level', $levels)
                ->update(['is_active' => false]);
        }

        return $count;
    }

    private function group(string $ikssId, string $code, string $name, int $sort, ?string $description = null, string $type = 'table', array $settings = []): array
    {
        return [
            'ikss_id' => $ikssId,
            'code' => $code,
            'name' => $name,
            'description' => $description,
            'section_code' => $ikssId,
            'group_type' => $type,
            'settings' => $settings + [
                'row_source' => 'fixed_parameters',
                'columns' => ['No', 'Parameter', 'Nilai'],
            ],
            'sort_order' => $sort,
        ];
    }

    private function stageLabel(string $stage): string
    {
        return match ($stage) {
            'penyelidikan' => 'penyelidikan',
            'penyidikan' => 'penyidikan',
            'prapenuntutan' => 'prapenuntutan',
            'penuntutan' => 'penuntutan',
            'eksekusi' => 'eksekusi',
        };
    }

    private function resolveMasterIkssIds(): array
    {
        $logicalIds = ['1-1', '2-1', '2-2', '2-3', '3-1', '4-1', '4-2'];
        $masterIds = Schema::hasTable('indikator_sastra')
            ? DB::table('indikator_sastra')->pluck('kode_indikator')->map(fn ($id) => (string) $id)
            : collect();

        return collect($logicalIds)->mapWithKeys(function ($logicalId) use ($masterIds) {
            $prefixed = 'IKSS'.$logicalId;

            return [$logicalId => $masterIds->contains($prefixed)
                ? $prefixed
                : ($masterIds->contains($logicalId) ? $logicalId : $prefixed)];
        })->all();
    }

    private function normalizeCatalogIkssIds(): void
    {
        foreach ($this->ikssIds as $logicalId => $masterId) {
            $aliases = collect([$logicalId, 'IKSS'.$logicalId])->unique()->reject(fn ($id) => $id === $masterId);

            foreach ($aliases as $alias) {
                DB::table('ikss_parameter_groups')->where('ikss_id', $alias)->update(['ikss_id' => $masterId]);
                DB::table('ikss_results')->where('ikss_id', $alias)->update(['ikss_id' => $masterId]);

                $legacyParameters = IkssParameter::query()->where('ikss_id', $alias)->get();
                foreach ($legacyParameters as $parameter) {
                    $duplicate = IkssParameter::query()
                        ->where('ikss_id', $masterId)
                        ->where('code', $parameter->code)
                        ->first();

                    if ($duplicate) {
                        $parameter->update(['is_active' => false]);
                    } else {
                        $parameter->update(['ikss_id' => $masterId]);
                    }
                }
            }
        }
    }

    private function masterIkssId(string $logicalOrMasterId): string
    {
        foreach ($this->ikssIds as $logicalId => $masterId) {
            if (in_array($logicalOrMasterId, [$logicalId, 'IKSS'.$logicalId, $masterId], true)) {
                return $masterId;
            }
        }

        return $logicalOrMasterId;
    }

    private function deactivateObsoleteDefinitions(): int
    {
        $obsoleteGroups = [
            'ikss2.pidum.prapenuntutan.summary',
            'ikss2.pidum.prapenuntutan.detail',
            'ikss2.pidum.prapenuntutan.formula',
        ];
        $obsoleteParameters = [
            'pidum.prapenuntutan.diselesaikan',
            'pidum.prapenuntutan.ditangani',
            'pidmil.koneksitas.penuntutan.diselesaikan',
            'pidmil.koneksitas.penuntutan.ditangani',
            'pidmil.koneksitas.penuntutan.tingkat_keberhasilan',
            'aset.denda.korupsi_dibayar',
        ];

        $groups = IkssParameterGroup::query()
            ->whereIn('code', $obsoleteGroups)
            ->update(['is_active' => false]);
        $parameters = IkssParameter::query()
            ->whereIn('code', $obsoleteParameters)
            ->update(['is_active' => false]);
        LkjipTemplateBinding::query()
            ->whereIn('source_key', $obsoleteGroups)
            ->update(['is_active' => false]);

        return $groups + $parameters;
    }

    private function syncStrategicMaster(): int
    {
        $strategicObjectives = [
            'SS1' => 'Terwujudnya kelembagaan hukum yang transparan dan adil',
            'SS2' => 'Terwujudnya efektivitas penegakan hukum dan keadilan melalui transformasi sistem penuntutan',
            'SS3' => 'Terwujudnya efektivitas pelaksanaan kewenangan Advocaat Generaal',
            'SS4' => 'Terwujudnya tata kelola organisasi yang optimal, transparan dan akuntabel',
        ];
        $indicators = [
            'IKSS1-1' => ['SS1', 'Indeks persepsi publik terhadap citra Kejaksaan RI'],
            'IKSS2-1' => ['SS2', 'Persentase peningkatan pengendalian perkara'],
            'IKSS2-2' => ['SS2', 'Tingkat keberhasilan kegiatan dan operasi intelijen penegakan hukum'],
            'IKSS2-3' => ['SS2', 'Tingkat keberhasilan pemulihan aset negara'],
            'IKSS3-1' => ['SS3', 'Tingkat efektivitas pelaksanaan kewenangan Advocaat Generaal'],
            'IKSS4-1' => ['SS4', 'Indeks Reformasi Birokrasi Kejaksaan RI'],
            'IKSS4-2' => ['SS4', 'Tingkat penerapan etika profesi jaksa'],
        ];
        $updated = 0;

        if (Schema::hasTable('sakip_sastra_new')) {
            foreach ($strategicObjectives as $code => $name) {
                $updated += DB::table('sakip_sastra_new')
                    ->where('id_sastra', $code)
                    ->update(['nama_sastra' => $name]);
            }
        }

        if (Schema::hasTable('indikator_sastra')) {
            foreach ($indicators as $code => [$ssCode, $name]) {
                $updated += DB::table('indikator_sastra')
                    ->where('kode_indikator', $code)
                    ->update(['kode_sastra' => $ssCode, 'nama_indikator' => $name]);
            }
        }

        return $updated;
    }
}
