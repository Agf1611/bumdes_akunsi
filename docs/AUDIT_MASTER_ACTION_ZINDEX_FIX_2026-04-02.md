# Audit Menu Aksi Data Master - 2026-04-02

## Keluhan
Pada menu data master (terutama Master Aset dan halaman lain yang memakai partial aksi yang sama), saat tombol **Aksi** dibuka lalu item seperti **Edit** dipilih, yang terkena klik justru tombol atau baris di belakangnya.

## Akar masalah
Masalah bukan pada link `Edit` atau route, tetapi pada **stacking / z-index** di kolom aksi yang dibuat `position: sticky`.

Kondisi sebelumnya:
- setiap sel kolom aksi memakai `.table-action-col { position: sticky; z-index: 2; }`
- dropdown menu memang punya `z-index` tinggi, tetapi masih berada di dalam parent cell dengan `z-index` rendah
- saat dropdown baris atas terbuka, sticky cell dari baris bawah masih bisa berada di atas area dropdown
- akibatnya, klik pada item dropdown bentrok dengan tombol **Aksi** baris di bawah

## File yang diperbaiki
- `app/views/partials/table_action_menu.php`

## Perbaikan yang diterapkan
1. Menambahkan class dinamis saat menu terbuka:
   - `.table-action-col.is-open`
   - `tr.table-action-row-open`
2. Menaikkan `z-index` parent cell yang sedang aktif agar dropdown berada di atas baris lain.
3. Menambahkan `isolation: isolate` pada wrapper menu supaya layer dropdown lebih stabil.
4. Mengubah JavaScript open/close menu agar ikut menandai parent `td` dan `tr`, bukan hanya wrapper menu.
5. Menjaga perilaku lama:
   - hanya satu menu terbuka pada satu waktu
   - klik di luar tetap menutup menu
   - tombol aksi tetap solid / tidak transparan

## Dampak perbaikan
Perbaikan ini berlaku untuk semua halaman data master yang memakai partial aksi yang sama, termasuk:
- Master Aset
- Kategori Aset
- COA
- Unit Usaha
- Referensi Jurnal
- Periode Akuntansi

## Catatan
Tidak ada perubahan database, route, controller, atau model.
Perbaikan murni pada UI layer dan interaksi dropdown aksi.
