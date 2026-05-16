<?php
require_once 'koneksi.php';
requireDatabaseReady($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $nama = trim($_POST['nama_obat'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    $harga = (float) ($_POST['harga'] ?? 0);

    if ($nama !== '') {
        if ($id > 0) {
            $stmt = $pdo->prepare('UPDATE obat SET nama_obat = ?, kategori = ?, harga = ? WHERE id = ?');
            $stmt->execute([$nama, $kategori, $harga, $id]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO obat (nama_obat, kategori, harga) VALUES (?, ?, ?)');
            $stmt->execute([$nama, $kategori, $harga]);
        }
    }
    redirect('obat.php');
}

if (isset($_GET['hapus'])) {
    $stmt = $pdo->prepare('DELETE FROM obat WHERE id = ?');
    $stmt->execute([(int) $_GET['hapus']]);
    redirect('obat.php');
}

$edit = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM obat WHERE id = ?');
    $stmt->execute([(int) $_GET['edit']]);
    $edit = $stmt->fetch();
}

$obatList = $pdo->query('SELECT * FROM obat ORDER BY nama_obat')->fetchAll();
require 'header.php';
?>
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-white fw-semibold"><?= $edit ? 'Edit Obat' : 'Tambah Obat'; ?></div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="id" value="<?= e($edit['id'] ?? ''); ?>">
                    <div class="mb-3">
                        <label class="form-label required">Nama Obat</label>
                        <input type="text" name="nama_obat" class="form-control" value="<?= e($edit['nama_obat'] ?? ''); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <input type="text" name="kategori" class="form-control" value="<?= e($edit['kategori'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label required">Harga Obat</label>
                        <input type="number" name="harga" class="form-control" min="0" step="500" value="<?= e($edit['harga'] ?? '0'); ?>" required>
                        <div class="form-text">Harga dipakai untuk menghitung biaya resep di website.</div>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Simpan Obat</button>
                    <?php if ($edit): ?><a class="btn btn-link w-100" href="obat.php">Batal Edit</a><?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white fw-semibold">Master Obat</div>
            <div class="card-body table-responsive">
                <table class="table table-hover align-middle">
                    <thead><tr><th>Nama Obat</th><th>Kategori</th><th>Harga</th><th>Aksi</th></tr></thead>
                    <tbody>
                    <?php foreach ($obatList as $obat): ?>
                        <tr>
                            <td><?= e($obat['nama_obat']); ?></td>
                            <td><?= e($obat['kategori']); ?></td>
                            <td><?= e(formatRupiah((float) $obat['harga'])); ?></td>
                            <td class="text-nowrap">
                                <a class="btn btn-sm btn-outline-primary" href="?edit=<?= e($obat['id']); ?>">Edit</a>
                                <a class="btn btn-sm btn-outline-danger" href="?hapus=<?= e($obat['id']); ?>" onclick="return confirm('Hapus obat ini? Data terkait juga dapat terdampak.')">Hapus</a>
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