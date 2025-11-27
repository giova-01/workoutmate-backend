<?php
/**
 * WorkoutMate - Get User Workouts Endpoint
 * 
 * GET /workouts/user/:userId
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/api_config.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';

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
    $uriParts = explode('/', $requestUri);
    $userId = end($uriParts);

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
                                '\"name\":\"', REPLACE(e.name, '\"', '\\\"'), '\",',
                                '\"sets\":', e.sets, ',',
                                '\"repetitions\":', e.repetitions, ',',
                                '\"rest_time\":', e.rest_time, ',',
                                '\"notes\":\"', REPLACE(e.notes, '\"', '\\\"'), '\",',
                                '\"order_index\":', e.order_index,
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

    // Convertir JSON text a array
    foreach ($workouts as &$workout) {
        $decoded = json_decode($workout['exercises'], true);
        $workout['exercises'] = $decoded ?: [];
        $workout['is_public'] = (bool) $workout['is_public'];
    }

    Response::success(['workouts' => $workouts], 'Rutinas obtenidas exitosamente');

} catch (Exception $e) {
    error_log("Get workouts error: " . $e->getMessage());
    Response::serverError('[WorkoutAPI] - Error al obtener rutinas');
}
