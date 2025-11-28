<?php
/**
 * WorkoutMate - Create Workout Endpoint
 * 
 * POST /workouts/create
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
    
    // Validar campos requeridos
    $required = Validator::requiredFields($data, ['user_id', 'name', 'category', 'exercises']);
    if ($required !== true) {
        Response::badRequest('[WorkoutAPI] - Campos requeridos faltantes: ' . implode(', ', $required));
    }
    
    // Validar categoría
    $validCategories = ['STRENGTH', 'CARDIO', 'FLEXIBILITY', 'FUNCTIONAL', 'MIXED'];
    if (!Validator::inArray($data->category, $validCategories)) {
        Response::badRequest('[WorkoutAPI] - Categoría inválida');
    }
    
    // Validar que exercises sea un array
    if (!is_array($data->exercises) || count($data->exercises) === 0) {
        Response::badRequest('[WorkoutAPI] - Debe incluir al menos un ejercicio');
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar que el usuario existe
    $userQuery = "SELECT id FROM users WHERE id = :user_id LIMIT 1";
    $userStmt = $db->prepare($userQuery);
    $userStmt->bindParam(":user_id", $data->user_id);
    $userStmt->execute();
    
    if ($userStmt->rowCount() === 0) {
        Response::notFound('[WorkoutAPI] - Usuario no encontrado');
    }
    
    // Iniciar transacción
    $db->beginTransaction();
    
    try {
        // Generar ID para el workout
        $workoutId = UUID::generate();
        
        // Insertar workout
        $workoutQuery = "INSERT INTO workouts (id, user_id, name, category, is_public) 
                        VALUES (:id, :user_id, :name, :category, :is_public)";
        
        $workoutStmt = $db->prepare($workoutQuery);
        $workoutStmt->bindParam(":id", $workoutId);
        $workoutStmt->bindParam(":user_id", $data->user_id);
        $workoutStmt->bindParam(":name", $data->name);
        $workoutStmt->bindParam(":category", $data->category);
        
        $isPublic = isset($data->is_public) ? $data->is_public : false;
        $workoutStmt->bindParam(":is_public", $isPublic, PDO::PARAM_BOOL);
        
        $workoutStmt->execute();
        
        // Insertar ejercicios
        $exerciseQuery = "INSERT INTO exercises (id, workout_id, name, sets, repetitions, rest_time, notes, order_index) 
                         VALUES (:id, :workout_id, :name, :sets, :repetitions, :rest_time, :notes, :order_index)";
        
        $exerciseStmt = $db->prepare($exerciseQuery);
        
        foreach ($data->exercises as $index => $exercise) {
            // Validar ejercicio
            if (!isset($exercise->name) || !Validator::required($exercise->name)) {
                throw new Exception('Nombre de ejercicio requerido');
            }
            
            $exerciseId = UUID::generate();
            $exerciseStmt->bindParam(":id", $exerciseId);
            $exerciseStmt->bindParam(":workout_id", $workoutId);
            $exerciseStmt->bindParam(":name", $exercise->name);
            
            $sets = isset($exercise->sets) ? intval($exercise->sets) : 1;
            $reps = isset($exercise->repetitions) ? intval($exercise->repetitions) : 1;
            $rest = isset($exercise->rest_time) ? intval($exercise->rest_time) : 60;
            $notes = isset($exercise->notes) ? $exercise->notes : null;
            
            $exerciseStmt->bindParam(":sets", $sets, PDO::PARAM_INT);
            $exerciseStmt->bindParam(":repetitions", $reps, PDO::PARAM_INT);
            $exerciseStmt->bindParam(":rest_time", $rest, PDO::PARAM_INT);
            $exerciseStmt->bindParam(":notes", $notes);
            $exerciseStmt->bindParam(":order_index", $index, PDO::PARAM_INT);
            
            $exerciseStmt->execute();
        }
        
        // Commit transacción
        $db->commit();
        
        // Obtener el workout completo usando GROUP_CONCAT (compatible con MariaDB)
        $getWorkoutQuery = "
            SELECT 
                w.id,
                w.user_id,
                w.name,
                w.category,
                w.is_public,
                w.share_link,
                w.qr_code_path,
                w.created_at,
                w.updated_at,
                (
                    SELECT 
                        CONCAT(
                            '[',
                            GROUP_CONCAT(
                                CONCAT(
                                    '{',
                                    '\"id\":\"', e.id, '\",',
                                    '\"name\":\"', REPLACE(REPLACE(e.name, '\"', '\\\\\"'), '\\n', ' '), '\",',
                                    '\"sets\":', COALESCE(e.sets, 0), ',',
                                    '\"repetitions\":', COALESCE(e.repetitions, 0), ',',
                                    '\"rest_time\":', COALESCE(e.rest_time, 0), ',',
                                    '\"notes\":\"', COALESCE(REPLACE(REPLACE(e.notes, '\"', '\\\\\"'), '\\n', ' '), ''), '\",',
                                    '\"order_index\":', COALESCE(e.order_index, 0),
                                    '}'
                                )
                                ORDER BY e.order_index SEPARATOR ','
                            ),
                            ']'
                        )
                    FROM exercises e 
                    WHERE e.workout_id = w.id
                ) AS exercises
            FROM workouts w 
            WHERE w.id = :id
        ";
        
        $getStmt = $db->prepare($getWorkoutQuery);
        $getStmt->bindParam(":id", $workoutId);
        $getStmt->execute();
        
        $workout = $getStmt->fetch(PDO::FETCH_ASSOC);
        
        // Convertir exercises de string a array
        if ($workout['exercises'] === null || $workout['exercises'] === '') {
            $workout['exercises'] = [];
        } else {
            $decoded = json_decode($workout['exercises'], true);
            $workout['exercises'] = $decoded ?: [];
        }
        $workout['is_public'] = (bool) $workout['is_public'];
        
        Response::created(['workout' => $workout], 'Rutina creada exitosamente');
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Create workout error: " . $e->getMessage());
    Response::serverError('[WorkoutAPI] - Error al crear rutina: ' . $e->getMessage());
}
?>