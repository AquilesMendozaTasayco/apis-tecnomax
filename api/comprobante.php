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
require_once "../config/cloudinary.php"; // 👈 agregado

use Cloudinary\Api\Upload\UploadApi;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_pedido = $_POST['id_pedido'] ?? null;
    $monto = $_POST['monto'] ?? null;
    $archivo = $_FILES['comprobante'] ?? null;

    if (!$id_pedido || !$archivo) {
        echo json_encode(["success" => false, "mensaje" => "Faltan datos requeridos"]);
        exit;
    }

    try {
        // 👇 Subir comprobante a Cloudinary
        $upload = (new UploadApi())->upload($archivo["tmp_name"], [
            'folder' => 'comprobantes'
        ]);
        $urlComprobante = $upload['secure_url'];

        $stmt = $conexion->prepare("INSERT INTO comprobantes (id_pedido, monto, imagen) VALUES (?, ?, ?)");
        $stmt->bind_param("ids", $id_pedido, $monto, $urlComprobante);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "mensaje" => "Comprobante subido correctamente", "url" => $urlComprobante]);
        } else {
            echo json_encode(["success" => false, "mensaje" => "Error SQL: " . $stmt->error]);
        }
        $stmt->close();

    } catch (Exception $e) {
        echo json_encode(["success" => false, "mensaje" => "Error al subir comprobante: " . $e->getMessage()]);
    }
}
?>
