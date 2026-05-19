<?php
require_once __DIR__ . '/../../api/middleware/auth.php';
setCorsHeaders();
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/database.php';
use \Firebase\JWT\JWT;
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan.']);
    exit;
}
$data = json_decode(file_get_contents('php://input'));
if (empty($data->credential)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Token Google tidak ditemukan.']);
    exit;
}
$id_token = $data->credential;
$client_id = $_ENV['GOOGLE_CLIENT_ID'] ?? getenv('GOOGLE_CLIENT_ID') ?? '';
$verify_url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($id_token);
$ch = curl_init($verify_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($http_code !== 200 || !$response) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Token Google tidak valid.']);
    exit;
}
$google_data = json_decode($response, true);
if ($google_data['aud'] !== $client_id) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Token Google tidak sesuai dengan aplikasi ini.']);
    exit;
}
$google_id   = $google_data['sub'];
$email       = $google_data['email'];
$name        = $google_data['name'] ?? $email;
$avatar_url  = $google_data['picture'] ?? null;
$stmt = $pdo->prepare('SELECT id, name, email, role, avatar_url, google_id FROM users WHERE google_id = :gid OR email = :email LIMIT 1');
$stmt->execute([':gid' => $google_id, ':email' => $email]);
$user = $stmt->fetch();
if ($user) {
    if (empty($user['google_id'])) {
        $updateStmt = $pdo->prepare('UPDATE users SET google_id = :gid, avatar_url = :avatar WHERE id = :id');
        $updateStmt->execute([':gid' => $google_id, ':avatar' => $avatar_url, ':id' => $user['id']]);
    }
} else {
    $insertStmt = $pdo->prepare('INSERT INTO users (name, email, google_id, avatar_url, role) VALUES (:name, :email, :gid, :avatar, :role)');
    $insertStmt->execute([
        ':name'   => $name,
        ':email'  => $email,
        ':gid'    => $google_id,
        ':avatar' => $avatar_url,
        ':role'   => 'USER',
    ]);
    $user = [
        'id'         => $pdo->lastInsertId(),
        'name'       => $name,
        'email'      => $email,
        'role'       => 'USER',
        'avatar_url' => $avatar_url,
    ];
}
$stmt = $pdo->prepare('SELECT id, name, email, role, avatar_url FROM users WHERE id = :id');
$stmt->execute([':id' => $user['id']]);
$user = $stmt->fetch();
$secret_key      = $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET') ?? 'silaf_jwt_secret_key_super_kuat_2024';
$issued_at       = time();
$expiration_time = $issued_at + (60 * 60 * 24 * 7);
$payload = [
    'iss'  => 'silaf-app',
    'iat'  => $issued_at,
    'exp'  => $expiration_time,
    'data' => [
        'id'         => $user['id'],
        'name'       => $user['name'],
        'email'      => $user['email'],
        'role'       => $user['role'],
        'avatar_url' => $user['avatar_url'],
    ]
];
$jwt = JWT::encode($payload, $secret_key, 'HS256');
http_response_code(200);
echo json_encode([
    'status'  => 'success',
    'message' => 'Login Google berhasil!',
    'token'   => $jwt,
    'user'    => [
        'id'         => $user['id'],
        'name'       => $user['name'],
        'email'      => $user['email'],
        'role'       => $user['role'],
        'avatar_url' => $user['avatar_url'],
    ]
]);
