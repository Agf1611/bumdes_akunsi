# Audit & Implementasi Sinkron Aset - Jurnal - Snapshot 2026-03-10

## Ringkasan
Paket ini menambahkan dan/atau merapikan modul aset agar aman dipakai pada aplikasi akuntansi BUMDes berbasis PHP Native.

## Fokus Perubahan
- Modul aset berdiri sendiri tetapi sinkron dengan jurnal untuk transaksi akuntansi.
- Mendukung input/import aset lama sebagai **saldo awal** tanpa memaksa membuat jurnal historis baru.
- Mendukung aset baru dengan **draft/trigger posting jurnal perolehan**.
- Mendukung **posting jurnal penyusutan** per periode.
- Mendukung **snapshot aset tahunan** untuk laporan pembanding tahun sebelumnya.
- Mendukung laporan aset per unit usaha, gabungan, dan pembanding terhadap tanggal lain.

## Tambahan Desain Akuntansi
- `entry_mode`: `OPENING` atau `ACQUISITION`
- `opening_accumulated_depreciation`
- `offset_coa_id` untuk akun lawan perolehan
- mapping akun per kategori aset:
  - akun aset
  - akun akumulasi penyusutan
  - akun beban penyusutan
  - akun laba pelepasan
  - akun rugi pelepasan
- event akuntansi aset (`asset_accounting_events`)
- snapshot nilai aset per tahun (`asset_year_snapshots`)

## Integrasi Jurnal
- Perolehan aset baru dapat diposting ke jurnal dari kartu aset.
- Penyusutan periode dapat diposting massal dari menu penyusutan aset.
- Aset saldo awal tidak dipaksa membuat jurnal perolehan baru.

## Audit Sintaks
Lint seluruh file PHP telah dijalankan dengan `php -l` dan lolos tanpa syntax error.

## Catatan Implementasi
- Paket ini tidak membongkar modul jurnal/laporan yang sudah ada.
- Laporan keuangan akan menangkap dampak aset setelah event akuntansinya diposting ke jurnal.
- Untuk pembanding tahun sebelumnya yang lebih stabil, gunakan fitur snapshot tahunan.
