<?php
/**
 * PROYECTOS - Página principal
 * Muestra todos los proyectos del usuario actual en formato de tarjetas.
 * Incluye modales para crear, editar y eliminar proyectos.
 */

session_start();
include("../config/bd.php");

// Verificar que el usuario haya iniciado sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// Obtener datos del usuario actual
$id_usuario = $_SESSION['id_usuario'];
$correo_usuario = trim(strtolower($_SESSION['usuario']));

// OBTENER PROYECTOS DEL USUARIO CON PROGRESO Y COLABORADORES
$query = $conexion->prepare("SELECT * FROM proyectos WHERE id_usuario = :id_usuario ORDER BY id DESC");
$query->bindParam(":id_usuario", $id_usuario);
$query->execute();
$proyectos = $query->fetchAll(PDO::FETCH_ASSOC);

// Calcular progreso de cada proyecto y obtener colaboradores
$progreso_proyectos = [];
$colaboradores_proyectos = []; // Array para almacenar usuarios por proyecto
$proyectos_activos = 0;
$proyectos_completados = 0;

foreach ($proyectos as $proyecto) {
    // Obtener progreso del proyecto
    $query_progreso = $conexion->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN estado = 'completada' THEN 1 ELSE 0 END) as completadas
        FROM tareas 
        WHERE id_proyecto = :id_proyecto
    ");
    $query_progreso->bindParam(":id_proyecto", $proyecto['id']);
    $query_progreso->execute();
    $stats = $query_progreso->fetch(PDO::FETCH_ASSOC);
    
    $total = $stats['total'] ?? 0;
    $completadas = $stats['completadas'] ?? 0;
    $porcentaje = $total > 0 ? round(($completadas / $total) * 100) : 0;
    $esta_completado = ($porcentaje == 100 && $total > 0);
    
    if ($esta_completado) {
        $proyectos_completados++;
    } else {
        $proyectos_activos++;
    }
    
    $progreso_proyectos[$proyecto['id']] = [
        'total' => $total,
        'completadas' => $completadas,
        'porcentaje' => $porcentaje,
        'completado' => $esta_completado
    ];
    
    // Obtener colaboradores únicos del proyecto (máximo 3)
    $query_colaboradores = $conexion->prepare("
        SELECT DISTINCT u.nombre, u.correo
        FROM tareas t
        INNER JOIN usuarios u ON t.id_asignado = u.id
        WHERE t.id_proyecto = :id_proyecto
        ORDER BY u.nombre
        LIMIT 3
    ");
    $query_colaboradores->bindParam(":id_proyecto", $proyecto['id']);
    $query_colaboradores->execute();
    $colaboradores_proyectos[$proyecto['id']] = $query_colaboradores->fetchAll(PDO::FETCH_ASSOC);
}

// Array de colores para las tarjetas
$colores = [
    // Tonos medios - NO demasiado saturados
    'linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%)', // 1. Índigo suave
    'linear-gradient(135deg, #ec4899 0%, #f43f5e 100%)', // 2. Rosa moderado
    'linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%)', // 3. Azul océano
    'linear-gradient(135deg, #10b981 0%, #14b8a6 100%)', // 4. Verde esmeralda
    'linear-gradient(135deg, #f59e0b 0%, #ef4444 100%)', // 5. Naranja-Rojo suave
    'linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%)', // 6. Púrpura-Rosa
    'linear-gradient(135deg, #06b6d4 0%, #3b82f6 100%)', // 7. Cian-Azul
    'linear-gradient(135deg, #14b8a6 0%, #10b981 100%)', // 8. Turquesa-Verde
    'linear-gradient(135deg, #f97316 0%, #fb923c 100%)', // 9. Naranja cálido
    'linear-gradient(135deg, #6366f1 0%, #a855f7 100%)', // 10. Violeta equilibrado
];

include("../includes/header.php");
?>

