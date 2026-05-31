@extends('layouts.app')
@section('title', 'Edit ' . $post->title)

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/post-edit.css') }}">
@endpush

@section('content')
<div class="edit-wrap">
    <div class="edit-left">
        <div class="form-section">
            <h2>Edit Postingan</h2>

            <form action="{{ route('post.update', $post->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="title">Judul *</label>
                    <input type="text" id="title" name="title" value="{{ old('title', $post->title) }}" required maxlength="200">
                    @error('title')
                        <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="location">Lokasi *</label>
                    <input type="text" id="location" name="location" value="{{ old('location', $post->location) }}" required maxlength="200">
                    @error('location')
                        <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="travel_date">Tanggal Perjalanan</label>
                    <input type="date" id="travel_date" name="travel_date" value="{{ old('travel_date', $post->travel_date) }}">
                    @error('travel_date')
                        <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>

                @php
                    $dests = $post->destinations
                        ? (is_array($post->destinations) ? $post->destinations : json_decode($post->destinations, true))
                        : [];
                @endphp

                <div class="form-group">
                    <label>Rute Destinasi</label>
                    <div style="font-size:11px; color:rgba(255,255,255,.3); margin-bottom:8px">Tambah lokasi satu per satu (ketik nama, pilih saran, atau klik peta)</div>

                    <div id="destSaran" style="display:none; background:rgba(20,20,30,.98); border:1px solid rgba(255,255,255,.1); border-radius:8px; overflow:hidden; position:absolute; z-index:9999; width:300px; max-height:200px; overflow-y:auto;"></div>

                    <div id="destList">
                        @foreach($dests as $i => $dest)
                            @php
                                $destName = is_array($dest) ? ($dest['name'] ?? '') : $dest;
                                $destLat = is_array($dest) ? ($dest['lat'] ?? '') : '';
                                $destLng = is_array($dest) ? ($dest['lng'] ?? '') : '';
                            @endphp
                            <div class="dest-item" data-index="{{ $i }}" data-lat="{{ $destLat }}" data-lng="{{ $destLng }}">
                                <span class="dest-num">{{ $i + 1 }}</span>
                                <input type="text" name="destinations[{{ $i }}][name]" value="{{ $destName }}" placeholder="Nama destinasi" class="dest-name-input" data-lat="{{ $destLat }}" data-lng="{{ $destLng }}">
                                <input type="hidden" name="destinations[{{ $i }}][lat]" value="{{ $destLat }}" class="dest-lat">
                                <input type="hidden" name="destinations[{{ $i }}][lng]" value="{{ $destLng }}" class="dest-lng">
                                <button type="button" class="btn-remove-dest" onclick="removeDest(this)">×</button>
                            </div>
                        @endforeach
                    </div>

                    <div style="display:flex; gap:6px; margin-top:8px">
                        <input type="text" id="editDestInput" class="p-input" placeholder=" Cari lokasi..." style="flex:1">
                        <button type="button" onclick="addFromInput()" class="p-add-btn">+</button>
                    </div>

                    @error('destinations')
                        <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="story">Cerita Perjalanan</label>
                    <textarea id="story" name="story" rows="6">{{ old('story', $post->story) }}</textarea>
                    @error('story')
                        <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="total_budget">Total Budget (Rp)</label>
                    <input type="number" id="total_budget" name="total_budget" value="{{ old('total_budget', $post->total_budget) }}" min="0">
                    @error('total_budget')
                        <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label>Foto saat ini</label>
                    <div class="current-photos">
                        @foreach($post->photos as $photo)
                            <div class="current-photo-item">
                                <img src="{{ Storage::url($photo->file_path) }}" alt="Foto">
                                <label>
                                    <input type="checkbox" name="delete_photos[]" value="{{ $photo->id }}"> Hapus
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="form-group">
                    <label for="photos">Tambah Foto Baru (multiple)</label>
                    <input type="file" id="photos" name="photos[]" multiple accept="image/*">
                    @error('photos')
                        <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-actions">
                    <a href="{{ route('post.show', $post->id) }}" class="btn-cancel">Batal</a>
                    <button type="submit" class="btn-save">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="edit-right">
        <div id="edit-map" style="width:100%;height:420px;"></div>
        <p class="map-hint">Klik peta untuk tambah destinasi, atau ketik di formulir</p>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Global functions needed by inline onclick handlers
function removeDest(btn) {
    const item = btn.closest('.dest-item');
    const index = Array.from(document.getElementById('destList').children).indexOf(item);
    item.remove();
    // Remove corresponding marker
    if (window.editMarkers[index]) {
        window.editMap.removeLayer(window.editMarkers[index]);
        window.editMarkers.splice(index, 1);
    }
    reindexDests();
    updatePolyline();
}

