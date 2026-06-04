@extends('layouts.app')
@section('title', $post->title)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/post-show.css') }}">
@endpush

@section('content')
@php
    $dests = $post->destinations
        ? (is_array($post->destinations) ? $post->destinations : json_decode($post->destinations, true))
        : [];
@endphp

<div class="show-wrap">

    <div class="show-left">

        {{-- Foto --}}
        <div class="foto-header">
    <a href="{{ route('dashboard') }}" class="btn-kembali">✕</a>

    @if($post->photos->count() > 0)
        <div class="foto-slider" id="fotoSlider">
            @foreach($post->photos as $foto)
                <div class="foto-slide">
                    <img src="{{ foto_url($foto->file_path) }}" alt="">
                </div>
            @endforeach
        </div>

        @if($post->photos->count() > 1)
            <button class="slider-btn slider-prev" onclick="slidePhoto(-1)">‹</button>
            <button class="slider-btn slider-next" onclick="slidePhoto(1)">›</button>
            <div class="slider-dots" id="sliderDots">
                @foreach($post->photos as $i => $foto)
                    <div class="slider-dot {{ $i === 0 ? 'active' : '' }}" onclick="goSlide({{ $i }})"></div>
                @endforeach
            </div>
        @endif
    @endif
</div>

        <div class="show-content">

            <div class="show-meta">
                <span>{{ $post->travel_date ?? '-' }}</span>
                <span>{{ $post->location }}</span>
                @if(count($dests) > 0)<span>{{ count($dests) }} Destinasi</span>@endif
            </div>

            <div class="show-title">{{ $post->title }}</div>

            <div class="show-author">
                <div class="author-av">{{ strtoupper(substr($post->user->name, 0, 1)) }}</div>
                <div>
                    <div class="author-name">{{ $post->user->name }}</div>
                    @if($post->user->bio)
                    <div class="author-role">{{ $post->user->bio }}</div>
                    @endif
                </div>
            </div>

            @if($post->story)
                <div class="section-title">Cerita Perjalanan</div>
                <div class="show-story">{{ $post->story }}</div>
            @endif

            @if(count($dests) > 0)
                <div class="section-title">Rute & Destinasi</div>
                <div style="margin-bottom:28px">
                    @foreach($dests as $i => $d)
                        <div class="rute-item" onclick="focusMap({{ $i }})">
                            <div class="rute-num" style="background:{{ $i===0 ? '#7c5cfc' : ($i===count($dests)-1 ? '#7c5cfc' : '#6366f1') }}">{{ $i+1 }}</div>
                            <span class="rute-text">{{ is_array($d) ? $d['name'] : $d }}</span>
                            <span style="color:rgba(255,255,255,.25)">›</span>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="section-title">Budget</div>
            <div class="budget-section">
                <span class="budget-label">Total Pengeluaran</span>
                <span class="budget-amount">Rp {{ number_format($post->total_budget, 0, ',', '.') }}</span>
            </div>

            @if(auth()->id() !== $post->user_id)
                <div class="section-title">Beri Rating</div>
                <form action="{{ route('post.rate', $post->id) }}" method="POST"
                      style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
                    @csrf
                    <div id="stars" style="display:flex">
                        @for($i = 1; $i <= 5; $i++)
                            <button type="button" class="star-btn"
                                    style="color:{{ $myRating && $myRating->score >= $i ? '#fbbf24' : 'rgba(255,255,255,.2)' }}"
                                    onclick="setStar({{ $i }})">★</button>
                        @endfor

                    </div>
                    <input type="hidden" name="score" id="scoreVal" value="{{ $myRating->score ?? '' }}">
                    <button type="submit" style="background:#7c5cfc;color:white;border:none;border-radius:8px;padding:9px 18px;font-family:inherit;font-size:13px;font-weight:600;cursor:pointer">Simpan</button>
                </form>
            @endif

            @if(auth()->id() === $post->user_id)
    <div style="display:flex; gap:10px; margin-top:8px">
        <a href="{{ route('post.edit', $post->id) }}"
           style="flex:1; text-align:center; padding:11px; border-radius:9px;
                  border:1px solid rgba(255,255,255,.15); color:rgba(255,255,255,.7);
                  text-decoration:none; font-size:14px; font-weight:500">
            Edit Postingan
        </a>
        <form action="{{ route('post.destroy', $post->id) }}" method="POST"
              onsubmit="return confirm('Hapus postingan ini?')" style="flex:1">
            @csrf @method('DELETE')
            <button type="submit"
                    style="width:100%; padding:11px; border-radius:9px;
                           background:rgba(239,68,68,.12); border:1px solid rgba(239,68,68,.2);
                           color:#f87171; font-family:inherit; font-size:14px;
                           font-weight:500; cursor:pointer">
                Hapus Postingan
            </button>
        </form>
    </div>
