# Upgrade Modul Aset 2026-03-09

Fokus upgrade:
- tambah field sumber dana dan detail sumber dana
- tambah import aset massal (.csv / .xlsx)
- tambah unduh template CSV aset
- tambah export data aset CSV
- perbaiki tampilan form aset, daftar aset, laporan aset, kartu aset
- tetap aman untuk unit usaha WIFI dan TERNAK DOMBA

## File penting
- database/asset_module.sql
- app/helpers/asset_helper.php
- app/modules/assets/AssetController.php
- app/modules/assets/AssetModel.php
- app/modules/assets/views/index.php
- app/modules/assets/views/form.php
- app/modules/assets/views/detail.php
- app/modules/assets/views/reports.php
- app/modules/assets/views/print.php
- app/modules/assets/views/card_print.php
- app/routes/web.php

## Langkah pasang
1. Backup project lama.
2. Upload semua file hasil patch.
3. Jalankan SQL `database/asset_module.sql`.
4. Hard refresh browser.

## Catatan import
- Template memakai kolom `category_code` dan `business_unit_code`.
- Jika `asset_code` sudah ada, sistem update data lama.
- Jika `asset_code` belum ada, sistem membuat aset baru.
