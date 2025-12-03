<?php
/**
 * ELIMINAR TAREA
 * Elimina una tarea específica del sistema
 */

session_start();
include("../config/bd.php");

// Verificar sesión activa
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
    exit;
}

$id_usuario = $_SESSION['id_usuario'];
$id_tarea = $_POST['id'] ?? null;

// Validar que se recibió el ID
if (!$id_tarea) {
    echo json_encode(['success' => false, 'mensaje' => 'ID de tarea no proporcionado']);
    exit;
}

try {
    // Verificar que la tarea esté asignada al usuario actual
    $query_verificar = $conexion->prepare("
        SELECT id FROM tareas 
        WHERE id = :id AND id_asignado = :id_usuario
    ");
    $query_verificar->bindParam(":id", $id_tarea);
    $query_verificar->bindParam(":id_usuario", $id_usuario);
    $query_verificar->execute();
    
    // Si no existe o no está asignada al usuario, denegar
    if ($query_verificar->rowCount() === 0) {
        echo json_encode(['success' => false, 'mensaje' => 'Tarea no encontrada o no autorizada']);
        exit;
    }
    
    // Eliminar la tarea
    $query_eliminar = $conexion->prepare("DELETE FROM tareas WHERE id = :id");
    $query_eliminar->bindParam(":id", $id_tarea);
    $query_eliminar->execute();
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Tarea eliminada correctamente'
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'mensaje' => 'Error: ' . $e->getMessage()]);
}
?>