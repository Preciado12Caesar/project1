<?php
// admin/subcategorias.php - Gestión de subcategorías

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

// Eliminar subcategoría
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_subcategoria = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    
    try {
        // Verificar si hay tipos de maquinaria asociados
        $check_stmt = $cn->prepare("SELECT COUNT(*) as total FROM tipo_maquinaria WHERE id_subcategoria = :id");
        $check_stmt->bindParam(':id', $id_subcategoria, PDO::PARAM_INT);
        $check_stmt->execute();
        $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total'] > 0) {
            $status_message = "No se puede eliminar la subcategoría porque tiene tipos de maquinaria asociados.";
            $status_type = "danger";
        } else {
            // Eliminar subcategoría
            $stmt = $cn->prepare("DELETE FROM subcategorias WHERE id_subcategoria = :id");
            $stmt->bindParam(':id', $id_subcategoria, PDO::PARAM_INT);
            $stmt->execute();
            
            $status_message = "Subcategoría eliminada correctamente.";
            $status_type = "success";
        }
    } catch (PDOException $e) {
        $status_message = "Error al eliminar: " . $e->getMessage();
        $status_type = "danger";
    }
}

// Obtener listado de subcategorías con nombre de categoría
try {
    $stmt = $cn->query("SELECT s.*, c.nombre as categoria_nombre 
                        FROM subcategorias s 
                        JOIN categorias c ON s.id_categoria = c.id_categoria 
                        ORDER BY c.nombre, s.nombre");
    $subcategorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $status_message = "Error al consultar subcategorías: " . $e->getMessage();
    $status_type = "danger";
    $subcategorias = array();
}

// Incluir header
include 'includes/header.php';
?>

<!-- Contenido principal -->
<div class="container-fluid py-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-folder-open me-2"></i>Gestión de Subcategorías
        </h1>
        <a href="subcategorias_create.php" class="btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus-circle fa-sm me-2"></i>Nueva Subcategoría
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
            <h6 class="m-0 font-weight-bold text-primary">Listado de Subcategorías</h6>
        </div>
        <div class="card-body">
            <?php if (count($subcategorias) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Categoría</th>
                                <th>Tipos</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subcategorias as $subcategoria): ?>
                                <tr>
                                    <td><?php echo $subcategoria['id_subcategoria']; ?></td>
                                    <td><?php echo htmlspecialchars($subcategoria['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($subcategoria['categoria_nombre']); ?></td>
                                    <td>
                                        <?php 
                                        // Contar tipos de maquinaria
                                        $tipos_stmt = $cn->prepare("SELECT COUNT(*) as total FROM tipo_maquinaria WHERE id_subcategoria = :id");
                                        $tipos_stmt->bindParam(':id', $subcategoria['id_subcategoria'], PDO::PARAM_INT);
                                        $tipos_stmt->execute();
                                        $total_tipos = $tipos_stmt->fetch(PDO::FETCH_ASSOC)['total'];
                                        echo $total_tipos;
                                        ?>
                                    </td>
                                    <td>
                                        <a href="subcategorias_edit.php?id=<?php echo $subcategoria['id_subcategoria']; ?>" class="btn btn-sm btn-primary me-2">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="#" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $subcategoria['id_subcategoria']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        
                                        <!-- Modal de confirmación de eliminación -->
                                        <div class="modal fade" id="deleteModal<?php echo $subcategoria['id_subcategoria']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Confirmar Eliminación</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>¿Está seguro de que desea eliminar la subcategoría <strong><?php echo htmlspecialchars($subcategoria['nombre']); ?></strong>?</p>
                                                        <?php if ($total_tipos > 0): ?>
                                                            <div class="alert alert-warning">
                                                                <i class="fas fa-exclamation-triangle me-2"></i>Esta subcategoría tiene <?php echo $total_tipos; ?> tipos de maquinaria asociados.
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <a href="subcategorias.php?action=delete&id=<?php echo $subcategoria['id_subcategoria']; ?>" class="btn btn-danger">Eliminar</a>
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
                    <i class="fas fa-info-circle me-2"></i>No hay subcategorías registradas.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Incluir footer
include 'includes/footer.php';
?>