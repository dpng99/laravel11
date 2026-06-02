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
        $this->addPohonKinerjaColumns('sakip_sastra_new', 'deskripsi');
        $this->addPohonKinerjaColumns('sakip_saspro_new', 'deskripsi');
        $this->addPohonKinerjaColumns('indikator_sastra', 'deskripsi_indikator_sastra');
        $this->addPohonKinerjaColumns('indikator_saspro', 'deskripsi_indikator_saspro');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['indikator_saspro', 'indikator_sastra', 'sakip_saspro_new', 'sakip_sastra_new'] as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            $columns = collect(['link', 'lingkup', 'tahun', 'hide', 'urutan'])
                ->when($tableName === 'sakip_sastra_new', fn ($columns) => $columns->push('target'))
                ->filter(fn ($column) => Schema::hasColumn($tableName, $column))
                ->all();

            if (!empty($columns)) {
                Schema::table($tableName, function (Blueprint $table) use ($columns) {
                    $table->dropColumn($columns);
                });
            }
        }
    }

    private function addPohonKinerjaColumns(string $tableName, string $afterColumn): void
    {
        if (!Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName, $afterColumn) {
            $after = $afterColumn;

            if (!Schema::hasColumn($tableName, 'link')) {
                $table->string('link', 255)->nullable()->after($after);
                $after = 'link';
            }

            if (!Schema::hasColumn($tableName, 'lingkup')) {
                $table->string('lingkup', 20)->nullable()->default('0')->after($after);
                $after = 'lingkup';
            }

            if ($tableName === 'sakip_sastra_new' && !Schema::hasColumn($tableName, 'target')) {
                $table->string('target', 255)->nullable()->after($after);
                $after = 'target';
            }

            if (!Schema::hasColumn($tableName, 'tahun')) {
                $table->string('tahun', 10)->nullable()->after($after);
                $after = 'tahun';
            }

            if (!Schema::hasColumn($tableName, 'hide')) {
                $table->string('hide', 1)->default('0')->after($after);
                $after = 'hide';
            }

            if (!Schema::hasColumn($tableName, 'urutan')) {
                $table->unsignedInteger('urutan')->nullable()->after($after);
            }
        });
    }
};
