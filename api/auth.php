<?php
/**
 * Archivo para verificar autenticación de usuarios
 * Se puede incluir en archivos que requieran autenticación para acceder
 */

// Verificar si se está intentando acceder directamente
if (basename($_SERVER['PHP_SELF']) == 'auth.php') {
    header('HTTP/1.0 403 Forbidden');
    echo json_encode([
        'success' => false,
        'message' => 'Acceso directo no permitido'
    ]);
    exit;
}

// Si estamos en desarrollo, simular autenticación
if ($_SERVER['HTTP_HOST'] == 'localhost') {
    $auth_user_id = 1;
    $auth_username = 'admin';
    return;
}

session_start();

// Verificar si existe una sesión iniciada
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Para API, responder con JSON y código 401
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode([
        'success' => false,
        'message' => 'No autorizado. Debe iniciar sesión.'
    ]);
    exit;
}

// Usuario autenticado, establecer variables
$auth_user_id = $_SESSION['admin_id'] ?? 1;
$auth_username = $_SESSION['admin_username'] ?? 'admin';