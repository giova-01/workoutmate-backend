<?php
/**
 * WorkoutMate - Update Report Status Endpoint (Admin)
 * 
 * PUT /admin/report/status
 */

require_once '../config/database.php';
require_once '../config/api_config.php';
require_once '../utils/Response.php';
require_once '../utils/Validator.php';

ApiConfig::setHeaders();

if (!ApiConfig::validateMasterKey()) {
    Response::forbidden('[AdminAPI] - Master Key inválida');
}

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    Response::badRequest('[AdminAPI] - Método no permitido');
}

try {
    $data = json_decode(file_get_contents("php://input"));
    
    // Validar campos requeridos
    $required = Validator::requiredFields($data, ['report_id', 'admin_id', 'status']);
    if ($required !== true) {
        Response::badRequest('[AdminAPI] - Campos requeridos faltantes: ' . implode(', ', $required));
    }
    
    // Validar status
    $validStatuses = ['PENDING', 'IN_PROGRESS', 'RESOLVED', 'REJECTED'];
    if (!Validator::inArray($data->status, $validStatuses)) {
        Response::badRequest('[AdminAPI] - Estado de reporte inválido');
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
    
    // Verificar que el reporte existe
    $checkReportQuery = "SELECT id FROM reports WHERE id = :id LIMIT 1";
    $checkReportStmt = $db->prepare($checkReportQuery);
    $checkReportStmt->bindParam(":id", $data->report_id);
    $checkReportStmt->execute();
    
    if ($checkReportStmt->rowCount() === 0) {
        Response::notFound('[AdminAPI] - Reporte no encontrado');
    }
    
    // Actualizar reporte
    $updateQuery = "UPDATE reports 
                   SET status = :status, 
                       resolved_by = :resolved_by,
                       resolution_notes = :resolution_notes,
                       resolved_at = CASE WHEN :status IN ('RESOLVED', 'REJECTED') THEN CURRENT_TIMESTAMP ELSE NULL END
                   WHERE id = :id";
    
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(":id", $data->report_id);
    $updateStmt->bindParam(":status", $data->status);
    $updateStmt->bindParam(":resolved_by", $data->admin_id);
    
    $resolutionNotes = isset($data->resolution_notes) ? $data->resolution_notes : null;
    $updateStmt->bindParam(":resolution_notes", $resolutionNotes);
    
    $updateStmt->execute();
    
    Response::success(null, 'Estado de reporte actualizado exitosamente');
    
} catch (Exception $e) {
    error_log("Update report status error: " . $e->getMessage());
    Response::serverError('[AdminAPI] - Error al actualizar estado de reporte');
}
?>
