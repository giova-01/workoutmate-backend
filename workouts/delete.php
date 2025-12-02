<?php
/**
 * WorkoutMate - Delete Workout Endpoint
 * 
 * DELETE /workouts/delete/:workoutId
 */

require_once '../config/database.php';
require_once '../config/api_config.php';
require_once '../utils/Response.php';

ApiConfig::setHeaders();

if (!ApiConfig::validateMasterKey()) {
    Response::forbidden('[WorkoutAPI] - Master Key inválida');
}

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    Response::badRequest('[WorkoutAPI] - Método no permitido');
}

try {
    // Obtener workoutId de la URL
    $requestUri = $_SERVER['REQUEST_URI'];
    $uriParts = explode('/', $requestUri);
    $workoutId = end($uriParts);
    
    if (empty($workoutId)) {
        Response::badRequest('[WorkoutAPI] - ID de rutina requerido');
    }
    
    // Obtener user_id del body (para verificar permisos)
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->user_id)) {
        Response::badRequest('[WorkoutAPI] - ID de usuario requerido');
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar que el workout existe y pertenece al usuario
    $checkQuery = "SELECT id FROM workouts WHERE id = :id AND user_id = :user_id LIMIT 1";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(":id", $workoutId);
    $checkStmt->bindParam(":user_id", $data->user_id);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        Response::notFound('[WorkoutAPI] - Rutina no encontrada o no autorizado');
    }
    
    // Eliminar workout (los ejercicios se eliminan automáticamente por CASCADE)
    $deleteQuery = "DELETE FROM workouts WHERE id = :id";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->bindParam(":id", $workoutId);
    $deleteStmt->execute();
    
    Response::success(null, 'Rutina eliminada exitosamente');
    
} catch (Exception $e) {
    error_log("Delete workout error: " . $e->getMessage());
    Response::serverError('[WorkoutAPI] - Error al eliminar rutina');
}
?>