<style>
    body {
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
        }

    .page-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 40px 20px;
    }

    /* Header de la página */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 30px;
    }

    .page-title-section h1 {
        font-size: 32px;
        font-weight: 700;
        color: #333;
        margin: 0;
    }

    .page-title-section p {
        color: #999;
        font-size: 16px;
        margin: 5px 0 0 0;
    }

    /* Botón nuevo proyecto */
    .btn-new-project {
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

    .btn-new-project:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }
    /* Pestañas de filtro */
    .tabs-container {
        display: flex;
        gap: 12px;
        margin-bottom: 30px;
    }

    .tab {
        padding: 10px 24px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s;
        border: none;
    }

    .tab.active {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .tab.inactive {
        background: #e8e8ea;
        color: #666;
    }

    .tab.inactive:hover {
        background: #d8d8da;
    }

    /* Grid de tarjetas de proyectos */
    .projects-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 24px;
    }

    /* Tarjeta individual de proyecto */
    .project-card {
        border-radius: 20px;
        padding: 28px;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        min-height: 180px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
    }

    .project-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 30px rgba(0,0,0,0.2);
    }

    /* Textura de fondo sutil */
    .project-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: repeating-linear-gradient(
            0deg,
            rgba(255,255,255,0.03) 0px,
            rgba(255,255,255,0.03) 1px,
            transparent 1px,
            transparent 20px
        ),
        repeating-linear-gradient(
            90deg,
            rgba(255,255,255,0.03) 0px,
            rgba(255,255,255,0.03) 1px,
            transparent 1px,
            transparent 20px
        );
        pointer-events: none;
    }
    /* Contenedor de usuarios asignados */
    .project-users {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 15px;
    }

    /* Contenedor de avatares con superposición */
    .user-avatars {
        display: flex;
        align-items: center;
    }

    /* Avatar individual con iniciales */
    .user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 13px;
        color: #667eea;
        border: 3px solid rgba(255, 255, 255, 0.9);
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        margin-left: -8px; /* Superposición de avatares */
        transition: all 0.3s;
    }

    /* Primer avatar sin margen */
    .user-avatar:first-child {
        margin-left: 0;
    }

    /* Efecto hover en avatares */
    .user-avatar:hover {
        transform: translateY(-3px) scale(1.1);
        z-index: 10;
    }

    /* Contenido de la tarjeta */
    .project-card-content {
        position: relative;
        z-index: 1;
    }

    /* Título del proyecto - mejorado */
    .project-card h4 {
        color: white;
        font-weight: 700;
        font-size: 24px;
        margin-bottom: 20px;
        line-height: 1.3;
        text-shadow: 0 3px 6px rgba(0,0,0,0.3);
    }
    /* Botón de opciones (3 puntos) */
    .project-options {
        position: absolute;
        top: 15px;
        right: 15px;
        z-index: 10;
    }

    .btn-options {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.9);
        border: none;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }

    .btn-options:hover {
        background: white;
        transform: scale(1.1);
    }

    .btn-options i {
        color: #495057;
        font-size: 18px;
    }

    /* Menú desplegable */
    .dropdown-menu {
        border-radius: 12px;
        border: none;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        padding: 8px;
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

    .dropdown-item i {
        font-size: 16px;
    }

    /* Tags del proyecto - mejorados */
    .project-tags {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }

    .project-tag {
        background: rgba(255, 255, 255, 0.35);
        color: white;
        padding: 8px 16px;
        border-radius: 14px;
        font-size: 13px;
        font-weight: 700;
        backdrop-filter: blur(10px);
        text-shadow: 0 1px 3px rgba(0,0,0,0.25);
        border: 1.5px solid rgba(255, 255, 255, 0.3);
    }
    .project-footer {
        display: flex;
        flex-direction: column;
        gap: 12px;
        position: relative;
        z-index: 1;
    }

    .project-footer i {
        font-size: 16px;
    }

    .project-date {
        display: flex;
        align-items: center;
        gap: 8px;
        color: rgba(255, 255, 255, 0.95);
        font-size: 14px;
        font-weight: 600;
        text-shadow: 0 1px 2px rgba(0,0,0,0.2);
    }

    .progress-container {
        margin-top: 5px;
    }

    .progress-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }

    .progress-info .label {
        font-size: 12px;
        color: rgba(255, 255, 255, 0.9);
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 6px;
        text-shadow: 0 1px 2px rgba(0,0,0,0.2);
    }

    .progress-info .percentage {
        font-size: 14px;
        font-weight: 700;
        color: white;
        text-shadow: 0 1px 3px rgba(0,0,0,0.3);
    }

    .progress {
        height: 10px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.25);
        overflow: hidden;
        box-shadow: inset 0 1px 3px rgba(0,0,0,0.2);
        backdrop-filter: blur(10px);
    }

    .progress-bar {
        background: white;
        border-radius: 10px;
        transition: width 0.6s ease;
        height: 100%;
        box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    }

    .task-count {
        font-size: 11px;
        color: rgba(255, 255, 255, 0.85);
        margin-top: 6px;
        display: flex;
        align-items: center;
        gap: 4px;
        text-shadow: 0 1px 2px rgba(0,0,0,0.2);
    }

    .empty-state {
        text-align: center;
        padding: 80px 20px;
        background: white;
        border-radius: 20px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }

    .empty-state-icon {
        font-size: 100px;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    .empty-state h3 {
        color: #333;
        font-weight: 600;
        margin-bottom: 10px;
    }

    .empty-state p {
        color: #999;
        margin-bottom: 30px;
    }

    .modal-content {
        border-radius: 25px;
        border: none;
    }
    /* 
   FOER DE LA TARJETA - Versión Limpia*/

    .project-footer {
        display: flex;
        flex-direction: column;
        gap: 16px;
        position: relative;
        z-index: 1;
        margin-top: auto;
    }

    /* 
   ESLOS DE FECHA - VERSIÓN LIMPIA Y ELEGANTE*/

/* Fecha de vencimiento - Base minimalista */
.project-date {
    display: flex;
    align-items: center;
    gap: 8px;
    color: white;
    font-size: 14px;
    font-weight: 700;
    text-shadow: 0 2px 6px rgba(0,0,0,0.25);
    padding: 8px 14px;
    border-radius: 50px; /* Más redondeado = más moderno */
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
    width: fit-content;
}

.project-date i {
    font-size: 16px;
    filter: drop-shadow(0 2px 3px rgba(0,0,0,0.2));
}

/* Estado: VENCIDO (discreto, no agresivo) */
.project-date.expired {
    background: rgba(0, 0, 0, 0.25);
    opacity: 0.8;
}

.project-date.expired i {
    opacity: 0.7;
}

/* Estado: VENCE HOY (énfasis sin ser chillón) */
.project-date.today {
    background: rgba(255, 255, 255, 0.3);
    box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.3);
    animation: subtle-pulse 3s ease-in-out infinite;
}

