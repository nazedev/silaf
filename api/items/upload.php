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
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'File gambar wajib diunggah.']);
    exit;
}
$file = $_FILES['image'];
$allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($file['type'], $allowed)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP.']);
    exit;
}
if ($file['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Ukuran file maksimal 5MB.']);
    exit;
}
$cloud_name = $_ENV['CLOUDINARY_CLOUD_NAME'] ?? getenv('CLOUDINARY_CLOUD_NAME') ?? '';
$api_key    = $_ENV['CLOUDINARY_API_KEY']    ?? getenv('CLOUDINARY_API_KEY')    ?? '';
$api_secret = $_ENV['CLOUDINARY_API_SECRET'] ?? getenv('CLOUDINARY_API_SECRET') ?? '';
if (!$cloud_name || !$api_key || !$api_secret) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Konfigurasi Cloudinary belum diatur.']);
    exit;
}
$timestamp = time();
$params = [
    'folder'    => 'silaf',
    'timestamp' => $timestamp,
];
ksort($params);
$signString = '';
foreach ($params as $key => $val) {
    $signString .= ($signString ? '&' : '') . "$key=$val";
}
$signString .= $api_secret;
$signature = sha1($signString);
$url = "https://api.cloudinary.com/v1_1/$cloud_name/image/upload";
$postFields = [
    'file'      => new CURLFile($file['tmp_name'], $file['type'], $file['name']),
    'api_key'   => $api_key,
    'timestamp' => $timestamp,
    'signature' => $signature,
    'folder'    => 'silaf',
];
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
if ($httpCode !== 200) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Gagal upload ke Cloudinary.', 'detail' => $response]);
    exit;
}
$result = json_decode($response, true);
echo json_encode([
    'status'    => 'success',
    'message'   => 'Upload berhasil.',
    'image_url' => $result['secure_url'],
]);
