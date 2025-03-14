<?php
// admin/marcas.php - Gestión de marcas

// Iniciar sesión
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Incluir archivo de conexión
require_once 'conexion.php';

// Mensaje de estado
$status_message = '';
$status_type = '';

// Eliminar marca
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_marca = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    
    try {
        // Verificar si hay productos asociados
        $check_stmt = $cn->prepare("SELECT COUNT(*) as total FROM productos WHERE id_marca = :id");
        $check_stmt->bindParam(':id', $id_marca, PDO::PARAM_INT);
        $check_stmt->execute();
        $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total'] > 0) {
            $status_message = "No se puede eliminar la marca porque tiene productos asociados.";
            $status_type = "danger";
        } else {
            // Eliminar PDFs asociados
            $pdf_stmt = $cn->prepare("SELECT * FROM archivos_pdf WHERE tipo_referencia = 'marca' AND id_referencia = :id");
            $pdf_stmt->bindParam(':id', $id_marca, PDO::PARAM_INT);
            $pdf_stmt->execute();
            
            $pdfs = $pdf_stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($pdfs as $pdf) {
                // Eliminar archivo físico
                if (!empty($pdf['ruta_archivo']) && file_exists('../' . $pdf['ruta_archivo'])) {
                    unlink('../' . $pdf['ruta_archivo']);
                }
                
                // Eliminar registro
                $delete_pdf_stmt = $cn->prepare("DELETE FROM archivos_pdf WHERE id_archivo = :id_archivo");
                $delete_pdf_stmt->bindParam(':id_archivo', $pdf['id_archivo'], PDO::PARAM_INT);
                $delete_pdf_stmt->execute();
            }
            
            // Eliminar el PDF principal si existe
            $marca_stmt = $cn->prepare("SELECT pdf_ruta FROM marcas WHERE id_marca = :id");
            $marca_stmt->bindParam(':id', $id_marca, PDO::PARAM_INT);
            $marca_stmt->execute();
            $marca = $marca_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!empty($marca['pdf_ruta']) && file_exists('../' . $marca['pdf_ruta'])) {
                unlink('../' . $marca['pdf_ruta']);
            }
            
            // Eliminar marca
            $stmt = $cn->prepare("DELETE FROM marcas WHERE id_marca = :id");
            $stmt->bindParam(':id', $id_marca, PDO::PARAM_INT);
            $stmt->execute();
            
            $status_message = "Marca eliminada correctamente.";
            $status_type = "success";
        }
    } catch (PDOException $e) {
        $status_message = "Error al eliminar: " . $e->getMessage();
        $status_type = "danger";
    }
}

// Obtener listado de marcas con nombre de tipo de maquinaria
try {
    $stmt = $cn->query("SELECT m.*, t.nombre as tipo_nombre, s.nombre as subcategoria_nombre 
                        FROM marcas m 
                        JOIN tipo_maquinaria t ON m.id_tipo_maquinaria = t.id_tipo_maquinaria 
                        JOIN subcategorias s ON t.id_subcategoria = s.id_subcategoria 
                        ORDER BY t.nombre, m.nombre");
    $marcas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $status_message = "Error al consultar marcas: " . $e->getMessage();
    $status_type = "danger";
    $marcas = array();
}

// Incluir header
include 'includes/header.php';
?>

<!-- Contenido principal -->
<div class="container-fluid py-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-trademark me-2"></i>Gestión de Marcas
        </h1>
        <a href="marcas_create.php" class="btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus-circle fa-sm me-2"></i>Nueva Marca
        </a>
    </div>
    
    <?php if (!empty($status_message)): ?>
        <div class="alert alert-<?php echo $status_type; ?> alert-dismissible fade show" role="alert">
            <?php echo $status_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Listado de Marcas</h6>
        </div>
        <div class="card-body">
            <?php if (count($marcas) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Tipo de Maquinaria</th>
                                <th>Subcategoría</th>
                                <th>Productos</th>
                                <th>PDF</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($marcas as $marca): ?>
                                <tr>
                                    <td><?php echo $marca['id_marca']; ?></td>
                                    <td><?php echo htmlspecialchars($marca['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($marca['tipo_nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($marca['subcategoria_nombre']); ?></td>
                                    <td>
                                        <?php 
                                        // Contar productos
                                        $productos_stmt = $cn->prepare("SELECT COUNT(*) as total FROM productos WHERE id_marca = :id");
                                        $productos_stmt->bindParam(':id', $marca['id_marca'], PDO::PARAM_INT);
                                        $productos_stmt->execute();
                                        $total_productos = $productos_stmt->fetch(PDO::FETCH_ASSOC)['total'];
                                        echo $total_productos;
                                        ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($marca['pdf_ruta'])): ?>
                                            <a href="../<?php echo $marca['pdf_ruta']; ?>" target="_blank" class="btn btn-sm btn-danger">
                                                <i class="fas fa-file-pdf"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="marcas_edit.php?id=<?php echo $marca['id_marca']; ?>" class="btn btn-sm btn-primary me-2">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="#" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $marca['id_marca']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        
                                        <!-- Modal de confirmación de eliminación -->
                                        <div class="modal fade" id="deleteModal<?php echo $marca['id_marca']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Confirmar Eliminación</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>¿Está seguro de que desea eliminar la marca <strong><?php echo htmlspecialchars($marca['nombre']); ?></strong>?</p>
                                                        <?php if ($total_productos > 0): ?>
                                                            <div class="alert alert-warning">
                                                                <i class="fas fa-exclamation-triangle me-2"></i>Esta marca tiene <?php echo $total_productos; ?> productos asociados.
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <a href="marcas.php?action=delete&id=<?php echo $marca['id_marca']; ?>" class="btn btn-danger">Eliminar</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No hay marcas registradas.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Incluir footer
include 'includes/footer.php';
?>