/* Estado: URGENTE 1-3 días (alerta sutil) */
.project-date.urgent {
    background: rgba(255, 255, 255, 0.25);
}

/* Estado: PRÓXIMO 4-7 días (normal) */
.project-date.soon {
    background: rgba(255, 255, 255, 0.2);
}

/* Estado: NORMAL +7 días (muy sutil) */
.project-date.normal {
    background: rgba(255, 255, 255, 0.15);
}

/* Animación sutil para "Vence HOY" */
@keyframes subtle-pulse {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.3);
    }
    50% {
        transform: scale(1.02);
        box-shadow: 0 0 0 5px rgba(255, 255, 255, 0.4);
    }
}

/* Hover suave */
.project-date:hover {
    background: rgba(255, 255, 255, 0.35);
    transform: translateX(2px);
}

/* AJUSTES AL BADGE DE COMPLETADO*/

/* Tag de completado más discreto */
.project-tag.completed {
    background: rgba(255, 255, 255, 0.3);
    box-shadow: 0 2px 8px rgba(255,255,255,0.2);
}

/* Mejoras contenedor*/

/* Eliminar fondo del contenedor de progreso */
.progress-container {
    background: none; /* Más limpio sin caja */
    padding: 0;
    border-radius: 0;
    backdrop-filter: none;
    border: none;
    margin-top: 5px;
}

/* Info del progreso más sutil */
.progress-info .label {
    font-size: 13px;
    color: white;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    letter-spacing: 0.2px;
    opacity: 0.95;
}

