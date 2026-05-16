<?php
require_once 'koneksi.php';
requireDatabaseReady($pdo, true);
header('Content-Type: application/json; charset=utf-8');

$range = $_GET['range'] ?? 'hari';
$allowed = ['hari', 'minggu', '1bulan', '3bulan', '6bulan', '1tahun'];
if (!in_array($range, $allowed, true)) {
    $range = 'hari';
}

$labels = [];
$keys = [];
$format = '%Y-%m-%d';
$start = new DateTime('today');
$end = new DateTime('now');

if ($range === 'hari') {
    $start = new DateTime('today');
    $format = '%Y-%m-%d %H';
    for ($hour = 0; $hour < 24; $hour++) {
        $key = $start->format('Y-m-d') . ' ' . str_pad((string) $hour, 2, '0', STR_PAD_LEFT);
        $keys[] = $key;
        $labels[] = str_pad((string) $hour, 2, '0', STR_PAD_LEFT) . ':00';
    }
} elseif ($range === 'minggu') {
    $start = (new DateTime('today'))->modify('-6 days');
    for ($i = 0; $i < 7; $i++) {
        $date = (clone $start)->modify("+{$i} days");
        $keys[] = $date->format('Y-m-d');
        $labels[] = $date->format('d/m');
    }
} elseif ($range === '1bulan') {
    $start = (new DateTime('today'))->modify('-29 days');
    for ($i = 0; $i < 30; $i++) {
        $date = (clone $start)->modify("+{$i} days");
        $keys[] = $date->format('Y-m-d');
        $labels[] = $date->format('d/m');
    }
} else {
    $months = ['3bulan' => 3, '6bulan' => 6, '1tahun' => 12][$range];
    $start = (new DateTime('first day of this month'))->modify('-' . ($months - 1) . ' months');
    $format = '%Y-%m';
    for ($i = 0; $i < $months; $i++) {
        $date = (clone $start)->modify("+{$i} months");
        $keys[] = $date->format('Y-m');
        $labels[] = $date->format('M Y');
    }
}

$stmt = $pdo->prepare(
    "SELECT DATE_FORMAT(tanggal, ?) AS periode, COUNT(*) AS jumlah
     FROM resep
     WHERE tanggal >= ? AND tanggal <= ?
     GROUP BY periode
     ORDER BY periode"
);
$stmt->execute([$format, $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')]);
$rows = $stmt->fetchAll();

$valuesByKey = array_fill_keys($keys, 0);
foreach ($rows as $row) {
    if (array_key_exists($row['periode'], $valuesByKey)) {
        $valuesByKey[$row['periode']] = (int) $row['jumlah'];
    }
}

echo json_encode([
    'range' => $range,
    'labels' => $labels,
    'values' => array_values($valuesByKey),
]);
