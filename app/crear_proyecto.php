<?php
session_start();
include("../config/bd.php");

// --------------------------------------
// VERIFICAR INICIO DE SESIÓN
// --------------------------------------
if (!isset($_SESSION['id_usuario'])) {
    if (isset($_GET['modal'])) {
        echo json_encode(['error' => 'No autorizado']);
        exit;
    }
    header("Location: login.php");
    exit;
}

$es_modal = isset($_GET['modal']) && $_GET['modal'] == '1';
$mensaje = "";

// --------------------------------------
// PROCESAR FORMULARIO (POST)
// --------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $fecha_limite = $_POST['fecha_limite'];
    $id_usuario = $_SESSION['id_usuario'];

    if (empty($nombre) || empty($descripcion) || empty($fecha_limite)) {
        $response = ['success' => false, 'mensaje' => 'Todos los campos son obligatorios.'];

        if ($es_modal) {
            echo json_encode($response);
            exit;
        } else {
            $mensaje = $response['mensaje'];
        }
    } else {
        try {
            // Crear proyecto
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

            // Guardar correos de miembros
            if (!empty($_POST['miembros'])) {
                $miembros = array_filter(array_map('trim', $_POST['miembros']));

                foreach ($miembros as $correo) {
                    $stmt = $conexion->prepare("
                        INSERT INTO miembros (id_proyecto, correo_miembro) 
                        VALUES (:id_proyecto, :correo)
                    ");
                    $stmt->bindParam(":id_proyecto", $id_proyecto);
                    $stmt->bindParam(":correo", $correo);
                    $stmt->execute();
                }
            }

            if ($es_modal) {
                echo json_encode(['success' => true, 'mensaje' => 'Proyecto creado correctamente']);
                exit;
            } else {
                header("Location: proyectos.php?creado=1");
                exit;
            }
        } catch (PDOException $e) {
            if ($es_modal) {
                echo json_encode(['success' => false, 'mensaje' => "Error: " . $e->getMessage()]);
                exit;
            }
            $mensaje = "Error al crear el proyecto: " . $e->getMessage();
        }
    }
}

// --------------------------------------
// CONTENIDO DEL MODAL (HTML PURO)
// --------------------------------------
if ($es_modal && $_SERVER['REQUEST_METHOD'] !== 'POST') {
?>
<div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 25px 25px 0 0; border: none; padding: 30px;">
    <div class="modal-header-content text-center text-white w-100">
        <h3 class="fw-bold mb-1">Nuevo Proyecto</h3>
        <p class="mb-0" style="opacity: 0.9;">Organiza tu trabajo</p>
    </div>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body p-4">
    <form method="post" id="formProyectoModal">

        <div class="mb-3">
            <label class="form-label fw-semibold">
                <i class="bi bi-folder-fill text-primary"></i> Nombre del proyecto
            </label>
            <input type="text" name="nombre" class="form-control form-control-lg"
                   placeholder="Ej: Rediseño de la app" style="border-radius: 15px;" required>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">
                <i class="bi bi-text-paragraph text-primary"></i> Descripción
            </label>
            <textarea name="descripcion" class="form-control" rows="4"
                      placeholder="Describe los objetivos del proyecto..."
                      style="border-radius: 15px;" required></textarea>
        </div>

        <div class="mb-3">
            <label class="form-label fw-semibold">
                <i class="bi bi-calendar-event text-primary"></i> Fecha límite
            </label>
            <input type="date" name="fecha_limite"
                   class="form-control form-control-lg"
                   style="border-radius: 15px;" required>
        </div>

        <!-- Miembros -->
        <div class="mb-4">
            <label class="form-label fw-semibold">
                <i class="bi bi-people-fill text-primary"></i> Miembros del proyecto
            </label>

            <div id="miembrosContainerModal">
                <div class="input-group mb-2 miembro-item">
                    <span class="input-group-text">
                        <i class="bi bi-envelope"></i>
                    </span>
                    <input type="email" name="miembros[]" class="form-control" placeholder="correo@ejemplo.com">
                </div>
            </div>

            <button type="button" class="btn btn-outline-primary btn-sm"
                    id="agregarMiembroModal" style="border-radius: 10px;">
                <i class="bi bi-plus-circle"></i> Agregar otro miembro
            </button>
        </div>

        <div class="d-grid gap-2">
            <button type="submit"
                    class="btn btn-lg text-white fw-bold"
                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 15px; padding: 14px;">
                <i class="bi bi-check-circle"></i> Crear proyecto
            </button>

            <button type="button" class="btn btn-outline-secondary btn-lg"
                    data-bs-dismiss="modal" style="border-radius: 15px;">
                <i class="bi bi-x-circle"></i> Cancelar
            </button>
        </div>

    </form>
</div>
<?php
exit;
}

