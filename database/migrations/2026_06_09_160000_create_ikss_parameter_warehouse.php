<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ikss_parameters', function (Blueprint $table) {
            $table->id();
            $table->string('ikss_id', 100);
            $table->foreignId('parent_id')->nullable()->constrained('ikss_parameters')->nullOnDelete();
            $table->string('code', 100);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('legacy_indicator_id', 100)->nullable();
            $table->string('value_type', 30)->default('number');
            $table->string('unit', 50)->nullable();
            $table->string('period_type', 20)->default('quarterly');
            $table->string('calculation_method', 30)->default('input');
            $table->string('aggregation_method', 30)->default('sum');
            $table->json('entry_levels')->nullable();
            $table->json('aggregate_to_levels')->nullable();
            $table->json('formula_config')->nullable();
            $table->unsignedTinyInteger('decimal_places')->default(2);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_result')->default(false);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('valid_from_year')->nullable();
            $table->unsignedSmallInteger('valid_until_year')->nullable();
            $table->timestamps();

            $table->unique(['ikss_id', 'code']);
            $table->index(['ikss_id', 'is_active', 'sort_order'], 'ikss_parameter_catalog_idx');
            $table->index(['legacy_indicator_id', 'is_active'], 'ikss_parameter_legacy_idx');
        });

        Schema::create('ikss_parameter_dependencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parameter_id')->constrained('ikss_parameters')->cascadeOnDelete();
            $table->foreignId('source_parameter_id')->constrained('ikss_parameters')->cascadeOnDelete();
            $table->string('role', 30)->default('component');
            $table->decimal('weight', 16, 6)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(
                ['parameter_id', 'source_parameter_id', 'role'],
                'ikss_parameter_dependency_unique'
            );
            $table->index(['source_parameter_id', 'parameter_id'], 'ikss_parameter_dependency_source_idx');
        });

        Schema::create('ikss_parameter_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parameter_id')->constrained('ikss_parameters')->cascadeOnDelete();
            $table->string('satker_id', 50);
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('quarter');
            $table->unsignedTinyInteger('month')->default(0);
            $table->decimal('value_decimal', 24, 6)->nullable();
            $table->longText('value_text')->nullable();
            $table->string('source_type', 30)->default('manual');
            $table->string('status', 30)->default('draft');
            $table->unsignedInteger('source_count')->default(1);
            $table->decimal('completeness', 5, 2)->default(100);
            $table->json('metadata')->nullable();
            $table->string('entered_by', 100)->nullable();
            $table->string('verified_by', 100)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->unsignedInteger('lock_version')->default(1);
            $table->timestamps();

            $table->unique(
                ['parameter_id', 'satker_id', 'year', 'quarter', 'month'],
                'ikss_parameter_value_period_unique'
            );
            $table->index(['satker_id', 'year', 'quarter'], 'ikss_parameter_value_satker_period_idx');
            $table->index(['parameter_id', 'year', 'quarter'], 'ikss_parameter_value_parameter_period_idx');
            $table->index(['source_type', 'year', 'quarter'], 'ikss_parameter_value_source_period_idx');
        });

        Schema::create('ikss_results', function (Blueprint $table) {
            $table->id();
            $table->string('ikss_id', 100);
            $table->string('satker_id', 50);
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('quarter');
            $table->decimal('target', 24, 6)->nullable();
            $table->decimal('capaian', 24, 6)->nullable();
            $table->decimal('achievement', 12, 4)->nullable();
            $table->unsignedInteger('source_count')->default(0);
            $table->decimal('completeness', 5, 2)->default(0);
            $table->string('status', 30)->default('calculated');
            $table->json('details')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->unique(['ikss_id', 'satker_id', 'year', 'quarter'], 'ikss_result_period_unique');
            $table->index(['satker_id', 'year', 'quarter'], 'ikss_result_satker_period_idx');
            $table->index(['ikss_id', 'year', 'quarter'], 'ikss_result_ikss_period_idx');
            $table->index(['year', 'quarter', 'achievement'], 'ikss_result_ranking_idx');
        });

        Schema::create('ikss_calculation_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('quarter');
            $table->string('satker_id', 50)->nullable();
            $table->string('scope', 30)->default('satker');
            $table->string('trigger', 30)->default('manual');
            $table->string('status', 30)->default('running');
            $table->unsignedInteger('parameters_count')->default(0);
            $table->unsignedInteger('values_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('error_message')->nullable();
            $table->json('stats')->nullable();
            $table->timestamps();

            $table->index(['year', 'quarter', 'status'], 'ikss_calculation_run_period_idx');
            $table->index(['satker_id', 'year', 'quarter'], 'ikss_calculation_run_satker_idx');
        });

        $this->addLegacyIndexes();
    }

    public function down(): void
    {
        $this->dropLegacyIndexes();

        Schema::dropIfExists('ikss_calculation_runs');
        Schema::dropIfExists('ikss_results');
        Schema::dropIfExists('ikss_parameter_values');
        Schema::dropIfExists('ikss_parameter_dependencies');
        Schema::dropIfExists('ikss_parameters');
    }

    private function addLegacyIndexes(): void
    {
        if (Schema::hasTable('pengukuran') && ! $this->hasIndex('pengukuran', 'pengukuran_satker_tahun_indikator_bulan_idx')) {
            Schema::table('pengukuran', function (Blueprint $table) {
                $table->index(
                    ['id_satker', 'tahun', 'indikator_id', 'bulan'],
                    'pengukuran_satker_tahun_indikator_bulan_idx'
                );
            });
        }

        if (Schema::hasTable('target') && ! $this->hasIndex('target', 'target_satker_tahun_indikator_idx')) {
            Schema::table('target', function (Blueprint $table) {
                $table->index(['id_satker', 'tahun', 'indikator_id'], 'target_satker_tahun_indikator_idx');
            });
        }
    }

    private function dropLegacyIndexes(): void
    {
        if (Schema::hasTable('pengukuran') && $this->hasIndex('pengukuran', 'pengukuran_satker_tahun_indikator_bulan_idx')) {
            Schema::table('pengukuran', function (Blueprint $table) {
                $table->dropIndex('pengukuran_satker_tahun_indikator_bulan_idx');
            });
        }

        if (Schema::hasTable('target') && $this->hasIndex('target', 'target_satker_tahun_indikator_idx')) {
            Schema::table('target', function (Blueprint $table) {
                $table->dropIndex('target_satker_tahun_indikator_idx');
            });
        }
    }

    private function hasIndex(string $table, string $name): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn (array $index) => ($index['name'] ?? null) === $name);
    }
};
