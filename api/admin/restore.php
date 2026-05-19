<?php
require_once __DIR__ . '/../../api/middleware/auth.php';
setCorsHeaders();
require_once __DIR__ . '/../../config/database.php';
$adminUser = requireAdmin();
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data   = json_decode(file_get_contents('php://input'));
    $itemId = (int)($data->id ?? 0);
    if (!$itemId) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID item wajib disertakan.']);
        exit;
    }
    $stmt = $pdo->prepare('UPDATE items SET deleted_at = NULL WHERE id = :id');
    $stmt->execute([':id' => $itemId]);
    $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action) VALUES (:uid, :action)");
    $logStmt->execute([
        ':uid'    => $adminUser['id'],
        ':action' => "Memulihkan (undo) laporan ID #" . $itemId,
    ]);
    echo json_encode(['status' => 'success', 'message' => 'Laporan berhasil dipulihkan.']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $itemId = (int)($_GET['id'] ?? 0);
    if (!$itemId) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID item wajib disertakan.']);
        exit;
    }
    $stmt = $pdo->prepare('DELETE FROM items WHERE id = :id');
    $stmt->execute([':id' => $itemId]);
    $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action) VALUES (:uid, :action)");
    $logStmt->execute([
        ':uid'    => $adminUser['id'],
        ':action' => "Menghapus permanen laporan ID #" . $itemId,
    ]);
    echo json_encode(['status' => 'success', 'message' => 'Laporan dihapus permanen.']);
    exit;
}
http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan.']);
