# SalesCraft AI - Backend

REST API untuk aplikasi SalesCraft AI — generator sales page berbasis AI, dibangun dengan Laravel 11 dan Laravel Sanctum untuk autentikasi berbasis token.

## Tech Stack

- **Laravel 11** - PHP framework
- **Laravel Sanctum** - token-based authentication
- **MySQL** - database
- **Groq API** (llama-3.3-70b) - AI utama untuk generate sales page
- **Gemini API** (gemini-2.5-flash) - fallback jika Groq tidak tersedia

## Fitur

- Register & login dengan token auth (Sanctum)
- Generate sales page AI berdasarkan: nama produk, deskripsi, fitur, target audience, harga, dan USP
- Output berupa structured JSON yang dirender oleh 3 template (Modern, Minimal, Bold)
- Riwayat generasi per user (CRUD)
- Search riwayat berdasarkan nama produk atau deskripsi
- Pagination

## Cara Menjalankan Lokal

**Prasyarat:** PHP 8.2+, Composer, MySQL

```bash
# Clone & install dependencies
composer install

# Copy env dan generate key
cp .env.example .env
php artisan key:generate

# Isi .env — DB, GROQ_API_KEY, GEMINI_API_KEY, FRONTEND_URL

# Migrate database
php artisan migrate

# Jalankan server
php artisan serve
```

Server berjalan di `http://localhost:8000`

## Environment Variables

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=salescraft_ai
DB_USERNAME=root
DB_PASSWORD=

GROQ_API_KEY=your_groq_api_key
GEMINI_API_KEY=your_gemini_api_key

FRONTEND_URL=http://localhost:3000
```

## API Endpoints

| Method | Endpoint | Keterangan |
|--------|----------|------------|
| POST | `/api/register` | Daftar akun baru |
| POST | `/api/login` | Login, return token |
| POST | `/api/logout` | Logout (butuh token) |
| GET | `/api/me` | Info user yang login |
| GET | `/api/generations` | List riwayat (support `?search=`) |
| POST | `/api/generations` | Generate sales page baru |
| GET | `/api/generations/{id}` | Detail satu generasi |
| DELETE | `/api/generations/{id}` | Hapus generasi |

## Deployment

Di-deploy ke Railway. Variabel environment di-set via Railway dashboard.
