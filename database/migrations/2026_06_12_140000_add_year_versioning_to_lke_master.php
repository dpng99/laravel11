<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'lke_komponen',
        'lke_subkomponen',
        'lke_kriteria',
        'lke_buktidukung',
        'lke_gabungan',
        'lke_parameter',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'tahun')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->unsignedSmallInteger('tahun')->default(2025)->index("{$table}_tahun_idx");
            });

            DB::table($table)->whereNull('tahun')->update(['tahun' => 2025]);
        }

        $this->addCompositeIndex('lke_subkomponen', ['tahun', 'kode'], 'lke_subkomponen_tahun_kode_idx');
        $this->addCompositeIndex('lke_kriteria', ['tahun', 'kode'], 'lke_kriteria_tahun_kode_idx');
        $this->addCompositeIndex('lke_gabungan', ['tahun', 'kriteria_id'], 'lke_gabungan_tahun_kriteria_idx');
        $this->addCompositeIndex('lke_parameter', ['tahun', 'kriteria_id'], 'lke_parameter_tahun_kriteria_idx');
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tahun')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn('tahun');
            });
        }
    }

    private function addCompositeIndex(string $table, array $columns, string $name): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $exists = collect(Schema::getIndexes($table))->contains(fn ($index) => ($index['name'] ?? null) === $name);
        if (! $exists) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $name));
        }
    }
};
