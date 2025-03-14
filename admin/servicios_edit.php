<?php
// admin/servicios_edit.php - Editar servicio existente

// Iniciar sesión
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit;
}

// Incluir archivo de conexión
require_once 'conexion.php';

// Variables para el formulario
$id_servicio = 0;
$nombre = '';
$id_subcategoria = '';
$descripcion = '';
$pdf_ruta_actual = '';
$status_message = '';
$status_type = '';

// Verificar ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: servicios.php');
    exit;
}

$id_servicio = filter_var($_GET['id'], FILTER_VALIDATE_INT);

// Obtener subcategorías de servicios para el selector
try {
    $subcategoria_stmt = $cn->query("SELECT s.id_subcategoria, s.nombre, c.nombre as categoria_nombre 
                                    FROM subcategorias s 
                                    JOIN categorias c ON s.id_categoria = c.id_categoria 
                                    WHERE c.nombre = 'SERVICIOS'");
    $subcategorias_servicios = $subcategoria_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $status_message = "Error al obtener subcategorías: " . $e->getMessage();
    $status_type = "danger";
    $subcategorias_servicios = array();
}

// Obtener datos del servicio
try {
    $stmt = $cn->prepare("SELECT t.* FROM tipo_maquinaria t WHERE t.id_tipo_maquinaria = :id");
    $stmt->bindParam(':id', $id_servicio, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        // Servicio no existe
        header('Location: servicios.php');
        exit;
    }
    
    $servicio = $stmt->fetch(PDO::FETCH_ASSOC);
    $nombre = $servicio['nombre'];
    $id_subcategoria = $servicio['id_subcategoria'];
    $pdf_ruta_actual = $servicio['pdf_ruta'];
    
    // Obtener descripción si existe
    $desc_stmt = $cn->prepare("SELECT descripcion FROM archivos_pdf WHERE tipo_referencia = 'tipo_maquinaria' AND id_referencia = :id LIMIT 1");
    $desc_stmt->bindParam(':id', $id_servicio, PDO::PARAM_INT);
    $desc_stmt->execute();
    
    if ($desc_stmt->rowCount() > 0) {
        $desc_result = $desc_stmt->fetch(PDO::FETCH_ASSOC);
        $descripcion = $desc_result['descripcion'];
    }
} catch (PDOException $e) {
    $status_message = "Error al obtener datos: " . $e->getMessage();
    $status_type = "danger";
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $id_subcategoria = isset($_POST['id_subcategoria']) ? trim($_POST['id_subcategoria']) : '';
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
    $mantener_pdf = isset($_POST['mantener_pdf']) ? true : false;
    
    // Validar datos
    $errors = array();
    
    if (empty($nombre)) {
        $errors[] = "El nombre del servicio es obligatorio";
    }
    
    if (empty($id_subcategoria)) {
        $errors[] = "Debe seleccionar una subcategoría";
    }
    
    // Manejar el archivo PDF
    $nueva_pdf_ruta = $mantener_pdf ? $pdf_ruta_actual : null;
    
    // Si se ha subido un nuevo archivo PDF
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
        $file_info = pathinfo($_FILES['pdf_file']['name']);
        $file_extension = strtolower($file_info['extension']);
        
        // Verificar que sea un PDF
        if ($file_extension !== 'pdf') {
            $errors[] = "El archivo debe ser un PDF";
        } else {
            // Crear directorio de uploads si no existe
            $upload_dir = '../uploads/pdf/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generar nombre único para el archivo
            $timestamp = time();
            $unique_filename = $timestamp . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '_', $file_info['filename'])) . '.pdf';
            $upload_path = $upload_dir . $unique_filename;
            
            // Mover archivo subido
            if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $upload_path)) {
                $nueva_pdf_ruta = 'uploads/pdf/' . $unique_filename; // Ruta relativa para la base de datos
                
                // Eliminar PDF anterior si existe y no es el mismo
                if (!empty($pdf_ruta_actual) && file_exists('../' . $pdf_ruta_actual) && $nueva_pdf_ruta !== $pdf_ruta_actual) {
                    unlink('../' . $pdf_ruta_actual);
                }
            } else {
                $errors[] = "Error al subir el archivo PDF";
            }
        }
    } elseif (!$mantener_pdf && !empty($pdf_ruta_actual)) {
        // Si se ha indicado eliminar el PDF actual
        if (file_exists('../' . $pdf_ruta_actual)) {
            unlink('../' . $pdf_ruta_actual);
        }
        $nueva_pdf_ruta = null;
    }
    
    // Si no hay errores, actualizar en la base de datos
    if (empty($errors)) {
        try {
            // Verificar si ya existe otro servicio con ese nombre en la misma subcategoría
            $check_stmt = $cn->prepare("SELECT COUNT(*) as total FROM tipo_maquinaria WHERE nombre = :nombre AND id_subcategoria = :id_subcategoria AND id_tipo_maquinaria != :id");
            $check_stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $check_stmt->bindParam(':id_subcategoria', $id_subcategoria, PDO::PARAM_INT);
            $check_stmt->bindParam(':id', $id_servicio, PDO::PARAM_INT);
            $check_stmt->execute();
            $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['total'] > 0) {
                $status_message = "Ya existe otro servicio con ese nombre en la subcategoría seleccionada.";
                $status_type = "danger";
                
                // Eliminar el nuevo archivo PDF subido si hubo error
                if ($nueva_pdf_ruta !== $pdf_ruta_actual && !empty($nueva_pdf_ruta) && file_exists('../' . $nueva_pdf_ruta)) {
                    unlink('../' . $nueva_pdf_ruta);
                }
                
                // Restaurar la ruta del PDF actual
                $nueva_pdf_ruta = $pdf_ruta_actual;
            } else {
                // Actualizar servicio
                $stmt = $cn->prepare("UPDATE tipo_maquinaria SET nombre = :nombre, id_subcategoria = :id_subcategoria, pdf_ruta = :pdf_ruta WHERE id_tipo_maquinaria = :id");
                $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
                $stmt->bindParam(':id_subcategoria', $id_subcategoria, PDO::PARAM_INT);
                $stmt->bindParam(':pdf_ruta', $nueva_pdf_ruta, PDO::PARAM_STR);
                $stmt->bindParam(':id', $id_servicio, PDO::PARAM_INT);
                $stmt->execute();
                
                // Actualizar o crear descripción
                if (!empty($descripcion)) {
                    // Verificar si ya existe una descripción
                    $check_desc_stmt = $cn->prepare("SELECT COUNT(*) as total FROM archivos_pdf WHERE tipo_referencia = 'tipo_maquinaria' AND id_referencia = :id");
                    $check_desc_stmt->bindParam(':id', $id_servicio, PDO::PARAM_INT);
                    $check_desc_stmt->execute();
                    $desc_exists = $check_desc_stmt->fetch(PDO::FETCH_ASSOC)['total'] > 0;
                    
                    if ($desc_exists) {
                        // Actualizar descripción existente
                        $update_desc_stmt = $cn->prepare("UPDATE archivos_pdf SET descripcion = :descripcion WHERE tipo_referencia = 'tipo_maquinaria' AND id_referencia = :id");
                        $update_desc_stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
                        $update_desc_stmt->bindParam(':id', $id_servicio, PDO::PARAM_INT);
                        $update_desc_stmt->execute();
                    } else if (!empty($nueva_pdf_ruta)) {
                        // Crear nueva entrada de descripción
                        $insert_desc_stmt = $cn->prepare("INSERT INTO archivos_pdf (nombre_archivo, ruta_archivo, descripcion, tipo_referencia, id_referencia) 
                                                       VALUES (:nombre_archivo, :ruta_archivo, :descripcion, 'tipo_maquinaria', :id_referencia)");
                        $insert_desc_stmt->bindParam(':nombre_archivo', $nombre, PDO::PARAM_STR);
                        $insert_desc_stmt->bindParam(':ruta_archivo', $nueva_pdf_ruta, PDO::PARAM_STR);
                        $insert_desc_stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
                        $insert_desc_stmt->bindParam(':id_referencia', $id_servicio, PDO::PARAM_INT);
                        $insert_desc_stmt->execute();
                    }
                }
                
                $status_message = "Servicio actualizado correctamente.";
                $status_type = "success";
                
                // Actualizar la ruta del PDF actual
                $pdf_ruta_actual = $nueva_pdf_ruta;
            }
        } catch (PDOException $e) {
            $status_message = "Error al actualizar: " . $e->getMessage();
            $status_type = "danger";
            
            // Eliminar el nuevo archivo PDF subido si hubo error
            if ($nueva_pdf_ruta !== $pdf_ruta_actual && !empty($nueva_pdf_ruta) && file_exists('../' . $nueva_pdf_ruta)) {
                unlink('../' . $nueva_pdf_ruta);
            }
            
            // Restaurar la ruta del PDF actual
            $nueva_pdf_ruta = $pdf_ruta_actual;
        }
    } else {
        $status_message = implode("<br>", $errors);
        $status_type = "danger";
        
        // Eliminar el nuevo archivo PDF subido si hubo error
        if ($nueva_pdf_ruta !== $pdf_ruta_actual && !empty($nueva_pdf_ruta) && file_exists('../' . $nueva_pdf_ruta)) {
            unlink('../' . $nueva_pdf_ruta);
        }
        
        // Restaurar la ruta del PDF actual
        $nueva_pdf_ruta = $pdf_ruta_actual;
    }
}

