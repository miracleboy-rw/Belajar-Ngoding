# CDSS-Med

CDSS-Med adalah prototipe aplikasi PHP Native + MySQL untuk rekam medis sederhana dan e-prescribing dengan pengecekan alergi pasien serta interaksi obat secara real-time.

## Cara Menjalankan di XAMPP

1. Salin folder proyek ke direktori XAMPP, misalnya `C:\xampp\htdocs\cdss`.
2. Jalankan Apache dan MySQL dari XAMPP Control Panel.
3. Buka `koneksi.php` dan sesuaikan konfigurasi jika user/password MySQL bukan default XAMPP.
4. Buka browser ke `http://localhost/cdss/setup.php`.
5. Klik tombol **Jalankan database.sql** untuk membuat database `cdss_med`, tabel, dan dummy data.
6. Buka `http://localhost/cdss/index.php` untuk masuk ke dashboard.

Jika muncul pesan seperti `Table 'cdss_med.pasien' doesn't exist`, artinya database sudah terkoneksi tetapi tabel belum dibuat. Jalankan `setup.php` atau import manual file `database.sql` melalui phpMyAdmin.

## Modul

- `index.php`: dashboard statistik, resep terbaru lengkap dengan pasangan obat/tingkat CDSS, dan grafik realtime pasien datang.
- `pasien.php`: CRUD pasien dan riwayat alergi obat.
- `obat.php`: CRUD master obat, kategori, dan harga obat.
- `interaksi.php`: CRUD aturan interaksi obat.
- `e_prescribing.php`: form resep dinamis dengan alert CDSS real-time.
- `detail_resep.php`: ringkasan resep selesai, total biaya pengobatan, daftar obat, dosis, aturan pakai, dan tombol PDF.
- `cetak_resep_pdf.php`: laporan PDF ukuran kecil seperti resep, berisi obat, dosis, dan aturan pakai tanpa biaya.
- `api_cek_cdss.php`: endpoint JSON untuk pengecekan alergi dan interaksi obat.
- `api_statistik_pasien.php`: endpoint JSON grafik kunjungan pasien untuk rentang hari, minggu, 1 bulan, 3 bulan, 6 bulan, dan 1 tahun.
- `setup.php`: helper setup database untuk lingkungan lokal/XAMPP.
