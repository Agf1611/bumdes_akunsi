VERSI WINDOWS LOCALHOST - SISTEM PELAPORAN KEUANGAN BUMDES
===========================================================

Paket ini sudah disiapkan agar lebih mudah dipasang di Windows localhost.
Target utama: XAMPP + Apache + MySQL.

CARA PALING CEPAT
-----------------
1. Install XAMPP di Windows.
2. Jalankan Apache dan MySQL dari XAMPP Control Panel.
3. Extract ZIP ini.
4. Double-click file paling atas: INSTALL_WINDOWS_XAMPP.bat
   atau jalankan tools\windows\install-xampp.bat
5. Ikuti instruksi sampai browser membuka:
   http://localhost/bumdes-akuntansi/install.php
7. Isi form installer sampai selesai.

YANG DILAKUKAN SCRIPT install-xampp.bat
---------------------------------------
- Menyalin aplikasi ke C:\xampp\htdocs\bumdes-akuntansi
- Membuat folder kerja yang diperlukan
- Menghapus state install lama (lock + generated config)
- Mencoba membuat database kosong otomatis
- Membuka URL installer di browser

DEFAULT YANG DISARANKAN
-----------------------
- Host database : 127.0.0.1
- Port          : 3306
- Database      : bumdes_akuntansi
- User MySQL    : root
- Password      : kosong (default XAMPP baru)

SETELAH INSTALL SELESAI
-----------------------
1. Login dengan akun admin yang Anda buat.
2. Isi Profil BUMDes.
3. Isi Unit Usaha bila perlu.
4. Isi / import COA.
5. Buat Periode Akuntansi.
6. Mulai input jurnal.

JIKA INGIN INSTALL ULANG
------------------------
- Jalankan tools\windows\reset-local-install.bat
- Lalu kosongkan database lewat phpMyAdmin
- Jalankan lagi installer

CATATAN
-------
- Paket ini belum menyertakan XAMPP. Jadi XAMPP tetap harus sudah terpasang di Windows.
- Aplikasi sudah diset agar URL otomatis mengikuti localhost bila belum di-hardcode.
- Bila mysql.exe gagal membuat database otomatis, buat database kosong manual di phpMyAdmin lalu lanjutkan installer.
