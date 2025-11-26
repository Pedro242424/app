<?php
session_start();
include("../config/bd.php");

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

$id_usuario = $_SESSION['id_usuario'];
$correo_usuario = trim(strtolower($_SESSION['usuario']));

//  Estadísticas
$stats_tareas_pendientes = $conexion->prepare("
    SELECT COUNT(*) as total FROM tareas
    WHERE id_asignado = :id AND estado = 'pendiente'
");
$stats_tareas_pendientes->bindParam(":id", $id_usuario);
$stats_tareas_pendientes->execute();
$total_pendientes = $stats_tareas_pendientes->fetch(PDO::FETCH_ASSOC)['total'];

$stats_tareas_completadas = $conexion->prepare("
    SELECT COUNT(*) as total FROM tareas
    WHERE id_asignado = :id AND estado = 'completada'
");
$stats_tareas_completadas->bindParam(":id", $id_usuario);
$stats_tareas_completadas->execute();
$total_completadas = $stats_tareas_completadas->fetch(PDO::FETCH_ASSOC)['total'];

$stats_proyectos = $conexion->prepare("
    SELECT COUNT(DISTINCT p.id) as total
    FROM proyectos p
    INNER JOIN miembros m ON p.id = m.id_proyecto
    WHERE LOWER(TRIM(m.correo_miembro)) = :correo
");
$stats_proyectos->bindParam(":correo", $correo_usuario);
$stats_proyectos->execute();
$total_proyectos = $stats_proyectos->fetch(PDO::FETCH_ASSOC)['total'];

// Tareas pendientes
$query_tareas = $conexion->prepare("
    SELECT * FROM tareas
    WHERE id_asignado = :id_usuario
    AND estado = 'pendiente'
    ORDER BY fecha_limite ASC
    LIMIT 6
");
$query_tareas->bindParam(":id_usuario", $id_usuario);
$query_tareas->execute();
$tareas_pendientes = $query_tareas->fetchAll(PDO::FETCH_ASSOC);

// Proyectos
$query_proyectos = $conexion->prepare("
    SELECT DISTINCT p.*
    FROM proyectos p
    INNER JOIN miembros m ON p.id = m.id_proyecto
    WHERE LOWER(TRIM(m.correo_miembro)) = :correo
    ORDER BY p.id DESC
    LIMIT 6
");
$query_proyectos->bindParam(":correo", $correo_usuario);
$query_proyectos->execute();
$proyectos = $query_proyectos->fetchAll(PDO::FETCH_ASSOC);
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
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
        }

        .dashboard-container {
            padding: 30px 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header */
        .header-dashboard {
            background: white;
            border-radius: 25px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            animation: fadeIn 0.5s;
        }

        .header-dashboard h1 {
            font-weight: 800;
            color: #667eea;
            margin: 0;
        }

        .header-dashboard p {
            color: #6c757d;
            margin: 5px 0 0 0;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s;
            animation: fadeInUp 0.6s;
        }

        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .stat-card .icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .stat-card h3 {
            font-size: 40px;
            font-weight: 800;
            margin: 0;
            color: #667eea;
        }

        .stat-card p {
            color: #6c757d;
            margin: 0;
            font-size: 14px;
        }

        /* Section Title */
        .section-title {
            color: white;
            font-weight: 700;
            font-size: 24px;
            margin: 30px 0 20px 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Task/Project Cards */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card-item {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s;
            animation: fadeInUp 0.7s;
        }

        .card-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.15);
        }

        .card-item h5 {
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }

        .card-item p {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .card-item .badge {
            font-size: 12px;
            padding: 5px 12px;
        }

        .btn-card {
            width: 100%;
            padding: 12px;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }

        /* Floating Action Button */
        .fab {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            z-index: 1000;
        }

        .fab:hover {
            transform: scale(1.1) rotate(90deg);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.6);
            color: white;
        }

        /* Animaciones */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
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

        /* Empty state */
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
        }

        .empty-state h4 {
            color: #6c757d;
            font-weight: 600;
        }

        /* Modal */
        .modal-content {
            border-radius: 25px;
            border: none;
        }
    </style>
</head>
<body>
    <?php include("../includes/header.php"); ?>

    <div class="dashboard-container">
        <!-- Header -->
        <div class="header-dashboard">
            <h1>¡Hola, <?= htmlspecialchars(explode('@', $correo_usuario)[0]); ?>! </h1>
            <p>Aquí está tu resumen del día</p>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card" style="animation-delay: 0.3s;">
                <div class="icon"></div>
                <h3><?= $total_proyectos; ?></h3>
                <p>Proyectos activos</p>
            </div>
        </div>

        <!-- Proyectos Activos -->
        <h2 class="section-title">
            <i class="bi bi-folder2-open"></i> Proyectos activos
        </h2>

        <?php if (!empty($proyectos)): ?>
            <div class="cards-grid">
                <?php foreach ($proyectos as $p): ?>
                    <div class="card-item">
                        <h5><?= htmlspecialchars($p['nombre']); ?></h5>
                        <p><?= htmlspecialchars(substr($p['descripcion'], 0, 80)) . '...'; ?></p>
                        <span class="badge bg-info text-dark mb-3">
                            <i class="bi bi-calendar-event"></i> <?= htmlspecialchars($p['fecha_limite']); ?>
                        </span>
                        <a href="proyecto_detalle.php?id=<?= $p['id']; ?>" class="btn btn-success btn-card">
                            Ver proyecto
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="icon"></div>
                <h4>Aún no tienes proyectos</h4>
                <p>Crea tu primer proyecto y empieza a organizarte</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Floating Action Button -->
    <button class="fab" data-bs-toggle="modal" data-bs-target="#modalNuevoProyecto" title="Nuevo proyecto">
        <i class="bi bi-plus-lg"></i>
    </button>

    <!-- ============ MODAL NUEVO PROYECTO (carga dinámicamente) ============ -->
    <div class="modal fade" id="modalNuevoProyecto" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" id="modalContenido">
                <!-- Spinner mientras carga -->
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Cargar contenido del modal cuando se abre
    document.getElementById('modalNuevoProyecto').addEventListener('show.bs.modal', function () {
        fetch('crear_proyecto.php?modal=1')
            .then(response => response.text())
            .then(html => {
                document.getElementById('modalContenido').innerHTML = html;
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('modalContenido').innerHTML = `
                    <div class="modal-body p-5 text-center">
                        <p class="text-danger"> Error al cargar el formulario</p>
                        <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                `;
            });
    });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php include("../includes/footer.php"); ?>