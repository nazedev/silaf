<?php
require_once __DIR__ . '/../../api/middleware/auth.php';
setCorsHeaders();
require_once __DIR__ . '/../../config/database.php';
$currentUser = getAuthUser();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan.']);
    exit;
}
$type     = $_GET['type']   ?? null;   // LOST | FOUND
$status   = $_GET['status'] ?? 'ACTIVE'; // ACTIVE | RESOLVED | ALL
$search   = $_GET['search'] ?? '';
$page     = max(1, (int)($_GET['page'] ?? 1));
$limit    = min(20, max(1, (int)($_GET['limit'] ?? 10)));
$offset   = ($page - 1) * $limit;
$mine     = $_GET['mine']   ?? null;   // 'true' = hanya milik sendiri
$conditions = ['i.deleted_at IS NULL'];
$params     = [];
if ($type && in_array($type, ['LOST', 'FOUND'])) {
    $conditions[] = 'i.type = :type';
    $params[':type'] = $type;
}
if ($status && $status !== 'ALL') {
    if (in_array($status, ['ACTIVE', 'RESOLVED'])) {
        $conditions[] = 'i.status = :status';
        $params[':status'] = $status;
    }
}
if ($search) {
    $conditions[] = '(i.item_name LIKE :search1 OR i.location LIKE :search2 OR i.description LIKE :search3 OR u.name LIKE :search4 OR i.event_time LIKE :search5)';
    $searchWildcard = '%' . $search . '%';
    $params[':search1'] = $searchWildcard;
    $params[':search2'] = $searchWildcard;
    $params[':search3'] = $searchWildcard;
    $params[':search4'] = $searchWildcard;
    $params[':search5'] = $searchWildcard;
}
if ($mine === 'true') {
    $conditions[] = 'i.user_id = :uid';
    $params[':uid'] = $currentUser['id'];
}
$where = implode(' AND ', $conditions);
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM items i JOIN users u ON i.user_id = u.id WHERE $where");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$stmt = $pdo->prepare("
    SELECT i.*, u.name AS reporter_name, u.avatar_url AS reporter_avatar
    FROM items i
    JOIN users u ON i.user_id = u.id
    WHERE $where
    ORDER BY i.created_at DESC
    LIMIT :limit OFFSET :offset
");
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$items = $stmt->fetchAll();
http_response_code(200);
echo json_encode([
    'status' => 'success',
    'data'   => $items,
    'meta'   => [
        'total'       => $total,
        'page'        => $page,
        'limit'       => $limit,
        'total_pages' => ceil($total / $limit),
    ]
]);
