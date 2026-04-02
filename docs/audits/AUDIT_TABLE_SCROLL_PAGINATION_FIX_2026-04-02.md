# Audit Perbaikan Tampilan Daftar, Aksi, dan Pagination

Tanggal audit: 2026-04-02
Modul utama: Jurnal Umum, Asset, dan seluruh tabel Data Master yang memakai menu Aksi.

## Keluhan yang diaudit
1. Kolom **Aksi** di daftar jurnal terlihat terpotong di sisi kanan.
2. Tabel data master kurang nyaman karena kolom paling kanan mudah terhalang.
3. Pagination hanya menyediakan **Sebelumnya/Berikutnya** tanpa pilihan nomor halaman.
4. Di modul **Asset**, kontrol pagination muncul di posisi yang salah, yaitu di area kiri atas kartu ringkasan, bukan di bawah tabel daftar.

## Temuan
### 1) Kolom aksi terpotong / sulit dijangkau
Penyebab utama:
- wrapper tabel belum dipaksa konsisten untuk **scroll horizontal yang nyaman**;
- kolom aksi tidak dipertahankan tetap terlihat ketika tabel melebar;
- menu aksi pada tabel data master memakai panel dropdown di kolom paling kanan, sehingga terasa sempit saat lebar layar terbatas.

### 2) Pagination kurang efisien
Penyebab utama:
- partial pagination hanya menyediakan tombol **Sebelumnya** dan **Berikutnya**;
- tidak ada **pilihan nomor halaman** langsung.

### 3) Asset menampilkan pagination di posisi yang salah
Penyebab utama:
- file view `app/modules/assets/views/index.php` me-render:
  `require APP_PATH . '/views/partials/listing_controls.php';`
  di area kartu ringkasan statistik, bukan setelah tabel daftar aset.

## Perbaikan yang diterapkan
### Jurnal Umum
- Wrapper tabel dipastikan bisa **scroll horizontal**.
- Ditambahkan catatan kecil agar user tahu tabel bisa digeser.
- Kolom **Aksi** dibuat **sticky di sisi kanan** agar tetap terlihat.

### Data Master
- Wrapper tabel pada halaman yang memakai partial menu aksi dibuat lebih aman untuk **scroll horizontal**.
- Kolom **Aksi** dibuat **sticky di sisi kanan** agar tidak mudah hilang saat tabel melebar.

### Pagination
- Ditambahkan dropdown **Halaman** agar user bisa langsung memilih nomor halaman.
- Tombol **Sebelumnya** dan **Berikutnya** tetap dipertahankan.
- Dropdown **Baris** tetap ada.

### Asset
- Pagination dipindahkan ke bawah daftar aset.

## File yang diubah
1. `app/views/partials/listing_controls.php`
2. `app/views/partials/table_action_menu.php`
3. `app/modules/journals/views/index.php`
4. `app/modules/assets/views/index.php`

## Validasi
- PHP lint seluruh file yang diubah: **lolos**.
- Perubahan ini tidak mengubah database.
- Perubahan fokus pada UI daftar, posisi pagination, dan akses cepat ke kolom aksi.
