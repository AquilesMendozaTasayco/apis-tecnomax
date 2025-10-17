<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

session_start();

if (isset($_SESSION['id_usuario'])) {
    echo json_encode([
        "logged" => true,
        "id_usuario" => $_SESSION['id_usuario'],
        "rol" => $_SESSION['rol'],
        "nombre" => $_SESSION['nombre']
    ]);
} else {
    echo json_encode(["logged" => false]);
}
?>
