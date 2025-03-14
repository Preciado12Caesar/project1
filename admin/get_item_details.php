<?php
// Incluir archivo de conexión
require_once 'admin/conexion.php';

// Función para obtener detalles de un ítem específico
function getItemDetails($itemId, $itemType) {
    global $cn; // Usar la conexión PDO desde conexion.php
    $details = array();
    
    try {
        switch($itemType) {
            case 'producto':
                $sql = "SELECT p.*, m.nombre as marca_nombre, t.nombre as tipo_nombre, 
                        s.nombre as subcategoria_nombre, c.nombre as categoria_nombre
                        FROM productos p
                        JOIN marcas m ON p.id_marca = m.id_marca
                        JOIN tipo_maquinaria t ON m.id_tipo_maquinaria = t.id_tipo_maquinaria
                        JOIN subcategorias s ON t.id_subcategoria = s.id_subcategoria
                        JOIN categorias c ON s.id_categoria = c.id_categoria
                        WHERE p.id_producto = :itemId";
                
                $stmt = $cn->prepare($sql);
                $stmt->bindParam(':itemId', $itemId, PDO::PARAM_INT);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $details = $stmt->fetch(PDO::FETCH_ASSOC);
                }
                break;
                
            case 'marca':
                $sql = "SELECT m.*, t.nombre as tipo_nombre, s.nombre as subcategoria_nombre, 
                        c.nombre as categoria_nombre
                        FROM marcas m
                        JOIN tipo_maquinaria t ON m.id_tipo_maquinaria = t.id_tipo_maquinaria
                        JOIN subcategorias s ON t.id_subcategoria = s.id_subcategoria
                        JOIN categorias c ON s.id_categoria = c.id_categoria
                        WHERE m.id_marca = :itemId";
                
                $stmt = $cn->prepare($sql);
                $stmt->bindParam(':itemId', $itemId, PDO::PARAM_INT);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $details = $stmt->fetch(PDO::FETCH_ASSOC);
                }
                break;
                
            case 'tipo':
                $sql = "SELECT t.*, s.nombre as subcategoria_nombre, c.nombre as categoria_nombre
                        FROM tipo_maquinaria t
                        JOIN subcategorias s ON t.id_subcategoria = s.id_subcategoria
                        JOIN categorias c ON s.id_categoria = c.id_categoria
                        WHERE t.id_tipo_maquinaria = :itemId";
                
                $stmt = $cn->prepare($sql);
                $stmt->bindParam(':itemId', $itemId, PDO::PARAM_INT);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $details = $stmt->fetch(PDO::FETCH_ASSOC);
                }
                break;
        }
        
        // Obtener PDFs adicionales
        if (!empty($details)) {
            $tipo_referencia = '';
            $id_referencia = $itemId;
            
            switch($itemType) {
                case 'producto': $tipo_referencia = 'producto'; break;
                case 'marca': $tipo_referencia = 'marca'; break;
                case 'tipo': $tipo_referencia = 'tipo_maquinaria'; break;
            }
            
            if (!empty($tipo_referencia)) {
                $sql_pdfs = "SELECT * FROM archivos_pdf 
                            WHERE tipo_referencia = :tipo_referencia 
                            AND id_referencia = :id_referencia";
                            
                $stmt_pdfs = $cn->prepare($sql_pdfs);
                $stmt_pdfs->bindParam(':tipo_referencia', $tipo_referencia, PDO::PARAM_STR);
                $stmt_pdfs->bindParam(':id_referencia', $id_referencia, PDO::PARAM_INT);
                $stmt_pdfs->execute();
                
                if ($stmt_pdfs->rowCount() > 0) {
                    $details['pdfs_adicionales'] = $stmt_pdfs->fetchAll(PDO::FETCH_ASSOC);
                }
            }
        }
        
        return $details;
    } catch (PDOException $e) {
        error_log("Error en getItemDetails: " . $e->getMessage());
        return ['error' => 'Error al obtener datos del ítem'];
    }
}

// Verificar si es una petición AJAX
if(isset($_GET['action']) && $_GET['action'] == 'getItemDetails') {
    if(isset($_GET['itemId']) && isset($_GET['itemType'])) {
        // Validar datos de entrada
        $itemId = filter_var($_GET['itemId'], FILTER_VALIDATE_INT);
        $itemType = filter_var($_GET['itemType'], FILTER_SANITIZE_STRING);
        
        if ($itemId === false) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'ID de ítem inválido']);
            exit;
        }
        
        header('Content-Type: application/json');
        echo json_encode(getItemDetails($itemId, $itemType));
        exit;
    }
}

// Si se llama directamente a este archivo sin parámetros correctos
header('Content-Type: application/json');
echo json_encode(['error' => 'Parámetros insuficientes']);