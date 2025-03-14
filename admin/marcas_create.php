<?php
// admin/marcas_create.php - Crear nueva marca

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
$id_tipo_maquinaria = '';
$pdf_file = null;
$status_message = '';
$status_type = '';

// Obtener tipos de maquinaria para el selector
try {
    $stmt_tipos = $cn->query("SELECT t.*, s.nombre as subcategoria_nombre, c.nombre as categoria_nombre 
                            FROM tipo_maquinaria t 
                            JOIN subcategorias s ON t.id_subcategoria = s.id_subcategoria 
                            JOIN categorias c ON s.id_categoria = c.id_categoria 
                            ORDER BY c.nombre, s.nombre, t.nombre");
    $tipos = $stmt_tipos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $status_message = "Error al obtener tipos de maquinaria: " . $e->getMessage();
    $status_type = "danger";
    $tipos = array();
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $id_tipo_maquinaria = isset($_POST['id_tipo_maquinaria']) ? trim($_POST['id_tipo_maquinaria']) : '';
    
    // Validar datos
    $errors = array();
    
    if (empty($nombre)) {
        $errors[] = "El nombre de la marca es obligatorio";
    }
    
    if (empty($id_tipo_maquinaria)) {
        $errors[] = "Debe seleccionar un tipo de maquinaria";
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
            // Verificar si ya existe una marca con ese nombre para el mismo tipo de maquinaria
            $check_stmt = $cn->prepare("SELECT COUNT(*) as total FROM marcas WHERE nombre = :nombre AND id_tipo_maquinaria = :id_tipo_maquinaria");
            $check_stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $check_stmt->bindParam(':id_tipo_maquinaria', $id_tipo_maquinaria, PDO::PARAM_INT);
            $check_stmt->execute();
            $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['total'] > 0) {
                $status_message = "Ya existe una marca con ese nombre para el tipo de maquinaria seleccionado.";
                $status_type = "danger";
                
                // Eliminar el archivo PDF subido si existía error
                if (!empty($pdf_ruta) && file_exists('../' . $pdf_ruta)) {
                    unlink('../' . $pdf_ruta);
                }
            } else {
                // Insertar nueva marca
                $stmt = $cn->prepare("INSERT INTO marcas (nombre, id_tipo_maquinaria, pdf_ruta) VALUES (:nombre, :id_tipo_maquinaria, :pdf_ruta)");
                $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
                $stmt->bindParam(':id_tipo_maquinaria', $id_tipo_maquinaria, PDO::PARAM_INT);
                $stmt->bindParam(':pdf_ruta', $pdf_ruta, PDO::PARAM_STR);
                $stmt->execute();
                
                $status_message = "Marca creada correctamente.";
                $status_type = "success";
                
                // Limpiar el formulario
                $nombre = '';
                $id_tipo_maquinaria = '';
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
            <i class="fas fa-trademark me-2"></i>Nueva Marca
        </h1>
        <a href="marcas.php" class="btn btn-sm btn-secondary shadow-sm">
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
            <h6 class="m-0 font-weight-bold text-primary">Datos de la marca</h6>
        </div>
        <div class="card-body">
            <form action="" method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre de la marca</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>" required>
                    <div class="form-text">Ingrese el nombre de la marca.</div>
                </div>
                
                <div class="mb-3">
                    <label for="id_tipo_maquinaria" class="form-label">Tipo de Maquinaria</label>
                    <select class="form-select" id="id_tipo_maquinaria" name="id_tipo_maquinaria" required>
                        <option value="">Seleccione un tipo de maquinaria</option>
                        <?php foreach($tipos as $tipo): ?>
                            <option value="<?php echo $tipo['id_tipo_maquinaria']; ?>" <?php echo ($id_tipo_maquinaria == $tipo['id_tipo_maquinaria']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($tipo['nombre'] . ' (' . $tipo['subcategoria_nombre'] . ' - ' . $tipo['categoria_nombre'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Seleccione el tipo de maquinaria al que pertenece esta marca.</div>
                </div>
                
                <div class="mb-3">
                    <label for="pdf_file" class="form-label">Archivo PDF (Opcional)</label>
                    <input type="file" class="form-control" id="pdf_file" name="pdf_file" accept=".pdf">
                    <div class="form-text">Puede adjuntar un catálogo en formato PDF para esta marca.</div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="marcas.php" class="btn btn-secondary me-md-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Marca</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Incluir footer
include 'includes/footer.php';
?>