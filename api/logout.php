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

session_start();
session_destroy();
echo json_encode(["success" => true, "mensaje" => "Sesión cerrada"]);
?>
