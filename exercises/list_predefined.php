<?php
/**
 * WorkoutMate - Get Exercises List Endpoint
 * 
 * GET /exercises/list
 */

require_once '../config/database.php';
require_once '../config/api_config.php';
require_once '../utils/Response.php';

ApiConfig::setHeaders();

if (!ApiConfig::validateMasterKey()) {
    Response::forbidden('[ExerciseAPI] - Master Key inválida');
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::badRequest('[ExerciseAPI] - Método no permitido');
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Parámetros opcionales
    $muscleGroup = isset($_GET['muscle_group']) ? $_GET['muscle_group'] : null;
    $difficulty = isset($_GET['difficulty']) ? $_GET['difficulty'] : null;
    $equipment = isset($_GET['equipment']) ? $_GET['equipment'] : null;
    
    $query = "SELECT id, name, category, muscle_group, description, difficulty, equipment 
              FROM predefined_exercises 
              WHERE is_active = 1";
    
    $params = [];
    
    if ($muscleGroup) {
        $query .= " AND muscle_group = :muscle_group";
        $params[':muscle_group'] = $muscleGroup;
    }
    
    if ($difficulty) {
        $query .= " AND difficulty = :difficulty";
        $params[':difficulty'] = $difficulty;
    }
    
    if ($equipment) {
        $query .= " AND equipment = :equipment";
        $params[':equipment'] = $equipment;
    }
    
    $query .= " ORDER BY name ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    $exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    Response::success([
        'exercises' => $exercises,
        'count' => count($exercises)
    ], 'Ejercicios obtenidos exitosamente');
    
} catch (Exception $e) {
    error_log("Get exercises error: " . $e->getMessage());
    Response::serverError('[ExerciseAPI] - Error al obtener ejercicios: ' . $e->getMessage());
}
?>