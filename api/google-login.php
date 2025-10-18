<?php
// ====== CONFIGURAR CORS ======
$allowed_origins = [
    "http://localhost:5173",
    "https://tecnomax-ecommerce.vercel.app",
    "https://tecnomax.netlify.app"
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
} else {
    header("HTTP/1.1 403 Forbidden");
    echo json_encode(["error" => "Origen no permitido"]);
    exit;
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once "db.php";

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data || !isset($data['correo'])) {
    echo json_encode(["success" => false, "error" => "Datos inválidos"]);
    exit;
}

$nombre = $data['nombre'] ?? "";
$correo = $data['correo'];
$imagen = $data['imagen'] ?? "";

// Revisar si el usuario ya existe
$stmt = $conn->prepare("SELECT * FROM usuarios WHERE correo = ?");
$stmt->execute([$correo]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // Crear usuario nuevo
    $stmt = $conn->prepare("INSERT INTO usuarios (nombre, correo, rol, imagen, estado) VALUES (?, ?, 'cliente', ?, 'activo')");
    $stmt->execute([$nombre, $correo, $imagen]);
    $id = $conn->lastInsertId();
} else {
    $id = $user['id_usuario'] ?? $user['id']; 
}

// Respuesta
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
