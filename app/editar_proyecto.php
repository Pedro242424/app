<?php
/**
 * EDITAR PROYECTO - MODAL
 * Permite editar un proyecto existente
 */

session_start();
include("../config/bd.php");

// Verificar sesión
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$id_usuario = $_SESSION['id_usuario'];
$id_proyecto = $_GET['id'] ?? null;
$mensaje = "";

if (!$id_proyecto) {
    echo json_encode(['error' => 'ID de proyecto no proporcionado']);
    exit;
}

// ===============================================
// PROCESAR FORMULARIO (ACTUALIZAR)
// ===============================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $fecha_limite = $_POST['fecha_limite'] ?? '';

    if (empty($nombre) || empty($descripcion) || empty($fecha_limite)) {
        echo json_encode(['success' => false, 'mensaje' => 'Todos los campos son obligatorios.']);
        exit;
    }

    try {
        // Verificar que el proyecto pertenezca al usuario
        $query_verificar = $conexion->prepare("
            SELECT id FROM proyectos WHERE id = :id AND id_usuario = :id_usuario
        ");
        $query_verificar->bindParam(":id", $id_proyecto);
        $query_verificar->bindParam(":id_usuario", $id_usuario);
        $query_verificar->execute();
        
        if ($query_verificar->rowCount() === 0) {
            echo json_encode(['success' => false, 'mensaje' => 'Proyecto no encontrado']);
            exit;
        }

        // Actualizar proyecto
        $query = $conexion->prepare("
            UPDATE proyectos 
            SET nombre = :nombre, descripcion = :descripcion, fecha_limite = :fecha_limite
            WHERE id = :id
        ");
        $query->bindParam(":nombre", $nombre);
        $query->bindParam(":descripcion", $descripcion);
        $query->bindParam(":fecha_limite", $fecha_limite);
        $query->bindParam(":id", $id_proyecto);
        $query->execute();

        echo json_encode(['success' => true, 'mensaje' => 'Proyecto actualizado correctamente']);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'mensaje' => "Error: " . $e->getMessage()]);
        exit;
    }
}

// ===============================================
// OBTENER DATOS DEL PROYECTO
// ===============================================
$query = $conexion->prepare("
    SELECT * FROM proyectos 
    WHERE id = :id AND id_usuario = :id_usuario
");
$query->bindParam(":id", $id_proyecto);
$query->bindParam(":id_usuario", $id_usuario);
$query->execute();
$proyecto = $query->fetch(PDO::FETCH_ASSOC);

if (!$proyecto) {
    echo '<div class="alert alert-danger m-4">Proyecto no encontrado</div>';
    exit;
}
?>

<style>
.input-group .btn-outline-danger {
    border-radius: 0 0.375rem 0.375rem 0;
}
</style>

<div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 25px 25px 0 0; border: none; padding: 30px;">
    <div class="modal-header-content text-center text-white w-100">
        <h3 class="fw-bold mb-1"><i class="bi bi-pencil-square"></i> Editar Proyecto</h3>
        <p class="mb-0" style="opacity: 0.9;">Actualiza la información del proyecto</p>
    </div>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body p-4">
    <form method="post" id="formEditarProyecto">
        <!-- Nombre -->
        <div class="mb-3">
            <label class="form-label fw-semibold">
                <i class="bi bi-folder-fill text-primary"></i> Nombre del proyecto
            </label>
            <input type="text" name="nombre" class="form-control form-control-lg"
                   value="<?= htmlspecialchars($proyecto['nombre']) ?>"
                   placeholder="Ej: Rediseño de la app" style="border-radius: 15px;" required>
        </div>

        <!-- Descripción -->
        <div class="mb-3">
            <label class="form-label fw-semibold">
                <i class="bi bi-text-paragraph text-primary"></i> Descripción
            </label>
            <textarea name="descripcion" class="form-control" rows="4"
                      placeholder="Describe los objetivos del proyecto..."
                      style="border-radius: 15px;" required><?= htmlspecialchars($proyecto['descripcion']) ?></textarea>
        </div>

        <!-- Fecha límite -->
        <div class="mb-4">
            <label class="form-label fw-semibold">
                <i class="bi bi-calendar-event text-primary"></i> Fecha límite
            </label>
            <input type="date" name="fecha_limite"
                   value="<?= $proyecto['fecha_limite'] ?>"
                   class="form-control form-control-lg"
                   style="border-radius: 15px;" required>
        </div>

        <input type="hidden" name="id" value="<?= $id_proyecto ?>">

        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-lg text-white fw-bold"
                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 15px; padding: 14px;">
                <i class="bi bi-check-circle"></i> Guardar cambios
            </button>

            <button type="button" class="btn btn-outline-secondary btn-lg"
                    data-bs-dismiss="modal" style="border-radius: 15px;">
                <i class="bi bi-x-circle"></i> Cancelar
            </button>
        </div>
    </form>
</div>

<script>
/**
 * MANEJO DEL FORMULARIO DE EDICIÓN
 */
setTimeout(function() {
    const form = document.getElementById('formEditarProyecto');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
            
            const formData = new FormData(form);
            formData.append('id', <?= $id_proyecto ?>);
            
            fetch('editar_proyecto.php?id=<?= $id_proyecto ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.parent.postMessage('proyecto_editado', '*');
                } else {
                    alert('Error: ' + data.mensaje);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al actualizar el proyecto');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    }
}, 100);
</script>