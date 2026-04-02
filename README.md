<div align="center">

# Sistem Akuntansi BUMDes

Aplikasi akuntansi BUMDes berbasis web untuk pengelolaan **master data**, **jurnal umum**, **aset**, **import Excel**, dan **laporan keuangan** yang siap dipasang di **shared hosting / aaPanel / cPanel** maupun **localhost XAMPP**.

![Version](https://img.shields.io/badge/version-1.0.0-2563eb)
![PHP](https://img.shields.io/badge/PHP-8.1%2B-777bb4)
![Database](https://img.shields.io/badge/Database-MySQL%20%2F%20MariaDB-0f766e)
![Install](https://img.shields.io/badge/Installer-Web%20Installer-16a34a)
![Status](https://img.shields.io/badge/status-GitHub%20Ready-f59e0b)

</div>

---

## Preview aplikasi

<img src="docs/images/preview-jurnal-umum.png" alt="Preview Jurnal Umum" width="100%">

---

## Fitur utama

| Modul | Fitur | Keterangan |
|---|---|---|
| Master Data | Unit Usaha | Kelola cabang atau lini usaha BUMDes |
| Master Data | Chart of Accounts (COA) | Kelola struktur akun dan kategori akun |
| Master Data | Aset | Kelola aset, kategori aset, nilai buku, dan penyusutan dasar |
| Master Data | Periode Akuntansi | Buka tutup periode dan atur periode aktif |
| Master Data | Referensi Jurnal | Kelola referensi mitra, persediaan, dan komponen arus kas |
| Transaksi | Jurnal Umum | Input double-entry, detail transaksi, dan cetak bukti |
| Transaksi | Import Jurnal Excel | Import jurnal dari template Excel resmi aplikasi |
| Transaksi | Aksi Massal Jurnal | Tandai, ubah unit usaha, dan hapus jurnal terpilih |
| Laporan | Buku Pembantu | BP Piutang, BP Utang, Buku Besar, Neraca Saldo |
| Laporan | Laporan Keuangan Dasar | Siap dikembangkan untuk kebutuhan pelaporan BUMDes |
| Sistem | Installer Web | Instalasi awal langsung dari browser |
| Sistem | Dokumentasi Repo | Sudah dilengkapi changelog, panduan deploy, dan catatan audit |

---

## Struktur folder penting

| Folder / File | Fungsi |
|---|---|
| `app/` | Source utama aplikasi |
| `public/` | Document root web dan aset publik |
| `storage/` | Log, import, attachment, backup, dan file runtime lain |
| `database/` | Struktur SQL, patch, dan seed |
| `docs/` | Panduan install, deploy, release, dan audit |
| `install.php` | Installer awal aplikasi |
| `CHANGELOG.md` | Riwayat perubahan versi |
| `VERSION` | Penanda versi aplikasi |
| `RELEASE_TAG.txt` | Tag rilis yang direkomendasikan |

---

## Kebutuhan minimum server

- PHP **8.1** atau lebih baru
- MySQL / MariaDB
- Web server Apache / LiteSpeed / Nginx
- Ekstensi PHP:
  - `pdo_mysql`
  - `mbstring`
  - `zip`
  - `fileinfo`
  - `simplexml`

---

## Quick start

### Opsi 1 — Install di hosting
Cocok untuk **shared hosting**, **cPanel**, atau **aaPanel**.

1. Upload semua isi project ke hosting.
2. Arahkan domain / subdomain ke folder `public/`.
3. Buat database MySQL / MariaDB.
4. Pastikan folder writable sudah benar.
5. Buka `https://domain-anda/install.php`.
6. Isi wizard installer sampai selesai.

Panduan lengkap: [`docs/deploy/INSTALL_SHARED_HOSTING.md`](docs/deploy/INSTALL_SHARED_HOSTING.md)

### Opsi 2 — Install di localhost XAMPP
Cocok untuk pengujian atau pengembangan lokal.

1. Simpan project ke `C:\xampp\htdocs\nama-folder-project`.
2. Jalankan **Apache** dan **MySQL** di XAMPP.
3. Buat database baru di phpMyAdmin.
4. Buka `http://localhost/nama-folder-project/install.php`.
5. Isi data koneksi database lalu selesaikan instalasi.

Panduan lengkap: [`docs/deploy/INSTALL_LOCAL_XAMPP.md`](docs/deploy/INSTALL_LOCAL_XAMPP.md)

---

## Urutan instalasi yang rapi

### A. Instalasi di hosting

#### 1) Upload source code
Upload seluruh isi repository ke hosting melalui File Manager, FTP, atau panel server.

#### 2) Atur document root
Direkomendasikan agar domain atau subdomain mengarah ke:

```text
/public
```

#### 3) Buat database
Siapkan data berikut:
- host database
- port database
- nama database
- username
- password

#### 4) Atur permission folder
Folder berikut harus bisa ditulis oleh server:
- `app/config`
- `storage`
- `storage/logs`
- `storage/imports`
- `storage/backups`
- `storage/bank_reconciliations`
- `storage/journal_attachments`
- `public/uploads/profiles`
- `public/uploads/signatures`

Permission umum:
- folder: `755` atau `775`
- file: `644`

#### 5) Jalankan installer
Buka alamat berikut di browser:

```text
https://domain-anda/install.php
```

#### 6) Lengkapi data awal
Setelah instalasi selesai, lanjutkan dengan urutan ini:
1. isi profil BUMDes
2. isi unit usaha
3. isi atau import COA
4. buat periode akuntansi
5. input jurnal atau import template jurnal

---

### B. Instalasi di localhost XAMPP

#### 1) Pindahkan project ke `htdocs`
Contoh:

```text
C:\xampp\htdocs\akuntansi-bumdes
```

#### 2) Jalankan layanan XAMPP
Aktifkan:
- Apache
- MySQL

#### 3) Buat database kosong
Buka phpMyAdmin:

```text
http://localhost/phpmyadmin
```

Contoh database:

```text
bumdes_akuntansi
```

#### 4) Jalankan wizard install
Buka:

```text
http://localhost/akuntansi-bumdes/install.php
```

Contoh isian umum XAMPP:
- Host: `127.0.0.1` atau `localhost`
- Port: `3306`
- Database: `bumdes_akuntansi`
- Username: `root`
- Password: kosongkan jika default XAMPP belum diubah
- App URL: `http://localhost/akuntansi-bumdes`

#### 5) Login dan mulai setup data
Setelah installer selesai:
1. login dengan akun admin
2. isi master data awal
3. buat periode
4. uji input jurnal
5. uji import template jurnal

---

## Instalasi ulang

Kalau aplikasi sebelumnya sudah pernah terpasang dan ingin install ulang:

1. hapus file berikut:
   - `app/config/generated.php`
   - `storage/installed.lock`
2. kosongkan database lama atau buat database baru
3. jalankan lagi `install.php`

---

## Troubleshooting singkat

| Masalah | Penyebab umum | Solusi |
|---|---|---|
| Installer bilang aplikasi sudah terpasang | File hasil install lama masih ada | Hapus `app/config/generated.php` dan `storage/installed.lock` |
| Upload logo / tanda tangan gagal | Folder upload belum writable | Periksa permission `public/uploads/profiles` dan `public/uploads/signatures` |
| Import jurnal gagal | Template lama, format salah, atau PHP zip belum aktif | Unduh template terbaru, cek format data, aktifkan ekstensi `zip` |
| Halaman error setelah deploy | Document root salah atau config belum lengkap | Arahkan domain ke `public/` dan cek hasil wizard installer |


## Dokumentasi tambahan

- [`docs/deploy/INSTALL_SHARED_HOSTING.md`](docs/deploy/INSTALL_SHARED_HOSTING.md)
- [`docs/deploy/INSTALL_LOCAL_XAMPP.md`](docs/deploy/INSTALL_LOCAL_XAMPP.md)
- [`docs/deploy/PRODUCTION_UPDATE_GUIDE.md`](docs/deploy/PRODUCTION_UPDATE_GUIDE.md)
- [`docs/releases/RELEASE_TAGGING.md`](docs/releases/RELEASE_TAGGING.md)
- [`CHANGELOG.md`](CHANGELOG.md)

---

## Catatan repository

- File hasil install seperti `app/config/generated.php` dan `storage/installed.lock` **tidak perlu disimpan di repository**.
- Folder upload, logs, imports, dan backup sudah diatur melalui `.gitignore` agar repository tetap bersih.
- Jika ingin mengganti screenshot preview, cukup timpa file di folder `docs/images/` lalu commit ulang.
