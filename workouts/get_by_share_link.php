<?php
/**
 * WorkoutMate - Get Workout by Share Link
 * 
 * GET /workouts/get_by_share_link/:shareLink
 */

require_once '../config/database.php';
require_once '../config/api_config.php';
require_once '../utils/Response.php';

ApiConfig::setHeaders();

if (!ApiConfig::validateMasterKey()) {
    Response::forbidden('[WorkoutAPI] - Master Key inválida');
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::badRequest('[WorkoutAPI] - Método no permitido');
}

try {
    $requestUri = $_SERVER['REQUEST_URI'];
    $uriParts = explode('/', trim($requestUri, '/'));
    $shareLink = end($uriParts);
    
    if (strpos($shareLink, '?') !== false) {
        $shareLink = substr($shareLink, 0, strpos($shareLink, '?'));
    }
    
    if (empty($shareLink)) {
        Response::badRequest('[WorkoutAPI] - Share link requerido');
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener workout por share_link
    $query = "SELECT w.*, u.first_name, u.last_name 
              FROM workouts w 
              LEFT JOIN users u ON w.user_id = u.id
              WHERE w.share_link = :share_link AND w.is_public = true 
              LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":share_link", $shareLink);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        Response::notFound('[WorkoutAPI] - Rutina no encontrada o no es pública');
    }
    
    $workout = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Obtener ejercicios de la tabla exercises
    $exercisesQuery = "SELECT * FROM exercises 
                       WHERE workout_id = :workout_id 
                       ORDER BY order_index ASC";
    $exercisesStmt = $db->prepare($exercisesQuery);
    $exercisesStmt->bindParam(":workout_id", $workout['id']);
    $exercisesStmt->execute();
    $exercises = $exercisesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $workout['exercises'] = $exercises;
    $workout['owner_name'] = trim($workout['first_name'] . ' ' . $workout['last_name']);
    unset($workout['first_name'], $workout['last_name']);
    
    Response::success(['workout' => $workout], 'Rutina obtenida exitosamente');
    
} catch (Exception $e) {
    error_log("Get by share link error: " . $e->getMessage());
    Response::serverError('[WorkoutAPI] - Error al obtener rutina');
}
?>