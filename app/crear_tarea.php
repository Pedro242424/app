<?php
session_start();
include("../config/bd.php");

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

$id_proyecto = $_GET['id_proyecto'] ?? null;
$mensaje = "";

if (!$id_proyecto) {
    header("Location: proyectos.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $fecha_limite = $_POST['fecha_limite'] ?? '';
    $prioridad = $_POST['prioridad'] ?? 'media';
    $id_asignado = $_POST['id_asignado'] ?? null;
    $id_proyecto = $_POST['id_proyecto'] ?? null;

    if (empty($titulo) || empty($descripcion) || empty($fecha_limite)) {
        $mensaje = " Todos los campos son obligatorios.";
    } else {
        try {
            $sql = $conexion->prepare("
                INSERT INTO tareas (titulo, descripcion, fecha_limite, prioridad, id_proyecto, estado, id_asignado)
                VALUES (:titulo, :descripcion, :fecha_limite, :prioridad, :id_proyecto, 'pendiente', :id_asignado)
            ");
            $sql->bindParam(":titulo", $titulo);
            $sql->bindParam(":descripcion", $descripcion);
            $sql->bindParam(":fecha_limite", $fecha_limite);
            $sql->bindParam(":prioridad", $prioridad);
            $sql->bindParam(":id_proyecto", $id_proyecto);
            $sql->bindParam(":id_asignado", $id_asignado);
            $sql->execute();

            // NO redirigir, dejar que JavaScript maneje el cierre del modal
            // header("Location: proyecto_detalle.php?id=" . urlencode($id_proyecto));
            // exit;
        } catch (PDOException $e) {
            $mensaje = " Error al guardar la tarea: " . $e->getMessage();
        }
    }
}

// Obtener miembros del proyecto
$queryMiembros = $conexion->prepare("
    SELECT u.id, u.nombre, u.correo
    FROM miembros m
    INNER JOIN usuarios u ON m.correo_miembro = u.correo
    WHERE m.id_proyecto = :id_proyecto
");
$queryMiembros->bindParam(":id_proyecto", $id_proyecto);
$queryMiembros->execute();
$miembros = $queryMiembros->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Tarea - SharkTask</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    
    <style>
        body {
            background: white;
            font-family: 'Segoe UI', sans-serif;
            padding: 0;
            margin: 0;
        }

        .modal-container {
            width: 100%;
            background: white;
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

        .modal-header-custom p {
            margin: 5px 0 0 0;
            opacity: 0.95;
            font-size: 15px;
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
            font-size: 16px;
        }

        .form-control-modern {
            border-radius: 12px;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-control-modern:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .form-control-modern::placeholder {
            color: #adb5bd;
        }

        .btn-crear {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-size: 16px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-crear:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
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
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            cursor: pointer;
        }

        .btn-cancelar:hover {
            background: #f8f9fa;
            border-color: #667eea;
            color: #667eea;
        }

        .alert {
            border-radius: 12px;
            border: none;
            margin-bottom: 20px;
        }

        select.form-control-modern {
            cursor: pointer;
        }
    </style>
</head>
<body>

<div class="modal-container">
    <!-- Header -->
    <div class="modal-header-custom">
        <h2> Nueva Tarea</h2>
        <p>Organiza el trabajo del proyecto</p>
    </div>

    <!-- Body -->
    <div class="modal-body-custom">
        <?php if ($mensaje): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <?= htmlspecialchars($mensaje); ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <!-- Título -->
            <div class="mb-3">
                <label class="form-label">
                    <i class="bi bi-pencil-square"></i>
                    Título de la tarea
                </label>
                <input 
                    type="text" 
                    name="titulo" 
                    required 
                    maxlength="100" 
                    class="form-control form-control-modern" 
                    placeholder="Ej: Crear prototipo de alta fidelidad"
                >
            </div>

            <!-- Descripción -->
            <div class="mb-3">
                <label class="form-label">
                    <i class="bi bi-text-paragraph"></i>
                    Descripción
                </label>
                <textarea 
                    name="descripcion" 
                    rows="4" 
                    required 
                    class="form-control form-control-modern" 
                    placeholder="Describe los detalles de la tarea..."
                ></textarea>
            </div>

            <!-- Fecha límite -->
            <div class="mb-3">
                <label class="form-label">
                    <i class="bi bi-calendar-event"></i>
                    Fecha límite
                </label>
                <input 
                    type="date" 
                    name="fecha_limite" 
                    required 
                    class="form-control form-control-modern"
                >
            </div>

            <!-- Prioridad -->
            <div class="mb-3">
                <label class="form-label">
                    <i class="bi bi-flag-fill"></i>
                    Prioridad
                </label>
                <select name="prioridad" class="form-control form-control-modern">
                    <option value="baja">🟢 Baja</option>
                    <option value="media" selected>🟡 Media</option>
                    <option value="alta">🔴 Alta</option>
                </select>
            </div>

            <!-- Asignar a -->
            <div class="mb-4">
                <label class="form-label">
                    <i class="bi bi-person-fill"></i>
                    Asignar a
                </label>
                <select name="id_asignado" class="form-control form-control-modern" required>
                    <option value="">Selecciona un miembro del proyecto</option>
                    <?php if (count($miembros) > 0): ?>
                        <?php foreach ($miembros as $mi): ?>
                            <option value="<?= htmlspecialchars($mi['id']) ?>">
                                <?= htmlspecialchars($mi['nombre']) ?> (<?= htmlspecialchars($mi['correo']) ?>)
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option disabled>No hay miembros registrados</option>
                    <?php endif; ?>
                </select>
            </div>

            <input type="hidden" name="id_proyecto" value="<?= htmlspecialchars($id_proyecto); ?>">

            <!-- Botones -->
            <button type="submit" class="btn-crear">
                <i class="bi bi-check-circle-fill"></i>
                Crear tarea
            </button>

            <button type="button" class="btn-cancelar" onclick="window.parent.postMessage('cerrar_modal', '*')">
                <i class="bi bi-x-circle"></i>
                Cancelar
            </button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Enviar mensaje al padre cuando se crea la tarea correctamente
<?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($mensaje)): ?>
    window.parent.postMessage('tarea_creada', '*');
<?php endif; ?>
</script>

</body>
</html>
