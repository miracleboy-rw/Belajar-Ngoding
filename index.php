<?php
require_once 'koneksi.php';

$stats = [
    'pasien' => 0,
    'obat' => 0,
    'interaksi' => 0,
    'resep' => 0,
];

foreach ($stats as $table => $value) {
    $stats[$table] = (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
}

$recentResep = $pdo->query(
    "SELECT r.id, r.tanggal, p.nama_lengkap, COUNT(dr.id) AS jumlah_obat
     FROM resep r
     JOIN pasien p ON p.id = r.pasien_id
     LEFT JOIN detail_resep dr ON dr.resep_id = r.id
     GROUP BY r.id, r.tanggal, p.nama_lengkap
     ORDER BY r.tanggal DESC
     LIMIT 5"
)->fetchAll();

require 'header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Dashboard</h1>
        <p class="text-muted mb-0">Statistik ringkas aplikasi CDSS-Med.</p>
    </div>
    <a href="e_prescribing.php" class="btn btn-primary">Buat Resep Baru</a>
</div>

<div class="row g-3 mb-4">
    <?php foreach ([
        'pasien' => ['label' => 'Total Pasien', 'color' => 'primary'],
        'obat' => ['label' => 'Master Obat', 'color' => 'success'],
        'interaksi' => ['label' => 'Aturan Interaksi', 'color' => 'warning'],
        'resep' => ['label' => 'Resep Tersimpan', 'color' => 'danger'],
    ] as $key => $item): ?>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <span class="text-muted small"><?= $item['label']; ?></span>
                    <div class="display-6 fw-bold text-<?= $item['color']; ?>"><?= $stats[$key]; ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-header bg-white fw-semibold">Resep Terbaru</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                <tr><th>ID</th><th>Pasien</th><th>Tanggal</th><th>Jumlah Obat</th></tr>
                </thead>
                <tbody>
                <?php if (!$recentResep): ?>
                    <tr><td colspan="4" class="text-center text-muted">Belum ada resep.</td></tr>
                <?php endif; ?>
                <?php foreach ($recentResep as $row): ?>
                    <tr>
                        <td>#<?= e($row['id']); ?></td>
                        <td><?= e($row['nama_lengkap']); ?></td>
                        <td><?= e($row['tanggal']); ?></td>
                        <td><?= e($row['jumlah_obat']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require 'footer.php'; ?>
