# Implementasi Tahap 6 - Template Transaksi Cepat & Duplikat Jurnal

Tanggal: 2026-03-15

## Fokus tahap ini
Tahap 6 menambah fitur input yang lebih efisien tanpa mengubah struktur database, yaitu:
1. Template transaksi cepat pada form jurnal.
2. Duplikat jurnal dari transaksi lama menjadi draft jurnal baru.

## Fitur yang ditambahkan

### 1. Template transaksi cepat
Pada halaman tambah jurnal sekarang tersedia kartu pilihan template:
- Penerimaan Kas / Pendapatan Tunai
- Pengeluaran Kas / Beban Tunai
- Penerimaan ke Rekening Bank
- Pengeluaran via Bank / Transfer
- Jurnal Penyesuaian

Template ini tidak memaksa akun tertentu karena COA tiap instalasi bisa berbeda. Sistem hanya mengisi awal:
- tanggal
- deskripsi jurnal
- template cetak
- metadata kwitansi
- uraian dua baris jurnal

### 2. Duplikat jurnal
Setiap jurnal sekarang dapat diduplikasi dari:
- daftar jurnal
- halaman detail jurnal

Saat pengguna memilih duplikat, form tambah jurnal akan diisi dari jurnal sumber:
- keterangan
- unit usaha
- template cetak
- metadata kwitansi
- detail baris jurnal
- nominal debit/kredit

Nomor jurnal tetap dibuat baru saat disimpan, dan tanggal default diarahkan ke hari ini.
Periode default diarahkan ke periode aktif bila tersedia.

## File yang diubah
- `app/helpers/journal_helper.php`
- `app/modules/journals/JournalController.php`
- `app/modules/journals/views/form.php`
- `app/modules/journals/views/index.php`
- `app/modules/journals/views/detail.php`

## Catatan teknis
- Tahap ini **tidak memerlukan patch SQL**.
- Tidak ada perubahan schema database.
- Fokus tahap ini aman untuk incremental upload.

## Rekomendasi uji setelah upload
1. Buka menu Jurnal Umum.
2. Klik `Transaksi Cepat`.
3. Pilih satu template dan cek apakah form terisi awal.
4. Simpan 1 jurnal baru dari template.
5. Buka salah satu jurnal lama lalu klik `Duplikat`.
6. Pastikan form terisi dari jurnal sumber dan bisa disimpan menjadi jurnal baru.
