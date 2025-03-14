<?php
// admin/archivos.php - Gestión de PDFs

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

// Eliminar archivo PDF
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id_archivo = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    
    try {
        // Obtener información del archivo
        $pdf_stmt = $cn->prepare("SELECT * FROM archivos_pdf WHERE id_archivo = :id");
        $pdf_stmt->bindParam(':id', $id_archivo, PDO::PARAM_INT);
        $pdf_stmt->execute();
        
        if ($pdf_stmt->rowCount() > 0) {
            $pdf = $pdf_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Eliminar archivo físico
            if (!empty($pdf['ruta_archivo']) && file_exists('../' . $pdf['ruta_archivo'])) {
                unlink('../' . $pdf['ruta_archivo']);
            }
            
            // Eliminar registro
            $delete_stmt = $cn->prepare("DELETE FROM archivos_pdf WHERE id_archivo = :id");
            $delete_stmt->bindParam(':id', $id_archivo, PDO::PARAM_INT);
            $delete_stmt->execute();
            
            $status_message = "Archivo PDF eliminado correctamente.";
            $status_type = "success";
        } else {
            $status_message = "El archivo PDF no existe.";
            $status_type = "danger";
        }
    } catch (PDOException $e) {
        $status_message = "Error al eliminar: " . $e->getMessage();
        $status_type = "danger";
    }
}

// Obtener listado de PDFs con información de referencia
try {
    $stmt = $cn->query("SELECT a.*, 
                        CASE 
                            WHEN a.tipo_referencia = 'tipo_maquinaria' THEN (SELECT nombre FROM tipo_maquinaria WHERE id_tipo_maquinaria = a.id_referencia)
                            WHEN a.tipo_referencia = 'marca' THEN (SELECT nombre FROM marcas WHERE id_marca = a.id_referencia)
                            WHEN a.tipo_referencia = 'producto' THEN (SELECT serie FROM productos WHERE id_producto = a.id_referencia)
                            ELSE 'Desconocido'
                        END as referencia_nombre,
                        CASE 
                            WHEN a.tipo_referencia = 'tipo_maquinaria' THEN 'Tipo de Maquinaria'
                            WHEN a.tipo_referencia = 'marca' THEN 'Marca'
                            WHEN a.tipo_referencia = 'producto' THEN 'Producto'
                            ELSE 'Desconocido'
                        END as tipo_referencia_nombre
                        FROM archivos_pdf a
                        ORDER BY a.fecha_creacion DESC");
    $archivos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $status_message = "Error al consultar archivos: " . $e->getMessage();
    $status_type = "danger";
    $archivos = array();
}

// Incluir header
include 'includes/header.php';
?>

<!-- Contenido principal -->
<div class="container-fluid py-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-file-pdf me-2"></i>Gestión de Archivos PDF
        </h1>
        <a href="archivos_upload.php" class="btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-upload fa-sm me-2"></i>Subir Archivo PDF
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
            <h6 class="m-0 font-weight-bold text-primary">Listado de Archivos PDF</h6>
        </div>
        <div class="card-body">
            <?php if (count($archivos) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Tipo de Referencia</th>
                                <th>Pertenece a</th>
                                <th>Fecha de Creación</th>
                                <th>Archivo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($archivos as $archivo): ?>
                                <tr>
                                    <td><?php echo $archivo['id_archivo']; ?></td>
                                    <td><?php echo htmlspecialchars($archivo['nombre_archivo']); ?></td>
                                    <td><?php echo htmlspecialchars($archivo['tipo_referencia_nombre']); ?></td>
                                    <td><?php echo htmlspecialchars($archivo['referencia_nombre']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($archivo['fecha_creacion'])); ?></td>
                                    <td>
                                        <a href="../<?php echo $archivo['ruta_archivo']; ?>" target="_blank" class="btn btn-sm btn-danger">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                    </td>
                                    <td>
                                        <a href="#" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $archivo['id_archivo']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        
                                        <!-- Modal de confirmación de eliminación -->
                                        <div class="modal fade" id="deleteModal<?php echo $archivo['id_archivo']; ?>" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Confirmar Eliminación</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>¿Está seguro de que desea eliminar el archivo PDF <strong><?php echo htmlspecialchars($archivo['nombre_archivo']); ?></strong>?</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <a href="archivos.php?action=delete&id=<?php echo $archivo['id_archivo']; ?>" class="btn btn-danger">Eliminar</a>
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
                    <i class="fas fa-info-circle me-2"></i>No hay archivos PDF registrados.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Incluir footer
include 'includes/footer.php';
?>