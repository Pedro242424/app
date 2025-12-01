<?php
/**
 * ACTUALIZAR TAREA - Cambiar estado
 * API para actualizar el estado de una tarea (pendiente/completada)
 */

session_start();
include("../config/bd.php");

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
    exit;
}

$id_usuario = $_SESSION['id_usuario'];

// Obtener datos del POST
$id_tarea = $_POST['id'] ?? null;
$nuevo_estado = $_POST['estado'] ?? null;

// Validar datos
if (!$id_tarea || !$nuevo_estado) {
    echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
    exit;
}

// Validar que el estado sea válido
if (!in_array($nuevo_estado, ['pendiente', 'completada'])) {
    echo json_encode(['success' => false, 'mensaje' => 'Estado inválido']);
    exit;
}

try {
    // Verificar que la tarea pertenezca al usuario
    $query_verificar = $conexion->prepare("
        SELECT id FROM tareas 
        WHERE id = :id AND id_asignado = :id_usuario
    ");
    $query_verificar->bindParam(":id", $id_tarea);
    $query_verificar->bindParam(":id_usuario", $id_usuario);
    $query_verificar->execute();
    
    if ($query_verificar->rowCount() === 0) {
        echo json_encode(['success' => false, 'mensaje' => 'Tarea no encontrada o no autorizada']);
        exit;
    }
    
    // Actualizar el estado de la tarea
    $query_actualizar = $conexion->prepare("
        UPDATE tareas 
        SET estado = :estado 
        WHERE id = :id
    ");
    $query_actualizar->bindParam(":estado", $nuevo_estado);
    $query_actualizar->bindParam(":id", $id_tarea);
    $query_actualizar->execute();
    
    echo json_encode([
        'success' => true, 
        'mensaje' => 'Tarea actualizada correctamente',
        'nuevo_estado' => $nuevo_estado
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'mensaje' => 'Error: ' . $e->getMessage()]);
}
?>
