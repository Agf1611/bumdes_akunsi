# Panduan GitHub dan Deploy

## 1) Siapkan repository GitHub
- Buat repo baru, misalnya `akuntansi-bumdes`
- Upload seluruh isi project ini
- Jangan commit file instalasi hasil produksi seperti `app/config/generated.php`

## 2) Deploy dari GitHub ke server
Ada 2 cara umum:

### Cara A - Clone langsung di server/VPS
```bash
cd /var/www
git clone https://github.com/USERNAME/akuntansi-bumdes.git
cd akuntansi-bumdes
```
Lalu arahkan web server ke folder `public/`.

### Cara B - Download ZIP dari GitHub lalu upload ke hosting
- Dari GitHub, klik **Code > Download ZIP**
- Upload ZIP ke hosting
- Extract di folder aplikasi
- Pastikan domain/subdomain mengarah ke folder `public/`

## 3) Setting permission
Folder berikut harus bisa ditulis web server:
- `app/config`
- `storage`
- `storage/logs`
- `storage/imports`
- `storage/backups`
- `storage/journal_attachments`
- `storage/bank_reconciliations`
- `public/uploads/profiles`
- `public/uploads/signatures`

Umumnya cukup `755` atau `775` tergantung hosting.

## 4) Jalankan installer
Buka:
- `https://domain-anda/install.php`

Isi:
- host database
- port database
- nama database
- username database
- password database
- nama admin
- email admin
- password admin

Installer akan membuat:
- `app/config/generated.php`
- `storage/installed.lock`

## 5) Sesudah instalasi
- Login sebagai admin
- Lengkapi profil BUMDes
- Import / susun COA
- Buat periode akuntansi aktif
- Mulai input jurnal atau import jurnal

## 6) Update dari GitHub
Saat update source baru:
1. backup database dulu
2. backup folder `public/uploads` dan `storage`
3. pull/update code
4. jangan timpa `app/config/generated.php`
5. jangan hapus `storage/installed.lock` pada server live kecuali memang ingin install ulang

## 7) Jika installer bilang aplikasi sudah terpasang
Untuk install ulang manual, hapus:
- `app/config/generated.php`
- `storage/installed.lock`

## 8) Jika halaman 500 setelah deploy
Cek:
- ekstensi PHP `zip` aktif
- permission folder writable
- document root benar ke `public`
- versi PHP minimal 8.1
- log di `storage/logs`
