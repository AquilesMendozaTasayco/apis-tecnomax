<?php
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once "../config/db.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pedido = $_POST['id_pedido'] ?? null;
    $monto = $_POST['monto'] ?? null;
    $archivo = $_FILES['comprobante'] ?? null;

    if (!$id_pedido || !$archivo) {
        echo json_encode(["success" => false, "mensaje" => "Faltan datos requeridos"]);
        exit;
    }

    $directorio = "../uploads/comprobantes/";
    if (!file_exists($directorio)) {
        mkdir($directorio, 0777, true);
    }

    $nombreArchivo = time() . "_" . basename($archivo["name"]);
    $rutaDestino = $directorio . $nombreArchivo;

    if (move_uploaded_file($archivo["tmp_name"], $rutaDestino)) {
        $stmt = $conexion->prepare("INSERT INTO comprobantes (id_pedido, monto, imagen) VALUES (?, ?, ?)");
        $stmt->bind_param("ids", $id_pedido, $monto, $nombreArchivo);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "mensaje" => "Comprobante subido correctamente"]);
        } else {
            echo json_encode(["success" => false, "mensaje" => "Error SQL: " . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(["success" => false, "mensaje" => "Error al mover el archivo"]);
    }
}
?>
