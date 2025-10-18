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
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once "../config/db.php";

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    echo json_encode(["error" => "Solo se permiten consultas (GET)"]);
    exit;
}

$tipo = isset($_GET['tipo']) ? $_GET['tipo'] : '';

switch ($tipo) {

    case 'ventas_mensuales':
        $sql = "SELECT DATE_FORMAT(fecha, '%Y-%m') AS mes, SUM(total) AS total_ventas
                FROM pedidos
                WHERE estado IN ('en_proceso','enviado','entregado')
                GROUP BY mes
                ORDER BY mes DESC";
        break;

    case 'productos_mas_vendidos':
        $sql = "SELECT pr.id_producto, pr.nombre, pr.precio, pr.imagen_principal, 
                    SUM(dp.cantidad) AS total_vendidos
                FROM detalle_pedido dp
                JOIN productos pr ON dp.id_producto = pr.id_producto
                JOIN pedidos pe ON dp.id_pedido = pe.id_pedido
                WHERE pe.estado IN ('en_proceso','enviado','entregado')
                GROUP BY pr.id_producto, pr.nombre, pr.precio, pr.imagen_principal
                ORDER BY total_vendidos DESC
                LIMIT 10";
        break;


    case 'clientes_frecuentes':
        $sql = "SELECT u.id_usuario, u.nombre, u.apellido, COUNT(p.id_pedido) AS pedidos_realizados
                FROM pedidos p
                JOIN usuarios u ON p.id_usuario = u.id_usuario
                WHERE p.estado IN ('en_proceso','enviado','entregado')
                GROUP BY u.id_usuario, u.nombre, u.apellido
                ORDER BY pedidos_realizados DESC
                LIMIT 10";
        break;

    default:
        echo json_encode(["error" => "Tipo de reporte no válido"]);
        exit;
}

$result = $conexion->query($sql);
$datos = [];
while ($row = $result->fetch_assoc()) {
    $datos[] = $row;
}
echo json_encode($datos);
?>
