# Audit Perbaikan Menu Aksi Data Master
Tanggal: 2026-04-02

## Permintaan
Merapikan kolom aksi pada menu data master agar pilihan seperti edit, detail, aktif/nonaktif, cetak kartu, checklist, dan hapus tidak tampil sebagai banyak tombol horizontal. Targetnya mirip modul jurnal: cukup satu tombol **Aksi** lalu pilihan tampil di menu.

## Hasil Audit
Masalah yang ditemukan:
1. Kolom aksi pada beberapa menu data master terlalu lebar karena memakai banyak tombol inline.
2. Pada layar desktop lebar tabel jadi boros dan kolom data utama ikut terdesak.
3. Pada layar kecil / tablet, tombol aksi mudah turun ke baris baru sehingga tinggi baris tabel menjadi tidak efisien.
4. Belum ada komponen menu aksi yang dipakai ulang antar halaman master data.

## Halaman yang diperbaiki
- Master Aset
- Kategori Aset
- Chart of Accounts (COA)
- Master Unit Usaha
- Master Referensi Jurnal
- Periode Akuntansi

## File yang diubah
- `app/views/partials/table_action_menu.php` *(baru)*
- `app/modules/assets/views/index.php`
- `app/modules/assets/views/categories.php`
- `app/modules/coa/views/index.php`
- `app/modules/business_units/views/index.php`
- `app/modules/reference_masters/views/index.php`
- `app/modules/periods/views/index.php`

## Ringkasan Perbaikan
1. Dibuat partial reusable `table_action_menu.php` untuk CSS + JavaScript menu aksi.
2. Kolom aksi pada data master diperkecil menjadi satu tombol **Aksi**.
3. Seluruh tombol aksi penting dipindahkan ke dropdown/menu aksi per baris.
4. Tombol berbahaya seperti hapus tetap diberi gaya merah.
5. Pada layar kecil, panel aksi diposisikan fixed di bawah agar lebih mudah ditekan.

## Uji yang dilakukan
- PHP lint untuk semua file yang diubah: **lolos**
- Audit markup aksi per halaman: **lolos**
- Tidak ada perubahan database: **ya**
- Tidak ada perubahan controller/model: **ya**

## Catatan
Perbaikan ini fokus pada efisiensi kolom dan kerapian UI tabel master data. Tidak mengubah logika bisnis, route, maupun permission yang sudah ada.
