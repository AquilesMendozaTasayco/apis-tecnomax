<?php
$allowed_origins = [
    "http://localhost:5173",                
    "https://tecnomax-ecommerce-b7ut.vercel.app" 
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
        if (isset($_GET['id_usuario'])) {
            $id_usuario = intval($_GET['id_usuario']);
            $sql = "SELECT * FROM notificaciones 
                    WHERE id_usuario=$id_usuario 
                    ORDER BY fecha DESC";
            $result = $conexion->query($sql);
            $notificaciones = [];
            while ($row = $result->fetch_assoc()) {
                $notificaciones[] = $row;
            }
            echo json_encode($notificaciones);
        } elseif (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $sql = "SELECT * FROM notificaciones WHERE id_notificacion=$id";
            $result = $conexion->query($sql);
            echo json_encode($result->fetch_assoc());
        } else {
            $sql = "SELECT * FROM notificaciones ORDER BY fecha DESC";
            $result = $conexion->query($sql);
            $notificaciones = [];
            while ($row = $result->fetch_assoc()) {
                $notificaciones[] = $row;
            }
            echo json_encode($notificaciones);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);

        $id_usuario = intval($data['id_usuario']);
        $mensaje = $conexion->real_escape_string($data['mensaje']);

        $sql = "INSERT INTO notificaciones (id_usuario, mensaje, leido) 
                VALUES ($id_usuario, '$mensaje', 0)";

        echo $conexion->query($sql) ? 
            json_encode(["success" => true, "mensaje" => "Notificación creada"]) : 
            json_encode(["success" => false, "error" => $conexion->error]);
        break;

    case 'PUT':
        if (!isset($_GET['id'])) {
            echo json_encode(["error" => "ID requerido"]);
            exit;
        }

        $id = intval($_GET['id']);
        $sql = "UPDATE notificaciones SET leido=1 WHERE id_notificacion=$id";

        echo $conexion->query($sql) ? 
            json_encode(["mensaje" => "Notificación marcada como leída"]) : 
            json_encode(["error" => $conexion->error]);
        break;

    case 'DELETE':
        if (!isset($_GET['id'])) {
            echo json_encode(["error" => "ID requerido"]);
            exit;
        }
        $id = intval($_GET['id']);
        $sql = "DELETE FROM notificaciones WHERE id_notificacion=$id";
        echo $conexion->query($sql) ? 
            json_encode(["mensaje" => "Notificación eliminada"]) : 
            json_encode(["error" => $conexion->error]);
        break;

    default:
        echo json_encode(["error" => "Método no permitido"]);
}
?>
