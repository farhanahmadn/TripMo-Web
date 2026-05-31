<nav class="nav">
    <div class="nav-inner">
        <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="nav-brand">
            <div class="nav-logo"></div>
            <span class="nav-name">Tripmo</span>
        </a>
        <div class="nav-right">
            @auth
                <div class="nav-user">
                    <div class="nav-av">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
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
