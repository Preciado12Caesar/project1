<?php
/**
 * Archivo de configuración para la conexión a la base de datos
 * y otras configuraciones generales.
 */

// Parámetros de conexión a la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'maquinaria_catalogo');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuración de PDFs
define('PDF_DIRECTORY', 'documentos/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB

// Configuración de la aplicación
define('APP_NAME', 'Catálogo de Maquinaria');
define('APP_VERSION', '1.0.0');

// Zona horaria
date_default_timezone_set('America/Lima');

// Crear conexión PDO
try {
    $cn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $cn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $cn->exec("SET NAMES utf8");
} catch (PDOException $e) {
    // En producción, no mostrar el mensaje de error directamente
    error_log("Error de conexión a la base de datos: " . $e->getMessage());
    die(json_encode([
        "success" => false,
        "message" => "Error de conexión a la base de datos. Contacte al administrador."
    ]));
}

/**
 * Función para registrar actividad en el sistema
 * @param PDO $cn - Conexión a la base de datos
 * @param string $accion - Descripción de la acción realizada
 * @param int $id_usuario - ID del usuario que realizó la acción
 * @return bool - Resultado de la operación
 */
function registrar_actividad($cn, $accion, $id_usuario = 1) {
    try {
        $stmt = $cn->prepare("INSERT INTO registros_actividad (id_usuario, accion, fecha) VALUES (:id_usuario, :accion, NOW())");
        $stmt->bindParam(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $stmt->bindParam(':accion', $accion, PDO::PARAM_STR);
        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Error al registrar actividad: " . $e->getMessage());
        return false;
    }
}

/**
 * Crea el directorio para PDFs si no existe
 */
if (!file_exists('../' . PDF_DIRECTORY)) {
    mkdir('../' . PDF_DIRECTORY, 0777, true);
}