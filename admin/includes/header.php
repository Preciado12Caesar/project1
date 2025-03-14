<?php
// admin/includes/header.php

// No permitir acceso directo a este archivo
if (!defined('SECURE_ACCESS') && !isset($_SESSION['admin_logged_in'])) {
    define('SECURE_ACCESS', true);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Catálogo de Maquinaria</title>
    <link rel="stylesheet" href="../styles/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="icon" type="image/png" href="../recursos/LOGO.svg">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fc;
        }
        .sidebar {
            min-height: 100vh;
            background-color: #4e73df;
            background-image: linear-gradient(180deg, #4e73df 10%, #224abe 100%);
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            z-index: 1;
            padding-top: 1rem;
        }
        .sidebar .nav-item {
            position: relative;
            margin-bottom: 0;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 0.75rem 1rem;
            font-weight: 700;
            font-size: 0.85rem;
        }
        .sidebar .nav-link:hover {
            color: #fff;
        }
        .sidebar .nav-link i {
            margin-right: 0.5rem;
            width: 1.5rem;
            text-align: center;
        }
        .sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
        }
        .sidebar-divider {
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            margin: 0 1rem 1rem;
        }
        .sidebar-heading {
            color: rgba(255, 255, 255, 0.4);
            font-size: 0.75rem;
            font-weight: 800;
            padding: 0 1rem;
            text-transform: uppercase;
            margin-top: 1rem;
        }
        .logo-sidebar {
            padding: 1rem;
            text-align: center;
            margin-bottom: 1rem;
        }
        .logo-sidebar img {
            height: 60px;
        }
        .topbar {
            height: 4.375rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            background-color: #fff;
            margin-bottom: 1.5rem;
        }
        .dropdown-menu {
            font-size: 0.85rem;
        }
        .dropdown-user a {
            font-size: 0.85rem;
        }
        .card {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            border: none;
            border-radius: 0.5rem;
        }
        .card-header {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem;
        }
        .content-container {
            min-height: calc(100vh - 4.375rem);
            padding-bottom: 3rem;
        }
        .footer {
            background-color: #fff;
            height: 3rem;
            padding: 0.8rem 0;
            font-size: 0.8rem;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
        }
        .nav-link.collapsed i.fa-angle-down {
            transform: rotate(-90deg);
        }
        .nav-link i.fa-angle-down {
            transition: transform 0.2s ease-in-out;
        }
        
        /* Estilos para tarjetas de estadísticas en dashboard */
        .border-left-primary {
            border-left: 0.25rem solid #4e73df !important;
        }
        .border-left-success {
            border-left: 0.25rem solid #1cc88a !important;
        }
        .border-left-info {
            border-left: 0.25rem solid #36b9cc !important;
        }
        .border-left-warning {
            border-left: 0.25rem solid #f6c23e !important;
        }
        .border-left-danger {
            border-left: 0.25rem solid #e74a3b !important;
        }
        .border-left-secondary {
            border-left: 0.25rem solid #858796 !important;
        }
        
        /* Hacer que los botones de bloque ocupen todo el ancho */
        .btn-block {
            display: block;
            width: 100%;
        }
        
        /* Ajustes para tablas responsivas */
        .table th {
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <!-- Page Wrapper -->
    <div class="d-flex">
        <!-- Sidebar -->
        <div class="sidebar col-md-3 col-lg-2 d-md-block collapse" id="sidebarMenu">
            <div class="logo-sidebar">
                <img src="../recursos/LOGO.png" alt="Logo" class="img-fluid">
            </div>
            <hr class="sidebar-divider">
            <div class="nav flex-column">
                <div class="sidebar-heading">Principal</div>
                <div class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                        <i class="fas fa-fw fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
                
                <hr class="sidebar-divider">
                <div class="sidebar-heading">Catálogo</div>
                
                <div class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'categorias.php' || basename($_SERVER['PHP_SELF']) == 'categorias_create.php' || basename($_SERVER['PHP_SELF']) == 'categorias_edit.php' ? 'active' : ''; ?>" href="categorias.php">
                        <i class="fas fa-fw fa-folder"></i>
                        <span>Categorías</span>
                    </a>
                </div>
                
                <div class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'subcategorias.php' || basename($_SERVER['PHP_SELF']) == 'subcategorias_create.php' || basename($_SERVER['PHP_SELF']) == 'subcategorias_edit.php' ? 'active' : ''; ?>" href="subcategorias.php">
                        <i class="fas fa-fw fa-folder-open"></i>
                        <span>Subcategorías</span>
                    </a>
                </div>
                
                <div class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'tipos.php' || basename($_SERVER['PHP_SELF']) == 'tipos_create.php' || basename($_SERVER['PHP_SELF']) == 'tipos_edit.php' ? 'active' : ''; ?>" href="tipos.php">
                        <i class="fas fa-fw fa-truck"></i>
                        <span>Tipos de Maquinaria</span>
                    </a>
                </div>
                
                <div class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'marcas.php' || basename($_SERVER['PHP_SELF']) == 'marcas_create.php' || basename($_SERVER['PHP_SELF']) == 'marcas_edit.php' ? 'active' : ''; ?>" href="marcas.php">
                        <i class="fas fa-fw fa-trademark"></i>
                        <span>Marcas</span>
                    </a>
                </div>
                
                <div class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'productos.php' || basename($_SERVER['PHP_SELF']) == 'productos_create.php' || basename($_SERVER['PHP_SELF']) == 'productos_edit.php' ? 'active' : ''; ?>" href="productos.php">
                        <i class="fas fa-fw fa-box"></i>
                        <span>Productos</span>
                    </a>
                </div>
                
                <div class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'archivos.php' || basename($_SERVER['PHP_SELF']) == 'archivos_upload.php' ? 'active' : ''; ?>" href="archivos.php">
                        <i class="fas fa-fw fa-file-pdf"></i>
                        <span>Archivos PDF</span>
                    </a>
                </div>
                <div class="nav-item">
    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'servicios.php' || basename($_SERVER['PHP_SELF']) == 'servicios_create.php' || basename($_SERVER['PHP_SELF']) == 'servicios_edit.php' ? 'active' : ''; ?>" href="servicios.php">
        <i class="fas fa-fw fa-cogs"></i>
        <span>Servicios</span>
    </a>
</div>
                
                <hr class="sidebar-divider">
                <div class="sidebar-heading">Administración</div>
                
                <div class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'usuarios.php' ? 'active' : ''; ?>" href="usuarios.php">
                        <i class="fas fa-fw fa-users"></i>
                        <span>Usuarios</span>
                    </a>
                </div>
                
                <div class="nav-item">
                    <a class="nav-link" href="logout.php">
                        <i class="fas fa-fw fa-sign-out-alt"></i>
                        <span>Cerrar Sesión</span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Content Wrapper -->
        <div class="col-md-9 col-lg-10 ms-sm-auto px-md-4">
            <!-- Topbar -->
            <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                <!-- Sidebar Toggle (Topbar) -->
                <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle me-3" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
                    <i class="fa fa-bars"></i>
                </button>
                
                <!-- Topbar Navbar -->
                <ul class="navbar-nav ms-auto">
                    <!-- Nav Item - User Information -->
                    <li class="nav-item dropdown no-arrow">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="me-2 d-none d-lg-inline text-gray-600 small">
                                <?php echo htmlspecialchars($_SESSION['admin_nombre'] ?? $_SESSION['admin_username']); ?>
                            </span>
                            <i class="fas fa-user-circle fa-fw"></i>
                        </a>
                        <!-- Dropdown - User Information -->
                        <div class="dropdown-menu dropdown-menu-end shadow animated--grow-in dropdown-user"
                            aria-labelledby="userDropdown">
                            <a class="dropdown-item" href="perfil.php">
                                <i class="fas fa-user fa-sm fa-fw me-2 text-gray-400"></i>
                                Perfil
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="logout.php">
                                <i class="fas fa-sign-out-alt fa-sm fa-fw me-2 text-gray-400"></i>
                                Cerrar Sesión
                            </a>
                        </div>
                    </li>
                </ul>
            </nav>
            
            <!-- Begin Page Content -->
            <div class="content-container">
                <!-- Contenido principal aquí -->