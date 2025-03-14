<?php
// admin/tipos.php - Gestión de tipos de maquinaria

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

// Eliminar tipo de maquinaria
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_tipo = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    
    try {
        // Verificar si hay marcas asociadas
        $check_stmt = $cn->prepare("SELECT COUNT(*) as total FROM marcas WHERE id_tipo_maquinaria = :id");
        $check_stmt->bindParam(':id', $id_tipo, PDO::PARAM_INT);
        $check_stmt->execute();
        $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['total'] > 0) {
            $status_message = "No se puede eliminar el tipo de maquinaria porque tiene marcas asociadas.";
            $status_type = "danger";
        } else {
            // Eliminar PDFs asociados
            $pdf_stmt = $cn->prepare("SELECT * FROM archivos_pdf WHERE tipo_referencia = 'tipo_maquinaria' AND id_referencia = :id");
            $pdf_stmt->bindParam(':id', $id_tipo, PDO::PARAM_INT);
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
            
            // Eliminar tipo de maquinaria
            $stmt = $cn->prepare("DELETE FROM tipo_maquinaria WHERE id_tipo_maquinaria = :id");
            $stmt->bindParam(':id', $id_tipo, PDO::PARAM_INT);
            $stmt->execute();
            
            $status_message = "Tipo de maquinaria eliminado correctamente.";
            $status_type = "success";
        }
    } catch (PDOException $e) {
        $status_message = "Error al eliminar: " . $e->getMessage();
        $status_type = "danger";
    }
}

// Obtener listado de tipos de maquinaria con nombre de subcategoría
try {
    $stmt = $cn->query("SELECT t.*, s.nombre as subcategoria_nombre, c.nombre as categoria_nombre 
                        FROM tipo_maquinaria t 
                        JOIN subcategorias s ON t.id_subcategoria = s.id_subcategoria 
                        JOIN categorias c ON s.id_categoria = c.id_categoria 
                        ORDER BY c.nombre, s.nombre, t.nombre");
    $tipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $status_message = "Error al consultar tipos de maquinaria: " . $e->getMessage();
    $status_type = "danger";
    $tipos = array();
}

// Incluir header
include 'includes/header.php';
?>

<!-- Contenido principal -->
<div class="container-fluid py-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-truck me-2"></i>Gestión de Tipos de Maquinaria
        </h1>
        <a href="tipos_create.php" class="btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus-circle fa-sm me-2"></i>Nuevo Tipo
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
            <h6 class="m-0 font-weight-bold text-primary">Listado de Tipos de Maquinaria</h6>
        </div>
        <div class="card-body">
            <?php if (count($tipos) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Subcategoría</th>
                                <th>Categoría</th>
                                <th>Marcas</th>
                                <th>PDF</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tipos as $tipo): ?>
                                <tr>
                                    <td><?php echo $tipo['id_tipo_maquinaria']; ?></td>
                                    <td><?php echo htmlspecialchars($tipo['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($tipo['subcategoria_nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($tipo['categoria_nombre']); ?></td>
                                    <td>
                                        <?php 
                                        // Contar marcas
                                        $marcas_stmt = $cn->prepare("SELECT COUNT(*) as total FROM marcas WHERE id_tipo_maquinaria = :id");
                                        $marcas_stmt->bindParam(':id', $tipo['id_tipo_maquinaria'], PDO::PARAM_INT);
                                        $marcas_stmt->execute();
                                        $total_marcas = $marcas_stmt->fetch(PDO::FETCH_ASSOC)['total'];
                                        echo $total_marcas;
                                        ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($tipo['pdf_ruta'])): ?>
                                            <a href="../<?php echo $tipo['pdf_ruta']; ?>" target="_blank" class="btn btn-sm btn-danger">
                                                <i class="fas fa-file-pdf"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="tipos_edit.php?id=<?php echo $tipo['id_tipo_maquinaria']; ?>" class="btn btn-sm btn-primary me-2">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="#" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $tipo['id_tipo_maquinaria']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        
                                        <!-- Modal de confirmación de eliminación -->
                                        <div class="modal fade" id="deleteModal<?php echo $tipo['id_tipo_maquinaria']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Confirmar Eliminación</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>¿Está seguro de que desea eliminar el tipo de maquinaria <strong><?php echo htmlspecialchars($tipo['nombre']); ?></strong>?</p>
                                                        <?php if ($total_marcas > 0): ?>
                                                            <div class="alert alert-warning">
                                                                <i class="fas fa-exclamation-triangle me-2"></i>Este tipo de maquinaria tiene <?php echo $total_marcas; ?> marcas asociadas.
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <a href="tipos.php?action=delete&id=<?php echo $tipo['id_tipo_maquinaria']; ?>" class="btn btn-danger">Eliminar</a>
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
                    <i class="fas fa-info-circle me-2"></i>No hay tipos de maquinaria registrados.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Incluir footer
include 'includes/footer.php';
?>