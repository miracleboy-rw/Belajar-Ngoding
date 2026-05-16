<?php
require_once 'koneksi.php';

$message = '';
$alertClass = 'info';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        importDatabaseSql($pdo);
        $message = 'Setup berhasil. Database, tabel, dan dummy data CDSS-Med sudah dibuat ulang dari database.sql.';
        $alertClass = 'success';
    } catch (Throwable $e) {
        $message = 'Setup gagal: ' . $e->getMessage();
        $alertClass = 'danger';
    }
}

if (getMissingTables($pdo) === []) {
    ensureSchemaUpdates($pdo);
}

$missingAfter = getMissingTables($pdo);
$isReady = $missingAfter === [];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Setup Database CDSS-Med</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<main class="container py-5">
    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <h1 class="h4 mb-3">Setup Database CDSS-Med</h1>
            <p class="text-muted">Gunakan halaman ini jika muncul error tabel seperti <code>Table 'cdss_med.pasien' doesn't exist</code>.</p>

            <?php if ($message): ?>
                <div class="alert alert-<?= e($alertClass); ?>"><?= e($message); ?></div>
            <?php endif; ?>

            <?php if ($isReady): ?>
                <div class="alert alert-success">Database siap digunakan. Semua tabel wajib sudah tersedia.</div>
                <a href="index.php" class="btn btn-primary">Buka Dashboard</a>
            <?php else: ?>
                <div class="alert alert-warning">
                    Tabel yang belum ditemukan: <strong><?= e(implode(', ', $missingAfter)); ?></strong>
                </div>
                <form method="post" onsubmit="return confirm('Setup akan menjalankan database.sql. Jika tabel sudah ada, data lama dapat dibuat ulang sesuai isi file SQL. Lanjutkan?')">
                    <button type="submit" class="btn btn-primary">Jalankan database.sql</button>
                    <a href="index.php" class="btn btn-outline-secondary">Kembali</a>
                </form>
            <?php endif; ?>

            <hr>
            <p class="small text-muted mb-0">
                Alternatif manual: buka phpMyAdmin, pilih/import file <code>database.sql</code>, lalu pastikan database bernama <code>cdss_med</code>.
            </p>
        </div>
    </div>
</main>
</body>
</html>
