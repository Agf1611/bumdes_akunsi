# Audit Update Menu Missing Fix

Temuan:
- Source GitHub-ready yang dipakai untuk install belum membawa modul `app/modules/updates`.
- Route `/updates` belum terdaftar.
- Menu sidebar untuk Update Aplikasi belum ada.
- Konfigurasi repo update belum ada di `app/config/app.php`.

Perbaikan:
- Menambahkan modul update aplikasi.
- Menambahkan route admin untuk cek/apply/report update.
- Menambahkan menu sidebar Update Aplikasi.
- Menambahkan konfigurasi `update_repo_url` dan `update_branch`.

Catatan:
- Fitur update tetap melakukan backup database dulu sebelum apply update.
- Update hanya mengganti file yang berubah/baru, tidak menimpa storage, upload, dan config install.
