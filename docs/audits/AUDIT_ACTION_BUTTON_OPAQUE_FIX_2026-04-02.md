# Audit Action Button Opaque Fix - 2026-04-02

## Ringkasan masalah
Tombol **Aksi** pada daftar jurnal dan seluruh menu data master terlihat tembus/transparan karena masih memakai gaya `btn-outline-*` dengan latar transparan. Saat kolom aksi dibuat sticky di sisi kanan tabel, isi kolom angka di belakang tombol masih tampak sehingga UI terlihat rusak.

## Akar masalah
1. `table_action_menu.php` hanya mengatur lebar tombol, belum memaksa latar tombol menjadi solid.
2. `app/modules/journals/views/index.php` punya implementasi CSS menu aksi sendiri dan juga belum memaksa latar tombol solid.
3. Di tampilan mobile jurnal, tombol `Menu` masih memakai `btn-outline-secondary` transparan.

## Perbaikan
- Menambahkan background putih solid, border, warna teks, dan hover state pada `.table-action-trigger`.
- Menambahkan background putih solid, border, warna teks, dan hover state pada `.journal-action-trigger`.
- Menyeragamkan tombol `Menu` mobile jurnal ke gaya aksi utama agar tidak transparan.

## File yang diubah
- `app/views/partials/table_action_menu.php`
- `app/modules/journals/views/index.php`

## Dampak
- Tombol aksi tidak lagi tembus pada jurnal.
- Tombol aksi di seluruh menu data master juga tidak lagi tembus.
- Tidak ada perubahan database atau logika bisnis.
