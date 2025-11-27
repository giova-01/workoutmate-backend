<?php
/**
 * WorkoutMate - Register Endpoint
 * 
 * POST /auth/register
 */

require_once '../config/database.php';
require_once '../config/api_config.php';

// Configurar headers
ApiConfig::setHeaders();

// Validar Master Key
if (!ApiConfig::validateMasterKey()) {
    ApiConfig::sendError(ApiConfig::RESPONSE_FORBIDDEN, '[AuthAPI] - Master Key inválida');
}

// Solo permitir POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiConfig::sendError(ApiConfig::RESPONSE_BAD_REQUEST, '[AuthAPI] - Método no permitido');
}

try {
    // Obtener datos del request
    $data = json_decode(file_get_contents("php://input"));
    
    // Validar datos requeridos
    if (empty($data->email) || empty($data->password) || 
        empty($data->first_name) || empty($data->last_name)) {
        ApiConfig::sendError(ApiConfig::RESPONSE_BAD_REQUEST, '[AuthAPI] - Todos los campos son requeridos');
    }
    
    // Validar formato de email
    if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
        ApiConfig::sendError(ApiConfig::RESPONSE_BAD_REQUEST, '[AuthAPI] - Formato de email inválido');
    }
    
    // Validar longitud de contraseña
    if (strlen($data->password) < 6) {
        ApiConfig::sendError(ApiConfig::RESPONSE_BAD_REQUEST, '[AuthAPI] - La contraseña debe tener al menos 6 caracteres');
    }
    
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar si el email ya existe
    $checkQuery = "SELECT id FROM users WHERE email = :email LIMIT 1";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(":email", $data->email);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() > 0) {
        ApiConfig::sendError(ApiConfig::RESPONSE_BAD_REQUEST, '[AuthAPI] - El email ya está registrado');
    }
    
    // Generar ID único
    $userId = bin2hex(random_bytes(16));
    
    // Encriptar contraseña
    $hashedPassword = password_hash($data->password, PASSWORD_BCRYPT);
    
    // Insertar nuevo usuario
    $insertQuery = "INSERT INTO users (id, email, password, first_name, last_name, role) 
                    VALUES (:id, :email, :password, :first_name, :last_name, 'user')";
    
    $insertStmt = $db->prepare($insertQuery);
    $insertStmt->bindParam(":id", $userId);
    $insertStmt->bindParam(":email", $data->email);
    $insertStmt->bindParam(":password", $hashedPassword);
    $insertStmt->bindParam(":first_name", $data->first_name);
    $insertStmt->bindParam(":last_name", $data->last_name);
    
    if (!$insertStmt->execute()) {
        ApiConfig::sendError(ApiConfig::RESPONSE_SERVER_ERROR, '[AuthAPI] - Error al crear el usuario');
    }
    
    // Preparar respuesta
    $userResponse = [
        'id' => $userId,
        'email' => $data->email,
        'first_name' => $data->first_name,
        'last_name' => $data->last_name,
        'role' => 'user'
    ];
    
    // Enviar respuesta exitosa
    ApiConfig::sendResponse(ApiConfig::RESPONSE_CREATED, [
        'success' => true,
        'message' => 'Usuario registrado exitosamente',
        'user' => $userResponse
    ]);
    
} catch (Exception $e) {
    error_log("Register error: " . $e->getMessage());
    ApiConfig::sendError(ApiConfig::RESPONSE_SERVER_ERROR, '[AuthAPI] - Error en el servidor');
}
?>
