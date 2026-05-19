````markdown
# Sistem Informasi Lost and Found (SILAF)

SILAF adalah aplikasi berbasis web yang dirancang sebagai pusat pelaporan barang hilang dan penemuan barang di lingkungan kampus. Sistem ini memfasilitasi mahasiswa untuk memublikasikan laporan barang hilang/temuan, sekaligus memberikan wewenang penuh kepada Pusat Keamanan (Admin) untuk melakukan moderasi data.

Aplikasi ini dibangun menggunakan arsitektur Serverless dengan pemisahan antara Frontend (Statis) dan Backend (API), serta dioptimalkan untuk deployment di Vercel.

## Teknologi yang Digunakan

- **Frontend:** HTML5, Vanilla JavaScript, Tailwind CSS (via CDN)
- **Backend:** Native PHP 8.x (berjalan sebagai Vercel Serverless Functions)
- **Database:** TiDB Cloud (MySQL-compatible)
- **Media Storage:** Cloudinary
- **Autentikasi:** JSON Web Token (JWT) & Google Identity Services (SSO)

## Fitur Utama & Autentikasi

Sistem ini menggunakan metode **Dual Authentication** (Metode Ganda) yang berjalan berdampingan dalam satu tabel database:

1. **Otentikasi Manual:** Pengguna dapat mendaftar dan masuk menggunakan kombinasi Email dan Password (dienkripsi menggunakan `password_hash` bawaan PHP).
2. **Google SSO (Single Sign-On):** Pengguna dapat langsung masuk menggunakan akun Google. Sistem secara otomatis akan menautkan akun jika email sudah terdaftar, atau membuat akun baru (tanpa password) jika belum terdaftar.
3. **Pendaftaran Admin Rahasia:** Registrasi untuk role `ADMIN` tidak dilakukan melalui database secara manual, melainkan melalui endpoint khusus yang membutuhkan Input Kode Rahasia (Secret Code).

## Struktur Direktori

```text
/
├── api/                    # Backend API (PHP)
│   ├── admin/              # Endpoint khusus hak akses Admin
│   ├── auth/               # Endpoint login, register, dan Google SSO
│   ├── items/              # Endpoint CRUD laporan barang
│   └── middleware/         # Pengecekan token JWT
├── config/                 # Konfigurasi sistem
│   └── database.php        # Koneksi PDO ke TiDB Cloud
├── frontend/               # Antarmuka Klien (HTML, CSS, JS)
│   ├── index.html          # Halaman Login/Register Utama
│   ├── dashboard.html      # Feed utama (User & Admin)
│   └── admin/
│       └── signup.html     # Halaman pendaftaran Admin via Secret Code
├── .env                    # Environment variables (TIDAK DI-UPLOAD KE GIT)
├── composer.json           # Dependensi PHP (firebase/php-jwt, cloudinary, dll)
└── vercel.json             # Konfigurasi routing serverless Vercel
```
````

## Daftar Rute & URL Path

### Frontend (Client-Side)

Konfigurasi `vercel.json` secara otomatis mengarahkan lalu lintas root ke dalam folder `frontend/`.

- `GET /` -> Menampilkan `frontend/index.html` (Login/Register)
- `GET /dashboard.html` -> Menampilkan halaman utama
- `GET /admin/signup.html` -> Menampilkan halaman registrasi admin

### Backend (REST API)

Semua permintaan ke `/api/*` diteruskan ke skrip PHP yang ada di dalam folder `api/`.

- `POST /api/auth/register.php` - Registrasi akun manual (User)
- `POST /api/auth/login.php` - Login manual (menghasilkan JWT)
- `POST /api/auth/google_login.php` - Validasi token Google & menghasilkan JWT aplikasi
- `POST /api/auth/admin_register.php` - Registrasi Admin (Butuh `secret_code`)
- `GET /api/items/list.php` - Mengambil daftar laporan barang (aktif)
- `POST /api/items/create.php` - Mengunggah gambar ke Cloudinary & menyimpan data ke TiDB

## Konfigurasi Environment (.env)

Buat file `.env` di folder root project. Isi dengan format berikut (sesuaikan dengan kredensial Anda):

```env
# Koneksi Database TiDB Cloud
DATABASE_URL="mysql://USER:PASSWORD@HOST:PORT/silaf_db"

# Kunci Rahasia untuk Generate JWT Token
JWT_SECRET="isi_dengan_string_rahasia_yang_panjang"

# Kode Rahasia untuk Pendaftaran Admin Keamanan
ADMIN_SECRET_CODE="kode_rahasia_admin_kampus"

# Google Identity Services (Untuk Login SSO)
GOOGLE_CLIENT_ID="id_client_google_anda.apps.googleusercontent.com"

# Kredensial Cloudinary (Untuk Upload Gambar Barang)
CLOUDINARY_URL="cloudinary://API_KEY:API_SECRET@CLOUD_NAME"

```

_Catatan: Pastikan `.env` terdaftar di dalam `.gitignore`._

## Panduan Instalasi (Lokal)

Jika Anda ingin menjalankan project ini di komputer lokal untuk keperluan development:

1. Clone repositori ini.
2. Pastikan Anda memiliki PHP (versi 8.0+) dan Composer terinstal di komputer.
3. Buka terminal di folder root project dan jalankan perintah:

```bash
composer install

```

_(Atau `php composer.phar install` jika menggunakan phar installer)._ 4. Siapkan file `.env` dan konfigurasikan koneksi TiDB Anda. 5. Jalankan server bawaan PHP:

```bash
php -S localhost:8000

```

6. Buka browser dan akses `http://localhost:8000/frontend/index.html`.
   _(Ingat: Pada environment lokal, Anda harus mengetikkan `/frontend` secara manual karena routing `vercel.json` tidak berjalan di server PHP bawaan)._

## Panduan Deployment (Vercel)

Aplikasi ini dioptimalkan untuk platform Vercel menggunakan `vercel-php`.

1. Push seluruh kode (kecuali folder `vendor` dan `.env`) ke repositori GitHub Anda.
2. Buka dashboard [Vercel](https://vercel.com) dan klik **Add New Project**.
3. Pilih (import) repositori SILAF Anda.
4. Buka tab **Environment Variables** di pengaturan Vercel, lalu tambahkan semua kunci yang ada di file `.env` Anda satu per satu (`DATABASE_URL`, `JWT_SECRET`, `GOOGLE_CLIENT_ID`, dll).
5. Klik **Deploy**.
6. Vercel akan membaca file `vercel.json`, menginstal dependensi Composer secara otomatis di cloud, dan mengatur routing Frontend/Backend. Aplikasi siap diakses secara global!
