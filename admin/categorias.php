<?php
// admin/categorias.php - Gestión de categorías

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

// Eliminar categoría
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_categoria = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    
    try {
        // Verificar si hay subcategorías asociadas
        $check_stmt = $cn->prepare("SELECT COUNT(*) as total FROM subcategorias WHERE id_categoria = :id");
        $check_stmt->bindParam(':id', $id_categoria, PDO::PARAM_INT);
        $check_stmt->execute();
        $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total'] > 0) {
            $status_message = "No se puede eliminar la categoría porque tiene subcategorías asociadas.";
            $status_type = "danger";
        } else {
            // Eliminar categoría
            $stmt = $cn->prepare("DELETE FROM categorias WHERE id_categoria = :id");
            $stmt->bindParam(':id', $id_categoria, PDO::PARAM_INT);
            $stmt->execute();
            
            $status_message = "Categoría eliminada correctamente.";
            $status_type = "success";
        }
    } catch (PDOException $e) {
        $status_message = "Error al eliminar: " . $e->getMessage();
        $status_type = "danger";
    }
}

// Obtener listado de categorías
try {
    $stmt = $cn->query("SELECT * FROM categorias ORDER BY nombre");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $status_message = "Error al consultar categorías: " . $e->getMessage();
    $status_type = "danger";
    $categorias = array();
}

// Incluir header
include 'includes/header.php';
?>

<!-- Contenido principal -->
<div class="container-fluid py-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-folder me-2"></i>Gestión de Categorías
        </h1>
        <a href="categorias_create.php" class="btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus-circle fa-sm me-2"></i>Nueva Categoría
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
            <h6 class="m-0 font-weight-bold text-primary">Listado de Categorías</h6>
        </div>
        <div class="card-body">
            <?php if (count($categorias) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Subcategorías</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categorias as $categoria): ?>
                                <tr>
                                    <td><?php echo $categoria['id_categoria']; ?></td>
                                    <td><?php echo htmlspecialchars($categoria['nombre']); ?></td>
                                    <td>
                                        <?php 
                                        // Contar subcategorías
                                        $sub_stmt = $cn->prepare("SELECT COUNT(*) as total FROM subcategorias WHERE id_categoria = :id");
                                        $sub_stmt->bindParam(':id', $categoria['id_categoria'], PDO::PARAM_INT);
                                        $sub_stmt->execute();
                                        $total_subs = $sub_stmt->fetch(PDO::FETCH_ASSOC)['total'];
                                        echo $total_subs;
                                        ?>
                                    </td>
                                    <td>
                                        <a href="categorias_edit.php?id=<?php echo $categoria['id_categoria']; ?>" class="btn btn-sm btn-primary me-2">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="#" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $categoria['id_categoria']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        
                                        <!-- Modal de confirmación de eliminación -->
                                        <div class="modal fade" id="deleteModal<?php echo $categoria['id_categoria']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Confirmar Eliminación</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>¿Está seguro de que desea eliminar la categoría <strong><?php echo htmlspecialchars($categoria['nombre']); ?></strong>?</p>
                                                        <?php if ($total_subs > 0): ?>
                                                            <div class="alert alert-warning">
                                                                <i class="fas fa-exclamation-triangle me-2"></i>Esta categoría tiene <?php echo $total_subs; ?> subcategorías asociadas.
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <a href="categorias.php?action=delete&id=<?php echo $categoria['id_categoria']; ?>" class="btn btn-danger">Eliminar</a>
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
                    <i class="fas fa-info-circle me-2"></i>No hay categorías registradas.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Incluir footer
include 'includes/footer.php';
?>