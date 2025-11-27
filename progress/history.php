<?php
/**
 * WorkoutMate - Get Progress History Endpoint
 * 
 * GET /progress/history/:userId?start_date=xxx&end_date=xxx
 */

require_once '../../config/database.php';
require_once '../../config/api_config.php';
require_once '../../utils/Response.php';
require_once '../../utils/Validator.php';

ApiConfig::setHeaders();

if (!ApiConfig::validateMasterKey()) {
    Response::forbidden('[ProgressAPI] - Master Key inválida');
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::badRequest('[ProgressAPI] - Método no permitido');
}

try {
    // Obtener userId de la URL
    $requestUri = $_SERVER['REQUEST_URI'];
    $uriParts = explode('/', trim($requestUri, '/'));
    $userId = end($uriParts);
    
    // Limpiar query string si existe
    if (strpos($userId, '?') !== false) {
        $userId = substr($userId, 0, strpos($userId, '?'));
    }
    
    if (empty($userId)) {
        Response::badRequest('[ProgressAPI] - ID de usuario requerido');
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener fechas de filtro (opcional)
    $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
    $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;
    
    // Construir query
    $query = "SELECT p.id, p.date, p.total_time, p.notes, p.created_at,
                     w.id as workout_id, w.name as workout_name, w.category,
                     (SELECT JSON_ARRAYAGG(
                         JSON_OBJECT(
                             'exercise_id', ep.exercise_id,
                             'completed', ep.completed,
                             'weight', ep.weight,
                             'actual_reps', ep.actual_reps,
                             'exercise_name', e.name
                         )
                     ) FROM exercise_progress ep 
                     INNER JOIN exercises e ON ep.exercise_id = e.id
                     WHERE ep.progress_id = p.id) as exercises_progress
              FROM progress p
              INNER JOIN workouts w ON p.workout_id = w.id
              WHERE p.user_id = :user_id";
    
    $params = [':user_id' => $userId];
    
    if ($startDate && Validator::date($startDate)) {
        $query .= " AND p.date >= :start_date";
        $params[':start_date'] = $startDate;
    }
    
    if ($endDate && Validator::date($endDate)) {
        $query .= " AND p.date <= :end_date";
        $params[':end_date'] = $endDate;
    }
    
    $query .= " ORDER BY p.date DESC, p.created_at DESC";
    
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Procesar resultados
    foreach ($history as &$entry) {
        $entry['exercises_progress'] = json_decode($entry['exercises_progress']) ?: [];
        
        // Convertir booleanos
        foreach ($entry['exercises_progress'] as &$ex) {
            $ex->completed = (bool) $ex->completed;
        }
    }
    
    Response::success([
        'history' => $history,
        'count' => count($history)
    ], 'Historial obtenido exitosamente');
    
} catch (Exception $e) {
    error_log("Get progress history error: " . $e->getMessage());
    Response::serverError('[ProgressAPI] - Error al obtener historial');
}
?>
