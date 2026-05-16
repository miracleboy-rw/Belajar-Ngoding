<?php
require_once 'koneksi.php';
requireDatabaseReady($pdo);

$statTables = [
    'pasien' => 'pasien',
    'obat' => 'obat',
    'interaksi' => 'aturan_interaksi',
    'resep' => 'resep',
];
$stats = array_fill_keys(array_keys($statTables), 0);

foreach ($statTables as $key => $table) {
    $stats[$key] = (int) $pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
}

$recentResep = $pdo->query(
    "SELECT r.id, r.tanggal, r.total_biaya, p.nama_lengkap, COUNT(dr.id) AS jumlah_obat
     FROM resep r
     JOIN pasien p ON p.id = r.pasien_id
     LEFT JOIN detail_resep dr ON dr.resep_id = r.id
     GROUP BY r.id, r.tanggal, r.total_biaya, p.nama_lengkap
     ORDER BY r.tanggal DESC
     LIMIT 5"
)->fetchAll();

foreach ($recentResep as &$row) {
    $row['analysis'] = analyzePrescription($pdo, (int) $row['id']);
}
unset($row);

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

<div class="card mb-4">
    <div class="card-header bg-white fw-semibold">Resep Terbaru</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                <tr><th>ID</th><th>Pasien</th><th>Tanggal</th><th>Obat / Pasangan Obat</th><th>Tingkat</th><th>Biaya</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                <?php if (!$recentResep): ?>
                    <tr><td colspan="7" class="text-center text-muted">Belum ada resep.</td></tr>
                <?php endif; ?>
                <?php foreach ($recentResep as $row): ?>
                    <?php $tingkat = $row['analysis']['tingkat']; ?>
                    <tr>
                        <td>#<?= e($row['id']); ?></td>
                        <td><?= e($row['nama_lengkap']); ?></td>
                        <td><?= e($row['tanggal']); ?></td>
                        <td><?= e($row['analysis']['pasangan_label'] ?: '-'); ?></td>
                        <td><span class="badge <?= $tingkat === 'Merah' ? 'badge-merah' : ($tingkat === 'Kuning' ? 'badge-kuning' : 'bg-success'); ?>"><?= e($tingkat); ?></span></td>
                        <td><?= e(formatRupiah((float) $row['total_biaya'])); ?></td>
                        <td><a class="btn btn-sm btn-outline-primary" href="detail_resep.php?id=<?= e($row['id']); ?>">Detail</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <div class="fw-semibold">Grafik Realtime Pasien Datang</div>
        <select id="rangeKunjungan" class="form-select form-select-sm" style="max-width: 180px;">
            <option value="hari">Hari Ini</option>
            <option value="minggu">1 Minggu</option>
            <option value="1bulan">1 Bulan</option>
            <option value="3bulan">3 Bulan</option>
            <option value="6bulan">6 Bulan</option>
            <option value="1tahun">1 Tahun</option>
        </select>
    </div>
    <div class="card-body">
        <canvas id="grafikPasien" height="110"></canvas>
        <p class="text-muted small mt-3 mb-0">Data diperbarui otomatis setiap 30 detik berdasarkan jumlah resep/kunjungan yang tersimpan.</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const ctx = document.getElementById('grafikPasien');
const rangeSelect = document.getElementById('rangeKunjungan');
const chart = new Chart(ctx, {
    type: 'line',
    data: { labels: [], datasets: [{ label: 'Pasien datang', data: [], borderColor: '#0d6efd', backgroundColor: 'rgba(13, 110, 253, .12)', tension: .35, fill: true }] },
    options: { responsive: true, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } }
});

async function loadGrafikPasien() {
    const response = await fetch(`api_statistik_pasien.php?range=${rangeSelect.value}`);
    const data = await response.json();
    chart.data.labels = data.labels || [];
    chart.data.datasets[0].data = data.values || [];
    chart.update();
}

rangeSelect.addEventListener('change', loadGrafikPasien);
loadGrafikPasien();
setInterval(loadGrafikPasien, 30000);
</script>
<?php require 'footer.php'; ?>
