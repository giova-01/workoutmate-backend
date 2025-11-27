<?php
/**
 * WorkoutMate - Get Weekly Progress Endpoint
 * 
 * GET /progress/weekly/:userId
 */

require_once '../../config/database.php';
require_once '../../config/api_config.php';
require_once '../../utils/Response.php';

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
    $uriParts = explode('/', $requestUri);
    $userId = end($uriParts);
    
    if (empty($userId)) {
        Response::badRequest('[ProgressAPI] - ID de usuario requerido');
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener progreso de los últimos 7 días
    $query = "SELECT 
                DATE(p.date) as date,
                COUNT(DISTINCT p.id) as workouts_completed,
                SUM(p.total_time) as total_time,
                GROUP_CONCAT(DISTINCT w.name SEPARATOR ', ') as workout_names
              FROM progress p
              INNER JOIN workouts w ON p.workout_id = w.id
              WHERE p.user_id = :user_id 
              AND p.date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
              GROUP BY DATE(p.date)
              ORDER BY date DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $userId);
    $stmt->execute();
    
    $weeklyProgress = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular estadísticas
    $totalWorkouts = 0;
    $totalTime = 0;
    
    foreach ($weeklyProgress as $day) {
        $totalWorkouts += intval($day['workouts_completed']);
        $totalTime += intval($day['total_time']);
    }
    
    $avgTimePerDay = count($weeklyProgress) > 0 ? round($totalTime / count($weeklyProgress)) : 0;
    
    Response::success([
        'weekly_data' => $weeklyProgress,
        'summary' => [
            'days_active' => count($weeklyProgress),
            'total_workouts' => $totalWorkouts,
            'total_time' => $totalTime,
            'avg_time_per_day' => $avgTimePerDay
        ]
    ], 'Progreso semanal obtenido exitosamente');
    
} catch (Exception $e) {
    error_log("Get weekly progress error: " . $e->getMessage());
    Response::serverError('[ProgressAPI] - Error al obtener progreso semanal');
}
?>
