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
if (empty($data->email) || empty($data->password)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Email dan password wajib diisi.']);
    exit;
}
$email    = trim($data->email);
$password = $data->password;
$stmt = $pdo->prepare('SELECT id, name, email, password, role, avatar_url FROM users WHERE email = :email LIMIT 1');
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();
if (!$user) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'Email tidak terdaftar di sistem.']);
    exit;
}
if (empty($user['password'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => "Akun ini terdaftar via Google. Silakan gunakan tombol 'Sign in with Google'."]);
    exit;
}
if (!password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Password yang Anda masukkan salah.']);
    exit;
}
$secret_key      = $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET') ?? 'silaf_jwt_secret_key_super_kuat_2024';
$issued_at       = time();
$expiration_time = $issued_at + (60 * 60 * 24 * 7); // 7 hari
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
    'message' => 'Login berhasil!',
    'token'   => $jwt,
    'user'    => [
        'id'         => $user['id'],
        'name'       => $user['name'],
        'email'      => $user['email'],
        'role'       => $user['role'],
        'avatar_url' => $user['avatar_url'],
    ]
]);