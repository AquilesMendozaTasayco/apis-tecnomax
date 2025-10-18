<?php
require_once "../config/db.php";

$allowed_origins = [
    "http://localhost:5173",
    "https://tecnomax-ecommerce.vercel.app",
    "https://tecnomax.netlify.app"
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if ($origin === '' || in_array($origin, $allowed_origins)) {
    if ($origin !== '') {
        header("Access-Control-Allow-Origin: $origin");
    } else {
        header("Access-Control-Allow-Origin: *");
    }
} else {
    header("HTTP/1.1 403 Forbidden");
    echo json_encode(["error" => "Origen no permitido: $origin"]);
    exit;
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}



$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data || !isset($data['correo'])) {
    echo json_encode(["success" => false, "error" => "Datos inválidos o incompletos"]);
    exit;
}

$nombre = $data['nombre'] ?? '';
$correo = $data['correo'];
$imagen = $data['imagen'] ?? '';

$stmt = $conn->prepare("SELECT * FROM usuarios WHERE correo = ?");
$stmt->execute([$correo]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, correo, rol, imagen, estado) VALUES (?, ?, 'cliente', ?, 'activo')");
    $stmt->execute([$nombre, $correo, $imagen]);
    $id = $conn->lastInsertId();
} else {
    $id = $user['id_usuario'] ?? $user['id'];
}

echo json_encode([
    "success" => true,
    "usuario" => [
        "id_usuario" => $id,
        "nombre" => $nombre,
        "correo" => $correo,
        "rol" => "cliente",
        "imagen" => $imagen
    ]
]);
