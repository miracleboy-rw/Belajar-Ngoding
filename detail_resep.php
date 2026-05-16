<?php
require_once 'koneksi.php';
requireDatabaseReady($pdo);

$resepId = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    "SELECT r.*, p.nama_lengkap, p.usia, p.no_hp, p.riwayat_penyakit
     FROM resep r
     JOIN pasien p ON p.id = r.pasien_id
     WHERE r.id = ?"
);
$stmt->execute([$resepId]);
$resep = $stmt->fetch();

if (!$resep) {
    http_response_code(404);
    die('Resep tidak ditemukan.');
}

$analysis = analyzePrescription($pdo, $resepId);
$details = $analysis['details'];
require 'header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Detail Resep #<?= e($resep['id']); ?></h1>
        <p class="text-muted mb-0">Ringkasan resep, biaya yang terlihat di website, dan unduhan PDF resep kecil.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="cetak_resep_pdf.php?id=<?= e($resep['id']); ?>" class="btn btn-danger" target="_blank">Download PDF Resep</a>
        <a href="e_prescribing.php" class="btn btn-outline-primary">Buat Resep Baru</a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">Informasi Pasien</div>
            <div class="card-body">
                <dl class="mb-0">
                    <dt>Nama</dt><dd><?= e($resep['nama_lengkap']); ?></dd>
                    <dt>Usia</dt><dd><?= e($resep['usia']); ?> tahun</dd>
                    <dt>No. HP</dt><dd><?= e($resep['no_hp'] ?: '-'); ?></dd>
                    <dt>Tanggal Resep</dt><dd><?= e($resep['tanggal']); ?></dd>
                    <dt>Status CDSS</dt>
                    <dd><span class="badge <?= $analysis['tingkat'] === 'Merah' ? 'badge-merah' : ($analysis['tingkat'] === 'Kuning' ? 'badge-kuning' : 'bg-success'); ?>"><?= e($analysis['tingkat']); ?></span></dd>
                </dl>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">Biaya Pengobatan</div>
            <div class="card-body">
                <div class="display-6 fw-bold text-primary mb-2"><?= e(formatRupiah((float) $resep['total_biaya'])); ?></div>
                <p class="text-muted mb-0">Biaya hanya tampil di website dan tidak dimasukkan ke laporan PDF resep.</p>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header bg-white fw-semibold">Obat dan Aturan Pakai</div>
    <div class="card-body table-responsive">
        <table class="table table-hover align-middle">
            <thead><tr><th>Obat</th><th>Dosis</th><th>Aturan Pakai</th><th>Harga</th></tr></thead>
            <tbody>
            <?php foreach ($details as $detail): ?>
                <tr>
                    <td><strong><?= e($detail['nama_obat']); ?></strong><br><span class="small text-muted"><?= e($detail['kategori']); ?></span></td>
                    <td><?= e($detail['dosis']); ?></td>
                    <td><?= e($detail['aturan_pakai']); ?></td>
                    <td><?= e(formatRupiah((float) $detail['subtotal'])); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr><th colspan="3" class="text-end">Total</th><th><?= e(formatRupiah((float) $resep['total_biaya'])); ?></th></tr>
            </tfoot>
        </table>
    </div>
</div>
<?php require 'footer.php'; ?>
