<?php
require_once __DIR__ . '/../../api/middleware/auth.php';
setCorsHeaders();
require_once __DIR__ . '/../../config/database.php';
$currentUser = getAuthUser();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan.']);
    exit;
}
$data = json_decode(file_get_contents('php://input'));
$required = ['type', 'item_name', 'location', 'event_time', 'contact_info'];
foreach ($required as $field) {
    if (empty($data->$field)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "Field '$field' wajib diisi."]);
        exit;
    }
}
if (!in_array($data->type, ['LOST', 'FOUND'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => "Type harus 'LOST' atau 'FOUND'."]);
    exit;
}
try {
    $stmt = $pdo->prepare("
        INSERT INTO items (user_id, type, item_name, description, image_url, location, event_time, contact_info, status)
        VALUES (:user_id, :type, :item_name, :description, :image_url, :location, :event_time, :contact_info, 'ACTIVE')
    ");
    $stmt->execute([
        ':user_id'      => $currentUser['id'],
        ':type'         => $data->type,
        ':item_name'    => htmlspecialchars(strip_tags($data->item_name)),
        ':description'  => isset($data->description) ? htmlspecialchars(strip_tags($data->description)) : null,
        ':image_url'    => $data->image_url ?? null,
        ':location'     => htmlspecialchars(strip_tags($data->location)),
        ':event_time'   => $data->event_time,
        ':contact_info' => htmlspecialchars(strip_tags($data->contact_info)),
    ]);
    $newId = $pdo->lastInsertId();
    $logStmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action) VALUES (:uid, :action)");
    $logStmt->execute([
        ':uid'    => $currentUser['id'],
        ':action' => "Membuat laporan " . $data->type . " #" . $newId . " - " . $data->item_name,
    ]);
    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Laporan berhasil dibuat.', 'id' => $newId]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal membuat laporan: ' . $e->getMessage()]);
}
