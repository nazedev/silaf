<?php
require_once __DIR__ . '/../../api/middleware/auth.php';
setCorsHeaders();
require_once __DIR__ . '/../../config/database.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method tidak diizinkan.']);
    exit;
}
$data = json_decode(file_get_contents('php://input'));
if (empty($data->name) || empty($data->email) || empty($data->password)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Nama, email, dan password wajib diisi.']);
    exit;
}
$name     = htmlspecialchars(strip_tags(trim($data->name)));
$email    = filter_var(trim($data->email), FILTER_VALIDATE_EMAIL);
$password = $data->password;
if (!$email) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Format email tidak valid.']);
    exit;
}
if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Password minimal 6 karakter.']);
    exit;
}
try {
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, 'USER')");
    $stmt->execute([
        ':name'     => $name,
        ':email'    => $email,
        ':password' => $hashed_password,
    ]);
    http_response_code(201);
    echo json_encode(['status' => 'success', 'message' => 'Pendaftaran berhasil! Silakan login.']);
} catch (PDOException $e) {
    http_response_code(400);
    if ($e->getCode() == 23000) {
        echo json_encode(['status' => 'error', 'message' => 'Email sudah terdaftar. Gunakan email lain atau login.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Gagal mendaftar. Terjadi kesalahan server.']);
    }
}