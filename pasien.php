<?php
require_once 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $nama = trim($_POST['nama_lengkap'] ?? '');
    $usia = (int) ($_POST['usia'] ?? 0);
    $noHp = trim($_POST['no_hp'] ?? '');
    $riwayat = trim($_POST['riwayat_penyakit'] ?? '');
    $alergiIds = array_map('intval', $_POST['alergi_obat'] ?? []);

    if ($nama !== '' && $usia > 0) {
        $pdo->beginTransaction();
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE pasien SET nama_lengkap = ?, usia = ?, no_hp = ?, riwayat_penyakit = ? WHERE id = ?');
            $stmt->execute([$nama, $usia, $noHp, $riwayat, $id]);
            $pasienId = $id;
            $pdo->prepare('DELETE FROM alergi_pasien WHERE pasien_id = ?')->execute([$pasienId]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO pasien (nama_lengkap, usia, no_hp, riwayat_penyakit) VALUES (?, ?, ?, ?)');
            $stmt->execute([$nama, $usia, $noHp, $riwayat]);
            $pasienId = (int) $pdo->lastInsertId();
        }

        $stmtAlergi = $pdo->prepare('INSERT IGNORE INTO alergi_pasien (pasien_id, obat_id) VALUES (?, ?)');
        foreach ($alergiIds as $obatId) {
            $stmtAlergi->execute([$pasienId, $obatId]);
        }
        $pdo->commit();
    }

    redirect('pasien.php');
}

if (isset($_GET['hapus'])) {
    $stmt = $pdo->prepare('DELETE FROM pasien WHERE id = ?');
    $stmt->execute([(int) $_GET['hapus']]);
    redirect('pasien.php');
}

$edit = null;
$selectedAlergi = [];
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM pasien WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $edit = $stmt->fetch();

    if ($edit) {
        $stmt = $pdo->prepare('SELECT obat_id FROM alergi_pasien WHERE pasien_id = ?');
        $stmt->execute([(int) $edit['id']]);
        $selectedAlergi = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }
}

$obatList = $pdo->query('SELECT * FROM obat ORDER BY nama_obat')->fetchAll();
$pasienList = $pdo->query(
    "SELECT p.*, GROUP_CONCAT(o.nama_obat ORDER BY o.nama_obat SEPARATOR ', ') AS daftar_alergi
     FROM pasien p
     LEFT JOIN alergi_pasien ap ON ap.pasien_id = p.id
     LEFT JOIN obat o ON o.id = ap.obat_id
     GROUP BY p.id
     ORDER BY p.nama_lengkap"
)->fetchAll();

require 'header.php';
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-white fw-semibold"><?= $edit ? 'Edit Pasien' : 'Tambah Pasien'; ?></div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="id" value="<?= e($edit['id'] ?? ''); ?>">
                    <div class="mb-3">
                        <label class="form-label required">Nama Lengkap</label>
                        <input type="text" name="nama_lengkap" class="form-control" value="<?= e($edit['nama_lengkap'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Usia</label>
                        <input type="number" name="usia" class="form-control" min="1" value="<?= e($edit['usia'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No. HP</label>
                        <input type="text" name="no_hp" class="form-control" value="<?= e($edit['no_hp'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Riwayat Penyakit</label>
                        <textarea name="riwayat_penyakit" class="form-control" rows="3"><?= e($edit['riwayat_penyakit'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Riwayat Alergi Obat</label>
                        <div class="border rounded p-2 bg-light" style="max-height: 180px; overflow:auto;">
                            <?php foreach ($obatList as $obat): ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="alergi_obat[]" value="<?= e($obat['id']); ?>" id="alergi<?= e($obat['id']); ?>" <?= in_array((int) $obat['id'], $selectedAlergi, true) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="alergi<?= e($obat['id']); ?>"><?= e($obat['nama_obat']); ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Simpan Pasien</button>
                    <?php if ($edit): ?><a class="btn btn-link w-100" href="pasien.php">Batal Edit</a><?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white fw-semibold">Data Pasien</div>
            <div class="card-body table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Nama</th><th>Usia</th><th>No HP</th><th>Alergi</th><th>Aksi</th></tr></thead>
                    <tbody>
                    <?php foreach ($pasienList as $pasien): ?>
                        <tr>
                            <td><strong><?= e($pasien['nama_lengkap']); ?></strong><br><span class="small text-muted"><?= e($pasien['riwayat_penyakit']); ?></span></td>
                            <td><?= e($pasien['usia']); ?></td>
                            <td><?= e($pasien['no_hp']); ?></td>
                            <td><?= e($pasien['daftar_alergi'] ?: '-'); ?></td>
                            <td class="text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="?edit=<?= e($pasien['id']); ?>">Edit</a>
                                <a class="btn btn-sm btn-outline-danger" href="?hapus=<?= e($pasien['id']); ?>" onclick="return confirm('Hapus pasien ini?')">Hapus</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require 'footer.php'; ?>
