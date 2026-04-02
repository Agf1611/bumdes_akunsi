# Audit Perbaikan Menu Aksi Data Master

Tanggal: 2026-04-02

## Masalah yang ditemukan

Menu aksi data master menggunakan elemen native `<details><summary>` di dalam kolom tabel yang sticky.
Pada beberapa browser/layout ini membuat panel aksi tertutup sendiri atau kembali ke state semula saat item seperti Edit diklik, sehingga terasa seperti tombol tidak bisa dipilih.

## Akar masalah

1. Toggle masih mengandalkan perilaku native `<details>` yang tidak stabil di kombinasi:
   - kolom sticky kanan
   - panel absolute dropdown
   - tabel responsif yang bisa digeser
2. State buka/tutup menu bercampur antara browser native dan JavaScript custom.
3. Akibatnya klik ke item menu bisa bentrok dengan toggle summary dan menu menutup lagi.

## Perbaikan yang diterapkan

1. Semua menu aksi data master diubah dari:
   - `<details><summary>...`
   menjadi
   - tombol biasa + panel dropdown custom.
2. JavaScript menu aksi diperbarui agar:
   - hanya satu menu terbuka dalam satu waktu
   - klik tombol Aksi membuka/menutup menu secara stabil
   - klik di luar menu menutup panel
   - tombol Edit/Hapus/Aktifkan tetap bisa diklik normal
3. Gaya visual tombol dan panel tetap dipertahankan.

## File yang diubah

- `app/views/partials/table_action_menu.php`
- `app/modules/business_units/views/index.php`
- `app/modules/periods/views/index.php`
- `app/modules/reference_masters/views/index.php`
- `app/modules/assets/views/index.php`
- `app/modules/assets/views/categories.php`
- `app/modules/coa/views/index.php`

## Catatan

Perbaikan ini fokus ke menu aksi pada data master. Menu aksi khusus di daftar jurnal tidak diubah dalam patch ini.
