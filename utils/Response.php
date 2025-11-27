<?php
/**
 * WorkoutMate - HTTP Response Helper
 * 
 * Clase para estandarizar respuestas HTTP
 */

class Response {
    
    /**
     * Enviar respuesta exitosa
     */
    public static function success($data = null, $message = null, $code = 200) {
        http_response_code($code);
        
        $response = ['success' => true];
        
        if ($message) {
            $response['message'] = $message;
        }
        
        if ($data !== null) {
            if (is_array($data) && !self::isAssoc($data)) {
                $response['data'] = $data;
            } else {
                $response = array_merge($response, is_array($data) ? $data : ['data' => $data]);
            }
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    /**
     * Enviar respuesta de error
     */
    public static function error($message, $code = 400, $details = null) {
        http_response_code($code);
        
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if ($details !== null) {
            $response['details'] = $details;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    /**
     * Verificar si un array es asociativo
     */
    private static function isAssoc(array $arr) {
        if (array() === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }
    
    /**
     * Respuestas predefinidas
     */
    public static function unauthorized($message = 'No autorizado') {
        self::error($message, 401);
    }
    
    public static function forbidden($message = 'Acceso prohibido') {
        self::error($message, 403);
    }
    
    public static function notFound($message = 'Recurso no encontrado') {
        self::error($message, 404);
    }
    
    public static function serverError($message = 'Error interno del servidor') {
        self::error($message, 500);
    }
    
    public static function badRequest($message = 'Petición inválida') {
        self::error($message, 400);
    }
    
    public static function created($data = null, $message = 'Recurso creado exitosamente') {
        self::success($data, $message, 201);
    }
}
?>
