# Aplikasi Catatan: Laravel + Vue.js + Docker + GitHub CI/CD

Proyek UTS mata kuliah **Konstruksi dan Evolusi Perangkat Lunak** (Semester Gasal 2026/2027).
Satu sistem utuh: backend **Laravel** (REST API) dan frontend **Vue.js**, masing-masing berjalan
di container **Docker**, disatukan dengan **Docker Compose**, dan dikelola di **GitHub** lengkap
dengan **CI/CD** (GitHub Actions + GitHub Container Registry).

> Ganti `<USERNAME>` dan `<REPO>` pada badge di atas dengan akun dan nama repository kamu.

## Daftar isi

1. [Fitur](#fitur)
2. [Teknologi](#teknologi)
3. [Struktur repository](#struktur-repository)
4. [Prasyarat](#prasyarat)
5. [Menjalankan dengan Docker Compose](#menjalankan-dengan-docker-compose)
6. [Menjalankan secara lokal (tanpa Docker)](#menjalankan-secara-lokal-tanpa-docker)
7. [Konfigurasi environment](#konfigurasi-environment)
8. [Dokumentasi API](#dokumentasi-api)
9. [Pengujian](#pengujian)
10. [Arsitektur dan alur data](#arsitektur-dan-alur-data)
11. [Docker dan Compose](#docker-dan-compose)
12. [CI/CD](#cicd)
13. [Alur kerja Git](#alur-kerja-git)
14. [Image di GitHub Container Registry](#image-di-github-container-registry)
15. [Bonus: FrankenPHP](#bonus-frankenphp)
16. [Pemecahan masalah](#pemecahan-masalah)

## Fitur

- Registrasi, login, dan logout dengan token (Laravel Sanctum).
- CRUD catatan milik pengguna yang sedang login (setiap pengguna hanya melihat catatannya sendiri).
- Validasi request dengan respons JSON dan kode status HTTP yang konsisten (200, 201, 401, 404, 422).
- Frontend Vue.js dengan Vue Router, Axios, dan penyimpanan token di browser.
- Feature test otomatis, CI dua job, dan deploy image Docker ke `ghcr.io`.

## Teknologi

| Lapisan | Teknologi |
|---|---|
| Backend | PHP 8.3, Laravel (11/12), Laravel Sanctum |
| Frontend | Vue 3, Vue Router 4, Axios, Vite |
| Database | MySQL 8.4 (Docker), SQLite untuk test |
| Kontainerisasi | Docker, Docker Compose, Nginx (menyajikan frontend) |
| CI/CD | GitHub Actions, GitHub Container Registry (ghcr.io) |

## Struktur repository

```
.
├── backend/                  # Laravel API
│   ├── app/Http/Controllers/ # AuthController, NoteController
│   ├── app/Models/           # User, Note
│   ├── routes/api.php        # definisi endpoint
│   ├── tests/Feature/        # NoteApiTest
│   ├── Dockerfile
│   └── .dockerignore
├── frontend/                 # Vue.js
│   ├── src/                  # api.js, router.js, views/
│   ├── Dockerfile            # multi-stage (node -> nginx)
│   ├── nginx.conf
│   └── .dockerignore
├── postman/                  # export Postman Collection
├── .github/workflows/
│   ├── ci.yml                # CI: backend-test dan frontend-build
│   └── deploy.yml            # CD: build dan push image ke GHCR
├── docker-compose.yml        # db + backend + frontend
├── .env.example              # contoh konfigurasi Compose
└── README.md
```

## Prasyarat

| Kebutuhan | Versi | Untuk |
|---|---|---|
| Docker Desktop / Docker Engine + Compose v2 | terbaru | menjalankan lewat Docker |
| Git | terbaru | version control |
| PHP | 8.2 atau lebih baru | pengembangan lokal backend |
| Composer | 2.x | dependensi PHP |
| Node.js dan npm | 20.19+ atau 22 | pengembangan lokal frontend |

Cek versi: `docker --version`, `docker compose version`, `php -v`, `composer -V`, `node -v`.

## Menjalankan dengan Docker Compose

```bash
git clone https://github.com/<USERNAME>/<REPO>.git
cd <REPO>

cp .env.example .env
```

Isi `APP_KEY` di `.env`. Cara membuatnya:

```bash
cd backend && composer install && php artisan key:generate --show
# salin hasilnya (berawalan base64:) ke APP_KEY di file .env root
cd ..
```

Jika tidak memakai PHP lokal, bisa lewat Docker:

```bash
docker run --rm php:8.3-cli-alpine php -r 'echo "base64:".base64_encode(random_bytes(32)).PHP_EOL;'
```

Ganti juga `DB_PASSWORD` dan `DB_ROOT_PASSWORD`, lalu jalankan:

```bash
docker compose up -d --build
docker compose ps
docker compose logs -f backend
```

| Layanan | Alamat |
|---|---|
| Frontend | http://localhost:8080 |
| API | http://localhost:8000/api |
| Health check | http://localhost:8000/up |

Perintah berguna:

```bash
docker compose down        # hentikan container (data database tetap ada di volume)
docker compose down -v     # hentikan DAN hapus volume database (data hilang)
docker compose up -d --build --force-recreate   # bangun ulang
docker compose exec backend php artisan migrate:status
```

## Menjalankan secara lokal (tanpa Docker)

```bash
# Terminal 1: backend
cd backend
cp .env.example .env            # lewati jika sudah ada
composer install
php artisan key:generate
php artisan migrate
php artisan serve               # http://127.0.0.1:8000

# Terminal 2: frontend
cd frontend
npm install
npm run dev                     # http://localhost:5173
```

Frontend membaca alamat API dari `frontend/.env.development` (`VITE_API_URL`).

## Konfigurasi environment

### `.env` di root (dibaca Docker Compose)

| Variabel | Fungsi | Contoh |
|---|---|---|
| `APP_KEY` | kunci enkripsi Laravel | `base64:...` |
| `DB_DATABASE` | nama database | `notes` |
| `DB_USERNAME` | pengguna database | `notes_user` |
| `DB_PASSWORD` | password pengguna | (isi sendiri) |
| `DB_ROOT_PASSWORD` | password root MySQL | (isi sendiri) |
| `VITE_API_URL` | alamat API yang dipanggil browser | `http://localhost:8000/api` |

Aturan penting:

- `.env` **tidak boleh** di-commit (sudah ada di `.gitignore`); `.env.example` yang di-commit.
- Rahasia **tidak boleh** ditulis di Dockerfile. Rahasia diberikan lewat environment saat runtime
  (Compose) atau GitHub Secrets (CI/CD).
- Variabel Vite berawalan `VITE_` ikut tertanam di hasil build dan bisa dibaca siapa pun, jadi hanya
  isi dengan hal yang aman dipublikasikan.
- `VITE_API_URL` dipakai browser, sehingga berisi `localhost:8000`, bukan nama service `backend`.
  Mengubah nilainya butuh build ulang frontend.

## Dokumentasi API

Basis URL: `http://localhost:8000/api`. Kirim header `Accept: application/json` dan, untuk endpoint
terproteksi, `Authorization: Bearer <token>`.

| Method | Endpoint | Auth | Body | Respons sukses | Respons gagal |
|---|---|---|---|---|---|
| POST | `/register` | tidak | `name`, `email`, `password` (min 8) | 201 + token | 422 |
| POST | `/login` | tidak | `email`, `password` | 200 + token | 401, 422 |
| GET | `/me` | ya | - | 200 | 401 |
| POST | `/logout` | ya | - | 200 | 401 |
| GET | `/notes` | ya | - | 200 | 401 |
| POST | `/notes` | ya | `title`, `content?` | 201 | 401, 422 |
| GET | `/notes/{id}` | ya | - | 200 | 401, 404 |
| PUT | `/notes/{id}` | ya | `title`, `content?` | 200 | 401, 404, 422 |
| DELETE | `/notes/{id}` | ya | - | 200 | 401, 404 |

Arti kode status: **200** berhasil, **201** data dibuat, **401** tidak terautentikasi atau login salah,
**404** data tidak ditemukan, **422** validasi gagal.

Contoh dengan curl:

```bash
curl -s -X POST http://localhost:8000/api/register \
  -H 'Accept: application/json' -H 'Content-Type: application/json' \
  -d '{"name":"Budi","email":"budi@example.com","password":"password123"}'

curl -s http://localhost:8000/api/notes \
  -H 'Accept: application/json' -H 'Authorization: Bearer <TOKEN>'
```

### Postman

Impor `postman/UTS-Notes-API.postman_collection.json` (Postman: **Import**). Jalankan request
**Login** lebih dulu; token tersimpan otomatis ke variabel koleksi dan dipakai request lain.

## Pengujian

```bash
cd backend
php artisan test
```

Feature test (`backend/tests/Feature/NoteApiTest.php`) memakai SQLite in-memory dan mencakup register,
login, validasi 422, proteksi 401, CRUD catatan, dan 404. Test yang sama dijalankan otomatis oleh CI.

## Arsitektur dan alur data

```
Browser (Vue + Axios)
   │  HTTP + header Authorization: Bearer <token>
   ▼
Laravel API (routes/api.php)
   │  middleware auth:sanctum  ->  controller + validasi
   ▼
Model Eloquent  ->  MySQL (service db, volume db_data)
   │
   ▼
Respons JSON (200/201/401/404/422)  ->  Vue memperbarui tampilan
```

1. Pengguna mengisi form di Vue. Komponen memanggil Axios yang mengirim permintaan ke `VITE_API_URL`.
2. Interceptor Axios menyisipkan token dari penyimpanan browser ke header setiap permintaan.
3. Laravel memeriksa token (`auth:sanctum`), memvalidasi data, lalu membaca atau menulis lewat model.
4. Di Docker Compose, backend menemukan database lewat nama service `db` pada jaringan `app-net`.
5. Data database disimpan di named volume `db_data`, sehingga tidak hilang saat container dibuat ulang.
6. Hasil dikembalikan sebagai JSON dan ditampilkan Vue.

## Docker dan Compose

| Service | Image / build | Port | Catatan |
|---|---|---|---|
| `db` | `mysql:8.4` | internal | volume `db_data`, healthcheck |
| `backend` | `./backend` (PHP 8.3 CLI Alpine) | 8000 | migrasi otomatis saat start, menunggu `db` sehat |
| `frontend` | `./frontend` (build Node -> Nginx) | 8080 | multi-stage, hanya berisi file statis |

Poin teknis:

- Semua service berada di jaringan `app-net`; antar container saling menemukan lewat nama service.
- `depends_on` dengan `condition: service_healthy` memastikan MySQL siap sebelum backend jalan.
- Dockerfile memakai tag versi tetap (bukan `latest`), menyalin file dependensi lebih dulu agar
  cache bekerja, dan `.dockerignore` mencegah `.env`, `vendor`, `node_modules` masuk ke image.

## CI/CD

### CI (`.github/workflows/ci.yml`)

Terpicu pada Pull Request dan push ke `main` dan `dev`. Dua job paralel:

- `backend-test`: setup PHP, `composer install`, `php artisan test`.
- `frontend-build`: setup Node, `npm ci`, `npm run build`.

### CD (`.github/workflows/deploy.yml`)

Hanya berjalan saat push ke `main`. Membangun image backend dan frontend lalu mengunggahnya ke
GitHub Container Registry. Login memakai **GitHub Secret** `GHCR_TOKEN`.

Menyiapkan secret: **Settings -> Secrets and variables -> Actions -> New repository secret**,
nama `GHCR_TOKEN`, isi Personal Access Token (classic) dengan scope `write:packages`.

### Branch protection

Branch `main` dan `dev` dilindungi: perubahan wajib lewat Pull Request dan status check
`backend-test` serta `frontend-build` harus lulus sebelum merge.

## Alur kerja Git

- `main`: kode stabil dan menjadi sumber deploy.
- `dev`: integrasi pekerjaan.
- Branch fitur: `feat/...`, `fix/...`, `ci/...`, `build/...`, dibuat dari `dev`.
- Semua perubahan lewat Pull Request: branch fitur -> `dev`, lalu `dev` -> `main`.
- Pesan commit mengikuti **Conventional Commits** (`feat:`, `fix:`, `docs:`, `test:`, `ci:`, `build:`, `chore:`).

## Image di GitHub Container Registry

Nama image memakai huruf kecil:

```bash
docker pull ghcr.io/<username-huruf-kecil>/uts-kepl-backend:main
docker pull ghcr.io/<username-huruf-kecil>/uts-kepl-frontend:main
```

Jika `pull` ditolak, ubah visibilitas package menjadi Public: halaman package -> **Package settings ->
Change visibility**.

## Bonus: FrankenPHP

Backend dapat dijalankan di FrankenPHP dengan Laravel Octane:

```bash
cd backend
composer require laravel/octane
php artisan octane:install --server=frankenphp
mv Dockerfile.frankenphp Dockerfile
cd .. && docker compose up -d --build
curl -I http://localhost:8000/up      # header Server: Caddy menandakan FrankenPHP aktif
```

## Pemecahan masalah

| Gejala | Penyebab | Solusi |
|---|---|---|
| Postman menampilkan HTML atau redirect `/login` | header `Accept` tidak ada | tambahkan `Accept: application/json` |
| `No application encryption key has been specified` | `APP_KEY` kosong | isi `APP_KEY` di `.env`, lalu `docker compose up -d --force-recreate` |
| Backend error koneksi database saat start | MySQL belum siap | tunggu; cek `docker compose ps` (db harus `healthy`) dan `docker compose logs db` |
| Frontend tidak bisa memanggil API di Docker | `VITE_API_URL` salah atau memakai nama service | set `http://localhost:8000/api`, lalu `docker compose up -d --build` |
| Error CORS di browser | konfigurasi CORS | `php artisan config:publish cors`, pastikan `paths` memuat `api/*` |
| `port is already allocated` | port 8000/8080 dipakai aplikasi lain | ubah pemetaan port di `docker-compose.yml` (dan `VITE_API_URL` jika port API berubah) |
| Data hilang setelah restart | memakai `down -v` | gunakan `docker compose down` tanpa `-v` |
| Push image ke GHCR ditolak | token/izin salah | cek scope `write:packages`, `permissions: packages: write`, dan nama image huruf kecil |
| Check tidak muncul di branch protection | workflow belum pernah jalan | jalankan CI sekali lewat Pull Request |
| `npm ci` gagal di CI/Docker | `package-lock.json` belum di-commit | jalankan `npm install` lalu commit `package-lock.json` |
| `composer install` gagal di CI/Docker | `composer.lock` belum di-commit | commit `backend/composer.lock` |

