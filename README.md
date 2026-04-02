# Sistem Akuntansi BUMDes

Repository ini adalah versi **GitHub-ready** dan **install-ready** dari aplikasi akuntansi BUMDes berbasis web.

## Isi utama repository
- `app/` source aplikasi
- `public/` document root web
- `database/` schema, seed, dan patch SQL
- `storage/` data runtime yang tidak boleh di-commit
- `docs/deploy/` panduan install, deploy, dan update produksi
- `docs/guides/` panduan penggunaan dan referensi struktur
- `docs/releases/` catatan rilis, versi, dan tagging
- `docs/audits/` hasil audit dan catatan perbaikan
- `scripts/` utilitas bantu untuk pengecekan dan release

## Versi rilis
- Versi aplikasi: lihat file `VERSION`
- Changelog: lihat `CHANGELOG.md`
- Tag rilis awal yang direkomendasikan: lihat `RELEASE_TAG.txt`

## Fitur/perbaikan penting yang sudah termasuk
- perbaikan form COA edit/create
- import jurnal yang lebih aman untuk template Excel
- pilihan unit usaha saat import jurnal
- aksi massal jurnal: tandai, ubah unit usaha, hapus terpilih
- menu Aksi pada data master lebih ringkas dan stabil
- perbaikan scroll tabel, pagination, dan dropdown aksi sticky

## Instalasi baru
Lihat panduan lengkap di:
- `docs/deploy/INSTALL_GITHUB_DEPLOY.md`
- `docs/deploy/INSTALL_SHARED_HOSTING.md`
- `docs/deploy/INSTALL_LOCAL_XAMPP.md`

Ringkasnya:
1. Upload project ke server atau push ke GitHub.
2. Arahkan document root ke folder `public`.
3. Buat database MySQL kosong.
4. Pastikan folder runtime writeable.
5. Buka `install.php` lalu selesaikan wizard instalasi.

## Update produksi
Gunakan panduan:
- `docs/deploy/PRODUCTION_UPDATE_GUIDE.md`

## Membuat release GitHub
Gunakan panduan:
- `docs/releases/RELEASE_TAGGING.md`

Contoh cepat:
```bash
git init
git add .
git commit -m "chore: initial github-ready release"
git branch -M main
git remote add origin https://github.com/USERNAME/NAMA-REPO.git
git push -u origin main
git tag -a v1.0.0 -m "Initial GitHub-ready release"
git push origin v1.0.0
```

## File yang tidak boleh ikut ter-commit
Sudah ditangani di `.gitignore`, terutama:
- `app/config/generated.php`
- `storage/installed.lock`
- isi folder log, imports, backups, attachments
- isi `public/uploads/*`

## Kebutuhan minimum server
- PHP 8.1+
- MariaDB/MySQL
- ekstensi: `pdo_mysql`, `mbstring`, `zip`, `fileinfo`, `simplexml`

## Catatan
File hasil install seperti `app/config/generated.php` dan `storage/installed.lock` akan dibuat otomatis setelah wizard install dijalankan.
