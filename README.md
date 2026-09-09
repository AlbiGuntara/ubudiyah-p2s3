<p align="center">
    <img src="public/logo/p2s3.png" alt="Logo P2S3" width="440">
</p>

<h1 align="center">Sistem Informasi Ubudiyah P2S3</h1>

<p align="center">
    Aplikasi pencatatan, pemantauan, dan pembinaan pelanggaran santri
    <br>
    <strong>Bidang Kepesantrenan</strong> — Unit Ubudiyah
</p>

<p align="center">
    Pondok Pesantren "Salafiyah Syafi'iyah" Sukorejo
    <br>
    Sumberejo Banyuputih Situbondo Jawa Timur
</p>

---

## Tentang Aplikasi

**Ubudiyah P2S3** adalah sistem informasi berbasis web yang digunakan oleh
Bidang Kepesantrenan (Unit Ubudiyah) untuk mencatat dan memantau pelanggaran
santri serta pembinaannya. Aplikasi ini mencakup pencatatan pelanggaran
(individual maupun massal), penerbitan surat panggilan, pengelolaan setoran
sanksi pembinaan (shalawat), pemutihan sisa sanksi, hingga penyusunan laporan
bulanan, tahunan, per daerah, dan per asrama dalam bentuk Excel maupun PDF.

Aplikasi dibangun menggunakan **Laravel** sebagai backend dan **Inertia.js +
React** untuk frontend single-page application (SPA).

## Fitur Utama

### Autentikasi & Manajemen Pengguna
- Login/logout dengan role-based access control (super admin, petugas, pembina)
- Manajemen user, role, dan permission
- Audit log aktivitas pengguna

### Dashboard
- Statistik pelanggaran hari ini dan bulan ini
- Grafik pelanggaran harian, bulanan, per daerah, dan per asrama
- Top 10 daerah, asrama, dan santri

### Master Data
- **Daerah** — kelola data daerah, soft delete
- **Asrama** — kelola kamar/asrama, filter per daerah
- **Santri** — kelola data santri (NIS, IKSASS, status, kamar), pencarian, import Excel
- **Petugas** — kelola data petugas
- **Daftar Pelanggaran** — kelola jenis-jenis pelanggaran

### Pelanggaran
- Input pelanggaran individual (per santri) dan massal (per asrama)
- Filter berdasarkan tanggal, bulan, tahun, daerah, asrama, dan sumber pencatatan
- Edit, hapus, dan update massal (bulk)

### Surat Panggilan
- Cetak surat panggilan pembinaan (semua atau per daerah)
- Riwayat sesi cetak dan cetak ulang

### Pembinaan
- Kelola catatan pembinaan santri dengan sisa sanksi
- Setor sanksi (shalawat) dengan pilihan pelanggaran & jumlah
- Tambah sanksi kembali (koreksi setoran salah)
- Pemutihan (multiply sisa sanksi semua santri)
- Cetak laporan pembinaan (semua atau per daerah)

### Laporan
- Laporan bulanan / tahunan
- Total pelanggaran (termasuk ketua kamar)
- Ranking per daerah (asrama) dan per asrama (santri)
- Export Excel (dengan kop pondok) dan PDF landscape/portrait

### Lainnya
- Dark mode (tersimpan di cookie)
- Global search (santri, asrama, daerah)
- Responsive — desktop, tablet, dan mobile

## Teknologi

| Bagian | Teknologi |
|--------|-----------|
| Backend | Laravel 13 (PHP 8.3+) |
| Frontend | React 19, TypeScript, Inertia.js |
| Styling | Tailwind CSS 4, shadcn-style UI components |
| Build | Vite |
| Database | MySQL 8 (atau SQLite) |
| Chart | Recharts |
| Export PDF | barryvdh/laravel-dompdf |
| Export Excel | maatwebsite/excel |
| RBAC | spatie/laravel-permission |

## Persyaratan Sistem

- PHP **8.3 ke atas**
- Composer **2.x**
- Node.js **20+** dan NPM
- MySQL **8.x** (atau SQLite sebagai alternatif cepat)

## Cara Clone & Menjalankan di Localhost

### 1. Clone Repository

```bash
git clone https://github.com/AlbiGuntara/ubudiyah-p2s3.git
cd ubudiyah-p2s3
```

### 2. Install PHP Dependencies

```bash
composer install
```

### 3. Konfigurasi Environment

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Konfigurasi Database

Default `.env.example` memakai **SQLite** (tanpa setup tambahan):

```env
DB_CONNECTION=sqlite
```

Jika memakai **MySQL**, ubah `.env` menjadi:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ubudiyah
DB_USERNAME=root
DB_PASSWORD=
```

Lalu buat database-nya:

```bash
mysql -u root -p -e "CREATE DATABASE ubudiyah;"
```

### 5. Install NPM Dependencies

```bash
npm install
```

### 6. Migrasi & Seeding Database

```bash
php artisan migrate:fresh --seed
```

Perintah ini akan membuat seluruh tabel, role & permission, serta mengisi data demo
(daerah, asrama, santri, petugas, pelanggaran, pembinaan) dan **3 user demo**.

### 7. Build Frontend

```bash
npm run build
```

> Untuk pengembangan (development) gunakan `npm run dev` agar Vite
> mem-build otomatis dengan hot reload.

### 8. Jalankan Aplikasi

```bash
php artisan serve
```

Aplikasi dapat diakses di **http://localhost:8000**

## User Demo

| Role | Username | Password |
|------|----------|----------|
| Super Admin | `superadmin` | `password` |
| Petugas | `petugas` | `password` |
| Pembina | `pembina` | `password` |

## Role & Hak Akses

- **Super Admin** — akses penuh ke seluruh fitur termasuk manajemen user, role, dan permission.
- **Petugas** — input pelanggaran, mengelola data miliknya, melihat statistik.
- **Pembina** — melihat data santri, akumulasi pelanggaran, dan mengelola pembinaan.

## Struktur Direktori

```
app/
├── Http/
│   ├── Controllers/       # Controller
│   ├── Requests/          # Form Request Validation
│   ├── Services/          # Service Layer
│   └── Middleware/        # Middleware
├── Models/                # Eloquent Models
├── Policies/              # Authorization Policies
├── Traits/                # Traits (Auditable)
├── Exports/               # Export Classes
└── Imports/               # Import Classes

database/
├── migrations/            # Database Migrations
├── seeders/               # Database Seeders
└── factories/             # Model Factories

resources/
├── js/
│   ├── components/        # React Components (UI, layout, shared)
│   ├── pages/             # React Pages (Inertia)
│   ├── lib/               # Utilities
│   └── types/             # TypeScript Types
└── views/                 # Blade Templates (export PDF)

public/
├── logo/                  # Logo pesantren (p2s3.png)
└── build/                 # Hasil build Vite (tidak di-commit)
```

## Script yang Tersedia

| Perintah | Deskripsi |
|----------|-----------|
| `npm run dev` | Jalankan Vite dev server (hot reload) |
| `npm run build` | Build asset untuk production |
| `npm run lint` | Jalankan ESLint + auto fix |
| `npm run types:check` | Type-check TypeScript |
| `composer run dev` | Jalankan semua service sekaligus (server, queue, pail, vite) |
| `composer run test` | Jalankan lint, type-check, dan test |

## Lisensi

Project ini dikembangkan untuk kebutuhan internal
Pondok Pesantren "Salafiyah Syafi'iyah" Sukorejo.