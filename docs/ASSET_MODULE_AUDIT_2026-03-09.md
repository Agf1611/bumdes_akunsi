# Audit & Implementasi Modul Aset BUMDes

## Ringkas
Modul aset ditambahkan ke aplikasi PHP Native yang sudah ada tanpa mengubah modul lain dari nol.

## Desain utama
- Kategori aset universal.
- Aset terhubung ke unit usaha (`business_units`) secara opsional.
- Mendukung gabungan semua unit usaha.
- Penyusutan garis lurus per bulan disimpan pada tabel `asset_depreciations`.
- Mutasi aset disimpan pada tabel `asset_mutations`.
- Integrasi jurnal dilakukan secara aman sebagai **tautan manual** melalui `linked_journal_id`.
- Aset biologis dicatat pada kategori khusus `BIOLOGICAL` dan secara default **tidak disusutkan otomatis**.

## File baru
- `app/helpers/asset_helper.php`
- `app/modules/assets/AssetController.php`
- `app/modules/assets/AssetModel.php`
- `app/modules/assets/views/index.php`
- `app/modules/assets/views/form.php`
- `app/modules/assets/views/detail.php`
- `app/modules/assets/views/categories.php`
- `app/modules/assets/views/category_form.php`
- `app/modules/assets/views/depreciation.php`
- `app/modules/assets/views/reports.php`
- `app/modules/assets/views/print.php`
- `app/modules/assets/views/card_print.php`
- `database/asset_module.sql`

## File diubah
- `app/bootstrap.php`
- `app/routes/web.php`
- `app/views/layouts/sidebar.php`
- `app/views/layouts/topbar.php`

## Uji cepat
- `php -l` pada semua file modul aset: lolos.
- Tidak ada perubahan ke database, route, atau modul lain selain integrasi menu, helper, dan route aset.

## Catatan
- Jalankan SQL `database/asset_module.sql` dulu sebelum membuka menu aset.
- Bila diperlukan auto-journal di masa depan, gunakan `linked_journal_id` dan mapping akun kategori sebagai tahap lanjutan, bukan bagian patch aman ini.