@endif

        </div>
    </div>

    <div class="show-right">
        <div id="detail-map"></div>
    </div>

</div>
@endsection

@push('scripts')
<script>
/* ── Rating bintang ── */
function setStar(val) {
    document.getElementById('scoreVal').value = val;
    document.querySelectorAll('#stars .star-btn').forEach((b, i) => {
        b.style.color = i < val ? '#fbbf24' : 'rgba(255,255,255,.2)';
    });
}

/* ── Inisialisasi peta ── */
const detailMap = L.map('detail-map', { zoomControl: false });

// Tile CartoDB Dark Matter — gratis tanpa API key, dengan filter agar lebih jelas
L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> © <a href="https://carto.com/">CARTO</a>',
    subdomains: 'abcd',
    maxZoom: 20,
    className: 'tiles-legible'
}).addTo(detailMap);

L.control.zoom({ position: 'topright' }).addTo(detailMap);

const dests       = @json($dests);
const markers     = [];
let   routeLayer  = null;   // layer polyline rute aktif

/* Buat marker bernomor */
function buatMarker(d, i) {
    const isEndpoint = i === 0 || i === dests.length - 1;
    const warna  = isEndpoint ? '#a78bfa' : '#818cf8';
    const glow   = isEndpoint ? 'rgba(167,139,250,.5)' : 'rgba(129,140,248,.4)';
    const ikon   = L.divIcon({
        html: `<div style="
            width:30px;height:30px;background:${warna};
            border:2.5px solid rgba(255,255,255,.9);border-radius:50%;
            display:flex;align-items:center;justify-content:center;
            font-size:11px;font-weight:700;color:white;
            box-shadow:0 0 0 4px ${glow},0 4px 14px rgba(0,0,0,.6)">${i + 1}</div>`,
        iconSize: [30, 30], iconAnchor: [15, 15], className: ''
    });
    return L.marker([parseFloat(d.lat), parseFloat(d.lng)], { icon: ikon }).addTo(detailMap);
}

/* Hitung jarak lurus antar dua titik (km) — Haversine */
function jarakLurus(a, b) {
    const R = 6371, dLat = (b.lat - a.lat) * Math.PI / 180,
          dLng = (b.lng - a.lng) * Math.PI / 180,
          x = Math.sin(dLat/2)**2 +
              Math.cos(a.lat*Math.PI/180) * Math.cos(b.lat*Math.PI/180) * Math.sin(dLng/2)**2;
    return R * 2 * Math.atan2(Math.sqrt(x), Math.sqrt(1-x));
}

function totalJarakLurus(destList) {
    let total = 0;
    for (let i = 0; i < destList.length - 1; i++) total += jarakLurus(destList[i], destList[i+1]);
    return total;
}

