<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);

        $email = $request->input('email');
        $password = $request->input('password');

        $user = DB::table('sinori_login')->where('id_satker', $email)->first();

        if ($user && md5($password) === $user->satkerpass) {
            $request->session()->put([
                'id_satker' => $user->id_satker,
                'satkernama' => str_replace('_', ' ', $user->satkernama),
                'id_sakip_level' => $user->id_sakip_level,
            ]);

            auth()->loginUsingId($user->id_satker);

            return redirect()->route('dashboard');
        }

        return back()->withErrors(['email' => 'User atau Password yang dimasukan salah!']);
    }

    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
