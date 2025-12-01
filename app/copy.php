<?php
/**
 * DASHBOARD - Página principal del usuario
 * Muestra estadísticas, tareas pendientes y proyectos activos.
 */

session_start();
include("../config/bd.php");

// Verificar que el usuario haya iniciado sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

$id_usuario = $_SESSION['id_usuario'];

// ===============================================
// OBTENER DATOS DEL USUARIO
// ===============================================
$query_usuario = $conexion->prepare("SELECT nombre, correo FROM usuarios WHERE id = :id");
$query_usuario->bindParam(":id", $id_usuario);
$query_usuario->execute();
$datos_usuario = $query_usuario->fetch(PDO::FETCH_ASSOC);
$nombre_usuario = $datos_usuario['nombre'] ?? 'Usuario';
$correo_usuario = strtolower(string: trim($datos_usuario['correo'] ?? ''));

// ===============================================
// ESTADÍSTICAS - Tareas pendientes del usuario
// ===============================================
$stats_tareas_pendientes = $conexion->prepare("
    SELECT COUNT(*) as total FROM tareas
    WHERE id_asignado = :id AND estado = 'pendiente'
");
$stats_tareas_pendientes->bindParam(":id", $id_usuario);
$stats_tareas_pendientes->execute();
$total_pendientes = $stats_tareas_pendientes->fetch(PDO::FETCH_ASSOC)['total'];

// ===============================================
// OBTENER TAREAS PENDIENTES
// ===============================================
$query_tareas = $conexion->prepare("
    SELECT t.*, p.nombre as nombre_proyecto
    FROM tareas t
    LEFT JOIN proyectos p ON t.id_proyecto = p.id
    WHERE t.id_asignado = :id_usuario
    AND t.estado = 'pendiente'
    ORDER BY t.fecha_limite ASC
    LIMIT 10
");
$query_tareas->bindParam(":id_usuario", $id_usuario);
$query_tareas->execute();
$tareas_pendientes = $query_tareas->fetchAll(PDO::FETCH_ASSOC);

// ===============================================
// OBTENER PROYECTOS (Creador o Miembro)
// ===============================================
$query_proyectos = $conexion->prepare("
    SELECT DISTINCT p.*
    FROM proyectos p
    WHERE p.id_usuario = :id_usuario
    OR p.id IN (
        SELECT m.id_proyecto 
        FROM miembros m 
        WHERE LOWER(TRIM(m.correo_miembro)) = :correo
    )
    ORDER BY p.id DESC
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
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', sans-serif;
        }

        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .welcome-header {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .welcome-header h2 {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin: 0 0 5px 0;
        }

        .welcome-header .subtitle {
            color: #6c757d;
            font-size: 15px;
        }

        .welcome-header .subtitle .count {
            color: #dc3545;
            font-weight: 700;
        }

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

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .section-header h4 {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-header .info-icon {
            color: #dc3545;
            cursor: help;
        }

        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .project-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s;
            cursor: pointer;
        }

        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
        }

        .project-card .project-title {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin-bottom: 8px;
        }

        .project-card .project-members {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
            flex-wrap: wrap;
        }

        .project-card .project-members .member-tag {
            font-size: 12px;
            color: #667eea;
            background: #f0f2ff;
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: 600;
        }

        .project-card .project-deadline {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 14px;
            color: #dc3545;
        }

        .project-card .project-deadline i {
            font-size: 16px;
        }

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

        .empty-state h5 {
            color: #6c757d;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .empty-state p {
            color: #adb5bd;
            font-size: 14px;
        }

        .fab {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 65px;
            height: 65px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            cursor: pointer;
            transition: all 0.3s;
            border: none;
            z-index: 1000;
        }

        .fab:hover {
            transform: scale(1.1) rotate(90deg);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.6);
        }

        .modal-content {
            border-radius: 25px;
            border: none;
        }

        @media (max-width: 768px) {
            .tasks-highlight {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }

            .projects-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include("../includes/header.php"); ?>

    <div class="dashboard-container">
        
        <!-- Header de bienvenida -->
        <div class="welcome-header">
            <h2>Hola <?= htmlspecialchars($nombre_usuario); ?>,</h2>
            <p class="subtitle">
                Tienes <span class="count"><?= $total_pendientes ?> tareas pendientes</span> esta semana
            </p>
        </div>

        <!-- Card destacado de tareas pendientes -->
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

        <!-- Sección de proyectos activos -->
        <div class="section-header">
            <h4>
                <?= count($proyectos) ?> Proyectos Activos
                <i class="bi bi-info-circle info-icon" title="Proyectos donde eres creador o miembro"></i>
            </h4>
        </div>

        <?php if (!empty($proyectos)): ?>
            <div class="projects-grid">
                <?php foreach ($proyectos as $p): ?>
                    <div class="project-card" onclick="window.location.href='tareas.php?id=<?= $p['id'] ?>'">
                        <div class="project-title"><?= htmlspecialchars($p['nombre']); ?></div>
                        
                        <div class="project-members">
                            <?php 
                            $miembros = $miembros_por_proyecto[$p['id']] ?? [];
                            
                            if (!empty($miembros)) {
                                foreach ($miembros as $miembro) {
                                    $primer_nombre = explode(' ', $miembro['nombre'])[0];
                                    echo '<span class="member-tag">' . htmlspecialchars($primer_nombre) . '</span>';
                                }
                            } else {
                                echo '<span class="member-tag text-muted">Sin miembros</span>';
                            }
                            ?>
                        </div>
                        
                        <div class="project-deadline">
                            <i class="bi bi-clock"></i>
                            <?php
                            $fecha = new DateTime($p['fecha_limite']);
                            $hoy = new DateTime();
                            $diff = $hoy->diff($fecha);
                            
                            if ($diff->invert) {
                                echo "Vencido";
                            } else {
                                echo $diff->days . "d";
                            }
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="icon"><i class="bi bi-folder-x"></i></div>
                <h5>Aún no tienes proyectos</h5>
                <p>Crea tu primer proyecto y empieza a organizarte</p>
            </div>
        <?php endif; ?>

    </div>

    <!-- Floating Action Button - ACTUALIZADO: Abre modal -->
    <button class="fab" data-bs-toggle="modal" data-bs-target="#modalNuevoProyecto" title="Nuevo proyecto">
        <i class="bi bi-plus-lg"></i>
    </button>

    <!-- MODAL NUEVO PROYECTO -->
    <div class="modal fade" id="modalNuevoProyecto" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" id="modalContenido">
                <!-- Contenedor donde se cargará el formulario -->
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    /**
     * SCRIPT PARA CARGA DINÁMICA DEL MODAL
     * Igual que en proyectos.php
     */
    const modalElement = document.getElementById('modalNuevoProyecto');

    if (modalElement) {
        modalElement.addEventListener('show.bs.modal', function () {
            const contenedor = document.getElementById('modalContenido');
            
            // Mostrar spinner mientras carga
            contenedor.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>
            `;
            
            // Cargar formulario por AJAX
            fetch('crear_proyecto.php?modal=1')
                .then(response => response.text())
                .then(html => {
                    contenedor.innerHTML = html;
                    
                    // Re-ejecutar scripts del modal
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

    // Escuchar cuando se crea un proyecto
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
    });
    </script>
</body>
</html>