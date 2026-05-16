<?php
require_once 'koneksi.php';
requireDatabaseReady($pdo);

$resepId = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare(
    "SELECT r.*, p.nama_lengkap, p.usia
     FROM resep r
     JOIN pasien p ON p.id = r.pasien_id
     WHERE r.id = ?"
);
$stmt->execute([$resepId]);
$resep = $stmt->fetch();

if (!$resep) {
    http_response_code(404);
    die('Resep tidak ditemukan.');
}

$details = getResepDetails($pdo, $resepId);

function pdfEscape(string $text): string
{
    $text = str_replace(["\\", "(", ")", "\r"], ["\\\\", "\\(", "\\)", ""], $text);
    return str_replace("\n", " ", $text);
}

function wrapText(string $text, int $limit = 34): array
{
    $words = preg_split('/\s+/', trim($text));
    $lines = [];
    $line = '';
    foreach ($words as $word) {
        if ($line !== '' && strlen($line . ' ' . $word) > $limit) {
            $lines[] = $line;
            $line = $word;
        } else {
            $line = $line === '' ? $word : $line . ' ' . $word;
        }
    }
    if ($line !== '') {
        $lines[] = $line;
    }
    return $lines ?: [''];
}

$lines = [
    ['text' => 'CDSS-Med', 'size' => 13, 'bold' => true],
    ['text' => 'Resep Dokter Praktik Mandiri', 'size' => 8],
    ['text' => 'No: #' . $resep['id'] . ' | ' . date('d/m/Y H:i', strtotime($resep['tanggal'])), 'size' => 8],
    ['text' => 'Pasien: ' . $resep['nama_lengkap'] . ' (' . $resep['usia'] . ' th)', 'size' => 8],
    ['text' => '--------------------------------', 'size' => 8],
];

foreach ($details as $index => $detail) {
    $lines[] = ['text' => ($index + 1) . '. ' . $detail['nama_obat'], 'size' => 9, 'bold' => true];
    foreach (wrapText('Dosis: ' . $detail['dosis'], 36) as $line) {
        $lines[] = ['text' => '   ' . $line, 'size' => 8];
    }
    foreach (wrapText('Aturan: ' . $detail['aturan_pakai'], 36) as $line) {
        $lines[] = ['text' => '   ' . $line, 'size' => 8];
    }
}

$lines[] = ['text' => '--------------------------------', 'size' => 8];
$lines[] = ['text' => 'Catatan: biaya tidak dicetak pada resep.', 'size' => 7];

$content = "BT\n/F1 10 Tf\n14 320 Td\n";
$currentSize = 10;
foreach ($lines as $line) {
    $size = $line['size'];
    if ($size !== $currentSize) {
        $content .= "/F1 {$size} Tf\n";
        $currentSize = $size;
    }
    $content .= '(' . pdfEscape($line['text']) . ") Tj\n0 -14 Td\n";
}
$content .= "ET";

$objects = [];
$objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
$objects[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
$objects[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 226.77 340.16] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n";
$objects[] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
$objects[] = "5 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n{$content}\nendstream\nendobj\n";

$pdf = "%PDF-1.4\n";
$offsets = [0];
foreach ($objects as $object) {
    $offsets[] = strlen($pdf);
    $pdf .= $object;
}
$xrefOffset = strlen($pdf);
$pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
$pdf .= "0000000000 65535 f \n";
for ($i = 1; $i <= count($objects); $i++) {
    $pdf .= str_pad((string) $offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
}
$pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="resep-' . $resepId . '.pdf"');
header('Content-Length: ' . strlen($pdf));
echo $pdf;
