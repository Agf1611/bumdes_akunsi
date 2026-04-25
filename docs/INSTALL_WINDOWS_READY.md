# Instalasi Windows Localhost (siap pakai)

## Paket ini untuk apa
Paket ini disiapkan khusus supaya pemasangan di Windows localhost lebih mudah, terutama untuk **XAMPP**.

## Opsi tercepat
1. Install **XAMPP**
2. Nyalakan **Apache** dan **MySQL**
3. Extract paket ini
4. Jalankan `INSTALL_WINDOWS_XAMPP.bat`
   atau `tools/windows/install-xampp.bat`
5. Browser akan diarahkan ke:
   - `http://localhost/bumdes-akuntansi/install.php`
6. Selesaikan form installer

## Nilai default yang cocok untuk XAMPP
- Host DB: `127.0.0.1`
- Port DB: `3306`
- Nama DB: `bumdes_akuntansi`
- User DB: `root`
- Password DB: kosong

## Yang sudah disiapkan dalam paket ini
- konfigurasi `config/app.php` diubah agar URL otomatis mengikuti host aktif
- batch installer Windows: `tools/windows/install-xampp.bat`
- batch reset lokal: `tools/windows/reset-local-install.bat`
- dokumentasi cepat: `README_WINDOWS_LOCALHOST.txt`

## Jika database otomatis gagal dibuat
Beberapa instalasi XAMPP punya password root berbeda. Kalau itu terjadi:
1. buka phpMyAdmin
2. buat database kosong bernama `bumdes_akuntansi`
3. lanjutkan installer dari browser

## Jika ingin instal ulang
1. jalankan `tools/windows/reset-local-install.bat`
2. kosongkan database
3. buka lagi `install.php`
