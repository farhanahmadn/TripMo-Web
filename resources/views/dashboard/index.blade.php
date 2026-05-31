@extends('layouts.app')
@section('title', 'Dashboard — Tripmo')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/glass.css') }}">
    <link rel="stylesheet" href="{{ asset('css/smart-systems.css') }}">
@endpush

@section('content')
<div class="dash-wrap">
    <div id="main-map"></div>

    <div class="side-panel" id="sidePanel">
        <button class="panel-close" onclick="closePanel()">✕</button>

        <div id="panelProfile" class="panel-profile" style="display: none">
            <div class="pp-top">
                <div class="pp-av">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                <div>
                    <div class="pp-name">{{ $user->name }}</div>
                    <div class="pp-handle">{{ '@' . strtolower(str_replace(' ', '', $user->name)) }}</div>
                </div>
            </div>
            <div class="pp-stats">
                <div>
                    <span class="pp-stat-num">{{ $postingan->count() }}</span>
                    <span class="pp-stat-label">Jejak</span>
                </div>
            </div>
            <div class="pp-btns">
                <a href="{{ route('profile.edit') }}" class="pp-btn" style="text-align: center; text-decoration: none">Edit Profil</a>
                <button class="pp-btn" onclick="shareProfile()">Bagikan Profil</button>
            </div>
            <div class="pp-divider"></div>

            @if($postingan->count() > 0)
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 2px; margin-bottom: 12px">
                    @foreach($postingan as $p)
                        <a href="{{ route('post.show', $p->id) }}" style="position: relative; aspect-ratio: 1; overflow: hidden; display: block; background: rgba(255, 255, 255, .04); border-radius: 8px">
                            @if($p->photos->first())
                                <img src="{{ Storage::url($p->photos->first()->file_path) }}" style="width: 100%; height: 100%; object-fit: cover; display: block" loading="lazy" alt="{{ $p->title }}">
                            @else
                                <div style="width: 100%; height: 100%; min-height: 80px; display: flex; align-items: center; justify-content: center; color: rgba(255, 255, 255, .3); font-size: 22px">🗺</div>
                            @endif
                        </a>
                    @endforeach
                </div>
            @else
                <div class="pp-empty">
                    <div class="pp-empty-text">Belum ada postingan.<br>Klik <strong>+</strong> untuk mulai.</div>
                </div>
            @endif

            <div id="travelStatsContainer"></div>

            <form action="{{ route('logout') }}" method="POST" style="padding: 4px 0 8px">
                @csrf
                <button type="submit" class="pp-logout">Keluar dari Tripmo</button>
            </form>
        </div>

        <div id="panelSearch" class="panel-search" style="display: none">
            <div id="trendingContainer"></div>

            <div style="padding: 0 20px">
                <div style="font-size: 16px; font-weight: 700; color: white; margin-bottom: 12px">Temukan Jejak</div>
                <div class="search-box">
                    <svg viewBox="0 0 24 24" style="width: 16px; height: 16px; stroke: rgba(255, 255, 255, .4); fill: none; stroke-width: 2; flex-shrink: 0">
                        <circle cx="11" cy="11" r="7"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                    <input type="text" id="searchInput" placeholder="Cari lokasi, destinasi..." oninput="handleSearch(this.value)">
                    <span id="searchSpinner" style="display: none; font-size: 12px; color: rgba(255, 255, 255, .3)">...</span>
                </div>
            </div>

            <div id="searchResults" style="padding: 12px 20px 40px; overflow-y: auto; max-height: calc(100vh - 160px)">
                <div style="text-align: center; padding: 32px 0; color: rgba(255, 255, 255, .3); font-size: 13px">Ketik nama lokasi untuk menemukan jejak perjalanan.</div>
            </div>
        </div>



        <div id="panelCreate" style="display: none; overflow-y: auto; height: 100%">
            <div style="padding: 22px 20px 0 20px">
                <div style="font-size: 16px; font-weight: 700; color: white; margin-bottom: 3px">Buat Postingan</div>
                <div style="font-size: 12px; color: rgba(255, 255, 255, .4); margin-bottom: 20px">Ceritakan perjalananmu</div>
            </div>

            <form action="{{ route('post.store') }}" method="POST" enctype="multipart/form-data" style="padding: 0 20px 40px; display: flex; flex-direction: column; gap: 16px">
                @csrf

                <div>
                    <div class="p-label">Judul <span style="color: #f87171">*</span></div>
                    <input type="text" name="title" class="p-input" placeholder="Contoh: Travel ke Bandung" required>
                </div>

                <div>
                    <div class="p-label">Tanggal</div>
                    <input type="date" name="travel_date" class="p-input">
                </div>

                <div>
                    <div class="p-label">Foto</div>
                    <div class="p-upload" onclick="document.getElementById('pFoto').click()">
                        <span style="font-size: 22px">🖼️</span>
                        <span style="font-size: 12px; color: rgba(255, 255, 255, .4)">Klik untuk upload foto</span>
                    </div>
                    <input type="file" id="pFoto" name="photos[]" multiple accept="image/*" style="display: none" onchange="previewFoto(this)">
                    <div id="pFotoPreview" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; margin-top: 8px"></div>
                </div>

                <div>
                    <div class="p-label">Cerita</div>
                    <textarea name="story" class="p-input" rows="3" placeholder="Ceritakan pengalamanmu..."></textarea>
                </div>

                <div>
                    <div class="p-label">Rute Destinasi <span style="color: #f87171">*</span></div>
                    <div style="font-size: 11px; color: rgba(255, 255, 255, .3); margin-bottom: 8px">Tambah minimal 1 destinasi — klik pada peta atau cari di bawah</div>
                    <div style="display: flex; gap: 6px; margin-bottom: 6px">
                        <input type="text" id="pDestInput" class="p-input" placeholder="Cari lokasi..." style="flex: 1">
                        <button type="button" onclick="tambahDest()" class="p-add-btn" aria-label="Tambah destinasi">+</button>
                    </div>
                    <div id="destSaran" style="display: none; background: rgba(20, 20, 30, .98); border: 1px solid rgba(255, 255, 255, .1); border-radius: 8px; overflow: hidden; margin-bottom: 8px"></div>
                    <div id="listDest"></div>
                    <input type="hidden" name="destinations" id="destData">
                    <input type="hidden" name="location" id="lokasiUtama">
                    <div id="budgetInsightCreate" style="display: none"></div>
                </div>

                <div>
                    <div class="p-label">Total Budget (Rp)</div>
                    <input type="number" name="total_budget" id="totalBudgetInput" class="p-input" placeholder="Contoh: 2500000" min="0">
                    <div id="budgetHint" style="font-size: 11px; color: rgba(255, 255, 255, .3); margin-top: 4px"></div>
                </div>

                <button type="submit" class="p-submit-btn">Posting Sekarang 🚀</button>
            </form>
        </div>
    </div>

    <div class="bottom-bar">
        <div class="bar-capsule">
            <button class="bar-btn" id="btnProfile" onclick="openPanel('profile')" title="Profil" aria-label="Buka panel profil">
                <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="8" r="4"></circle>
                    <path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"></path>
                </svg>
            </button>
            <button class="bar-btn" id="btnSearch" onclick="openPanel('search')" title="Cari" aria-label="Buka panel pencarian">
                <svg viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="7"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
            </button>

        </div>
        <div class="bar-single" id="btnCreate" onclick="openPanel('create')" style="background: #7c5cfc; border-color: #7c5cfc; cursor: pointer" title="Buat Postingan" aria-label="Buat postingan baru" role="button">
            <svg viewBox="0 0 24 24" style="stroke: white; fill: none; stroke-width: 2.5; stroke-linecap: round; width: 22px; height: 22px">
                <path d="M12 5v14M5 12h14"></path>
            </svg>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const profileUrl = "{{ route('profile.show', $user->id) }}";
