// <?php

// namespace App\Http\Controllers;

// use App\Http\Controllers\Controller;
// use App\Models\absen_pm;
// use App\Models\ba_pleno;
// use App\Models\ba_praeval;
// use Illuminate\Http\Request;
// use App\Models\DataLke1;
// use App\Models\Dipa;
// use App\Models\Iku;
// use App\Models\lhe_2023;
// use App\Models\LheAkip;
// use App\Models\lke_subkomponens;
// use Dflydev\DotAccessData\Data;
// use Illuminate\Support\Facades\DB;
// use App\Models\lke_buktidukung;
// use App\Models\Lkjip;
// use App\Models\memo_datakinerja;
// use App\Models\memo_lkjip;
// use App\Models\MonevRenaksi;
// use App\Models\nodis_eval_sakip;
// use App\Models\nodis_p_sakip;
// use App\Models\notulensi_pm;
// use App\Models\Pk;
// use App\Models\PokinRanwal;
// use App\Models\Renaksi;
// use App\Models\Renja;
// use App\Models\Renstra;
// use App\Models\reward_punish;
// use App\Models\Rkakl;
// use App\Models\sampel_rekom;
// use App\Models\sample_skp;
// use App\Models\sk_pk;
// use App\Models\sk_pm;
// use App\Models\tar_pm;
// use App\Models\TlLheAkip;
// use App\Models\tar_lkjip;
// use App\Models\ss_perencanaan;
// use App\Models\ss_laporanweb;
// use App\Models\ss_laporanapp;
// use App\Models\nodis_datakinerja;
// use App\Models\RapatStaffEka;


// class DataLke extends Controller
// {
// public function index(){
//       if (!session()->has('tahun_terpilih')) {
//             return redirect()->route('pilih.tahun');
//         }
//         // Ambil tahun yang dipilih dari session
//         $tahun = session('tahun_terpilih');
//       $sections = [
//     'Perencanaan' => DataLke1::whereIn('subkomponen_id', [1, 2, 3])->get(),
//     'Pengukuran'  => DataLke1::whereIn('subkomponen_id', [4, 5, 6])->get(),
//     'Pelaporan'   => DataLke1::whereIn('subkomponen_id', [7, 8, 9])->get(),
//     'Evaluasi'    => DataLke1::whereIn('subkomponen_id', [10, 11, 12])->get(),
//     ];

//       return view('kelola.components.evaluasi_lke', compact('sections', 'tahun'));
// }

// private function getMapping()
// {
//     return [
//         1 => Renstra::class,
//         2 => Renja::class,
//         3 => Renaksi::class,
//         4 => Rkakl::class,
//         5 => Dipa::class,
//         6 => Pk::class,
//         7 => Pk::class,
//         8 => Iku::class,
//         9 => Iku::class,
//         10 => Lkjip::class,
//         11 => Lkjip::class,
//         12 => Lkjip::class,
//         13 => RapatStaffEka::class,
//         14 => RapatStaffEka::class,
//         15 => LheAkip::class,
//         16 => LheAkip::class,
//         17 => TlLheAkip::class,
//         18 => MonevRenaksi::class,
//         19 => MonevRenaksi::class,
//         20 => PokinRanwal::class,
//         21 => Renstra::class,
//         22 => Lkjip::class,
//         23 => sample_skp::class,
//         24 => sk_pm::class,
//         25 => sk_pk::class,
//         26 => absen_pm::class,
//         27 => notulensi_pm::class,
//         28 => nodis_p_sakip::class,
//         29 => nodis_eval_sakip::class,
//         30 => memo_datakinerja::class,
//         31 => nodis_datakinerja::class,
//         32 => reward_punish::class,
//         33 => sampel_rekom::class,
//         34 => ss_perencanaan::class,
//         35 => ss_laporanweb::class,
//         36 => ss_laporanapp::class,
//         37 => tar_lkjip::class,
//         38 => tar_lkjip::class,
//         39 => memo_lkjip::class,
//         40 => memo_lkjip::class,
//         41 => tar_pm::class,
//         42 => ba_praeval::class,
//         43 => ba_pleno::class,
//         44 => lhe_2023::class,
//     ];
// }
// private function penamaan(){
//     return [
//         20 => "Pokin_Ranwal",
//         21 => "Renstra",
//         22 => "Lkjip",
//         23 => "sample_skp",
//         24 => "SK_PM",
//         25 => "SK_PK",
//         26 => "Absen_PM",
//         27 => "Notulen_Bimtek_PM",
//         28 => "Nodis_P_Sakip",
//         29 => "Nodis_Eval_Sakip",
//         30 => "Memo_Data_Kinerja",
//         31 => "Nodis_Data_Kinerja",
//         32 => "Reward_Punish",
//         33 => "Sample_Rekomendasi",
//         34 => "SS_Perencanaan",
//         35 => "SS_Laporan_Web",
//         36 => "SS_Laporan_App",
//         37 => "Target_Lkjip_TW_1",
//         38 => "Target_Lkjip_TW_2",
//         39 => "Memo_Lkjip_TW_1",
//         40 => "Memo_Lkjip_TW_2",
//         41 => "Target_PM",
//         42 => "BA_Praevalusi_PM",
//         43 => "BA_Pleno_PM",
//         44 => "LHE"
//     ];
// }   