/* Porcentaje más discreto */
.progress-info .percentage {
    font-size: 16px;
    font-weight: 700;
    color: white;
    text-shadow: 0 2px 6px rgba(0,0,0,0.35);
    background: rgba(255, 255, 255, 0.2);
    padding: 4px 12px;
    border-radius: 50px;
    min-width: 55px;
    text-align: center;
    backdrop-filter: blur(10px);
}

/* Barra de progreso más delgada y elegante */
.progress {
    height: 6px; /* Más delgada */
    border-radius: 50px;
    background: rgba(255, 255, 255, 0.25);
    overflow: hidden;
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    backdrop-filter: blur(10px);
}

.progress-bar {
    background: white;
    border-radius: 50px;
    transition: width 0.8s cubic-bezier(0.4, 0, 0.2, 1);
    height: 100%;
    box-shadow: 0 2px 6px rgba(255,255,255,0.4);
    position: relative;
    overflow: hidden;
}

/* Efecto shimmer más sutil */
.progress-bar::after {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.3),
        transparent
    );
    animation: shimmer 4s infinite; /* Más lento */
}

@keyframes shimmer {
    0% { left: -100%; }
    100% { left: 100%; }
}

/* Contador de tareas */
.task-count {
    font-size: 12px;
    color: white;
    font-weight: 600;
    margin-top: 6px;
    display: flex;
    align-items: center;
    gap: 5px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    opacity: 0.9;
}

.task-count i {
    font-size: 13px;
    filter: drop-shadow(0 2px 3px rgba(0,0,0,0.25));
}

/* Tags*/

.project-tag {
    background: rgba(255, 255, 255, 0.2); 
    color: white;
    padding: 7px 14px;
    border-radius: 50px;
    font-size: 12px;
    font-weight: 700;
    backdrop-filter: blur(10px);
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    transition: all 0.3s ease;
}

.project-tag:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-1px);
}

/* Titulo balanceado*/

.project-card h4 {
    color: white;
    font-weight: 700; 
    font-size: 24px;
    margin-bottom: 16px;
    line-height: 1.3;
    text-shadow: 0 3px 8px rgba(0,0,0,0.35);
    letter-spacing: -0.3px;
}

/* Responsive*/

@media (min-width: 1400px) {
    .project-card h4 {
        font-size: 26px;
    }
    
    .project-date {
        font-size: 15px;
    }
}

@media (max-width: 576px) {
    .project-card h4 {
        font-size: 20px;
    }
    
    .project-date {
        font-size: 13px;
        padding: 7px 12px;
    }
    
    .progress {
        height: 5px;
    }
}
    
</style>

