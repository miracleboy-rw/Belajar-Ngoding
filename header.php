<?php
$currentPage = basename($_SERVER['PHP_SELF']);
$menus = [
    'index.php' => 'Dashboard',
    'pasien.php' => 'Pasien',
    'obat.php' => 'Obat',
    'interaksi.php' => 'Interaksi',
    'e_prescribing.php' => 'E-Prescribing',
];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>CDSS-Med</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f7f9fc; }
        .navbar-brand { font-weight: 700; letter-spacing: .3px; }
        .card { border: 0; box-shadow: 0 10px 30px rgba(15, 23, 42, .08); }
        .badge-merah { background: #dc3545; }
        .badge-kuning { background: #ffc107; color: #212529; }
        .required::after { content: ' *'; color: #dc3545; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="index.php">CDSS-Med</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php foreach ($menus as $file => $label): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $currentPage === $file ? 'active fw-semibold' : ''; ?>" href="<?= $file; ?>"><?= $label; ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</nav>
<main class="container py-4">
