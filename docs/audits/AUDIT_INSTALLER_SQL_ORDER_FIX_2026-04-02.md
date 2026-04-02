# Audit Installer SQL Order Fix — 2026-04-02

## Gejala
Installer gagal saat import SQL dengan error:
- `SQLSTATE[42S22]: Column not found: 1054 Unknown column 'business_unit_id' in 'journal_headers'`

## Akar masalah
Urutan file SQL pada `app/install/Installer.php` salah.

Sebelumnya installer menjalankan:
1. `patch_journal_print_receipt.sql`
2. `patch_multi_unit_profile_signature.sql`

Padahal file `patch_journal_print_receipt.sql` menambahkan kolom `print_template` dengan posisi:
- `AFTER business_unit_id`

Sedangkan kolom `business_unit_id` baru dibuat oleh patch multi unit.
Akibatnya instalasi baru gagal saat import database.

## Perbaikan
- Mengubah urutan SQL installer agar patch multi unit dijalankan lebih dulu.
- Mengganti patch multi unit ke versi aman: `patch_multi_unit_profile_signature_safe.sql`.
- Menambahkan `patch_stage18_asset_qty_columns_safe.sql` ke alur installer agar skema asset konsisten pada fresh install.
- Menambahkan nama file SQL ke pesan error installer agar log berikutnya lebih jelas.

## File yang diubah
- `app/install/Installer.php`

## Dampak
- Fresh install tidak lagi gagal pada tahap import `journal_headers`.
- Log installer lebih mudah ditelusuri bila ada file SQL lain yang bermasalah.
