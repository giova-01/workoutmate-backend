<?php
/**
 * WorkoutMate - Search Workouts Endpoint
 * 
 * GET /workouts/search?query=xxx&category=xxx
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
    $database = new Database();
    $db = $database->getConnection();
    
    // Obtener parámetros de búsqueda
    $query = isset($_GET['query']) ? $_GET['query'] : '';
    $category = isset($_GET['category']) ? $_GET['category'] : '';
    $userId = isset($_GET['user_id']) ? $_GET['user_id'] : '';
    
    // Construir query SQL (compatible con MariaDB)
    $sql = "SELECT w.id, w.name, w.category, w.user_id, w.is_public, 
                   w.created_at, w.updated_at,
                   u.first_name, u.last_name,
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
                   ) as exercises
            FROM workouts w 
            INNER JOIN users u ON w.user_id = u.id
            WHERE w.is_public = true";
    
    $params = [];
    
    // Filtrar por búsqueda de texto
    if (!empty($query)) {
        $sql .= " AND (w.name LIKE :query OR EXISTS (
                    SELECT 1 FROM exercises e 
                    WHERE e.workout_id = w.id AND e.name LIKE :query
                  ))";
        $params[':query'] = '%' . $query . '%';
    }
    
    // Filtrar por categoría
    if (!empty($category)) {
        $sql .= " AND w.category = :category";
        $params[':category'] = $category;
    }
    
    // Si se proporciona user_id, también incluir workouts privados del usuario
    if (!empty($userId)) {
        $sql .= " OR w.user_id = :user_id";
        $params[':user_id'] = $userId;
    }
    
    $sql .= " ORDER BY w.created_at DESC LIMIT 50";
    
    $stmt = $db->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    
    $workouts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Procesar resultados
    foreach ($workouts as &$workout) {
        if ($workout['exercises'] === null || $workout['exercises'] === '') {
            $workout['exercises'] = [];
        } else {
            $decoded = json_decode($workout['exercises'], true);
            $workout['exercises'] = $decoded ?: [];
        }
        $workout['is_public'] = (bool) $workout['is_public'];
        $workout['author'] = [
            'first_name' => $workout['first_name'],
            'last_name' => $workout['last_name']
        ];
        unset($workout['first_name'], $workout['last_name']);
    }
    
    Response::success([
        'workouts' => $workouts,
        'count' => count($workouts)
    ], 'Búsqueda completada');
    
} catch (Exception $e) {
    error_log("Search workouts error: " . $e->getMessage());
    Response::serverError('[WorkoutAPI] - Error al buscar rutinas');
}
?>