<div class="page-container">
    <div class="page-header">
        <div class="page-title-section">
            <h1>Mis Proyectos</h1>
            <p><?= count($proyectos); ?> proyectos activos</p>
        </div>
        <button class="btn-new-project" data-bs-toggle="modal" data-bs-target="#modalNuevoProyecto">
            <i class="bi bi-plus-lg"></i>
            Nuevo proyecto
        </button>
    </div>

    <div class="tabs-container">
        <button class="tab active" data-filter="todos">Todos (<?= count($proyectos); ?>)</button>
        <button class="tab inactive" data-filter="activo">Activos (<?= $proyectos_activos ?>)</button>
        <button class="tab inactive" data-filter="completado">Completados (<?= $proyectos_completados ?>)</button>
    </div>

    <?php if (count($proyectos) > 0): ?>
        <div class="projects-grid">
        <?php foreach ($proyectos as $index => $p): 
    $color = $colores[$index % count($colores)];
    $tags = ['Proyecto'];
    if (stripos($p['nombre'], 'diseño') !== false || stripos($p['nombre'], 'prototipo') !== false) {
        $tags[] = 'Diseño';
    }
    if (stripos($p['nombre'], 'software') !== false || stripos($p['nombre'], 'calidad') !== false) {
        $tags[] = 'Testing';
    }
    if (stripos($p['nombre'], 'ágil') !== false || stripos($p['nombre'], 'scrum') !== false) {
        $tags[] = 'Scrum';
    }
    
    $progreso = $progreso_proyectos[$p['id']];
    $porcentaje = $progreso['porcentaje'];
    $completadas = $progreso['completadas'];
    $total = $progreso['total'];
    
    // Calcular estado de vencimiento
    $fecha_limite = new DateTime($p['fecha_limite']);
    $hoy = new DateTime();
    $hoy->setTime(0, 0, 0);
    $fecha_limite->setTime(0, 0, 0);
    
    $diff = $hoy->diff($fecha_limite);
    $dias_diferencia = (int)$diff->format('%R%a'); // Positivo = futuro, Negativo = pasado
    
    // Determinar mensaje y estilo
    if ($dias_diferencia < 0) {
        // Proyecto VENCIDO
        $dias_abs = abs($dias_diferencia);
        $mensaje_fecha = "Venció hace " . $dias_abs . " día" . ($dias_abs != 1 ? 's' : '');
        $icono_fecha = 'bi-x-circle-fill';
        $clase_fecha = 'expired';
    } elseif ($dias_diferencia == 0) {
        // Vence HOY
        $mensaje_fecha = "¡Vence HOY!";
        $icono_fecha = 'bi-exclamation-triangle-fill';
        $clase_fecha = 'today';
    } elseif ($dias_diferencia <= 3) {
        // Urgente (1-3 días)
        $mensaje_fecha = "Vence en " . $dias_diferencia . " día" . ($dias_diferencia != 1 ? 's' : '');
        $icono_fecha = 'bi-alarm-fill';
        $clase_fecha = 'urgent';
    } elseif ($dias_diferencia <= 7) {
        // Próximo (4-7 días)
        $mensaje_fecha = "Vence: " . date('d/m/Y', strtotime($p['fecha_limite']));
        $icono_fecha = 'bi-calendar-check';
        $clase_fecha = 'soon';
    } else {
        // Normal (más de 7 días)
        $mensaje_fecha = "Vence: " . date('d/m/Y', strtotime($p['fecha_limite']));
        $icono_fecha = 'bi-calendar-check';
        $clase_fecha = 'normal';
    }
?>
    <div class="project-card" 
         style="background: <?= $color; ?>;"
         data-estado="<?= $progreso['completado'] ? 'completado' : 'activo' ?>"
         data-vencimiento="<?= $clase_fecha ?>">
        
        <div class="project-options" onclick="event.stopPropagation()">
            <button class="btn-options" 
                    type="button" 
                    data-bs-toggle="dropdown" 
                    aria-expanded="false">
                <i class="bi bi-three-dots-vertical"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <a class="dropdown-item" href="proyecto_detalle.php?id=<?= $p['id'] ?>">
                        <i class="bi bi-eye-fill text-primary"></i>
                        Ver detalles
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="#" onclick="editarProyecto(<?= $p['id'] ?>); return false;">
                        <i class="bi bi-pencil-fill text-info"></i>
                        Editar
                    </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <a class="dropdown-item text-danger" href="#" onclick="eliminarProyecto(<?= $p['id'] ?>, '<?= htmlspecialchars($p['nombre']) ?>'); return false;">
                        <i class="bi bi-trash-fill"></i>
                        Eliminar
                    </a>
                </li>
            </ul>
        </div>
        
        <div onclick="window.location.href='proyecto_detalle.php?id=<?= $p['id']; ?>'">
            <div class="project-card-content">
                <h4><?= htmlspecialchars($p['nombre']); ?></h4>
                
                <div class="project-tags">
                    <?php foreach ($tags as $tag): ?>
                        <span class="project-tag"><?= $tag; ?></span>
                    <?php endforeach; ?>
                    
                    <?php if ($progreso['completado']): ?>
                        <span class="project-tag completed">
                            <i class="bi bi-check-circle-fill"></i> Completado
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="project-footer">
                <!-- FECHA CON ESTADO VISUAL -->
                <div class="project-date <?= $clase_fecha ?>">
                    <i class="<?= $icono_fecha ?>"></i>
                    <?= $mensaje_fecha ?>
                </div>
                
                <div class="progress-container">
                    <div class="progress-info">
                        <span class="label">
                            <i class="bi bi-graph-up"></i>
                            Progreso
                        </span>
                        <span class="percentage"><?= $porcentaje ?>%</span>
                    </div>
                    
                    <div class="progress">
                        <div class="progress-bar" style="width: <?= $porcentaje ?>%"></div>
                    </div>

                    <div class="task-count">
                        <i class="bi bi-check2-circle"></i>
                        <?= $completadas ?> de <?= $total ?> tareas
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="bi bi-folder-x"></i>
            </div>
            <h3>No tienes proyectos aún</h3>
            <p>Crea tu primer proyecto y empieza a organizarte</p>
            <button class="btn-new-project" data-bs-toggle="modal" data-bs-target="#modalNuevoProyecto">
                <i class="bi bi-folder-plus"></i>
                Crear primer proyecto
            </button>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="modalNuevoProyecto" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" id="modalContenido"></div>
    </div>
