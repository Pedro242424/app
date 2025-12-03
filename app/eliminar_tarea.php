<?php
/**
 * ELIMINAR TAREA
 * Elimina una tarea de la base de datos
 */

session_start();
include("../config/bd.php");

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
    exit;
}

// Obtener ID de la tarea
$id_tarea = $_POST['id'] ?? null;

if (!$id_tarea) {
    echo json_encode(['success' => false, 'mensaje' => 'ID de tarea no proporcionado']);
    exit;
}

try {
    // Eliminar la tarea
    $query = $conexion->prepare("DELETE FROM tareas WHERE id = :id");
    $query->bindParam(":id", $id_tarea);
    $query->execute();
    
    echo json_encode([
        'success' => true,
        'mensaje' => 'Tarea eliminada correctamente'
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error al eliminar: ' . $e->getMessage()
    ]);
}
?>