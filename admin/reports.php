<?php
/**
 * WorkoutMate - Get Reports Endpoint (Admin)
 * 
 * GET /admin/reports?status=xxx&admin_id=xxx
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
    
    // Construir query
    $query = "SELECT r.*, 
                     u.email as user_email, u.first_name as user_first_name, u.last_name as user_last_name,
                     a.first_name as resolved_by_first_name, a.last_name as resolved_by_last_name
              FROM reports r
              LEFT JOIN users u ON r.user_id = u.id
              LEFT JOIN users a ON r.resolved_by = a.id
              WHERE 1=1";
    
    $params = [];
    
    // Filtrar por estado si se proporciona
    $status = isset($_GET['status']) ? $_GET['status'] : '';
    if (!empty($status)) {
        $query .= " AND r.status = :status";
        $params[':status'] = $status;
    }
    
    $query .= " ORDER BY 
                CASE r.status 
                    WHEN 'PENDING' THEN 1
                    WHEN 'IN_PROGRESS' THEN 2
                    WHEN 'RESOLVED' THEN 3
                    WHEN 'REJECTED' THEN 4
                END,
                r.created_at DESC";
    
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    
    $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear respuesta
    foreach ($reports as &$report) {
        if ($report['user_email']) {
            $report['user'] = [
                'email' => $report['user_email'],
                'first_name' => $report['user_first_name'],
                'last_name' => $report['user_last_name']
            ];
        } else {
            $report['user'] = null;
        }
        
        if ($report['resolved_by']) {
            $report['resolved_by_name'] = $report['resolved_by_first_name'] . ' ' . $report['resolved_by_last_name'];
        }
        
        unset($report['user_email'], $report['user_first_name'], $report['user_last_name']);
        unset($report['resolved_by_first_name'], $report['resolved_by_last_name']);
    }
    
    Response::success([
        'reports' => $reports,
        'total' => count($reports)
    ], 'Reportes obtenidos exitosamente');
    
} catch (Exception $e) {
    error_log("Get reports error: " . $e->getMessage());
    Response::serverError('[AdminAPI] - Error al obtener reportes');
}
?>
