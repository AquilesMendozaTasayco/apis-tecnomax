<?php
// === CONFIGURAR CORS ===
$allowedOrigin = "http://localhost:5173"; // 👈 porque tu frontend sigue en local
header("Access-Control-Allow-Origin: $allowedOrigin");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// === CONFIGURAR SESIONES ===
// Render usa HTTPS, por lo que la cookie debe marcarse como Secure + None
ini_set('session.cookie_secure', '1'); // Solo enviar cookies por HTTPS
ini_set('session.cookie_samesite', 'None'); // Permitir cross-site cookies
ini_set('session.cookie_httponly', '1'); // (opcional) evitar acceso JS
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
