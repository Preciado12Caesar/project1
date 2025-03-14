<?php
// admin/servicios_create.php - Crear nuevo servicio

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
$nombre = '';
$id_subcategoria = '';
$descripcion = '';
$pdf_file = null;
$status_message = '';
$status_type = '';

// Obtener la subcategoría "TREN DE RODAMIENTO" de la categoría "SERVICIOS"
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

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $id_subcategoria = isset($_POST['id_subcategoria']) ? trim($_POST['id_subcategoria']) : '';
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
    
    // Validar datos
    $errors = array();
    
    if (empty($nombre)) {
        $errors[] = "El nombre del servicio es obligatorio";
    }
    
    if (empty($id_subcategoria)) {
        $errors[] = "Debe seleccionar una subcategoría";
    }
    
    // Validar archivo PDF si se ha subido
    $pdf_ruta = null;
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
                $pdf_ruta = 'uploads/pdf/' . $unique_filename; // Ruta relativa para la base de datos
            } else {
                $errors[] = "Error al subir el archivo PDF";
            }
        }
    }
    
    // Si no hay errores, guardar en la base de datos
    if (empty($errors)) {
        try {
            // Verificar si ya existe un servicio con ese nombre en la misma subcategoría
            $check_stmt = $cn->prepare("SELECT COUNT(*) as total FROM tipo_maquinaria WHERE nombre = :nombre AND id_subcategoria = :id_subcategoria");
            $check_stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $check_stmt->bindParam(':id_subcategoria', $id_subcategoria, PDO::PARAM_INT);
            $check_stmt->execute();
            $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['total'] > 0) {
                $status_message = "Ya existe un servicio con ese nombre en la subcategoría seleccionada.";
                $status_type = "danger";
                
                // Eliminar el archivo PDF subido si existía error
                if (!empty($pdf_ruta) && file_exists('../' . $pdf_ruta)) {
                    unlink('../' . $pdf_ruta);
                }
            } else {
                // Insertar nuevo servicio como un tipo de maquinaria
                $stmt = $cn->prepare("INSERT INTO tipo_maquinaria (nombre, id_subcategoria, pdf_ruta) VALUES (:nombre, :id_subcategoria, :pdf_ruta)");
                $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
                $stmt->bindParam(':id_subcategoria', $id_subcategoria, PDO::PARAM_INT);
                $stmt->bindParam(':pdf_ruta', $pdf_ruta, PDO::PARAM_STR);
                $stmt->execute();
                
                $id_servicio = $cn->lastInsertId();
                
                // Si hay descripción, guardarla como metadata
                if (!empty($descripcion) && !empty($pdf_ruta)) {
                    $desc_stmt = $cn->prepare("INSERT INTO archivos_pdf (nombre_archivo, ruta_archivo, descripcion, tipo_referencia, id_referencia) 
                                             VALUES (:nombre_archivo, :ruta_archivo, :descripcion, 'tipo_maquinaria', :id_referencia)");
                    $desc_stmt->bindParam(':nombre_archivo', $nombre, PDO::PARAM_STR);
                    $desc_stmt->bindParam(':ruta_archivo', $pdf_ruta, PDO::PARAM_STR);
                    $desc_stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
                    $desc_stmt->bindParam(':id_referencia', $id_servicio, PDO::PARAM_INT);
                    $desc_stmt->execute();
                }
                
                $status_message = "Servicio creado correctamente.";
                $status_type = "success";
                
                // Limpiar el formulario
                $nombre = '';
                $id_subcategoria = '';
                $descripcion = '';
            }
        } catch (PDOException $e) {
            $status_message = "Error al guardar: " . $e->getMessage();
            $status_type = "danger";
            
            // Eliminar el archivo PDF subido si existía error
            if (!empty($pdf_ruta) && file_exists('../' . $pdf_ruta)) {
                unlink('../' . $pdf_ruta);
            }
        }
    } else {
        $status_message = implode("<br>", $errors);
        $status_type = "danger";
        
        // Eliminar el archivo PDF subido si existía error
        if (!empty($pdf_ruta) && file_exists('../' . $pdf_ruta)) {
            unlink('../' . $pdf_ruta);
        }
    }
}

// Incluir header
include 'includes/header.php';
?>

<!-- Contenido principal -->
<div class="container-fluid py-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-plus-circle me-2"></i>Nuevo Servicio
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
                    <input type="file" class="form-control" id="pdf_file" name="pdf_file" accept=".pdf">
                    <div class="form-text">Puede adjuntar información detallada en formato PDF para este servicio.</div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="servicios.php" class="btn btn-secondary me-md-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Servicio</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Incluir footer
include 'includes/footer.php';
?>