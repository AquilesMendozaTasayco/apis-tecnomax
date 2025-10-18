<?php
$allowed_origins = [
    "http://localhost:5173",
    "https://tecnomax-ecommerce.vercel.app/",
    "https://tecnomax.netlify.app" 
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
}


header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once "../config/db.php";

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    echo json_encode(["error" => "Solo se permiten consultas (GET)"]);
    exit;
}

$stats = [];

$sql = "SELECT COUNT(*) AS total_usuarios FROM usuarios WHERE estado='activo'";
$stats['usuarios'] = $conexion->query($sql)->fetch_assoc()['total_usuarios'];

$sql = "SELECT COUNT(*) AS total_productos FROM productos WHERE estado='activo'";
$stats['productos'] = $conexion->query($sql)->fetch_assoc()['total_productos'];

$sql = "SELECT COUNT(*) AS total_pedidos FROM pedidos";
$stats['pedidos'] = $conexion->query($sql)->fetch_assoc()['total_pedidos'];

$sql = "SELECT COUNT(*) AS pendientes FROM pedidos WHERE estado='pendiente'";
$stats['pendientes'] = $conexion->query($sql)->fetch_assoc()['pendientes'];

$sql = "SELECT IFNULL(SUM(total),0) AS total_ventas 
        FROM pedidos 
        WHERE estado IN ('en_proceso','enviado','entregado')";
$stats['ventas'] = $conexion->query($sql)->fetch_assoc()['total_ventas'];

echo json_encode($stats);
?>
