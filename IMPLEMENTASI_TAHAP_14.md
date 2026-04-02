# Implementasi Tahap 14 — Jurnal dengan Metadata Referensi

## Ringkasan audit awal
Source terbaru yang diunggah masih memakai struktur jurnal dasar:
- `journal_headers`
- `journal_lines`
- detail jurnal hanya menyimpan akun, uraian, debit, kredit
- belum ada metadata referensi per baris jurnal
- belum ada master referensi seperti mitra, persediaan, bahan baku, simpanan, komponen arus kas

## Tujuan tahap ini
Menambah metadata referensi per baris jurnal tanpa merombak total sistem dan tetap kompatibel dengan data lama.

## Perubahan database
File patch:
- `database/patch_stage14_journal_reference_metadata.sql`

Patch ini menambahkan:
- tabel `reference_partners`
- tabel `inventory_items`
- tabel `raw_materials`
- tabel `saving_accounts`
- tabel `cashflow_components`
- kolom baru di `journal_lines`:
  - `partner_id`
  - `inventory_item_id`
  - `raw_material_id`
  - `asset_id`
  - `saving_account_id`
  - `cashflow_component_id`
  - `entry_tag`

## Perubahan source code
### 1. JournalModel
- query detail jurnal kini membawa metadata referensi dengan fallback aman
- insert jurnal mendukung kolom metadata bila patch SQL sudah dijalankan
- dropdown akun jurnal tetap kompatibel dan siap membaca `allow_direct_posting` bila tahap smart COA sudah diterapkan
- ditambahkan options loader untuk:
  - mitra
  - persediaan
  - bahan baku
  - aset
  - simpanan
  - komponen arus kas

### 2. JournalController
- membaca array metadata referensi dari form jurnal
- validasi referensi secara defensif
- normalisasi `entry_tag`
- menyiapkan option metadata ke form

### 3. Form jurnal
- setiap baris jurnal kini punya tombol `Meta`
- metadata tampil di baris kedua yang bisa dibuka/tutup
- field metadata:
  - mitra/debitur/kreditur
  - persediaan
  - bahan baku
  - aset
  - simpanan
  - komponen arus kas
  - tag entri
- backend tetap menyimpan `coa_id[]`, `debit[]`, `credit[]` seperti sebelumnya

### 4. Detail & print jurnal
- metadata referensi tampil di detail jurnal dan print standar sebagai informasi tambahan

## File yang diubah
- `app/modules/journals/JournalModel.php`
- `app/modules/journals/JournalController.php`
- `app/modules/journals/views/form.php`
- `app/modules/journals/views/detail.php`
- `app/modules/journals/views/print.php`
- `database/patch_stage14_journal_reference_metadata.sql`

## Audit yang dijalankan
- lint seluruh file PHP di `app/` dan `public/`
- smoke test render:
  - form jurnal
  - detail jurnal
  - print jurnal

## Hasil audit
- syntax PHP: lulus
- smoke test render: lulus

## Catatan jujur
Tahap ini menyiapkan fondasi metadata referensi di jurnal, tetapi belum membuat CRUD penuh untuk master:
- mitra
- persediaan
- bahan baku
- simpanan

Tabelnya sudah ada dan siap dipakai. Tahap berikut yang paling tepat adalah membuat modul master referensi agar dropdown metadata dapat diisi langsung dari UI aplikasi.
