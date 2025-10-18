<?php
$allowed_origins = [
    "http://localhost:5173",                
    "https://tecnomax-ecommerce-b7ut.vercel.app" 
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowed_origins)) {
    header("Access-Control-Allow-Origin: " . $_SERVER['HTTP_ORIGIN']);
}

header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
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
            $sql = "SELECT p.id_pago, p.monto, p.fecha, p.estado, p.comprobante,
                           pe.id_pedido, u.nombre, u.apellido
                    FROM pagos p
                    JOIN pedidos pe ON p.id_pedido = pe.id_pedido
                    JOIN usuarios u ON pe.id_usuario = u.id_usuario
                    WHERE p.id_pago=$id";
            $result = $conexion->query($sql);
            echo json_encode($result->fetch_assoc());
        } else {
            $sql = "SELECT p.id_pago, p.monto, p.fecha, p.estado, p.comprobante,
                           pe.id_pedido, u.nombre, u.apellido
                    FROM pagos p
                    JOIN pedidos pe ON p.id_pedido = pe.id_pedido
                    JOIN usuarios u ON pe.id_usuario = u.id_usuario
                    ORDER BY p.id_pago DESC";
            $result = $conexion->query($sql);
            $pagos = [];
            while ($row = $result->fetch_assoc()) {
                $pagos[] = $row;
            }
            echo json_encode($pagos);
        }
        break;

    case 'POST':
        if (!empty($_FILES['comprobante']['name'])) {
            $id_pedido = intval($_POST['id_pedido']);
            $monto = floatval($_POST['monto']);
            $estado = "pendiente";

            $nombreArchivo = time() . "_" . basename($_FILES['comprobante']['name']);
            $ruta = "../uploads/comprobantes/" . $nombreArchivo;

            if (!is_dir("../uploads/comprobantes")) {
                mkdir("../uploads/comprobantes", 0777, true);
            }

            if (move_uploaded_file($_FILES['comprobante']['tmp_name'], $ruta)) {
                $sql = "INSERT INTO pagos (id_pedido, monto, estado, comprobante)
                        VALUES ($id_pedido, $monto, '$estado', '$nombreArchivo')";
                if ($conexion->query($sql)) {
                    echo json_encode([
                        "success" => true,
                        "mensaje" => "✅ Comprobante subido correctamente. Esperando verificación.",
                        "archivo" => $nombreArchivo
                    ]);
                } else {
                    echo json_encode(["success" => false, "error" => $conexion->error]);
                }
            } else {
                echo json_encode(["success" => false, "mensaje" => "❌ Error al mover el archivo."]);
            }

        } else {
            $data = json_decode(file_get_contents("php://input"), true);
            $id_pedido = intval($data['id_pedido']);
            $monto = floatval($data['monto']);
            $estado = isset($data['estado']) ? $conexion->real_escape_string($data['estado']) : "pendiente";

            $sql = "INSERT INTO pagos (id_pedido, monto, estado) VALUES ($id_pedido, $monto, '$estado')";
            if ($conexion->query($sql)) {
                if ($estado === "pagado") {
                    $conexion->query("UPDATE pedidos SET estado='en_proceso' WHERE id_pedido=$id_pedido");
                }
                echo json_encode(["success" => true, "mensaje" => "Pago registrado correctamente."]);
            } else {
                echo json_encode(["success" => false, "error" => $conexion->error]);
            }
        }
        break;

    case 'PUT':
        if (!isset($_GET['id'])) {
            echo json_encode(["error" => "ID requerido"]);
            exit;
        }

        $id = intval($_GET['id']);
        $data = json_decode(file_get_contents("php://input"), true);
        $estado = $conexion->real_escape_string($data['estado']);

        $sql = "UPDATE pagos SET estado='$estado' WHERE id_pago=$id";

        if ($conexion->query($sql)) {
            if ($estado === "pagado") {
                $conexion->query("UPDATE pedidos pe 
                                  JOIN pagos pa ON pe.id_pedido=pa.id_pedido
                                  SET pe.estado='en_proceso'
                                  WHERE pa.id_pago=$id");
            }
            echo json_encode(["mensaje" => "✅ Estado de pago actualizado correctamente."]);
        } else {
            echo json_encode(["error" => $conexion->error]);
        }
        break;

    case 'DELETE':
        if (!isset($_GET['id'])) {
            echo json_encode(["error" => "ID requerido"]);
            exit;
        }
        $id = intval($_GET['id']);
        $sql = "UPDATE pagos SET estado='fallido' WHERE id_pago=$id";
        echo $conexion->query($sql)
            ? json_encode(["mensaje" => "⚠️ Pago marcado como fallido."])
            : json_encode(["error" => $conexion->error]);
        break;

    default:
        echo json_encode(["error" => "Método no soportado."]);
        break;
}
?>
