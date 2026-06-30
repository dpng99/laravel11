<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Tabel Konfigurasi Threshold Pengukuran
        Schema::create('measurement_thresholds', function (Blueprint $table) {
            $table->id();
            $table->integer('level_code')->nullable()->index();
            $table->string('satker_id', 50)->nullable()->index();
            $table->string('indikator_id', 100)->index();
            $table->enum('indikator_type', ['kinerja', 'kepatuhan', 'kelembagaan'])->default('kinerja');
            $table->string('measurement_unit', 50)->nullable();
            
            // Threshold values
            $table->decimal('excellent_min', 10, 2)->comment('Minimum untuk excellent status');
            $table->decimal('good_min', 10, 2)->comment('Minimum untuk good status');
            $table->decimal('fair_min', 10, 2)->comment('Minimum untuk fair status');
            $table->decimal('poor_max', 10, 2)->comment('Maximum untuk poor status');
            
            // Status labels
            $table->string('status_excellent', 100)->default('Sempurna');
            $table->string('status_good', 100)->default('Baik');
            $table->string('status_fair', 100)->default('Cukup');
            $table->string('status_poor', 100)->default('Perlu Perhatian');
            
            // Weighting
            $table->decimal('weight', 5, 2)->default(1.0);
            
            // Lifecycle
            $table->date('effective_date');
            $table->date('deprecated_date')->nullable();
            $table->boolean('active')->default(true);
            
            // Audit
            $table->string('created_by', 100);
            $table->timestamps();
            
            // Unique constraint
            $table->unique(['level_code', 'satker_id', 'indikator_id', 'effective_date'], 'mt_lc_sid_iid_ed_unique');
        });

        // 2. Tabel Metadata Indikator
        Schema::create('indikator_metadata', function (Blueprint $table) {
            $table->string('id', 50)->primary();
            $table->string('nama', 255);
            $table->text('deskripsi')->nullable();
            $table->enum('indikator_type', ['kinerja', 'kepatuhan', 'kelembagaan'])->default('kinerja');
            $table->string('measurement_unit', 50)->nullable()->comment('%, orang, rupiah, kasus');
            $table->text('calculation_method')->nullable();
            $table->boolean('is_critical')->default(false);
            $table->decimal('weight', 5, 2)->default(1.0);
            $table->integer('level')->nullable();
            
            $table->date('effective_date');
            $table->date('deprecated_date')->nullable();
            $table->text('notes')->nullable();
            
            $table->string('created_by', 100);
            $table->timestamps();
            
            $table->index('indikator_type');
            $table->index('is_critical');
            $table->index('level');
        });

        // 3. Tabel Audit Log Pengukuran
        Schema::create('measurement_audit_log', function (Blueprint $table) {
            $table->id();
            $table->string('id_satker', 50);
            $table->integer('id_periode');
            $table->string('id_indikator', 50);
            
            $table->decimal('nilai_lama', 10, 2)->nullable();
            $table->decimal('nilai_baru', 10, 2);
            
            $table->string('status_lama', 100)->nullable();
            $table->string('status_baru', 100)->nullable();
            
            $table->text('perubahan_reason')->nullable();
            $table->string('changed_by', 100);
            $table->timestamp('changed_at');
            
            $table->index(['id_satker', 'id_periode']);
            $table->index(['id_satker', 'id_indikator']);
            $table->index('changed_at');
        });

        // 4. Tabel Measurement Framework Version
        Schema::create('measurement_frameworks', function (Blueprint $table) {
            $table->id();
            $table->string('version', 20)->unique();
            $table->integer('level_code')->nullable();
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(false);
            $table->date('effective_date');
            $table->date('deprecated_date')->nullable();
            
            $table->longText('config_json')->nullable()->comment('Seluruh konfigurasi dalam JSON');
            
            $table->string('created_by', 100);
            $table->timestamps();
            
            $table->index('is_active');
            $table->index('effective_date');
        });

        // 5. Tabel Template Version
        Schema::create('measurement_template_versions', function (Blueprint $table) {
            $table->id();
            $table->string('framework_version', 20);
            $table->string('template_type', 50)->comment('lkjip-kejati, lkjip-kejari, etc');
            $table->binary('template_file')->nullable();
            $table->string('template_hash', 64)->nullable();
            $table->text('change_notes')->nullable();
            
            $table->string('created_by', 100);
            $table->timestamps();
            
            $table->foreign('framework_version')->references('version')->on('measurement_frameworks');
            $table->index(['framework_version', 'template_type'], 'mtv_fv_tt_index');
        });

        // 6. Tabel LKE Dokumen Mapping (Dynamic)
        Schema::create('lke_dokumen_mapping', function (Blueprint $table) {
            $table->id();
            $table->integer('bukti_kode')->unique();
            $table->string('dokumen_nama', 255);
            $table->text('deskripsi')->nullable();
            $table->string('model_class', 255)->nullable();
            $table->string('source_table', 100)->nullable();
            $table->integer('tahun_referensi_offset')->default(0)->comment('0=tahun berjalan, -1=tahun lalu');
            $table->text('filter_criteria')->nullable()->comment('JSON criteria untuk query');
            
            $table->boolean('active')->default(true);
            $table->integer('tahun_mulai')->nullable();
            $table->integer('tahun_selesai')->nullable();
            
            $table->string('created_by', 100);
            $table->timestamps();
            
            $table->index('active');
            $table->index(['tahun_mulai', 'tahun_selesai']);
        });

        // 7. Tabel LKE Verification Log
        Schema::create('lke_verification_log', function (Blueprint $table) {
            $table->id();
            $table->string('id_satker', 50);
            $table->integer('bukti_id');
            $table->string('criteria_code', 50);
            
            $table->boolean('is_verified')->default(false);
            $table->text('verification_notes')->nullable();
            $table->integer('verification_score')->nullable();
            
            $table->string('verified_by', 100);
            $table->timestamp('verified_at');
            
            $table->index(['id_satker', 'criteria_code']);
            $table->index('verified_at');
        });

        // 8. Extend bukti_dukung table dengan kolom quality
        if (Schema::hasTable('bukti_dukung')) {
            Schema::table('bukti_dukung', function (Blueprint $table) {
                if (!Schema::hasColumn('bukti_dukung', 'is_verified')) {
                    $table->boolean('is_verified')->default(false)->nullable();
                }
                if (!Schema::hasColumn('bukti_dukung', 'is_complete')) {
                    $table->boolean('is_complete')->default(false)->nullable();
                }
                if (!Schema::hasColumn('bukti_dukung', 'quality_score')) {
                    $table->integer('quality_score')->nullable();
                }
                if (!Schema::hasColumn('bukti_dukung', 'last_verified_at')) {
                    $table->timestamp('last_verified_at')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('measurement_template_versions');
        Schema::dropIfExists('measurement_frameworks');
        Schema::dropIfExists('lke_verification_log');
        Schema::dropIfExists('lke_dokumen_mapping');
        Schema::dropIfExists('measurement_audit_log');
        Schema::dropIfExists('indikator_metadata');
        Schema::dropIfExists('measurement_thresholds');
    }
};
