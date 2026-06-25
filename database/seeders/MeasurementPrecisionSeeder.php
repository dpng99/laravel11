<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MeasurementPrecisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed measurement_thresholds dengan konfigurasi default
        $this->seedThresholds();

        // Seed indikator_metadata
        $this->seedIndicators();

        // Seed lke_dokumen_mapping untuk menggantikan hard-coded values
        $this->seedDocumenMapping();

        // Seed measurement_frameworks
        $this->seedFrameworks();
    }

    /**
     * Seed default measurement thresholds
     */
    private function seedThresholds()
    {
        $thresholds = [
            // Global defaults (level_code = null, satker_id = null)
            [
                'level_code' => null,
                'satker_id' => null,
                'indikator_id' => 'KPI_DEFAULT',
                'indikator_type' => 'kinerja',
                'excellent_min' => 100,
                'good_min' => 80,
                'fair_min' => 60,
                'poor_max' => 60,
                'weight' => 1.0,
                'status_excellent' => 'Excellent',
                'status_good' => 'Good',
                'status_fair' => 'Fair',
                'status_poor' => 'Poor',
                'effective_date' => Carbon::now()->subYear(),
                'deprecated_date' => null,
                'created_by' => 'seeder',
                'active' => true,
            ],

            // Level 2 (Kejati) - more strict
            [
                'level_code' => '2',
                'satker_id' => null,
                'indikator_id' => 'KPI_DEFAULT',
                'indikator_type' => 'kinerja',
                'excellent_min' => 100,
                'good_min' => 85,
                'fair_min' => 70,
                'poor_max' => 70,
                'weight' => 1.2,
                'status_excellent' => 'Excellent',
                'status_good' => 'Good',
                'status_fair' => 'Fair',
                'status_poor' => 'Poor',
                'effective_date' => Carbon::now()->subYear(),
                'deprecated_date' => null,
                'created_by' => 'seeder',
                'active' => true,
            ],

            // Level 3-4 (Kejari/CabJari) - less strict
            [
                'level_code' => '3',
                'satker_id' => null,
                'indikator_id' => 'KPI_DEFAULT',
                'indikator_type' => 'kinerja',
                'excellent_min' => 95,
                'good_min' => 75,
                'fair_min' => 50,
                'poor_max' => 50,
                'weight' => 1.0,
                'status_excellent' => 'Excellent',
                'status_good' => 'Good',
                'status_fair' => 'Fair',
                'status_poor' => 'Poor',
                'effective_date' => Carbon::now()->subYear(),
                'deprecated_date' => null,
                'created_by' => 'seeder',
                'active' => true,
            ],

            // Kepatuhan indicators - stricter
            [
                'level_code' => null,
                'satker_id' => null,
                'indikator_id' => 'KPI_COMPLIANCE',
                'indikator_type' => 'kepatuhan',
                'excellent_min' => 100,
                'good_min' => 90,
                'fair_min' => 75,
                'poor_max' => 75,
                'weight' => 1.5,
                'status_excellent' => 'Compliant',
                'status_good' => 'Mostly Compliant',
                'status_fair' => 'Partially Compliant',
                'status_poor' => 'Non-Compliant',
                'effective_date' => Carbon::now()->subYear(),
                'deprecated_date' => null,
                'created_by' => 'seeder',
                'active' => true,
            ],
        ];

        DB::table('measurement_thresholds')->insert($thresholds);
    }

    /**
     * Seed indikator metadata
     */
    private function seedIndicators()
    {
        $indicators = [
            [
                'id' => 'IND_001',
                'nama' => 'Realisasi RAPB',
                'deskripsi' => 'Persentase realisasi anggaran untuk operasional',
                'indikator_type' => 'kinerja',
                'measurement_unit' => '%',
                'calculation_method' => 'Realisasi / Target * 100',
                'is_critical' => true,
                'weight' => 1.5,
                'level' => null,
                'effective_date' => Carbon::now()->subYear(),
                'deprecated_date' => null,
                'created_by' => 'seeder',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 'IND_002',
                'nama' => 'Kasus Diselesaikan',
                'deskripsi' => 'Jumlah kasus yang diselesaikan dalam periode',
                'indikator_type' => 'kinerja',
                'measurement_unit' => 'kasus',
                'calculation_method' => 'Penghitungan langsung',
                'is_critical' => true,
                'weight' => 2.0,
                'level' => null,
                'effective_date' => Carbon::now()->subYear(),
                'deprecated_date' => null,
                'created_by' => 'seeder',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 'IND_003',
                'nama' => 'Tingkat Kepuasan Publik',
                'deskripsi' => 'Survei kepuasan layanan kepada publik',
                'indikator_type' => 'kinerja',
                'measurement_unit' => '%',
                'calculation_method' => 'Rata-rata skor survei',
                'is_critical' => false,
                'weight' => 1.0,
                'level' => null,
                'effective_date' => Carbon::now()->subYear(),
                'deprecated_date' => null,
                'created_by' => 'seeder',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 'IND_004',
                'nama' => 'Kepatuhan LKJIP',
                'deskripsi' => 'Kepatuhan laporan kinerja instansi pemerintah',
                'indikator_type' => 'kepatuhan',
                'measurement_unit' => '%',
                'calculation_method' => 'Penghitungan langsung',
                'is_critical' => true,
                'weight' => 2.5,
                'level' => null,
                'effective_date' => Carbon::now()->subYear(),
                'deprecated_date' => null,
                'created_by' => 'seeder',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 'IND_005',
                'nama' => 'Kepatuhan LKE',
                'deskripsi' => 'Kepatuhan laporan kerja evaluasi',
                'indikator_type' => 'kepatuhan',
                'measurement_unit' => '%',
                'calculation_method' => 'Penghitungan langsung',
                'is_critical' => true,
                'weight' => 2.5,
                'level' => null,
                'effective_date' => Carbon::now()->subYear(),
                'deprecated_date' => null,
                'created_by' => 'seeder',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        DB::table('indikator_metadata')->insert($indicators);
    }

    /**
     * Seed LKE dokumen mapping (dynamic replacement untuk hard-coded values)
     */
    private function seedDocumenMapping()
    {
        $mappings = [
            [
                'komponen_id' => 1,
                'criteria_code' => 'CRITERIA_001',
                'dokumen_type' => 'RKT',
                'dokumen_nama' => 'Rencana Kinerja Tahunan',
                'is_required' => true,
                'evidence_dimension' => 'availability',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'komponen_id' => 1,
                'criteria_code' => 'CRITERIA_001',
                'dokumen_type' => 'PENETAPAN_IKU',
                'dokumen_nama' => 'Penetapan Indikator Kinerja Utama',
                'is_required' => true,
                'evidence_dimension' => 'availability',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'komponen_id' => 2,
                'criteria_code' => 'CRITERIA_002',
                'dokumen_type' => 'LKJIP',
                'dokumen_nama' => 'Laporan Kinerja Instansi Pemerintah',
                'is_required' => true,
                'evidence_dimension' => 'availability',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'komponen_id' => 2,
                'criteria_code' => 'CRITERIA_002',
                'dokumen_type' => 'LKJIP_ANALISIS',
                'dokumen_nama' => 'Analisis LKJIP',
                'is_required' => false,
                'evidence_dimension' => 'completeness',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'komponen_id' => 3,
                'criteria_code' => 'CRITERIA_003',
                'dokumen_type' => 'LKE',
                'dokumen_nama' => 'Laporan Kerja Evaluasi',
                'is_required' => true,
                'evidence_dimension' => 'availability',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'komponen_id' => 3,
                'criteria_code' => 'CRITERIA_003',
                'dokumen_type' => 'BUKTI_DUKUNG',
                'dokumen_nama' => 'Bukti Dukung LKE',
                'is_required' => true,
                'evidence_dimension' => 'verification',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        DB::table('lke_dokumen_mapping')->insert($mappings);
    }

    /**
     * Seed measurement frameworks untuk versioning
     */
    private function seedFrameworks()
    {
        $framework = [
            'version' => '1.0.0',
            'nama' => 'Framework Pengukuran Presisi Standar',
            'deskripsi' => 'Framework standar untuk pengukuran kinerja LKJIP-LKE',
            'effective_date' => Carbon::now()->subYear(),
            'deprecated_date' => null,
            'config_json' => json_encode([
                'threshold_strategy' => 'multi_level',
                'scoring_model' => 'weighted_average',
                'audit_enabled' => true,
                'verification_required' => true,
                'default_weight' => 1.0,
                'compliance_threshold' => 80.0
            ]),
            'created_by' => 'seeder',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];

        DB::table('measurement_frameworks')->insert($framework);

        // Seed template versions
        $templates = [
            [
                'framework_version' => '1.0.0',
                'template_type' => 'lkjip',
                'template_name' => 'LKJIP Standard Template',
                'version' => '1.0',
                'effective_date' => Carbon::now()->subYear(),
                'deprecated_date' => null,
                'created_by' => 'seeder',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'framework_version' => '1.0.0',
                'template_type' => 'lke',
                'template_name' => 'LKE Standard Template',
                'version' => '1.0',
                'effective_date' => Carbon::now()->subYear(),
                'deprecated_date' => null,
                'created_by' => 'seeder',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        DB::table('measurement_template_versions')->insert($templates);
    }
}
