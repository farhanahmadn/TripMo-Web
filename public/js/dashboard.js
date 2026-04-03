// ── PETA ──
const map = L.map('main-map', { center: [-6.9, 107.6], zoom: 13 });

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://openstreetmap.org">OpenStreetMap</a>',
    maxZoom: 19,
}).addTo(map);

const pinIcon = L.divIcon({
    html: `<svg width="28" height="36" viewBox="0 0 28 36" xmlns="http://www.w3.org/2000/svg">
        <path d="M14 0C6.268 0 0 6.268 0 14c0 9.333 14 22 14 22S28 23.333 28 14C28 6.268 21.732 0 14 0z" fill="#e84040"/>
        <circle cx="14" cy="14" r="6" fill="white"/>
    </svg>`,
    iconSize: [28, 36], iconAnchor: [14, 36], popupAnchor: [0, -38], className: '',
});

let activeMarker = null;
const coordBadge = document.getElementById('coordBadge');

map.on('click', function(e) {
    const lat = e.latlng.lat.toFixed(5);
    const lng = e.latlng.lng.toFixed(5);
    if (activeMarker) map.removeLayer(activeMarker);
    activeMarker = L.marker(e.latlng, { icon: pinIcon })
        .addTo(map)
        .bindPopup(`<div style="text-align:center;padding:4px"><strong>📍 Lokasi Dipilih</strong><br><small>${lat}, ${lng}</small></div>`)
        .openPopup();
    coordBadge.textContent = '📍 ' + lat + ', ' + lng;
});

map.on('mousemove', e => {
    if (!activeMarker)
        coordBadge.textContent = '🖱 ' + e.latlng.lat.toFixed(4) + ', ' + e.latlng.lng.toFixed(4);
});

map.on('mouseout', () => {
    if (!activeMarker) coordBadge.textContent = 'Klik peta untuk tandai lokasi';
});

// ── PANEL ──
let currentPanel = null;

const panels = {
    profile: document.getElementById('panelProfile'),
    search:  document.getElementById('panelSearch'),
    people:  document.getElementById('panelPeople'),
};

const btns = {
    profile: document.getElementById('btnProfile'),
    search:  document.getElementById('btnSearch'),
    people:  document.getElementById('btnPeople'),
};

function openPanel(name) {
    if (currentPanel === name) { closePanel(); return; }
    Object.values(panels).forEach(p => p && (p.style.display = 'none'));
    Object.values(btns).forEach(b => b && b.classList.remove('active'));
    if (panels[name]) panels[name].style.display = 'block';
    if (btns[name]) btns[name].classList.add('active');
    document.getElementById('sidePanel').classList.add('open');
    currentPanel = name;
    setTimeout(() => map.invalidateSize(), 300);
}

function closePanel() {
    document.getElementById('sidePanel').classList.remove('open');
    Object.values(panels).forEach(p => p && (p.style.display = 'none'));
    Object.values(btns).forEach(b => b && b.classList.remove('active'));
    currentPanel = null;
    setTimeout(() => map.invalidateSize(), 300);
}

// Search tabs
document.querySelectorAll('.search-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.search-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
    });
});

document.getElementById('btnPin').addEventListener('click', function() {
    this.classList.toggle('active');
});
