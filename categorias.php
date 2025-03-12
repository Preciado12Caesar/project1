<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require 'conexion.php';

try {
    $sql = "SELECT 
                categoria.id AS id_categoria, 
                categoria.nombre AS categoria_nombre, 
                producto.id AS id_producto,
                producto.nombre AS producto_nombre,
                marca.id AS id_marca, 
                marca.nombre AS marca_nombre
            FROM categoria
            LEFT JOIN producto ON producto.id_categoria = categoria.id
            LEFT JOIN marca ON producto.id_marca = marca.id
            GROUP BY categoria.id, producto.id, marca.id";

    $stmt = $cn->prepare($sql);
    $stmt->execute();
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $categorias = [];

    foreach ($result as $row) {
        $categoriaId = $row['id_categoria'];
        $productoId = $row['id_producto'];
        $marcaId = $row['id_marca'];

        if (!isset($categorias[$categoriaId])) {
            $categorias[$categoriaId] = [
                "id" => $categoriaId,
                "nombre" => $row['categoria_nombre'],
                "productos" => []
            ];
        }

        if (!empty($productoId) && !isset($categorias[$categoriaId]["productos"][$productoId])) {
            $categorias[$categoriaId]["productos"][$productoId] = [
                "id" => $productoId,
                "nombre" => $row['producto_nombre'],
                "marcas" => []
            ];
        }

        if (!empty($marcaId)) {
            $categorias[$categoriaId]["productos"][$productoId]["marcas"][] = [
                "id" => $marcaId,
                "nombre" => $row['marca_nombre']
            ];
        }
    }

    // Convertimos los productos en arrays indexados
    foreach ($categorias as &$categoria) {
        $categoria["productos"] = array_values($categoria["productos"]);
    }

    echo json_encode(array_values($categorias));
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
