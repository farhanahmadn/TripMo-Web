# Tripmo — API untuk Flutter

Base URL produksi: **`https://tripmo-jade.vercel.app/api`**

Auth memakai **Bearer token** (Laravel Sanctum). Simpan token setelah login/register,
kirim di header `Authorization: Bearer <token>` untuk endpoint yang butuh login.

---

## Endpoint Publik (tanpa token)

| Method | Endpoint | Keterangan |
|--------|----------|-----------|
| GET  | `/api/ping` | Health check |
| POST | `/api/register` | Daftar → balikan token |
| POST | `/api/login` | Login → balikan token |
| GET  | `/api/posts` | Feed semua postingan (paginated) |
| GET  | `/api/posts/{id}` | Detail postingan |
| GET  | `/api/users/{id}` | Profil user + postingannya |

## Endpoint Butuh Token (header `Authorization: Bearer <token>`)

| Method | Endpoint | Keterangan |
|--------|----------|-----------|
| GET    | `/api/user` | Data user yang sedang login |
| POST   | `/api/logout` | Hapus token aktif |
| POST   | `/api/posts` | Buat postingan (multipart bila ada foto) |
| DELETE | `/api/posts/{id}` | Hapus postingan (pemilik) |
| POST   | `/api/posts/{id}/rate` | Beri rating (`score` 1–5) |

---

## Contoh Request

### Register
```
POST /api/register
Content-Type: application/json

{ "name": "Budi", "email": "budi@mail.com", "password": "password123" }
```
Response:
```json
{
  "message": "Registrasi berhasil",
  "user": { "id": 1, "name": "Budi", "email": "budi@mail.com", "bio": null, "photo": null },
  "token": "1|xxxxxxxxxxxxxxxxxxxx"
}
```

### Login
```
POST /api/login
{ "email": "budi@mail.com", "password": "password123" }
```

### Ambil feed
```
GET /api/posts?per_page=20
```
Response:
```json
{
  "data": [
    { "id": 5, "title": "Bandung", "location": "Buah Batu", "travel_date": "2026-06-04",
      "total_budget": 0, "author": "Budi", "author_id": 1,
      "photo": "https://res.cloudinary.com/...", "photos_count": 2, "rating": 4.5 }
  ],
  "meta": { "current_page": 1, "last_page": 1, "total": 1 }
}
```

### Buat postingan (dengan foto)
```
POST /api/posts
Authorization: Bearer <token>
Content-Type: multipart/form-data

title=Bandung
location=Buah Batu
story=Liburan seru
total_budget=500000
travel_date=2026-06-04
destinations=[{"name":"Dago","lat":-6.86,"lng":107.61}]
photos[]=<file>
```

---

## Contoh Dart (Flutter) — http package

```dart
import 'package:http/http.dart' as http;
import 'dart:convert';

const base = 'https://tripmo-jade.vercel.app/api';

// Login
Future<String> login(String email, String password) async {
  final res = await http.post(
    Uri.parse('$base/login'),
    headers: {'Accept': 'application/json', 'Content-Type': 'application/json'},
    body: jsonEncode({'email': email, 'password': password}),
  );
  final data = jsonDecode(res.body);
  return data['token']; // simpan token (mis. di SharedPreferences)
}

// Ambil feed
Future<List> fetchPosts() async {
  final res = await http.get(Uri.parse('$base/posts'),
      headers: {'Accept': 'application/json'});
  return jsonDecode(res.body)['data'];
}

// Endpoint terproteksi
Future<Map> me(String token) async {
  final res = await http.get(Uri.parse('$base/user'), headers: {
    'Accept': 'application/json',
    'Authorization': 'Bearer $token',
  });
  return jsonDecode(res.body)['user'];
}
```

---

## Catatan
- Selalu kirim header `Accept: application/json` agar error pun balikan JSON.
- URL foto sudah absolut (Cloudinary atau `/storage/...`); `null` jika foto tidak ada.
- CORS sudah dibuka untuk semua origin (`config/cors.php`).
