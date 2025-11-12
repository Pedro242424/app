<?php
session_start();
include("../config/bd.php");

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

$id_proyecto = $_GET['id'] ?? null;
if (!$id_proyecto) {
    header("Location: proyectos.php");
    exit;
}

// Obtener proyecto
$query = $conexion->prepare("SELECT * FROM proyectos WHERE id = :id_proyecto LIMIT 1");
$query->bindParam(":id_proyecto", $id_proyecto);
$query->execute();
$proyecto = $query->fetch(PDO::FETCH_ASSOC);

if (!$proyecto) {
    header("Location: proyectos.php");
    exit;
}

// Obtener tareas con nombre del usuario asignado
$queryTareas = $conexion->prepare("
    SELECT t.*, u.nombre AS nombre_asignado
    FROM tareas t
    LEFT JOIN usuarios u ON t.id_asignado = u.id
    WHERE t.id_proyecto = :id_proyecto
    ORDER BY t.id DESC
");
$queryTareas->bindParam(":id_proyecto", $id_proyecto);
$queryTareas->execute();
$tareas = $queryTareas->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include("../includes/header.php"); ?>

<div class="container mt-4">
    <h3 class="mb-2"><?= htmlspecialchars($proyecto['nombre']); ?></h3>
    <p><?= htmlspecialchars($proyecto['descripcion']); ?></p>
    <p><strong><i class="bi bi-calendar-event"></i> Fecha límite:</strong> <?= htmlspecialchars($proyecto['fecha_limite']); ?></p>

    <a href="crear_tarea.php?id_proyecto=<?= $proyecto['id']; ?>" class="btn btn-success mb-3">
        <i class="bi bi-plus-circle"></i> Nueva tarea
    </a>

    <?php if (count($tareas) > 0): ?>
        <ul class="list-group">
            <?php foreach ($tareas as $t): ?>
                <?php
                    $estado = $t['estado'] ?? 'pendiente';
                    $textoEstado = ucfirst($estado);
                    $colorEstado = match ($estado) {
                        'pendiente' => 'bg-secondary',
                        'en progreso' => 'bg-warning text-dark',
                        'completada' => 'bg-success',
                        default => 'bg-light text-dark'
                    };

                    $prioridad = $t['prioridad'] ?? 'media';
                    $colorPrioridad = match ($prioridad) {
                        'alta' => 'bg-danger',
                        'baja' => 'bg-success',
                        default => 'bg-warning text-dark'
                    };

                    $asignado = $t['nombre_asignado'] ? htmlspecialchars($t['nombre_asignado']) : 'Sin asignar';
                ?>
                <li class="list-group-item d-flex justify-content-between align-items-start flex-wrap">
                    <div class="col-md-9">
                        <strong class="d-block mb-1">
                            <i class="bi bi-list-task"></i> <?= htmlspecialchars($t['titulo']); ?>
                        </strong>
                        <small class="text-muted d-block mb-1"><?= htmlspecialchars($t['descripcion']); ?></small>
                        <small class="d-block mb-1">
                            <i class="bi bi-calendar3"></i> Fecha límite: <?= htmlspecialchars($t['fecha_limite']); ?>
                        </small>
                        <span class="badge <?= $colorPrioridad; ?> me-1">
                            <i class="bi bi-flag-fill"></i> Prioridad: <?= ucfirst($prioridad); ?>
                        </span>
                        <span class="badge <?= $colorEstado; ?> me-1">
                            <i class="bi bi-check-circle-fill"></i> Estado: <?= $textoEstado; ?>
                        </span>
                        <span class="badge bg-info text-dark">
                            <i class="bi bi-person"></i> <?= $asignado; ?>
                        </span>
                    </div>

                    <div class="col-md-3 text-end">
                        <form method="post" action="estado_tarea.php" style="display:inline;">
                            <input type="hidden" name="id_tarea" value="<?= $t['id']; ?>">
                            <input type="hidden" name="estado_actual" value="<?= $estado; ?>">
                            <button type="submit" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-arrow-repeat"></i> Cambiar estado
                            </button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle-fill"></i> Aún no hay tareas en este proyecto.
        </div>
    <?php endif; ?>
</div>

<?php include("../includes/footer.php"); ?>
