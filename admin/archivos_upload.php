<?php
// admin/archivos_upload.php - Subir nuevos archivos PDF

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
$nombre_archivo = '';
$descripcion = '';
$tipo_referencia = '';
$id_referencia = '';
$status_message = '';
$status_type = '';

// Obtener tipos de maquinaria para el selector
try {
    $stmt_tipos = $cn->query("SELECT * FROM tipo_maquinaria ORDER BY nombre");
    $tipos_maquinaria = $stmt_tipos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $tipos_maquinaria = array();
}

// Obtener marcas para el selector
try {
    $stmt_marcas = $cn->query("SELECT m.*, t.nombre as tipo_nombre FROM marcas m 
                              JOIN tipo_maquinaria t ON m.id_tipo_maquinaria = t.id_tipo_maquinaria 
                              ORDER BY m.nombre");
    $marcas = $stmt_marcas->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $marcas = array();
}

// Obtener productos para el selector
try {
    $stmt_productos = $cn->query("SELECT p.*, m.nombre as marca_nombre FROM productos p 
                                JOIN marcas m ON p.id_marca = m.id_marca 
                                ORDER BY p.serie");
    $productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $productos = array();
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $nombre_archivo = isset($_POST['nombre_archivo']) ? trim($_POST['nombre_archivo']) : '';
    $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
    $tipo_referencia = isset($_POST['tipo_referencia']) ? trim($_POST['tipo_referencia']) : '';
    $id_referencia = isset($_POST['id_referencia']) ? trim($_POST['id_referencia']) : '';
    
    // Validar datos
    $errors = array();
    
    if (empty($nombre_archivo)) {
        $errors[] = "El nombre del archivo es obligatorio";
    }
    
    if (empty($tipo_referencia)) {
        $errors[] = "Debe seleccionar el tipo de referencia";
    }
    
    if (empty($id_referencia)) {
        $errors[] = "Debe seleccionar el elemento al que pertenece el PDF";
    }
    
    // Validar archivo subido
    if (!isset($_FILES['archivo_pdf']) || $_FILES['archivo_pdf']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Debe subir un archivo PDF válido";
    } else {
        $file_info = pathinfo($_FILES['archivo_pdf']['name']);
        $file_extension = strtolower($file_info['extension']);
        
        // Verificar que sea un PDF
        if ($file_extension !== 'pdf') {
            $errors[] = "El archivo debe ser un PDF";
        }
    }
    
    // Si no hay errores, guardar en la base de datos y subir archivo
    if (empty($errors)) {
        try {
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
            if (move_uploaded_file($_FILES['archivo_pdf']['tmp_name'], $upload_path)) {
                // Guardar información en la base de datos
                $ruta_archivo = 'uploads/pdf/' . $unique_filename; // Ruta relativa para la base de datos
                
                $stmt = $cn->prepare("INSERT INTO archivos_pdf (nombre_archivo, ruta_archivo, descripcion, tipo_referencia, id_referencia) 
                                    VALUES (:nombre_archivo, :ruta_archivo, :descripcion, :tipo_referencia, :id_referencia)");
                
                $stmt->bindParam(':nombre_archivo', $nombre_archivo, PDO::PARAM_STR);
                $stmt->bindParam(':ruta_archivo', $ruta_archivo, PDO::PARAM_STR);
                $stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
                $stmt->bindParam(':tipo_referencia', $tipo_referencia, PDO::PARAM_STR);
                $stmt->bindParam(':id_referencia', $id_referencia, PDO::PARAM_INT);
                
                $stmt->execute();
                
                // También actualizar la tabla correspondiente para asignar el PDF principal si se seleccionó
                if (isset($_POST['es_principal']) && $_POST['es_principal'] == 1) {
                    switch ($tipo_referencia) {
                        case 'tipo_maquinaria':
                            $update_stmt = $cn->prepare("UPDATE tipo_maquinaria SET pdf_ruta = :ruta WHERE id_tipo_maquinaria = :id");
                            $update_stmt->bindParam(':ruta', $ruta_archivo, PDO::PARAM_STR);
                            $update_stmt->bindParam(':id', $id_referencia, PDO::PARAM_INT);
                            $update_stmt->execute();
                            break;
                            
                        case 'marca':
                            $update_stmt = $cn->prepare("UPDATE marcas SET pdf_ruta = :ruta WHERE id_marca = :id");
                            $update_stmt->bindParam(':ruta', $ruta_archivo, PDO::PARAM_STR);
                            $update_stmt->bindParam(':id', $id_referencia, PDO::PARAM_INT);
                            $update_stmt->execute();
                            break;
                            
                        case 'producto':
                            $update_stmt = $cn->prepare("UPDATE productos SET pdf_ruta = :ruta WHERE id_producto = :id");
                            $update_stmt->bindParam(':ruta', $ruta_archivo, PDO::PARAM_STR);
                            $update_stmt->bindParam(':id', $id_referencia, PDO::PARAM_INT);
                            $update_stmt->execute();
                            break;
                    }
                }
                
                $status_message = "Archivo PDF subido y registrado correctamente.";
                $status_type = "success";
                
                // Limpiar el formulario
                $nombre_archivo = '';
                $descripcion = '';
                $tipo_referencia = '';
                $id_referencia = '';
            } else {
                $status_message = "Error al subir el archivo.";
                $status_type = "danger";
            }
        } catch (PDOException $e) {
            $status_message = "Error al guardar: " . $e->getMessage();
            $status_type = "danger";
        }
    } else {
        $status_message = implode("<br>", $errors);
        $status_type = "danger";
    }
}

// Incluir header
include 'includes/header.php';
?>

<!-- Contenido principal -->
<div class="container-fluid py-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-file-pdf me-2"></i>Subir Archivo PDF
        </h1>
        <a href="archivos.php" class="btn btn-sm btn-secondary shadow-sm">
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
            <h6 class="m-0 font-weight-bold text-primary">Datos del archivo PDF</h6>
        </div>
        <div class="card-body">
            <form action="" method="post" enctype="multipart/form-data">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="nombre_archivo" class="form-label">Nombre del archivo</label>
                        <input type="text" class="form-control" id="nombre_archivo" name="nombre_archivo" value="<?php echo htmlspecialchars($nombre_archivo); ?>" required>
                        <div class="form-text">Nombre descriptivo para identificar el PDF.</div>
                    </div>
                    <div class="col-md-6">
                        <label for="archivo_pdf" class="form-label">Archivo PDF</label>
                        <input type="file" class="form-control" id="archivo_pdf" name="archivo_pdf" accept=".pdf" required>
                        <div class="form-text">Seleccione un archivo PDF (máx. 10MB).</div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="descripcion" class="form-label">Descripción</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo htmlspecialchars($descripcion); ?></textarea>
                    <div class="form-text">Describa brevemente el contenido del PDF.</div>
                </div>
                
                <div class="mb-3">
                    <label for="tipo_referencia" class="form-label">Tipo de referencia</label>
                    <select class="form-select" id="tipo_referencia" name="tipo_referencia" required>
                        <option value="" <?php echo empty($tipo_referencia) ? 'selected' : ''; ?>>Seleccione tipo...</option>
                        <option value="tipo_maquinaria" <?php echo $tipo_referencia === 'tipo_maquinaria' ? 'selected' : ''; ?>>Tipo de Maquinaria</option>
                        <option value="marca" <?php echo $tipo_referencia === 'marca' ? 'selected' : ''; ?>>Marca</option>
                        <option value="producto" <?php echo $tipo_referencia === 'producto' ? 'selected' : ''; ?>>Producto/Serie</option>
                    </select>
                </div>
                
                <!-- Selección dinámica basada en el tipo de referencia -->
                <div class="mb-3" id="referencia_tipo_maquinaria" style="display: none;">
                    <label for="id_tipo_maquinaria" class="form-label">Tipo de Maquinaria</label>
                    <select class="form-select" id="id_tipo_maquinaria" name="id_referencia_tipo">
                        <option value="">Seleccione tipo de maquinaria...</option>
                        <?php foreach ($tipos_maquinaria as $tipo): ?>
                            <option value="<?php echo $tipo['id_tipo_maquinaria']; ?>">
                                <?php echo htmlspecialchars($tipo['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3" id="referencia_marca" style="display: none;">
                    <label for="id_marca" class="form-label">Marca</label>
                    <select class="form-select" id="id_marca" name="id_referencia_marca">
                        <option value="">Seleccione marca...</option>
                        <?php foreach ($marcas as $marca): ?>
                            <option value="<?php echo $marca['id_marca']; ?>">
                                <?php echo htmlspecialchars($marca['nombre']) . ' (' . htmlspecialchars($marca['tipo_nombre']) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3" id="referencia_producto" style="display: none;">
                    <label for="id_producto" class="form-label">Producto/Serie</label>
                    <select class="form-select" id="id_producto" name="id_referencia_producto">
                        <option value="">Seleccione producto...</option>
                        <?php foreach ($productos as $producto): ?>
                            <option value="<?php echo $producto['id_producto']; ?>">
                                <?php echo htmlspecialchars($producto['serie']) . ' (' . htmlspecialchars($producto['marca_nombre']) . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Campo oculto para el ID de referencia final -->
                <input type="hidden" id="id_referencia" name="id_referencia" value="<?php echo htmlspecialchars($id_referencia); ?>">
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="es_principal" name="es_principal" value="1">
                    <label class="form-check-label" for="es_principal">Establecer como PDF principal</label>
                    <div class="form-text">Si marca esta opción, este PDF se mostrará como el principal para el elemento seleccionado.</div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="archivos.php" class="btn btn-secondary me-md-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Subir Archivo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript para manejar la selección dinámica -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tipoReferenciaSelect = document.getElementById('tipo_referencia');
    const refTipoDiv = document.getElementById('referencia_tipo_maquinaria');
    const refMarcaDiv = document.getElementById('referencia_marca');
    const refProductoDiv = document.getElementById('referencia_producto');
    const idReferenciaInput = document.getElementById('id_referencia');
    
    const idTipoSelect = document.getElementById('id_tipo_maquinaria');
    const idMarcaSelect = document.getElementById('id_marca');
    const idProductoSelect = document.getElementById('id_producto');
    
    // Función para mostrar el selector adecuado
    function mostrarSelectorReferencia() {
        const tipoSeleccionado = tipoReferenciaSelect.value;
        
        // Ocultar todos los selectores
        refTipoDiv.style.display = 'none';
        refMarcaDiv.style.display = 'none';
        refProductoDiv.style.display = 'none';
        
        // Mostrar el selector correspondiente
        if (tipoSeleccionado === 'tipo_maquinaria') {
            refTipoDiv.style.display = 'block';
        } else if (tipoSeleccionado === 'marca') {
            refMarcaDiv.style.display = 'block';
        } else if (tipoSeleccionado === 'producto') {
            refProductoDiv.style.display = 'block';
        }
    }
    
    // Función para actualizar el campo oculto de id_referencia
    function actualizarIdReferencia() {
        const tipoSeleccionado = tipoReferenciaSelect.value;
        
        if (tipoSeleccionado === 'tipo_maquinaria') {
            idReferenciaInput.value = idTipoSelect.value;
        } else if (tipoSeleccionado === 'marca') {
            idReferenciaInput.value = idMarcaSelect.value;
        } else if (tipoSeleccionado === 'producto') {
            idReferenciaInput.value = idProductoSelect.value;
        } else {
            idReferenciaInput.value = '';
        }
    }
    
    // Asignar eventos
    tipoReferenciaSelect.addEventListener('change', function() {
        mostrarSelectorReferencia();
        actualizarIdReferencia();
    });
    
    idTipoSelect.addEventListener('change', actualizarIdReferencia);
    idMarcaSelect.addEventListener('change', actualizarIdReferencia);
    idProductoSelect.addEventListener('change', actualizarIdReferencia);
    
    // Inicializar
    mostrarSelectorReferencia();
    actualizarIdReferencia();
});
</script>

<?php
// Incluir footer
include 'includes/footer.php';
?>