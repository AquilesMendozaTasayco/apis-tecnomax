<?php
header("Content-Type: application/json");

$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: 'root';
$dbname = getenv('DB_NAME') ?: 'tecnomax';

$conexion = new mysqli($host, $user, $pass, $dbname);

if ($conexion->connect_error) {
    http_response_code(500);
    die(json_encode(["error" => "Error de conexión: " . $conexion->connect_error]));
}
?>
