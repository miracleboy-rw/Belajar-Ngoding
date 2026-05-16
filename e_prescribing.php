<?php
require_once 'koneksi.php';
requireDatabaseReady($pdo);

$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pasienId = (int) ($_POST['pasien_id'] ?? 0);
    $obatIds = $_POST['obat_id'] ?? [];
    $dosisList = $_POST['dosis'] ?? [];
    $aturanList = $_POST['aturan_pakai'] ?? [];

    $details = [];
    foreach ($obatIds as $index => $obatId) {
        $obatId = (int) $obatId;
        $dosis = trim($dosisList[$index] ?? '');
        $aturan = trim($aturanList[$index] ?? '');
        if ($obatId > 0 && $dosis !== '' && $aturan !== '') {
            $details[] = compact('obatId', 'dosis', 'aturan');
        }
    }

    if ($pasienId > 0 && $details) {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('INSERT INTO resep (pasien_id, tanggal, total_biaya) VALUES (?, NOW(), 0)');
        $stmt->execute([$pasienId]);
        $resepId = (int) $pdo->lastInsertId();

        $totalBiaya = 0;
        $stmtObat = $pdo->prepare('SELECT harga FROM obat WHERE id = ?');
        $stmtDetail = $pdo->prepare('INSERT INTO detail_resep (resep_id, obat_id, dosis, aturan_pakai, harga_satuan, subtotal) VALUES (?, ?, ?, ?, ?, ?)');
        foreach ($details as $detail) {
            $stmtObat->execute([$detail['obatId']]);
            $hargaSatuan = (float) $stmtObat->fetchColumn();
            $subtotal = $hargaSatuan;
            $totalBiaya += $subtotal;
            $stmtDetail->execute([$resepId, $detail['obatId'], $detail['dosis'], $detail['aturan'], $hargaSatuan, $subtotal]);
        }
        $stmt = $pdo->prepare('UPDATE resep SET total_biaya = ? WHERE id = ?');
        $stmt->execute([$totalBiaya, $resepId]);
        $pdo->commit();
        redirect('detail_resep.php?id=' . $resepId);
    } else {
        $errorMessage = 'Pilih pasien dan minimal satu obat lengkap dengan dosis serta aturan pakai.';
    }
}

$pasienList = $pdo->query('SELECT * FROM pasien ORDER BY nama_lengkap')->fetchAll();
$obatList = $pdo->query('SELECT * FROM obat ORDER BY nama_obat')->fetchAll();
require 'header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">E-Prescribing</h1>
        <p class="text-muted mb-0">Susun resep dan cek alergi/interaksi obat secara real-time.</p>
    </div>
</div>

<?php if ($successMessage): ?><div class="alert alert-success"><?= e($successMessage); ?></div><?php endif; ?>
<?php if ($errorMessage): ?><div class="alert alert-danger"><?= e($errorMessage); ?></div><?php endif; ?>

<div id="cdssAlerts" class="mb-3"></div>

<form method="post" id="resepForm" class="card">
    <div class="card-body">
        <div class="mb-4">
            <label class="form-label required">Pasien</label>
            <select name="pasien_id" id="pasien_id" class="form-select" required>
                <option value="">Pilih pasien</option>
                <?php foreach ($pasienList as $pasien): ?>
                    <option value="<?= e($pasien['id']); ?>"><?= e($pasien['nama_lengkap']); ?> (<?= e($pasien['usia']); ?> tahun)</option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="table-responsive">
            <table class="table align-middle" id="resepTable">
                <thead>
                <tr><th style="width: 35%">Obat</th><th>Dosis</th><th>Aturan Pakai</th><th style="width: 80px">Aksi</th></tr>
                </thead>
                <tbody id="resepBody"></tbody>
            </table>
        </div>

        <button type="button" class="btn btn-outline-primary" id="tambahBaris">+ Tambah Obat</button>
    </div>
    <div class="card-footer bg-white d-flex justify-content-end gap-2">
        <button type="reset" class="btn btn-outline-secondary">Reset</button>
        <button type="submit" class="btn btn-primary">Simpan Resep</button>
    </div>
</form>

