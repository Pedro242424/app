<?php
session_start();
include("../config/bd.php");

if (!isset($_SESSION['usuario'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$id_tarea = $_GET['id'] ?? null;
$mensaje = "";

if (!$id_tarea) {
    echo "Tarea no encontrada";
    exit;
}

// PROCESAR FORMULARIO
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $fecha_limite = $_POST['fecha_limite'] ?? '';
    $prioridad = $_POST['prioridad'] ?? 'media';
    $estado = $_POST['estado'] ?? 'pendiente';
    $id_asignado = $_POST['id_asignado'] ?? null;

    if (empty($titulo) || empty($descripcion) || empty($fecha_limite)) {
        $mensaje = "Todos los campos son obligatorios.";
    } else {
        try {
            $sql = $conexion->prepare("
                UPDATE tareas 
                SET titulo = :titulo,
                    descripcion = :descripcion,
                    fecha_limite = :fecha_limite,
                    prioridad = :prioridad,
                    estado = :estado,
                    id_asignado = :id_asignado
                WHERE id = :id
            ");
            $sql->bindParam(":titulo", $titulo);
            $sql->bindParam(":descripcion", $descripcion);
            $sql->bindParam(":fecha_limite", $fecha_limite);
            $sql->bindParam(":prioridad", $prioridad);
            $sql->bindParam(":estado", $estado);
            $sql->bindParam(":id_asignado", $id_asignado);
            $sql->bindParam(":id", $id_tarea);
            $sql->execute();
            
            // Enviar mensaje al padre
            echo "<script>window.parent.postMessage('tarea_editada', '*');</script>";
            exit;
            
        } catch (PDOException $e) {
            $mensaje = "Error al actualizar: " . $e->getMessage();
        }
    }
}

// Obtener datos de la tarea
$query = $conexion->prepare("SELECT * FROM tareas WHERE id = :id");
$query->bindParam(":id", $id_tarea);
$query->execute();
$tarea = $query->fetch(PDO::FETCH_ASSOC);

// Obtener miembros del proyecto
$queryMiembros = $conexion->prepare("
    SELECT u.id, u.nombre, u.correo
    FROM miembros m
    INNER JOIN usuarios u ON m.correo_miembro = u.correo
    WHERE m.id_proyecto = :id_proyecto
");
$queryMiembros->bindParam(":id_proyecto", $tarea['id_proyecto']);
$queryMiembros->execute();
$miembros = $queryMiembros->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Tarea</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    
    <style>
        body {
            background: white;
            font-family: 'Segoe UI', sans-serif;
        }

        .modal-header-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            text-align: center;
            color: white;
        }

        .modal-header-custom h2 {
            margin: 0;
            font-weight: 700;
            font-size: 28px;
        }

        .modal-body-custom {
            padding: 30px;
        }

        .form-label {
            color: #333;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-label i {
            color: #667eea;
        }

        .form-control-modern {
            border-radius: 12px;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
        }

        .form-control-modern:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .btn-guardar {
            width: 100%;
            padding: 14px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-guardar:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-cancelar {
            width: 100%;
            margin-top: 12px;
            padding: 12px;
            border-radius: 12px;
            border: 2px solid #e9ecef;
            background: white;
            font-weight: 600;
            color: #6c757d;
            cursor: pointer;
        }

        .btn-cancelar:hover {
            background: #f8f9fa;
            border-color: #667eea;
            color: #667eea;
        }
    </style>
</head>
<body>

<div class="modal-container">
    <div class="modal-header-custom">
        <h2><i class="bi bi-pencil-square"></i> Editar Tarea</h2>
        <p style="margin: 5px 0 0 0; opacity: 0.95;">Modifica los detalles de la tarea</p>
    </div>

    <div class="modal-body-custom">
        <?php if ($mensaje): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <?= htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label class="form-label">
                    <i class="bi bi-pencil-square"></i>
                    Título
                </label>
                <input 
                    type="text" 
                    name="titulo" 
                    value="<?= htmlspecialchars($tarea['titulo']); ?>"
                    required 
                    class="form-control form-control-modern"
                >
            </div>

            <div class="mb-3">
                <label class="form-label">
                    <i class="bi bi-card-text"></i>
                    Descripción
                </label>
                <textarea 
                    name="descripcion" 
                    rows="4" 
                    required 
                    class="form-control form-control-modern"
                ><?= htmlspecialchars($tarea['descripcion']); ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">
                    <i class="bi bi-calendar-check"></i>
                    Fecha límite
                </label>
                <input 
                    type="date" 
                    name="fecha_limite" 
                    value="<?= $tarea['fecha_limite']; ?>"
                    required 
                    class="form-control form-control-modern"
                >
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        <i class="bi bi-flag-fill"></i>
                        Prioridad
                    </label>
                    <select name="prioridad" class="form-control form-control-modern">
                        <option value="baja" <?= $tarea['prioridad'] == 'baja' ? 'selected' : '' ?>>Baja</option>
                        <option value="media" <?= $tarea['prioridad'] == 'media' ? 'selected' : '' ?>>Media</option>
                        <option value="alta" <?= $tarea['prioridad'] == 'alta' ? 'selected' : '' ?>>Alta</option>
                        <option value="urgente" <?= $tarea['prioridad'] == 'urgente' ? 'selected' : '' ?>>Urgente</option>
                    </select>
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        <i class="bi bi-arrow-repeat"></i>
                        Estado
                    </label>
                    <select name="estado" class="form-control form-control-modern">
                        <option value="pendiente" <?= $tarea['estado'] == 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
                        <option value="en_proceso" <?= $tarea['estado'] == 'en_proceso' ? 'selected' : '' ?>>En Proceso</option>
                        <option value="completada" <?= $tarea['estado'] == 'completada' ? 'selected' : '' ?>>Completada</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label">
                    <i class="bi bi-person-fill"></i>
                    Asignar a
                </label>
                <select name="id_asignado" class="form-control form-control-modern" required>
                    <?php foreach ($miembros as $m): ?>
                        <option value="<?= $m['id'] ?>" <?= $tarea['id_asignado'] == $m['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['nombre']) ?> (<?= htmlspecialchars($m['correo']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn-guardar">
                <i class="bi bi-check-circle-fill"></i>
                Guardar Cambios
            </button>

            <button type="button" class="btn-cancelar" onclick="window.parent.postMessage('cerrar_modal_editar', '*')">
                <i class="bi bi-x-circle"></i>
                Cancelar
            </button>
        </form>
    </div>
</div>

</body>
</html>