@extends('layouts.app')
@section('title', 'Dashboard — Tripmo')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@endpush

@section('content')
<div class="dash-wrap">

    {{-- PETA --}}
    <div id="main-map"></div>

    {{-- PANEL KIRI --}}
    <div class="side-panel" id="sidePanel">
        <button class="panel-close" onclick="closePanel()">✕</button>

        {{-- Profil --}}
        <div id="panelProfile" class="panel-profile" style="display:none">
            <div class="pp-top">
                <div class="pp-av">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                <div>
                    <div class="pp-name">{{ $user->name }}</div>
                    <div class="pp-handle">@{{ strtolower(str_replace(' ', '', $user->name)) }}</div>
                </div>
            </div>
            <div class="pp-stats">
                <div><span class="pp-stat-num">0</span><span class="pp-stat-label">Pengikut</span></div>
                <div><span class="pp-stat-num">0</span><span class="pp-stat-label">Mengikuti</span></div>
                <div><span class="pp-stat-num">0</span><span class="pp-stat-label">Jejak</span></div>
            </div>
            <div class="pp-btns">
                <button class="pp-btn" disabled>Edit Profil</button>
                <button class="pp-btn" disabled>Bagikan Profil</button>
                <div class="pp-icon-btn">👤</div>
            </div>
            <div class="pp-divider"></div>
            <div class="pp-empty">
                <span class="pp-empty-icon"></span>
                <div class="pp-empty-text">Belum ada postingan perjalanan.<br>Tandai lokasi di peta dan mulai cerita!</div>
            </div>
            <form action="{{ route('logout') }}" method="POST" style="padding:20px 0 0">
                @csrf
                <button type="submit" class="pp-logout">Keluar dari Tripmo</button>
            </form>
        </div>

        {{-- Search --}}
        <div id="panelSearch" class="panel-search" style="display:none">
            <div class="search-box">
                <span style="color:rgba(255,255,255,.4)"></span>
                <input type="text" placeholder="Cari...">
            </div>
            <div class="search-tabs">
                <button class="search-tab active">Pengguna</button>
                <button class="search-tab">Tempat</button>
            </div>
            <div class="search-empty">Ketik nama pengguna atau tempat untuk mencari.</div>
        </div>

        {{-- People --}}
        <div id="panelPeople" class="panel-people" style="display:none">
            <div class="people-title">Jelajahi Traveler</div>
            <div class="people-empty">Fitur ini akan hadir segera!</div>
        </div>
    </div>

    {{-- KOORDINAT --}}
    <div class="coord-badge" id="coordBadge"> Klik peta untuk tandai lokasi</div>

    {{-- BOTTOM BAR --}}
    <div class="bottom-bar">
        <div class="bar-capsule">
            <button class="bar-btn" id="btnProfile" onclick="openPanel('profile')" title="Profil">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
            </button>
            <button class="bar-btn" id="btnSearch" onclick="openPanel('search')" title="Cari">
                <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.35-4.35"/></svg>
            </button>
            <button class="bar-btn" id="btnPeople" onclick="openPanel('people')" title="Traveler">
                <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3 1.34 3 3 3zm-8 0c1.66 0 3-1.34 3-3S9.66 5 8 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5C15 14.17 10.33 13 8 13zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
            </button>
            <button class="bar-btn" disabled style="opacity:.4" title="Pengaturan">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            </button>
        </div>
        <div class="bar-single" id="btnPin" title="Tandai Lokasi">
            <svg viewBox="0 0 24 24" style="stroke:rgba(255,255,255,0.65);fill:none;stroke-width:1.8;stroke-linecap:round;stroke-linejoin:round;width:22px;height:22px"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        </div>
    </div>

</div>
@endsection

@push('scripts')
    <script src="{{ asset('js/dashboard.js') }}"></script>
@endpush
