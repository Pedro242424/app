<?php
session_start();
include("../config/bd.php");

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// Datos del usuario
$id_usuario = $_SESSION['id_usuario'];
$correo_usuario = trim(strtolower($_SESSION['usuario'])); // correo seguro

// 1️⃣ Traer tareas pendientes asignadas al usuario
$query_tareas = $conexion->prepare("
    SELECT * FROM tareas
    WHERE id_asignado = :id_usuario
    AND estado = 'pendiente'
    ORDER BY fecha_limite ASC
");
$query_tareas->bindParam(":id_usuario", $id_usuario);
$query_tareas->execute();
$tareas_pendientes = $query_tareas->fetchAll(PDO::FETCH_ASSOC);

// 2️⃣ Traer proyectos donde el usuario es miembro (comparación segura por correo)
$query_proyectos = $conexion->prepare("
    SELECT DISTINCT p.*
    FROM proyectos p
    INNER JOIN miembros m ON p.id = m.id_proyecto
    WHERE LOWER(TRIM(m.correo_miembro)) = :correo
    ORDER BY p.id DESC
");
$query_proyectos->bindParam(":correo", $correo_usuario);
$query_proyectos->execute();
$proyectos = $query_proyectos->fetchAll(PDO::FETCH_ASSOC);

include("../includes/header.php");
?>

<div class="container-fluid mt-5" style="background-color: #f0f2f5; min-height: 100vh;">
    <!-- Saludo -->
    <div class="text-center mb-4">
        <h2 class="fw-bold text-primary">Hola <?= htmlspecialchars($correo_usuario); ?>!</h2>
        <p class="text-muted">Este es tu panel de control. Revisa tus tareas y proyectos activos.</p>
    </div>

    <!-- Tareas pendientes -->
    <h4 class="mb-3">📝 Tareas pendientes</h4>
    <?php if (!empty($tareas_pendientes)): ?>
        <div class="row g-4 mb-5">
            <?php foreach ($tareas_pendientes as $t): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card shadow-sm p-3 card-hover">
                        <h6 class="fw-bold"><?= htmlspecialchars($t['titulo']); ?></h6>
                        <p class="text-muted mb-1"><?= nl2br(htmlspecialchars($t['descripcion'])); ?></p>
                        <small class="text-secondary">Fecha límite: <?= htmlspecialchars($t['fecha_limite']); ?></small>
                        <a href="ver_tarea.php?id=<?= $t['id']; ?>" class="btn btn-sm btn-primary w-100 mt-2">
                            <i class="bi bi-eye"></i> Ver tarea
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No tienes tareas pendientes. ¡Buen trabajo! 🎉</div>
    <?php endif; ?>

    <!-- Proyectos activos -->
    <h4 class="mb-3">📁 Proyectos activos</h4>
    <?php if (!empty($proyectos)): ?>
        <div class="row g-4 mb-5">
            <?php foreach ($proyectos as $p): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card shadow-sm p-3 card-hover">
                        <h5 class="fw-bold"><?= htmlspecialchars($p['nombre']); ?></h5>
                        <p class="text-muted mb-1"><?= nl2br(htmlspecialchars($p['descripcion'])); ?></p>
                        <small class="text-secondary">Fecha límite: <?= htmlspecialchars($p['fecha_limite']); ?></small>
                        <a href="proyecto_detalle.php?id=<?= $p['id']; ?>" class="btn btn-sm btn-success w-100 mt-2">
                            <i class="bi bi-eye"></i> Ver proyecto
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">No tienes proyectos activos. Crea uno o únete a un proyecto existente.</div>
    <?php endif; ?>

</div>

<!-- Estilos personalizados -->
<style>
.card-hover {
    border-radius: 1rem;
    transition: transform 0.3s, box-shadow 0.3s;
}
.card-hover:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}
</style>

<?php include("../includes/footer.php"); ?>





