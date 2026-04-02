# Audit Installer log_error fix

- Masalah: installer memanggil `log_error()` sebelum helper aplikasi dimuat, sehingga instalasi fatal error.
- Akar masalah: `app/install/Installer.php` bergantung pada helper global yang tidak dijamin tersedia saat `public/install.php` berjalan.
- Perbaikan: menambahkan logger fallback internal `logThrowable()` di `Installer.php`.
- Dampak: tombol Tes Koneksi Database dan Mulai Instalasi tidak lagi fatal walau helper belum dimuat. Error asli tetap ditulis ke `storage/logs/install-YYYY-MM-DD.log`.
