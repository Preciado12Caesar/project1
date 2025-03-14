<?php
// admin/categorias_create.php - Crear nueva categoría

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
$status_message = '';
$status_type = '';

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    
    // Validar datos
    $errors = array();
    
    if (empty($nombre)) {
        $errors[] = "El nombre de la categoría es obligatorio";
    }
    
    // Si no hay errores, guardar en la base de datos
    if (empty($errors)) {
        try {
            // Verificar si ya existe una categoría con ese nombre
            $check_stmt = $cn->prepare("SELECT COUNT(*) as total FROM categorias WHERE nombre = :nombre");
            $check_stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $check_stmt->execute();
            $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['total'] > 0) {
                $status_message = "Ya existe una categoría con ese nombre.";
                $status_type = "danger";
            } else {
                // Insertar nueva categoría
                $stmt = $cn->prepare("INSERT INTO categorias (nombre) VALUES (:nombre)");
                $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
                $stmt->execute();
                
                $status_message = "Categoría creada correctamente.";
                $status_type = "success";
                
                // Limpiar el formulario
                $nombre = '';
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
            <i class="fas fa-folder-plus me-2"></i>Nueva Categoría
        </h1>
        <a href="categorias.php" class="btn btn-sm btn-secondary shadow-sm">
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
            <h6 class="m-0 font-weight-bold text-primary">Datos de la categoría</h6>
        </div>
        <div class="card-body">
            <form action="" method="post">
                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre de la categoría</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>" required>
                    <div class="form-text">Ingrese un nombre descriptivo para la categoría.</div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="categorias.php" class="btn btn-secondary me-md-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Guardar Categoría</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Incluir footer
include 'includes/footer.php';
?>