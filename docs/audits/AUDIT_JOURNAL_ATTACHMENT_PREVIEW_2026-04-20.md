# Audit Jurnal - Preview Bukti Transaksi

Tanggal audit: 2026-04-20
Fokus: modul jurnal, khusus area lampiran bukti transaksi pada halaman detail jurnal.

## Temuan
1. Bukti transaksi hanya bisa diunduh.
   - Pada tabel lampiran di `app/modules/journals/views/detail.php`, aksi yang tersedia hanya **Unduh** dan **Hapus**.
   - Tidak ada pratinjau cepat untuk PDF maupun gambar.

2. Endpoint lampiran selalu memaksa download.
   - `app/modules/journals/JournalController.php` mengirim header `Content-Disposition: attachment` untuk semua request lampiran.
   - Akibatnya browser tidak bisa menampilkan PDF/gambar secara inline walaupun tipe file sebenarnya mendukung preview.

3. Tipe file upload sudah kompatibel untuk preview.
   - Upload lampiran dibatasi ke: PDF, JPG, JPEG, PNG, WEBP.
   - Semua tipe tersebut dapat dipratinjau aman di browser modern, jadi perbaikan cukup dilakukan di layer controller dan view.

## Perbaikan yang diterapkan
- Menambahkan mode `preview`/`inline` pada endpoint lampiran yang sama, tanpa membuat perubahan database.
- Menambahkan tombol **Pratinjau** pada tabel lampiran jurnal.
- Menambahkan modal preview yang lebih rapi untuk:
  - PDF via `iframe`
  - gambar via `<img>`
- Menambahkan tombol cepat **Buka Tab Baru** dan **Unduh File** pada modal preview.
- Menjaga aksi **Hapus** tetap seperti semula.

## File yang diubah
- `app/modules/journals/JournalController.php`
- `app/modules/journals/views/detail.php`

## File yang tidak diubah
- model jurnal
- database / SQL
- upload service lampiran
- halaman list jurnal
- cetak jurnal / cetak kwitansi

## Validasi
- PHP lint `app/modules/journals/JournalController.php`: lolos
- PHP lint `app/modules/journals/views/detail.php`: lolos
- Tidak ada perubahan schema database
- Tidak ada perubahan route karena preview memakai endpoint download yang sama dengan parameter mode

## Catatan implementasi
- Preview aktif di halaman **Detail Jurnal** pada tabel lampiran.
- File yang didukung preview: PDF, JPG, JPEG, PNG, WEBP.
- Untuk browser yang gagal menampilkan preview inline, pengguna tetap bisa memakai tombol **Buka Tab Baru** atau **Unduh File**.
