<?php
include("../config/bd.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_tarea = $_POST['id_tarea'] ?? null;
    $estado_actual = $_POST['estado_actual'] ?? null;

    if ($id_tarea && $estado_actual) {
        // Cambiar el estado en orden circular
        if ($estado_actual == 'pendiente') {
            $nuevo_estado = 'en progreso';
        } elseif ($estado_actual == 'en progreso') {
            $nuevo_estado = 'completada';
        } else {
            $nuevo_estado = 'pendiente';
        }

        $query = $conexion->prepare("UPDATE tareas SET estado = :estado WHERE id = :id_tarea");
        $query->bindParam(":estado", $nuevo_estado);
        $query->bindParam(":id_tarea", $id_tarea);
        $query->execute();
    }

    // Redirigir de vuelta al proyecto
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit;
}
?>

