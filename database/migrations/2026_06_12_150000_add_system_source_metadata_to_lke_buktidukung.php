<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lke_buktidukung', function (Blueprint $table) {
            $table->string('format_nama_file')->nullable()->after('dokumen');
            $table->boolean('ada_di_sistem')->default(false)->after('keterangan');
            $table->string('tabel_sumber', 100)->nullable()->index()->after('ada_di_sistem');
        });

        $tables = [
            1 => 'sinori_sakip_renstra', 2 => 'sinori_sakip_renja', 3 => 'sinori_sakip_renaksi',
            4 => 'sinori_sakip_rkakl', 5 => 'sinori_sakip_dipa', 6 => 'pk', 7 => 'pk',
            8 => 'sinori_sakip_iku', 9 => 'sinori_sakip_iku', 10 => 'sinori_sakip_lakip',
            11 => 'sinori_sakip_lakip', 12 => 'lhe', 13 => 'sinori_sakip_rastaff',
            14 => 'sinori_sakip_rastaff', 15 => 'lhe', 16 => 'lhe', 17 => 'tl_lhe_akip',
            18 => 'sinori_sakip_renaksieval', 19 => 'sinori_sakip_renaksieval',
            20 => 'pokin_ranwal', 21 => 'sinori_sakip_renstra', 22 => 'sinori_sakip_lakip',
            23 => 'sample_skp', 24 => 'sk_pm', 25 => 'sk_pk', 26 => 'absen_pm',
            27 => 'notulensi_pm', 28 => 'nodis_p_akip', 29 => 'nodis_eval_akip',
            30 => 'memo_datakinerja', 31 => 'nodis_datakinerja', 32 => 'reward_punish',
            33 => 'sampel_rekom', 34 => 'ss_perencanaan', 35 => 'ss_laporweb',
            36 => 'ss_laporapp', 37 => 'tar_lkjip', 38 => 'tar_lkjip', 39 => 'memo_lkjip',
            40 => 'memo_lkjip', 41 => 'tar_pm', 42 => 'ba_praeval', 43 => 'ba_pleno', 44 => 'lhe',
        ];
        $prefixes = [
            1 => 'renstra', 2 => 'renja', 3 => 'renaksi', 4 => 'rkakl', 5 => 'dipa', 6 => 'pk',
            7 => 'pk', 8 => 'IKU', 9 => 'IKU', 10 => 'lkjip', 11 => 'lkjip', 12 => 'lkjip',
            13 => 'rastaff', 14 => 'rastaff', 15 => 'lhe', 16 => 'lhe', 17 => 'tl_lhe_akip',
            18 => 'monev', 19 => 'monev', 20 => 'pokin_ranwal', 21 => 'renstra_lembaga',
            22 => 'lkjip', 23 => 'sampel_skp', 24 => 'tim_pm', 25 => 'tim_evaluator',
            26 => 'absen_pm', 27 => 'notulensi_bimtek', 28 => 'nodis_penyelenggaraan_akip',
            29 => 'nodis_evaluasi_akip', 30 => 'memo_data_kinerja', 31 => 'nodis_data_kinerja',
            32 => 'reward_punishment', 33 => 'sampel_rekom', 34 => 'ss_perencanaan',
            35 => 'ss_laporan_web', 36 => 'ss_laporan_app', 37 => 'tar_lkjip', 38 => 'tar_lkjip',
            39 => 'memo_lkjip', 40 => 'memo_lkjip', 41 => 'tar_pm', 42 => 'ba_praevaluasi',
            43 => 'ba_pleno', 44 => 'lhe',
        ];

        foreach ($tables as $id => $table) {
            DB::table('lke_buktidukung')->where('id', $id)->update([
                'format_nama_file' => ($prefixes[$id] ?? 'dokumen_sakip') . '_{tahun}_{iterasi}{triwulan}.{ext}',
                'ada_di_sistem' => true,
                'tabel_sumber' => $table,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('lke_buktidukung', function (Blueprint $table) {
            $table->dropIndex(['tabel_sumber']);
            $table->dropColumn(['format_nama_file', 'ada_di_sistem', 'tabel_sumber']);
        });
    }
};
