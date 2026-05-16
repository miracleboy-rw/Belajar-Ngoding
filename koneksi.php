diff --git a/koneksi.php b/koneksi.php
new file mode 100644
index 0000000000000000000000000000000000000000..38daacd08084eb0c45163d1cef9ac95f016ab83b
--- /dev/null
+++ b/koneksi.php
@@ -0,0 +1,189 @@
+<?php
+$host = 'localhost';
+$dbname = 'cdss_med';
+$username = 'root';
+$password = '';
+$charset = 'utf8mb4';
+
+$options = [
+    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
+    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
+    PDO::ATTR_EMULATE_PREPARES => false,
+];
+
+try {
+    $serverDsn = "mysql:host={$host};charset={$charset}";
+    $pdo = new PDO($serverDsn, $username, $password, $options);
+    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
+    $pdo->exec("USE `{$dbname}`");
+} catch (PDOException $e) {
+    http_response_code(500);
+    die('Koneksi database gagal. Periksa konfigurasi MySQL di koneksi.php dan pastikan service MySQL/XAMPP sudah berjalan.');
+}
+
+const REQUIRED_TABLES = [
+    'pasien',
+    'obat',
+    'alergi_pasien',
+    'aturan_interaksi',
+    'resep',
+    'detail_resep',
+];
+
+function e(?string $value): string
+{
+    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
+}
+
+function redirect(string $url): void
+{
+    header("Location: {$url}");
+    exit;
+}
+
+function formatRupiah(float $amount): string
+{
+    return 'Rp ' . number_format($amount, 0, ',', '.');
+}
+
+function getMissingTables(PDO $pdo, array $requiredTables = REQUIRED_TABLES): array
+{
+    $stmt = $pdo->query('SHOW TABLES');
+    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
+    return array_values(array_diff($requiredTables, $existingTables));
+}
+
+function isDatabaseReady(PDO $pdo): bool
+{
+    return getMissingTables($pdo) === [];
+}
+
+function columnExists(PDO $pdo, string $table, string $column): bool
+{
+    $stmt = $pdo->prepare("SHOW COLUMNS FROM `{$table}` LIKE ?");
+    $stmt->execute([$column]);
+    return (bool) $stmt->fetch();
+}
+
+function ensureSchemaUpdates(PDO $pdo): void
+{
+    if (!columnExists($pdo, 'obat', 'harga')) {
+        $pdo->exec('ALTER TABLE obat ADD harga DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER kategori');
+    }
+
+    if (!columnExists($pdo, 'resep', 'total_biaya')) {
+        $pdo->exec('ALTER TABLE resep ADD total_biaya DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER tanggal');
+    }
+
+    if (!columnExists($pdo, 'detail_resep', 'harga_satuan')) {
+        $pdo->exec('ALTER TABLE detail_resep ADD harga_satuan DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER aturan_pakai');
+    }
+
+    if (!columnExists($pdo, 'detail_resep', 'subtotal')) {
+        $pdo->exec('ALTER TABLE detail_resep ADD subtotal DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER harga_satuan');
+    }
+}
+
+function importDatabaseSql(PDO $pdo, string $sqlFile = __DIR__ . '/database.sql'): void
+{
+    if (!is_file($sqlFile)) {
+        throw new RuntimeException('File database.sql tidak ditemukan.');
+    }
+
+    $sql = file_get_contents($sqlFile);
+    if ($sql === false || trim($sql) === '') {
+        throw new RuntimeException('File database.sql kosong atau tidak dapat dibaca.');
+    }
+
+    $statements = array_filter(array_map('trim', explode(';', $sql)));
+    foreach ($statements as $statement) {
+        $pdo->exec($statement);
+    }
+}
+
+function requireDatabaseReady(PDO $pdo, bool $jsonResponse = false): void
+{
+    $missingTables = getMissingTables($pdo);
+    if ($missingTables === []) {
+        ensureSchemaUpdates($pdo);
+        return;
+    }
+
+    if ($jsonResponse) {
+        http_response_code(503);
+        header('Content-Type: application/json; charset=utf-8');
+        echo json_encode([
+            'status' => 'error',
+            'message' => 'Database belum siap. Import database.sql atau buka setup.php terlebih dahulu.',
+            'missing_tables' => $missingTables,
+        ]);
+        exit;
+    }
+
+    http_response_code(503);
+    $missingList = implode(', ', array_map('e', $missingTables));
+    echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
+    echo '<title>Setup Database CDSS-Med</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>';
+    echo '<body class="bg-light"><main class="container py-5"><div class="card shadow-sm border-0"><div class="card-body p-4">';
+    echo '<h1 class="h4">Database CDSS-Med belum siap</h1>';
+    echo '<p class="text-muted">Koneksi MySQL berhasil, tetapi tabel aplikasi belum ditemukan.</p>';
+    echo '<div class="alert alert-warning">Tabel yang belum ada: <strong>' . $missingList . '</strong></div>';
+    echo '<p>Solusi cepat untuk XAMPP/phpMyAdmin:</p><ol>';
+    echo '<li>Buka <code>http://localhost/cdss/setup.php</code> untuk membuat tabel otomatis dari <code>database.sql</code>, atau</li>';
+    echo '<li>Import manual file <code>database.sql</code> ke database <code>cdss_med</code> melalui phpMyAdmin.</li>';
+    echo '</ol><a class="btn btn-primary" href="setup.php">Jalankan Setup Database</a> ';
+    echo '<a class="btn btn-outline-secondary" href="index.php">Coba Lagi</a>';
+    echo '</div></div></main></body></html>';
+    exit;
+}
+
+function getResepDetails(PDO $pdo, int $resepId): array
+{
+    $stmt = $pdo->prepare(
+        "SELECT dr.*, o.nama_obat, o.kategori
+         FROM detail_resep dr
+         JOIN obat o ON o.id = dr.obat_id
+         WHERE dr.resep_id = ?
+         ORDER BY dr.id"
+    );
+    $stmt->execute([$resepId]);
+    return $stmt->fetchAll();
+}
+
+function analyzePrescription(PDO $pdo, int $resepId): array
+{
+    $details = getResepDetails($pdo, $resepId);
+    $obatIds = array_values(array_unique(array_map('intval', array_column($details, 'obat_id'))));
+    $obatNames = array_column($details, 'nama_obat');
+    $severity = 'Hijau';
+    $pairs = [];
+
+    if (count($obatIds) > 1) {
+        $placeholders = implode(',', array_fill(0, count($obatIds), '?'));
+        $stmt = $pdo->prepare(
+            "SELECT ai.tingkat_bahaya, o1.nama_obat AS obat1, o2.nama_obat AS obat2
+             FROM aturan_interaksi ai
+             JOIN obat o1 ON o1.id = ai.obat1_id
+             JOIN obat o2 ON o2.id = ai.obat2_id
+             WHERE ai.obat1_id IN ({$placeholders}) AND ai.obat2_id IN ({$placeholders})"
+        );
+        $stmt->execute(array_merge($obatIds, $obatIds));
+        $interactions = $stmt->fetchAll();
+
+        foreach ($interactions as $interaction) {
+            $pairs[] = $interaction['obat1'] . ' + ' . $interaction['obat2'];
+            if ($interaction['tingkat_bahaya'] === 'Merah') {
+                $severity = 'Merah';
+            } elseif ($severity !== 'Merah') {
+                $severity = 'Kuning';
+            }
+        }
+    }
+
+    return [
+        'details' => $details,
+        'obat_label' => implode(' + ', $obatNames),
+        'pasangan_label' => $pairs ? implode('; ', array_unique($pairs)) : implode(' + ', $obatNames),
+        'tingkat' => $severity,
+    ];
+}