// public function cekBuktiDukung($kode){
//     if (!session()->has('tahun_terpilih')) {
//         return redirect()->route('pilih.tahun');
//     }
    
//     $tahun = session('tahun_terpilih');
//     $idSatker = session('id_satker');
//     $data1 = DataLke1::where('kode', $kode)->get();
    
//     // Ambil semua kode_bukti, pecah jadi array angka
//     $kodeBuktiIds = [];
//     foreach ($data1 as $item) {
//         $ids = explode(',', $item->kode_bukti);
//         foreach ($ids as $id) {
//             $kodeBuktiIds[] = (int)trim($id);
//         }
//     }
    
//     // Ambil nama dokumen dari lke_buktidukung
//     $bukti_dukung_nama = lke_buktidukung::whereIn('id', $kodeBuktiIds)->pluck('dokumen', 'id');
    
//     // Siapkan array hasil
//     $bukti_dukung = [];
//     foreach ($kodeBuktiIds as $id) {
//         $nama = $bukti_dukung_nama[$id] ?? '-';
//         $status = 'Tidak Ada';
//         $file = null;
//         if ($id == 1) {
//             $data= Renstra::where('id_satker', $idSatker)->first();
//              if ($data) {
//             $status = 'Ada';
//             $file   = $data->file ?? null; // kolom nama file
//         }
//         } elseif ($id == 2) {
//             $data= Renja::where('id_satker', $idSatker)->where('id_periode', '2025')->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         } elseif ($id == 3) {
//             $data= Renaksi::where('id_satker', $idSatker)->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 4) {
//             // Cek di tabel renaksi
//             $data= Rkakl::where('id_satker', $idSatker)->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 5) {
//             // Cek di tabel renaksi
//             $data= Dipa::where('id_satker', $idSatker)->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         } elseif($id == 6) {
//             // Cek di tabel renaksi
//             $data= Pk::where('id_satker', $idSatker)->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 7) {
//             // Cek di tabel renaksi
//             $data= Pk::where('id_satker', $idSatker)->where('id_periode', '2024')->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 8) {
//             // Cek di tabel renaksi
//             $data= Iku::where('id_satker', $idSatker)->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 9) {
//             // Cek di tabel renaksi
//             $data= Iku::where('id_satker', $idSatker)->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 10) {
//             // Cek di tabel renaksi
//             $data= Lkjip::where('id_satker', $idSatker)->where('triwulan', 'TW 1')->where('id_periode', 2025)->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 11) {
//             // Cek di tabel renaksi
//             $data= Lkjip::where('id_satker', $idSatker)->where('triwulan', 'TW 2')->where('id_periode', 2025)->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 12) {
//             // Cek di tabel renaksi
//             $data= Lkjip::where('id_satker', $idSatker)->where('triwulan', 'TW 4')->where('id_periode', 2024)->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 13) {
//             // Cek di tabel renaksi
//             $data= DB::table('sinori_sakip_rastaff')->where('id_satker', $idSatker)->where('id_triwulan', 'TW 1')->where('id_periode', 2025)->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 14) {
//             // Cek di tabel renaksi
//             $data= DB::table('sinori_sakip_rastaff')->where('id_satker', $idSatker)->where('id_triwulan', 'TW 2')->where('id_periode', 2025)->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 15) {
//             // Cek di tabel renaksi
//             $data= LheAkip::where('id_satker', $idSatker)->where('id_periode', '2024')->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 16) {
//             // Cek di tabel renaksi
//             $data= LheAkip::where('id_satker', $idSatker)->where('id_periode', '2025')->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 17) {
//             // Cek di tabel renaksi
//             $data= TlLheAkip::where('id_satker', $idSatker)->where('id_periode', '2024')->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 18) {
//             // Cek di tabel renaksi
//             $data= MonevRenaksi::where('id_satker', $idSatker)->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 19) {
//             // Cek di tabel renaksi
//             $data= MonevRenaksi::where('id_satker', $idSatker)->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 20) {
//             // Cek di tabel renaksi
//             $data= PokinRanwal::where('id_satker', $idSatker)->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 21) {
//             // Cek di tabel renaksi
//             $data=Renstra::where('id_satker', $idSatker)->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 22) {
//             // cari tahun 2023
//             $data=Lkjip::where('id_satker', $idSatker)->where('id_periode', '2023')->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 23) {
//             // cari tahun 2023
//             $data=sample_skp::where('id_satker', $idSatker)->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 24) {
//             $data=sk_pm::where('id_satker', $idSatker)->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 25) {
//             $data=sk_pk::where('id_satker', $idSatker)->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 26) {
//             $data=absen_pm::where('id_satker', $idSatker)->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 27) {
//             //harusnya notulensi bimtek
//             $data=notulensi_pm::where('id_satker', $idSatker)->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 28) {
//             $data=nodis_p_sakip::where('id_satker', $idSatker)->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 29) {
//             $data=nodis_eval_sakip::where('id_satker', $idSatker)->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 30) {
//             $data=memo_datakinerja::where('id_satker', $idSatker)->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 31) {
//             $data=nodis_datakinerja::where('id_satker', $idSatker)->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 32) {
//             $data=reward_punish::where('id_satker', $idSatker)->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 33) {
//             $data=sampel_rekom::where('id_satker', $idSatker)->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 34) {
//             $data=ss_perencanaan::where('id_satker', $idSatker)->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 35) {
//             $data=ss_laporanweb::where('id_satker', $idSatker)->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 36) {
//             $data=ss_laporanapp::where('id_satker', $idSatker)->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 37) {
//             //tw1
//             $data=tar_lkjip::where('id_satker', $idSatker)->where('id_triwulan', 'TW 1')->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 38) {
//             //tw2
//             $data=tar_lkjip::where('id_satker', $idSatker)->where('id_triwulan', 'TW 2')->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 39) {
//             //tw1
//             $data=memo_lkjip::where('id_satker', $idSatker)->where('id_triwulan', 'TW 1')->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 40) {
//             //tw2
//             $data=memo_lkjip::where('id_satker', $idSatker)->where('id_triwulan', 'TW 2')->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 41) {
//             //tw1
//             $data=tar_pm::where('id_satker', $idSatker)->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 42) {
//             $data=ba_praeval::where('id_satker', $idSatker)->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif ($id == 43) {
//             $data=ba_pleno::where('id_satker', $idSatker)->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//         }elseif( $id == 44) {
//             $data=lhe_2023::where('id_satker', $idSatker)->first();
//             if ($data) {
//                 $status = 'Ada';
//                 $file   = $data->file ?? null; // kolom nama file
//             }
//             }
//         $bukti_dukung[] = [
//             'nama'   => $nama,
//             'status' => $status,
//             'file'   => $file ? base_path('uploads/repository/'.$idSatker.'/'.$file) : null,
//         ];
//     }
    
