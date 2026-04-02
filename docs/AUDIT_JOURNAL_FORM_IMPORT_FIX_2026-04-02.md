# Audit Form Tambah Jurnal & Import Jurnal

Tanggal audit: 2026-04-02

## Ringkasan temuan

### 1) Import jurnal gagal walaupun memakai template resmi
Akar masalah yang ditemukan:
- `journal_date` dari file Excel sering terbaca sebagai **serial date Excel** (mis. `46019`) saat user memilih tanggal langsung di Excel.
- parser import lama hanya menerima tanggal mentah `YYYY-MM-DD`, sehingga file template resmi sering ditolak walaupun secara tampilan di Excel sudah benar.
- parser nominal lama terlalu ketat dan mudah gagal pada format lokal seperti `500.000` atau `500.000,50`.
- pembaca XLSX selalu mengasumsikan worksheet pertama berada di `sheet1.xml`; ini berisiko gagal pada file yang pernah diedit ulang.
- jika server belum mengaktifkan `php-zip`, pesan error lama kurang jelas.

### 2) Form tambah jurnal tampil tidak rapi / terlihat berbeda
Akar masalah yang ditemukan:
- blok **Transaksi Cepat** di form create terlalu besar dan deskripsi template memanjang, sehingga di layout hosting tampil seperti strip teks tipis di bagian atas.
- form belum memberi penjelasan tegas bahwa **kolom inti form sama dengan kolom inti template import jurnal**.

### 3) Audit hasil error import belum nyaman dipakai
Akar masalah yang ditemukan:
- URL file audit sebelumnya hanya masuk ke daftar pesan error sebagai teks biasa, bukan tombol unduh yang jelas.

## File yang diperbaiki
- `app/helpers/import_helper.php`
- `app/core/XlsxReader.php`
- `app/modules/imports/ImportService.php`
- `app/modules/imports/ImportController.php`
- `app/modules/journals/views/index.php`
- `app/modules/journals/views/form.php`
- `public/templates/import_journal_template.xlsx`

## Perubahan utama
- Import jurnal sekarang menerima tanggal dalam bentuk:
  - `YYYY-MM-DD`
  - `DD/MM/YYYY`
  - tanggal Excel hasil pilih dari cell date
- Nominal import sekarang menerima format:
  - `500000`
  - `500.000`
  - `500,000`
  - `500000.50`
  - `500.000,50`
- Reader XLSX sekarang lebih aman membaca worksheet pertama berdasarkan metadata workbook, bukan asumsi `sheet1.xml` saja.
- Jika server belum aktif `php-zip`, pesan error sekarang jelas menyebut penyebabnya.
- Form tambah jurnal dirapikan menjadi selector template cepat berbentuk pill/button agar tidak pecah layout.
- Ditambahkan catatan bahwa form tambah jurnal dan template import memakai kolom inti yang sama.
- Halaman jurnal sekarang menampilkan tombol **Unduh File Audit Import** saat validasi import gagal.
- Template import jurnal diperbarui:
  - sheet `template` aman untuk diisi langsung
  - sheet `petunjuk` berisi aturan pengisian dan contoh
  - contoh tidak lagi diletakkan di sheet utama agar tidak ikut terimport tanpa sengaja

## Catatan server
Jika setelah patch ini import masih gagal dengan pesan tentang `ZipArchive` atau `php-zip`, berarti masalah ada di konfigurasi PHP hosting dan bukan di template jurnal.
