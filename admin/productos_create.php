<?php
// admin/productos_create.php - Crear nuevo producto

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
$serie = '';
$id_marca = '';
$pdf_file = null;
$status_message = '';
$status_type = '';

// Obtener marcas para el selector
try {
    $stmt_marcas = $cn->query("SELECT m.*, t.nombre as tipo_nombre, s.nombre as subcategoria_nombre 
                             FROM marcas m 
                             JOIN tipo_maquinaria t ON m.id_tipo_maquinaria = t.id_tipo_maquinaria 
                             JOIN subcategorias s ON t.id_subcategoria = s.id_subcategoria 
                             ORDER BY t.nombre, m.nombre");
    $marcas = $stmt_marcas->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $status_message = "Error al obtener marcas: " . $e->getMessage();
    $status_type = "danger";
    $marcas = array();
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $serie = isset($_POST['serie']) ? trim($_POST['serie']) : '';
    $id_marca = isset($_POST['id_marca']) ? trim($_POST['id_marca']) : '';
    
    // Validar datos
    $errors = array();
    
    if (empty($serie)) {
        $errors[] = "La serie del producto es obligatoria";
    }
    
    if (empty($id_marca)) {
        $errors[] = "Debe seleccionar una marca";
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
            // Verificar si ya existe un producto con esa serie para la misma marca
            $check_stmt = $cn->prepare("SELECT COUNT(*) as total FROM productos WHERE serie = :serie AND id_marca = :id_marca");
            $check_stmt->bindParam(':serie', $serie, PDO::PARAM_STR);
            $check_stmt->bindParam(':id_marca', $id_marca, PDO::PARAM_INT);
            $check_stmt->execute();
            $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['total'] > 0) {
                $status_message = "Ya existe un producto con esa serie para la marca seleccionada.";
                $status_type = "danger";
                
                // Eliminar el archivo PDF subido si existía error
                if (!empty($pdf_ruta) && file_exists('../' . $pdf_ruta)) {
                    unlink('../' . $pdf_ruta);
                }
            } else {
                // Insertar nuevo producto
                $stmt = $cn->prepare("INSERT INTO productos (serie, id_marca, pdf_ruta) VALUES (:serie, :id_marca, :pdf_ruta)");
                $stmt->bindParam(':serie', $serie, PDO::PARAM_STR);
                $stmt->bindParam(':id_marca', $id_marca, PDO::PARAM_INT);
                $stmt->bindParam(':pdf_ruta', $pdf_ruta, PDO::PARAM_STR);
                $stmt->execute();
                
                $status_message = "Producto creado correctamente.";
                $status_type = "success";
                
                // Limpiar el formulario
                $serie = '';
                $id_marca = '';
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
            <i class="fas fa-box me-2"></i>Nuevo Producto
        </h1>
        <a href="productos.php" class="btn btn-sm btn-secondary shadow-sm">
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
            <h6 class="m-0 font-weight-bold text-primary">Datos del producto</h6>
        </div>
        <div class="card-body">
            <form action="" method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="serie" class="form-label">Serie del producto</label>
                    <input type="text" class="form-control" id="serie" name="serie" value="<?php echo htmlspecialchars($serie); ?>" required>
                    <div class="form-text">Ingrese la serie o modelo del producto.</div>
                </div>
                
                <div class="mb-3">
                    <label for="id_marca" class="form-label">Marca</label>
                    <select class="form-select" id="id_marca" name="id_marca" required>
                        <option value="">Seleccione una marca</option>
                        <?php foreach($marcas as $marca): ?>
                            <option value="<?php echo $marca['id_marca']; ?>" <?php echo ($id_marca == $marca['id_marca']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($marca['nombre'] . ' (' . $marca['tipo_nombre'] . ' - ' . $marca['subcategoria_nombre'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Seleccione la marca a la que pertenece este producto.</div>
                </div>
                
                <div class="mb-3">
                    <label for="pdf_file" class="form-label">Archivo PDF (Opcional)</label>
                    <input type="file" class="form-control" id="pdf_file" name="pdf_file" accept=".pdf">
                    <div class="form-text">Puede adjuntar un catálogo en formato PDF para este producto.</div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="productos.php" class="btn btn-secondary me-md-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Producto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Incluir footer
include 'includes/footer.php';
?>