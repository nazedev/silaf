# Sistem Informasi Lost and Found (SILAF)
<div align="center">
  
[![GitHub stars](https://img.shields.io/github/stars/nazedev/silaf?style=flat-square)](https://github.com/nazedev/silaf/stargazers)
[![GitHub forks](https://img.shields.io/github/forks/nazedev/silaf?style=flat-square)](https://github.com/nazedev/silaf/network/members)
[![GitHub issues](https://img.shields.io/github/issues/nazedev/silaf?style=flat-square)](https://github.com/nazedev/silaf/issues)
[![Views](https://visitor-badge.laobi.icu/badge?page_id=nazedev.silaf)](https://github.com/nazedev/silaf)
</div>

SILAF adalah aplikasi berbasis web untuk pelaporan dan manajemen barang hilang maupun temuan di lingkungan kampus. Platform ini memfasilitasi mahasiswa dalam memublikasikan laporan, serta memberikan akses moderasi terpusat bagi pihak Pusat Keamanan. 

Dibangun menggunakan arsitektur *serverless*, sistem ini memisahkan lapisan frontend statis dengan backend API untuk mengoptimalkan proses *deployment* di Vercel.

## 💻 Tech Stack

- **Frontend:** HTML5, Vanilla JavaScript, Tailwind CSS
- **Backend:** Native PHP 8.x (Vercel Serverless Functions)
- **Database:** TiDB Cloud (MySQL-compatible)
- **Storage:** Cloudinary
- **Authentication:** JSON Web Token (JWT) & Google Identity Services (SSO)

## 🔐 Fitur Autentikasi

Sistem mengimplementasikan otentikasi ganda yang berjalan paralel pada satu skema database:

1. **Otentikasi Standar:** Registrasi dan login menggunakan kredensial manual (enkripsi menggunakan `password_hash`).
2. **Google SSO:** Login cepat terintegrasi dengan Google Workspace/Gmail. Sistem secara otomatis melakukan *account linking* jika email sudah terdaftar, atau *auto-provisioning* untuk pengguna baru.
3. **Admin Provisioning:** Pembuatan akun dengan *role* `ADMIN` diamankan melalui *endpoint* khusus yang mewajibkan validasi `ADMIN_SECRET_CODE`.

## 📁 Struktur Direktori

```text
/
├── api/                    # Backend REST API (PHP)
│   ├── admin/              # Endpoint moderasi admin
│   ├── auth/               # Endpoint otentikasi & SSO
│   ├── items/              # Endpoint manajemen laporan
│   └── middleware/         # Verifikasi token JWT
├── config/                 
│   └── database.php        # Konfigurasi PDO TiDB Cloud
├── frontend/               # Antarmuka Klien
│   ├── index.html          # Gerbang utama (Login/Register)
│   ├── dashboard.html      # Tampilan utama laporan
│   └── admin/
│       └── signup.html     # Pendaftaran admin (Secret Code)
├── .env.example            # Template environment variables
├── composer.json           # Dependensi project
└── vercel.json             # Konfigurasi routing Vercel

```

## 🛣️ API & Routing Path

Konfigurasi `vercel.json` secara otomatis mengarahkan akses *root* ke direktori `/frontend/`.

**Client-Side:**

* `GET /` : Menampilkan `frontend/index.html`
* `GET /dashboard.html` : Halaman dashboard pengguna
* `GET /admin/signup.html` : Halaman registrasi keamanan

**Backend (REST API):**

* `POST /api/auth/register.php` : Pendaftaran akun standar
* `POST /api/auth/login.php` : Autentikasi & generate JWT
* `POST /api/auth/google_login.php` : Validasi SSO Google & JWT
* `POST /api/auth/admin_register.php` : Registrasi role Admin
* `GET  /api/items/list.php` : Pengambilan data laporan
* `POST /api/items/create.php` : Upload media & simpan laporan

## ⚙️ Environment Variables

Gunakan file `.env.example` sebagai referensi konfigurasi. Buat file `.env` pada *root* direktori dengan format berikut:

```env
DATABASE_URL="mysql://USER:PASSWORD@HOST:PORT/silaf_db"
JWT_SECRET="secret_key_anda"
ADMIN_SECRET_CODE="kode_rahasia_admin"
GOOGLE_CLIENT_ID="client_id_google.apps.googleusercontent.com"
CLOUDINARY_URL="cloudinary://API_KEY:API_SECRET@CLOUD_NAME"

```

> **Note:** Pastikan file `.env` telah didaftarkan ke dalam `.gitignore` untuk mencegah kebocoran kredensial.

## 🚀 Development (Lokal)

1. Clone repositori:
```bash
git clone [https://github.com/nazedev/silaf.git](https://github.com/nazedev/silaf.git)
cd silaf

```


2. Install dependensi (pastikan PHP 8.0+ & Composer tersedia):
```bash
composer install

```


3. Konfigurasi kredensial pada file `.env`.
4. Jalankan *development server*:
```bash
php -S localhost:8000

```


5. Akses aplikasi melalui `http://localhost:8000/frontend/index.html`.

## ☁️ Deployment (Vercel)

Aplikasi ini menggunakan `vercel-php` untuk eksekusi skrip.

1. Push repositori terbaru ke GitHub Anda.
2. Tambahkan *project* baru di dashboard [Vercel](https://vercel.com) dan hubungkan dengan repositori ini.
3. Konfigurasikan seluruh kunci dari file `.env` ke dalam menu **Environment Variables** di pengaturan Vercel.
4. Lakukan *Deploy*. Vercel akan secara otomatis membaca `vercel.json`, menginstal dependensi Composer, dan mengatur *routing*.
