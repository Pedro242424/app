<?php
session_start();
include("../config/bd.php");

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

$id_proyecto = $_GET['id_proyecto'] ?? null;
$mensaje = "";

if (!$id_proyecto) {
    header("Location: proyectos.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $fecha_limite = $_POST['fecha_limite'] ?? '';
    $prioridad = $_POST['prioridad'] ?? 'media';
    $id_asignado = $_POST['id_asignado'] ?? null;
    $id_proyecto = $_POST['id_proyecto'] ?? null;

    if (empty($titulo) || empty($descripcion) || empty($fecha_limite)) {
        $mensaje = "⚠️ Todos los campos son obligatorios.";
    } else {
        try {
            $sql = $conexion->prepare("
                INSERT INTO tareas (titulo, descripcion, fecha_limite, prioridad, id_proyecto, estado, id_asignado)
                VALUES (:titulo, :descripcion, :fecha_limite, :prioridad, :id_proyecto, 'pendiente', :id_asignado)
            ");
            $sql->bindParam(":titulo", $titulo);
            $sql->bindParam(":descripcion", $descripcion);
            $sql->bindParam(":fecha_limite", $fecha_limite);
            $sql->bindParam(":prioridad", $prioridad);
            $sql->bindParam(":id_proyecto", $id_proyecto);
            $sql->bindParam(":id_asignado", $id_asignado);
            $sql->execute();

            header("Location: proyecto_detalle.php?id=" . urlencode($id_proyecto));
            exit;
        } catch (PDOException $e) {
            $mensaje = "❌ Error al guardar la tarea: " . $e->getMessage();
        }
    }
}

// ✅ Obtener los miembros del proyecto actual que tengan cuenta en 'usuarios'
$queryMiembros = $conexion->prepare("
    SELECT u.id, u.nombre, u.correo
    FROM miembros m
    INNER JOIN usuarios u ON m.correo_miembro = u.correo
    WHERE m.id_proyecto = :id_proyecto
");
$queryMiembros->bindParam(":id_proyecto", $id_proyecto);
$queryMiembros->execute();
$miembros = $queryMiembros->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include("../includes/header.php"); ?>

<div class="container mt-5">
    <h3>Nueva tarea</h3>

    <?php if ($mensaje): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($mensaje); ?></div>
    <?php endif; ?>

    <form method="post" action="">
        <div class="mb-3">
            <label class="form-label">Título:</label>
            <input type="text" class="form-control" name="titulo" required maxlength="100" placeholder="Ej: Diseñar login">
        </div>

        <div class="mb-3">
            <label class="form-label">Descripción:</label>
            <textarea class="form-control" name="descripcion" rows="3" required placeholder="Describe la tarea"></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label">Fecha límite:</label>
            <input type="date" class="form-control" name="fecha_limite" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Prioridad:</label>
            <select class="form-select" name="prioridad" required>
                <option value="baja">🟢 Baja</option>
                <option value="media" selected>🟡 Media</option>
                <option value="alta">🔴 Alta</option>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Asignar a usuario:</label>
            <select class="form-select" name="id_asignado" required>
                <option value="">-- Seleccionar miembro del proyecto --</option>
                <?php if (count($miembros) > 0): ?>
                    <?php foreach ($miembros as $miembro): ?>
                        <option value="<?= htmlspecialchars($miembro['id']); ?>">
                            <?= htmlspecialchars($miembro['nombre']); ?> (<?= htmlspecialchars($miembro['correo']); ?>)
                        </option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option disabled>No hay miembros con cuenta registrada</option>
                <?php endif; ?>
            </select>
        </div>

        <input type="hidden" name="id_proyecto" value="<?= htmlspecialchars($id_proyecto); ?>">

        <button type="submit" class="btn btn-success">Guardar tarea</button>
        <a href="proyecto_detalle.php?id=<?= htmlspecialchars($id_proyecto); ?>" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<style>
body { 
    background-image: 
    linear-gradient(rgba(255, 255, 255, 0.5), rgba(255, 255, 255, 0.8)),
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

