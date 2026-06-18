<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $canonical = DB::table('lke_buktidukung')
            ->where('ada_di_sistem', true)
            ->whereNotNull('tabel_sumber')
            ->orderBy('tahun')
            ->orderBy('id')
            ->get()
            ->unique(fn ($row) => mb_strtolower(trim((string) $row->dokumen)));

        foreach (DB::table('lke_buktidukung')->where('ada_di_sistem', false)->get() as $row) {
            $source = $canonical->first(
                fn ($item) => mb_strtolower(trim((string) $item->dokumen)) === mb_strtolower(trim((string) $row->dokumen))
            );
            if (! $source) {
                continue;
            }

            DB::table('lke_buktidukung')->where('id', $row->id)->update([
                'format_nama_file' => $source->format_nama_file,
                'ada_di_sistem' => true,
                'tabel_sumber' => $source->tabel_sumber,
            ]);
        }
    }

    public function down(): void
    {
        // Metadata can be edited by administrators, so rolling it back would destroy valid changes.
    }
};
