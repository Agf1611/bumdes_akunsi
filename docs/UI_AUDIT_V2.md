# Audit UI & Perbaikan v2

Temuan utama dari audit tampilan:
1. **Warna teks tidak sinkron**  
   Beberapa elemen mewarisi warna terlalu pucat dari patch sebelumnya, sehingga judul hero, label filter, dan isi kartu sulit dibaca.

2. **Theme light/dark belum utuh**  
   Tombol theme sudah ada, tetapi warna komponen utama belum konsisten antara kartu, tabel, input, alert, dan sidebar.

3. **Logo sidebar belum tampil utuh**  
   Logo sebelumnya lebih mirip avatar bulat dan tidak selalu tampil penuh.

4. **Komponen halaman belum menyatu**  
   COA, Dashboard, form, dan laporan terlihat seperti beberapa gaya berbeda yang bercampur.

Perbaikan di patch ini:
- Menyatukan variabel warna light/dark.
- Mempertegas warna heading, teks isi, muted text, dan state hover.
- Membuat sidebar, topbar, card, table, form, alert, dan tombol berada dalam satu sistem visual.
- Menampilkan logo secara penuh dengan object-fit contain.
- Tetap aman: tidak menyentuh controller, model, database, atau route.
