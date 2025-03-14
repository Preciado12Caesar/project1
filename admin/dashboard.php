<?php
// admin/dashboard.php - Panel principal de administración

// Iniciar sesión
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location:login.php');
    exit;
}

// Incluir archivo de conexión
require_once 'conexion.php';

// Función para obtener estadísticas
function getEstadisticas($cn) {
    $stats = array();
    
    try {
        // Total de categorías
        $stmt = $cn->query("SELECT COUNT(*) as total FROM categorias");
        $stats['categorias'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total de subcategorías
        $stmt = $cn->query("SELECT COUNT(*) as total FROM subcategorias");
        $stats['subcategorias'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total de tipos de maquinaria
        $stmt = $cn->query("SELECT COUNT(*) as total FROM tipo_maquinaria");
        $stats['tipos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total de marcas
        $stmt = $cn->query("SELECT COUNT(*) as total FROM marcas");
        $stats['marcas'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total de productos
        $stmt = $cn->query("SELECT COUNT(*) as total FROM productos");
        $stats['productos'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total de PDFs
        $stmt = $cn->query("SELECT COUNT(*) as total FROM archivos_pdf");
        $stats['pdfs'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        return $stats;
    } catch (PDOException $e) {
        return array(
            'categorias' => 0,
            'subcategorias' => 0,
            'tipos' => 0,
            'marcas' => 0,
            'productos' => 0,
            'pdfs' => 0
        );
    }
}

// Obtener estadísticas
$estadisticas = getEstadisticas($cn);

// Incluir header
include 'includes/header.php';
?>

<!-- Contenido principal -->
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-tachometer-alt me-2"></i>Panel de Administración
            </h1>
            <p class="mb-0">Bienvenido, <?php echo htmlspecialchars($_SESSION['admin_nombre']); ?></p>
        </div>
    </div>
    
    <!-- Tarjetas de estadísticas -->
    <div class="row">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Categorías</div>
                           
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-folder fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <a href="categorias.php" class="btn btn-sm btn-outline-primary">Administrar</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Subcategorías</div>
                           
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-folder-open fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <a href="subcategorias.php" class="btn btn-sm btn-outline-success">Administrar</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Tipos de Maquinaria</div>
                           
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-truck fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <a href="tipos.php" class="btn btn-sm btn-outline-info">Administrar</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Marcas</div>
                           
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-trademark fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <a href="marcas.php" class="btn btn-sm btn-outline-warning">Administrar</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Productos</div>
                      
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-box fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <a href="productos.php" class="btn btn-sm btn-outline-danger">Administrar</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-secondary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                Archivos PDF</div>
                   
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-pdf fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <a href="archivos.php" class="btn btn-sm btn-outline-secondary">Administrar</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Accesos rápidos -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Acciones Rápidas</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <a href="productos_create.php" class="btn btn-primary btn-block">
                                <i class="fas fa-plus-circle me-2"></i>Nuevo Producto
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="marcas_create.php" class="btn btn-warning btn-block">
                                <i class="fas fa-plus-circle me-2"></i>Nueva Marca
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="tipos_create.php" class="btn btn-info btn-block">
                                <i class="fas fa-plus-circle me-2"></i>Nuevo Tipo
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="archivos_upload.php" class="btn btn-secondary btn-block">
                                <i class="fas fa-upload me-2"></i>Subir PDF
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Enlaces Útiles</h6>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <a href="../index.php" target="_blank" class="text-decoration-none">
                                <i class="fas fa-external-link-alt me-2"></i>Ver sitio web
                            </a>
                            <span class="badge bg-primary rounded-pill">
                                <i class="fas fa-arrow-right"></i>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <a href="usuarios.php" class="text-decoration-none">
                                <i class="fas fa-users-cog me-2"></i>Administrar usuarios
                            </a>
                            <span class="badge bg-primary rounded-pill">
                                <i class="fas fa-arrow-right"></i>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <a href="logout.php" class="text-decoration-none text-danger">
                                <i class="fas fa-sign-out-alt me-2"></i>Cerrar sesión
                            </a>
                            <span class="badge bg-danger rounded-pill">
                                <i class="fas fa-arrow-right"></i>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Incluir footer
include 'includes/footer.php';
?>