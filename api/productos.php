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
require_once "../config/cloudinary.php"; 

use Cloudinary\Api\Upload\UploadApi;

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

    $imagenUrl = null;

    // 👇 NUEVO: subir a Cloudinary
    if (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] == 0) {
        $tmpPath = $_FILES['imagen_principal']['tmp_name'];
        try {
            $upload = (new UploadApi())->upload($tmpPath, [
                'folder' => 'productos' // crea carpeta "productos" en Cloudinary
            ]);
            $imagenUrl = $upload['secure_url']; // URL accesible públicamente
        } catch (Exception $e) {
            echo json_encode(["success" => false, "error" => "Error al subir imagen: " . $e->getMessage()]);
            exit;
        }
    }

    $sql = "INSERT INTO productos (nombre, descripcion, precio, stock, id_categoria, imagen_principal, estado) 
            VALUES ('$nombre','$descripcion',$precio,$stock,$id_categoria,'$imagenUrl','activo')";

    echo $conexion->query($sql) ? 
        json_encode(["success" => true, "mensaje" => "Producto creado", "imagen_url" => $imagenUrl]) : 
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
