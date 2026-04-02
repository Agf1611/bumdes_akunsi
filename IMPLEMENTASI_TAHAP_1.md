# Implementasi Tahap 1 - Audit Trail, CaLK, dan Perapihan Print

Tanggal: 2026-03-15

## Fitur yang ditambahkan

### 1. Audit Trail / Log Aktivitas
Menu baru:
- `Pengaturan -> Audit Trail`
- URL: `/audit-logs`

Pencatatan aktivitas yang sudah aktif:
- login berhasil / gagal / diblokir / logout
- tambah / ubah / hapus jurnal umum
- tambah / ubah / aktifkan / nonaktifkan akun pengguna
- tambah / ubah / set aktif / tutup / buka kembali periode akuntansi
- ubah profil BUMDes dan pengaturan dokumen

File database baru:
- `database/audit_module.sql`
- `database/patch_audit_logs.sql`
- `database/patch_stage1_audit_calk.sql`

### 2. Catatan atas Laporan Keuangan (CaLK)
Menu baru:
- `Laporan -> CaLK`
- URL: `/financial-notes`
- Cetak: `/financial-notes/print`
- PDF: `/financial-notes/pdf`

Isi laporan CaLK yang dibuat otomatis dari data akuntansi:
- Informasi umum BUMDes
- Dasar penyusunan dan kebijakan akuntansi
- Kas dan setara kas
- Piutang
- Persediaan
- Aset tetap dan akumulasi penyusutan
- Liabilitas dan ekuitas
- Kinerja pendapatan dan beban

### 3. Perapihan identitas print laporan
Ditambahkan ke header print HTML dan PDF:
- desa / kecamatan / kabupaten / provinsi
- nomor badan hukum
- NIB
- NPWP
- metadata cetak (tanggal/jam dan user pencetak)

### 4. Perluasan profil BUMDes
Field profil baru:
- desa
- kecamatan
- kabupaten
- provinsi
- no. badan hukum
- NIB
- NPWP

### 5. Perbaikan bug CSV aset untuk PHP 8.4
File diperbaiki:
- `app/modules/assets/AssetController.php`

Perbaikan:
- `fputcsv()` diberi parameter `escape`
- `fgetcsv()` diberi parameter `escape`
- BOM UTF-8 dibetulkan
- pembacaan header CSV dibuat lebih kompatibel

## File utama yang ditambahkan
- `app/helpers/audit_helper.php`
- `app/helpers/financial_notes_helper.php`
- `app/modules/audit_logs/AuditLogController.php`
- `app/modules/audit_logs/AuditLogModel.php`
- `app/modules/audit_logs/views/index.php`
- `app/modules/financial_notes/FinancialNotesController.php`
- `app/modules/financial_notes/FinancialNotesModel.php`
- `app/modules/financial_notes/views/index.php`
- `app/modules/financial_notes/views/print.php`
- `database/audit_module.sql`
- `database/patch_audit_logs.sql`
- `database/patch_profile_legal_identity.sql`
- `database/patch_stage1_audit_calk.sql`

## File penting yang diubah
- `app/bootstrap.php`
- `app/routes/web.php`
- `app/helpers/profile_helper.php`
- `app/helpers/report_layout_helper.php`
- `app/helpers/report_pdf_helper.php`
- `app/views/layouts/print.php`
- `app/views/layouts/sidebar.php`
- `app/modules/settings/ProfileModel.php`
- `app/modules/settings/ProfileController.php`
- `app/modules/settings/views/profile_form.php`
- `app/modules/auth/AuthController.php`
- `app/modules/journals/JournalController.php`
- `app/modules/periods/PeriodController.php`
- `app/modules/user_accounts/UserAccountController.php`
- `app/modules/assets/AssetController.php`
- `public/assets/css/print-professional.css`
- `app/install/Installer.php`
- `database/profile_module.sql`

## Pengujian yang sudah dilakukan
1. Lint seluruh file PHP pada folder `app`
2. Smoke test bootstrap aplikasi (`require app/bootstrap.php`)
3. Smoke test render view:
   - halaman CaLK
   - halaman cetak CaLK
   - halaman Audit Trail
4. Verifikasi daftar SQL installer memuat modul baru

## Cara pasang untuk instalasi yang SUDAH berjalan
1. Upload file patch ke source server
2. Jalankan SQL berikut sekali:
   - `database/patch_stage1_audit_calk.sql`
3. Login sebagai admin
4. Lengkapi data profil BUMDes yang baru di menu pengaturan profil
5. Cek menu baru:
   - `Laporan -> CaLK`
   - `Pengaturan -> Audit Trail`

## Cara pasang untuk instalasi BARU
Gunakan paket full source. Installer sudah diperbarui sehingga tabel audit dan field profil baru ikut dibuat.

## Catatan
Tahap 1 ini sengaja dibuat aman dan bertahap. Belum ada perubahan besar pada logika akuntansi inti, sehingga risiko merusak transaksi/jurnal yang sudah berjalan dibuat sekecil mungkin.
