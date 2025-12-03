<?php
session_start();
include("../config/bd.php");

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

$id_usuario = $_SESSION['id_usuario'];

// Obtener todas las tareas del usuario
$query_tareas = $conexion->prepare("
    SELECT t.*, p.nombre as nombre_proyecto, p.id as id_proyecto
    FROM tareas t
    LEFT JOIN proyectos p ON t.id_proyecto = p.id
    WHERE t.id_asignado = :id_usuario
    ORDER BY 
        CASE 
            WHEN t.estado = 'pendiente' THEN 1
            WHEN t.estado = 'en_proceso' THEN 2
            WHEN t.estado = 'completada' THEN 3
        END,
        t.fecha_limite ASC
");
$query_tareas->bindParam(":id_usuario", $id_usuario);
$query_tareas->execute();
$tareas = $query_tareas->fetchAll(PDO::FETCH_ASSOC);

// Contar tareas por estado
$total_tareas = count($tareas);
$pendientes = count(array_filter($tareas, fn($t) => $t['estado'] == 'pendiente'));
$en_proceso = count(array_filter($tareas, fn($t) => $t['estado'] == 'en_proceso'));
$completadas = count(array_filter($tareas, fn($t) => $t['estado'] == 'completada'));

include("../includes/header.php");
?>

<style>
    /* === LAYOUT GENERAL === */
    body {
        background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
        min-height: 100vh;
    }

    /* === HEADER DE PÁGINA === */
    .page-header {
        background: white;
        border-radius: 20px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }

    .page-header h1 {
        font-size: 32px;
        font-weight: 700;
        color: #333;
        margin: 0 0 8px 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    /* === PESTAÑAS DE FILTRO === */
    .section-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        flex-wrap: wrap;
        gap: 15px;
    }

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
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

    /* === TARJETA DE TAREA === */
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
        overflow: visible; /* Permite que dropdown se vea completo */
        z-index: 1;
    }

    /* Barra de color lateral según estado */
    .task-card::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 5px;
        border-radius: 16px 0 0 16px;
    }

    .task-card[data-estado="pendiente"]::before { background: #f59e0b; }
    .task-card[data-estado="en_proceso"]::before { background: #3b82f6; }
    .task-card[data-estado="completada"]::before { background: #10b981; }

    .task-card:hover {
        border-color: #667eea;
        box-shadow: 0 4px 16px rgba(102, 126, 234, 0.15);
        transform: translateY(-2px);
        z-index: 10; /* Sube al hacer hover */
    }

    /* === CONTENIDO DE TAREA === */
    .task-left {
        display: flex;
        gap: 16px;
        align-items: flex-start;
        flex: 1;
    }

    /* Checkbox personalizado */
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

    /* Estados del checkbox */
    .task-checkbox-container[data-estado="pendiente"] .checkmark {
        background-color: white;
        border-color: #f59e0b;
    }

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

    /* Título de tarea */
    .task-title {
        font-size: 16px;
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
    }

    .task-card[data-estado="completada"] .task-title {
        text-decoration: line-through;
        color: #9ca3af;
    }

    /* Tag de proyecto */
    .task-project {
        font-size: 13px;
        color: #667eea;
        background: #f0f2ff;
        padding: 4px 12px;
        border-radius: 12px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    /* Fecha */
    .task-date {
        font-size: 13px;
        color: #868e96;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    /* === BADGES === */
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

    .estado-badge.pendiente { background: #fef3c7; color: #d97706; }
    .estado-badge.en_proceso { background: #dbeafe; color: #2563eb; }
    .estado-badge.completada { background: #d1fae5; color: #059669; }

    .priority-tag {
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        white-space: nowrap;
    }
    
    .alta { background: #ffe0e0; color: #d32f2f; }
    .media { background: #fff4e0; color: #f57c00; }
    .baja { background: #e0f7e0; color: #388e3c; }

    /* === MENÚ DESPLEGABLE === */
    .dropdown-menu {
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        padding: 8px;
        z-index: 1000; /* Siempre encima */
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

    /* === MODALES === */
    .modal-iframe .modal-content {
        border-radius: 20px;
        border: none;
    }

    .modal-iframe .modal-body {
        padding: 0;
    }

    .modal-iframe iframe {
        width: 100%;
        border: none;
    }

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

    /* === MODAL DE ELIMINACIÓN === */
    .modal-eliminar .modal-content {
        border-radius: 20px;
        border: none;
        overflow: hidden;
    }

    .modal-eliminar .modal-header {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        border: none;
        padding: 25px 30px;
    }

    .modal-eliminar .modal-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 700;
        font-size: 20px;
    }

    .modal-eliminar .modal-body {
        padding: 30px;
    }

    .modal-eliminar .alert-warning {
        border-radius: 12px;
        border: none;
        background: #fff3cd;
        color: #856404;
    }

    .modal-eliminar .modal-footer {
        border: none;
        padding: 20px 30px;
        gap: 10px;
    }

    .modal-eliminar .btn {
        border-radius: 12px;
        padding: 10px 20px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .modal-eliminar .btn-secondary {
        background: #6c757d;
        border: none;
    }

    .modal-eliminar .btn-danger {
        background: #dc3545;
        border: none;
    }
</style>

<div class="container" style="max-width: 1200px; padding-top: 30px; padding-bottom: 50px;">
    
    <!-- Botón volver -->
    <a href="dashboard.php" class="btn btn-outline-secondary mb-3" style="border-radius: 12px;">
        <i class="bi bi-arrow-left"></i> Volver al dashboard
    </a>

    <!-- Header -->
    <div class="page-header">
        <h1>
            <i class="bi bi-list-check"></i>
            Mis Tareas
        </h1>
    </div>

    <!-- Filtros -->
    <div class="section-top">
        <div>
            <button class="tab-btn active" data-filter="todas">Todas (<?= $total_tareas ?>)</button>
            <button class="tab-btn inactive" data-filter="pendiente">Pendientes (<?= $pendientes ?>)</button>
            <button class="tab-btn inactive" data-filter="en_proceso">En Proceso (<?= $en_proceso ?>)</button>
            <button class="tab-btn inactive" data-filter="completada">Completadas (<?= $completadas ?>)</button>
        </div>
    </div>

    <!-- Lista de tareas -->
    <div id="listaTareas">
        <?php if (count($tareas) > 0): ?>
            <?php foreach ($tareas as $t): ?>
                <?php
                    $prioridad = strtolower($t['prioridad'] ?? 'media');
                    $estado = strtolower($t['estado'] ?? 'pendiente');
                    $nombre_proyecto = $t['nombre_proyecto'] ?? 'Sin proyecto';
                    $id_proyecto = $t['id_proyecto'] ?? null;
                    
                    $estados_texto = [
                        'pendiente' => 'Pendiente',
                        'en_proceso' => 'En Proceso',
                        'completada' => 'Completada'
                    ];
                    
                    $nombre_estado = $estados_texto[$estado] ?? 'Pendiente';
                ?>

                <div class="task-card" data-estado="<?= $estado ?>">
                    <div class="task-left">
                        <!-- Checkbox de estado -->
                        <label class="task-checkbox-container" data-estado="<?= $estado ?>" data-id="<?= $t['id'] ?>">
                            <span class="checkmark"></span>
                        </label>
                        
                        <!-- Contenido clickeable -->
                        <div style="flex: 1; cursor: pointer;" onclick="verDetallesTarea(<?= $t['id'] ?>)">
                            <div class="task-title"><?= htmlspecialchars($t['titulo']); ?></div>
                            
                            <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                                <?php if ($id_proyecto): ?>
                                    <a href="proyecto_detalle.php?id=<?= $id_proyecto ?>" 
                                       class="task-project text-decoration-none"
                                       onclick="event.stopPropagation()">
                                        <i class="bi bi-folder"></i>
                                        <?= htmlspecialchars($nombre_proyecto) ?>
                                    </a>
                                <?php endif; ?>
                                
                                <span class="task-date">
                                    <i class="bi bi-calendar-check"></i>
                                    <?= date('d/m/Y', strtotime($t['fecha_limite'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Badges y menú -->
                    <div style="display: flex; align-items: center; gap: 12px;">
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

                        <!-- Menú de opciones -->
                        <div class="dropdown" style="display: inline-block;" onclick="event.stopPropagation()">
                            <button class="btn btn-sm" style="background: white; border: 2px solid #e9ecef; border-radius: 8px; padding: 6px 10px;" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-three-dots-vertical" style="font-size: 16px; color: #6c757d;"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
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
            <!-- Estado vacío -->
            <div style="background: white; border-radius: 20px; padding: 60px 30px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
                <div style="font-size: 80px; margin-bottom: 20px; opacity: 0.3;">
                    <i class="bi bi-check2-circle"></i>
                </div>
                <h3 style="color: #333; font-weight: 600; margin-bottom: 10px;">No tienes tareas asignadas</h3>
                <p style="color: #6c757d; margin-bottom: 20px;">Cuando te asignen tareas, aparecerán aquí</p>
                <a href="proyectos.php" class="btn btn-primary" style="border-radius: 12px;">
                    <i class="bi bi-folder-plus"></i> Ver proyectos
                </a>
            </div>
        <?php endif; ?>
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

// FILTROS DE TAREAS

document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        // Cambiar tab activo
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('active');
            b.classList.add('inactive');
        });
        this.classList.remove('inactive');
        this.classList.add('active');
        
        // Filtrar tareas
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


// CAMBIAR ESTADO DE TAREA (Checkbox)
// Ciclo: pendiente → en_proceso → completada → pendiente

document.querySelectorAll('.task-checkbox-container').forEach(checkbox => {
    checkbox.addEventListener('click', function() {
        const idTarea = this.getAttribute('data-id');
        const estadoActual = this.getAttribute('data-estado');
        
        // Determinar siguiente estado
        let nuevoEstado;
        if (estadoActual === 'pendiente') {
            nuevoEstado = 'en_proceso';
        } else if (estadoActual === 'en_proceso') {
            nuevoEstado = 'completada';
        } else {
            nuevoEstado = 'pendiente';
        }
        
        // Actualizar en servidor
        fetch('estado_tarea.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${idTarea}&estado=${nuevoEstado}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error al actualizar la tarea: ' + data.mensaje);
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
</script>

<?php include("../includes/footer.php"); ?>