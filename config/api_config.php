<?php
/**
 * WorkoutMate - API Configuration
 * 
 * Configuración de constantes para la API
 */

class ApiConfig {
    // Master Key
    const MASTER_KEY = "workoutmate_masterkey_XD";
    
    // Headers
    const MASTER_KEY_HEADER = "master-key";
    const AUTH_HEADER = "Authorization";
    const CONTENT_TYPE = "Content-Type";
    const APPLICATION_JSON = "application/json";
    
    // Configuración de respuestas
    const RESPONSE_SUCCESS = 200;
    const RESPONSE_CREATED = 201;
    const RESPONSE_BAD_REQUEST = 400;
    const RESPONSE_UNAUTHORIZED = 401;
    const RESPONSE_FORBIDDEN = 403;
    const RESPONSE_NOT_FOUND = 404;
    const RESPONSE_SERVER_ERROR = 500;
    
    // Configuración de JWT
    const JWT_SECRET = "workoutmate_jwt_secret_2025";
    const JWT_ALGORITHM = "HS256";
    const JWT_EXPIRATION = 86400;
    
    /**
     * Configurar headers de respuesta
     */
    public static function setHeaders() {
        header("Access-Control-Allow-Origin: *");
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Methods: POST, GET, PUT, DELETE, OPTIONS");
        header("Access-Control-Max-Age: 3600");
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With, X-Master-Key");
        
        // Manejar preflight request
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
    }
    
    /**
     * Validar Master Key
     * 
     * @return bool
     */
    public static function validateMasterKey() {
        $headers = getallheaders();
        
        if (!isset($headers[self::MASTER_KEY_HEADER])) {
            return false;
        }
        
        return $headers[self::MASTER_KEY_HEADER] === self::MASTER_KEY;
    }
    
    /**
     * Enviar respuesta JSON
     * 
     * @param int $code HTTP status code
     * @param mixed $data Data to send
     */
    public static function sendResponse($code, $data) {
        http_response_code($code);
        echo json_encode($data);
        exit();
    }
    
    /**
     * Enviar error
     * 
     * @param int $code HTTP status code
     * @param string $message Error message
     */
    public static function sendError($code, $message) {
        self::sendResponse($code, [
            'success' => false,
            'message' => $message
        ]);
    }
}
?>
