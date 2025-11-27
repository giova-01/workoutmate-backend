<?php
/**
 * WorkoutMate - Delete User Endpoint (Admin)
 * 
 * DELETE /admin/delete_user
 */

require_once '../config/database.php';
require_once '../config/api_config.php';
require_once '../utils/Response.php';

ApiConfig::setHeaders();

if (!ApiConfig::validateMasterKey()) {
    Response::forbidden('[AdminAPI] - Master Key inválida');
}

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    Response::badRequest('[AdminAPI] - Método no permitido');
}

try {
    // Obtener userId de la URL
    $requestUri = $_SERVER['REQUEST_URI'];
    $uriParts = explode('/', $requestUri);
    $userId = end($uriParts);
    
    if (empty($userId)) {
        Response::badRequest('[AdminAPI] - ID de usuario requerido');
    }
    
    // Obtener admin_id del body
    $data = json_decode(file_get_contents("php://input"));
    
    if (!isset($data->admin_id)) {
        Response::unauthorized('[AdminAPI] - ID de administrador requerido');
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar rol de admin
    $checkAdminQuery = "SELECT role FROM users WHERE id = :id LIMIT 1";
    $checkAdminStmt = $db->prepare($checkAdminQuery);
    $checkAdminStmt->bindParam(":id", $data->admin_id);
    $checkAdminStmt->execute();
    
    if ($checkAdminStmt->rowCount() === 0) {
        Response::unauthorized('[AdminAPI] - Administrador no encontrado');
    }
    
    $admin = $checkAdminStmt->fetch(PDO::FETCH_ASSOC);
    if ($admin['role'] !== 'admin') {
        Response::forbidden('[AdminAPI] - Acceso denegado. Se requiere rol de administrador');
    }
    
    // No permitir que un admin se elimine a sí mismo
    if ($userId === $data->admin_id) {
        Response::badRequest('[AdminAPI] - No puedes eliminar tu propia cuenta');
    }
    
    // Verificar que el usuario a eliminar existe
    $checkUserQuery = "SELECT id, role FROM users WHERE id = :id LIMIT 1";
    $checkUserStmt = $db->prepare($checkUserQuery);
    $checkUserStmt->bindParam(":id", $userId);
    $checkUserStmt->execute();
    
    if ($checkUserStmt->rowCount() === 0) {
        Response::notFound('[AdminAPI] - Usuario no encontrado');
    }
    
    // Eliminar usuario (CASCADE eliminará sus workouts, progress, etc.)
    $deleteQuery = "DELETE FROM users WHERE id = :id";
    $deleteStmt = $db->prepare($deleteQuery);
    $deleteStmt->bindParam(":id", $userId);
    $deleteStmt->execute();
    
    Response::success(null, 'Usuario eliminado exitosamente');
    
} catch (Exception $e) {
    error_log("Delete user error: " . $e->getMessage());
    Response::serverError('[AdminAPI] - Error al eliminar usuario');
}
?>
