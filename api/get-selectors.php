<?php
/**
 * API para obtener datos para selectores de formularios
 * Retorna un JSON con los datos según el tipo solicitado
 */

// Incluir configuración de base de datos
require_once 'config.php';
require_once 'auth.php';

// Configuración de cabeceras para JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Obtener el tipo de selector solicitado
$type = isset($_GET['type']) ? $_GET['type'] : '';

// Validar tipo
if (empty($type)) {
    echo json_encode([
        'success' => false,
        'message' => 'Tipo de selector no especificado'
    ]);
    exit;
}

try {
    $results = [];
    
    switch ($type) {
        case 'tipo_maquinaria':
            $sql = "SELECT id_tipo_maquinaria as id, nombre FROM tipo_maquinaria ORDER BY nombre";
            $stmt = $cn->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'marcas':
            $sql = "SELECT id_marca as id, nombre FROM marcas ORDER BY nombre";
            $stmt = $cn->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'productos':
            $sql = "SELECT id_producto as id, serie as nombre FROM productos ORDER BY serie";
            $stmt = $cn->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'subcategorias':
            $sql = "SELECT id_subcategoria as id, nombre FROM subcategorias ORDER BY nombre";
            $stmt = $cn->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'categorias':
            $sql = "SELECT id_categoria as id, nombre FROM categorias ORDER BY nombre";
            $stmt = $cn->query($sql);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Tipo de selector no válido'
            ]);
            exit;
    }
    
    // Retornar resultados
    echo json_encode($results);
    
} catch (PDOException $e) {
    error_log("Error al obtener datos para selector: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => "Error al cargar datos para $type",
        'error' => $e->getMessage()
    ]);
}