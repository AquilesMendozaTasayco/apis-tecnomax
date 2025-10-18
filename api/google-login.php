<?php
$allowed_origins = [
    "http://localhost:5173",
    "https://tecnomax-ecommerce.vercel.app",
    "https://tecnomax.netlify.app" 
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
}
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require 'db.php';
$data = json_decode(file_get_contents('php://input'), true);

$nombre = $data['nombre'];
$correo = $data['correo'];
$imagen = $data['imagen'];

// Revisar si el usuario ya existe
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE correo = ?");
$stmt->execute([$correo]);
$user = $stmt->fetch();

if (!$user) {
    // Crear usuario
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, correo, rol, imagen) VALUES (?, ?, 'cliente', ?)");
    $stmt->execute([$nombre, $correo, $imagen]);
    $id = $conn->lastInsertId();
} else {
    $id = $user['id'];
}

echo json_encode([
    "success" => true,
    "usuario" => [
        "id" => $id,
        "nombre" => $nombre,
        "correo" => $correo,
        "rol" => "cliente",
        "imagen" => $imagen
    ]
]);
