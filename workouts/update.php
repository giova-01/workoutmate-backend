<?php
/**
 * WorkoutMate - Update Workout Endpoint
 * 
 * PUT /workouts/update
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

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    Response::badRequest('[WorkoutAPI] - Método no permitido');
}

try {
    $data = json_decode(file_get_contents("php://input"));
    
    // Validar campos requeridos
    $required = Validator::requiredFields($data, ['id', 'user_id']);
    if ($required !== true) {
        Response::badRequest('[WorkoutAPI] - Campos requeridos faltantes: ' . implode(', ', $required));
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar que el workout existe y pertenece al usuario
    $checkQuery = "SELECT id FROM workouts WHERE id = :id AND user_id = :user_id LIMIT 1";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(":id", $data->id);
    $checkStmt->bindParam(":user_id", $data->user_id);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        Response::notFound('[WorkoutAPI] - Rutina no encontrada o no autorizado');
    }
    
    // Iniciar transacción
    $db->beginTransaction();
    
    try {
        // Actualizar workout si hay campos para actualizar
        $updateFields = [];
        $params = [':id' => $data->id];
        
        if (isset($data->name)) {
            $updateFields[] = "name = :name";
            $params[':name'] = $data->name;
        }
        
        if (isset($data->category)) {
            $validCategories = ['STRENGTH', 'CARDIO', 'FLEXIBILITY', 'FUNCTIONAL', 'MIXED'];
            if (!Validator::inArray($data->category, $validCategories)) {
                throw new Exception('Categoría inválida');
            }
            $updateFields[] = "category = :category";
            $params[':category'] = $data->category;
        }
        
        if (isset($data->is_public)) {
            $updateFields[] = "is_public = :is_public";
            $params[':is_public'] = $data->is_public ? 1 : 0;
        }
        
        if (!empty($updateFields)) {
            $updateQuery = "UPDATE workouts SET " . implode(', ', $updateFields) . 
                          ", updated_at = CURRENT_TIMESTAMP WHERE id = :id";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute($params);
        }
        
        // Si se enviaron ejercicios, reemplazarlos completamente
        if (isset($data->exercises) && is_array($data->exercises)) {
            // Eliminar ejercicios existentes
            $deleteQuery = "DELETE FROM exercises WHERE workout_id = :workout_id";
            $deleteStmt = $db->prepare($deleteQuery);
            $deleteStmt->bindParam(":workout_id", $data->id);
            $deleteStmt->execute();
            
            // Insertar nuevos ejercicios (mismos campos que create.php)
            $exerciseQuery = "INSERT INTO exercises (
                id, workout_id, name, category, muscle_group, 
                sets, repetitions, rest_time, description, difficulty, 
                equipment, order_index
            ) VALUES (
                :id, :workout_id, :name, :category, :muscle_group,
                :sets, :repetitions, :rest_time, :description, :difficulty,
                :equipment, :order_index
            )";
            
            $exerciseStmt = $db->prepare($exerciseQuery);
            
            foreach ($data->exercises as $index => $exercise) {
                if (!isset($exercise->name) || !Validator::required($exercise->name)) {
                    throw new Exception('Nombre de ejercicio requerido');
                }
                
                $exerciseId = UUID::generate();
                $exerciseStmt->bindParam(":id", $exerciseId);
                $exerciseStmt->bindParam(":workout_id", $data->id);
                $exerciseStmt->bindParam(":name", $exercise->name);
                
                // Nuevos campos con defaults
                $category = isset($exercise->category) ? $exercise->category : null;
                $muscle = isset($exercise->muscle_group) ? $exercise->muscle_group : null;
                $desc = isset($exercise->description) ? $exercise->description : null;
                $difficulty = isset($exercise->difficulty) ? $exercise->difficulty : null;
                $equipment = isset($exercise->equipment) ? $exercise->equipment : null;
                
                $exerciseStmt->bindParam(":category", $category);
                $exerciseStmt->bindParam(":muscle_group", $muscle);
                $exerciseStmt->bindParam(":description", $desc);
                $exerciseStmt->bindParam(":difficulty", $difficulty);
                $exerciseStmt->bindParam(":equipment", $equipment);
                
                // Campos numéricos
                $sets = isset($exercise->sets) ? intval($exercise->sets) : 1;
                $reps = isset($exercise->repetitions) ? intval($exercise->repetitions) : 1;
                $rest = isset($exercise->rest_time) ? intval($exercise->rest_time) : 60;
                
                $exerciseStmt->bindParam(":sets", $sets, PDO::PARAM_INT);
                $exerciseStmt->bindParam(":repetitions", $reps, PDO::PARAM_INT);
                $exerciseStmt->bindParam(":rest_time", $rest, PDO::PARAM_INT);
                $exerciseStmt->bindParam(":order_index", $index, PDO::PARAM_INT);
                
                $exerciseStmt->execute();
            }
        }
        
        $db->commit();
        
        // Obtener workout actualizado (misma query que create.php)
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
                    SELECT CONCAT(
                        '[',
                        GROUP_CONCAT(
                            CONCAT(
                                '{',
                                '\"id\":\"', e.id, '\",',
                                '\"name\":\"', REPLACE(REPLACE(e.name, '\"','\\\\\"'), '\\n', ' '), '\",',
                                '\"category\":\"', COALESCE(e.category,''), '\",',
                                '\"muscle_group\":\"', COALESCE(e.muscle_group,''), '\",',
                                '\"sets\":', COALESCE(e.sets,0), ',',
                                '\"repetitions\":', COALESCE(e.repetitions,0), ',',
                                '\"rest_time\":', COALESCE(e.rest_time,0), ',',
                                '\"description\":\"', COALESCE(REPLACE(REPLACE(e.description,'\"','\\\\\"'), '\\n',' '),''), '\",',
                                '\"difficulty\":\"', COALESCE(e.difficulty,''), '\",',
                                '\"equipment\":\"', COALESCE(e.equipment,''), '\",',
                                '\"order_index\":', COALESCE(e.order_index,0),
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
        $getStmt->bindParam(":id", $data->id);
        $getStmt->execute();
        
        $workout = $getStmt->fetch(PDO::FETCH_ASSOC);
        $workout['exercises'] = json_decode($workout['exercises'] ? $workout['exercises'] : '[]', true);
        $workout['is_public'] = (bool) $workout['is_public'];
        
        Response::success(['workout' => $workout], 'Rutina actualizada exitosamente');
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Update workout error: " . $e->getMessage());
    Response::serverError('[WorkoutAPI] - Error al actualizar rutina: ' . $e->getMessage());
}
?>