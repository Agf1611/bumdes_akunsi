# Implementasi Backup + Restore (2026-03-16)

Perubahan utama:
- Menambahkan aksi restore database dari file backup server.
- Menambahkan aksi restore database dari upload file `.sql`.
- Menambahkan route POST `/backups/restore`.
- Menambahkan panel restore pada halaman Backup Database.

File yang diubah:
- app/modules/backups/BackupService.php
- app/modules/backups/BackupController.php
- app/modules/backups/views/index.php
- app/routes/web.php

Catatan penting:
- Restore akan menimpa isi database aktif.
- Sebaiknya buat backup baru sebelum menjalankan restore.
- File restore yang didukung adalah `.sql`.
- Upload restore dibatasi 30 MB pada level aplikasi, tetapi batas efektif tetap mengikuti konfigurasi PHP hosting.

Audit yang dilakukan:
- lint syntax PHP untuk semua file yang diubah
- review alur backup list/download/delete agar tetap kompatibel
- review parser SQL restore agar cocok dengan format backup SQL bawaan aplikasi
