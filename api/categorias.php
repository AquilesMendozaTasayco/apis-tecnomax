<?php
$allowed_origins = [
    "http://localhost:5173",
    "https://tecnomax-ecommerce-b7ut.vercel.app",
    "https://tecnomax.netlify.app" 
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
}
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Responder a preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ------------------- CONEXIÓN A BASE DE DATOS -------------------
require_once "../config/db.php"; // Ajusta la ruta según tu proyecto

// ------------------- DETECTAR MÉTODO -------------------
$method = $_SERVER['REQUEST_METHOD'];

// ------------------- FUNCIONES AUXILIARES -------------------
function response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

// ------------------- LÓGICA SEGÚN MÉTODO -------------------
switch ($method) {

    // ------------------- GET -------------------
    case 'GET':
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $stmt = $conexion->prepare("SELECT * FROM categorias WHERE id_categoria=? AND estado!='eliminado'");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            response($result->fetch_assoc());
        } else {
            $stmt = $conexion->prepare("SELECT * FROM categorias WHERE estado!='eliminado'");
            $stmt->execute();
            $result = $stmt->get_result();
            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            response($data);
        }
        break;

    // ------------------- POST -------------------
    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data) response(["success" => false, "mensaje" => "No se recibieron datos"], 400);

        $nombre = $conexion->real_escape_string($data['nombre'] ?? '');
        $descripcion = $conexion->real_escape_string($data['descripcion'] ?? '');

        $stmt = $conexion->prepare("INSERT INTO categorias (nombre, descripcion) VALUES (?, ?)");
        $stmt->bind_param("ss", $nombre, $descripcion);

        if ($stmt->execute()) {
            response(["success" => true, "mensaje" => "Categoría creada"]);
        } else {
            response(["success" => false, "error" => $stmt->error], 500);
        }
        break;

    // ------------------- PUT -------------------
    case 'PUT':
        if (!isset($_GET['id'])) response(["error" => "ID requerido"], 400);
        $id = intval($_GET['id']);
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data) response(["error" => "No se recibieron datos"], 400);

        $nombre = $conexion->real_escape_string($data['nombre'] ?? '');
        $descripcion = $conexion->real_escape_string($data['descripcion'] ?? '');
        $estado = $conexion->real_escape_string($data['estado'] ?? 'activo');

        $stmt = $conexion->prepare("UPDATE categorias SET nombre=?, descripcion=?, estado=? WHERE id_categoria=?");
        $stmt->bind_param("sssi", $nombre, $descripcion, $estado, $id);

        if ($stmt->execute()) {
            response(["mensaje" => "Categoría actualizada"]);
        } else {
            response(["error" => $stmt->error], 500);
        }
        break;

    // ------------------- DELETE -------------------
    case 'DELETE':
        if (!isset($_GET['id'])) response(["error" => "ID requerido"], 400);
        $id = intval($_GET['id']);

        $stmt = $conexion->prepare("UPDATE categorias SET estado='eliminado' WHERE id_categoria=?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            response(["mensaje" => "Categoría eliminada (soft delete)"]);
        } else {
            response(["error" => $stmt->error], 500);
        }
        break;

    // ------------------- MÉTODO NO PERMITIDO -------------------
    default:
        response(["error" => "Método no permitido"], 405);
}
?>
