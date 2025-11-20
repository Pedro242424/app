<?php
session_start();
include("../config/bd.php");

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

$id_usuario = $_SESSION['id_usuario'];
$query = $conexion->prepare("SELECT * FROM proyectos WHERE id_usuario = :id_usuario ORDER BY id DESC");
$query->bindParam(":id_usuario", $id_usuario);
$query->execute();
$proyectos = $query->fetchAll(PDO::FETCH_ASSOC);

include("../includes/header.php");
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h3>Mis proyectos</h3>
    <a href="crear_proyecto.php" class="btn btn-success">
        <i class="bi bi-plus-lg"></i> Nuevo proyecto
    </a>
</div>

<?php if (isset($_GET['creado'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        ✅ Proyecto creado correctamente.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (count($proyectos) > 0): ?>
    <div class="row g-4">
        <?php foreach ($proyectos as $p): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card card-proyecto h-100 d-flex flex-column p-3">
                    <h5 class="card-title"><?= htmlspecialchars($p['nombre']); ?></h5>
                    <p class="card-text flex-grow-1"><?= nl2br(htmlspecialchars($p['descripcion'])); ?></p>
                    <p class="mb-3 fecha-limite">Fecha límite: <?= htmlspecialchars($p['fecha_limite']); ?></p>
                    <a href="proyecto_detalle.php?id=<?= $p['id']; ?>" class="btn btn-morado w-100 mt-auto">
                        <i class="bi bi-eye"></i> Ver tareas
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="alert alert-warning text-center">Aún no tienes proyectos creados.</div>
<?php endif; ?>

<style>
.card-proyecto {
    border-radius: 1rem;
    box-shadow: 0 6px 15px rgba(0,0,0,0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.card-proyecto:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.12);
}
.fecha-limite {
    font-weight: bold;
    color: #0d6efd;
}
.btn-morado {
    background-color: #513174;
    border-color: #513174;
    color: white; /* texto blanco */
}
.btn-morado:hover {
    background-color: #3f255b; /* un poco más oscuro para el hover */
    border-color: #3f255b;
    color: white;
}
body { 
    background-image: 
    linear-gradient(rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.8)),
    url("/app/assets/img/bg-shapes.png");
    background-size: cover;
    background-repeat: repeat;
    min-height: 100vh;
}
:root {
    --bs-body-font-family: "Roboto", sans-serif;
}
</style>

<?php include("../includes/footer.php"); ?>

