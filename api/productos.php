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
        $sql = "SELECT * FROM productos WHERE id_producto=$id AND estado != 'eliminado'";
        $result = $conexion->query($sql);
        echo json_encode($result->fetch_assoc());
    } elseif (isset($_GET['categoria'])) {
        $cat = intval($_GET['categoria']);
        $sql = "SELECT p.id_producto, p.nombre, p.descripcion, p.precio, p.stock, 
                       p.imagen_principal, c.nombre AS categoria, p.estado
                FROM productos p
                LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
                WHERE p.estado != 'eliminado' AND p.id_categoria=$cat";
        $result = $conexion->query($sql);
        $productos = [];
        while ($row = $result->fetch_assoc()) {
            $productos[] = $row;
        }
        echo json_encode($productos);
    } else {
            $sql = "SELECT p.id_producto, p.nombre, p.descripcion, p.precio, p.stock, 
                           p.imagen_principal, c.nombre AS categoria, p.estado
                    FROM productos p
                    LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
                    WHERE p.estado != 'eliminado'";
            $result = $conexion->query($sql);
            $productos = [];
            while ($row = $result->fetch_assoc()) {
                $productos[] = $row;
            }
            echo json_encode($productos);
        }
        break;

    case 'POST':
        $nombre = $conexion->real_escape_string($_POST['nombre']);
        $descripcion = $conexion->real_escape_string($_POST['descripcion']);
        $precio = floatval($_POST['precio']);
        $stock = intval($_POST['stock']);
        $id_categoria = intval($_POST['id_categoria']);

        $imagen = null;
        if (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] == 0) {
            $nombreArchivo = time() . "_" . basename($_FILES['imagen_principal']['name']);
            $rutaDestino = "../uploads/productos/" . $nombreArchivo;
            if (move_uploaded_file($_FILES['imagen_principal']['tmp_name'], $rutaDestino)) {
                $imagen = $nombreArchivo;
            }
        }

        $sql = "INSERT INTO productos (nombre, descripcion, precio, stock, id_categoria, imagen_principal, estado) 
                VALUES ('$nombre','$descripcion',$precio,$stock,$id_categoria,'$imagen','activo')";

        echo $conexion->query($sql) ? 
            json_encode(["success" => true, "mensaje" => "Producto creado"]) : 
            json_encode(["success" => false, "error" => $conexion->error]);
        break;

    case 'PUT':
        echo json_encode(["error" => "Para actualizar productos con imagen usa PATCH o un endpoint separado"]);
        break;

    case 'DELETE':
        if (!isset($_GET['id'])) {
            echo json_encode(["error" => "ID requerido"]);
            exit;
        }
        $id = intval($_GET['id']);
        $sql = "UPDATE productos SET estado='eliminado' WHERE id_producto=$id";
        echo $conexion->query($sql) ? 
            json_encode(["mensaje" => "Producto eliminado (soft delete)"]) : 
            json_encode(["error" => $conexion->error]);
        break;

    default:
        echo json_encode(["error" => "Método no permitido"]);
}
?>
