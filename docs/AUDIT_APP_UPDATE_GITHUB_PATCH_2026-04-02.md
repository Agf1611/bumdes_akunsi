# Audit dan Implementasi Menu Update Aplikasi GitHub

## Tujuan
Menambahkan menu **Update Aplikasi** yang mengambil source dari repository GitHub publik, dengan syarat wajib backup database otomatis sebelum update dan proses update hanya menimpa file yang memang berubah.

## Temuan audit sebelum implementasi
1. Belum ada modul update aplikasi berbasis GitHub.
2. Backup database sudah ada, tetapi belum terhubung ke alur update source.
3. Belum ada pelacakan commit/versi lokal serta laporan audit update yang bisa diunduh.
4. Belum ada perlindungan agar file runtime seperti `storage/`, `public/uploads/`, dan `app/config/generated.php` tidak tertimpa saat update.

## Implementasi yang ditambahkan
- Menu baru: **Pengaturan > Update Aplikasi**
- Halaman cek update GitHub dan jalankan update
- Backup database otomatis sebelum update
- Update hanya untuk file yang berbeda
- File lama dibackup ke `storage/update_manager/file_backups/`
- Laporan audit cek/update ke `storage/update_manager/reports/*.txt`
- Kandidat file lokal yang tidak ada di GitHub hanya dilaporkan, tidak dihapus otomatis
- Rollback file otomatis jika proses copy update gagal di tengah jalan

## File yang ditambahkan / diubah
- `app/config/app.php`
- `app/routes/web.php`
- `app/views/layouts/sidebar.php`
- `app/views/layouts/topbar.php`
- `app/modules/updates/UpdateController.php`
- `app/modules/updates/GitHubAppUpdaterService.php`
- `app/modules/updates/views/index.php`
- `VERSION`

## Aturan update yang dipasang
### Tidak disentuh saat update
- `storage/`
- `public/uploads/`
- `app/config/generated.php`
- `.github/`
- `.git/`
- `docs/`
- `scripts/`
- file README dan file audit/test lokal

### Disentuh saat update
- `app/`
- `public/assets/`
- `public/templates/`
- file root runtime yang relevan seperti `index.php`, `bootstrap.php`, `install.php`, `.htaccess`, dan `VERSION`

## Catatan penting
- Repo GitHub harus publik, atau nanti perlu token tambahan jika private.
- Server perlu mendukung **cURL** atau `allow_url_fopen` untuk download paket GitHub.
- Server perlu mendukung **PharData** atau **ZipArchive** untuk ekstrak arsip update.
- Update modul ini sengaja **tidak menghapus file lokal yang hilang di repo** agar aman untuk patch manual di server.
