<?php
/**
 * MIS TAREAS - Todas las tareas del usuario
 * Muestra todas las tareas asignadas al usuario actual con filtros y agrupación por proyecto.
 */

session_start();
include("../config/bd.php");

// Verificar sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

$id_usuario = $_SESSION['id_usuario'];

// ===============================================
// OBTENER TODAS LAS TAREAS DEL USUARIO
// ===============================================
$query_tareas = $conexion->prepare("
    SELECT t.*, p.nombre as nombre_proyecto, p.id as id_proyecto
    FROM tareas t
    LEFT JOIN proyectos p ON t.id_proyecto = p.id
    WHERE t.id_asignado = :id_usuario
    ORDER BY 
        CASE WHEN t.estado = 'pendiente' THEN 0 ELSE 1 END,
        t.fecha_limite ASC
");
$query_tareas->bindParam(":id_usuario", $id_usuario);
$query_tareas->execute();
$tareas = $query_tareas->fetchAll(PDO::FETCH_ASSOC);

// Contar tareas por estado
$total_tareas = count($tareas);
$pendientes = count(array_filter($tareas, fn($t) => $t['estado'] == 'pendiente'));
$completadas = count(array_filter($tareas, fn($t) => $t['estado'] == 'completada'));

include("../includes/header.php");
?>

<style>
    body {
        background: linear-gradient(135deg, #667eea10 0%, #764ba210 100%);
        min-height: 100vh;
    }

    .container {
        max-width: 1200px;
        padding-top: 30px;
        padding-bottom: 50px;
    }

    /* Encabezado de página */
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

    .page-header p {
        color: #6c757d;
        margin: 0;
        font-size: 15px;
    }

    /* Sección superior con filtros */
    .section-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        flex-wrap: wrap;
        gap: 15px;
    }

    /* Tabs de filtro */
    .tab-btn {
        border: none;
        padding: 10px 20px;
        border-radius: 12px;
        font-weight: 600;
        background: #e9ecef;
        color: #6c757d;
        margin-right: 8px;
        transition: all 0.3s;
        cursor: pointer;
        font-size: 14px;
    }
    
    .tab-btn:hover {
        background: #dee2e6;
    }
    
    .tab-btn.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    /* Tarjeta de tarea */
    .task-card {
        background: white;
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
        flex: 1;
    }

    .task-checkbox {
        width: 22px;
        height: 22px;
        cursor: pointer;
        accent-color: #667eea;
        margin-top: 2px;
    }

    .task-info {
        flex: 1;
    }

    .task-title {
        font-size: 16px;
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
    }

    .task-meta {
        display: flex;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
    }

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

    .task-date {
        font-size: 13px;
        color: #868e96;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .task-right {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    /* Etiqueta de prioridad */
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

    /* Estado vacío */
    .empty-state {
        background: white;
        border-radius: 20px;
        padding: 60px 30px;
        text-align: center;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }

    .empty-state .icon {
        font-size: 80px;
        margin-bottom: 20px;
        opacity: 0.3;
    }

    .empty-state h3 {
        color: #333;
        font-weight: 600;
        margin-bottom: 10px;
    }

    .empty-state p {
        color: #6c757d;
        margin-bottom: 20px;
    }

    /* Botón para volver */
    .btn-back {
        background: white;
        border: 2px solid #e9ecef;
        color: #6c757d;
        padding: 10px 20px;
        border-radius: 12px;
        font-weight: 600;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }

    .btn-back:hover {
        border-color: #667eea;
        color: #667eea;
        transform: translateX(-3px);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .task-card {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }

        .task-right {
            width: 100%;
            justify-content: space-between;
        }
    }
</style>

<div class="container">
    
    <!-- Botón volver -->
    <a href="dashboard.php" class="btn-back mb-3">
        <i class="bi bi-arrow-left"></i> Volver al dashboard
    </a>

    <!-- Encabezado -->
    <div class="page-header">
        <h1>
            <i class="bi bi-list-check"></i>
            Mis Tareas
        </h1>
        <p><?= $total_tareas ?> tareas en total · <?= $pendientes ?> pendientes · <?= $completadas ?> completadas</p>
    </div>

    <!-- Filtros -->
    <div class="section-top">
        <div>
            <button class="tab-btn active" data-filter="todas">Todas (<?= $total_tareas ?>)</button>
            <button class="tab-btn" data-filter="pendiente">Pendientes (<?= $pendientes ?>)</button>
            <button class="tab-btn" data-filter="completada">Completadas (<?= $completadas ?>)</button>
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
                ?>

                <div class="task-card" data-estado="<?= $estado ?>">
                    <div class="task-left">
                        <input type="checkbox" 
                               class="task-checkbox" 
                               <?= ($estado == 'completada') ? 'checked' : '' ?>
                               data-id="<?= $t['id'] ?>">
                        
                        <div class="task-info">
                            <div class="task-title"><?= htmlspecialchars($t['titulo']); ?></div>
                            
                            <div class="task-meta">
                                <?php if ($id_proyecto): ?>
                                    <a href="tareas.php?id=<?= $id_proyecto ?>" 
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

                    <div class="task-right">
                        <span class="priority-tag <?= $prioridad ?>">
                            <?= ucfirst($prioridad) ?>
                        </span>
                    </div>
                </div>

            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="icon">
                    <i class="bi bi-check2-circle"></i>
                </div>
                <h3>No tienes tareas asignadas</h3>
                <p>Cuando te asignen tareas, aparecerán aquí</p>
                <a href="proyectos.php" class="btn btn-primary" style="border-radius: 12px;">
                    <i class="bi bi-folder-plus"></i> Ver proyectos
                </a>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
/**
 * FILTROS DE TAREAS
 * Permite filtrar las tareas por estado (todas, pendientes, completadas)
 */
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        // Actualizar botón activo
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        // Obtener filtro seleccionado
        const filter = this.getAttribute('data-filter');
        const tareas = document.querySelectorAll('.task-card');
        
        // Mostrar/ocultar tareas según filtro
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

/**
 * CAMBIAR ESTADO DE TAREA (checkbox)
 * Actualiza el estado de la tarea cuando se marca/desmarca el checkbox
 */
document.querySelectorAll('.task-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const idTarea = this.getAttribute('data-id');
        const nuevoEstado = this.checked ? 'completada' : 'pendiente';
        
        // Enviar actualización al servidor
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
                // Actualizar el atributo data-estado de la tarjeta
                const taskCard = this.closest('.task-card');
                taskCard.setAttribute('data-estado', nuevoEstado);
                
                // Recargar después de 500ms para actualizar contadores
                setTimeout(() => location.reload(), 500);
            } else {
                alert('Error al actualizar la tarea');
                this.checked = !this.checked; // Revertir checkbox
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al actualizar la tarea');
            this.checked = !this.checked; // Revertir checkbox
        });
    });
});
</script>

<?php include("../includes/footer.php"); ?>