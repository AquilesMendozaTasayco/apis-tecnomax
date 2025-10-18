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
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once "../config/db.php";

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $sql = "SELECT * FROM metodos_pago WHERE id_metodo=$id AND estado != 'inactivo'";
            $result = $conexion->query($sql);
            echo json_encode($result->fetch_assoc());
        } else {
            $sql = "SELECT * FROM metodos_pago WHERE estado='activo'";
            $result = $conexion->query($sql);
            $metodos = [];
            while ($row = $result->fetch_assoc()) {
                $metodos[] = $row;
            }
            echo json_encode($metodos);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);

        $nombre = $conexion->real_escape_string($data['nombre']);
        $descripcion = $conexion->real_escape_string($data['descripcion']);
        $estado = isset($data['estado']) ? $conexion->real_escape_string($data['estado']) : "activo";

        $sql = "INSERT INTO metodos_pago (nombre, descripcion, estado) 
                VALUES ('$nombre','$descripcion','$estado')";

        echo $conexion->query($sql) ? 
            json_encode(["success" => true, "mensaje" => "Método de pago creado"]) : 
            json_encode(["success" => false, "error" => $conexion->error]);
        break;

    case 'PUT':
        if (!isset($_GET['id'])) {
            echo json_encode(["error" => "ID requerido"]);
            exit;
        }

        $id = intval($_GET['id']);
        $data = json_decode(file_get_contents("php://input"), true);

        $nombre = $conexion->real_escape_string($data['nombre']);
        $descripcion = $conexion->real_escape_string($data['descripcion']);
        $estado = $conexion->real_escape_string($data['estado']);

        $sql = "UPDATE metodos_pago 
                SET nombre='$nombre', descripcion='$descripcion', estado='$estado' 
                WHERE id_metodo=$id";

        echo $conexion->query($sql) ? 
            json_encode(["mensaje" => "Método de pago actualizado"]) : 
            json_encode(["error" => $conexion->error]);
        break;

    case 'DELETE':
        if (!isset($_GET['id'])) {
            echo json_encode(["error" => "ID requerido"]);
            exit;
        }
        $id = intval($_GET['id']);
        $sql = "UPDATE metodos_pago SET estado='inactivo' WHERE id_metodo=$id";
        echo $conexion->query($sql) ? 
            json_encode(["mensaje" => "Método de pago marcado como inactivo"]) : 
            json_encode(["error" => $conexion->error]);
        break;

    default:
        echo json_encode(["error" => "Método no permitido"]);
}
?>
