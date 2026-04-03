<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Tripmo')</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <style>
        :root {
            --bg:        #181818;
            --bg2:       #222222;
            --bg3:       #2a2a2a;
            --border:    rgba(255,255,255,0.08);
            --border2:   rgba(255,255,255,0.13);
            --white:     #ffffff;
            --text:      #f0f0f0;
            --text2:     #9a9a9a;
            --text3:     #5a5a5a;
            --purple:    #7c5cfc;
            --purple-h:  #6a4de8;
            --purple-bg: rgba(124,92,252,0.15);
            --red:       #e84040;
            --nav-h:     56px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body {
            height: 100%;
            background: var(--bg);
            color: var(--text);
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 14px;
            -webkit-font-smoothing: antialiased;
        }

        /* ── NAVBAR ── */
        .nav {
            position: fixed;
            top: 0; left: 0; right: 0;
            height: var(--nav-h);
            background: var(--bg);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            padding: 0 20px;
            z-index: 999;
        }

        .nav-inner {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 9px;
            text-decoration: none;
        }

        /* Logo: kotak ungu + teks */
        .nav-logo {
            width: 28px; height: 28px;
            background: var(--purple);
            border-radius: 7px;
        }

        .nav-name {
            font-size: 17px;
            font-weight: 700;
            color: var(--white);
            letter-spacing: -0.3px;
        }

        .nav-right { display: flex; align-items: center; gap: 8px; }

        .nav-user {
            display: flex; align-items: center; gap: 8px;
            padding: 5px 12px 5px 6px;
            background: var(--bg3);
            border-radius: 100px;
            border: 1px solid var(--border);
        }

        .nav-av {
            width: 24px; height: 24px;
            background: var(--purple);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 10px; font-weight: 700; color: white;
        }

        .nav-user-name { font-size: 13px; font-weight: 500; color: var(--text2); }

        .btn-logout {
            background: var(--bg3);
            border: 1px solid var(--border);
            color: var(--text2);
            padding: 6px 14px;
            border-radius: 100px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 12px; font-weight: 500;
            cursor: pointer;
            transition: all .18s;
        }
        .btn-logout:hover { border-color: var(--border2); color: var(--text); }

        .btn-nav {
            text-decoration: none;
            padding: 7px 16px;
            border-radius: 100px;
            font-size: 13px; font-weight: 600;
            transition: all .18s;
        }
        .btn-nav-solid { background: var(--purple); color: white; }
        .btn-nav-solid:hover { background: var(--purple-h); }
        .btn-nav-ghost { color: var(--text2); }
        .btn-nav-ghost:hover { color: var(--text); }

        /* PAGE */
        .page { padding-top: var(--nav-h); min-height: 100vh; }

        /* TOAST*/
        .toasts {
            position: fixed; bottom: 20px; right: 20px;
            z-index: 9999; display: flex; flex-direction: column; gap: 8px;
        }

        .toast {
            background: var(--bg3);
            border: 1px solid var(--border2);
            border-radius: 10px;
            padding: 11px 16px;
            font-size: 13px; font-weight: 500;
            display: flex; align-items: center; gap: 9px;
            color: var(--text);
            cursor: pointer;
            animation: toastIn .25s ease;
            box-shadow: 0 4px 24px rgba(0,0,0,.4);
        }
        .toast-ok  { border-left: 3px solid #22c55e; }
        .toast-err { border-left: 3px solid #ef4444; }

        @keyframes toastIn {
            from { opacity:0; transform:translateY(6px); }
            to   { opacity:1; transform:translateY(0); }
        }

        /* FORM GLOBALS */
        .fg { display: flex; flex-direction: column; gap: 6px; }
        .fl { font-size: 12px; font-weight: 600; color: var(--text2); letter-spacing: .3px; }

        .fi {
            width: 100%;
            background: var(--bg3);
            border: 1px solid var(--border2);
            border-radius: 10px;
            padding: 12px 14px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 14px; color: var(--text);
            outline: none;
            transition: border-color .18s, box-shadow .18s;
        }
        .fi:focus { border-color: var(--purple); box-shadow: 0 0 0 3px rgba(124,92,252,.15); }
        .fi::placeholder { color: var(--text3); }
        .fi.is-err { border-color: var(--red); }

        .err-msg { font-size: 12px; color: #f87171; }

        /* BUTTONS */
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 7px;
            padding: 11px 20px; border-radius: 10px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 14px; font-weight: 600;
            cursor: pointer; border: none; text-decoration: none; transition: all .18s;
        }
        .btn-purple { background: var(--purple); color: white; }
        .btn-purple:hover { background: var(--purple-h); }
        .btn-white { background: white; color: #111; }
        .btn-white:hover { background: #f0f0f0; }
        .btn-ghost { background: var(--bg3); color: var(--text2); border: 1px solid var(--border); }
        .btn-ghost:hover { border-color: var(--border2); color: var(--text); }
        .btn-full { width: 100%; padding: 13px; font-size: 15px; border-radius: 12px; }
        .btn-sm { padding: 7px 13px; font-size: 12px; border-radius: 8px; }
    </style>
    @stack('styles')
</head>
<body>

<nav class="nav">
    <div class="nav-inner">
        <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="nav-brand">
            <div class="nav-logo"></div>
            <span class="nav-name">Tripmo</span>
        </a>
        <div class="nav-right">
            @auth
                <div class="nav-user">
                    <div class="nav-av">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</div>
                    <span class="nav-user-name">{{ auth()->user()->name }}</span>
                </div>
                <form action="{{ route('logout') }}" method="POST" style="margin:0">
                    @csrf
                    <button type="submit" class="btn-logout">Logout</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="btn-nav btn-nav-ghost">Masuk</a>
                <a href="{{ route('register') }}" class="btn-nav btn-nav-solid">Daftar</a>
            @endauth
        </div>
    </div>
</nav>

<div class="toasts">
    @if(session('success'))
        <div class="toast toast-ok" onclick="this.remove()">✓ {{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="toast toast-err" onclick="this.remove()">✕ {{ session('error') }}</div>
    @endif
</div>

<div class="page">@yield('content')</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    setTimeout(() => {
        document.querySelectorAll('.toast').forEach(el => {
            el.style.transition = 'opacity .3s';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 300);
        });
    }, 3500);
</script>
@stack('scripts')
</body>
</html>
