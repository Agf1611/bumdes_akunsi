# Implementasi Tahap 2 - Backup Database dan Neraca Pembanding

Tanggal: 2026-03-15

## Fokus tahap ini
Tahap 2 dikerjakan dengan prinsip aman dan bertahap, tanpa mengubah logika transaksi inti. Fitur yang ditambahkan hanya yang berdampak besar namun minim risiko:

1. **Backup Database dari UI**
2. **Neraca Komparatif / pembanding periode sebelumnya**

## 1. Backup Database dari UI
Menu baru:
- `Pengaturan -> Backup Database`
- URL: `/backups`

Fitur yang tersedia:
- tombol **Buat Backup Sekarang**
- daftar file backup yang tersimpan di `storage/backups`
- tombol **Unduh** file backup
- tombol **Hapus** file backup dari server
- informasi status koneksi database dan writable folder backup
- checksum SHA1 agar file backup lebih mudah diverifikasi

Teknis backup:
- format file: `.sql`
- struktur tabel diekspor dengan `SHOW CREATE TABLE`
- data tabel diekspor sebagai `INSERT INTO`
- output aman untuk instalasi yang memakai database MySQL aplikasi ini

Catatan pemasangan:
- **tidak perlu build ulang**
- **tidak perlu patch SQL database**
- pastikan folder `storage/backups` bisa ditulis server

## 2. Neraca Komparatif
Modul yang diperbarui:
- `Laporan -> Neraca`
- halaman layar, print, dan PDF

Perubahan utama:
- jika user memilih **periode akuntansi**, sistem akan otomatis mencari **periode sebelumnya**
- laporan neraca menampilkan kolom **Pembanding**
- total aset, liabilitas, ekuitas, dan total liabilitas + ekuitas ikut memiliki nilai pembanding
- kolom pembanding juga ikut tampil pada:
  - halaman layar
  - halaman print
  - export PDF

Tujuan:
- hasil laporan lebih formal
- lebih enak dibaca pihak desa / kecamatan / DPMD
- lebih mudah melihat kenaikan / penurunan posisi keuangan

## File baru
- `app/modules/backups/BackupController.php`
- `app/modules/backups/BackupService.php`
- `app/modules/backups/views/index.php`

## File yang diubah
- `app/helpers/common_helper.php`
- `app/routes/web.php`
- `app/views/layouts/sidebar.php`
- `app/views/layouts/topbar.php`
- `app/helpers/report_layout_helper.php`
- `app/install/Installer.php`
- `app/helpers/balance_sheet_helper.php`
- `app/modules/balance_sheet/BalanceSheetModel.php`
- `app/modules/balance_sheet/BalanceSheetController.php`
- `app/modules/balance_sheet/views/index.php`
- `app/modules/balance_sheet/views/print.php`

## Pengujian yang dilakukan
1. Lint seluruh file PHP pada folder `app` -> **lulus**
2. Render smoke test halaman:
   - Backup Database
   - Neraca layar dengan pembanding
   - Neraca print dengan pembanding
   -> **lulus**
3. Verifikasi route dan menu baru -> **tersambung**

## Catatan jujur
Di lingkungan kerja ini driver PDO MySQL tidak tersedia, jadi saya **tidak bisa melakukan uji backup database end-to-end ke MySQL nyata**. Yang sudah saya validasi adalah:
- syntax semua file PHP
- render view tanpa error
- route/controller/view baru tersambung
- struktur kode backup aman untuk runtime MySQL aplikasi

Untuk server Anda, setelah upload lakukan cek cepat:
1. buka menu Backup Database
2. klik Buat Backup Sekarang
3. unduh file `.sql`
4. buka laporan Neraca pada periode yang punya periode sebelumnya
5. cek kolom Pembanding di layar, print, dan PDF
