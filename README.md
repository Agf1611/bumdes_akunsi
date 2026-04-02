# Sistem Akuntansi BUMDes

Repository ini berisi aplikasi akuntansi BUMDes berbasis web yang siap dipasang di **shared hosting / aaPanel / cPanel** maupun di **localhost XAMPP**.

## Ringkasan aplikasi
Fitur utama yang sudah tersedia:
- master data unit usaha, COA, aset, periode akuntansi, referensi jurnal
- jurnal umum dan cetak bukti
- import jurnal dari template Excel
- aksi massal jurnal
- laporan pembantu dan laporan keuangan dasar

## Struktur folder penting
- `app/` source utama aplikasi
- `public/` document root web dan aset publik
- `storage/` file runtime seperti log, import, attachment, backup
- `docs/` dokumentasi instalasi, update, dan catatan rilis
- `database/` struktur SQL bila tersedia
- `install.php` installer awal aplikasi

## Kebutuhan minimum server
- PHP 8.1 atau lebih baru
- MySQL / MariaDB
- ekstensi PHP: `pdo_mysql`, `mbstring`, `zip`, `fileinfo`, `simplexml`

## Instalasi di hosting (shared hosting / aaPanel / cPanel)

### 1. Upload source code
Upload semua isi project ke hosting. Bisa lewat File Manager, FTP, atau panel server.

### 2. Atur document root
Yang paling disarankan adalah mengarahkan domain atau subdomain ke folder:

```text
/public
```

Kalau panel hosting tidak bisa mengubah document root, upload source ke folder aplikasi lalu sesuaikan entry point sesuai struktur hosting Anda.

### 3. Buat database
Buat database MySQL/MariaDB baru dari panel hosting, lalu siapkan:
- nama database
- username database
- password database
- host database
- port database bila berbeda dari default

### 4. Pastikan folder writable
Folder berikut harus bisa ditulis server:
- `app/config`
- `storage`
- `storage/logs`
- `storage/imports`
- `storage/backups`
- `storage/bank_reconciliations`
- `storage/journal_attachments`
- `public/uploads/profiles`
- `public/uploads/signatures`

Permission yang umum dipakai:
- folder: `755` atau `775`
- file: `644`

### 5. Jalankan installer
Buka browser ke alamat:

```text
https://domain-anda/install.php
```

Lalu isi data berikut:
- host database
- port database
- nama database
- username database
- password database
- URL aplikasi
- akun admin pertama

### 6. Selesaikan instalasi
Setelah instalasi berhasil:
- login ke aplikasi
- isi profil BUMDes
- isi unit usaha
- isi atau import COA
- buat periode akuntansi
- mulai input jurnal

## Instalasi di localhost dengan XAMPP

### 1. Simpan project ke folder htdocs
Contoh:

```text
C:\xampp\htdocs\bumdes-akuntansi
```

### 2. Jalankan Apache dan MySQL
Buka XAMPP Control Panel lalu nyalakan:
- Apache
- MySQL

### 3. Buat database kosong
Buka phpMyAdmin:

```text
http://localhost/phpmyadmin
```

Buat database baru, misalnya:

```text
bumdes_akuntansi
```

### 4. Jalankan installer
Buka browser:

```text
http://localhost/bumdes-akuntansi/install.php
```

Lalu isi data database lokal Anda.

Contoh umum di XAMPP:
- Host: `127.0.0.1` atau `localhost`
- Port: `3306`
- Database: `bumdes_akuntansi`
- Username: `root`
- Password: kosongkan bila default XAMPP belum diubah
- App URL: `http://localhost/bumdes-akuntansi`

### 5. Login ke aplikasi
Setelah install selesai, buka aplikasi dan login memakai akun admin yang dibuat saat instalasi.

## Instalasi ulang
Kalau aplikasi sebelumnya sudah pernah terpasang dan ingin install ulang:
1. hapus file berikut:
   - `app/config/generated.php`
   - `storage/installed.lock`
2. kosongkan database lama atau buat database baru
3. jalankan lagi `install.php`

## Troubleshooting singkat

### Installer bilang aplikasi sudah terpasang
Hapus:
- `app/config/generated.php`
- `storage/installed.lock`

### Upload logo / tanda tangan gagal
Periksa permission folder:
- `public/uploads/profiles`
- `public/uploads/signatures`

### Import jurnal gagal
Pastikan:
- file memakai template terbaru dari aplikasi
- ekstensi PHP `zip` aktif
- format tanggal dan nominal mengikuti template

## Cara menambahkan gambar ke README
Agar gambar tampil di GitHub, simpan gambar di dalam repository. Folder yang disarankan:

```text
docs/images/
```

Contoh file gambar:

```text
docs/images/dashboard.png
```

### Cara pakai dengan Markdown
```md
![Dashboard Aplikasi](docs/images/dashboard.png)
```

### Cara pakai dengan ukuran khusus
GitHub mendukung HTML sederhana di README:

```html
<img src="docs/images/dashboard.png" alt="Dashboard Aplikasi" width="900">
```

### Langkah menambahkan gambar
1. simpan file gambar ke folder `docs/images/`
2. commit atau upload file gambar itu ke repository
3. panggil gambarnya dari README dengan path relatif
4. refresh halaman repo GitHub

### Tips
- pakai nama file tanpa spasi, misalnya `dashboard-jurnal.png`
- format yang aman: `.png`, `.jpg`, `.webp`
- kalau gambar belum muncul, cek apakah path di README sudah sama persis

## Dokumentasi tambahan
- `docs/deploy/INSTALL_SHARED_HOSTING.md`
- `docs/deploy/INSTALL_LOCAL_XAMPP.md`
- `docs/deploy/PRODUCTION_UPDATE_GUIDE.md`
- `CHANGELOG.md`

## Catatan
File hasil instalasi seperti `app/config/generated.php` dan `storage/installed.lock` akan dibuat otomatis setelah wizard install dijalankan, jadi file itu memang tidak perlu disimpan di repository.
