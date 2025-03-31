<?php
// Incluir configuración de conexión a la base de datos
require_once __DIR__ . '/../admin/conexion.php';

// Función para generar el menú en formato JSON usando PDO
function generarMenuJSON($cn) {
    $menu = array();
    
    try {
        // Obtener subcategorías (que serán nuestras categorías principales como PUNTAS, CUCHILLAS, etc.)
        $sql_subcategorias = "SELECT * FROM subcategorias ORDER BY id_subcategoria";
        $stmt_subcategorias = $cn->query($sql_subcategorias);
        
        while($subcategoria = $stmt_subcategorias->fetch(PDO::FETCH_ASSOC)) {
            $id_subcategoria = $subcategoria['id_subcategoria'];
            $nombre_subcategoria = $subcategoria['nombre'];
            
            $subcat_item = array(
                'id' => $id_subcategoria,
                'nombre' => $nombre_subcategoria,
                'tipo' => 'subcategoria',
                'marcas' => array()
            );
            
            // Obtener tipos de maquinaria para esta subcategoría
            $sql_tipos = "SELECT * FROM tipo_maquinaria WHERE id_subcategoria = :id_subcategoria";
            $stmt_tipos = $cn->prepare($sql_tipos);
            $stmt_tipos->bindParam(':id_subcategoria', $id_subcategoria, PDO::PARAM_INT);
            $stmt_tipos->execute();
            
            // Array para evitar marcas duplicadas (usando el nombre como clave)
            $marcas_unicas = array();
            
            while($tipo = $stmt_tipos->fetch(PDO::FETCH_ASSOC)) {
                $id_tipo = $tipo['id_tipo_maquinaria'];
                
                // Si es el tipo "MISCELANEOS", lo manejamos de forma especial
                if ($tipo['nombre'] === 'MISCELANEOS') {
                    // Obtener los items misceláneos (que están como marcas en la BD)
                    $sql_misc = "SELECT * FROM marcas WHERE id_tipo_maquinaria = :id_tipo ORDER BY nombre";
                    $stmt_misc = $cn->prepare($sql_misc);
                    $stmt_misc->bindParam(':id_tipo', $id_tipo, PDO::PARAM_INT);
                    $stmt_misc->execute();
                    
                    $items_misc = array();
                    while($misc = $stmt_misc->fetch(PDO::FETCH_ASSOC)) {
                        $items_misc[] = array(
                            'id' => $misc['id_marca'],
                            'nombre' => $misc['nombre'],
                            'tipo' => 'item',
                            'pdf_ruta' => $misc['pdf_ruta']
                        );
                    }
                    
                    if (!empty($items_misc)) {
                        $subcat_item['items'] = $items_misc;
                    }
                } else {
                    // Para tipos normales, obtener las marcas
                    $sql_marcas = "SELECT * FROM marcas WHERE id_tipo_maquinaria = :id_tipo ORDER BY nombre";
                    $stmt_marcas = $cn->prepare($sql_marcas);
                    $stmt_marcas->bindParam(':id_tipo', $id_tipo, PDO::PARAM_INT);
                    $stmt_marcas->execute();
                    
                    while($marca = $stmt_marcas->fetch(PDO::FETCH_ASSOC)) {
                        // Usar el nombre como clave para evitar duplicados
                        $nombre_marca = $marca['nombre'];
                        
                        // Solo agregar si no existe ya
                        if (!isset($marcas_unicas[$nombre_marca])) {
                            $marcas_unicas[$nombre_marca] = array(
                                'id' => $marca['id_marca'],
                                'nombre' => $nombre_marca,
                                'tipo' => 'marca',
                                'pdf_ruta' => $marca['pdf_ruta']
                            );
                            
                            // Obtener PDFs adicionales para esta marca
                            $sql_pdfs = "SELECT * FROM archivos_pdf WHERE tipo_referencia = 'marca' AND id_referencia = :id_marca";
                            $stmt_pdfs = $cn->prepare($sql_pdfs);
                            $stmt_pdfs->bindParam(':id_marca', $marca['id_marca'], PDO::PARAM_INT);
                            $stmt_pdfs->execute();
                            
                            if ($stmt_pdfs->rowCount() > 0) {
                                $pdfs_marca = array();
                                while($pdf = $stmt_pdfs->fetch(PDO::FETCH_ASSOC)) {
                                    $pdfs_marca[] = array(
                                        'id' => $pdf['id_archivo'],
                                        'nombre' => $pdf['nombre_archivo'],
                                        'ruta' => $pdf['ruta_archivo'],
                                        'descripcion' => $pdf['descripcion']
                                    );
                                }
                                $marcas_unicas[$nombre_marca]['pdfs_adicionales'] = $pdfs_marca;
                            }
                        }
                    }
                }
            }
            
            // Convertir el array asociativo de marcas a un array indexado
            $subcat_item['marcas'] = array_values($marcas_unicas);
            
            $menu[] = $subcat_item;
        }
        
        // Para depuración
        error_log("Menu generado: " . json_encode($menu));
        
        return json_encode($menu);
    } catch (PDOException $e) {
        error_log("Error en generarMenuJSON: " . $e->getMessage());
        
        // En modo desarrollo, mostrar el error
        if (true) { // Cambiar a false en producción
            return json_encode(['error' => 'Error al generar el menú: ' . $e->getMessage()]);
        } else {
            return json_encode(['error' => 'Error al generar el menú']);
        }
    }
}

// Devolver menú en formato JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permitir acceso desde cualquier origen
echo generarMenuJSON($cn);