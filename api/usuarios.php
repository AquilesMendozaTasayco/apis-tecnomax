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
require_once "../config/cloudinary.php"; // 👈 Importante

use Cloudinary\Api\Upload\UploadApi;

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {

    case 'GET':
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $sql = "SELECT id_usuario, nombre, apellido, correo, telefono, imagen_perfil, rol, estado, fecha_registro 
                    FROM usuarios 
                    WHERE id_usuario=$id AND estado != 'eliminado'";
            $result = $conexion->query($sql);
            echo json_encode($result->fetch_assoc());
        } else {
            $sql = "SELECT id_usuario, nombre, apellido, correo, telefono, imagen_perfil, rol, estado, fecha_registro 
                    FROM usuarios 
                    WHERE estado != 'eliminado'";
            $result = $conexion->query($sql);
            $usuarios = [];
            while ($row = $result->fetch_assoc()) {
                $usuarios[] = $row;
            }
            echo json_encode($usuarios);
        }
        break;

    case 'POST':
        // LOGIN
        if (isset($_GET['login'])) {
            $data = json_decode(file_get_contents("php://input"), true);

            $correo = $conexion->real_escape_string($data['correo']);
            $password = $data['password'];

            $sql = "SELECT * FROM usuarios WHERE correo='$correo' AND estado='activo' LIMIT 1";
            $result = $conexion->query($sql);

            if ($result->num_rows > 0) {
                $usuario = $result->fetch_assoc();

                if (password_verify($password, $usuario['password'])) {
                    unset($usuario['password']);
                    echo json_encode(["success" => true, "mensaje" => "Login exitoso", "usuario" => $usuario]);
                } else {
                    echo json_encode(["success" => false, "error" => "Contraseña incorrecta"]);
                }
            } else {
                echo json_encode(["success" => false, "error" => "Usuario no encontrado"]);
            }
        } else {
            // REGISTRO DE USUARIO
            $nombre = $conexion->real_escape_string($_POST['nombre']);
            $apellido = $conexion->real_escape_string($_POST['apellido']);
            $correo = $conexion->real_escape_string($_POST['correo']);
            $telefono = isset($_POST['telefono']) ? $conexion->real_escape_string($_POST['telefono']) : null;
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $rol = isset($_POST['rol']) ? $conexion->real_escape_string($_POST['rol']) : 'cliente';
            $estado = isset($_POST['estado']) ? $conexion->real_escape_string($_POST['estado']) : 'activo';

            $imagenUrl = "https://res.cloudinary.com/demo/image/upload/v1312461204/sample.jpg"; // valor por defecto

            // 👇 Subir imagen a Cloudinary (si existe)
            if (isset($_FILES['imagen_perfil']) && $_FILES['imagen_perfil']['error'] == 0) {
                $tmpPath = $_FILES['imagen_perfil']['tmp_name'];
                try {
                    $upload = (new UploadApi())->upload($tmpPath, [
                        'folder' => 'perfiles' // carpeta en Cloudinary
                    ]);
                    $imagenUrl = $upload['secure_url'];
                } catch (Exception $e) {
                    echo json_encode(["success" => false, "error" => "Error al subir imagen: " . $e->getMessage()]);
                    exit;
                }
            }

            $sql = "INSERT INTO usuarios (nombre, apellido, correo, telefono, imagen_perfil, password, rol, estado) 
                    VALUES ('$nombre','$apellido','$correo','$telefono','$imagenUrl','$password','$rol','$estado')";

            echo $conexion->query($sql)
                ? json_encode(["success" => true, "mensaje" => "Usuario registrado", "imagen_url" => $imagenUrl])
                : json_encode(["success" => false, "error" => $conexion->error]);
        }
        break;

    case 'DELETE':
        if (!isset($_GET['id'])) {
            echo json_encode(["error" => "ID requerido"]);
            exit;
        }
        $id = intval($_GET['id']);
        $sql = "UPDATE usuarios SET estado='eliminado' WHERE id_usuario=$id";
        echo $conexion->query($sql)
            ? json_encode(["mensaje" => "Usuario eliminado (soft delete)"])
            : json_encode(["error" => $conexion->error]);
        break;

    default:
        echo json_encode(["error" => "Método no permitido"]);
}
?>
