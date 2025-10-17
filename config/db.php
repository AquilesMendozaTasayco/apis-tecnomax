<?php
header("Content-Type: application/json");

$host = getenv('DB_HOST') ?: 'mysql.railway.internal';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: 'mfJrKLXdABPHuHCxuFJuUsFlziBVWUCR';
$dbname = getenv('DB_NAME') ?: 'railway';
$port = getenv('DB_PORT') ?: 3306;

$conexion = new mysqli($host, $user, $pass, $dbname, $port);

if ($conexion->connect_error) {
    http_response_code(500);
    die(json_encode(["error" => "Error de conexión: " . $conexion->connect_error]));
}
?>
