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

// Obtener tareas
$queryTareas = $conexion->prepare("
    SELECT t.*, u.nombre AS nombre_asignado
    FROM tareas t
    LEFT JOIN usuarios u ON t.id_asignado = u.id
    WHERE t.id_proyecto = :id_proyecto
    ORDER BY 
        CASE 
            WHEN t.estado = 'pendiente' THEN 1
            WHEN t.estado = 'en_proceso' THEN 2
            WHEN t.estado = 'completada' THEN 3
        END,
        t.fecha_limite ASC
");
$queryTareas->bindParam(":id_proyecto", $id_proyecto);
$queryTareas->execute();
$tareas = $queryTareas->fetchAll(PDO::FETCH_ASSOC);

// Obtener integrantes del proyecto
$queryIntegrantes = $conexion->prepare("
    SELECT 
        u.nombre AS nombre_miembro
    FROM miembros m
    LEFT JOIN usuarios u ON u.correo = m.correo_miembro
    WHERE m.id_proyecto = :id_proyecto
");
$queryIntegrantes->bindParam(":id_proyecto", $id_proyecto);
$queryIntegrantes->execute();
$integrantes = $queryIntegrantes->fetchAll(PDO::FETCH_ASSOC);

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

    /* TABS */
    .tab-btn {
        padding: 10px 24px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s;
        border: none;
        margin-right: 8px;
    }

    .tab-btn.active {
        background: #667eea;
        color: white;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .tab-btn.inactive {
        background: #e8e8ea;
        color: #666;
    }

    .tab-btn.inactive:hover {
        background: #d8d8da;
    }

    /* BOTÓN NUEVA TAREA */
    .btn-nueva-tarea {
        background: #667eea;
        color: white;
        border: none;
        padding: 12px 28px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 15px;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .btn-nueva-tarea:hover {
        background: #5568d3;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        color: white;
    }

    /* TARJETA DE TAREA */
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
        position: relative;
        overflow: visible; 
        z-index: 1; 
    }

    .task-card:hover {
        border-color: #667eea;
        box-shadow: 0 4px 16px rgba(102, 126, 234, 0.15);
        transform: translateY(-2px);
        z-index: 10; 
    }

    .task-card::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 5px;
        border-radius: 16px 0 0 16px;
    }

    .task-card[data-estado="pendiente"]::before {
        background: #f59e0b;
    }

    .task-card[data-estado="en_proceso"]::before {
        background: #3b82f6;
    }

    .task-card[data-estado="completada"]::before {
        background: #10b981;
    }

    .task-left {
        display: flex;
        gap: 16px;
        align-items: flex-start;
        flex: 1;
    }

    /* CHECKBOX */
    .task-checkbox-container {
        position: relative;
        cursor: pointer;
        user-select: none;
        width: 28px;
        height: 28px;
        margin-top: 2px;
        flex-shrink: 0;
    }

    .checkmark {
        position: absolute;
        top: 0;
        left: 0;
        height: 28px;
        width: 28px;
        background-color: white;
        border: 3px solid #d1d5db;
        border-radius: 8px;
        transition: all 0.3s;
    }

    .task-checkbox-container:hover .checkmark {
        border-color: #667eea;
        transform: scale(1.1);
    }

    /* Pendiente */
    .task-checkbox-container[data-estado="pendiente"] .checkmark {
        background-color: white;
        border-color: #f59e0b;
    }

    /* En Proceso */
    .task-checkbox-container[data-estado="en_proceso"] .checkmark {
        background-color: #3b82f6;
        border-color: #3b82f6;
    }

    .task-checkbox-container[data-estado="en_proceso"] .checkmark::after {
        content: "";
        position: absolute;
        display: block;
        left: 4px;
        top: 11px;
        width: 12px;
        height: 3px;
        background: white;
        border-radius: 2px;
    }

    /* Completada */
    .task-checkbox-container[data-estado="completada"] .checkmark {
        background-color: #10b981;
        border-color: #10b981;
    }

    .task-checkbox-container[data-estado="completada"] .checkmark::after {
        content: "";
        position: absolute;
        display: block;
        left: 8px;
        top: 4px;
        width: 6px;
        height: 12px;
        border: solid white;
        border-width: 0 3px 3px 0;
        transform: rotate(45deg);
    }

    .task-title {
        font-size: 16px;
        font-weight: 600;
        color: #333;
        margin-bottom: 6px;
        transition: all 0.3s;
    }

    .task-card[data-estado="completada"] .task-title {
        text-decoration: line-through;
        color: #9ca3af;
    }

    .task-date {
        font-size: 13px;
        color: #868e96;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    /* BADGES */
    .estado-badge {
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-right: 10px;
    }

    .estado-badge.pendiente {
        background: #fef3c7;
        color: #d97706;
    }

    .estado-badge.en_proceso {
        background: #dbeafe;
        color: #2563eb;
    }

    .estado-badge.completada {
        background: #d1fae5;
        color: #059669;
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

    /* MENÚ */
    .dropdown-menu {
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        padding: 8px;
        z-index: 1000; 
    }

    .dropdown-item {
        border-radius: 8px;
        padding: 10px 16px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s;
    }

    .dropdown-item:hover {
        background: #f8f9fa;
    }

    /* MODAL */
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

    /* BOTÓN CERRAR */
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
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .modal-iframe .btn-close::before {
        content: "\f62a";
        font-family: "bootstrap-icons";
        font-size: 20px;
        color: #6c757d;
    }

    /* CÍRCULO PROGRESO */
    .progress-circle {
        width: 140px;
        height: 140px;
        border-radius: 50%;
        background: conic-gradient(#667eea var(--p), #e6e6e6 0deg);
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .progress-circle span {
        position: absolute;
        font-size: 26px;
        font-weight: 700;
        color: #4c4c4c;
    }

    .progress-circle::before {
        content: "";
        width: 105px;
        height: 105px;
        background: white;
        border-radius: 50%;
    }
</style>

<div class="container mt-4">

    <a href="proyectos.php" class="btn btn-outline-secondary mb-3" style="border-radius: 10px;">
        <i class="bi bi-arrow-left"></i> Volver a proyectos
    </a>

    <!-- Header del proyecto -->
<div class="bg-white p-4 rounded-3 shadow-sm mb-4 d-flex justify-content-between align-items-center flex-wrap">

<!-- IZQUIERDA: Datos del proyecto -->
<div style="max-width: 65%;">
    <h2 class="fw-bold mb-2" style="color: #667eea;">
        <i class="bi bi-folder-fill"></i> <?= htmlspecialchars($proyecto['nombre']); ?>
    </h2>

    <p class="text-muted mb-2"><?= htmlspecialchars($proyecto['descripcion']); ?></p>

    <div class="d-flex gap-4 text-muted mb-2">
        <span><i class="bi bi-calendar-event"></i> Vence: 
            <strong><?= htmlspecialchars($proyecto['fecha_limite']); ?></strong>
        </span>

        <span><i class="bi bi-list-check"></i> 
            <strong><?= count($tareas); ?></strong> tareas totales
        </span>
    </div>

    <!-- TAGS DE INTEGRANTES -->
<div class="d-flex flex-wrap gap-3 mt-3" style="padding-top: 4px;">
    <?php if (count($integrantes) > 0): ?>
        <?php foreach ($integrantes as $i): 
            $inicial = strtoupper(substr($i['nombre_miembro'], 0, 1));
        ?>
            <div class="d-flex align-items-center gap-2" style="margin-right: 8px;">

                <!-- Avatar pequeño -->
                <div style="
                    width: 26px;
                    height: 26px;
                    border-radius: 50%;
                    background: #667eea;
                    color: white;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-weight: 600;
                    font-size: 13px;
                ">
                    <?= $inicial ?>
                </div>

                <!-- Nombre -->
                <span style="font-size: 14px; font-weight: 600; color:#4a4a4a;">
                    <?= htmlspecialchars($i['nombre_miembro'] ?: 'Sin nombre'); ?>
                </span>

            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="text-muted" style="margin-top: 6px;">No hay integrantes asignados todavía.</p>
    <?php endif; ?>
</div>

</div>


<!-- DERECHA: Barra circular de progreso -->
<div class="text-center" style="width:140px;">

    <?php  
        $total = count($tareas);
        $done = 0;

        foreach($tareas as $t){
            if($t['estado'] === 'completada'){ $done++; }
        }

        $porcentaje = $total > 0 ? round(($done / $total) * 100) : 0;
    ?>

    <div class="progress-circle" data-progress="<?= $porcentaje ?>">
        <span><?= $porcentaje ?>%</span>
    </div>

    <p class="mt-2 text-muted fw-semibold">Progreso</p>
</div>

</div>

    <!-- Tabs y botón Nueva Tarea -->
    <div class="section-top my-4">
        <div>
            <button class="tab-btn active" data-filter="todas">Todas</button>
            <button class="tab-btn inactive" data-filter="pendiente">Pendientes</button>
            <button class="tab-btn inactive" data-filter="en_proceso">En Proceso</button>
            <button class="tab-btn inactive" data-filter="completada">Completadas</button>
        </div>

        <button class="btn-nueva-tarea" data-bs-toggle="modal" data-bs-target="#modalCrearTarea">
            <i class="bi bi-plus-circle-fill"></i> Nueva tarea
        </button>
    </div>

    <!-- Lista de tareas -->
    <div id="listaTareas">
        <?php if (count($tareas) > 0): ?>
            <?php foreach ($tareas as $t): ?>
                <?php
                    $prioridad = isset($t['prioridad']) ? strtolower($t['prioridad']) : 'media';
                    $asignado = isset($t['nombre_asignado']) && $t['nombre_asignado'] ? htmlspecialchars($t['nombre_asignado']) : 'Sin asignar';
                    $estado = isset($t['estado']) ? strtolower($t['estado']) : 'pendiente';
                    
                    $estado_texto = [
                        'pendiente' => 'Pendiente',
                        'en_proceso' => 'En Proceso',
                        'completada' => 'Completada'
                    ];
                    
                    $nombre_estado = isset($estado_texto[$estado]) ? $estado_texto[$estado] : 'Pendiente';
                ?>

                <div class="task-card" data-estado="<?= $estado ?>">
                    <div class="task-left">
                        <label class="task-checkbox-container" data-estado="<?= $estado ?>" data-id="<?= $t['id'] ?>">
                            <span class="checkmark"></span>
                        </label>
                        
                        <div style="flex: 1; cursor: pointer;" onclick="verDetallesTarea(<?= $t['id'] ?>)">
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

                    <div>
                        <span class="estado-badge <?= $estado ?>">
                            <?php if ($estado == 'pendiente'): ?>
                                <i class="bi bi-clock"></i>
                            <?php elseif ($estado == 'en_proceso'): ?>
                                <i class="bi bi-hourglass-split"></i>
                            <?php else: ?>
                                <i class="bi bi-check-circle-fill"></i>
                            <?php endif; ?>
                            <?= $nombre_estado ?>
                        </span>
                        
                        <span class="priority-tag <?= $prioridad ?>">
                            <?= ucfirst($prioridad) ?>
                        </span>
                        
                        <!-- BOTONES DE ACCIÓN -->
                        <div class="dropdown" style="display: inline-block; margin-left: 10px;">
                            <button class="btn btn-sm" style="background: white; border: 2px solid #e9ecef; border-radius: 8px; padding: 6px 10px;" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical" style="font-size: 16px; color: #6c757d;"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" style="border-radius: 12px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.15);">
                                <li>
                                    <a class="dropdown-item" href="#" onclick="verDetallesTarea(<?= $t['id'] ?>); return false;">
                                        <i class="bi bi-eye-fill text-primary"></i> Ver detalles
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="#" onclick="editarTarea(<?= $t['id'] ?>); return false;">
                                        <i class="bi bi-pencil-fill text-info"></i> Editar
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="#" onclick="confirmarEliminarTarea(<?= $t['id'] ?>, '<?= htmlspecialchars($t['titulo']) ?>'); return false;">
                                        <i class="bi bi-trash-fill"></i> Eliminar
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info mt-3" style="border-radius: 12px;">
                <i class="bi bi-info-circle-fill"></i> No hay tareas en este proyecto todavía. ¡Crea la primera!
            </div>
        <?php endif; ?>
    </div>

</div>

<!-- MODAL CON IFRAME CREAR TAREA -->
<div class="modal fade modal-iframe" id="modalCrearTarea" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body">
                <iframe 
                    src="crear_tarea.php?id_proyecto=<?= $id_proyecto ?>" 
                    id="iframeCrearTarea"
                    frameborder="0"
                ></iframe>
            </div>
        </div>
    </div>
</div>

<!-- MODAL VER DETALLES -->
<div class="modal fade modal-iframe" id="modalVerTarea" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            <div class="modal-body">
                <iframe id="iframeVerTarea" style="height: 600px;"></iframe>
            </div>
        </div>
    </div>
</div>

<!-- MODAL EDITAR TAREA -->
<div class="modal fade modal-iframe" id="modalEditarTarea" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            <div class="modal-body">
                <iframe id="iframeEditarTarea" style="height: 650px;"></iframe>
            </div>
        </div>
    </div>
</div>

<script>
// FILTROS DE TABS
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('active');
            b.classList.add('inactive');
        });
        this.classList.remove('inactive');
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

// CAMBIAR ESTADO DE TAREA (3 ESTADOS CÍCLICOS)
document.querySelectorAll('.task-checkbox-container').forEach(checkbox => {
    checkbox.addEventListener('click', function(e) {
        e.preventDefault();
        
        const idTarea = this.getAttribute('data-id');
        const estadoActual = this.getAttribute('data-estado');
        
        console.log('Estado actual:', estadoActual); // Debug
        
        // Ciclo: pendiente → en_proceso → completada → pendiente
        let nuevoEstado;
        if (estadoActual === 'pendiente') {
            nuevoEstado = 'en_proceso';
        } else if (estadoActual === 'en_proceso') {
            nuevoEstado = 'completada';
        } else {
            nuevoEstado = 'pendiente';
        }
        
        console.log('Nuevo estado:', nuevoEstado); // Debug
        
        // Actualizar en el servidor
        fetch('estado_tarea.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${idTarea}&estado=${nuevoEstado}`
        })
        .then(response => response.json())
        .then(data => {
            console.log('Respuesta:', data); // Debug
            if (data.success) {
                // Recargar página para actualizar UI
                location.reload();
            } else {
                alert('Error: ' + data.mensaje);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al actualizar la tarea');
        });
    });
});
// VER DETALLES DE TAREA (Modal)

function verDetallesTarea(idTarea) {
    const modal = new bootstrap.Modal(document.getElementById('modalVerTarea'));
    const iframe = document.getElementById('iframeVerTarea');
    iframe.src = `detalle_tarea.php?id=${idTarea}`;
    modal.show();
}


// EDITAR TAREA (Modal)

function editarTarea(idTarea) {
    const modal = new bootstrap.Modal(document.getElementById('modalEditarTarea'));
    const iframe = document.getElementById('iframeEditarTarea');
    iframe.src = `editar_tarea.php?id=${idTarea}`;
    modal.show();
}


// CONFIRMAR ELIMINAR TAREA (Modal)

function confirmarEliminarTarea(idTarea, tituloTarea) {
    const modalHTML = `
        <div class="modal fade modal-eliminar" id="modalEliminarTarea" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            Confirmar eliminación
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">¿Estás seguro de eliminar la tarea:</p>
                        <p class="fw-bold text-center fs-5 text-primary mb-4">"${tituloTarea}"</p>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-circle"></i>
                            <strong>Advertencia:</strong> Esta acción no se puede deshacer.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </button>
                        <button type="button" class="btn btn-danger" onclick="ejecutarEliminarTarea(${idTarea})">
                            <i class="bi bi-trash-fill"></i> Sí, eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Eliminar modal anterior si existe
    const modalAntiguo = document.getElementById('modalEliminarTarea');
    if (modalAntiguo) {
        modalAntiguo.remove();
    }
    
    // Crear y mostrar modal
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    const modal = new bootstrap.Modal(document.getElementById('modalEliminarTarea'));
    modal.show();
}


// EJECUTAR ELIMINACIÓN (después de confirmar)

function ejecutarEliminarTarea(idTarea) {
    // Cerrar modal
    const modal = bootstrap.Modal.getInstance(document.getElementById('modalEliminarTarea'));
    modal.hide();
    
    // Mostrar spinner de carga
    const loadingHTML = `
        <div class="position-fixed top-50 start-50 translate-middle" style="z-index: 9999;" id="loadingEliminar">
            <div class="spinner-border text-danger" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Eliminando...</span>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', loadingHTML);
    
    // Hacer petición de eliminación
    fetch('eliminar_tarea.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${idTarea}`
    })
    .then(response => response.json())
    .then(data => {
        // Remover spinner
        document.getElementById('loadingEliminar')?.remove();
        
        if (data.success) {
            alert(' Tarea eliminada correctamente');
            location.reload();
        } else {
            alert(' Error: ' + data.mensaje);
        }
    })
    .catch(error => {
        document.getElementById('loadingEliminar')?.remove();
        console.error('Error:', error);
        alert(' Error al eliminar la tarea');
    });
}


// ESCUCHAR MENSAJES DE MODALES (iframes)

window.addEventListener('message', function(event) {
    if (event.data === 'tarea_actualizada' || event.data === 'tarea_editada') {
        location.reload();
    }
});
// CERRAR MODAL Y RECARGAR
document.getElementById('modalCrearTarea').addEventListener('hidden.bs.modal', function () {
    location.reload();
});

// ESCUCHAR MENSAJES DEL IFRAME
window.addEventListener('message', function(event) {
    if (event.data === 'tarea_creada') {
        const modal = bootstrap.Modal.getInstance(document.getElementById('modalCrearTarea'));
        modal.hide();
        setTimeout(() => location.reload(), 300);
    }
});
// Inicializar progreso circular
document.querySelectorAll(".progress-circle").forEach(circle => {
    const val = circle.getAttribute("data-progress");
    const degrees = (val * 360) / 100;
    circle.style.setProperty("--p", degrees + "deg");
});

</script>

<?php include("../includes/footer.php"); ?>