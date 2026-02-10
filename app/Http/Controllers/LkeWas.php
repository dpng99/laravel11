<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use App\Models\LheAkip;
use App\Models\TlLheAkip;
use App\Models\MonevRenaksi;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;
class LkeWas extends Controller
{
    public function index()
    {  if(!session()->has('tahun_terpilih')) {
            return redirect()->route('pilih.tahun');
        }
        $tahun = session('tahun_terpilih');
        // $id_satker = preg_replace('/^was/i', '', session('id_satker'));
        $id_satker = session('id_satker');
        
        $id_kejati = DB::table('sinori_login')->where('id_satker', $id_satker)->first();
        $level = session('id_sakip_level');

        if (!$id_kejati) {
            return back()->with('error', 'Data Kejati tidak ditemukan');
        }

        $list_kejari = DB::table('sinori_login')
            ->where('id_kejati', $id_kejati->id_kejati)
            ->where('id_hidesatker', 0)
            ->orderBy('satkernama')
            ->get();
            
        if (in_array($id_satker, [999999, 'admin', 'Pengawasan', 'Panev', 'menpanrb'])) {
            // Ambil semua satker, urutkan berdasarkan id_kejati
            $list_kejari = DB::table('sinori_login')
                ->whereNotIn('id_satker', [888881, 888882, 'admin', 999999, 'Pengawasan', 'Panev', 'menpanrb']) // dikecualikan
                ->where('id_satker', 'not like', 'was%')
                ->where('id_satker', 'not like', '00budi')
                ->where('id_kejati', 'not like', '87') // dikecualikan
                ->orderBy('id_kejati', 'asc')
                ->orderBy('id_kejari', 'asc')
                ->get();
        } else {
            // Ambil data satkernama dan id_satker sesuai id_kejati
            $list_kejari = DB::table('sinori_login')
                ->where('id_kejati', $id_kejati->id_kejati)
                ->where('id_satker', 'not like', 'was%') // dikecualikan
                // ->orderBy('id_satker', 'asc')
                ->get();
        }
        return Inertia::render('Lkewas', compact('tahun', 'list_kejari'));
    }

    public function listBuktiDukung(Request $request)
    {
        
        // Cek apakah tahun sudah dipilih
        if (!session()->has('tahun_terpilih')) {
            return redirect()->route('pilih.tahun');
        }
        // Ambil tahun yang dipilih dari session
        $tahun = session('tahun_terpilih');
        $idSatker = $request->id_satker;

        $lheAkipFiles = LheAkip::orderBy('id_tglupload', 'desc')->get();
        $lheAkipFiles = LheAkip::where('id_satker', $idSatker)
            ->where('id_periode', $tahun)
            ->orderBy('id_tglupload', 'desc')
            ->get();
        $tlLheAkipFiles = TLLheAkip::where('id_satker', $idSatker)
            ->where('id_periode', $tahun)
            ->orderBy('id_tglupload', 'desc')
            ->get();
        $monevRenaksiFiles = MonevRenaksi::where('id_periode', $tahun)
            ->where('id_satker', $idSatker)
            ->orderBy('id_perubahan', 'desc')
            ->get();

        // === Ambil dokumen bukti dukung (group by kriteria) ===
        $buktiDukung = DB::table('bukti_dukung')
            ->where('id_satker', $idSatker)
            ->get()
            ->groupBy('id_kriteria');

        // === Ambil semua kriteria yang punya format_nama_file ===
        $kriteria = DB::table('kriteria')->get();

        foreach ($kriteria as $row) {
    if (!$row->format_nama_file) continue;

    $formats = array_map('trim', explode(';', $row->format_nama_file));

    foreach ($formats as $format) {
        $regex = '/^' . str_replace('_', '.*', preg_quote($format, '/')) . '/i';

        $dokumenList = DB::table('bukti_dukung')
            ->where('id_satker', $idSatker)
            ->where('id_kriteria', $row->id)
            ->get()
            ->filter(fn($d) => preg_match($regex, $d->link_bukti_dukung));

        if ($dokumenList->isNotEmpty()) {
            if (!isset($buktiDukung[$row->id])) {
                $buktiDukung[$row->id] = collect();
            }
            foreach ($dokumenList as $dok) {
                $buktiDukung[$row->id]->push($dok);
            }
        }
    }
}



        $komponen = DB::table('komponen')
            ->leftJoin('sub_komponen', 'komponen.id', '=', 'sub_komponen.id_komponen')
            ->leftJoin('kriteria', 'sub_komponen.id_subkomponen', '=', 'kriteria.id_sub_komponen')
            ->select(
                'komponen.id as id_komponen',
                'komponen.nama_komponen',
                'sub_komponen.id_subkomponen',
                'sub_komponen.nama_subkomponen',
                'kriteria.id as id_kriteria',
                'kriteria.nama_kriteria',
                'kriteria.kode',
                'kriteria.bukti_pengisian',
                'kriteria.format_nama_file' // ini penting biar dipakai untuk nama file
            )
            ->orderBy('komponen.id')
            ->orderBy('sub_komponen.id_subkomponen')
            ->orderBy('kriteria.id')
            ->get();


        // Tambahkan info jumlah kebutuhan vs realisasi
        $komponen->transform(function ($item) use ($buktiDukung) {
            // pecah kebutuhan dari kriteria
            $kebutuhan = $item->bukti_pengisian
                ? array_map('trim', explode(';', $item->bukti_pengisian))
                : [];

            // dokumen realisasi
            $realisasi = isset($buktiDukung[$item->id_kriteria])
                ? $buktiDukung[$item->id_kriteria]
                : collect();

            $item->jumlah_kebutuhan = count($kebutuhan);
            $item->jumlah_realisasi = $realisasi->count();
            $item->persen_terpenuhi = $item->jumlah_kebutuhan > 0
                ? round(($item->jumlah_realisasi / $item->jumlah_kebutuhan) * 100, 2)
                : 0;

            return $item;
        });
                $nama_satker = DB::table('sinori_login')->where('id_satker', $idSatker)->value('satkernama');
        $satkernama = str_replace('_', ' ', $nama_satker);
        return Inertia::render('EvalWas/EvalWas', ['tahun' => $tahun, 'lheAkipFiles' => $lheAkipFiles, 'monevRenaksiFiles' => $monevRenaksiFiles, 'tlLheAkipFiles' => $tlLheAkipFiles, 'komponen' => $komponen, 'idSatker' => $idSatker, 'buktiDukung' => $buktiDukung, 'satkernama' => $satkernama]);
    }
}
