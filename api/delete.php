<?php
/**
 * API para eliminar un PDF del sistema
 * Recibe el ID del PDF a eliminar
 */

// Incluir configuración de base de datos
require_once 'config.php';
require_once 'auth.php';

// Configuración de cabeceras para JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, DELETE');

// Verificar ID
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    echo json_encode([
        'success' => false,
        'message' => 'ID de PDF no válido'
    ]);
    exit;
}

$id = filter_var($_GET['id'], FILTER_VALIDATE_INT);

try {
    // Primero obtener la ruta del archivo
    $stmt = $cn->prepare("SELECT nombre_archivo, ruta_archivo FROM archivos_pdf WHERE id_archivo = :id");
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    
    $pdf = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$pdf) {
        echo json_encode([
            'success' => false,
            'message' => 'PDF no encontrado'
        ]);
        exit;
    }
    
    // Eliminar de la base de datos
    $stmt_delete = $cn->prepare("DELETE FROM archivos_pdf WHERE id_archivo = :id");
    $stmt_delete->bindParam(':id', $id, PDO::PARAM_INT);
    
    if ($stmt_delete->execute()) {
        // Intentar eliminar el archivo físico
        $ruta_completa = '../' . $pdf['ruta_archivo'];
        if (file_exists($ruta_completa)) {
            unlink($ruta_completa);
        }
        
        // Registrar actividad
        $accion = "PDF eliminado: " . $pdf['nombre_archivo'];
        registrar_actividad($cn, $accion, $auth_user_id);
        
        echo json_encode([
            'success' => true,
            'message' => 'PDF eliminado correctamente'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al eliminar el PDF de la base de datos'
        ]);
    }
} catch (PDOException $e) {
    error_log("Error al eliminar PDF: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor al eliminar el PDF',
        'error' => $e->getMessage()
    ]);
}