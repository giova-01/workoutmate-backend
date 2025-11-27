<?php
/**
 * WorkoutMate - Save Progress Endpoint
 * 
 * POST /progress/save
 */

require_once '../config/database.php';
require_once '../config/api_config.php';
require_once '../utils/Response.php';
require_once '../utils/Validator.php';
require_once '../utils/UUID.php';

ApiConfig::setHeaders();

if (!ApiConfig::validateMasterKey()) {
    Response::forbidden('[ProgressAPI] - Master Key inválida');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::badRequest('[ProgressAPI] - Método no permitido');
}

try {
    $data = json_decode(file_get_contents("php://input"));
    
    // Validar campos requeridos
    $required = Validator::requiredFields($data, ['user_id', 'workout_id', 'date']);
    if ($required !== true) {
        Response::badRequest('[ProgressAPI] - Campos requeridos faltantes: ' . implode(', ', $required));
    }
    
    // Validar fecha
    if (!Validator::date($data->date)) {
        Response::badRequest('[ProgressAPI] - Formato de fecha inválido (use Y-m-d)');
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar que el workout existe
    $workoutQuery = "SELECT id FROM workouts WHERE id = :id LIMIT 1";
    $workoutStmt = $db->prepare($workoutQuery);
    $workoutStmt->bindParam(":id", $data->workout_id);
    $workoutStmt->execute();
    
    if ($workoutStmt->rowCount() === 0) {
        Response::notFound('[ProgressAPI] - Rutina no encontrada');
    }
    
    // Iniciar transacción
    $db->beginTransaction();
    
    try {
        // Verificar si ya existe progreso para este día
        $checkQuery = "SELECT id FROM progress 
                      WHERE user_id = :user_id AND workout_id = :workout_id AND date = :date 
                      LIMIT 1";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(":user_id", $data->user_id);
        $checkStmt->bindParam(":workout_id", $data->workout_id);
        $checkStmt->bindParam(":date", $data->date);
        $checkStmt->execute();
        
        $progressId = null;
        
        if ($checkStmt->rowCount() > 0) {
            // Actualizar existente
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
            $progressId = $existing['id'];
            
            $updateQuery = "UPDATE progress SET total_time = :total_time, notes = :notes 
                           WHERE id = :id";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindParam(":id", $progressId);
            
            $totalTime = isset($data->total_time) ? intval($data->total_time) : 0;
            $notes = isset($data->notes) ? $data->notes : null;
            
            $updateStmt->bindParam(":total_time", $totalTime, PDO::PARAM_INT);
            $updateStmt->bindParam(":notes", $notes);
            $updateStmt->execute();
            
            // Eliminar exercise_progress existentes
            $deleteExQuery = "DELETE FROM exercise_progress WHERE progress_id = :progress_id";
            $deleteExStmt = $db->prepare($deleteExQuery);
            $deleteExStmt->bindParam(":progress_id", $progressId);
            $deleteExStmt->execute();
            
        } else {
            // Crear nuevo
            $progressId = UUID::generate();
            
            $insertQuery = "INSERT INTO progress (id, user_id, workout_id, date, total_time, notes) 
                           VALUES (:id, :user_id, :workout_id, :date, :total_time, :notes)";
            
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->bindParam(":id", $progressId);
            $insertStmt->bindParam(":user_id", $data->user_id);
            $insertStmt->bindParam(":workout_id", $data->workout_id);
            $insertStmt->bindParam(":date", $data->date);
            
            $totalTime = isset($data->total_time) ? intval($data->total_time) : 0;
            $notes = isset($data->notes) ? $data->notes : null;
            
            $insertStmt->bindParam(":total_time", $totalTime, PDO::PARAM_INT);
            $insertStmt->bindParam(":notes", $notes);
            $insertStmt->execute();
        }
        
        // Insertar exercise_progress si se proporcionaron
        if (isset($data->completed_exercises) && is_array($data->completed_exercises)) {
            $exProgressQuery = "INSERT INTO exercise_progress 
                               (id, progress_id, exercise_id, completed, weight, actual_reps) 
                               VALUES (:id, :progress_id, :exercise_id, :completed, :weight, :actual_reps)";
            
            $exProgressStmt = $db->prepare($exProgressQuery);
            
            foreach ($data->completed_exercises as $exProgress) {
                if (!isset($exProgress->exercise_id)) {
                    continue;
                }
                
                $exId = UUID::generate();
                $exProgressStmt->bindParam(":id", $exId);
                $exProgressStmt->bindParam(":progress_id", $progressId);
                $exProgressStmt->bindParam(":exercise_id", $exProgress->exercise_id);
                
                $completed = isset($exProgress->completed) ? $exProgress->completed : false;
                $weight = isset($exProgress->weight) ? floatval($exProgress->weight) : null;
                $actualReps = isset($exProgress->actual_reps) ? intval($exProgress->actual_reps) : null;
                
                $exProgressStmt->bindParam(":completed", $completed, PDO::PARAM_BOOL);
                $exProgressStmt->bindParam(":weight", $weight);
                $exProgressStmt->bindParam(":actual_reps", $actualReps, PDO::PARAM_INT);
                
                $exProgressStmt->execute();
            }
        }
        
        $db->commit();
        
        Response::success(['progress_id' => $progressId], 'Progreso guardado exitosamente');
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Save progress error: " . $e->getMessage());
    Response::serverError('[ProgressAPI] - Error al guardar progreso: ' . $e->getMessage());
}
?>
