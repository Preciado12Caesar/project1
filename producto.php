<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require 'conexion.php';

try {
    // Obtener productos
    $sqlProductos = "SELECT 
                        producto.id, 
                        producto.nombre, 
                        producto.descripcion, 
                        producto.id_categoria, 
                        producto.id_marca, 
                        marca.nombre AS marca, 
                        producto.pdf
                     FROM producto
                     LEFT JOIN marca ON producto.id_marca = marca.id";
    
    $stmtProductos = $cn->prepare($sqlProductos);
    $stmtProductos->execute();
    $productos = $stmtProductos->fetchAll(PDO::FETCH_ASSOC);

    // Obtener imágenes y agruparlas por producto
    $sqlImagenes = "SELECT id_producto, imagen FROM productoimagen";
    $stmtImagenes = $cn->prepare($sqlImagenes);
    $stmtImagenes->execute();
    $imagenes = $stmtImagenes->fetchAll(PDO::FETCH_ASSOC);

    // Crear un array asociativo con las imágenes organizadas por producto
    $imagenesPorProducto = [];
    foreach ($imagenes as $img) {
        $idProducto = $img['id_producto'];
        if (!isset($imagenesPorProducto[$idProducto])) {
            $imagenesPorProducto[$idProducto] = [];
        }
        $imagenesPorProducto[$idProducto][] = $img['imagen'];
    }

    // Agregar las imágenes a cada producto
    foreach ($productos as &$producto) {
        $id = $producto['id'];
        $producto['imagenes'] = $imagenesPorProducto[$id] ?? []; // Si no tiene imágenes, devuelve un array vacío
    }

    echo json_encode($productos);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
