# Instalasi Lokal (XAMPP / Laragon / localhost)

## Kebutuhan minimum
- PHP 8.1+
- MySQL / MariaDB
- ekstensi PHP aktif: `pdo`, `pdo_mysql`, `mbstring`, `json`, `libxml`, `simplexml`, `fileinfo`, `zip`

## Langkah pemasangan
1. Copy source code ke folder web server, misalnya:
   - XAMPP: `htdocs/bumdes-akuntansi`
2. Buat database kosong dari phpMyAdmin.
3. Pastikan folder berikut writable:
   - `app/config`
   - `storage`
   - `storage/logs`
   - `storage/imports`
   - `storage/backups`
   - `storage/bank_reconciliations`
   - `storage/journal_attachments`
   - `public/uploads/profiles`
   - `public/uploads/signatures`
4. Buka browser:
   - `http://localhost/bumdes-akuntansi/install.php`
5. Isi form installer:
   - host database
   - port
   - nama database
   - username
   - password
   - URL aplikasi
   - akun admin pertama
6. Klik **Install**.
7. Setelah berhasil, buka login dan masuk memakai akun admin.

## Setelah instalasi
1. Isi **Profil BUMDes**
2. Isi **Unit Usaha** bila diperlukan
3. Isi atau import **COA**
4. Buat **Periode Akuntansi**
5. Mulai input **Jurnal**

## Catatan
- Untuk instalasi ulang, hapus:
  - `storage/installed.lock`
  - `app/config/generated.php`
- Lalu kosongkan database sebelum menjalankan installer lagi.
