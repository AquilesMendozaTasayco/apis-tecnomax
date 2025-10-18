<?php
header("Content-Type: application/json; charset=UTF-8");

// 🔹 1. Configurar orígenes permitidos
$allowed_origins = [
    "http://localhost:5173",
    "https://tecnomax-ecommerce.vercel.app",
    "https://tecnomax.netlify.app"
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';

if ($origin !== '' && in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
} else if ($origin === '') {
    // Si no hay HTTP_ORIGIN (por ejemplo, si entras desde el navegador directo)
    header("Access-Control-Allow-Origin: *");
} else {
    header("HTTP/1.1 403 Forbidden");
    echo json_encode(["error" => "Origen no permitido", "origen" => $origin]);
    exit;
}

// 🔹 2. Permitir cabeceras y métodos
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// 🔹 3. Manejo de preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 🔹 4. Conexión a BD
require_once __DIR__ . "/../config/db.php"; // Asegúrate de que esta ruta exista

// 🔹 5. Leer datos del cuerpo JSON
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// 🔍 (Temporal) Mostrar lo que realmente está llegando para depuración
if (!$data) {
    echo json_encode([
        "success" => false,
        "error" => "No se pudo decodificar el JSON",
        "raw" => $input,  // Muestra lo que llegó realmente
        "method" => $_SERVER['REQUEST_METHOD']
    ]);
    exit;
}

if (empty($data['correo'])) {
    echo json_encode([
        "success" => false,
        "error" => "Datos inválidos o incompletos",
        "recibido" => $data  // 👈 útil para ver qué campos llegaron
    ]);
    exit;
}

// 🔹 6. Procesar usuario
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

// 🔹 7. Respuesta final
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
