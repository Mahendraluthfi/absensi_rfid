# 🎓 Sistem Presensi / Absensi Siswa Berbasis RFID Tag

Aplikasi sistem absensi dan presensi siswa mandiri berbasis **RFID Tag Reader (USB HID Keyboard Emulation)** yang modern, responsif, dan fleksibel. Dibangun menggunakan stack modern **Laravel 11**, **Vue 3 (Inertia.js)**, **Tailwind CSS**, dan **MySQL**.

Mendukung **Multi-Sesi Dinamis** (Absen Pagi, Sholat Dhuhur, Absen Pulang, Ekstrakurikuler, dll.), **Deteksi Sesi Otomatis** berdasarkan jam operasional, **Tampilan Foto Profil Siswa** berukuran besar saat tap kartu, audio feedback (Web Audio API), serta **Laporan Kehadiran & Rekap Matriks Bulanan** dengan fitur ekspor Excel (.xlsx).

---

## ✨ Fitur Utama

- **🚀 Kiosk Terminal Scan RFID (`/attendance/scan`)**:
  - Kompatibel dengan alat USB RFID Reader (Plug & Play / Keyboard Emulation).
  - Tampilan foto siswa beresolusi tinggi saat tap kartu berhasil.
  - Audio feedback otomatis (nada sukses, nada peringatan dobel tap, dan nada error).
  - Anti-duplikasi tap dalam sesi yang sama (mencegah siswa tap 2 kali).
  - Mode fullscreen & live digital clock (WIB / UTC+07:00).
- **⏱️ Multi-Sesi Presensi Dinamis**:
  - Konfigurasi kategori sesi fleksibel (misal: Absen Berangkat, Sholat Dhuhur, Absen Pulang, Ekstrakurikuler).
  - Deteksi keterlambatan otomatis dan perhitungan selisih menit terlambat.
  - Opsi override sesi manual jika dibutuhkan operator gerbang/guru piket.
- **👨‍🎓 Manajemen Data Siswa**:
  - CRUD lengkap data siswa (NISN, Nama, Kelas, RFID UID, Foto, No Telepon, Status).
  - Upload dan live preview foto profil siswa.
  - Quick RFID Card Pairing (pasangkan kartu langsung dengan sekali tap).
  - Import data siswa dari Excel (.xlsx) & Export ke Excel.
- **📊 Laporan & Rekapitulasi Presensi (`/reports`)**:
  - Log detail riwayat tap per tanggal, jam, kelas, dan kategori.
  - Rekap Matriks Kehadiran Bulanan (Format H/T/S/I/A per tanggal).
  - Filter pencarian nama/NISN, kelas, dan rentang tanggal.
  - Ekspor laporan ke file Microsoft Excel (.xlsx) dan fitur Cetak / Print.
- **🎨 UI Modern & Dark Mode**:
  - Desain modern bernuansa Tailwind CSS & komponen bergaya Shadcn UI.
  - Dukungan tema Terang (Light) dan Gelap (Dark) dengan kontras tinggi.

---

## 🛠️ Tech Stack

- **Backend**: Laravel 11 (PHP 8.3)
- **Frontend**: Vue 3 (Composition API) + Inertia.js
- **Styling**: Tailwind CSS + Lucide Icons
- **Database**: MySQL / MariaDB
- **Excel Processing**: PhpSpreadsheet (`phpoffice/phpspreadsheet`)
- **Testing**: PHPUnit / Laravel Test Suite (36 passing tests)

---

## 📋 Persyaratan Sistem (Prerequisites)

Sebelum menginstal, pastikan komputer/server Anda telah terpasang:

1. **PHP** versi `>= 8.2` (Direkomendasikan PHP 8.3)
   - Ekstensi PHP wajib aktif: `pdo_mysql`, `mbstring`, `openssl`, `curl`, `fileinfo`, `gd`, `zip`.
2. **Composer** versi `>= 2.x`
3. **Node.js** versi `>= 18.x` & **NPM**
4. **MySQL** versi `>= 5.7` atau **MariaDB** versi `>= 10.3`
5. *(Opsional)* **USB RFID Reader** (125 KHz EM4100/TK4100 atau 13.56 MHz Mifare/NFC)

---

## 🚀 Panduan Instalasi (Step-by-Step)

Ikuti langkah-langkah berikut untuk menjalankan project di komputer lokal:

### 1. Clone Repository
```bash
git clone https://github.com/Mahendraluthfi/absensi_rfid.git
cd absensi_rfid
```

### 2. Install Dependensi PHP (Composer)
```bash
composer install
```

### 3. Install Dependensi Frontend (NPM)
```bash
npm install
```

### 4. Konfigurasi Environment (`.env`)
Salin file `.env.example` menjadi `.env`:
```bash
# Windows PowerShell
copy .env.example .env

# Linux / macOS / Git Bash
cp .env.example .env
```

Buka file `.env` dan sesuaikan koneksi database MySQL Anda:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=absensi_rfid
DB_USERNAME=root
DB_PASSWORD=
```
> **Catatan**: Buat database kosong bernama `absensi_rfid` terlebih dahulu di MySQL (misal via phpMyAdmin, MySQL Workbench, atau terminal: `CREATE DATABASE absensi_rfid;`).

### 5. Generate Application Key
```bash
php artisan key:generate
```

### 6. Jalankan Migrasi & Seeder Database
Perintah ini akan membuat struktur tabel dan mengisi data awal (115 data siswa, foto profil dummy, kategori sesi absensi, aturan jam sekolah, dan akun admin):
```bash
php artisan migrate:fresh --seed
```

### 7. Buat Symbolic Link Storage (Jika Dibutuhkan)
```bash
php artisan storage:link
```

### 8. Kompilasi Aset Frontend
Untuk mode produksi:
```bash
npm run build
```
*(Atau gunakan `npm run dev` jika Anda ingin mengembangkan tampilan secara live-reload).*

### 9. Jalankan Server Aplikasi
```bash
php artisan serve
```

Aplikasi sekarang dapat diakses melalui browser di: **`http://localhost:8000`**

---

## 🔐 Akun Login Default

Masuk ke halaman login di **`http://localhost:8000/login`** menggunakan akun administrator:

- **Email**: `admin@absensi.local`
- **Password**: `admin123`

---

## 🖥️ Menggunakan Terminal Kiosk Scan RFID

1. Akses langsung halaman scanner di: **`http://localhost:8000/attendance/scan`** (bisa diakses tanpa login admin, cocok untuk PC/Laptop di pos gerbang atau resepsionis).
2. Hubungkan alat **USB RFID Reader** ke laptop/PC.
3. Tempelkan kartu/gantungan RFID siswa:
   - Sesi absensi otomatis terdeteksi sesuai jam saat ini (Berangkat, Sholat Dhuhur, Pulang, dsb.).
   - Foto dan data siswa langsung muncul di layar utama disertai indikator Tepat Waktu / Terlambat.
   - Jika kartu belum terdaftar, sistem akan memberikan notifikasi dan admin dapat langsung mendaftarkannya ke siswa terkait.

---

## 🧪 Menjalankan Pengujian Otomatis (Tests)

Proyek ini telah dilengkapi dengan 36 test kasus fitur otomatis:

```bash
php artisan test
```

---

## 📄 Lisensi

Proyek ini dilisensikan di bawah [MIT License](LICENSE).
