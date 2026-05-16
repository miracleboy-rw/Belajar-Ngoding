# Product Requirements Document: CDSS-Med

## Ringkasan
CDSS-Med (Clinical Decision Support System for Medical Prescribing) adalah aplikasi rekam medis sederhana untuk dokter praktik mandiri. Fitur utama aplikasi adalah e-prescribing dengan Clinical Decision Support System (CDSS) untuk mendeteksi kontraindikasi alergi pasien dan interaksi antar obat secara real-time saat dokter menyusun resep.

## Target Pengguna
- Dokter praktik mandiri.
- Admin klinik kecil yang membantu mengelola data pasien, obat, dan aturan interaksi.

## Tech Stack
- Backend: PHP Native dengan PDO.
- Database: MySQL.
- Frontend: HTML, CSS, Bootstrap 5 via CDN.
- Interaktivitas: Vanilla JavaScript Fetch API untuk AJAX.

## Modul Utama
1. Dashboard statistik dasar.
2. CRUD pasien dengan input alergi obat.
3. CRUD master obat.
4. CRUD aturan interaksi obat.
5. E-prescribing dengan pemeriksaan CDSS real-time.
6. API lokal `api_cek_cdss.php` untuk pemeriksaan alergi dan interaksi obat.

## Aturan CDSS
- Rule 1: Jika obat yang dipilih termasuk alergi pasien, sistem menampilkan alert merah dengan status `KONTRAINDIKASI ALERGI`.
- Rule 2: Jika obat baru berinteraksi dengan obat lain di keranjang resep, sistem menampilkan alert kuning atau merah sesuai tingkat bahaya pada knowledge base.

## Catatan Medis
Aplikasi ini adalah prototipe edukasi. Keputusan klinis akhir tetap berada pada dokter dan membutuhkan validasi data interaksi obat dari sumber medis terpercaya sebelum digunakan pada layanan nyata.
