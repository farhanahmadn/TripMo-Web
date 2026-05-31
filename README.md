# TripMo - Platform Dokumentasi Perjalanan

Platform berbasis web dan mobile untuk mendokumentasikan pengalaman perjalanan pasca-liburan dan membagikannya kepada komunitas.

## Tentang Aplikasi

TripMo memudahkan pengguna merekam rekam jejak perjalanan yang sudah selesai dalam satu formulir terintegrasi, mencakup foto, cerita, rute destinasi, dan estimasi budget. Konten yang dibuat bisa dilihat oleh pengguna lain sebagai referensi sebelum berwisata.

## Fitur Utama

- **Autentikasi**: Register, login, dan logout dengan tampilan dark mode
- **Buat Postingan**: Upload foto, tulis cerita, tambah rute destinasi bertahap, dan input total budget dalam satu halaman
- **Peta Interaktif**: Visualisasi rute perjalanan menggunakan OpenStreetMap dan Leaflet.js
- **Detail Postingan**: Slider foto, tampilan rute bernomor, dan peta dengan polyline antar destinasi
- **Edit & Hapus Postingan**: Pengelolaan konten milik sendiri dengan konfirmasi sebelum hapus
- **Pencarian**: Temukan postingan berdasarkan nama lokasi atau destinasi
- **Rating**: Beri penilaian bintang (1-5) pada postingan milik pengguna lain
- **Profil**: Tampilkan jejak perjalanan dan bagikan profil sebagai portofolio wisata

## Tech Stack

- **Backend**: PHP 8.2, Laravel
- **Frontend**: Blade Templates, CSS, JavaScript
- **Mobile**: Flutter (Dart)
- **Database**: MySQL
- **Peta**: Leaflet.js + OpenStreetMap 

## Instalasi

### Prasyarat

- PHP 8.2 atau lebih tinggi
- Composer
- MySQL

### Langkah-langkah

1. **Clone repositori**
```bash
   git clone https://github.com/Wrrynn/TRON.git
   cd TRON
```

2. **Install dependensi**
```bash
   composer install
```

3. **Konfigurasi environment**
```bash
   cp .env.example .env
```

   Edit file `.env`:
```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=tron
   DB_USERNAME=root
   DB_PASSWORD=
```

4. **Generate application key**
```bash
   php artisan key:generate
```

5. **Jalankan migrasi**
```bash
   php artisan migrate
```

6. **Jalankan server**
```bash
   php artisan serve
```

   Akses aplikasi di `http://localhost:8000`

## Anggota Kelompok 5

| Nama | NIM |
|---|---|
| Nabila Rosdika Azhara | 103012300010 |
| I Made Dwi Wiryawan Raditya | 103012300142 |
| M Reyenno Rakhazhillan S | 103012300326 |
| Farhan Ahmad Naufal | 103012300311 |
| Ghaisani Zhafarina | 103012300379 |
