<?php
/**
 * WorkoutMate - Clone Workout Endpoint
 * 
 * POST /workouts/clone
 */

require_once '../config/database.php';
require_once '../config/api_config.php';
require_once '../utils/Response.php';
require_once '../utils/Validator.php';
require_once '../utils/UUID.php';

ApiConfig::setHeaders();

if (!ApiConfig::validateMasterKey()) {
    Response::forbidden('[WorkoutAPI] - Master Key inválida');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::badRequest('[WorkoutAPI] - Método no permitido');
}

try {
    $data = json_decode(file_get_contents("php://input"));
    
    $required = Validator::requiredFields($data, ['share_link', 'user_id']);
    if ($required !== true) {
        Response::badRequest('[WorkoutAPI] - Campos requeridos faltantes: ' . implode(', ', $required));
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener workout original por share_link
    $query = "SELECT * FROM workouts WHERE share_link = :share_link AND is_public = true LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":share_link", $data->share_link);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        Response::notFound('[WorkoutAPI] - Rutina no encontrada o no es pública');
    }
    
    $originalWorkout = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verificar que no sea el mismo usuario
    if ($originalWorkout['user_id'] === $data->user_id) {
        Response::badRequest('[WorkoutAPI] - No puedes clonar tu propia rutina');
    }
    
    // Crear nuevo workout
    $newWorkoutId = UUID::generate();
    $newName = $originalWorkout['name'] . ' (copia)';
    
    $insertQuery = "INSERT INTO workouts (id, user_id, name, category, is_public, created_at, updated_at) 
                    VALUES (:id, :user_id, :name, :category, false, NOW(), NOW())";
    $insertStmt = $db->prepare($insertQuery);
    $insertStmt->bindParam(":id", $newWorkoutId);
    $insertStmt->bindParam(":user_id", $data->user_id);
    $insertStmt->bindParam(":name", $newName);
    $insertStmt->bindParam(":category", $originalWorkout['category']);
    $insertStmt->execute();
    
    // Obtener ejercicios originales
    $exercisesQuery = "SELECT * FROM exercises WHERE workout_id = :workout_id ORDER BY order_index";
    $exercisesStmt = $db->prepare($exercisesQuery);
    $exercisesStmt->bindParam(":workout_id", $originalWorkout['id']);
    $exercisesStmt->execute();
    $exercises = $exercisesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Clonar cada ejercicio
    foreach ($exercises as $exercise) {
        $newExerciseId = UUID::generate();
        $cloneQuery = "INSERT INTO exercises (id, workout_id, name, category, muscle_group, description, difficulty, equipment, sets, repetitions, rest_time, order_index) 
                       VALUES (:id, :workout_id, :name, :category, :muscle_group, :description, :difficulty, :equipment, :sets, :repetitions, :rest_time, :order_index)";
        $cloneStmt = $db->prepare($cloneQuery);
        $cloneStmt->bindParam(":id", $newExerciseId);
        $cloneStmt->bindParam(":workout_id", $newWorkoutId);
        $cloneStmt->bindParam(":name", $exercise['name']);
        $cloneStmt->bindParam(":category", $exercise['category']);
        $cloneStmt->bindParam(":muscle_group", $exercise['muscle_group']);
        $cloneStmt->bindParam(":description", $exercise['description']);
        $cloneStmt->bindParam(":difficulty", $exercise['difficulty']);
        $cloneStmt->bindParam(":equipment", $exercise['equipment']);
        $cloneStmt->bindParam(":sets", $exercise['sets']);
        $cloneStmt->bindParam(":repetitions", $exercise['repetitions']);
        $cloneStmt->bindParam(":rest_time", $exercise['rest_time']);
        $cloneStmt->bindParam(":order_index", $exercise['order_index']);
        $cloneStmt->execute();
    }
    
    Response::success([
        'workout_id' => $newWorkoutId,
        'message' => 'Rutina clonada exitosamente'
    ], 'Rutina guardada en tu cuenta');
    
} catch (Exception $e) {
    error_log("Clone workout error: " . $e->getMessage());
    Response::serverError('[WorkoutAPI] - Error al clonar rutina');
}
?>