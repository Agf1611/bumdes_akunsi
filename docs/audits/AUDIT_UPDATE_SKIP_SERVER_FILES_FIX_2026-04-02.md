# Audit Update Skip Server Files Fix - 2026-04-02

## Masalah
Update aplikasi gagal saat mencoba menimpa file konfigurasi server seperti `user.ini` / `.user.ini`.
Di shared hosting atau panel tertentu, file ini sering read-only, dibuat otomatis oleh server, atau memang tidak boleh diganti oleh updater aplikasi.

## Dampak
- Proses update berhenti di tengah jalan.
- Rollback file lokal dijalankan.
- Update dari GitHub gagal walau file aplikasi lain sebenarnya valid.

## Perbaikan
File updater diperbarui agar mengecualikan file konfigurasi server dan environment dari proses cek maupun salin update, termasuk:
- `user.ini`
- `.user.ini`
- `php.ini`
- `.env`
- `.env.local`

## File yang diubah
- `app/modules/updates/GitHubAppUpdaterService.php`

## Catatan
Perbaikan ini tetap menjaga konsep update incremental: hanya file aplikasi yang aman dan memang relevan yang ditimpa.
File runtime, upload, storage, config hasil instalasi, dan file environment/server tetap tidak disentuh.
