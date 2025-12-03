<?php
/**
 * DASHBOARD - Página principal del usuario
 * 
 * Muestra una vista general con:
 * - Estadísticas de proyectos y tareas
 * - Los 3 proyectos más próximos a vencer
 * - Barra de progreso de cada proyecto
 * - Enlaces rápidos a secciones principales
 * 
 * @author SharkTask Team
 * @version 2.0
 */

session_start();
include("../config/bd.php");

// ===============================================
// VERIFICACIÓN DE SESIÓN
// ===============================================
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// Obtener ID del usuario actual
$id_usuario = $_SESSION['id_usuario'];

// ===============================================
// OBTENER DATOS DEL USUARIO
// ===============================================
$query_usuario = $conexion->prepare("
    SELECT nombre, correo 
    FROM usuarios 
    WHERE id = :id
");
$query_usuario->bindParam(":id", $id_usuario);
$query_usuario->execute();
$datos_usuario = $query_usuario->fetch(PDO::FETCH_ASSOC);

// Asignar valores por defecto si no se encuentran
$nombre_usuario = $datos_usuario['nombre'] ?? 'Usuario';
$correo_usuario = strtolower(trim($datos_usuario['correo'] ?? ''));

// ===============================================
// ESTADÍSTICAS: TAREAS PENDIENTES
// ===============================================
$stats_tareas_pendientes = $conexion->prepare("
    SELECT COUNT(*) as total 
    FROM tareas
    WHERE id_asignado = :id 
    AND estado = 'pendiente'
");
$stats_tareas_pendientes->bindParam(":id", $id_usuario);
$stats_tareas_pendientes->execute();
$total_pendientes = $stats_tareas_pendientes->fetch(PDO::FETCH_ASSOC)['total'];

// ===============================================
// ESTADÍSTICAS: TOTAL DE PROYECTOS
// ===============================================
// Contar proyectos donde el usuario es creador o miembro
$stats_proyectos = $conexion->prepare("
    SELECT COUNT(DISTINCT p.id) as total
    FROM proyectos p
    WHERE p.id_usuario = :id_usuario
    OR p.id IN (
        SELECT m.id_proyecto 
        FROM miembros m 
        WHERE LOWER(TRIM(m.correo_miembro)) = :correo
    )
");
$stats_proyectos->bindParam(":id_usuario", $id_usuario);
$stats_proyectos->bindParam(":correo", $correo_usuario);
$stats_proyectos->execute();
$total_proyectos = $stats_proyectos->fetch(PDO::FETCH_ASSOC)['total'];

// ===============================================
// OBTENER PROYECTOS PRÓXIMOS A VENCER
// ===============================================
// Consulta los 3 proyectos con fecha límite más cercana (que no hayan vencido)
// Incluye el conteo de tareas totales y completadas para calcular el progreso
$query_proyectos = $conexion->prepare("
    SELECT DISTINCT 
        p.id, 
        p.nombre, 
        p.descripcion, 
        p.fecha_limite,
        (SELECT COUNT(*) FROM tareas WHERE id_proyecto = p.id) as total_tareas,
        (SELECT COUNT(*) FROM tareas WHERE id_proyecto = p.id AND estado = 'completada') as tareas_completadas
    FROM proyectos p
    WHERE (
        p.id_usuario = :id_usuario
        OR p.id IN (
            SELECT m.id_proyecto 
            FROM miembros m 
            WHERE LOWER(TRIM(m.correo_miembro)) = :correo
        )
    )
    AND p.fecha_limite >= CURDATE()
    ORDER BY p.fecha_limite ASC
    LIMIT 3
");
$query_proyectos->bindParam(":id_usuario", $id_usuario);
$query_proyectos->bindParam(":correo", $correo_usuario);
$query_proyectos->execute();
$proyectos = $query_proyectos->fetchAll(PDO::FETCH_ASSOC);

// ===============================================
// OBTENER MIEMBROS DE CADA PROYECTO
// ===============================================
$miembros_por_proyecto = [];
foreach ($proyectos as $proyecto) {
    $query_miembros = $conexion->prepare("
        SELECT DISTINCT u.nombre, u.correo
        FROM miembros m
        INNER JOIN usuarios u ON LOWER(TRIM(m.correo_miembro)) = LOWER(TRIM(u.correo))
        WHERE m.id_proyecto = :id_proyecto
        LIMIT 3
    ");
    $query_miembros->bindParam(":id_proyecto", $proyecto['id']);
    $query_miembros->execute();
    $miembros_por_proyecto[$proyecto['id']] = $query_miembros->fetchAll(PDO::FETCH_ASSOC);
}

// ===============================================
// FUNCIÓN AUXILIAR: CALCULAR PORCENTAJE
// ===============================================
/**
 * Calcula el porcentaje de tareas completadas
 * 
 * @param int $total Total de tareas del proyecto
 * @param int $completadas Tareas completadas
 * @return int Porcentaje redondeado (0-100)
 */
function calcularPorcentaje($total, $completadas) {
    if ($total == 0) return 0;
    return round(($completadas / $total) * 100);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SharkTask</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        /* Estilos generales */
        body {
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        /* Header de bienvenida */
        .welcome-header {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .welcome-header h2 {
            font-size: 32px;
            font-weight: 700;
            color: #333;
            margin: 0 0 5px 0;
        }

        .welcome-header .subtitle {
            color: #6c757d;
            font-size: 15px;
        }
        /* Card tareas*/
        .tasks-highlight {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 30px;
            color: white;
            margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .tasks-highlight .left h3 {
            font-size: 48px;
            font-weight: 800;
            margin: 0;
        }

        .tasks-highlight .left p {
            margin: 0;
            opacity: 0.95;
            font-size: 15px;
        }

        .tasks-highlight .right .btn-ver-tareas {
            background: white;
            color: #667eea;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s;
            cursor: pointer;
        }

        .tasks-highlight .right .btn-ver-tareas:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.2);
        }


        /* Header de seccion */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .section-header h4 {
            font-size: 22px;
            font-weight: 700;
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-header h4 i {
            font-size: 24px;
        }

        /* Botón para ver todos los proyectos */
        .btn-ver-todos {
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

        .btn-ver-todos:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        /* Grid de proyectos */
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }

        /* Tarjeta de proyecto */
        .project-card {
            background: white;
            border-radius: 20px;
            padding: 28px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            animation: fadeInUp 0.7s ease-out;
        }

        .project-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .project-card .project-title {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin-bottom: 14px;
        }

        /* Contenedor de miembros del proyecto */
        .project-card .project-members {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }

        .project-card .member-tag {
            font-size: 12px;
            color: #667eea;
            background: #f0f2ff;
            padding: 5px 12px;
            border-radius: 12px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        /* Etiqueta cuando no hay miembros */
        .project-card .member-tag.empty {
            background: #f8f9fa;
            color: #999;
        }

        /* Fecha límite  */
        .project-card .project-deadline {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            margin-bottom: 18px;
            font-weight: 600;
            padding: 8px 12px;
            border-radius: 10px;
            width: fit-content;
        }

        /* Color naranja: más de 3 días */
        .project-card .project-deadline.warning {
            background: #fff3e0;
            color: #ff9800;
        }

        /* Color rojo: 1-3 días restantes (urgente) */
        .project-card .project-deadline.danger {
            background: #ffebee;
            color: #dc3545;
        }

        /* Color rojo intenso: VENCE HOY */
        .project-card .project-deadline.today {
            background: linear-gradient(135deg, #ff5252 0%, #f44336 100%);
            color: white;
            font-weight: 700;
            animation: pulse 2s infinite;
            box-shadow: 0 4px 12px rgba(244, 67, 54, 0.4);
        }

        /* Color gris: YA VENCIÓ */
        .project-card .project-deadline.expired {
            background: #f5f5f5;
            color: #757575;
            text-decoration: line-through;
            opacity: 0.8;
        }

        /* Animación de pulso para "Vence HOY" */
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 4px 12px rgba(244, 67, 54, 0.4);
            }
            50% {
                transform: scale(1.05);
                box-shadow: 0 6px 20px rgba(244, 67, 54, 0.6);
            }
        }

        /* Barra de progeso */
        .progress-container {
            margin-top: 15px;
        }

        .progress-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .progress-info .label {
            font-size: 13px;
            color: #6c757d;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .progress-info .percentage {
            font-size: 15px;
            font-weight: 700;
            color: #667eea;
        }

        /* Barra de progreso personalizada */
        .progress {
            height: 10px;
            border-radius: 10px;
            background: #f0f2ff;
            overflow: hidden;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
        }

        .progress-bar {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            transition: width 0.6s ease;
            height: 100%;
        }

        .progress-container .task-count {
            font-size: 12px;
            color: #999;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        /* Estado vacio */
        .empty-state {
            background: white;
            border-radius: 20px;
            padding: 60px 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .empty-state .icon {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.5;
            color: #667eea;
        }

        .empty-state h5 {
            color: #6c757d;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #adb5bd;
            font-size: 14px;
            margin-bottom: 25px;
        }

        .empty-state .btn-crear {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 28px;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .empty-state .btn-crear:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        /* Animaciones */
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .projects-grid {
                grid-template-columns: 1fr;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .welcome-header h2 {
                font-size: 26px;
            }
        }
    </style>
</head>
<body>
    <?php include("../includes/header.php"); ?>

    <div class="dashboard-container">
        
        <!-- Header bienvenida-->
        <div class="welcome-header">
            <h2>
                Hola, <?= htmlspecialchars($nombre_usuario); ?>
            </h2>
            <p class="subtitle">
                Aquí está tu resumen del día
            </p>
        </div>

        <!-- Card tareas pendientes -->
        <div class="tasks-highlight">
            <div class="left">
                <h3><?= $total_pendientes ?></h3>
                <p>Tareas pendientes esta semana</p>
            </div>
            <div class="right">
                <!-- Ir a la página de todas las tareas del usuario -->
                <a href="mis_tareas.php" class="btn-ver-tareas text-decoration-none">
                    Ver tareas
                </a>
            </div>
        </div>

        <!-- SECCIÓN: Proyectos próximos a vencer -->
        <div class="section-header">
            <h4>
                <i class="bi bi-clock-history"></i>
                Proyectos próximos a vencer
            </h4>
            <a href="proyectos.php" class="btn-ver-todos">
                Ver todos
                <i class="bi bi-arrow-right"></i>
            </a>
        </div>

        <?php if (!empty($proyectos)): ?>
            <!-- Si hay proyectos, mostrar el grid -->
            <div class="projects-grid">
            <?php foreach ($proyectos as $p): 
    // Calcular porcentaje de progreso
    $porcentaje = calcularPorcentaje($p['total_tareas'], $p['tareas_completadas']);
    
    // Calcular días restantes hasta la fecha límite
    $fecha = new DateTime($p['fecha_limite']);
    $hoy = new DateTime();
    $hoy->setTime(0, 0, 0); // Resetear hora para comparar solo fechas
    $fecha->setTime(0, 0, 0);
    
    $diff = $hoy->diff($fecha);
    $dias_restantes = (int)$diff->format('%R%a'); // Positivo = futuro, Negativo = pasado
    
    // Determinar mensaje y clase CSS según estado
    if ($dias_restantes < 0) {
        // Ya venció
        $dias_abs = abs($dias_restantes);
        $mensaje_vencimiento = "Venció hace " . $dias_abs . " día" . ($dias_abs != 1 ? 's' : '');
        $clase_urgencia = 'expired';
        $icono = 'bi-x-circle';
    } elseif ($dias_restantes == 0) {
        // Vence HOY
        $mensaje_vencimiento = "¡Vence HOY!";
        $clase_urgencia = 'today';
        $icono = 'bi-exclamation-triangle-fill';
    } elseif ($dias_restantes <= 3) {
        // Vence en 1-3 días (urgente)
        $mensaje_vencimiento = "Vence en " . $dias_restantes . " día" . ($dias_restantes != 1 ? 's' : '');
        $clase_urgencia = 'danger';
        $icono = 'bi-alarm-fill';
    } else {
        // Vence en más de 3 días
        $mensaje_vencimiento = "Vence en " . $dias_restantes . " día" . ($dias_restantes != 1 ? 's' : '');
        $clase_urgencia = 'warning';
        $icono = 'bi-alarm';
    }
?>
    <!-- Tarjeta de proyecto -->
    <div class="project-card" onclick="window.location.href='proyecto_detalle.php?id=<?= $p['id'] ?>'">
        
        <!-- Título del proyecto -->
        <div class="project-title">
            <?= htmlspecialchars($p['nombre']); ?>
        </div>
        
        <!-- Miembros del proyecto -->
        <div class="project-members">
            <i class="bi bi-people" style="color: #667eea;"></i>
            <?php 
            $miembros = $miembros_por_proyecto[$p['id']] ?? [];
            
            if (!empty($miembros)) {
                foreach ($miembros as $miembro) {
                    $primer_nombre = explode(' ', $miembro['nombre'])[0];
                    echo '<span class="member-tag">' . htmlspecialchars($primer_nombre) . '</span>';
                }
            } else {
                echo '<span class="member-tag empty">Sin miembros</span>';
            }
            ?>
        </div>
        
        <!-- Fecha límite con indicador de urgencia -->
        <div class="project-deadline <?= $clase_urgencia ?>">
            <i class="<?= $icono ?>"></i>
            <?= $mensaje_vencimiento ?>
        </div>

        <!-- Barra de progreso -->
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
                <?= $p['tareas_completadas'] ?> de <?= $p['total_tareas'] ?> tareas completadas
            </div>
        </div>
    </div>
<?php endforeach; ?>
            </div>
        <?php else: ?>
            <!-- Si no hay proyectos próximos a vencer -->
            <div class="empty-state">
                <div class="icon">
                    <i class="bi bi-folder-x"></i>
                </div>
                <h5>No tienes proyectos próximos a vencer</h5>
                <p>Todos tus proyectos están al día o aún no has creado ninguno</p>
                <a href="proyectos.php" class="btn-crear">
                    <i class="bi bi-folder-plus"></i>
                    Ver todos los proyectos
                </a>
            </div>
        <?php endif; ?>

    </div>

    <!-- Scripts necesarios de Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>