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
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once "../config/db.php";
require_once "../config/cloudinary.php";

use Cloudinary\Api\Upload\UploadApi;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id_pedido = $_POST['id_pedido'] ?? null;
    $monto = $_POST['monto'] ?? null;
    $archivo = $_FILES['comprobante'] ?? null; 
    if (!$id_pedido || !$archivo || $archivo['error'] !== UPLOAD_ERR_OK) {
        echo json_encode([
            "success" => false,
            "mensaje" => "Archivo temporal no disponible o inválido",
            "debug" => $_FILES
        ]);
        exit;
    }

    try {
        if (empty($archivo["tmp_name"]) || !file_exists($archivo["tmp_name"])) {
            throw new Exception("Archivo temporal no disponible");
        }

        $contenido = file_get_contents($archivo["tmp_name"]);
        $base64 = "data:" . $archivo["type"] . ";base64," . base64_encode($contenido);

        $upload = (new UploadApi())->upload($base64, [
            'folder' => 'comprobantes'
        ]);

        $urlComprobante = $upload['secure_url'];

        $stmt = $conexion->prepare("INSERT INTO comprobantes (id_pedido, monto, imagen) VALUES (?, ?, ?)");
        $stmt->bind_param("ids", $id_pedido, $monto, $urlComprobante);

        if ($stmt->execute()) {
            echo json_encode([
                "success" => true,
                "mensaje" => "Comprobante subido correctamente",
                "url" => $urlComprobante
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "mensaje" => "Error SQL: " . $stmt->error
            ]);
        }

        $stmt->close();

    } catch (Exception $e) {
        echo json_encode([
            "success" => false,
            "mensaje" => "Error al subir comprobante: " . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(["success" => false, "mensaje" => "Método no permitido"]);
}
?>
