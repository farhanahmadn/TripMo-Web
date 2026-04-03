<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    
    /**
     * Bagian register 
     * Tampilkan halaman form register
     */
    public function showRegister()
    {
        return view('auth.register');
    }

    /**
     * Proses data dari form register
     */
    public function register(Request $request)
    {
        // 1. Validasi input dari form
        $request->validate([
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email', 
            'password' => 'required|string|min:8|confirmed',   
        ], [
            // Pesan error 
            'name.required'          => 'Nama wajib diisi.',
            'email.required'         => 'Email wajib diisi.',
            'email.email'            => 'Format email tidak valid.',
            'email.unique'           => 'Email sudah terdaftar. Gunakan email lain.',
            'password.required'      => 'Password wajib diisi.',
            'password.min'           => 'Password minimal 8 karakter.',
            'password.confirmed'     => 'Konfirmasi password tidak cocok.',
        ]);

        // 2. Simpan user baru ke database
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password), // enkripsi pw
        ]);

        // 3. Langsung login kalau register berhasil
        Auth::login($user);

        // 4. Redirect ke dashboard
        return redirect()->route('dashboard')->with('success', 'Selamat datang di Tripmo, ' . $user->name . '!');
    }

    /**
     * Bagian Loginnn
     * Tampilkan halaman form login
     */
    public function showLogin()
    {
        return view('auth.login');
    }

    /**
     * Proses data dari form login
     */
    public function login(Request $request)
    {
        // 1. Validasi input
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ], [
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        // 2. Ambil credential dari form
        $credentials = $request->only('email', 'password');

        // 3. Cek apakah user ingin diingat (remember me)
        $remember = $request->boolean('remember');

        // 4. Coba login dengan Auth::attempt()
        if (Auth::attempt($credentials, $remember)) {
            // Login berhasil akan regenerate session untuk keamanan
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'))
                             ->with('success', 'Selamat datang kembali, ' . Auth::user()->name . '! ✈️');
        }

        // 5. Login gagal akan ada pesan error
        return back()->withErrors([
            'email' => 'Email atau password salah.',
        ])->withInput($request->only('email'));
    }

    /**
     * Proses logout
     * Harus via POST untuk keamanan buat cegah CSRF
     */
    public function logout(Request $request)
    {
        // 1. Logout dari sistem
        Auth::logout();

        // 2. Invalidasi session lama
        $request->session()->invalidate();

        // 3. Buat token CSRF baru
        $request->session()->regenerateToken();

        // 4. Redirect ke halaman login
        return redirect()->route('login')->with('success', 'Kamu berhasil logout. Sampai jumpa! 👋');
    }
}
