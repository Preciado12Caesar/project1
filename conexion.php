<?php
// Configuración de conexión a la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'catalogo');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $cn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $cn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(["error" => "Error de conexión: " . $e->getMessage()]));
}
?>
