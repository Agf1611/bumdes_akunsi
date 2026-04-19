# Audit dan penambahan COA Global BUMDes

Perubahan ini menambahkan paket akun COA global yang umum dipakai lintas unit usaha BUMDes tanpa menimpa akun yang sudah ada.

## Tujuan
- Menyediakan akun dasar yang lebih lengkap untuk transaksi operasional umum BUMDes.
- Tetap aman untuk database yang sudah berjalan: akun existing berdasarkan kode dilewati.
- Tetap siap installer: akun global ikut tersisip saat instalasi baru.

## File yang diubah
- `app/helpers/coa_helper.php`
- `app/modules/coa/CoaModel.php`
- `app/modules/coa/CoaController.php`
- `app/modules/coa/views/index.php`
- `app/routes/web.php`
- `database/coa_module.sql`

## Ringkasan teknis
1. Menambahkan daftar akun global di helper agar satu sumber dipakai oleh fitur admin.
2. Menambahkan aksi admin `Tambah COA Global BUMDes` di menu COA.
3. Menambahkan proses insert aman dengan pengecekan `account_code` agar tidak menimpa akun lama.
4. Menambahkan akun global yang sama ke `database/coa_module.sql` agar instalasi baru langsung lebih lengkap.

## Dampak
- Installasi baru mendapat COA global yang lebih siap pakai.
- Instalasi lama bisa menambah akun global dari halaman COA tanpa edit satu per satu.
- Tidak ada perubahan struktur tabel.
