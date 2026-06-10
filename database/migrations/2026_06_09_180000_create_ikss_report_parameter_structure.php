<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ikss_parameter_groups', function (Blueprint $table) {
            $table->id();
            $table->string('ikss_id', 100)->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('ikss_parameter_groups')->nullOnDelete();
            $table->string('code', 150)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('section_code', 100)->nullable();
            $table->string('group_type', 30)->default('table');
            $table->json('settings')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['ikss_id', 'is_active', 'sort_order'], 'ikss_parameter_group_catalog_idx');
            $table->index(['section_code', 'is_active'], 'ikss_parameter_group_section_idx');
        });

        Schema::table('ikss_parameters', function (Blueprint $table) {
            $table->foreignId('group_id')
                ->nullable()
                ->after('parent_id')
                ->constrained('ikss_parameter_groups')
                ->nullOnDelete();
            $table->string('parameter_role', 30)->default('input')->after('description');
            $table->string('input_mode', 30)->default('scalar')->after('parameter_role');
            $table->string('source_type', 30)->default('manual')->after('input_mode');
            $table->string('source_reference', 150)->nullable()->after('source_type');
            $table->string('aggregation_scope', 30)->default('children')->after('aggregation_method');
            $table->boolean('include_in_report')->default(true)->after('is_required');

            $table->index(['group_id', 'is_active', 'sort_order'], 'ikss_parameter_group_order_idx');
            $table->index(['source_type', 'source_reference'], 'ikss_parameter_source_idx');
        });

        Schema::create('ikss_parameter_value_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parameter_value_id')->constrained('ikss_parameter_values')->cascadeOnDelete();
            $table->string('item_key', 150);
            $table->string('item_label');
            $table->decimal('value_decimal', 24, 6)->nullable();
            $table->longText('value_text')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['parameter_value_id', 'item_key'], 'ikss_parameter_value_item_unique');
            $table->index(['item_key', 'parameter_value_id'], 'ikss_parameter_value_item_key_idx');
        });

        Schema::create('lkjip_template_bindings', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('template_level');
            $table->string('binding_key', 180);
            $table->string('binding_type', 30)->default('scalar');
            $table->string('source_type', 30);
            $table->string('source_key', 180);
            $table->string('marker', 220);
            $table->string('formatter', 50)->nullable();
            $table->json('options')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['template_level', 'binding_key'], 'lkjip_template_binding_unique');
            $table->index(['template_level', 'is_active', 'sort_order'], 'lkjip_template_binding_catalog_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lkjip_template_bindings');
        Schema::dropIfExists('ikss_parameter_value_items');

        Schema::table('ikss_parameters', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropIndex('ikss_parameter_group_order_idx');
            $table->dropIndex('ikss_parameter_source_idx');
            $table->dropColumn([
                'group_id',
                'parameter_role',
                'input_mode',
                'source_type',
                'source_reference',
                'aggregation_scope',
                'include_in_report',
            ]);
        });

        Schema::dropIfExists('ikss_parameter_groups');
    }
};
