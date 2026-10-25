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
4. **MySQL** versi `>= 5.7` atau **MariaDB** versi `>= 10.3` (atau melalui XAMPP)
5. *(Opsional)* **USB RFID Reader** (125 KHz EM4100/TK4100 atau 13.56 MHz Mifare/NFC)

---

## 🚀 Panduan Instalasi Standar (Step-by-Step)

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

## 📦 Panduan Khusus: Instalasi Menggunakan XAMPP (`htdocs`)

Jika Anda menggunakan paket web server **XAMPP di Windows** dan ingin meletakkan folder project di dalam `C:\xampp\htdocs\`:

### 1. Persiapan XAMPP
1. Pastikan versi PHP pada XAMPP Anda minimal **PHP 8.2** (buka terminal lalu ketik `php -v`).
2. Buka **XAMPP Control Panel**, klik tombol **Start** pada modul **Apache** dan **MySQL**.
3. Buka browser ke **phpMyAdmin**: `http://localhost/phpmyadmin`.
4. Klik tab **Databases**, buat database baru bernama: **`absensi_rfid`** (Collation: `utf8mb4_unicode_ci`).
5. **Cek Ekstensi PHP di XAMPP**:
   Buka file konfigurasi `C:\xampp\php\php.ini` (atau klik tombol **Config > PHP (php.ini)** pada baris Apache di XAMPP Control Panel), pastikan baris-baris ekstensi berikut tidak diawali tanda titik koma `;` (sudah aktif):
   ```ini
   extension=fileinfo
   extension=gd
   extension=zip
   extension=pdo_mysql
   extension=mbstring
   ```
   *(Simpan file lalu klik **Stop** dan **Start** ulang Apache jika ada perubahan).*

### 2. Meletakkan Project ke Folder `htdocs`
Buka **PowerShell** atau **Command Prompt**, lalu arahkan ke folder `htdocs`:
```bash
cd C:\xampp\htdocs
git clone https://github.com/Mahendraluthfi/absensi_rfid.git
cd absensi_rfid
```

### 3. Install Dependensi & Setup Environment
Di dalam folder `C:\xampp\htdocs\absensi_rfid`, jalankan:
```bash
# Install paket PHP
composer install

# Install paket JavaScript
npm install

# Buat file konfigurasi .env dari template
copy .env.example .env
```

Buka file `.env` di text editor, pastikan konfigurasi database sesuai default XAMPP:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=absensi_rfid
DB_USERNAME=root
DB_PASSWORD=
```
*(Secara default, user MySQL XAMPP adalah `root` dan password-nya kosong).*

Kemudian lanjutkan perintah berikut:
```bash
# Generate encryption key
php artisan key:generate

# Generate tabel dan data awal (115 siswa, foto, kategori, admin)
php artisan migrate:fresh --seed

# Build asset Vite (CSS & JS)
npm run build
```

---

### 4. Cara Menjalankan Aplikasi di XAMPP

Ada 2 metode untuk mengakses aplikasi yang berada di `htdocs`:

#### 💡 Metode A: Menggunakan `php artisan serve` (Paling Direkomendasikan & Praktis)
Meskipun file ditaruh di `htdocs`, cara termudah dan paling stabil untuk Laravel + Inertia.js adalah menjalankan *built-in server*:
1. Pastikan modul **MySQL** di XAMPP Control Panel tetap dalam kondisi **Running**.
2. Di dalam folder `C:\xampp\htdocs\absensi_rfid`, jalankan:
   ```bash
   php artisan serve
   ```
3. Buka browser: **`http://localhost:8000`**

---

#### 🌐 Metode B: Menggunakan Apache VirtualHost XAMPP (Akses Port 80 Tanpa `:8000`)
Jika Anda ingin mengakses aplikasi layaknya website lokal (contoh: `http://absensi.test`):

1. Buka file `C:\xampp\apache\conf\extra\httpd-vhosts.conf` dengan text editor, lalu tambahkan konfigurasi ini di baris paling bawah:
   ```apache
   <VirtualHost *:80>
       DocumentRoot "C:/xampp/htdocs/absensi_rfid/public"
       ServerName absensi.test
       <Directory "C:/xampp/htdocs/absensi_rfid/public">
           Options Indexes FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

2. Buka aplikasi **Notepad as Administrator**, buka file `C:\Windows\System32\drivers\etc\hosts`, lalu tambahkan baris berikut di paling bawah:
   ```text
   127.0.0.1    absensi.test
   ```

3. Pada file `.env` di project Anda, ubah `APP_URL`:
   ```env
   APP_URL=http://absensi.test
   ```

4. Di **XAMPP Control Panel**, klik **Stop** lalu **Start** pada modul **Apache** untuk memuat ulang konfigurasi.
5. Buka browser di: **`http://absensi.test`**

---

#### 📁 Metode C: Akses Langsung Melalui Subfolder `localhost`
Jika tanpa VirtualHost dan ingin diakses via `http://localhost/absensi_rfid/public`:
1. Buka file `.env`, ubah `APP_URL` menjadi:
   ```env
   APP_URL=http://localhost/absensi_rfid/public
   ```
2. Buka browser langsung ke URL: **`http://localhost/absensi_rfid/public`**

---

## 🔐 Akun Login Default

Masuk ke halaman login di **`http://localhost:8000/login`** (atau `http://absensi.test/login` jika pakai VirtualHost):

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
