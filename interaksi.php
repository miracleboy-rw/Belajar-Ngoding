<?php
require_once 'koneksi.php';

function normalizePair(int $obat1, int $obat2): array
{
    return $obat1 < $obat2 ? [$obat1, $obat2] : [$obat2, $obat1];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $obat1 = (int) ($_POST['obat1_id'] ?? 0);
    $obat2 = (int) ($_POST['obat2_id'] ?? 0);
    $tingkat = $_POST['tingkat_bahaya'] ?? 'Kuning';
    $deskripsi = trim($_POST['deskripsi_efek'] ?? '');

    if ($obat1 > 0 && $obat2 > 0 && $obat1 !== $obat2 && in_array($tingkat, ['Kuning', 'Merah'], true) && $deskripsi !== '') {
        [$obat1, $obat2] = normalizePair($obat1, $obat2);
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE aturan_interaksi SET obat1_id = ?, obat2_id = ?, tingkat_bahaya = ?, deskripsi_efek = ? WHERE id = ?');
            $stmt->execute([$obat1, $obat2, $tingkat, $deskripsi, $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO aturan_interaksi (obat1_id, obat2_id, tingkat_bahaya, deskripsi_efek) VALUES (?, ?, ?, ?)');
            $stmt->execute([$obat1, $obat2, $tingkat, $deskripsi]);
        }
    }
    redirect('interaksi.php');
}

if (isset($_GET['hapus'])) {
    $stmt = $pdo->prepare('DELETE FROM aturan_interaksi WHERE id = ?');
    $stmt->execute([(int) $_GET['hapus']]);
    redirect('interaksi.php');
}

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM aturan_interaksi WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $edit = $stmt->fetch();
}

$obatList = $pdo->query('SELECT * FROM obat ORDER BY nama_obat')->fetchAll();
$interaksiList = $pdo->query(
    "SELECT ai.*, o1.nama_obat AS obat1, o2.nama_obat AS obat2
     FROM aturan_interaksi ai
     JOIN obat o1 ON o1.id = ai.obat1_id
     JOIN obat o2 ON o2.id = ai.obat2_id
     ORDER BY ai.tingkat_bahaya DESC, o1.nama_obat"
)->fetchAll();
require 'header.php';
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-white fw-semibold"><?= $edit ? 'Edit Aturan Interaksi' : 'Tambah Aturan Interaksi'; ?></div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="id" value="<?= e($edit['id'] ?? ''); ?>">
                    <div class="mb-3">
                        <label class="form-label required">Obat A</label>
                        <select name="obat1_id" class="form-select" required>
                            <option value="">Pilih obat</option>
                            <?php foreach ($obatList as $obat): ?>
                                <option value="<?= e($obat['id']); ?>" <?= (int) ($edit['obat1_id'] ?? 0) === (int) $obat['id'] ? 'selected' : ''; ?>><?= e($obat['nama_obat']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Obat B</label>
                        <select name="obat2_id" class="form-select" required>
                            <option value="">Pilih obat</option>
                            <?php foreach ($obatList as $obat): ?>
                                <option value="<?= e($obat['id']); ?>" <?= (int) ($edit['obat2_id'] ?? 0) === (int) $obat['id'] ? 'selected' : ''; ?>><?= e($obat['nama_obat']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Tingkat Bahaya</label>
                        <select name="tingkat_bahaya" class="form-select" required>
                            <option value="Kuning" <?= ($edit['tingkat_bahaya'] ?? '') === 'Kuning' ? 'selected' : ''; ?>>Kuning</option>
                            <option value="Merah" <?= ($edit['tingkat_bahaya'] ?? '') === 'Merah' ? 'selected' : ''; ?>>Merah</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Deskripsi Efek</label>
                        <textarea name="deskripsi_efek" class="form-control" rows="3" required><?= e($edit['deskripsi_efek'] ?? ''); ?></textarea>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Simpan Aturan</button>
                    <?php if ($edit): ?><a class="btn btn-link w-100" href="interaksi.php">Batal Edit</a><?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white fw-semibold">Knowledge Base Interaksi Obat</div>
            <div class="card-body table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Pasangan Obat</th><th>Tingkat</th><th>Efek</th><th>Aksi</th></tr></thead>
                    <tbody>
                    <?php foreach ($interaksiList as $row): ?>
                        <tr>
                            <td><?= e($row['obat1']); ?> + <?= e($row['obat2']); ?></td>
                            <td><span class="badge <?= $row['tingkat_bahaya'] === 'Merah' ? 'badge-merah' : 'badge-kuning'; ?>"><?= e($row['tingkat_bahaya']); ?></span></td>
                            <td><?= e($row['deskripsi_efek']); ?></td>
                            <td class="text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="?edit=<?= e($row['id']); ?>">Edit</a>
                                <a class="btn btn-sm btn-outline-danger" href="?hapus=<?= e($row['id']); ?>" onclick="return confirm('Hapus aturan ini?')">Hapus</a>
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
