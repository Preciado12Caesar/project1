<?php
// admin/servicios.php - Gestión de servicios

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

// Eliminar servicio (tipo de maquinaria de la categoría servicio)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_tipo_maquinaria = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    
    try {
        // Verificar si hay PDFs asociados
        $pdf_stmt = $cn->prepare("SELECT * FROM archivos_pdf WHERE tipo_referencia = 'tipo_maquinaria' AND id_referencia = :id");
        $pdf_stmt->bindParam(':id', $id_tipo_maquinaria, PDO::PARAM_INT);
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
        
        // Eliminar PDF principal si existe
        $tipo_stmt = $cn->prepare("SELECT pdf_ruta FROM tipo_maquinaria WHERE id_tipo_maquinaria = :id");
        $tipo_stmt->bindParam(':id', $id_tipo_maquinaria, PDO::PARAM_INT);
        $tipo_stmt->execute();
        
        $tipo = $tipo_stmt->fetch(PDO::FETCH_ASSOC);
        if (!empty($tipo['pdf_ruta']) && file_exists('../' . $tipo['pdf_ruta'])) {
            unlink('../' . $tipo['pdf_ruta']);
        }
        
        // Eliminar el tipo de maquinaria (servicio)
        $stmt = $cn->prepare("DELETE FROM tipo_maquinaria WHERE id_tipo_maquinaria = :id");
        $stmt->bindParam(':id', $id_tipo_maquinaria, PDO::PARAM_INT);
        $stmt->execute();
        
        $status_message = "Servicio eliminado correctamente.";
        $status_type = "success";
    } catch (PDOException $e) {
        $status_message = "Error al eliminar: " . $e->getMessage();
        $status_type = "danger";
    }
}

// Obtener la subcategoría "TREN DE RODAMIENTO" de la categoría "SERVICIOS"
try {
    $subcategoria_stmt = $cn->query("SELECT s.id_subcategoria FROM subcategorias s 
                                    JOIN categorias c ON s.id_categoria = c.id_categoria 
                                    WHERE c.nombre = 'SERVICIOS' AND s.nombre = 'TREN DE RODAMIENTO'");
    $subcategoria = $subcategoria_stmt->fetch(PDO::FETCH_ASSOC);
    $id_subcategoria_servicios = $subcategoria ? $subcategoria['id_subcategoria'] : null;
    
    // Obtener todos los servicios (tipos de maquinaria) de esta subcategoría
    if ($id_subcategoria_servicios) {
        $stmt = $cn->prepare("SELECT t.*, s.nombre as subcategoria_nombre, c.nombre as categoria_nombre 
                            FROM tipo_maquinaria t 
                            JOIN subcategorias s ON t.id_subcategoria = s.id_subcategoria 
                            JOIN categorias c ON s.id_categoria = c.id_categoria 
                            WHERE t.id_subcategoria = :id_subcategoria 
                            ORDER BY t.nombre");
        $stmt->bindParam(':id_subcategoria', $id_subcategoria_servicios, PDO::PARAM_INT);
        $stmt->execute();
        $servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $servicios = array();
        $status_message = "No se encontró la subcategoría de servicios.";
        $status_type = "warning";
    }
} catch (PDOException $e) {
    $status_message = "Error al consultar servicios: " . $e->getMessage();
    $status_type = "danger";
    $servicios = array();
}

// Incluir header
include 'includes/header.php';
?>

<!-- Contenido principal -->
<div class="container-fluid py-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-cogs me-2"></i>Gestión de Servicios
        </h1>
        <a href="servicios_create.php" class="btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus-circle fa-sm me-2"></i>Nuevo Servicio
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
            <h6 class="m-0 font-weight-bold text-primary">Listado de Servicios</h6>
        </div>
        <div class="card-body">
            <?php if (count($servicios) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre del Servicio</th>
                                <th>Subcategoría</th>
                                <th>PDF</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($servicios as $servicio): ?>
                                <tr>
                                    <td><?php echo $servicio['id_tipo_maquinaria']; ?></td>
                                    <td><?php echo htmlspecialchars($servicio['nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($servicio['subcategoria_nombre']); ?></td>
                                    <td>
                                        <?php if (!empty($servicio['pdf_ruta'])): ?>
                                            <a href="../<?php echo $servicio['pdf_ruta']; ?>" target="_blank" class="btn btn-sm btn-danger">
                                                <i class="fas fa-file-pdf"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">No</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="servicios_edit.php?id=<?php echo $servicio['id_tipo_maquinaria']; ?>" class="btn btn-sm btn-primary me-2">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="#" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $servicio['id_tipo_maquinaria']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        
                                        <!-- Modal de confirmación de eliminación -->
                                        <div class="modal fade" id="deleteModal<?php echo $servicio['id_tipo_maquinaria']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Confirmar Eliminación</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>¿Está seguro de que desea eliminar el servicio <strong><?php echo htmlspecialchars($servicio['nombre']); ?></strong>?</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <a href="servicios.php?action=delete&id=<?php echo $servicio['id_tipo_maquinaria']; ?>" class="btn btn-danger">Eliminar</a>
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
                    <i class="fas fa-info-circle me-2"></i>No hay servicios registrados.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Incluir footer
include 'includes/footer.php';
?>