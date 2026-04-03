@extends('layouts.app')
@section('title', 'Daftar — Tripmo')

@push('styles')
<style>
    .auth-wrap {
        min-height: calc(100vh - 56px);
        display: grid;
        grid-template-columns: 1fr 1fr;
    }

    .auth-left {
        position: relative;
        overflow: hidden;
        background: #111;
    }

    #auth-map { width: 100%; height: 100%; filter: brightness(0.45) saturate(0.7); }

    .auth-left-overlay {
        position: absolute; inset: 0;
        background: linear-gradient(to top, rgba(10,10,10,0.85) 0%, rgba(10,10,10,0.2) 60%);
        z-index: 2;
        display: flex; flex-direction: column; justify-content: flex-end;
        padding: 40px;
    }

    .auth-tag {
        display: inline-flex; align-items: center; gap: 6px;
        background: var(--purple); color: white;
        font-size: 11px; font-weight: 700; letter-spacing: .6px; text-transform: uppercase;
        padding: 5px 12px; border-radius: 100px; margin-bottom: 18px; width: fit-content;
    }

    .auth-left-title {
        font-size: 36px; font-weight: 800; color: white;
        line-height: 1.15; letter-spacing: -.5px; margin-bottom: 12px;
    }
    .auth-left-title span { color: rgba(255,255,255,.4); font-weight: 300; font-style: italic; }
    .auth-left-desc { font-size: 14px; color: rgba(255,255,255,.5); line-height: 1.65; max-width: 300px; }

    .auth-right {
        background: var(--bg2);
        display: flex; align-items: center; justify-content: center;
        padding: 48px 44px; overflow-y: auto;
    }

    .auth-box { width: 100%; max-width: 360px; animation: fadeUp .3s ease; }

    @keyframes fadeUp {
        from { opacity:0; transform:translateY(10px); }
        to   { opacity:1; transform:translateY(0); }
    }

    .auth-eyebrow {
        font-size: 11px; font-weight: 600; color: var(--text3);
        letter-spacing: .8px; text-transform: uppercase; margin-bottom: 8px;
    }

    .auth-title {
        font-size: 26px; font-weight: 800; color: var(--white);
        letter-spacing: -.4px; margin-bottom: 30px;
    }

    .auth-form { display: flex; flex-direction: column; gap: 15px; }

    .hint { font-size: 11px; color: var(--text3); margin-top: 3px; }

    .auth-foot {
        margin-top: 20px; text-align: center;
        font-size: 13px; color: var(--text2);
    }
    .auth-foot a { color: var(--purple); font-weight: 600; text-decoration: none; }
    .auth-foot a:hover { text-decoration: underline; }

    @media (max-width: 768px) {
        .auth-wrap { grid-template-columns: 1fr; }
        .auth-left { display: none; }
    }
</style>
@endpush

@section('content')
<div class="auth-wrap">

    <div class="auth-left">
        <div id="auth-map"></div>
        <div class="auth-left-overlay">
            <div class="auth-tag">Mulai Perjalanan</div>
            <h1 class="auth-left-title">
                Ceritakan<br><span>perjalananmu</span><br>ke dunia
            </h1>
            <p class="auth-left-desc">
                Bergabung dengan komunitas traveler Indonesia dan jadikan setiap liburanmu inspirasi bagi orang lain.
            </p>
        </div>
    </div>

    <div class="auth-right">
        <div class="auth-box">

            <p class="auth-eyebrow">Buat akun baru</p>
            <h2 class="auth-title">Daftar ke Tripmo</h2>

            <form action="{{ route('register.post') }}" method="POST" class="auth-form">
                @csrf

                <div class="fg">
                    <label class="fl" for="name">Nama Lengkap</label>
                    <input type="text" id="name" name="name"
                        class="fi {{ $errors->has('name') ? 'is-err' : '' }}"
                        placeholder="Nama kamu" value="{{ old('name') }}" autofocus>
                    @error('name')<span class="err-msg">{{ $message }}</span>@enderror
                </div>

                <div class="fg">
                    <label class="fl" for="email">Email</label>
                    <input type="email" id="email" name="email"
                        class="fi {{ $errors->has('email') ? 'is-err' : '' }}"
                        placeholder="nama@email.com" value="{{ old('email') }}">
                    @error('email')<span class="err-msg">{{ $message }}</span>@enderror
                </div>

                <div class="fg">
                    <label class="fl" for="password">Password</label>
                    <input type="password" id="password" name="password"
                        class="fi {{ $errors->has('password') ? 'is-err' : '' }}"
                        placeholder="Minimal 8 karakter">
                    <span class="hint">Minimal 8 karakter</span>
                    @error('password')<span class="err-msg">{{ $message }}</span>@enderror
                </div>

                <div class="fg">
                    <label class="fl" for="password_confirmation">Konfirmasi Password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                        class="fi" placeholder="Ulangi password">
                </div>

                <button type="submit" class="btn btn-purple btn-full" style="margin-top:4px">
                    Buat Akun
                </button>
            </form>

            <div class="auth-foot">
                Sudah punya akun? <a href="{{ route('login') }}">Masuk di sini</a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const map = L.map('auth-map', {
        center: [-2.5, 118], zoom: 5,
        zoomControl: false, scrollWheelZoom: false,
        dragging: false, attributionControl: false,
    });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
</script>
@endpush
