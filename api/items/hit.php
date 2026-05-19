<?php
require_once __DIR__ . '/../../api/middleware/auth.php';
setCorsHeaders();
require_once __DIR__ . '/../../config/database.php';
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan.']);
    exit;
}
$currentUser = getAuthUser();
$input = json_decode(file_get_contents('php://input'), true);
$itemId = $input['id'] ?? null;
if (!$itemId) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID item wajib diisi.']);
    exit;
}
try {
    $stmt = $pdo->prepare("SELECT user_id FROM items WHERE id = :id");
    $stmt->execute([':id' => $itemId]);
    $item = $stmt->fetch();
    if ($item) {
        if ($item['user_id'] != $currentUser['id']) {
            $updateStmt = $pdo->prepare("UPDATE items SET hits = hits + 1 WHERE id = :id");
            $updateStmt->execute([':id' => $itemId]);
            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'Hit recorded.']);
        } else {
            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'Same user, hit ignored.']);
        }
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Item tidak ditemukan.']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan server.']);
}
