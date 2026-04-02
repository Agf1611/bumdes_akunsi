# Panduan Update Produksi

## Tujuan
Panduan ini dipakai saat repository sudah live dan akan diperbarui tanpa install ulang penuh.

## Sebelum update
1. Catat versi aktif saat ini.
2. Backup database.
3. Backup folder runtime penting:
   - `app/config/generated.php`
   - `storage/`
   - `public/uploads/`
4. Pastikan periode sibuk transaksi sudah diminimalkan.
5. Siapkan rollback plan.

## Langkah update yang direkomendasikan
1. Pull source terbaru dari GitHub ke staging atau lokal.
2. Baca `CHANGELOG.md`.
3. Cek apakah ada file SQL baru di folder `database/`.
4. Jika ada perubahan database, jalankan patch SQL di server terlebih dahulu.
5. Aktifkan mode maintenance manual bila diperlukan.
6. Deploy file aplikasi baru, tapi jangan timpa:
   - `app/config/generated.php`
   - isi `storage/`
   - isi `public/uploads/`
7. Pastikan permission folder runtime tetap benar.
8. Uji halaman penting:
   - login
   - dashboard
   - jurnal
   - COA
   - aset
   - import jurnal
9. Cek log error server dan `storage/logs/`.
10. Jika aman, nonaktifkan mode maintenance.

## Rollback cepat
1. Kembalikan source code dari backup sebelumnya.
2. Kembalikan database dari backup jika patch SQL menyebabkan masalah.
3. Verifikasi login dan transaksi utama.

## Catatan penting
- Jangan commit file hasil install dari server produksi ke GitHub.
- Selalu uji dulu di staging jika memungkinkan.
- Bila rilis menyentuh import/export dan laporan, lakukan uji data riil minimal 1 sampel.
