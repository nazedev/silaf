<?php
require_once __DIR__ . '/../../api/middleware/auth.php';
setCorsHeaders();
require_once __DIR__ . '/../../config/database.php';
$currentUser = getAuthUser();
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan.']);
    exit;
}
$itemId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$itemId) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID item wajib disertakan.']);
    exit;
}
$stmt = $pdo->prepare('SELECT * FROM items WHERE id = :id AND deleted_at IS NULL');
$stmt->execute([':id' => $itemId]);
$item = $stmt->fetch();
if (!$item) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Item tidak ditemukan.']);
    exit;
}
if ($item['user_id'] !== $currentUser['id'] && $currentUser['role'] !== 'ADMIN') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki izin untuk menghapus item ini.']);
    exit;
}
try {
    $stmt = $pdo->prepare('UPDATE items SET deleted_at = NOW() WHERE id = :id');
    $stmt->execute([':id' => $itemId]);
    $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action) VALUES (:uid, :action)");
    $logStmt->execute([
        ':uid'    => $currentUser['id'],
        ':action' => "Menghapus laporan ID #" . $itemId . " - " . $item['item_name'],
    ]);
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Laporan berhasil dihapus.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus: ' . $e->getMessage()]);
}
