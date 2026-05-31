/**
 * TripMo Smart Systems — smart-systems.js
 *
 * Empat kelas sistem cerdas:
 *   1. BudgetInsight        — estimasi biaya berdasarkan data komunitas
 *   2. TrendingDestinations — chip lokasi trending 30 hari terakhir
 *   3. SimilarTrips         — postingan serupa di halaman detail
 *   4. TravelStats          — statistik perjalanan pribadi di profil
 */

'use strict';

/* ─────────────────────────────────────────────────────────────
   UTILS
   ───────────────────────────────────────────────────────────── */

function escHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

function escAttr(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#x27;');
}

function formatRp(amount) {
    const n = parseInt(amount) || 0;
    if (n >= 1_000_000_000) return 'Rp ' + (n / 1_000_000_000).toFixed(1).replace('.0', '') + ' M';
    if (n >= 1_000_000)     return 'Rp ' + (n / 1_000_000).toFixed(1).replace('.0', '') + ' jt';
    if (n >= 1_000)         return 'Rp ' + Math.round(n / 1_000) + ' rb';
    return 'Rp ' + n.toLocaleString('id-ID');
}

function debounce(fn, ms) {
    let t;
    return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
}

function countUp(el, target, formatFn, duration = 900, delay = 0) {
    setTimeout(() => {
        const start = performance.now();
        const tick  = (now) => {
            const p    = Math.min((now - start) / duration, 1);
            const ease = 1 - Math.pow(1 - p, 3);
            el.textContent = formatFn(Math.round(target * ease));
            if (p < 1) requestAnimationFrame(tick);
        };
        requestAnimationFrame(tick);
    }, delay);
}

/* ─────────────────────────────────────────────────────────────
   1. BUDGET INSIGHT
   ───────────────────────────────────────────────────────────── */

class BudgetInsight {
    constructor(containerId) {
        this.el      = document.getElementById(containerId);
        this.current = null;
        this.cache   = new Map();
        this._fetch  = debounce(this._doFetch.bind(this), 480);
    }

    trigger(location) {
        const q = (location || '').trim();
        if (q.length < 2) { this.hide(); return; }
        if (q === this.current) return;
        this._fetch(q);
    }

    async _doFetch(q) {
        if (this.cache.has(q)) {
            const cached = this.cache.get(q);
            cached.trips > 0 ? this._render(cached) : this.hide();
            return;
        }

        this._showLoading();

        try {
            const res  = await fetch(`/smart/budget-insight?q=${encodeURIComponent(q)}`);
            const data = await res.json();
            this.cache.set(q, data);
            this.current = q;

            if (data.trips > 0) {
                this._render(data);
            } else {
                this.hide();
            }
        } catch {
            this.hide();
        }
    }

    _render(d) {
        if (!this.el) return;

        const range  = d.max_budget - d.min_budget || 1;
        const avgPct = Math.min(100, Math.max(5,
                           Math.round(((d.avg_budget - d.min_budget) / range) * 100)));

        this.el.innerHTML = `
            <div class="bi-card">
                <div class="bi-header">
                    <i class="ti ti-bulb" aria-hidden="true"></i>
                    Budget referensi untuk <strong>${escHtml(d.location)}</strong>
                    <span class="bi-count">${d.trips} trip</span>
                </div>
                <div class="bi-main">
                    <div class="bi-avg">
                        <div class="bi-avg-label">Rata-rata</div>
                        <div class="bi-avg-val" id="bi-avg-num">Rp 0</div>
                    </div>
                    <div class="bi-range-wrap">
                        <div class="bi-range-bar">
                            <div class="bi-range-fill" style="width:${avgPct}%"></div>
                            <div class="bi-range-marker" style="left:${avgPct}%"
                                 title="Rata-rata: ${escAttr(formatRp(d.avg_budget))}"></div>
                        </div>
                        <div class="bi-range-labels">
                            <span>${escHtml(formatRp(d.min_budget))}</span>
                            <span>${escHtml(formatRp(d.max_budget))}</span>
                        </div>
                    </div>
                </div>
            </div>`;

        this.el.style.display = 'block';

        const avgEl = document.getElementById('bi-avg-num');
        if (avgEl) countUp(avgEl, d.avg_budget, v => formatRp(v), 700);
    }

