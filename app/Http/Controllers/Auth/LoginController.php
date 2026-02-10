<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
class LoginController extends Controller
{
    // Menampilkan form login
    public function showLoginForm()
    {
        return Inertia::render('Auth/Login');
    }

    // Menangani login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);

        $email = $request->input('email');
        $password = $request->input('password');

        // Fetch user from database
        $user = DB::table('sinori_login')->where('id_satker', $email)->first();

        // Check if user exists and password is correct
        if ($user && md5($password) === $user->satkerpass) {
            // Store user data in session
            $request->session()->put('id_satker', $user->id_satker);
            $request->session()->put('satkernama', str_replace('_', ' ', $user->satkernama));
            $request->session()->put('id_sakip_level', $user->id_sakip_level);

            // Mark the user as logged in manually
            auth()->loginUsingId($user->id_satker);
            // dd($user->id_sakip_level); 
            return redirect()->route('dashboard');
        }

        return back()->withErrors(['email' => 'User atau Password yang dimasukan salah!']);
    }


    // Menangani logout
    public function logout(Request $request)
    {
        // Invalidate session
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redirect ke halaman login
        // return redirect('https://sicana.kejaksaan.go.id/');
         return redirect('/');
    }
}
