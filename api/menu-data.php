<?php
// Incluir configuración de conexión a la base de datos
require_once __DIR__ . '/../admin/conexion.php';

// Función para generar el menú en formato JSON usando PDO
function generarMenuJSON($cn) {
    $menu = array();
    
    try {
        // Obtener categorías principales (Nivel 1)
        $sql_categorias = "SELECT * FROM categorias ORDER BY id_categoria";
        $stmt_categorias = $cn->query($sql_categorias);
        
        while($categoria = $stmt_categorias->fetch(PDO::FETCH_ASSOC)) {
            $id_categoria = $categoria['id_categoria'];
            $nombre_categoria = $categoria['nombre'];
            
            $cat_item = array(
                'id' => $id_categoria,
                'nombre' => $nombre_categoria,
                'tipo' => 'categoria',
                'subcategorias' => array()
            );
            
            // Obtener subcategorías (Nivel 2)
            $sql_subcategorias = "SELECT * FROM subcategorias WHERE id_categoria = :id_categoria ORDER BY nombre";
            $stmt_subcategorias = $cn->prepare($sql_subcategorias);
            $stmt_subcategorias->bindParam(':id_categoria', $id_categoria, PDO::PARAM_INT);
            $stmt_subcategorias->execute();
            
            while($subcategoria = $stmt_subcategorias->fetch(PDO::FETCH_ASSOC)) {
                $id_subcategoria = $subcategoria['id_subcategoria'];
                $nombre_subcategoria = $subcategoria['nombre'];
                
                $subcat_item = array(
                    'id' => $id_subcategoria,
                    'nombre' => $nombre_subcategoria,
                    'tipo' => 'subcategoria',
                    'tipos_maquinaria' => array()
                );
                
                // Obtener tipos de maquinaria (Nivel 3)
                $sql_tipos = "SELECT * FROM tipo_maquinaria WHERE id_subcategoria = :id_subcategoria ORDER BY nombre";
                $stmt_tipos = $cn->prepare($sql_tipos);
                $stmt_tipos->bindParam(':id_subcategoria', $id_subcategoria, PDO::PARAM_INT);
                $stmt_tipos->execute();
                
                while($tipo = $stmt_tipos->fetch(PDO::FETCH_ASSOC)) {
                    $id_tipo = $tipo['id_tipo_maquinaria'];
                    $nombre_tipo = $tipo['nombre'];
                    $pdf_tipo = isset($tipo['pdf_ruta']) ? $tipo['pdf_ruta'] : null;
                    
                    $tipo_item = array(
                        'id' => $id_tipo,
                        'nombre' => $nombre_tipo,
                        'tipo' => 'tipo_maquinaria',
                        'pdf_ruta' => $pdf_tipo,
                        'marcas' => array()
                    );
                    
                    // Obtener PDFs adicionales para este tipo de maquinaria
                    $sql_pdfs_tipo = "SELECT * FROM archivos_pdf WHERE tipo_referencia = 'tipo_maquinaria' AND id_referencia = :id_tipo";
                    $stmt_pdfs_tipo = $cn->prepare($sql_pdfs_tipo);
                    $stmt_pdfs_tipo->bindParam(':id_tipo', $id_tipo, PDO::PARAM_INT);
                    $stmt_pdfs_tipo->execute();
                    
                    if ($stmt_pdfs_tipo->rowCount() > 0) {
                        $pdfs_tipo = array();
                        while($pdf = $stmt_pdfs_tipo->fetch(PDO::FETCH_ASSOC)) {
                            $pdfs_tipo[] = array(
                                'id' => $pdf['id_archivo'],
                                'nombre' => $pdf['nombre_archivo'],
                                'ruta' => $pdf['ruta_archivo'],
                                'descripcion' => $pdf['descripcion']
                            );
                        }
                        $tipo_item['pdfs_adicionales'] = $pdfs_tipo;
                    }
                    
                    // Obtener marcas (Nivel 4)
                    $sql_marcas = "SELECT * FROM marcas WHERE id_tipo_maquinaria = :id_tipo ORDER BY nombre";
                    $stmt_marcas = $cn->prepare($sql_marcas);
                    $stmt_marcas->bindParam(':id_tipo', $id_tipo, PDO::PARAM_INT);
                    $stmt_marcas->execute();
                    
                    while($marca = $stmt_marcas->fetch(PDO::FETCH_ASSOC)) {
                        $id_marca = $marca['id_marca'];
                        $nombre_marca = $marca['nombre'];
                        $pdf_marca = isset($marca['pdf_ruta']) ? $marca['pdf_ruta'] : null;
                        
                        $marca_item = array(
                            'id' => $id_marca,
                            'nombre' => $nombre_marca,
                            'tipo' => 'marca',
                            'pdf_ruta' => $pdf_marca,
                            'productos' => array()
                        );
                        
                        // Obtener PDFs adicionales para esta marca
                        $sql_pdfs_marca = "SELECT * FROM archivos_pdf WHERE tipo_referencia = 'marca' AND id_referencia = :id_marca";
                        $stmt_pdfs_marca = $cn->prepare($sql_pdfs_marca);
                        $stmt_pdfs_marca->bindParam(':id_marca', $id_marca, PDO::PARAM_INT);
                        $stmt_pdfs_marca->execute();
                        
                        if ($stmt_pdfs_marca->rowCount() > 0) {
                            $pdfs_marca = array();
                            while($pdf = $stmt_pdfs_marca->fetch(PDO::FETCH_ASSOC)) {
                                $pdfs_marca[] = array(
                                    'id' => $pdf['id_archivo'],
                                    'nombre' => $pdf['nombre_archivo'],
                                    'ruta' => $pdf['ruta_archivo'],
                                    'descripcion' => $pdf['descripcion']
                                );
                            }
                            $marca_item['pdfs_adicionales'] = $pdfs_marca;
                        }
                        
                        // Obtener productos/series (Nivel 5)
                        $sql_productos = "SELECT * FROM productos WHERE id_marca = :id_marca ORDER BY serie";
                        $stmt_productos = $cn->prepare($sql_productos);
                        $stmt_productos->bindParam(':id_marca', $id_marca, PDO::PARAM_INT);
                        $stmt_productos->execute();
                        
                        while($producto = $stmt_productos->fetch(PDO::FETCH_ASSOC)) {
                            $id_producto = $producto['id_producto'];
                            $serie = $producto['serie'];
                            $pdf_producto = isset($producto['pdf_ruta']) ? $producto['pdf_ruta'] : null;
                            
                            $producto_item = array(
                                'id' => $id_producto,
                                'nombre' => $serie,
                                'tipo' => 'producto',
                                'pdf_ruta' => $pdf_producto
                            );
                            
                            // Obtener PDFs adicionales para este producto
                            $sql_pdfs_producto = "SELECT * FROM archivos_pdf WHERE tipo_referencia = 'producto' AND id_referencia = :id_producto";
                            $stmt_pdfs_producto = $cn->prepare($sql_pdfs_producto);
                            $stmt_pdfs_producto->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
                            $stmt_pdfs_producto->execute();
                            
                            if ($stmt_pdfs_producto->rowCount() > 0) {
                                $pdfs_producto = array();
                                while($pdf = $stmt_pdfs_producto->fetch(PDO::FETCH_ASSOC)) {
                                    $pdfs_producto[] = array(
                                        'id' => $pdf['id_archivo'],
                                        'nombre' => $pdf['nombre_archivo'],
                                        'ruta' => $pdf['ruta_archivo'],
                                        'descripcion' => $pdf['descripcion']
                                    );
                                }
                                $producto_item['pdfs_adicionales'] = $pdfs_producto;
                            }
                            
                            $marca_item['productos'][] = $producto_item;
                        }
                        
                        $tipo_item['marcas'][] = $marca_item;
                    }
                    
                    $subcat_item['tipos_maquinaria'][] = $tipo_item;
                }
                
                $cat_item['subcategorias'][] = $subcat_item;
            }
            
            $menu[] = $cat_item;
        }
        
        return json_encode($menu);
    } catch (PDOException $e) {
        error_log("Error en generarMenuJSON: " . $e->getMessage());
        return json_encode(['error' => 'Error al generar el menú']);
    }
}

// Devolver menú en formato JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Permitir acceso desde cualquier origen
echo generarMenuJSON($cn);