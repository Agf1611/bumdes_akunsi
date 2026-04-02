# Audit Fix - Installer Unbuffered Query

Tanggal: 2026-04-02

## Masalah
Saat instalasi baru, import SQL gagal pada file `patch_stage18_asset_qty_columns_safe.sql` dengan pesan:

`Cannot execute queries while other unbuffered queries are active`

## Akar masalah
Beberapa patch SQL installer menjalankan statement dinamis seperti `EXECUTE stmt_xxx;`.
Ketika statement dinamis itu berisi `SELECT 1`, MySQL mengembalikan result set.
Versi installer sebelumnya mengeksekusi semua statement dengan `PDO::exec()`, sehingga result set dari statement tertentu tidak selalu dikonsumsi/ditutup dengan aman.
Akibatnya statement berikutnya gagal karena masih ada unbuffered query yang aktif.

## Perbaikan
- Menambahkan `PDO::MYSQL_ATTR_USE_BUFFERED_QUERY` saat membuat koneksi installer.
- Menambahkan runner SQL yang membedakan statement yang berpotensi mengembalikan result set (`SELECT`, `SHOW`, `DESCRIBE`, `EXPLAIN`, `CALL`, `EXECUTE`).
- Untuk statement yang mengembalikan result set, installer sekarang memakai `PDO::query()` lalu mengonsumsi seluruh rowset dan menutup cursor.

## File yang diubah
- `app/install/Installer.php`

## Dampak
- Installer lebih stabil saat mengimpor patch SQL bertahap.
- Error `2014 Cannot execute queries while other unbuffered queries are active` tidak lagi mengganggu alur instalasi normal.
