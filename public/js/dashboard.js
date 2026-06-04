// PETA
const map = L.map('main-map', {
    center: [-6.9, 107.6], zoom: 13,
    zoomControl: false
});

// Tile CartoDB Dark Matter — gratis tanpa API key.
// className 'tiles-legible' diberi filter CSS agar jalan & latar lebih jelas
// (mirip Mapbox/Google Maps dark) tanpa terlalu pekat.
L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> © <a href="https://carto.com/">CARTO</a>',
    subdomains: 'abcd',
    maxZoom: 20,
    className: 'tiles-legible'
}).addTo(map);

// Zoom control di kiri bawah
L.control.zoom({ position: 'topright' }).addTo(map);

// Klik peta — pin modern
let pinAktif = null;
const pinIkon = L.divIcon({
    html: `<div style="
        position:relative;width:20px;height:20px;
        background:#7c5cfc;border:3px solid white;border-radius:50%;
        box-shadow:0 0 0 4px rgba(124,92,252,.3),0 4px 12px rgba(0,0,0,.5)">
    </div>`,
    iconSize: [20,20], iconAnchor: [10,10], popupAnchor: [0,-14], className: ''
});

map.on('click', function(e) {
    if (pinAktif) map.removeLayer(pinAktif);
    pinAktif = L.marker(e.latlng, { icon: pinIkon }).addTo(map);
});


// =====================
// PANEL
// =====================
let currentPanel = null;
const panels = {
    profile: document.getElementById('panelProfile'),
    search:  document.getElementById('panelSearch'),
    create:  document.getElementById('panelCreate'),
};
const btns = {
    profile: document.getElementById('btnProfile'),
    search:  document.getElementById('btnSearch'),
    create:  document.getElementById('btnCreate'),
};

