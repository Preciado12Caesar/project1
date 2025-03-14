<?php
/**
 * API para subir un nuevo PDF al sistema
 * Recibe un archivo PDF y sus metadatos
 */

// Incluir configuración de base de datos
require_once 'config.php';
require_once 'auth.php';

// Configuración de cabeceras para JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

// Verificar datos obligatorios
if (!isset($_FILES['pdf_file']) || $_FILES['pdf_file']['error'] !== UPLOAD_ERR_OK) {
    $errorMessage = 'Archivo no proporcionado o inválido';
    
    // Obtener mensaje de error específico
    if (isset($_FILES['pdf_file']['error'])) {
        switch ($_FILES['pdf_file']['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $errorMessage = 'El archivo excede el tamaño máximo permitido por el servidor';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $errorMessage = 'El archivo excede el tamaño máximo permitido por el formulario';
                break;
            case UPLOAD_ERR_PARTIAL:
                $errorMessage = 'El archivo se subió parcialmente';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errorMessage = 'No se seleccionó ningún archivo';
                break;
        }
    }
    
    echo json_encode([
        'success' => false,
        'message' => $errorMessage
    ]);
    exit;
}

// Verificar tipo de referencia y id
if (empty($_POST['tipo_referencia']) || empty($_POST['id_referencia'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Tipo de referencia o ID no especificados'
    ]);
    exit;
}

// Verificar tipo de archivo
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $_FILES['pdf_file']['tmp_name']);
finfo_close($finfo);

if ($mime !== 'application/pdf') {
    echo json_encode([
        'success' => false,
        'message' => 'El archivo debe ser un PDF'
    ]);
    exit;
}

// Verificar tamaño máximo
if ($_FILES['pdf_file']['size'] > MAX_FILE_SIZE) {
    echo json_encode([
        'success' => false,
        'message' => 'El archivo excede el tamaño máximo permitido (' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB)'
    ]);
    exit;
}

// Procesar datos
$tipo_referencia = filter_var($_POST['tipo_referencia'], FILTER_SANITIZE_STRING);
$id_referencia = filter_var($_POST['id_referencia'], FILTER_VALIDATE_INT);
$descripcion = isset($_POST['descripcion']) ? filter_var($_POST['descripcion'], FILTER_SANITIZE_STRING) : '';
$nombre_personalizado = isset($_POST['nombre_personalizado']) ? filter_var($_POST['nombre_personalizado'], FILTER_SANITIZE_STRING) : '';

try {
    // Verificar que el ID de referencia exista
    $tabla_referencia = '';
    $campo_id = '';
    
    switch ($tipo_referencia) {
        case 'tipo_maquinaria':
            $tabla_referencia = 'tipo_maquinaria';
            $campo_id = 'id_tipo_maquinaria';
            break;
        case 'marca':
            $tabla_referencia = 'marcas';
            $campo_id = 'id_marca';
            break;
        case 'producto':
            $tabla_referencia = 'productos';
            $campo_id = 'id_producto';
            break;
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Tipo de referencia no válido'
            ]);
            exit;
    }
    
    $sql_check = "SELECT COUNT(*) FROM $tabla_referencia WHERE $campo_id = :id";
    $stmt_check = $cn->prepare($sql_check);
    $stmt_check->bindParam(':id', $id_referencia, PDO::PARAM_INT);
    $stmt_check->execute();
    
    if ($stmt_check->fetchColumn() == 0) {
        echo json_encode([
            'success' => false,
            'message' => "El ID de $tabla_referencia no existe"
        ]);
        exit;
    }
    
    // Crear directorio si no existe
    if (!file_exists('../' . PDF_DIRECTORY)) {
        mkdir('../' . PDF_DIRECTORY, 0777, true);
    }
    
    // Generar nombre de archivo único
    $nombre_original = basename($_FILES['pdf_file']['name']);
    $extension = pathinfo($nombre_original, PATHINFO_EXTENSION);
    $nombre_base = empty($nombre_personalizado) 
        ? pathinfo($nombre_original, PATHINFO_FILENAME) 
        : $nombre_personalizado;
        
    // Sanitizar nombre de archivo
    $nombre_base = preg_replace('/[^a-zA-Z0-9_-]/', '', $nombre_base);
    $nombre_archivo = $nombre_base . '_' . time() . '.' . $extension;
    $ruta_destino = '../' . PDF_DIRECTORY . $nombre_archivo;
    
    // Mover el archivo
    if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $ruta_destino)) {
        // Guardar en la base de datos
        $stmt = $cn->prepare("
            INSERT INTO archivos_pdf (nombre_archivo, ruta_archivo, descripcion, tipo_referencia, id_referencia, fecha_creacion) 
            VALUES (:nombre, :ruta, :descripcion, :tipo_referencia, :id_referencia, NOW())
        ");
        
        $nombre_mostrar = empty($nombre_personalizado) 
            ? pathinfo($nombre_original, PATHINFO_FILENAME) 
            : $nombre_personalizado;
        
        $ruta_relativa = PDF_DIRECTORY . $nombre_archivo;
        
        $stmt->bindParam(':nombre', $nombre_mostrar, PDO::PARAM_STR);
        $stmt->bindParam(':ruta', $ruta_relativa, PDO::PARAM_STR);
        $stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
        $stmt->bindParam(':tipo_referencia', $tipo_referencia, PDO::PARAM_STR);
        $stmt->bindParam(':id_referencia', $id_referencia, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            // Registrar actividad
            $accion = "PDF añadido: $nombre_mostrar";
            registrar_actividad($cn, $accion, $auth_user_id);
            
            echo json_encode([
                'success' => true,
                'message' => 'PDF subido correctamente',
                'pdf_id' => $cn->lastInsertId(),
                'pdf_name' => $nombre_mostrar,
                'pdf_path' => $ruta_relativa
            ]);
        } else {
            // Si falla la inserción, eliminar el archivo
            unlink($ruta_destino);
            
            echo json_encode([
                'success' => false,
                'message' => 'Error al guardar la información del PDF en la base de datos'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Error al mover el archivo cargado'
        ]);
    }
} catch (PDOException $e) {
    error_log("Error al subir PDF: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Error en el servidor al procesar el PDF',
        'error' => $e->getMessage()
    ]);
}