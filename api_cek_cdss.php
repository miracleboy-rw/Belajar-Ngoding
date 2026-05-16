<?php
require_once 'koneksi.php';
requireDatabaseReady($pdo, true);
header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST ?: $_GET;
}

$pasienId = (int) ($input['pasien_id'] ?? 0);
$obatBaruId = (int) ($input['obat_id'] ?? 0);
$keranjang = $input['keranjang_obat'] ?? [];

if (!is_array($keranjang)) {
    $keranjang = array_filter(array_map('trim', explode(',', (string) $keranjang)));
}
$keranjang = array_values(array_unique(array_filter(array_map('intval', $keranjang), fn ($id) => $id > 0 && $id !== $obatBaruId)));

$response = [
    'status' => 'aman',
    'alerts' => [],
];

if ($pasienId <= 0 || $obatBaruId <= 0) {
    http_response_code(422);
    echo json_encode([
        'status' => 'error',
        'message' => 'pasien_id dan obat_id wajib dikirim.',
    ]);
    exit;
}

$stmt = $pdo->prepare(
    "SELECT o.nama_obat
     FROM alergi_pasien ap
     JOIN obat o ON o.id = ap.obat_id
     WHERE ap.pasien_id = ? AND ap.obat_id = ?
     LIMIT 1"
);
$stmt->execute([$pasienId, $obatBaruId]);
$alergi = $stmt->fetch();

if ($alergi) {
    $response['status'] = 'bahaya';
    $response['alerts'][] = [
        'tipe' => 'alergi',
        'tingkat_bahaya' => 'Merah',
        'judul' => 'KONTRAINDIKASI ALERGI',
        'pesan' => 'Pasien tercatat memiliki alergi/kontraindikasi terhadap ' . $alergi['nama_obat'] . '.',
    ];
}

if ($keranjang) {
    $placeholders = implode(',', array_fill(0, count($keranjang), '?'));
    $params = array_merge([$obatBaruId], $keranjang, $keranjang, [$obatBaruId]);
    $stmt = $pdo->prepare(
        "SELECT ai.tingkat_bahaya, ai.deskripsi_efek, o1.nama_obat AS obat1, o2.nama_obat AS obat2
         FROM aturan_interaksi ai
         JOIN obat o1 ON o1.id = ai.obat1_id
         JOIN obat o2 ON o2.id = ai.obat2_id
         WHERE (ai.obat1_id = ? AND ai.obat2_id IN ({$placeholders}))
            OR (ai.obat1_id IN ({$placeholders}) AND ai.obat2_id = ?)"
    );
    $stmt->execute($params);
    $interaksi = $stmt->fetchAll();

    foreach ($interaksi as $row) {
        if ($row['tingkat_bahaya'] === 'Merah') {
            $response['status'] = 'bahaya';
        } elseif ($response['status'] !== 'bahaya') {
            $response['status'] = 'peringatan';
        }

        $response['alerts'][] = [
            'tipe' => 'interaksi',
            'tingkat_bahaya' => $row['tingkat_bahaya'],
            'judul' => 'INTERAKSI OBAT ' . strtoupper($row['tingkat_bahaya']),
            'pesan' => $row['obat1'] . ' + ' . $row['obat2'] . ': ' . $row['deskripsi_efek'],
        ];
    }
}

echo json_encode($response);
