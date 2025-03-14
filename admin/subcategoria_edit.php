<?php
// admin/subcategorias_edit.php - Editar subcategoría existente

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
$id_subcategoria = 0;
$nombre = '';
$id_categoria = '';
$status_message = '';
$status_type = '';

// Verificar ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: subcategorias.php');
    exit;
}

$id_subcategoria = filter_var($_GET['id'], FILTER_VALIDATE_INT);

// Obtener categorías para el selector
try {
    $stmt_categorias = $cn->query("SELECT * FROM categorias ORDER BY nombre");
    $categorias = $stmt_categorias->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $status_message = "Error al obtener categorías: " . $e->getMessage();
    $status_type = "danger";
    $categorias = array();
}

// Obtener datos de la subcategoría
try {
    $stmt = $cn->prepare("SELECT * FROM subcategorias WHERE id_subcategoria = :id");
    $stmt->bindParam(':id', $id_subcategoria, PDO::PARAM_INT);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        // Subcategoría no existe
        header('Location: subcategorias.php');
        exit;
    }
    
    $subcategoria = $stmt->fetch(PDO::FETCH_ASSOC);
    $nombre = $subcategoria['nombre'];
    $id_categoria = $subcategoria['id_categoria'];
} catch (PDOException $e) {
    $status_message = "Error al obtener datos: " . $e->getMessage();
    $status_type = "danger";
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener datos del formulario
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';
    $id_categoria = isset($_POST['id_categoria']) ? trim($_POST['id_categoria']) : '';
    
    // Validar datos
    $errors = array();
    
    if (empty($nombre)) {
        $errors[] = "El nombre de la subcategoría es obligatorio";
    }
    
    if (empty($id_categoria)) {
        $errors[] = "Debe seleccionar una categoría";
    }
    
    // Si no hay errores, actualizar en la base de datos
    if (empty($errors)) {
        try {
            // Verificar si ya existe otra subcategoría con ese nombre en la misma categoría
            $check_stmt = $cn->prepare("SELECT COUNT(*) as total FROM subcategorias WHERE nombre = :nombre AND id_categoria = :id_categoria AND id_subcategoria != :id");
            $check_stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $check_stmt->bindParam(':id_categoria', $id_categoria, PDO::PARAM_INT);
            $check_stmt->bindParam(':id', $id_subcategoria, PDO::PARAM_INT);
            $check_stmt->execute();
            $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result['total'] > 0) {
                $status_message = "Ya existe otra subcategoría con ese nombre en la categoría seleccionada.";
                $status_type = "danger";
            } else {
                // Actualizar subcategoría
                $stmt = $cn->prepare("UPDATE subcategorias SET nombre = :nombre, id_categoria = :id_categoria WHERE id_subcategoria = :id");
                $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
                $stmt->bindParam(':id_categoria', $id_categoria, PDO::PARAM_INT);
                $stmt->bindParam(':id', $id_subcategoria, PDO::PARAM_INT);
                $stmt->execute();
                
                $status_message = "Subcategoría actualizada correctamente.";
                $status_type = "success";
            }
        } catch (PDOException $e) {
            $status_message = "Error al actualizar: " . $e->getMessage();
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
            <i class="fas fa-edit me-2"></i>Editar Subcategoría
        </h1>
        <a href="subcategorias.php" class="btn btn-sm btn-secondary shadow-sm">
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
            <h6 class="m-0 font-weight-bold text-primary">Datos de la subcategoría</h6>
        </div>
        <div class="card-body">
            <form action="" method="post">
                <input type="hidden" name="id_subcategoria" value="<?php echo $id_subcategoria; ?>">
                
                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre de la subcategoría</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($nombre); ?>" required>
                    <div class="form-text">Ingrese un nombre descriptivo para la subcategoría.</div>
                </div>
                
                <div class="mb-3">
                    <label for="id_categoria" class="form-label">Categoría</label>
                    <select class="form-select" id="id_categoria" name="id_categoria" required>
                        <option value="">Seleccione una categoría</option>
                        <?php foreach($categorias as $categoria): ?>
                            <option value="<?php echo $categoria['id_categoria']; ?>" <?php echo ($id_categoria == $categoria['id_categoria']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($categoria['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Seleccione la categoría a la que pertenece esta subcategoría.</div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="subcategorias.php" class="btn btn-secondary me-md-2">Cancelar</a>
                    <button type="submit" class="btn btn-primary">Actualizar Subcategoría</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Incluir footer
include 'includes/footer.php';
?>