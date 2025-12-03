<?php
session_start();
include("../config/bd.php");

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$id_tarea = $_GET['id'] ?? null;

if (!$id_tarea) {
    echo "Tarea no encontrada";
    exit;
}

// Obtener datos completos de la tarea
$query = $conexion->prepare("
    SELECT 
        t.*,
        u.nombre AS nombre_asignado,
        u.correo AS correo_asignado,
        p.nombre AS nombre_proyecto
    FROM tareas t
    LEFT JOIN usuarios u ON t.id_asignado = u.id
    LEFT JOIN proyectos p ON t.id_proyecto = p.id
    WHERE t.id = :id
");
$query->bindParam(":id", $id_tarea);
$query->execute();
$tarea = $query->fetch(PDO::FETCH_ASSOC);

if (!$tarea) {
    echo "Tarea no encontrada";
    exit;
}

// Mapear estados y prioridades
$estados = [
    'pendiente' => ['nombre' => 'Pendiente', 'color' => '#f59e0b', 'icono' => 'bi-clock'],
    'en_proceso' => ['nombre' => 'En Proceso', 'color' => '#3b82f6', 'icono' => 'bi-hourglass-split'],
    'completada' => ['nombre' => 'Completada', 'color' => '#10b981', 'icono' => 'bi-check-circle-fill']
];

$prioridades = [
    'baja' => ['nombre' => 'Baja', 'color' => '#10b981'],
    'media' => ['nombre' => 'Media', 'color' => '#f59e0b'],
    'alta' => ['nombre' => 'Alta', 'color' => '#ef4444'],
    'urgente' => ['nombre' => 'Urgente', 'color' => '#dc2626']
];

$estado_actual = $estados[$tarea['estado']] ?? $estados['pendiente'];
$prioridad_actual = $prioridades[$tarea['prioridad']] ?? $prioridades['media'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de Tarea</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    
    <style>
        body {
        background: white;
        font-family: 'Segoe UI', sans-serif;
        padding: 0;
        margin: 0;
        overflow-x: hidden; /* evita scroll horizontal */
        }

        .modal-container {
            position: relative; 
            overflow: visible; 
        }

        .modal-header-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            color: white;
            position: relative;
            overflow: visible; 
        }

        .modal-header-custom h2 {
            margin: 0 0 8px 0;
            font-weight: 700;
            font-size: 24px;
        }

        .project-tag {
            background: rgba(255, 255, 255, 0.2);
            padding: 6px 14px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .modal-body-custom {
        padding: 30px;
        overflow: visible; 
    }

        .info-section {
            margin-bottom: 25px;
        }

        .info-label {
            font-size: 12px;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .info-content {
            font-size: 15px;
            color: #333;
            line-height: 1.6;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
        }

        .priority-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
        }

        .assignee-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .assignee-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 18px;
        }

        .assignee-info h6 {
            margin: 0;
            font-weight: 600;
            color: #333;
        }

        .assignee-info p {
            margin: 0;
            font-size: 13px;
            color: #6c757d;
        }

        .description-box {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 12px;
        border-left: 4px solid #667eea;
        min-height: 80px;
        word-wrap: break-word; /* ← AGREGAR para textos largos */
        overflow-wrap: break-word; /* ← AGREGAR */
        }

        .btn-action {
        padding: 12px 24px;
        border-radius: 12px;
        font-weight: 600;
        border: none;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 8px;
        justify-content: center;
        cursor: pointer; 
        }


        .btn-edit {
            background: #667eea;
            color: white;
        }

        .btn-edit:hover {
        background: #5568d3;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
        color: white; 
        }

        .btn-delete {
            background: white;
            color: #dc3545;
            border: 2px solid #dc3545;
        }

        .btn-delete:hover {
        background: #dc3545;
        color: white;
        border-color: #dc3545;
        }

        .btn-close-modal {
            background: white;
            color: #6c757d;
            border: 2px solid #e9ecef;
        }

        .btn-close-modal:hover {
            background: #f8f9fa;
            border-color: #667eea;
            color: #667eea;
        }
    </style>
</head>
<body>

<div class="modal-container">
    <!-- HEADER -->
    <div class="modal-header-custom">
        <h2><?= htmlspecialchars($tarea['titulo']); ?></h2>
        <span class="project-tag">
            <i class="bi bi-folder"></i>
            <?= htmlspecialchars($tarea['nombre_proyecto']); ?>
        </span>
    </div>

    <!-- BODY -->
    <div class="modal-body-custom">
        
        <!-- ESTADO Y PRIORIDAD -->
        <div class="info-section">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="info-label">
                        <i class="bi bi-flag-fill"></i>
                        Estado
                    </div>
                    <span class="status-badge" style="background: <?= $estado_actual['color'] ?>20; color: <?= $estado_actual['color'] ?>;">
                        <i class="<?= $estado_actual['icono'] ?>"></i>
                        <?= $estado_actual['nombre'] ?>
                    </span>
                </div>

                <div class="col-md-6">
                    <div class="info-label">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        Prioridad
                    </div>
                    <span class="priority-badge" style="background: <?= $prioridad_actual['color'] ?>20; color: <?= $prioridad_actual['color'] ?>;">
                        <i class="bi bi-flag-fill"></i>
                        <?= $prioridad_actual['nombre'] ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- DESCRIPCIÓN -->
        <div class="info-section">
            <div class="info-label">
                <i class="bi bi-card-text"></i>
                Descripción
            </div>
            <div class="description-box">
                <?= nl2br(htmlspecialchars($tarea['descripcion'])); ?>
            </div>
        </div>

        <!-- FECHA LÍMITE -->
        <div class="info-section">
            <div class="info-label">
                <i class="bi bi-calendar-check"></i>
                Fecha Límite
            </div>
            <div class="info-content">
                <strong><?= date('d/m/Y', strtotime($tarea['fecha_limite'])); ?></strong>
                <?php
                    $fecha = new DateTime($tarea['fecha_limite']);
                    $hoy = new DateTime();
                    $diff = $hoy->diff($fecha);
                    $dias = (int)$diff->format('%R%a');
                    
                    if ($dias < 0) {
                        echo '<span style="color: #dc3545; margin-left: 10px;">(Vencida hace ' . abs($dias) . ' días)</span>';
                    } elseif ($dias == 0) {
                        echo '<span style="color: #f59e0b; margin-left: 10px;">(¡Vence HOY!)</span>';
                    } elseif ($dias <= 3) {
                        echo '<span style="color: #f59e0b; margin-left: 10px;">(Vence en ' . $dias . ' días)</span>';
                    } else {
                        echo '<span style="color: #10b981; margin-left: 10px;">(Faltan ' . $dias . ' días)</span>';
                    }
                ?>
            </div>
        </div>

        <!-- ASIGNADO A -->
        <div class="info-section">
            <div class="info-label">
                <i class="bi bi-person-fill"></i>
                Asignado a
            </div>
            <?php if ($tarea['nombre_asignado']): ?>
                <div class="assignee-card">
                    <div class="assignee-avatar">
                        <?= strtoupper(substr($tarea['nombre_asignado'], 0, 2)); ?>
                    </div>
                    <div class="assignee-info">
                        <h6><?= htmlspecialchars($tarea['nombre_asignado']); ?></h6>
                        <p><?= htmlspecialchars($tarea['correo_asignado']); ?></p>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-muted">Sin asignar</p>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
function editarTarea(idTarea) {
    window.parent.postMessage({tipo: 'editar_tarea', id: idTarea}, '*');
}

function confirmarEliminar(idTarea, nombreTarea) {
    window.parent.postMessage({tipo: 'eliminar_tarea', id: idTarea, nombre: nombreTarea}, '*');
}
</script>

</body>
</html>