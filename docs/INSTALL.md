# Cara Pemasangan

## 1. Upload project
Upload seluruh folder project ke hosting atau server lokal.

## 2. Atur document root
### Opsi terbaik
Arahkan document root domain/subdomain ke folder `public`.

Jika memakai opsi ini:
- ubah `app/config/app.php`
- set `public_path_prefix` menjadi `''`
- sesuaikan `url` dengan domain Anda

Contoh:
```php
'url' => 'https://domainanda.com',
'public_path_prefix' => '',
```

### Opsi shared hosting biasa
Jika document root tidak bisa diarahkan ke `public`, biarkan root proyek sebagai document root.

Jika memakai opsi ini:
- biarkan `index.php` di root aktif
- biarkan `public_path_prefix` bernilai `'/public'`
- sesuaikan `url` dengan domain/subfolder Anda

Contoh:
```php
'url' => 'https://domainanda.com/app-bumdes',
'public_path_prefix' => '/public',
```

## 3. Konfigurasi database
Edit file `app/config/database.php` sesuai database hosting.

## 4. Hak akses folder
Pastikan folder berikut bisa ditulis server:
- `app/storage/logs`

## 5. Login awal
- Username: `admin`
- Password: `Admin123!`

Segera ganti mekanisme login ini ke versi database pada tahap modul user berikutnya.
