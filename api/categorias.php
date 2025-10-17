<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
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
            $sql = "SELECT * FROM categorias WHERE id_categoria=$id AND estado != 'eliminado'";
            $result = $conexion->query($sql);
            echo json_encode($result->fetch_assoc());
        } else {
            $sql = "SELECT * FROM categorias WHERE estado != 'eliminado'";
            $result = $conexion->query($sql);
            $categorias = [];
            while ($row = $result->fetch_assoc()) {
                $categorias[] = $row;
            }
            echo json_encode($categorias);
        }
        break;


    case 'POST':
        $data = json_decode(file_get_contents("php://input"), true);

        $nombre = $conexion->real_escape_string($data['nombre']);
        $descripcion = $conexion->real_escape_string($data['descripcion']);

        $sql = "INSERT INTO categorias (nombre, descripcion) 
                VALUES ('$nombre', '$descripcion')";

        echo $conexion->query($sql) ? 
            json_encode(["success" => true, "mensaje" => "Categoría creada"]) : 
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

        $sql = "UPDATE categorias 
                SET nombre='$nombre', descripcion='$descripcion', estado='$estado' 
                WHERE id_categoria=$id";

        echo $conexion->query($sql) ? 
            json_encode(["mensaje" => "Categoría actualizada"]) : 
            json_encode(["error" => $conexion->error]);
        break;

    case 'DELETE':
        if (!isset($_GET['id'])) {
            echo json_encode(["error" => "ID requerido"]);
            exit;
        }
        $id = intval($_GET['id']);
        $sql = "UPDATE categorias SET estado='eliminado' WHERE id_categoria=$id";
        echo $conexion->query($sql) ? 
            json_encode(["mensaje" => "Categoría eliminada (soft delete)"]) : 
            json_encode(["error" => $conexion->error]);
        break;

    default:
        echo json_encode(["error" => "Método no permitido"]);
}
?>
