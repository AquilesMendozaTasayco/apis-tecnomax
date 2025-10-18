<?php
$allowed_origins = [
    "http://localhost:5173",
    "https://tecnomax-ecommerce.vercel.app/",
    "https://tecnomax.netlify.app" 
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

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
// ✅ Manejo de excepciones global
// =========================
set_exception_handler(function ($e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
    exit;
});

require_once "../config/db.php";

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    // =========================
    // GET: Obtener pedidos
    // =========================
    case 'GET':
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);

            $sql = "SELECT p.*, u.nombre, u.apellido, m.nombre AS metodo_pago
                    FROM pedidos p
                    JOIN usuarios u ON p.id_usuario = u.id_usuario
                    LEFT JOIN metodos_pago m ON p.id_metodo = m.id_metodo
                    WHERE p.id_pedido = $id";

            $pedido = $conexion->query($sql)->fetch_assoc();

            $det_sql = "SELECT d.*, pr.nombre, pr.precio
                        FROM detalle_pedido d
                        JOIN productos pr ON d.id_producto = pr.id_producto
                        WHERE d.id_pedido = $id";

            $detalles = $conexion->query($det_sql);
            $pedido['detalles'] = [];
            while ($row = $detalles->fetch_assoc()) {
                $pedido['detalles'][] = $row;
            }

            echo json_encode($pedido);
        } else {
            $sql = "SELECT p.id_pedido, p.fecha, p.estado, p.total, 
                           u.nombre, u.apellido, m.nombre AS metodo_pago
                    FROM pedidos p
                    JOIN usuarios u ON p.id_usuario = u.id_usuario
                    LEFT JOIN metodos_pago m ON p.id_metodo = m.id_metodo
                    ORDER BY p.fecha DESC";

            $result = $conexion->query($sql);
            $pedidos = [];
            while ($row = $result->fetch_assoc()) {
                $pedidos[] = $row;
            }
            echo json_encode($pedidos);
        }
        break;

    // =========================
    // POST: Crear pedido
    // =========================
    case 'POST':
        $input = file_get_contents("php://input");

        // A veces se envía vacío, validamos antes
        if (!$input) {
            echo json_encode(["success" => false, "error" => "No se recibió contenido"]);
            exit;
        }

        $data = json_decode($input, true);
        if (!$data) {
            echo json_encode(["success" => false, "error" => "JSON inválido recibido"]);
            exit;
        }

        $id_usuario = intval($data['id_usuario'] ?? 0);
        $id_metodo = intval($data['id_metodo'] ?? 0);
        $estado = "pendiente";
        $total = floatval($data['total'] ?? 0);
        $productos = $data['productos'] ?? [];

        if ($id_usuario <= 0 || empty($productos)) {
            echo json_encode(["success" => false, "error" => "Faltan datos obligatorios"]);
            exit;
        }

        // Insertar pedido
        $sql = "INSERT INTO pedidos (id_usuario, estado, total, id_metodo) 
                VALUES ($id_usuario, '$estado', $total, $id_metodo)";

        if ($conexion->query($sql)) {
            $id_pedido = $conexion->insert_id;

            foreach ($productos as $prod) {
                $id_producto = intval($prod['id_producto']);
                $cantidad = intval($prod['cantidad']);
                $precio_unitario = floatval($prod['precio_unitario']);

                $conexion->query("INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unitario) 
                                  VALUES ($id_pedido, $id_producto, $cantidad, $precio_unitario)");

                $conexion->query("UPDATE productos 
                                  SET stock = stock - $cantidad 
                                  WHERE id_producto = $id_producto");
            }

            echo json_encode([
                "success" => true,
                "mensaje" => "Pedido creado correctamente",
                "id_pedido" => $id_pedido
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "error" => $conexion->error
            ]);
        }
        break;

    // =========================
    // PUT: Actualizar estado
    // =========================
    case 'PUT':
        if (!isset($_GET['id'])) {
            echo json_encode(["success" => false, "error" => "ID requerido"]);
            exit;
        }

        $id = intval($_GET['id']);
        $data = json_decode(file_get_contents("php://input"), true);
        $estado = $conexion->real_escape_string($data['estado'] ?? "pendiente");

        $sql = "UPDATE pedidos SET estado='$estado' WHERE id_pedido=$id";
        if ($conexion->query($sql)) {
            echo json_encode(["success" => true, "mensaje" => "Estado de pedido actualizado"]);
        } else {
            echo json_encode(["success" => false, "error" => $conexion->error]);
        }
        break;

    // =========================
    // DELETE: Cancelar pedido
    // =========================
    case 'DELETE':
        if (!isset($_GET['id'])) {
            echo json_encode(["success" => false, "error" => "ID requerido"]);
            exit;
        }

        $id = intval($_GET['id']);
        $sql = "UPDATE pedidos SET estado='cancelado' WHERE id_pedido=$id";
        if ($conexion->query($sql)) {
            echo json_encode(["success" => true, "mensaje" => "Pedido cancelado"]);
        } else {
            echo json_encode(["success" => false, "error" => $conexion->error]);
        }
        break;

    default:
        echo json_encode(["success" => false, "error" => "Método no permitido"]);
        break;
}
?>
