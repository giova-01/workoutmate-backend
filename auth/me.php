<?php
/**
 * WorkoutMate - Get Current User Endpoint
 * 
 * GET /auth/me
 */

require_once '../config/database.php';
require_once '../config/api_config.php';

// Configurar headers
ApiConfig::setHeaders();

// Validar Master Key
if (!ApiConfig::validateMasterKey()) {
    ApiConfig::sendError(ApiConfig::RESPONSE_FORBIDDEN, '[AuthAPI] - Master Key inválida');
}

// Solo permitir GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    ApiConfig::sendError(ApiConfig::RESPONSE_BAD_REQUEST, '[AuthAPI] - Método no permitido');
}

try {
    // Obtener userId de la URL
    $requestUri = $_SERVER['REQUEST_URI'];
    $uriParts = explode('/', $requestUri);
    $userId = end($uriParts);
    
    if (empty($userId)) {
        ApiConfig::sendError(ApiConfig::RESPONSE_BAD_REQUEST, '[AuthAPI] - ID de usuario requerido');
    }
    
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Buscar usuario por ID
    $query = "SELECT id, email, first_name, last_name, role 
              FROM users 
              WHERE id = :id 
              LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $userId);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        ApiConfig::sendError(ApiConfig::RESPONSE_NOT_FOUND, '[AuthAPI] - Usuario no encontrado');
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Preparar respuesta
    $userResponse = [
        'id' => $user['id'],
        'email' => $user['email'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'role' => $user['role']
    ];
    
    // Enviar respuesta exitosa
    ApiConfig::sendResponse(ApiConfig::RESPONSE_SUCCESS, [
        'success' => true,
        'user' => $userResponse
    ]);
    
} catch (Exception $e) {
    error_log("Get user error: " . $e->getMessage());
    ApiConfig::sendError(ApiConfig::RESPONSE_SERVER_ERROR, '[AuthAPI] - Error en el servidor');
}
?>
