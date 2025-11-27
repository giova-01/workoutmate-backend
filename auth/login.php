<?php
/**
 * WorkoutMate - Login Endpoint
 * 
 * POST /auth/login
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
    if (empty($data->email) || empty($data->password)) {
        ApiConfig::sendError(ApiConfig::RESPONSE_BAD_REQUEST, '[AuthAPI] - Email y contraseña son requeridos');
    }
    
    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();
    
    // Buscar usuario por email
    $query = "SELECT id, email, password, first_name, last_name, role 
              FROM users 
              WHERE email = :email 
              LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":email", $data->email);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        ApiConfig::sendError(ApiConfig::RESPONSE_UNAUTHORIZED, '[AuthAPI] - Credenciales inválidas');
    }
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verificar contraseña
    if (!password_verify($data->password, $user['password'])) {
        ApiConfig::sendError(ApiConfig::RESPONSE_UNAUTHORIZED, '[AuthAPI] - Credenciales inválidas');
    }
    
    // Preparar respuesta (sin incluir la contraseña)
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
        'message' => 'Login exitoso',
        'user' => $userResponse
    ]);
    
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    ApiConfig::sendError(ApiConfig::RESPONSE_SERVER_ERROR, '[AuthAPI] - Error en el servidor');
}
?>
