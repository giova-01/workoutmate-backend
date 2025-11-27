<?php
/**
 * WorkoutMate - Generate Share Link Endpoint
 * 
 * POST /workouts/share
 */

require_once '../config/database.php';
require_once '../config/api_config.php';
require_once '../utils/Response.php';
require_once '../utils/Validator.php';
require_once '../utils/UUID.php';

ApiConfig::setHeaders();

if (!ApiConfig::validateMasterKey()) {
    Response::forbidden('[WorkoutAPI] - Master Key inválida');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::badRequest('[WorkoutAPI] - Método no permitido');
}

try {
    $data = json_decode(file_get_contents("php://input"));
    
    // Validar campos requeridos
    $required = Validator::requiredFields($data, ['workout_id', 'user_id']);
    if ($required !== true) {
        Response::badRequest('[WorkoutAPI] - Campos requeridos faltantes: ' . implode(', ', $required));
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Verificar que el workout existe y pertenece al usuario
    $checkQuery = "SELECT id, share_link FROM workouts WHERE id = :id AND user_id = :user_id LIMIT 1";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->bindParam(":id", $data->workout_id);
    $checkStmt->bindParam(":user_id", $data->user_id);
    $checkStmt->execute();
    
    if ($checkStmt->rowCount() === 0) {
        Response::notFound('[WorkoutAPI] - Rutina no encontrada o no autorizado');
    }
    
    $workout = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    // Si ya tiene share_link, devolverlo
    if (!empty($workout['share_link'])) {
        Response::success([
            'share_link' => $workout['share_link'],
            'full_url' => 'https://workoutmate.app/share/' . $workout['share_link']
        ], 'Link de compartir existente');
    }
    
    // Generar nuevo share_link
    $shareLink = UUID::generateShortCode();
    
    // Verificar que no existe (muy improbable, pero por seguridad)
    $checkLinkQuery = "SELECT id FROM workouts WHERE share_link = :share_link LIMIT 1";
    $checkLinkStmt = $db->prepare($checkLinkQuery);
    $checkLinkStmt->bindParam(":share_link", $shareLink);
    $checkLinkStmt->execute();
    
    // Si existe, generar otro
    while ($checkLinkStmt->rowCount() > 0) {
        $shareLink = UUID::generateShortCode();
        $checkLinkStmt->execute();
    }
    
    // Actualizar workout con share_link
    $updateQuery = "UPDATE workouts SET share_link = :share_link, is_public = true WHERE id = :id";
    $updateStmt = $db->prepare($updateQuery);
    $updateStmt->bindParam(":share_link", $shareLink);
    $updateStmt->bindParam(":id", $data->workout_id);
    $updateStmt->execute();
    
    // Registrar la compartición
    $shareId = UUID::generate();
    $shareQuery = "INSERT INTO workout_shares (id, workout_id, shared_by) 
                   VALUES (:id, :workout_id, :shared_by)";
    $shareStmt = $db->prepare($shareQuery);
    $shareStmt->bindParam(":id", $shareId);
    $shareStmt->bindParam(":workout_id", $data->workout_id);
    $shareStmt->bindParam(":shared_by", $data->user_id);
    $shareStmt->execute();
    
    Response::success([
        'share_link' => $shareLink,
        'full_url' => 'https://workoutmate.app/share/' . $shareLink
    ], 'Link de compartir generado exitosamente');
    
} catch (Exception $e) {
    error_log("Generate share link error: " . $e->getMessage());
    Response::serverError('[WorkoutAPI] - Error al generar link de compartir');
}
?>
