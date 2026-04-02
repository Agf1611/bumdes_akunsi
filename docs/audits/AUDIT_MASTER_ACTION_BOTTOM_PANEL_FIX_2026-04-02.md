# Audit Fix - Action Menu Bottom Row (2026-04-02)

## Keluhan
Dropdown **Aksi** pada baris paling bawah daftar data master tidak terlihat / terpotong.

## Akar Masalah
1. Beberapa tabel master masih memakai wrapper `.table-responsive` biasa, sehingga `overflow-y` tetap tersembunyi.
2. Panel dropdown selalu dibuka ke bawah. Pada baris terakhir, panel keluar area viewport / area tabel dan terlihat seperti tidak muncul.

## Perbaikan
- Wrapper tabel periode diubah ke `.table-responsive coa-table-wrapper` supaya `overflow-y` visible.
- Partial `table_action_menu.php` diperbarui agar dropdown otomatis **membuka ke atas** bila ruang bawah tidak cukup.
- Posisi dropdown akan dihitung ulang saat buka, resize, dan scroll.

## File yang Diubah
- `app/views/partials/table_action_menu.php`
- `app/modules/periods/views/index.php`

## Dampak
- Dropdown aksi pada baris paling bawah kini tetap muncul.
- Berlaku ke semua menu data master yang memakai partial aksi yang sama.
