<?php
require_once __DIR__ . '/../../vendor/autoload.php';
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;
function getAuthUser() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Token tidak ditemukan. Silakan login kembali.']);
        exit;
    }
    $token = substr($authHeader, 7);
    $secret_key = $_ENV['JWT_SECRET'] ?? getenv('JWT_SECRET') ?? 'silaf_jwt_secret_key_super_kuat_2024';
    try {
        $decoded = JWT::decode($token, new Key($secret_key, 'HS256'));
        return (array) $decoded->data;
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Token tidak valid atau sudah kadaluarsa. Silakan login kembali.']);
        exit;
    }
}
function requireAdmin() {
    $user = getAuthUser();
    if ($user['role'] !== 'ADMIN') {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Akses ditolak. Hanya Admin yang diizinkan.']);
        exit;
    }
    return $user;
}
function setCorsHeaders() {
    header('Content-Type: application/json; charset=UTF-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
}
