<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\User;

class UbahpasswordController extends Controller
{
    public function index()
    {
         // Cek apakah tahun sudah dipilih
        if (!session()->has('tahun_terpilih')) {
            return redirect()->route('pilih.tahun');
        }

        $tahun = session('tahun_terpilih');
        return view('ubahpassword', ['tahun' => $tahun]);
    }
    

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);
    
        $user = auth()->user();
    
        // Bandingkan password lama (pakai MD5)
        if (md5($request->current_password) !== $user->satkerpass) {
            dd(md5($request->current_password), $user->satkerpass, $user->id_satker);
            return back()->withErrors(['current_password' => 'Password lama tidak cocok!']);
        }
    
        // Update password (pakai MD5)
        $user->update([
            'satkerpass' => md5($request->new_password),
        ]);
        
        return back()->with('success', 'Password berhasil diperbarui!');
    }

}
