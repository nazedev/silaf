<?php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path === '/' || $path === '') {
    header("Location: /frontend/index.html");
    exit;
}
$file = __DIR__ . $path;
if (file_exists($file) && !is_dir($file)) {
    return false; // Biarkan PHP server menangani file aslinya
}
http_response_code(404);
if (file_exists(__DIR__ . '/frontend/404.html')) {
    include __DIR__ . '/frontend/404.html';
} else {
    echo "404 Not Found";
}
exit;