    _showLoading() {
        if (!this.el) return;
        this.el.style.display = 'block';
        this.el.innerHTML = `
            <div class="bi-card bi-loading">
                <i class="ti ti-loader-2 ti-spin" aria-hidden="true"></i>
                Mencari referensi budget...
            </div>`;
    }

    hide() {
        if (!this.el) return;
        this.el.style.display = 'none';
        this.el.innerHTML     = '';
        this.current          = null;
    }
}

/* ─────────────────────────────────────────────────────────────
   2. TRENDING DESTINATIONS
   ───────────────────────────────────────────────────────────── */

class TrendingDestinations {
    constructor(containerId, onSelect) {
        this.el       = document.getElementById(containerId);
        this.onSelect = onSelect || (() => {});
        this.loaded   = false;
    }

    async load() {
        if (this.loaded || !this.el) return;
        try {
            const res  = await fetch('/smart/trending');
            const json = await res.json();
            if (json.success && json.data.length > 0) {
                this._render(json.data);
            }
        } catch { /* fail silently */ }
        this.loaded = true;
    }

    _render(destinations) {
        const chips = destinations.map(d => {
            const ratingBadge = d.avg_rating
                ? `<span style="color:#fbbf24;font-size:10px">★ ${d.avg_rating}</span>`
                : '';
            return `
                <button class="trend-chip" data-loc="${escAttr(d.location)}">
                    ${escHtml(d.location)}
                    <span class="trend-chip-count">${d.count}</span>
                    ${ratingBadge}
                </button>`;
        }).join('');

        this.el.innerHTML = `
            <div class="trend-wrap">
                <div class="trend-header">
                    <i class="ti ti-flame" aria-hidden="true"></i>
                    Trending 30 hari ini
                </div>
                <div class="trend-chips">${chips}</div>
            </div>`;

        // Event listener via data attribute — menghindari inline onclick
        this.el.querySelectorAll('.trend-chip').forEach(btn => {
            btn.addEventListener('click', () => this._select(btn.dataset.loc));
        });
    }

    _select(location) {
        this.el.querySelectorAll('.trend-chip').forEach(c => {
            const active = c.dataset.loc === location;
            c.style.background  = active ? 'rgba(124,92,252,0.2)' : '';
            c.style.borderColor = active ? 'rgba(124,92,252,0.5)' : '';
            c.style.color       = active ? 'white' : '';
        });
        this.onSelect(location);
    }
}

/* ─────────────────────────────────────────────────────────────
   3. SIMILAR TRIPS
   ───────────────────────────────────────────────────────────── */

class SimilarTrips {
    constructor(containerId, postId) {
        this.el     = document.getElementById(containerId);
        this.postId = postId;
    }

    async load() {
        if (!this.el || !this.postId) return;

        this.el.innerHTML = `
            <div class="st-loading">
                <i class="ti ti-loader-2 ti-spin" aria-hidden="true"></i>
                Mencari perjalanan serupa...
            </div>`;

        try {
            const res  = await fetch(`/smart/similar/${this.postId}`);
            const json = await res.json();
            if (json.success && json.data.length > 0) {
                this._render(json.data);
            } else {
                this.el.innerHTML = '';
            }
        } catch {
            this.el.innerHTML = '';
        }
    }

    _render(trips) {
        const cards = trips.map(t => `
            <a href="${escAttr(t.url)}" class="st-card" style="opacity:0">
                <div class="st-thumb">
                    ${t.thumbnail
                        ? `<img src="${escAttr(t.thumbnail)}" alt="${escAttr(t.title)}" loading="lazy">`
                        : `<i class="ti ti-map-pin" aria-hidden="true"></i>`}
                    ${t.avg_rating > 0
                        ? `<div class="st-badge">
                               <i class="ti ti-star-filled" style="font-size:10px" aria-hidden="true"></i>
                               ${t.avg_rating}
                           </div>`
                        : ''}
                </div>
                <div class="st-info">
                    <div class="st-title">${escHtml(t.title)}</div>
                    <div class="st-author">${escHtml(t.author)}${t.travel_date ? ' · ' + escHtml(t.travel_date) : ''}</div>
                </div>
            </a>`).join('');

        this.el.innerHTML = `
            <div class="st-section">
                <div class="st-section-header">
                    <i class="ti ti-route" aria-hidden="true"></i>
                    Perjalanan serupa
                </div>
                <div class="st-grid">${cards}</div>
            </div>`;

        requestAnimationFrame(() => {
            this.el.querySelectorAll('.st-card').forEach((el, i) => {
                el.style.animationDelay = `${i * 65}ms`;
                el.classList.add('st-card-appear');
            });
        });
    }
}

