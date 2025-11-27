<?php
/**
 * WorkoutMate - Get All Users Endpoint (Admin)
 * 
 * GET /admin/users
 */

require_once '../config/database.php';
require_once '../config/api_config.php';
require_once '../utils/Response.php';

ApiConfig::setHeaders();

if (!ApiConfig::validateMasterKey()) {
    Response::forbidden('[AdminAPI] - Master Key inválida');
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::badRequest('[AdminAPI] - Método no permitido');
}

try {
    // Verificar que el usuario que hace la petición es admin
    $adminId = isset($_GET['admin_id']) ? $_GET['admin_id'] : '';
    
    if (empty($adminId)) {
        Response::unauthorized('[AdminAPI] - ID de administrador requerido');
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar rol de admin
    $checkAdminQuery = "SELECT role FROM users WHERE id = :id LIMIT 1";
    $checkAdminStmt = $db->prepare($checkAdminQuery);
    $checkAdminStmt->bindParam(":id", $adminId);
    $checkAdminStmt->execute();
    
    if ($checkAdminStmt->rowCount() === 0) {
        Response::unauthorized('[AdminAPI] - Usuario no encontrado');
    }
    
    $admin = $checkAdminStmt->fetch(PDO::FETCH_ASSOC);
    if ($admin['role'] !== 'admin') {
        Response::forbidden('[AdminAPI] - Acceso denegado. Se requiere rol de administrador');
    }
    
    // Obtener todos los usuarios con estadísticas
    $query = "SELECT 
                u.id, u.email, u.first_name, u.last_name, u.role, u.created_at,
                (SELECT COUNT(*) FROM workouts WHERE user_id = u.id) as total_workouts,
                (SELECT COUNT(*) FROM progress WHERE user_id = u.id) as total_progress_entries,
                (SELECT MAX(date) FROM progress WHERE user_id = u.id) as last_activity
              FROM users u
              ORDER BY u.created_at DESC";
    
    $stmt = $db->query($query);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convertir valores numéricos
    foreach ($users as &$user) {
        $user['total_workouts'] = intval($user['total_workouts']);
        $user['total_progress_entries'] = intval($user['total_progress_entries']);
    }
    
    Response::success([
        'users' => $users,
        'total' => count($users)
    ], 'Usuarios obtenidos exitosamente');
    
} catch (Exception $e) {
    error_log("Get all users error: " . $e->getMessage());
    Response::serverError('[AdminAPI] - Error al obtener usuarios');
}
?>