//     return view('kelola.components.bukti_dukung_tabel', compact('bukti_dukung'));
// } 
//     public function showUploadForm()
//     {
//         if (!session()->has('tahun_terpilih')) {
//             return redirect()->route('pilih.tahun');
//         }
//         $tahun = session('tahun_terpilih');
//         $mapping = $this->getMapping(); // method getMapping()
//         $input = lke_buktidukung::all(); // contoh ambil semua dokumen
//         return view('upload_file', compact('tahun', 'input', 'mapping'));
//     }
// public function upload(Request $request)
// {   if (!session()->has('tahun_terpilih')) {
//             return redirect()->route('pilih.tahun');
//         }
//         $tahun = session('tahun_terpilih');
//     $request->validate([
//         'id_bukti' => 'required|integer',
//         'file'     => 'required|file|max:2048|mimes:pdf',
//     ]);

//     $id_bukti  = $request->id_bukti;
//     $file      = $request->file('file');
//     $tahun     = session('tahun_terpilih');
//     $id_satker = session('id_satker');

//     // Penamaan file
//     $namafile = ($this->penamaan()[$id_bukti] ?? 'dokumen')."_{$id_satker}_{$tahun}.pdf";
//     $file->move(base_path("uploads/repository/{$id_satker}"), $namafile);// Mapping model atau tabel
//     $mapping = $this->getMapping();
//     $target  = $mapping[$id_bukti] ?? null;

