<?php
require_once __DIR__ . '/../../api/middleware/auth.php';
setCorsHeaders();
require_once __DIR__ . '/../../config/database.php';
$currentUser = getAuthUser();
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan.']);
    exit;
}
$data = json_decode(file_get_contents('php://input'));
if (empty($data->id)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'ID item wajib disertakan.']);
    exit;
}
$itemId = (int) $data->id;
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
    echo json_encode(['status' => 'error', 'message' => 'Anda tidak memiliki izin untuk mengubah item ini.']);
    exit;
}
$fields = [];
$params = [':id' => $itemId];
$allowedFields = ['item_name', 'description', 'location', 'event_time', 'contact_info', 'status', 'image_url'];
foreach ($allowedFields as $field) {
    if (isset($data->$field)) {
        $fields[] = "$field = :$field";
        $params[":$field"] = is_string($data->$field)
            ? htmlspecialchars(strip_tags($data->$field))
            : $data->$field;
    }
}
if (empty($fields)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Tidak ada field yang diupdate.']);
    exit;
}
try {
    $sql = 'UPDATE items SET ' . implode(', ', $fields) . ' WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action) VALUES (:uid, :action)");
    $logStmt->execute([
        ':uid'    => $currentUser['id'],
        ':action' => "Mengupdate laporan #" . $itemId,
    ]);
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Laporan berhasil diperbarui.']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal update: ' . $e->getMessage()]);
}
