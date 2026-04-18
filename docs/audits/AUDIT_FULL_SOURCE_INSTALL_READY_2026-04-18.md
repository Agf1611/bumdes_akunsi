# Audit source code penuh - paket siap installer

Tanggal audit: 2026-04-18

## Ruang lingkup audit
- Seluruh file PHP di aplikasi
- Konsistensi route -> controller -> method
- Konsistensi controller -> view
- Pemeriksaan helper/function global yang dipakai
- Pemeriksaan dasar installer browser
- Pemeriksaan jalur asset/public untuk mode document root root project dan document root folder public

## Pemeriksaan yang dijalankan
- PHP lint untuk seluruh file PHP
- Smoke check bawaan project (`scripts/final_smoke_check.php`)
- Route audit: semua route memiliki controller dan method yang valid
- View audit: semua view yang dipanggil controller ada di filesystem
- Static scan untuk mendeteksi pemanggilan function yang tidak tersedia
- Dry render halaman installer dan halaman login tanpa menjalankan koneksi database nyata

## Temuan yang diperbaiki
1. **COA form berpotensi 500**
   - `CoaController::showForm()` memanggil `coa_categories_grouped()` yang tidak tersedia.
   - View `app/modules/coa/views/form.php` memakai `$formData` dan `$categoriesMap`, tetapi controller lama tidak mengirim keduanya.
   - Dampak: halaman tambah/edit COA dapat gagal tampil.

2. **URL asset/upload salah saat server memakai document root ke folder `public`**
   - `public_url()` selalu menambahkan `/public` walau aplikasi sudah dijalankan langsung dari folder `public`.
   - Dampak: CSS, JS, upload, dan file public lain berpotensi 404.

3. **Cetak kwitansi masih bisa menampilkan angka pada field terbilang**
   - `printReceipt()` belum menormalisasi `amount_in_words` yang tersimpan sebagai angka.
   - Dampak: output kwitansi tidak konsisten karena terbilang tampil numerik.

4. **Bug UI scroll desktop**
   - `theme.js` mengunci `body.style.overflow = 'hidden'` pada desktop.
   - Dampak: halaman panjang terasa macet/tidak bisa scroll normal.

5. **Dead code error di file pendukung**
   - `app/middleware/GuestMiddleware.php` memakai namespace dan helper yang tidak konsisten dengan arsitektur global project.
   - `app/views/layouts/header.php` memanggil helper `config()` yang tidak ada.
   - Dampak: bila file ini dipakai, akan memicu fatal error.

## File yang diubah
- `app/helpers/common_helper.php`
- `public/assets/js/theme.js`
- `app/modules/coa/CoaController.php`
- `app/modules/coa/views/form.php`
- `app/modules/journals/JournalController.php`
- `app/middleware/GuestMiddleware.php`
- `app/views/layouts/header.php`

## Hasil verifikasi setelah perbaikan
- PHP lint seluruh file: lulus
- Smoke check bawaan project: lulus
- Route audit: lulus
- Dry render `public/install.php`: lulus
- Dry render `/login`: lulus
- Verifikasi helper `public_url()` untuk mode root/public document root: lulus

## Catatan jujur
Audit ini belum menjalankan instalasi database end-to-end karena environment kerja ini tidak menyediakan server MySQL/MariaDB aktif. Namun:
- file installer sudah lolos pemeriksaan sintaks,
- halaman installer bisa dirender,
- urutan import SQL dan runner installer sudah diperiksa secara statis.

Paket ini disiapkan agar siap dipakai installer dengan perubahan seminimal mungkin dan hanya pada file yang terbukti bermasalah atau berpotensi memicu error nyata.
