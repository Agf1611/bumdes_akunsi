# Implementasi Tahap 9 - Dashboard Pimpinan

## Ringkasan
Tahap ini menambahkan **Dashboard Pimpinan** yang fokus pada kebutuhan direktur/pimpinan BUMDes:
- laba/rugi berjalan
- posisi kas/bank
- kesiapan tutup buku
- titik perhatian checklist
- jurnal terbaru
- akun pendapatan dan beban teratas
- ringkasan per unit usaha

## Route baru
- `GET /dashboard/pimpinan`

## Hak akses
- `admin`
- `bendahara`
- `pimpinan`

## File yang diubah
- `app/modules/dashboard/DashboardController.php`
- `app/modules/dashboard/DashboardModel.php`
- `app/modules/dashboard/views/leadership.php`
- `app/routes/web.php`
- `app/views/layouts/sidebar.php`

## Perubahan teknis
### Controller
Menambahkan method `leadership()` pada `DashboardController`.
Method ini memakai filter dashboard yang sama dengan dashboard utama, lalu memuat:
- summary metrics
- cash/bank summary
- monthly trend
- recent journals
- top revenue accounts
- top expense accounts
- unit summaries
- closing checklist dari modul periode

### Model
Menambahkan method `getTopAccounts()` pada `DashboardModel` untuk mengambil akun pendapatan/beban terbesar pada rentang dashboard.

### View
Menambahkan view baru `leadership.php` dengan komponen:
- hero dan filter
- kartu metrik pimpinan
- ringkasan checklist tutup buku
- tabel jurnal terbaru
- daftar akun pendapatan/beban teratas
- tabel ringkasan per unit usaha

### Sidebar
Menambahkan menu baru:
- `Dashboard Pimpinan`

## Perubahan database
Tidak ada.

## Catatan pemasangan
- tidak perlu build ulang
- tidak perlu patch SQL
- cukup upload patch atau full source

## Uji cepat setelah upload
1. login sebagai admin/bendahara/pimpinan
2. buka menu `Dashboard Pimpinan`
3. ubah filter periode dan tanggal
4. cek kartu `Kesiapan Tutup Buku`
5. klik `Buka Checklist Tutup Buku`
6. cek daftar akun pendapatan/beban teratas
7. cek ringkasan unit usaha bila fitur unit aktif