// Incluir header
include 'includes/header.php';
?>

<!-- Contenido principal -->
<div class="container-fluid py-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-edit me-2"></i>Editar Servicio
        </h1>
        <a href="servicios.php" class="btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm me-2"></i>Volver al listado
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
            <h6 class="m-0 font-weight-bold text-primary">Datos del servicio</h6>
        </div>
        <div class="card-body">
            <form action="" method="post" enctype="multipart/form-data">
                <input type="hidden" name="id_servicio" value="<?php echo $id_servicio; ?>">
                
                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre del servicio</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>" required>
                    <div class="form-text">Ingrese un nombre descriptivo para el servicio.</div>
                </div>
                
                <div class="mb-3">
                    <label for="id_subcategoria" class="form-label">Subcategoría</label>
                    <select class="form-select" id="id_subcategoria" name="id_subcategoria" required>
                        <option value="">Seleccione una subcategoría</option>
                        <?php foreach($subcategorias_servicios as $subcategoria): ?>
                            <option value="<?php echo $subcategoria['id_subcategoria']; ?>" <?php echo ($id_subcategoria == $subcategoria['id_subcategoria']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subcategoria['nombre'] . ' (' . $subcategoria['categoria_nombre'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Seleccione la subcategoría a la que pertenece este servicio.</div>
                </div>
                
                <div class="mb-3">
                    <label for="descripcion" class="form-label">Descripción del servicio</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="4"><?php echo htmlspecialchars($descripcion); ?></textarea>
                    <div class="form-text">Describa brevemente en qué consiste este servicio.</div>
                </div>
                
                <div class="mb-3">
                    <label for="pdf_file" class="form-label">Archivo PDF (Opcional)</label>
                    <?php if (!empty($pdf_ruta_actual)): ?>
                        <div class="mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="mantener_pdf" id="mantener_pdf" value="1" checked>
                                <label class="form-check-label" for="mantener_pdf">
                                    Mantener PDF actual
                                </label>
                            </div>
                            <a href="../<?php echo $pdf_ruta_actual; ?>" target="_blank" class="btn btn-sm btn-danger">
                                <i class="fas fa-file-pdf me-1"></i>Ver PDF actual
                            </a>
                        </div>
                    <?php endif; ?>
                    <input type="file" class="form-control" id="pdf_file" name="pdf_file" accept=".pdf">
                    <div class="form-text">Puede adjuntar información detallada en formato PDF para este servicio.</div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="servicios.php" class="btn btn-secondary me-md-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Actualizar Servicio</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Incluir footer
include 'includes/footer.php';
?>