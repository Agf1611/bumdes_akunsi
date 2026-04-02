# Audit tandai jurnal hilang - konsolidasi

Temuan:
1. Source full yang dipakai setelah patch menu aksi data master belum membawa kembali file bulk selection jurnal.
2. Akibatnya daftar jurnal masih memakai view tanpa kolom checkbox tandai dan tanpa panel aksi massal.
3. Backend route/controller/model untuk bulk action juga perlu ada agar checkbox dan proses massal aktif penuh.

File yang dipulihkan:
- app/routes/web.php
- app/modules/journals/JournalController.php
- app/modules/journals/JournalModel.php
- app/modules/journals/views/index.php

Hasil:
- Kolom checkbox tandai muncul lagi di daftar jurnal desktop.
- Panel aksi massal jurnal muncul lagi.
- Tandai semua, bersihkan tandai, ubah unit usaha massal, dan hapus massal aktif lagi.
- Tetap kompatibel dengan perbaikan menu aksi data master paling bawah.
