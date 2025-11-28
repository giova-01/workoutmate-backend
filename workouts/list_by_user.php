<?php
/**
 * WorkoutMate - Get User Workouts Endpoint
 * 
 * GET /workouts/list_by_user/:userId
 */

require_once '../config/database.php';
require_once '../config/api_config.php';
require_once '../utils/Response.php';
require_once '../utils/Validator.php';

ApiConfig::setHeaders();

// Validar Master Key
if (!ApiConfig::validateMasterKey()) {
    Response::forbidden('[WorkoutAPI] - Master Key inválida');
}

// Solo permitir GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::badRequest('[WorkoutAPI] - Método no permitido');
}

try {
    // Obtener userId desde la URL
    $requestUri = $_SERVER['REQUEST_URI'];
    $uriParts = explode('/', trim($requestUri, '/'));
    $userId = end($uriParts);
    
    // Limpiar query string si existe
    if (strpos($userId, '?') !== false) {
        $userId = substr($userId, 0, strpos($userId, '?'));
    }

    if (empty($userId)) {
        Response::badRequest('[WorkoutAPI] - ID de usuario requerido');
    }

    $database = new Database();
    $db = $database->getConnection();

    // Query compatible con MariaDB (sin JSON_ARRAYAGG)
    $query = "
        SELECT 
            w.id,
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
        WHERE w.user_id = :user_id
        ORDER BY w.created_at DESC
    ";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();

    $workouts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Convertir exercises de string a array
    foreach ($workouts as &$workout) {
        if ($workout['exercises'] === null || $workout['exercises'] === '') {
            $workout['exercises'] = [];
        } else {
            $decoded = json_decode($workout['exercises'], true);
            $workout['exercises'] = $decoded ?: [];
        }
        $workout['is_public'] = (bool) $workout['is_public'];
    }

    Response::success(['workouts' => $workouts], 'Rutinas obtenidas exitosamente');

} catch (Exception $e) {
    error_log("Get workouts error: " . $e->getMessage());
    Response::serverError('[WorkoutAPI] - Error al obtener rutinas');
}