<?php
/**
 * ACTUALIZAR ESTADO DE TAREA - Para proyecto_detalle.php
 * Permite cambiar el estado de cualquier tarea del proyecto
 * (sin restricción de que sea el usuario asignado)
 */

session_start();
include("../config/bd.php");

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
    exit;
}

// Obtener datos del POST
$id_tarea = $_POST['id'] ?? null;
$nuevo_estado = $_POST['estado'] ?? null;

// Log para debug
error_log("ID Tarea: " . $id_tarea);
error_log("Nuevo Estado: " . $nuevo_estado);

// Validar datos
if (!$id_tarea || !$nuevo_estado) {
    echo json_encode(['success' => false, 'mensaje' => 'Datos incompletos']);
    exit;
}

// Validar que el estado sea válido (3 estados)
$estados_validos = ['pendiente', 'en_proceso', 'completada'];
if (!in_array($nuevo_estado, $estados_validos)) {
    echo json_encode(['success' => false, 'mensaje' => 'Estado inválido: ' . $nuevo_estado]);
    exit;
}

try {
    // Actualizar el estado de la tarea (SIN verificar usuario)
    $query_actualizar = $conexion->prepare("
        UPDATE tareas 
        SET estado = :estado 
        WHERE id = :id
    ");
    $query_actualizar->bindParam(":estado", $nuevo_estado);
    $query_actualizar->bindParam(":id", $id_tarea);
    $resultado = $query_actualizar->execute();
    
    error_log("Resultado actualización: " . ($resultado ? 'true' : 'false'));
    
    echo json_encode([
        'success' => true, 
        'mensaje' => 'Tarea actualizada correctamente',
        'nuevo_estado' => $nuevo_estado
    ]);
    
} catch (PDOException $e) {
    error_log("Error PDO: " . $e->getMessage());
    echo json_encode(['success' => false, 'mensaje' => 'Error: ' . $e->getMessage()]);
}
?>