<template id="rowTemplate">
    <tr>
        <td>
            <select name="obat_id[]" class="form-select obat-select" required>
                <option value="">Pilih obat</option>
                <?php foreach ($obatList as $obat): ?>
                    <option value="<?= e($obat['id']); ?>"><?= e($obat['nama_obat']); ?> - <?= e($obat['kategori']); ?> (<?= e(formatRupiah((float) $obat['harga'])); ?>)</option>
                <?php endforeach; ?>
            </select>
        </td>
        <td><input type="text" name="dosis[]" class="form-control" placeholder="Contoh: 500 mg" required></td>
        <td><input type="text" name="aturan_pakai[]" class="form-control" placeholder="Contoh: 3x sehari sesudah makan" required></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger hapus-baris">Hapus</button></td>
    </tr>
</template>

<script>
const resepBody = document.getElementById('resepBody');
const tambahBaris = document.getElementById('tambahBaris');
const rowTemplate = document.getElementById('rowTemplate');
const alertContainer = document.getElementById('cdssAlerts');
const form = document.getElementById('resepForm');
let latestAlerts = [];

function getSelectedObatIds(exceptSelect = null) {
    return [...document.querySelectorAll('.obat-select')]
        .filter(select => select !== exceptSelect && select.value)
        .map(select => Number(select.value));
}

function renderAlerts() {
    if (latestAlerts.length === 0) {
        alertContainer.innerHTML = '<div class="alert alert-success">Belum ada alert CDSS. Kombinasi obat saat ini aman berdasarkan knowledge base.</div>';
        return;
    }

    alertContainer.innerHTML = latestAlerts.map(alert => {
        const css = alert.tingkat_bahaya === 'Merah' ? 'danger' : 'warning';
        return `<div class="alert alert-${css} mb-2"><strong>${alert.judul}</strong><br>${alert.pesan}</div>`;
    }).join('');
}

async function cekCdssForSelect(select) {
    const pasienId = document.getElementById('pasien_id').value;
    const obatId = select.value;

    if (!pasienId || !obatId) {
        return [];
    }

    const response = await fetch('api_cek_cdss.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            pasien_id: pasienId,
            obat_id: obatId,
            keranjang_obat: getSelectedObatIds(select)
        })
    });
    const data = await response.json();
    return data.alerts || [];
}

async function cekSemuaBaris() {
    const selects = [...document.querySelectorAll('.obat-select')].filter(select => select.value);
    const alertMap = new Map();

    for (const select of selects) {
        const alerts = await cekCdssForSelect(select);
        alerts.forEach(alert => {
            const key = `${alert.tipe}-${alert.tingkat_bahaya}-${alert.pesan}`;
            alertMap.set(key, alert);
        });
    }

    latestAlerts = [...alertMap.values()];
    renderAlerts();
}

function addRow() {
    const clone = rowTemplate.content.cloneNode(true);
    resepBody.appendChild(clone);
}

tambahBaris.addEventListener('click', addRow);
resepBody.addEventListener('click', event => {
    if (event.target.classList.contains('hapus-baris')) {
        event.target.closest('tr').remove();
        cekSemuaBaris();
    }
});
resepBody.addEventListener('change', event => {
    if (event.target.classList.contains('obat-select')) {
        cekSemuaBaris();
    }
});
document.getElementById('pasien_id').addEventListener('change', cekSemuaBaris);
form.addEventListener('submit', event => {
    const hasRedAlert = latestAlerts.some(alert => alert.tingkat_bahaya === 'Merah');
    const hasYellowAlert = latestAlerts.some(alert => alert.tingkat_bahaya === 'Kuning');

    if (hasRedAlert) {
        event.preventDefault();
        alert('Resep tidak dapat disimpan karena terdapat alert merah/kontraindikasi.');
        return;
    }

    if (hasYellowAlert && !confirm('Terdapat alert kuning. Lanjutkan menyimpan resep dengan pemantauan klinis?')) {
        event.preventDefault();
    }
});
form.addEventListener('reset', () => setTimeout(() => { latestAlerts = []; renderAlerts(); }, 0));

addRow();
renderAlerts();
</script>
<?php require 'footer.php'; ?>
