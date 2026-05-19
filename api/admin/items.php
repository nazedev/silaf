<?php
require_once __DIR__ . '/../../api/middleware/auth.php';
setCorsHeaders();
require_once __DIR__ . '/../../config/database.php';
$adminUser = requireAdmin();
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan.']);
    exit;
}
$showDeleted = ($_GET['deleted'] ?? '') === 'true';
$type        = $_GET['type']   ?? null;
$status      = $_GET['status'] ?? null;
$search      = $_GET['search'] ?? '';
$page        = max(1, (int)($_GET['page']  ?? 1));
$limit       = min(50, max(1, (int)($_GET['limit'] ?? 20)));
$offset      = ($page - 1) * $limit;
$conditions = [];
$params     = [];
if ($showDeleted) {
    $conditions[] = 'i.deleted_at IS NOT NULL';
} // else: tampilkan semua (termasuk deleted dan non-deleted)
if ($type && in_array($type, ['LOST', 'FOUND'])) {
    $conditions[] = 'i.type = :type';
    $params[':type'] = $type;
}
if ($status && in_array($status, ['ACTIVE', 'RESOLVED'])) {
    $conditions[] = 'i.status = :status';
    $params[':status'] = $status;
}
if ($search) {
    $conditions[] = '(i.item_name LIKE :search OR i.location LIKE :search OR u.name LIKE :search)';
    $params[':search'] = '%' . $search . '%';
}
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM items i JOIN users u ON i.user_id = u.id $where");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$stmt = $pdo->prepare("
    SELECT i.*, u.name AS reporter_name, u.email AS reporter_email
    FROM items i
    JOIN users u ON i.user_id = u.id
    $where
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
$statsStmt = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN deleted_at IS NULL AND status='ACTIVE' THEN 1 ELSE 0 END) AS active,
        SUM(CASE WHEN deleted_at IS NULL AND status='RESOLVED' THEN 1 ELSE 0 END) AS resolved,
        SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END) AS deleted,
        SUM(CASE WHEN type='LOST' AND deleted_at IS NULL THEN 1 ELSE 0 END) AS lost,
        SUM(CASE WHEN type='FOUND' AND deleted_at IS NULL THEN 1 ELSE 0 END) AS found
    FROM items
");
$stats = $statsStmt->fetch();
$userCountStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'USER'");
$stats['total_users'] = (int) $userCountStmt->fetchColumn();
echo json_encode([
    'status' => 'success',
    'data'   => $items,
    'stats'  => $stats,
    'meta'   => [
        'total'       => $total,
        'page'        => $page,
        'limit'       => $limit,
        'total_pages' => ceil($total / $limit),
    ]
]);