async function loadFeatured() {
    const results = document.getElementById('searchResults');
    results.innerHTML = `<div style="font-size:11px;color:rgba(255,255,255,.3);margin-bottom:12px">⭐ Jejak Terpopuler</div>`;

    try {
        const res  = await fetch('/featured');
        const data = await res.json();

        if (!data.length) {
            results.innerHTML += `<div style="text-align:center;padding:20px 0;color:rgba(255,255,255,.3);font-size:13px">Belum ada jejak.</div>`;
            return;
        }

        data.forEach(p => {
            results.innerHTML += `
            <a href="${p.url}" style="display:flex;gap:12px;align-items:center;
               padding:10px 12px;border-radius:10px;margin-bottom:6px;
               background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06);
               text-decoration:none;transition:background .15s"
               onmouseover="this.style.background='rgba(124,92,252,.15)'"
               onmouseout="this.style.background='rgba(255,255,255,.04)'">
                <div style="width:48px;height:48px;border-radius:8px;overflow:hidden;
                            flex-shrink:0;background:rgba(255,255,255,.06)">
                    ${p.photo
                        ? `<img src="${p.photo}" style="width:100%;height:100%;object-fit:cover">`
                        : `<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;font-size:18px">📍</div>`
                    }
                </div>
                <div style="flex:1;min-width:0">
                    <div style="font-size:13px;font-weight:600;color:white;
                                white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                        ${escHtml(p.title)}
                    </div>
                    <div style="font-size:11px;color:rgba(255,255,255,.4);margin-top:2px">
                        ${escHtml(p.location)} · oleh ${escHtml(p.author)}
                        ${p.rating ? ' · ⭐ ' + p.rating : ''}
                    </div>
                </div>
            </a>`;
        });
    } catch(e) {
        results.innerHTML += `<div style="color:rgba(255,100,100,.5);font-size:13px">Gagal memuat.</div>`;
    }
}

function openPanel(name) {
    if (currentPanel === name) { closePanel(); return; }
    Object.values(panels).forEach(p => p && (p.style.display = 'none'));
    Object.values(btns).forEach(b => b && b.classList.remove('active'));
    if (panels[name]) panels[name].style.display = 'block';
    if (btns[name]) btns[name].classList.add('active');
    document.getElementById('sidePanel').classList.add('open');
    currentPanel = name;
    setTimeout(() => map.invalidateSize(), 300);

    if (name === 'search') {
        document.getElementById('searchInput').value = '';
        loadFeatured();
    }   
}

function closePanel() {
    document.getElementById('sidePanel').classList.remove('open');
    Object.values(panels).forEach(p => p && (p.style.display = 'none'));
    Object.values(btns).forEach(b => b && b.classList.remove('active'));
    currentPanel = null;
    setTimeout(() => map.invalidateSize(), 300);
}


// =====================
// SEARCH
// =====================
let searchTimer = null;

function handleSearch(val) {
    clearTimeout(searchTimer);
    const results = document.getElementById('searchResults');
    const spinner = document.getElementById('searchSpinner');

    if (!val || val.trim().length < 2) {
        spinner.style.display = 'none';
        results.innerHTML = `
            <div style="text-align:center; padding:40px 0; color:rgba(255,255,255,.3); font-size:13px">
                Ketik nama lokasi untuk menemukan jejak perjalanan.
            </div>`;
        return;
    }

    spinner.style.display = 'inline';

    searchTimer = setTimeout(async () => {
        try {
            const res  = await fetch(`/search?q=${encodeURIComponent(val.trim())}`);
            const data = await res.json();
            spinner.style.display = 'none';
            renderSearchResults(data, val.trim());
        } catch (e) {
            spinner.style.display = 'none';
            results.innerHTML = `
                <div style="text-align:center;padding:40px 0;color:rgba(255,100,100,.5);font-size:13px">
                    Terjadi kesalahan.
                </div>`;
        }
    }, 350);
}

function renderSearchResults(data, query) {
    const results = document.getElementById('searchResults');

    if (!data.length) {
        results.innerHTML = `
            <div style="text-align:center;padding:40px 0">
                <div style="font-size:28px;margin-bottom:8px">🔍</div>
                <div style="color:rgba(255,255,255,.3);font-size:13px">
                    Tidak ada jejak di "<strong style="color:rgba(255,255,255,.5)">${escHtml(query)}</strong>"
                </div>
            </div>`;
        return;
    }

    const total = data.reduce((a, g) => a + g.total_posts, 0);
    let html = `<div style="font-size:11px;color:rgba(255,255,255,.3);margin-bottom:12px;margin-top:4px">
        ${total} jejak ditemukan</div>`;

    data.forEach(group => {
        html += `
        <div style="margin-bottom:24px">
            <div style="display:flex;align-items:center;gap:6px;margin-bottom:8px">
                <svg viewBox="0 0 24 24" style="width:12px;height:12px;stroke:#7c5cfc;fill:none;stroke-width:2;flex-shrink:0">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/>
                    <circle cx="12" cy="9" r="2.5"/>
                </svg>
                <span style="font-size:11px;font-weight:600;color:#7c5cfc;text-transform:uppercase;letter-spacing:.5px">
                    ${escHtml(group.location)}
                </span>
                <span style="font-size:10px;color:rgba(255,255,255,.2)">${group.total_posts} jejak</span>
            </div>

            ${group.posts.map(p => `
            <a href="${p.url}" style="display:flex;gap:12px;align-items:center;
               padding:10px 12px;border-radius:10px;margin-bottom:6px;
               background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.06);
               text-decoration:none;transition:background .15s"
               onmouseover="this.style.background='rgba(124,92,252,.15)'"
               onmouseout="this.style.background='rgba(255,255,255,.04)'">
                <div style="width:48px;height:48px;border-radius:8px;overflow:hidden;
                            flex-shrink:0;background:rgba(255,255,255,.06)">
                    ${p.photo
                        ? `<img src="${p.photo}" style="width:100%;height:100%;object-fit:cover">`
                        : `<div style="width:100%;height:100%;display:flex;align-items:center;
                                       justify-content:center;font-size:18px">📍</div>`
                    }
                </div>
                <div style="flex:1;min-width:0">
                    <div style="font-size:13px;font-weight:600;color:white;
                                white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                        ${escHtml(p.title)}
                    </div>
                    <div style="font-size:11px;color:rgba(255,255,255,.4);margin-top:2px">
                        oleh ${escHtml(p.author)}${p.travel_date ? ' · ' + p.travel_date : ''}${p.rating ? ' · ⭐ ' + p.rating : ''}
                    </div>
                </div>
                <svg viewBox="0 0 24 24" style="width:14px;height:14px;stroke:rgba(255,255,255,.2);
                     fill:none;stroke-width:2;flex-shrink:0">
                    <path d="M9 18l6-6-6-6"/>
                </svg>
            </a>`).join('')}
        </div>`;
    });

    results.innerHTML = html;
}

function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}


// =====================
// DESTINASI
// =====================
let dests = [];
let ruteLayer = null;
let timer = null;

