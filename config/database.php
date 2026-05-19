<?php
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    if (file_exists(__DIR__ . '/../.env')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();
    }
}
$db_url = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL') ?? null;
if ($db_url) {
    $url     = parse_url($db_url);
    $host    = $url['host'];
    $user    = $url['user'];
    $pass    = $url['pass'];
    $port    = $url['port'] ?? 3306;
    $db_name = ltrim($url['path'], '/');
} else {
    $host    = $_ENV['DB_HOST']     ?? getenv('DB_HOST')     ?? '127.0.0.1';
    $user    = $_ENV['DB_USER']     ?? getenv('DB_USER')     ?? 'root';
    $pass    = $_ENV['DB_PASS']     ?? getenv('DB_PASS')     ?? '';
    $port    = $_ENV['DB_PORT']     ?? getenv('DB_PORT')     ?? 3306;
    $db_name = $_ENV['DB_NAME']     ?? getenv('DB_NAME')     ?? 'silaf_db';
}
try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db_name;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $isRemote = !in_array($host, ['127.0.0.1', 'localhost', '::1']);
    if ($isRemote) {
        if (is_file('/etc/ssl/certs/ca-certificates.crt')) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = '/etc/ssl/certs/ca-certificates.crt';
        } else {
            $options[PDO::MYSQL_ATTR_SSL_CA] = true;
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }
    }
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Gagal terhubung ke database: ' . $e->getMessage()
    ]);
    exit;
}