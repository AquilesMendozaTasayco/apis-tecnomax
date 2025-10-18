<?php
require 'vendor/autoload.php';
require 'config/cloudinary.php';
require 'config/db.php'; // tu conexión MySQL

use Cloudinary\Api\Upload\UploadApi;

// Ajusta estas rutas según tu estructura
$folders = [
    'productos' => 'uploads/productos',
    'perfiles' => 'uploads/perfiles',
    'comprobantes' => 'uploads/comprobantes'
];

foreach ($folders as $tipo => $rutaCarpeta) {
    echo "📂 Migrando $tipo...\n";

    $files = glob("$rutaCarpeta/*.*");

    foreach ($files as $file) {
        try {
            $nombreArchivo = basename($file);

            // Subir imagen a Cloudinary
            $uploadResult = (new UploadApi())->upload($file, [
                'folder' => $tipo // Cloudinary folder name
            ]);

            $url = $uploadResult['secure_url'];

            // Actualizar URL en la base de datos según tu estructura
            switch ($tipo) {
                case 'productos':
                    $stmt = $pdo->prepare("UPDATE productos SET imagen = ? WHERE imagen = ?");
                    $stmt->execute([$url, $nombreArchivo]);
                    break;
                case 'perfiles':
                    $stmt = $pdo->prepare("UPDATE usuarios SET foto_perfil = ? WHERE foto_perfil = ?");
                    $stmt->execute([$url, $nombreArchivo]);
                    break;
                case 'comprobantes':
                    $stmt = $pdo->prepare("UPDATE comprobantes SET archivo = ? WHERE archivo = ?");
                    $stmt->execute([$url, $nombreArchivo]);
                    break;
            }

            echo "✅ Subido $nombreArchivo → $url\n";

        } catch (Exception $e) {
            echo "❌ Error con $file: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n🎉 Migración completa.\n";