</script>
<script src="{{ asset('js/smart-systems.js') }}"></script>
<script src="{{ asset('js/dashboard.js') }}"></script>
<script>


const { BudgetInsight, TrendingDestinations, TravelStats } = window.TripMoSmart;
const budgetInsightCreate = new BudgetInsight('budgetInsightCreate');

function _triggerBudgetInsight() {
    setTimeout(() => {
        const lokasi = document.getElementById('lokasiUtama')?.value || '';
        if (lokasi) {
            budgetInsightCreate.trigger(lokasi);
            const hint = document.getElementById('budgetHint');
            if (hint) hint.textContent = 'Lihat referensi budget di atas sebelum mengisi ↑';
        }
    }, 200);
}

const _origTambahDest = window.tambahDest;
window.tambahDest = function () {
    _origTambahDest && _origTambahDest();
    _triggerBudgetInsight();
};

const _origPilihDest = window.pilihDest;
window.pilihDest = function (name, lat, lng) {
    _origPilihDest && _origPilihDest(name, lat, lng);
    _triggerBudgetInsight();
};

const trending = new TrendingDestinations('trendingContainer', (location) => {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.value = location;
        handleSearch(location);
    }
});

const stats = new TravelStats('travelStatsContainer');

const _origOpenPanel = window.openPanel;
window.openPanel = function (name) {
    _origOpenPanel && _origOpenPanel(name);
    if (name === 'search')  trending.load();
    if (name === 'profile') stats.load();
    if (name === 'create')  budgetInsightCreate.hide();
};
</script>
@endpush