//     if (!$target) {
//         return back()->withErrors(['msg' => 'ID bukti tidak dikenali.']);
//     }

//     // Tentukan triwulan (jika berlaku)
//     $triwulan = null;
//     if (in_array($id_bukti, [37, 38])) {
//         $triwulan = $id_bukti == 37 ? 1 : 2;
//     } elseif (in_array($id_bukti, [39, 40])) {
//         $triwulan = $id_bukti == 39 ? 1 : 2;
//     }

//     // Cari latest data
//     $latest = null;
//     if (class_exists($target)) {
//         $latest = $target::where('id_satker', $id_satker)
//             ->where('id_periode', $tahun)
//             ->when($triwulan, function ($query) use ($triwulan) {
//                 return $query->where('id_triwulan', $triwulan);
//             })
//             ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc')
//             ->first();
//         $id_perubahan = $latest ? $latest->id_perubahan + 1 : 0;  
//         $namafile = ($this->penamaan()[$id_bukti] ?? 'dokumen')."_{$id_satker}_{$tahun}_{$id_perubahan}.pdf";
//         // Simpan ke model
//         $model              = new $target();
//         $model->id_satker   = $id_satker;
//         $model->id_periode  = $tahun;
//         $model->id_filename = $namafile;
//         $model->id_tglupload = now();
//         $model->id_perubahan = $latest ? $latest->id_perubahan + 1 : 0;
//         if (in_array($id_bukti, [37, 38, 39, 40])) {
//             $model->id_triwulan= $triwulan;
//         }

//         $model->save();

//     }else{
//         // Kalau target adalah nama tabel
//         $latest = DB::table($target)
//             ->where('id_satker', $id_satker)
//             ->where('id_periode', $tahun)
//             ->when($triwulan, function ($query) use ($triwulan) {
//                 return $query->where('id_triwulan', $triwulan);
//             })
//             ->orderBy(DB::raw('CAST(id_perubahan AS UNSIGNED)'), 'desc')
//             ->first();
//         DB::table($target)->insert([
//             'id_satker'    => $id_satker,
//             'id_periode'   => $tahun,
//             'id_filename'  => $namafile,
//             'id_tglupload' => now(),
//             'id_triwulan'  => $triwulan, // kalau tabel punya kolom ini
//         ]);
//     }

//     return back()->with('success', 'Bukti dukung berhasil diupload.');
//     }

//   public function getUploadedFiles(Request $request, $id_bukti)
// {
//     if (!session()->has('tahun_terpilih')) {
//         return response()->json([]);
//     }

//     $tahun     = session('tahun_terpilih');
//     $id_satker = session('id_satker');

//     $mapping = $this->getMapping();
//     $target  = $mapping[$id_bukti] ?? null;

//     if (!$target) {
//         return response()->json([]);
//     }

//     // Query file yang sudah ada
//     $query = $target::where('id_satker', $id_satker)
//         ->where('id_periode', $tahun);

//     if ($request->has('tw') && $request->tw) {
//         // ganti underscore jadi spasi
//         $tw = str_replace('_', ' ', $request->tw);
//         $query->where('id_triwulan', $tw);
//     }

//     $files = $query->orderBy('id_tglupload', 'desc')->get();

//     return response()->json($files);
// }

  
// }
