<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ChatsupportController extends Controller
{
    public function index()
    {
        // Cek apakah tahun sudah dipilih
        if (!session()->has('tahun_terpilih')) {
            return redirect()->route('pilih.tahun');
        }

        $tahun = session('tahun_terpilih');
        return view('chatsupport', ['tahun' => $tahun]);
    }
}
