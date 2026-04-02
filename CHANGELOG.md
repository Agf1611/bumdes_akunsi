# Changelog

Semua perubahan penting pada repository ini dicatat di file ini.

Format mengikuti gaya sederhana yang mudah dipakai untuk release GitHub dan update produksi.

## [1.0.0] - 2026-04-02
### Added
- Struktur repository lebih rapi untuk GitHub dengan pemisahan dokumentasi ke folder `docs/deploy`, `docs/guides`, `docs/releases`, dan `docs/audits`.
- `CHANGELOG.md` untuk riwayat perubahan.
- `VERSION` untuk penanda versi aplikasi.
- `RELEASE_TAG.txt` untuk tag rilis awal yang direkomendasikan.
- `docs/deploy/PRODUCTION_UPDATE_GUIDE.md` untuk panduan update produksi.
- `docs/releases/RELEASE_TAGGING.md` untuk langkah membuat tag dan release GitHub.
- `.github/ISSUE_TEMPLATE` dan `pull_request_template.md` untuk alur kolaborasi yang lebih rapi.
- `scripts/create_release_tag.sh` untuk membantu membuat annotated tag secara konsisten.

### Changed
- `README.md` dirapikan agar lebih fokus ke struktur repo, install, deploy, dan release.
- `.gitignore` dirapikan agar file runtime, konfigurasi hasil install, log, import, backup, dan upload tidak ikut ter-commit.
- Dokumentasi dipindahkan ke struktur folder yang lebih mudah dipahami.

### Included fixes from previous audit
- Perbaikan form COA edit dan create.
- Perbaikan import jurnal agar template Excel lebih stabil.
- Pilihan unit usaha saat import jurnal.
- Aksi massal jurnal: tandai, ubah unit usaha, dan hapus terpilih.
- Perapihan menu aksi data master.
- Perbaikan scroll tabel, pagination, dan dropdown aksi sticky.

## [Unreleased]
### Planned
- Tambah CI lint sederhana untuk pengecekan PHP sebelum merge.
- Tambah panduan migrasi versi per rilis bila ada perubahan database berikutnya.
