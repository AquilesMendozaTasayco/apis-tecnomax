<?php
$allowed_origins = [
    "http://localhost:5173",
    "https://tecnomax-ecommerce.vercel.app",
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
session_start();

$input = file_get_contents("php://input");
$data = json_decode($input, true);

if (!$data || !isset($data['correo']) || !isset($data['password'])) {
    echo json_encode(["success" => false, "error" => "Datos inválidos o incompletos"]);
    exit;
}

// Escapar datos
$correo = $conexion->real_escape_string($data['correo']);
$password = $data['password'];

// Buscar usuario activo
$sql = "SELECT * FROM usuarios WHERE correo='$correo' AND estado='activo' LIMIT 1";
$result = $conexion->query($sql);

if ($result && $result->num_rows > 0) {
    $usuario = $result->fetch_assoc();

    if (password_verify($password, $usuario['password'])) {
        $_SESSION['id_usuario'] = $usuario['id_usuario'];
        $_SESSION['rol'] = $usuario['rol'];
        $_SESSION['nombre'] = $usuario['nombre'];

        unset($usuario['password']); 
        echo json_encode([
            "success" => true,
            "mensaje" => "Login exitoso",
            "usuario" => $usuario
        ]);
    } else {
        echo json_encode(["success" => false, "error" => "Contraseña incorrecta"]);
    }
} else {
    echo json_encode(["success" => false, "error" => "Usuario no encontrado"]);
}
?>
