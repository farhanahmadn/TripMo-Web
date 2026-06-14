# 🗺️ Tripmo — Travel Journey Sharing Platform

**Tripmo** adalah aplikasi web modern untuk berbagi cerita perjalanan, destinasi wisata, dan pengalaman traveling dengan fitur peta interaktif, rating perjalanan, dan insights budget perjalanan.

---

## 📋 Daftar Isi

1. [Fitur Utama](#fitur-utama)
2. [Tech Stack](#tech-stack)
3. [Instalasi](#instalasi)
4. [Konfigurasi Database](#konfigurasi-database)
5. [Running Aplikasi](#running-aplikasi)
6. [Struktur Database](#struktur-database)
7. [Troubleshooting](#troubleshooting)

---

## ✨ Fitur Utama

- **📍 Dashboard dengan Map** — Explore destinasi dan rute perjalanan secara realtime
- **🎒 Post Perjalanan** — Buat postingan dengan foto, cerita, dan destinasi
- **🗺️ Interactive Map** — Lihat rute jalan sebenarnya dengan routing OSRM
- **⭐ Rating System** — Beri rating untuk perjalanan yang menarik
- **💰 Budget Insights** — Estimasi budget berdasarkan lokasi
- **👤 User Profiles** — Kelola profil dan lihat jejak perjalanan
- **🔍 Search & Trending** — Cari perjalanan dan lihat destinasi trending

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|-----------|
| **Backend** | Laravel 11, PHP 8.0+ |
| **Frontend** | Blade Templates, Tailwind CSS, Leaflet.js |
| **Database** | MySQL (Aiven Cloud) |
| **Map** | Leaflet, Stadia Maps (Dark Mode), OSRM Routing |
| **Storage** | Local + Cloud (Storage symlink) |
| **Auth** | Laravel Breeze, Session-based |

---

## 🚀 Instalasi

### Step 1: Clone Repository
```bash
git clone <repo-url>
cd Tripmo
```

### Step 2: Install Dependencies
```bash
composer install
npm install
```

### Step 3: Setup Environment File
```bash
# Copy .env.example ke .env (file akan langsung terisi dengan config Aiven)
cp .env.example .env

# Generate APP_KEY jika belum ada
php artisan key:generate
```

### Step 4: Run Migrations
```bash
# Jalankan semua migration ke database Aiven
php artisan migrate --force
```

### Step 5: Setup Storage Symlink
```bash
# Buat symlink agar foto bisa diakses via public/storage
php artisan storage:link
```

### Step 6: Build Assets (Optional)
```bash
npm run build
```

---

## 🔌 Konfigurasi Database

### 🌐 Production (Aiven Cloud MySQL)
**Sudah dikonfigurasi otomatis** di `.env.example`

```env
DB_CONNECTION=mysql
DB_HOST=mysql-3958c3e0-nabilarosdika97-3270.c.aivencloud.com
DB_PORT=23819
DB_DATABASE=defaultdb
DB_USERNAME=avnadmin
DB_PASSWORD=your_aiven_password
MYSQL_ATTR_SSL_CA=false
```

✅ **Kelebihan:**
- Database online, accessible dari mana saja
- Automatic backups dari Aiven
- SSL encryption untuk keamanan data
- Tidak perlu setup server lokal

### 💻 Development (Local XAMPP MySQL)
Jika ingin menggunakan XAMPP lokal, edit `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=
```

Lalu jalankan:
```bash
php artisan migrate
```

---

## ▶️ Running Aplikasi

### Development Server
```bash
php artisan serve
```
Akses di: **http://localhost:8000**

### Watch Frontend Changes (Optional)
```bash
npm run dev
```

### Default Login
```
Email: aya@gmail.com
Password: (sesuai password yang di-hash di DB)
```

---

## 📊 Struktur Database

### Tabel Utama

| Tabel | Deskripsi |
|-------|-----------|
| `users` | User account dengan bio & photo |
| `travel_posts` | Postingan perjalanan |
| `post_photos` | Foto untuk setiap postingan |
| `ratings` | Rating & review perjalanan |
| `sessions` | Session management |
| `cache` | Cache storage |
| `jobs` | Queue job storage |

### Kolom Penting

**users**
```sql
id | name | email | password | bio | photo | created_at | updated_at
```

**travel_posts**
```sql
id | user_id | title | location | story | destinations (JSON) | total_budget | travel_date | created_at | updated_at
```

**post_photos**
```sql
id | travel_post_id | file_path | created_at | updated_at
```

**ratings**
```sql
id | user_id | travel_post_id | score (1-5) | unique(user_id, travel_post_id)
```

---

## 🆘 Troubleshooting

### ❌ Error: "No connection could be made to Aiven"

**Solusi:**
1. Cek koneksi internet
2. Verifikasi credentials di `.env` sudah benar
3. Pastikan IP Anda tidak diblokir Aiven (check Aiven console)
4. Cek firewall tidak memblokir port 23819

```bash
# Test koneksi
php artisan db:show
```

### ❌ Error: "SQLSTATE[HY000]: Connection refused"

**Jika pakai XAMPP lokal:**
1. Pastikan MySQL/MariaDB running
```bash
# Windows - start MySQL
net start mysql
# atau jalankan XAMPP Control Panel → Start MySQL
```

### ❌ Error: "File not found" saat buka foto
```bash
# Perbaiki storage symlink
php artisan storage:link
```

### ❌ Migrasi gagal
```bash
# Cek migration status
php artisan migrate:status

# Refresh semua migration (HATI-HATI: hapus semua data)
php artisan migrate:refresh --force

# Rollback migration terakhir
php artisan migrate:rollback
```

### ❌ Aplikasi lambat saat membuka map
**Penyebab:** OSRM routing timeout untuk rute kompleks (pendakian)

**Solusi:** Aplikasi otomatis fallback ke garis lurus dengan badge "Jalur estimasi"

---

## 📝 Environment Variables Penting

| Variable | Deskripsi | Default |
|----------|-----------|---------|
| `APP_ENV` | Environment (local/production) | local |
| `APP_DEBUG` | Debug mode | true |
| `DB_CONNECTION` | Database driver | mysql |
| `SESSION_DRIVER` | Session storage | database |
| `FILESYSTEM_DISK` | File storage | local |

---

## 🔐 Security Tips

1. **Jangan share `.env` ke public** — Contains database credentials
2. **Gunakan `.env.local` untuk local overrides** — Git ignored
3. **Reset APP_KEY jika deploy baru** — `php artisan key:generate`
4. **Check Aiven firewall settings** — Whitelist IP Anda

---

## 📞 Support

Jika ada pertanyaan atau bug, hubungi tim developer atau buat issue di repository, Thankyouu

---

**Happy traveling! 🚀✈️🗺️**