function addFromInput() {
    const input = document.getElementById('editDestInput');
    const val = input.value.trim();
    if (!val) return;
    // Geocode
    fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(val)}&format=json&limit=1&countrycodes=id`)
        .then(r => r.json())
        .then(data => {
            if (data.length === 0) { alert('Lokasi tidak ditemukan'); return; }
            const d = data[0];
            addDest(d.display_name.split(',')[0], parseFloat(d.lat), parseFloat(d.lon));
            input.value = '';
        });
}

function addDest(name, lat, lng) {
    const list = document.getElementById('destList');
    const idx = list.children.length;
    // Escape double quotes for HTML attribute
    const escapedName = name.replace(/"/g, '&quot;');
    const div = document.createElement('div');
    div.className = 'dest-item';
    div.dataset.lat = lat;
    div.dataset.lng = lng;
    div.innerHTML = `
        <span class="dest-num">${idx + 1}</span>
        <input type="text" name="destinations[${idx}][name]" value="${escapedName}" class="dest-name-input" data-lat="${lat}" data-lng="${lng}">
        <input type="hidden" name="destinations[${idx}][lat]" value="${lat}" class="dest-lat">
        <input type="hidden" name="destinations[${idx}][lng]" value="${lng}" class="dest-lng">
        <button type="button" class="btn-remove-dest" onclick="removeDest(this)">×</button>
    `;
    list.appendChild(div);
    // Add marker
    const icon = L.divIcon({
        html: `<div style="width:28px;height:28px;background:#7c5cfc;border:2px solid white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:white">${idx + 1}</div>`,
        iconSize: [28,28], iconAnchor: [14,14], className: ''
    });
    const marker = L.marker([lat, lng], {icon}).addTo(window.editMap).bindPopup('<b>'+name+'</b>');
    window.editMarkers.push(marker);
    updatePolyline();
    window.editMap.setView([lat, lng], 13);
}

function reindexDests() {
    const list = document.getElementById('destList');
    Array.from(list.children).forEach((item, idx) => {
        item.dataset.index = idx;
        item.querySelector('.dest-num').textContent = idx + 1;
        item.querySelectorAll('input').forEach(inp => {
            const n = inp.getAttribute('name');
            if (n) inp.setAttribute('name', n.replace(/\[\d+\]/, '[' + idx + ']'));
        });
    });
    // Renumber markers
    window.editMarkers.forEach((m, i) => {
        m.setIcon(L.divIcon({
            html: `<div style="width:28px;height:28px;background:#7c5cfc;border:2px solid white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:white">${i + 1}</div>`,
            iconSize: [28,28], iconAnchor: [14,14], className: ''
        }));
    });
}

function updatePolyline() {
    window.editLines.forEach(l => window.editMap.removeLayer(l));
    window.editLines.length = 0;
    const coords = window.editMarkers.map(m => m.getLatLng());
    if (coords.length > 1) {
        const pl = L.polyline(coords, {color:'#7c5cfc', weight:3, dashArray:'8 4'}).addTo(window.editMap);
        window.editLines.push(pl);
        window.editMap.fitBounds(coords, {padding:[40,40]});
    }
}

// Autocomplete for destination input
let timer = null;
document.getElementById('editDestInput').addEventListener('input', function() {
    clearTimeout(timer);
    const q = this.value.trim();
    if (q.length < 3) { document.getElementById('destSaran').style.display = 'none'; return; }
    timer = setTimeout(() => {
        fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(q)}&format=json&limit=4&countrycodes=id`)
            .then(r => r.json())
            .then(data => {
                const box = document.getElementById('destSaran');
                if (!data.length) { box.style.display = 'none'; return; }
                box.innerHTML = data.map(d => {
                    const n = d.display_name.split(',')[0];
                    return `<div class="dest-saran" onclick="pilihDest('${n.replace(/'/g,"\\'")}',${d.lat},${d.lon})">📍 ${n}</div>`;
                }).join('');
                box.style.display = 'block';
            });
    }, 400);
});

function pilihDest(name, lat, lng) {
    document.getElementById('destSaran').style.display = 'none';
    document.getElementById('editDestInput').value = '';
    addDest(name, lat, lng);
}

document.getElementById('editDestInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        addFromInput();
    }
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('.dest-item') && !e.target.closest('#destSaran')) {
        document.getElementById('destSaran').style.display = 'none';
    }
});

// Initialize map
if (document.getElementById('edit-map')) {
    window.editMap = L.map('edit-map');
    window.editMarkers = [];
    window.editLines = [];

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap', maxZoom: 19
    }).addTo(window.editMap);

    // Map click adds destination
    window.editMap.on('click', function(e) {
        const {lat, lng} = e.latlng;
        addDest('Lokasi baru', lat, lng);
    });

    // Load existing destinations
    @foreach($dests as $i => $dest)
        @php
            $destName = is_array($dest) ? ($dest['name'] ?? '') : $dest;
            $destLat = is_array($dest) ? ($dest['lat'] ?? '') : '';
            $destLng = is_array($dest) ? ($dest['lng'] ?? '') : '';
        @endphp
        @if($destLat && $destLng)
            (function() {
                const lat = parseFloat({{ $destLat }}), lng = parseFloat({{ $destLng }});
                if (!isNaN(lat) && !isNaN(lng)) {
                    const icon = L.divIcon({
                        html: `<div style="width:28px;height:28px;background:#7c5cfc;border:2px solid white;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:white">{{ $i + 1 }}</div>`,
                        iconSize: [28,28], iconAnchor: [14,14], className: ''
                    });
                    const popupContent = {!! json_encode('<b>'.$destName.'</b>') !!};
                    const marker = L.marker([lat, lng], {icon}).addTo(window.editMap).bindPopup(popupContent);
                    window.editMarkers.push(marker);
                    if (window.editMarkers.length === 1) window.editMap.setView([lat, lng], 13);
                }
            })();
        @endif
    @endforeach

    updatePolyline();
} else {
    console.error('Map container not found');
}

</script>
@endpush