# Release GitHub Ready - 2026-04-02

Paket ini disiapkan ulang agar aman dipakai sebagai repository GitHub dan instalasi baru.

## Pembersihan yang dilakukan
- hapus kredensial database produksi dari `app/config/generated.php`
- hapus status aplikasi terpasang dari `storage/installed.lock`
- hapus file upload/logo lama di `public/uploads/profiles`
- hapus file duplikat patch/lokal yang tidak dipakai aplikasi runtime

## Catatan
File hasil instalasi akan dibuat ulang otomatis oleh installer saat deploy:
- `app/config/generated.php`
- `storage/installed.lock`