async function tambahDest() {
    const input = document.getElementById('pDestInput');
    const val = input.value.trim();
    if (!val) return;

    const res  = await fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(val)}&format=json&limit=1`);
    const data = await res.json();
    if (!data.length) { alert('Lokasi tidak ditemukan!'); return; }

    dests.push({ name: data[0].display_name.split(',')[0], lat: parseFloat(data[0].lat), lng: parseFloat(data[0].lon) });
    input.value = '';
    document.getElementById('destSaran').style.display = 'none';
    tampilRute();
    updatePeta();
}

document.getElementById('pDestInput').addEventListener('input', function() {
    clearTimeout(timer);
    const q = this.value.trim();
    if (q.length < 3) { document.getElementById('destSaran').style.display = 'none'; return; }
    timer = setTimeout(async () => {
        const res  = await fetch(`https://nominatim.openstreetmap.org/search?q=${encodeURIComponent(q)}&format=json&limit=4`);
        const data = await res.json();
        const box  = document.getElementById('destSaran');
        if (!data.length) { box.style.display = 'none'; return; }
        box.innerHTML = data.map(d =>
            `<div class="dest-saran" onclick="pilihDest('${d.display_name.split(',')[0].replace(/'/g,"\\'")}',${d.lat},${d.lon})">
                📍 ${d.display_name.split(',').slice(0,2).join(',')}
            </div>`).join('');
        box.style.display = 'block';
    }, 400);
});

function pilihDest(name, lat, lng) {
    dests.push({ name, lat: parseFloat(lat), lng: parseFloat(lng) });
    document.getElementById('pDestInput').value = '';
    document.getElementById('destSaran').style.display = 'none';
    tampilRute();
    updatePeta();
}

function hapusDest(i) {
    dests.splice(i, 1);
    tampilRute();
    updatePeta();
}

function tampilRute() {
    const list = document.getElementById('listDest');
    list.innerHTML = '';
    dests.forEach((d, i) => {
        const kelas = i === 0 ? 'start' : (i === dests.length - 1 ? 'end' : 'middle');
        const div = document.createElement('div');
        div.className = 'route-item';
        div.innerHTML = `
            <div class="route-dot ${kelas}">${i+1}</div>
            <div class="route-card">
                <div>
                    <div class="route-name">📍 ${d.name}</div>
                    <div class="route-coords">${d.lat.toFixed(4)}, ${d.lng.toFixed(4)}</div>
                </div>
                <button class="route-remove" type="button" onclick="hapusDest(${i})">×</button>
            </div>`;
        list.appendChild(div);
    });
    document.getElementById('destData').value    = JSON.stringify(dests);
    document.getElementById('lokasiUtama').value = dests.length > 0 ? dests[0].name : '';
}

function updatePeta() {
    if (ruteLayer) map.removeLayer(ruteLayer);
    map.eachLayer(l => { if (l._tripmo) map.removeLayer(l); });
    if (dests.length === 0) return;

    const titik = dests.map(d => [d.lat, d.lng]);

    // Shadow + garis utama — warna cerah agar kontras di dark tile
    if (titik.length > 1) {
        const shadow = L.polyline(titik, { color: '#818cf8', weight: 10, opacity: 0.18 }).addTo(map);
        shadow._tripmo = true;
        ruteLayer = L.polyline(titik, { color: '#818cf8', weight: 3.5, opacity: 1 }).addTo(map);
    }

    dests.forEach((d, i) => {
        const isEnd = i === 0 || i === dests.length - 1;
        const warna = isEnd ? '#a78bfa' : '#818cf8';
        const glow  = isEnd ? 'rgba(167,139,250,.5)' : 'rgba(129,140,248,.4)';
        const ikon  = L.divIcon({
            html: `<div style="
                width:28px;height:28px;background:${warna};
                border:2.5px solid rgba(255,255,255,.9);border-radius:50%;
                display:flex;align-items:center;justify-content:center;
                font-size:11px;font-weight:700;color:white;
                box-shadow:0 0 0 4px ${glow},0 4px 14px rgba(0,0,0,.6)">${i + 1}</div>`,
            iconSize: [28,28], iconAnchor: [14,14], className: ''
        });
        const m = L.marker([d.lat, d.lng], { icon: ikon }).addTo(map);
        m._tripmo = true;
    });

    if (titik.length > 1) map.fitBounds(titik, { padding: [60, 60] });
    else map.setView(titik[0], 13);
}

document.getElementById('pDestInput').addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); tambahDest(); }
});


// =====================
// PREVIEW FOTO
// =====================
function previewFoto(input) {
    const preview = document.getElementById('pFotoPreview');
    preview.innerHTML = '';
    Array.from(input.files).forEach(file => {
        const reader = new FileReader();
        reader.onload = e => {
            const div = document.createElement('div');
            div.style.cssText = 'aspect-ratio:1;border-radius:6px;overflow:hidden';
            div.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover">`;
            preview.appendChild(div);
        };
        reader.readAsDataURL(file);
    });

}
function shareProfile() {
    if (typeof profileUrl === 'undefined') {
        alert("Link profil tidak tersedia");
        return;
    }

    navigator.clipboard.writeText(profileUrl)
        .then(() => {
            alert("Link profil berhasil disalin!");
        })
        .catch(() => {
            alert("Gagal menyalin link");
        });
}