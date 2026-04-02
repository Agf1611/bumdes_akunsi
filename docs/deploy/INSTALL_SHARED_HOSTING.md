# Instalasi di Shared Hosting

## Sebelum mulai
Pastikan hosting mendukung:
- PHP 8.1+
- MySQL / MariaDB
- ekstensi PHP: `pdo_mysql`, `mbstring`, `zip`, `fileinfo`, `simplexml`

## Langkah pemasangan
1. Upload source code ke folder aplikasi.
2. Jika domain diarahkan ke `public_html`, Anda bisa:
   - menaruh isi source di subfolder aplikasi, lalu arahkan document root ke folder `public`, atau
   - upload seluruh source ke root aplikasi dan gunakan `public/index.php` sebagai entry point.
3. Buat database MySQL kosong dari cPanel / panel hosting.
4. Pastikan folder berikut bisa ditulis server:
   - `app/config`
   - `storage`
   - `storage/logs`
   - `storage/imports`
   - `storage/backups`
   - `storage/bank_reconciliations`
   - `storage/journal_attachments`
   - `public/uploads/profiles`
   - `public/uploads/signatures`
5. Akses `install.php` dari browser.
6. Isi data database dan akun admin.
7. Setelah berhasil, login ke aplikasi.

## Jika upload logo / tanda tangan gagal
- cek permission folder upload
- ubah permission folder ke `755` atau `775`
- jika perlu sesuaikan owner folder dengan user web server hosting

## Jika installer menyatakan aplikasi sudah terpasang
Hapus manual file:
- `storage/installed.lock`
- `app/config/generated.php`

## Rekomendasi keamanan
- hapus file backup lama secara berkala
- jangan simpan kredensial database di dokumentasi publik
- gunakan HTTPS untuk domain produksi
