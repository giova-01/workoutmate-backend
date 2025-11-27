<?php
/**
 * WorkoutMate - Create Report Endpoint
 * 
 * POST /admin/report
 */

require_once '../config/database.php';
require_once '../config/api_config.php';
require_once '../utils/Response.php';
require_once '../utils/Validator.php';
require_once '../utils/UUID.php';

ApiConfig::setHeaders();

if (!ApiConfig::validateMasterKey()) {
    Response::forbidden('[AdminAPI] - Master Key inválida');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::badRequest('[AdminAPI] - Método no permitido');
}

try {
    $data = json_decode(file_get_contents("php://input"));
    
    // Validar campos requeridos
    $required = Validator::requiredFields($data, ['type', 'description']);
    if ($required !== true) {
        Response::badRequest('[AdminAPI] - Campos requeridos faltantes: ' . implode(', ', $required));
    }
    
    // Validar tipo de reporte
    $validTypes = ['USER_REPORT', 'SYSTEM_ERROR', 'CONTENT_ISSUE'];
    if (!Validator::inArray($data->type, $validTypes)) {
        Response::badRequest('[AdminAPI] - Tipo de reporte inválido');
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar usuario si se proporciona
    $userId = isset($data->user_id) ? $data->user_id : null;
    
    if ($userId) {
        $checkUserQuery = "SELECT id FROM users WHERE id = :id LIMIT 1";
        $checkUserStmt = $db->prepare($checkUserQuery);
        $checkUserStmt->bindParam(":id", $userId);
        $checkUserStmt->execute();
        
        if ($checkUserStmt->rowCount() === 0) {
            Response::notFound('[AdminAPI] - Usuario no encontrado');
        }
    }
    
    // Crear reporte
    $reportId = UUID::generate();
    
    $insertQuery = "INSERT INTO reports (id, user_id, type, description, status) 
                   VALUES (:id, :user_id, :type, :description, 'PENDING')";
    
    $insertStmt = $db->prepare($insertQuery);
    $insertStmt->bindParam(":id", $reportId);
    $insertStmt->bindParam(":user_id", $userId);
    $insertStmt->bindParam(":type", $data->type);
    $insertStmt->bindParam(":description", $data->description);
    
    $insertStmt->execute();
    
    Response::created(['report_id' => $reportId], 'Reporte creado exitosamente');
    
} catch (Exception $e) {
    error_log("Create report error: " . $e->getMessage());
    Response::serverError('[AdminAPI] - Error al crear reporte');
}
?>
