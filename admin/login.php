<?php
// admin/index.php - Página de inicio de sesión

// Iniciar sesión si no está iniciada
session_start();

// Si ya hay una sesión activa, redirigir al panel
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

// Incluir archivo de conexión
require_once 'conexion.php';

$error_message = '';

// Procesar formulario de inicio de sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (empty($username) || empty($password)) {
        $error_message = 'Por favor ingrese usuario y contraseña';
    } else {
        try {
            // Buscar usuario en la base de datos
            $stmt = $cn->prepare("SELECT * FROM usuarios WHERE username = :username LIMIT 1");
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Verificar la contraseña
                if (password_verify($password, $user['password'])) {
                    // Actualizar fecha de último login
                    $update_stmt = $cn->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id_usuario = :id");
                    $update_stmt->bindParam(':id', $user['id_usuario'], PDO::PARAM_INT);
                    $update_stmt->execute();
                    
                    // Iniciar sesión
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $user['id_usuario'];
                    $_SESSION['admin_username'] = $user['username'];
                    $_SESSION['admin_nombre'] = $user['nombre'];
                    
                    // Redirigir al panel
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error_message = 'Contraseña incorrecta';
                }
            } else {
                $error_message = 'Usuario no encontrado';
            }
        } catch (PDOException $e) {
            $error_message = 'Error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Iniciar Sesión</title>
    <link rel="stylesheet" href="../styles/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
        }
        .card {
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .card-header {
            background-color: #0d6efd;
            color: white;
            text-align: center;
            border-radius: 1rem 1rem 0 0 !important;
            padding: 1.5rem;
        }
        .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo-container img {
            height: 80px;
        }
    </style>
</head>
<body>
    <div class="container login-container">
        <div class="logo-container">
            <img src="../recursos/LOGO.png" alt="Logo">
        </div>
        
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0"><i class="fas fa-lock me-2"></i>Acceso Administrador</h4>
            </div>
            <div class="card-body p-4">
                <?php if (!empty($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">Usuario</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="password" class="form-label">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-key"></i></span>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt me-2"></i>Iniciar Sesión
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="text-center mt-3 text-muted">
            <a href="../index.php" class="text-decoration-none">
                <i class="fas fa-arrow-left me-1"></i>Volver al sitio
            </a>
        </div>
    </div>
    
    <script src="../scripts/js/bootstrap.bundle.min.js"></script>
</body>
</html>