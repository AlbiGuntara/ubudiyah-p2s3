# INSTALASI

## Persyaratan Sistem

- PHP 8.3+
- Composer 2.x
- MySQL 8.x
- Node.js 20+
- NPM / PNPM

## Langkah Instalasi

### 1. Clone Repository

```bash
git clone <repository-url>
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

Edit `.env` sesuai dengan konfigurasi database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ubudiyah
DB_USERNAME=root
DB_PASSWORD=
```

### 4. Install NPM Dependencies

```bash
npm install
```

### 5. Migrasi dan Seeding Database

```bash
php artisan migrate:fresh --seed
```

Perintah ini akan:
- Membuat seluruh tabel database
- Mengisi data demo (daerah, asrama, santri, petugas, pelanggaran, pembinaan)
- Membuat role dan permission
- Membuat 3 user demo

### 6. Build Frontend

```bash
npm run build
```

### 7. Jalankan Aplikasi

```bash
php artisan serve
```

Akses di `http://localhost:8000`

## User Demo

| Role | Username | Password |
|------|----------|----------|
| Super Admin | `superadmin` | `password` |
| Petugas | `petugas` | `password` |
| Pembina | `pembina` | `password` |

## Fitur

### Master Data
- **Daerah** - CRUD dengan soft delete
- **Asrama** - CRUD dengan filter daerah
- **Santri** - CRUD dengan filter, pencarian, import Excel
- **Petugas** - CRUD lengkap
- **Jenis Pelanggaran** - CRUD daftar pelanggaran

### Pelanggaran
- Input individual (pilih santri)
- Input massal (pilih asrama, jumlah santri)
- Filter berdasarkan tanggal, bulan, tahun, daerah, asrama, sumber

### Pembinaan
- CRUD pembinaan dengan panggilan I/II/III

### Dashboard
- Statistik hari ini dan bulan ini
- Grafik pelanggaran harian, bulanan, daerah, asrama
- Top 10 daerah, asrama, santri

### Laporan
- Bulanan - filter bulan/tahun/daerah/asrama/IKSASS
- Tahunan - filter tahun/daerah/asrama/IKSASS
- Total pelanggaran (termasuk ketua kamar)
- Per daerah (ranking asrama)
- Per asrama (ranking santri)

### Export
- Excel dengan header pondok
- PDF A4 portrait/landscape

### Fitur Lain
- Dark mode (tersimpan di cookie)
- Global search (santri, asrama, daerah)
- Audit log aktivitas
- Responsive (desktop, tablet, mobile)

## Role & Hak Akses

### Super Admin
Akses penuh ke seluruh fitur termasuk manajemen user.

### Petugas
Input pelanggaran, edit pelanggaran miliknya, melihat statistik.

### Pembina
Melihat data santri, akumulasi pelanggaran, mengelola pembinaan.

## Struktur Direktori

```
app/
├── Http/
│   ├── Controllers/     # Controller
│   ├── Requests/        # Form Request Validation
│   ├── Services/        # Service Layer
│   └── Middleware/      # Middleware
├── Models/              # Eloquent Models
├── Policies/            # Authorization Policies
├── Traits/              # Traits (Auditable)
├── Exports/             # Export Classes
└── Imports/             # Import Classes

database/
├── migrations/          # Database Migrations
├── seeders/             # Database Seeders
└── factories/           # Model Factories

resources/
├── js/
│   ├── components/      # React Components
│   │   ├── ui/          # Shadcn-style UI
│   │   └── layout/      # Layout Components
│   ├── pages/           # React Pages
│   ├── lib/             # Utilities
│   └── types/           # TypeScript Types
└── views/               # Blade Templates
```
