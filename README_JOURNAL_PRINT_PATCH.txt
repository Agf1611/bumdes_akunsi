PATCH FITUR CETAK JURNAL DAN KWITANSI
====================================

Yang ditambahkan:
1. Cetak daftar jurnal / daftar transaksi berdasarkan filter periode, tanggal, dan unit usaha.
2. Cetak per jurnal dalam 2 template:
   - detail jurnal standar
   - bukti transaksi / kwitansi
3. Metadata khusus bukti transaksi pada form jurnal.

LANGKAH PASANG:
1. Backup project dan database.
2. Upload semua file patch ini ke root project lalu timpa file lama.
3. Import file database/patch_journal_print_receipt.sql melalui phpMyAdmin.
4. Login kembali lalu buka menu Jurnal Umum.

RUTE BARU:
- /journals/print-list
- /journals/print-receipt?id=...

CATATAN:
- Jurnal lama otomatis tetap template standar.
- Hanya jurnal dengan template "kwitansi / bukti transaksi" yang dapat dicetak sebagai kwitansi.
- Export PDF tetap menggunakan print browser / Save as PDF.