/* ─────────────────────────────────────────────────────────────
   4. TRAVEL STATS
   ───────────────────────────────────────────────────────────── */

class TravelStats {
    constructor(containerId) {
        this.el     = document.getElementById(containerId);
        this.loaded = false;
    }

    async load() {
        if (this.loaded || !this.el) return;

        this._showSkeleton();

        try {
            const res  = await fetch('/smart/stats');
            const json = await res.json();
            if (json.success) {
                this._render(json.data);
            }
        } catch {
            this.el.innerHTML = '';
        }
        this.loaded = true;
    }

    _render(d) {
        const metrics = [
            { icon: 'ti-plane-departure', label: 'Total trip',      value: d.total_trips,   fmt: 'count' },
            { icon: 'ti-map-pin',         label: 'Kota dikunjungi', value: d.unique_cities, fmt: 'count' },
            { icon: 'ti-wallet',          label: 'Total budget',    value: d.total_spent,   fmt: 'rp'    },
            { icon: 'ti-receipt',         label: 'Rata-rata/trip',  value: d.avg_per_trip,  fmt: 'rp'    },
        ];

        const cards = metrics.map((m, i) => `
            <div class="ts-card">
                <i class="ti ${escAttr(m.icon)} ts-icon" aria-hidden="true"></i>
                <div class="ts-val"
                     data-target="${m.value}"
                     data-fmt="${m.fmt}"
                     data-delay="${i * 100}">
                    ${m.fmt === 'rp' ? formatRp(0) : '0'}
                </div>
                <div class="ts-label">${escHtml(m.label)}</div>
            </div>`).join('');

        const favHtml = d.fav_destination ? `
            <div class="ts-fav">
                <i class="ti ti-heart ts-fav-icon" aria-hidden="true"></i>
                <div>
                    <div class="ts-fav-label">Destinasi favorit</div>
                    <div class="ts-fav-dest">${escHtml(d.fav_destination)}</div>
                </div>
            </div>` : '';

        this.el.innerHTML = `
            <div class="ts-section">
                <div class="ts-section-label">Statistik perjalananmu</div>
                <div class="ts-grid">
                    ${cards}
                    ${favHtml}
                </div>
            </div>`;

        this.el.querySelectorAll('.ts-val').forEach(el => {
            const target = parseInt(el.dataset.target) || 0;
            const fmt    = el.dataset.fmt;
            const delay  = parseInt(el.dataset.delay) || 0;
            if (target === 0) return;

            const formatFn = fmt === 'rp'
                ? v => formatRp(v)
                : v => v.toLocaleString('id-ID');

            countUp(el, target, formatFn, 900, delay);
        });
    }

    _showSkeleton() {
        const skels = Array.from({ length: 4 }, () => `
            <div class="ts-card ts-skeleton">
                <i class="ti ti-circle ts-icon" aria-hidden="true"></i>
                <div class="ts-val">loading</div>
                <div class="ts-label">loading stat</div>
            </div>`).join('');

        this.el.innerHTML = `
            <div class="ts-section">
                <div class="ts-section-label">Statistik perjalananmu</div>
                <div class="ts-grid">${skels}</div>
            </div>`;
    }
}

/* ─────────────────────────────────────────────────────────────
   EXPORT — dipakai oleh blade views
   ───────────────────────────────────────────────────────────── */

window.TripMoSmart = { BudgetInsight, TrendingDestinations, SimilarTrips, TravelStats, formatRp };
