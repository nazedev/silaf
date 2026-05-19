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
if (empty($data->email) || empty($data->password) || empty($data->secret_code)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Email, password, dan kode rahasia wajib diisi.']);
    exit;
}
$email       = trim($data->email);
$password    = $data->password;
$secret_code = $data->secret_code;
$admin_secret = $_ENV['ADMIN_SECRET_CODE'] ?? getenv('ADMIN_SECRET_CODE') ?? 'SILAF_ADMIN_2026_MANTAP';
if ($secret_code !== $admin_secret) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Kode rahasia admin salah.']);
    exit;
}
$stmt = $pdo->prepare('SELECT id, name, email, password, role, avatar_url FROM users WHERE email = :email AND role = "ADMIN" LIMIT 1');
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();
if (!$user) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Akun admin tidak ditemukan.']);
    exit;
}
if (!password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Password salah.']);
    exit;
}
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
    'message' => 'Login Admin berhasil!',
    'token'   => $jwt,
    'user'    => [
        'id'         => $user['id'],
        'name'       => $user['name'],
        'email'      => $user['email'],
        'role'       => $user['role'],
        'avatar_url' => $user['avatar_url'],
    ]
]);
