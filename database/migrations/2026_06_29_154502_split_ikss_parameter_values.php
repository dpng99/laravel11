<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ikss_parameter_inputs', function (Blueprint $table) {
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
                'ikss_parameter_input_period_unique'
            );
            $table->index(['satker_id', 'year', 'quarter'], 'ikss_parameter_input_satker_idx');
            $table->index(['parameter_id', 'year', 'quarter'], 'ikss_parameter_input_parameter_idx');
        });

        Schema::create('ikss_parameter_input_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parameter_input_id')->constrained('ikss_parameter_inputs')->cascadeOnDelete();
            $table->string('item_key', 150);
            $table->string('item_label');
            $table->decimal('value_decimal', 24, 6)->nullable();
            $table->longText('value_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['parameter_input_id', 'item_key'], 'ikss_parameter_input_item_unique');
            $table->index(['item_key', 'parameter_input_id'], 'ikss_parameter_input_item_key_idx');
        });

        if (Schema::hasTable('ikss_parameter_values')) {
            DB::statement('
                INSERT INTO ikss_parameter_inputs 
                (id, parameter_id, satker_id, year, quarter, month, value_decimal, value_text, source_type, status, source_count, completeness, metadata, entered_by, verified_by, verified_at, calculated_at, lock_version, created_at, updated_at)
                SELECT id, parameter_id, satker_id, year, quarter, month, value_decimal, value_text, source_type, status, source_count, completeness, metadata, entered_by, verified_by, verified_at, calculated_at, lock_version, created_at, updated_at
                FROM ikss_parameter_values
                WHERE calculated_at IS NULL
            ');

            if (Schema::hasTable('ikss_parameter_value_items')) {
                DB::statement('
                    INSERT INTO ikss_parameter_input_items
                    (id, parameter_input_id, item_key, item_label, value_decimal, value_text, sort_order, metadata, created_at, updated_at)
                    SELECT id, parameter_value_id, item_key, item_label, value_decimal, value_text, sort_order, metadata, created_at, updated_at
                    FROM ikss_parameter_value_items
                    WHERE parameter_value_id IN (SELECT id FROM ikss_parameter_inputs)
                ');
                
                DB::statement('
                    DELETE FROM ikss_parameter_value_items
                    WHERE parameter_value_id IN (SELECT id FROM ikss_parameter_inputs)
                ');
            }

            DB::statement('
                DELETE FROM ikss_parameter_values
                WHERE calculated_at IS NULL
            ');
        }

        if (Schema::hasTable('ikss_parameter_value_items')) {
            Schema::rename('ikss_parameter_value_items', 'ikss_parameter_result_items');
            
            Schema::table('ikss_parameter_result_items', function (Blueprint $table) {
                $table->renameColumn('parameter_value_id', 'parameter_result_id');
            });
        }

        if (Schema::hasTable('ikss_parameter_values')) {
            Schema::rename('ikss_parameter_values', 'ikss_parameter_results');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ikss_parameter_results')) {
            Schema::rename('ikss_parameter_results', 'ikss_parameter_values');
        }
        if (Schema::hasTable('ikss_parameter_result_items')) {
             Schema::table('ikss_parameter_result_items', function (Blueprint $table) {
                $table->renameColumn('parameter_result_id', 'parameter_value_id');
            });
            Schema::rename('ikss_parameter_result_items', 'ikss_parameter_value_items');
        }

        if (Schema::hasTable('ikss_parameter_inputs') && Schema::hasTable('ikss_parameter_values')) {
            DB::statement('
                INSERT INTO ikss_parameter_values 
                (parameter_id, satker_id, year, quarter, month, value_decimal, value_text, source_type, status, source_count, completeness, metadata, entered_by, verified_by, verified_at, calculated_at, lock_version, created_at, updated_at)
                SELECT parameter_id, satker_id, year, quarter, month, value_decimal, value_text, source_type, status, source_count, completeness, metadata, entered_by, verified_by, verified_at, calculated_at, lock_version, created_at, updated_at
                FROM ikss_parameter_inputs
            ');
        }
        
        Schema::dropIfExists('ikss_parameter_input_items');
        Schema::dropIfExists('ikss_parameter_inputs');
    }
};
