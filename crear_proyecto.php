<?php
session_start();
include("../config/bd.php");

if (!isset($_SESSION['id_usuario'])) {
    header("Location: ../auth/login.php");
    exit;
}

$mensaje = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $fecha_limite = $_POST['fecha_limite'];
    $id_usuario = $_SESSION['id_usuario'];

    if (empty($nombre) || empty($descripcion) || empty($fecha_limite)) {
        $mensaje = "⚠️ Todos los campos son obligatorios.";
    } else {
        try {
            // 1️⃣ Crear el proyecto
            $query = $conexion->prepare("
                INSERT INTO proyectos (nombre, descripcion, fecha_limite, id_usuario) 
                VALUES (:nombre, :descripcion, :fecha_limite, :id_usuario)
            ");
            $query->bindParam(":nombre", $nombre);
            $query->bindParam(":descripcion", $descripcion);
            $query->bindParam(":fecha_limite", $fecha_limite);
            $query->bindParam(":id_usuario", $id_usuario);
            $query->execute();

            $id_proyecto = $conexion->lastInsertId();

            // 2️⃣ Agregar miembros (correos)
            if (!empty($_POST['miembros'])) {
                $miembros = array_filter(array_map('trim', $_POST['miembros']));

                foreach ($miembros as $correo) {
                    if (!empty($correo)) {
                        $stmt = $conexion->prepare("INSERT INTO miembros (id_proyecto, correo_miembro) VALUES (:id_proyecto, :correo)");
                        $stmt->bindParam(":id_proyecto", $id_proyecto);
                        $stmt->bindParam(":correo", $correo);
                        $stmt->execute();
                    }
                }
            }

            header("Location: proyectos.php");
            exit;
        } catch (PDOException $e) {
            $mensaje = "❌ Error al crear el proyecto: " . $e->getMessage();
        }
    }
}
?>

<?php include("../includes/header.php"); ?>

<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-body">
                <h4 class="mb-3 text-center">Nuevo Proyecto</h4>
                <?php if($mensaje): ?>
                    <div class="alert alert-danger py-2"><?= htmlspecialchars($mensaje) ?></div>
                <?php endif; ?>
                <form method="post" id="formProyecto">
                    <div class="mb-3">
                        <label class="form-label">Nombre del proyecto</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Fecha límite</label>
                        <input type="date" name="fecha_limite" class="form-control" required>
                    </div>

                    <!-- 👥 Agregar miembros -->
                    <div class="mb-3">
                        <label class="form-label">Miembros del proyecto (por correo):</label>
                        <div id="miembrosContainer">
                            <input type="correo" name="miembros[]" class="form-control mb-2" placeholder="ejemplo@correo.com">
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="agregarMiembro">+ Agregar otro miembro</button>
                    </div>

                    <button type="submit" class="btn btn-success w-100 mt-3">Guardar proyecto</button>
                    <a href="proyectos.php" class="btn btn-secondary w-100 mt-2">Cancelar</a>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Permitir agregar más campos de miembros dinámicamente
document.getElementById("agregarMiembro").addEventListener("click", function() {
    const container = document.getElementById("miembrosContainer");
    const input = document.createElement("input");
    input.type = "correo";
    input.name = "miembros[]";
    input.className = "form-control mb-2";
    input.placeholder = "otro@correo.com";
    container.appendChild(input);
});
</script>

<?php include("../includes/footer.php"); ?>

