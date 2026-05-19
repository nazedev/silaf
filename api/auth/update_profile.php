<?php
require_once __DIR__ . '/../../api/middleware/auth.php';
setCorsHeaders();
require_once __DIR__ . '/../../config/database.php';
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan.']);
    exit;
}
$currentUser = getAuthUser();
$input = json_decode(file_get_contents('php://input'), true);
$newName = trim($input['name'] ?? '');
if (empty($newName)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Nama tidak boleh kosong.']);
    exit;
}
if (strlen($newName) > 100) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Nama terlalu panjang (maksimal 100 karakter).']);
    exit;
}
try {
    $stmt = $pdo->prepare("UPDATE users SET name = :name, updated_at = CURRENT_TIMESTAMP WHERE id = :id");
    $stmt->execute([
        ':name' => $newName,
        ':id' => $currentUser['id']
    ]);
    $stmt = $pdo->prepare("SELECT id, email, name, avatar_url, role FROM users WHERE id = :id");
    $stmt->execute([':id' => $currentUser['id']]);
    $updatedUser = $stmt->fetch();
    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'message' => 'Profil berhasil diperbarui.',
        'user' => $updatedUser
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Terjadi kesalahan pada server.']);
}
