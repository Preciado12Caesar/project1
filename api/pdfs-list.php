<?php
/**
 * API para obtener la lista de PDFs
 * Retorna un JSON con todos los PDFs disponibles
 */

// Incluir configuración de base de datos
require_once 'config.php';
require_once 'auth.php';

// Configuración de cabeceras para JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    // Consulta con JOIN para obtener nombres relacionados
    $sql = "
        SELECT a.*, 
        CASE 
            WHEN a.tipo_referencia = 'tipo_maquinaria' THEN t.nombre
            WHEN a.tipo_referencia = 'marca' THEN m.nombre
            WHEN a.tipo_referencia = 'producto' THEN p.serie
            ELSE 'Desconocido'
        END as item_nombre
        FROM archivos_pdf a
        LEFT JOIN tipo_maquinaria t ON a.tipo_referencia = 'tipo_maquinaria' AND a.id_referencia = t.id_tipo_maquinaria
        LEFT JOIN marcas m ON a.tipo_referencia = 'marca' AND a.id_referencia = m.id_marca
        LEFT JOIN productos p ON a.tipo_referencia = 'producto' AND a.id_referencia = p.id_producto
        ORDER BY a.fecha_creacion DESC
    ";
    
    $stmt = $cn->query($sql);
    $pdfs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Añadir tamaño de archivo a cada PDF
    foreach ($pdfs as &$pdf) {
        // Asegurarse de que la ruta no comience con ../
        $ruta_completa = $pdf['ruta_archivo'];
        if (substr($ruta_completa, 0, 3) === '../') {
            $ruta_completa = substr($ruta_completa, 3);
        }
        
        if (file_exists($ruta_completa)) {
            $pdf['tamano'] = filesize($ruta_completa);
        } else {
            $pdf['tamano'] = 0;
        }
    }
    
    // Retornar resultados
    echo json_encode($pdfs);
    
} catch (PDOException $e) {
    error_log("Error al obtener lista de PDFs: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error al cargar los PDFs',
        'error' => $e->getMessage()
    ]);
}