/* Gambar rute — profil foot (berjalan/pendakian), fallback cerdas */
async function gambarRute(destList) {
    if (routeLayer) {
        if (Array.isArray(routeLayer)) { routeLayer.forEach(l => detailMap.removeLayer(l)); }
        else detailMap.removeLayer(routeLayer);
        routeLayer = null;
    }
    if (destList.length < 2) return;

    const coords   = destList.map(d => `${parseFloat(d.lng)},${parseFloat(d.lat)}`).join(';');
    const lurusKm  = totalJarakLurus(destList);

    try {
        /* Coba profil foot (jalan kaki / hiking) */
        const res  = await fetch(
            `https://router.project-osrm.org/route/v1/foot/${coords}?overview=full&geometries=geojson`
        );
        const data = await res.json();

        if (data.code !== 'Ok' || !data.routes.length) throw new Error('no-route');

        const osrmKm = data.routes[0].distance / 1000;
        const rasio  = osrmKm / lurusKm;

        if (rasio > 3.5) {
            /* Rute terlalu memutar (misal: antar pulau / jalur pendakian off-road)
               → tampilkan garis estimasi dengan indikator */
            gambarGarisEstimasi(destList);
            tampilBadgeEstimasi();
        } else {
            /* Rute normal: shadow + garis utama */
            const shadow = L.geoJSON(data.routes[0].geometry, {
                style: { color: '#4f46e5', weight: 9, opacity: 0.18 }
            }).addTo(detailMap);
            const line   = L.geoJSON(data.routes[0].geometry, {
                style: { color: '#6366f1', weight: 4, opacity: 0.95 }
            }).addTo(detailMap);
            routeLayer = [shadow, line];
            sembunyikanBadgeEstimasi();
        }
    } catch (e) {
        gambarGarisEstimasi(destList);
        tampilBadgeEstimasi();
    }
}

function gambarGarisEstimasi(destList) {
    const titik  = destList.map(d => [parseFloat(d.lat), parseFloat(d.lng)]);
    const shadow = L.polyline(titik, { color: '#f59e0b', weight: 8, opacity: 0.15 }).addTo(detailMap);
    const line   = L.polyline(titik, {
        color: '#f59e0b', weight: 3, opacity: 0.85, dashArray: '10 6'
    }).addTo(detailMap);
    routeLayer = [shadow, line];
}

function tampilBadgeEstimasi() {
    let badge = document.getElementById('routeBadge');
    if (!badge) {
        badge = document.createElement('div');
        badge.id = 'routeBadge';
        badge.style.cssText = `
            position:absolute; bottom:14px; left:14px; z-index:500;
            background:rgba(245,158,11,0.15); border:1px solid rgba(245,158,11,0.4);
            color:#fbbf24; font-size:11px; font-weight:600; padding:6px 12px;
            border-radius:20px; backdrop-filter:blur(8px); pointer-events:none;`;
        badge.textContent = '〰 Jalur estimasi (off-road / pendakian)';
        document.querySelector('.show-right').appendChild(badge);
    }
    badge.style.display = 'block';
}

function sembunyikanBadgeEstimasi() {
    const badge = document.getElementById('routeBadge');
    if (badge) badge.style.display = 'none';
}

/* Inisialisasi marker + rute */
if (dests.length > 0) {
    const validDests = dests.filter(d => d && d.lat && d.lng);

    validDests.forEach((d, i) => markers.push(buatMarker(d, i)));

    if (validDests.length > 1) {
        gambarRute(validDests).then(() => {
            /* Fit bounds setelah rute ter-render */
            const titik = validDests.map(d => [parseFloat(d.lat), parseFloat(d.lng)]);
            detailMap.fitBounds(titik, { padding: [60, 60] });
        });
    } else {
        detailMap.setView([parseFloat(validDests[0].lat), parseFloat(validDests[0].lng)], 14);
    }
} else {
    detailMap.setView([-6.9, 107.6], 12);
}

/* Pastikan peta render penuh (terutama di mobile setelah layout berubah) */
window.addEventListener('load', () => setTimeout(() => detailMap.invalidateSize(), 200));
window.addEventListener('resize', () => detailMap.invalidateSize());

/* Fokus ke titik saat item rute diklik */
function focusMap(i) {
    document.querySelectorAll('.rute-item').forEach((el, j) => el.classList.toggle('active', i === j));
    const d = dests[i];
    if (!d || !d.lat || !d.lng) return;
    detailMap.setView([parseFloat(d.lat), parseFloat(d.lng)], 15);
    if (markers[i]) markers[i].openPopup();
}

/* ── Slider foto ── */
let slideIndex = 0;
const slides   = document.querySelectorAll('.foto-slide');
const dots     = document.querySelectorAll('.slider-dot');

function goSlide(n) {
    slideIndex = n;
    const slider = document.getElementById('fotoSlider');
    if (slider) slider.style.transform = `translateX(-${slideIndex * 100}%)`;
    dots.forEach((d, i) => d.classList.toggle('active', i === slideIndex));
}

function slidePhoto(dir) {
    goSlide((slideIndex + dir + slides.length) % slides.length);
}
</script>
@endpush