</div>

<div class="modal fade" id="modalEditarProyecto" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" id="modalEditarContenido"></div>
    </div>
</div>

<script>
document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.tab').forEach(t => {
            t.classList.remove('active');
            t.classList.add('inactive');
        });
        this.classList.remove('inactive');
        this.classList.add('active');
        
        const filtro = this.getAttribute('data-filter');
        const proyectos = document.querySelectorAll('.project-card');
        
        proyectos.forEach(proyecto => {
            if (filtro === 'todos') {
                proyecto.style.display = 'flex';
            } else {
                const estado = proyecto.getAttribute('data-estado');
                proyecto.style.display = estado === filtro ? 'flex' : 'none';
            }
        });
    });
});

function editarProyecto(idProyecto) {
    const modalEditar = new bootstrap.Modal(document.getElementById('modalEditarProyecto'));
    const contenedor = document.getElementById('modalEditarContenido');
    
    contenedor.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
            </div>
        </div>
    `;
    
    modalEditar.show();
    
    fetch(`editar_proyecto.php?modal=1&id=${idProyecto}`)
        .then(response => response.text())
        .then(html => {
            contenedor.innerHTML = html;
            const scripts = contenedor.querySelectorAll('script');
            scripts.forEach((scriptViejo) => {
                const scriptNuevo = document.createElement('script');
                scriptNuevo.textContent = scriptViejo.textContent;
                document.body.appendChild(scriptNuevo);
            });
        })
        .catch(error => {
            console.error('Error:', error);
            contenedor.innerHTML = `
                <div class="modal-body p-5 text-center">
                    <p class="text-danger">Error al cargar el formulario</p>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            `;
        });
}

function eliminarProyecto(idProyecto, nombreProyecto) {
    const modalHTML = `
        <div class="modal fade" id="modalEliminarProyecto" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius: 20px; border: none;">
                    <div class="modal-header" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; border-radius: 20px 20px 0 0; border: none;">
                        <h5 class="modal-title">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            Confirmar eliminación
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="mb-3">¿Estás seguro de eliminar el proyecto:</p>
                        <p class="fw-bold text-center fs-5 text-primary">"${nombreProyecto}"</p>
                        <div class="alert alert-warning" style="border-radius: 12px;">
                            <i class="bi bi-exclamation-circle"></i>
                            <strong>Advertencia:</strong> Esta acción eliminará también todas las tareas asociadas y no se puede deshacer.
                        </div>
                    </div>
                    <div class="modal-footer" style="border: none; padding: 20px;">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="border-radius: 12px;">
                            <i class="bi bi-x-circle"></i> Cancelar
                        </button>
                        <button type="button" class="btn btn-danger" onclick="confirmarEliminarProyecto(${idProyecto})" style="border-radius: 12px;">
                            <i class="bi bi-trash-fill"></i> Sí, eliminar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    if (!document.getElementById('modalEliminarProyecto')) {
        document.body.insertAdjacentHTML('beforeend', modalHTML);
    }
    
    const modal = new bootstrap.Modal(document.getElementById('modalEliminarProyecto'));
    modal.show();
}

