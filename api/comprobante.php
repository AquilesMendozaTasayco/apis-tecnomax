<?php
// =========================
// ✅ CORS dinámico
// =========================
$allowed_origins = ["http://localhost:5173", "https://tuappfront.onrender.com"];
$origin = $_SERVER["HTTP_ORIGIN"] ?? "";
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
}
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// =========================
// ✅ Mostrar errores en desarrollo
// =========================
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// =========================
// ✅ Dependencias
// =========================
require_once "../config/db.php";
require_once "../config/cloudinary.php"; // Debe definir Cloudinary::config()

use Cloudinary\Api\Upload\UploadApi;

// =========================
// ✅ Validar conexión DB
// =========================
if (!$conexion) {
    echo json_encode(["success" => false, "mensaje" => "Error de conexión a la base de datos"]);
    exit;
}

// =========================
// ✅ Solo POST permitido
// =========================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["success" => false, "mensaje" => "Método no permitido"]);
    exit;
}

$id_pedido = $_POST['id_pedido'] ?? null;
$monto = $_POST['monto'] ?? null;
$archivo = $_FILES['comprobante'] ?? null;

// =========================
// ✅ Validar datos recibidos
// =========================
if (!$id_pedido || !$archivo) {
    echo json_encode(["success" => false, "mensaje" => "Faltan datos requeridos (id_pedido o comprobante)"]);
    exit;
}

try {
    // =========================
    // ✅ Subir comprobante a Cloudinary
    // =========================
    $upload = (new UploadApi())->upload($archivo["tmp_name"], [
        'folder' => 'comprobantes'
    ]);

    $urlComprobante = $upload['secure_url'] ?? null;

    if (!$urlComprobante) {
        echo json_encode(["success" => false, "mensaje" => "No se pudo obtener la URL del comprobante"]);
        exit;
    }

    // =========================
    // ✅ Guardar registro en BD
    // =========================
    $stmt = $conexion->prepare("INSERT INTO comprobantes (id_pedido, monto, imagen) VALUES (?, ?, ?)");
    $stmt->bind_param("ids", $id_pedido, $monto, $urlComprobante);

    if ($stmt->execute()) {
        echo json_encode([
            "success" => true,
            "mensaje" => "Comprobante subido correctamente",
            "url" => $urlComprobante
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "mensaje" => "Error SQL: " . $stmt->error
        ]);
    }

    $stmt->close();

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "mensaje" => "Error al subir comprobante: " . $e->getMessage()
    ]);
}
?>
