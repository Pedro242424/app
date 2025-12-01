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
    header("Location: tareas.php");
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

include("../includes/header.php");
?>

<style>
    body {
        background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%);
    }

    .section-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }

    .tab-btn {
        border: none;
        padding: 10px 20px;
        border-radius: 10px;
        font-weight: 600;
        background: #e9ecef;
        color: #6c757d;
        margin-right: 8px;
        transition: all 0.3s;
        cursor: pointer;
    }
    
    .tab-btn:hover {
        background: #dee2e6;
    }
    
    .tab-btn.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .task-card {
        background: #fff;
        padding: 20px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 15px;
        border: 2px solid #f1f3f5;
        transition: all 0.3s;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }

    .task-card:hover {
        border-color: #667eea;
        box-shadow: 0 4px 16px rgba(102, 126, 234, 0.15);
        transform: translateY(-2px);
    }

    .task-left {
        display: flex;
        gap: 16px;
        align-items: flex-start;
    }

    .task-checkbox {
        width: 22px;
        height: 22px;
        cursor: pointer;
        accent-color: #667eea;
    }

    .task-title {
        font-size: 16px;
        font-weight: 600;
        color: #333;
        margin-bottom: 6px;
    }

    .task-date {
        font-size: 13px;
        color: #868e96;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .priority-tag {
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .alta { background: #ffe0e0; color: #d32f2f; }
    .media { background: #fff4e0; color: #f57c00; }
    .baja { background: #e0f7e0; color: #388e3c; }

    .btn-purple {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 12px;
        padding: 12px 24px;
        border: none;
        font-weight: 600;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }

    .btn-purple:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    /* Modal personalizado para iframe */
    .modal-iframe .modal-dialog {
        max-width: 700px;
    }

    .modal-iframe .modal-content {
        border: none;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    }

    .modal-iframe .modal-body {
        padding: 0;
        background: transparent;
    }

    .modal-iframe iframe {
        width: 100%;
        height: 650px;
        border: none;
        display: block;
        background: white;
    }

    /* Botón cerrar personalizado */
    .modal-iframe .btn-close {
        position: absolute;
        top: 15px;
        right: 15px;
        z-index: 1000;
        background: white;
        opacity: 1;
        width: 35px;
        height: 35px;
        border-radius: 50%;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }

    .modal-iframe .btn-close:hover {
        transform: scale(1.1);
    }
</style>

<div class="container mt-4">

    <a href="proyectos.php" class="btn btn-outline-secondary mb-3" style="border-radius: 10px;">
        <i class="bi bi-arrow-left"></i> Volver a proyectos
    </a>

    <!-- Header del proyecto -->
    <div class="bg-white p-4 rounded-3 shadow-sm mb-4">
        <h2 class="fw-bold mb-3" style="color: #667eea;">
            <i class="bi bi-folder-fill"></i> <?= htmlspecialchars($proyecto['nombre']); ?>
        </h2>
        <p class="text-muted mb-2"><?= htmlspecialchars($proyecto['descripcion']); ?></p>
        <div class="d-flex gap-4 text-muted">
            <span><i class="bi bi-calendar-event"></i> Vence: <strong><?= htmlspecialchars($proyecto['fecha_limite']); ?></strong></span>
            <span><i class="bi bi-list-check"></i> <strong><?= count($tareas); ?></strong> tareas totales</span>
        </div>
    </div>

    <!-- Tabs y botón Nueva Tarea -->
    <div class="section-top my-4">
        <div>
            <button class="tab-btn active" data-filter="todas">Todas</button>
            <button class="tab-btn" data-filter="pendiente">Pendientes</button>
            <button class="tab-btn" data-filter="completada">Completadas</button>
        </div>

        <!-- Botón que abre el modal con iframe -->
        <button class="btn-purple" data-bs-toggle="modal" data-bs-target="#modalCrearTarea">
            <i class="bi bi-plus-circle-fill"></i> Nueva tarea
        </button>
    </div>

    <!-- Lista de tareas -->
    <div id="listaTareas">
        <?php if (count($tareas) > 0): ?>
            <?php foreach ($tareas as $t): ?>
                <?php
                    $prioridad = strtolower($t['prioridad'] ?? 'media');
                    $asignado = $t['nombre_asignado'] ? htmlspecialchars($t['nombre_asignado']) : 'Sin asignar';
                    $estado = strtolower($t['estado'] ?? 'pendiente');
                ?>

                <div class="task-card" data-estado="<?= $estado ?>">
                    <div class="task-left">
                        <input type="checkbox" class="task-checkbox" <?= ($estado == 'completada') ? 'checked' : '' ?>>
                        <div>
                            <div class="task-title"><?= htmlspecialchars($t['titulo']); ?></div>
                            <div class="task-date">
                                <i class="bi bi-calendar3"></i>
                                Vence: <?= htmlspecialchars($t['fecha_limite']); ?>
                                &nbsp;|&nbsp;
                                <i class="bi bi-person"></i>
                                <?= $asignado ?>
                            </div>
                        </div>
                    </div>

                    <span class="priority-tag <?= $prioridad ?>">
                        <?= ucfirst($prioridad) ?>
                    </span>
                </div>

            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info mt-3" style="border-radius: 12px;">
                <i class="bi bi-info-circle-fill"></i> No hay tareas en este proyecto todavía. ¡Crea la primera!
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- MODAL CON IFRAME -->
<div class="modal fade modal-iframe" id="modalCrearTarea" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body">
                <!-- Aquí se carga tu crear_tarea.php -->
                <iframe 
                    src="crear_tarea.php?id_proyecto=<?= $id_proyecto ?>" 
                    id="iframeCrearTarea"
                    frameborder="0"
                ></iframe>
            </div>
        </div>
    </div>
</div>

<script>
// Filtros de tabs
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        const filter = this.getAttribute('data-filter');
        const tareas = document.querySelectorAll('.task-card');
        
        tareas.forEach(tarea => {
            if (filter === 'todas') {
                tarea.style.display = 'flex';
            } else {
                const estado = tarea.getAttribute('data-estado');
                tarea.style.display = estado === filter ? 'flex' : 'none';
            }
        });
    });
});

// Detectar cuando se cierra el modal del iframe y recargar la página
document.getElementById('modalCrearTarea').addEventListener('hidden.bs.modal', function () {
    // Verificar si el iframe realizó algún cambio (opcional)
    location.reload(); // Recarga para mostrar la nueva tarea
});

// Escuchar mensajes del iframe (comunicación entre ventanas)
window.addEventListener('message', function(event) {
    // Si el iframe envía mensaje de "tarea_creada"
    if (event.data === 'tarea_creada') {
        // Cerrar el modal
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalCrearTarea'));
        modal.hide();
        // Recargar la página
        setTimeout(() => location.reload(), 300);
    }
});
</script>

<?php include("../includes/footer.php"); ?>