function confirmarEliminarProyecto(idProyecto) {
    const modal = bootstrap.Modal.getInstance(document.getElementById('modalEliminarProyecto'));
    modal.hide();
    
    const loadingHTML = `
        <div class="position-fixed top-50 start-50 translate-middle" style="z-index: 9999;">
            <div class="spinner-border text-danger" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Eliminando...</span>
            </div>
        </div>
    `;
    document.body.insertAdjacentHTML('beforeend', loadingHTML);
    
    fetch('eliminar_proyecto.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${idProyecto}`
    })
    .then(response => response.json())
    .then(data => {
        document.querySelector('.spinner-border')?.parentElement.remove();
        
        if (data.success) {
            alert(' Proyecto eliminado correctamente');
            window.location.reload();
        } else {
            alert(' Error: ' + data.mensaje);
        }
    })
    .catch(error => {
        document.querySelector('.spinner-border')?.parentElement.remove();
        console.error('Error:', error);
        alert(' Error al eliminar el proyecto');
    });
}

const modalElement = document.getElementById('modalNuevoProyecto');

if (modalElement) {
    modalElement.addEventListener('show.bs.modal', function () {
        const contenedor = document.getElementById('modalContenido');
        
        contenedor.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
            </div>
        `;
        
        fetch('crear_proyecto.php?modal=1')
            .then(response => response.text())
            .then(html => {
                contenedor.innerHTML = html;
                
                const scripts = contenedor.querySelectorAll('script');
                scripts.forEach((scriptViejo) => {
                    const scriptNuevo = document.createElement('script');
                    scriptNuevo.textContent = scriptViejo.textContent;
                    document.body.appendChild(scriptNuevo);
                });
            })
            .catch(error => {
                console.error('Error al cargar modal:', error);
                contenedor.innerHTML = `
                    <div class="modal-body p-5 text-center">
                        <p class="text-danger">Error al cargar el formulario</p>
                        <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                `;
            });
    });
}

window.addEventListener('message', function(event) {
    if (event.data === 'proyecto_creado') {
        const modal = bootstrap.Modal.getInstance(modalElement);
        if (modal) {
            modal.hide();
        }
        
        setTimeout(() => {
            window.location.reload();
        }, 300);
    }
    
    if (event.data === 'proyecto_editado') {
        const modalEditar = bootstrap.Modal.getInstance(document.getElementById('modalEditarProyecto'));
        if (modalEditar) {
            modalEditar.hide();
        }
        
        setTimeout(() => {
            alert(' Proyecto actualizado correctamente');
            window.location.reload();
        }, 300);
    }
    // Filtros de vencimiento
document.querySelectorAll('[data-filter-vencimiento]').forEach(btn => {
    btn.addEventListener('click', function() {
        // Resetear filtros normales
        document.querySelectorAll('[data-filter]').forEach(t => {
            t.classList.remove('active');
            t.classList.add('inactive');
        });
        
        // Activar este filtro
        document.querySelectorAll('[data-filter-vencimiento]').forEach(t => {
            t.classList.remove('active');
            t.classList.add('inactive');
        });
        this.classList.remove('inactive');
        this.classList.add('active');
        
        const filtro = this.getAttribute('data-filter-vencimiento');
        const proyectos = document.querySelectorAll('.project-card');
        
        proyectos.forEach(proyecto => {
            const estadoVencimiento = proyecto.getAttribute('data-vencimiento');
            
            if (filtro === 'vencido') {
                proyecto.style.display = estadoVencimiento === 'expired' ? 'flex' : 'none';
            } else if (filtro === 'urgente') {
                proyecto.style.display = (estadoVencimiento === 'today' || estadoVencimiento === 'urgent') ? 'flex' : 'none';
            }
        });
    });
});

// Modificar el filtro "Todos" para que también resetee filtros de vencimiento
document.querySelector('[data-filter="todos"]').addEventListener('click', function() {
    document.querySelectorAll('[data-filter-vencimiento]').forEach(t => {
        t.classList.remove('active');
        t.classList.add('inactive');
    });
});
});
</script>

<?php include("../includes/footer.php"); ?>