// -----------------------------------------------------
// VISTA NORMAL (NO MODAL)
// -----------------------------------------------------
include("../includes/header.php");
?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            <div class="card shadow-lg border-0" style="border-radius: 24px;">

                <div class="card-header text-white text-center py-4"
                     style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 24px 24px 0 0; border: none;">
                    <h4 class="mb-0 fw-bold">Nuevo Proyecto</h4>
                    <small style="opacity: 0.9;">Organiza tu trabajo</small>
                </div>

                <div class="card-body p-4">
                    <?php if($mensaje): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 12px;">
                            <?= htmlspecialchars($mensaje) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form method="post" id="formProyecto">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-folder-fill text-primary"></i> Nombre del proyecto
                            </label>
                            <input type="text" name="nombre" class="form-control"
                                   placeholder="Ej: Rediseño de la app"
                                   style="border-radius: 12px; padding: 12px;" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-text-paragraph text-primary"></i> Descripción
                            </label>
                            <textarea name="descripcion" class="form-control" rows="4"
                                      placeholder="Describe los objetivos del proyecto..."
                                      style="border-radius: 12px; padding: 12px;" required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-calendar-event text-primary"></i> Fecha límite
                            </label>
                            <input type="date" name="fecha_limite" class="form-control"
                                   style="border-radius: 12px; padding: 12px;" required>
                        </div>

                        <!-- Miembros -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-people-fill text-primary"></i> Miembros del proyecto
                            </label>

                            <div id="miembrosContainer">
                                <div class="input-group mb-2">
                                    <span class="input-group-text" style="border-radius: 12px 0 0 12px;">
                                        <i class="bi bi-envelope"></i>
                                    </span>
                                    <input type="email" name="miembros[]" class="form-control"
                                           placeholder="correo@ejemplo.com"
                                           style="border-radius: 0 12px 12px 0;">
                                </div>
                            </div>

                            <button type="button" class="btn btn-outline-primary btn-sm"
                                    id="agregarMiembro" style="border-radius: 10px;">
                                <i class="bi bi-plus-circle"></i> Agregar otro miembro
                            </button>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-lg w-100 text-white fw-bold mb-2"
                                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 12px; padding: 14px;">
                                <i class="bi bi-check-circle"></i> Crear proyecto
                            </button>
                            <a href="proyectos.php" class="btn btn-outline-secondary w-100"
                               style="border-radius: 12px; padding: 12px;">
                                <i class="bi bi-x-circle"></i> Cancelar
                            </a>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Estilos -->
<style>
body {
    background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
    min-height: 100vh;
}
.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15);
}
.btn:hover {
    transform: translateY(-2px);
    transition: all 0.3s ease;
}
.card {
    animation: slideUp 0.5s ease-out;
}
@keyframes slideUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<!-- Scripts -->
<script>
// --------------------------------------
// AGREGAR MIEMBROS EN VISTA NORMAL
// --------------------------------------
document.getElementById("agregarMiembro")?.addEventListener("click", function() {
    const container = document.getElementById("miembrosContainer");
    const div = document.createElement("div");
    div.className = "input-group mb-2";
    div.innerHTML = `
        <span class="input-group-text" style="border-radius: 12px 0 0 12px;">
            <i class="bi bi-envelope"></i>
        </span>
        <input type="email" name="miembros[]" class="form-control"
               placeholder="correo@ejemplo.com"
               style="border-radius: 0 12px 12px 0;">
    `;
    container.appendChild(div);
});

// --------------------------------------
// AGREGAR MIEMBROS EN MODAL CARGADO POR AJAX
// --------------------------------------
document.getElementById('modalNuevoProyecto')?.addEventListener('shown.bs.modal', function () {

    const btn = document.getElementById("agregarMiembroModal");
    const cont = document.getElementById("miembrosContainerModal");

    if (btn && cont) {
        btn.onclick = function () {
            const div = document.createElement("div");
            div.className = "input-group mb-2";
            div.innerHTML = `
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="miembros[]" class="form-control" placeholder="correo@ejemplo.com">
            `;
            cont.appendChild(div);
        };
    }

});
</script>

<?php include("../includes/footer.php"); ?>
