# Audit & Fix - Import Jurnal Pilih Unit Usaha

Tanggal: 2026-04-02

## Permintaan
Menambahkan opsi sebelum import jurnal agar user bisa memilih jurnal hasil import masuk ke unit usaha mana, tanpa perlu edit satu per satu setelah import.

## Hasil Audit
Ditemukan bahwa alur import jurnal sebelumnya selalu membuat `journal_headers.business_unit_id = NULL` karena:

1. Form import jurnal di halaman **Jurnal Umum** belum memiliki pilihan unit usaha.
2. Form import jurnal di halaman **Import Excel** juga belum memiliki pilihan unit usaha.
3. `ImportController::importJournal()` belum membaca target unit usaha dari form.
4. `ImportService::importJournal()` belum meneruskan `business_unit_id` ke `JournalModel->create()`.

Akibatnya, semua jurnal hasil import otomatis masuk ke **Global / Semua unit**.

## Perbaikan yang dilakukan
### 1) Tambah pilihan unit usaha di form import
Ditambahkan dropdown **Masuk ke Unit Usaha** pada:
- `app/modules/journals/views/index.php`
- `app/modules/imports/views/index.php`

Pilihan:
- **Global / Semua unit**
- seluruh unit usaha aktif yang tersedia di master

### 2) Validasi unit usaha tujuan saat submit import
Di `app/modules/imports/ImportController.php` ditambahkan validasi:
- unit kosong => hasil import masuk ke global
- unit tidak ditemukan => import dibatalkan dengan pesan error
- unit nonaktif => import dibatalkan dengan pesan error

### 3) Simpan hasil import ke unit usaha terpilih
Di `app/modules/imports/ImportService.php` ditambahkan dukungan parameter `business_unit_id` dan nilainya diteruskan ke proses pembuatan jurnal.

## File yang diubah
- `app/modules/imports/ImportController.php`
- `app/modules/imports/ImportService.php`
- `app/modules/imports/views/index.php`
- `app/modules/journals/views/index.php`

## Dampak
- Template jurnal **tidak perlu diubah**.
- Tidak ada perubahan struktur database.
- Import lama tetap kompatibel.
- User sekarang bisa memilih target unit usaha sebelum import.

## Hasil pengecekan
- PHP lint semua file yang diubah: **lolos**
- Tidak ada perubahan pada file lain di luar kebutuhan fitur ini.
