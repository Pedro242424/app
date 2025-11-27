<?php
session_start();
include("../config/bd.php");

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

$id_usuario = $_SESSION['id_usuario'];
$mensaje = "";
$tipo_mensaje = "";

// Obtener datos del usuario
$query = $conexion->prepare("SELECT * FROM usuarios WHERE id = :id LIMIT 1");
$query->bindParam(":id", $id_usuario);
$query->execute();
$usuario = $query->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    header("Location: logout.php");
    exit;
}

// Actualizar perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['actualizar_perfil'])) {
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $password_actual = $_POST['password_actual'] ?? '';
    $password_nueva = $_POST['password_nueva'] ?? '';
    $confirmar_password = $_POST['confirmar_password'] ?? '';

    // Validar campos básicos
    if (empty($nombre) || empty($correo)) {
        $mensaje = "El nombre y correo son obligatorios";
        $tipo_mensaje = "danger";
    } 
    // Si quiere cambiar contraseña
    elseif (!empty($password_nueva)) {
        if ($password_actual !== $usuario['password']) {
            $mensaje = "La contraseña actual es incorrecta";
            $tipo_mensaje = "danger";
        } elseif (strlen($password_nueva) < 6) {
            $mensaje = "La nueva contraseña debe tener al menos 6 caracteres";
            $tipo_mensaje = "danger";
        } elseif ($password_nueva !== $confirmar_password) {
            $mensaje = "Las contraseñas nuevas no coinciden";
            $tipo_mensaje = "danger";
        } else {
            // Actualizar con nueva contraseña
            $update = $conexion->prepare("UPDATE usuarios SET nombre = :nombre, correo = :correo, password = :password WHERE id = :id");
            $update->bindParam(":nombre", $nombre);
            $update->bindParam(":correo", $correo);
            $update->bindParam(":password", $password_nueva);
            $update->bindParam(":id", $id_usuario);
            
            if ($update->execute()) {
                $_SESSION['usuario'] = $nombre;
                $mensaje = "Perfil y contraseña actualizados correctamente";
                $tipo_mensaje = "success";
                $usuario['nombre'] = $nombre;
                $usuario['correo'] = $correo;
                $usuario['password'] = $password_nueva;
            } else {
                $mensaje = "Error al actualizar el perfil";
                $tipo_mensaje = "danger";
            }
        }
    } 
    // Solo actualizar nombre y correo
    else {
        $update = $conexion->prepare("UPDATE usuarios SET nombre = :nombre, correo = :correo WHERE id = :id");
        $update->bindParam(":nombre", $nombre);
        $update->bindParam(":correo", $correo);
        $update->bindParam(":id", $id_usuario);
        
        if ($update->execute()) {
            $_SESSION['usuario'] = $nombre;
            $mensaje = "Perfil actualizado correctamente";
            $tipo_mensaje = "success";
            $usuario['nombre'] = $nombre;
            $usuario['correo'] = $correo;
        } else {
            $mensaje = "Error al actualizar el perfil";
            $tipo_mensaje = "danger";
        }
    }
}

// Estadísticas del usuario
$stats_proyectos = $conexion->prepare("SELECT COUNT(*) as total FROM proyectos WHERE id_usuario = :id");
$stats_proyectos->bindParam(":id", $id_usuario);
$stats_proyectos->execute();
$total_proyectos = $stats_proyectos->fetch(PDO::FETCH_ASSOC)['total'];

$stats_tareas = $conexion->prepare("SELECT COUNT(*) as total FROM tareas WHERE id_asignado = :id");
$stats_tareas->bindParam(":id", $id_usuario);
$stats_tareas->execute();
$total_tareas = $stats_tareas->fetch(PDO::FETCH_ASSOC)['total'];

$stats_completadas = $conexion->prepare("SELECT COUNT(*) as total FROM tareas WHERE id_asignado = :id AND estado = 'completada'");
$stats_completadas->bindParam(":id", $id_usuario);
$stats_completadas->execute();
$total_completadas = $stats_completadas->fetch(PDO::FETCH_ASSOC)['total'];

include("../includes/header.php");
?>

<style>
    body {
        background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
        min-height: 100vh;
    }

    .profile-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 20px;
        padding: 40px;
        margin-bottom: 30px;
        color: white;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    }

    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        background: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 48px;
        font-weight: 700;
        color: #667eea;
        margin: 0 auto 20px;
        box-shadow: 0 8px 25px rgba(0,0,0,0.2);
    }

    .profile-name {
        font-size: 32px;
        font-weight: 700;
        text-align: center;
        margin-bottom: 5px;
    }

    .profile-email {
        text-align: center;
        opacity: 0.95;
        font-size: 16px;
    }

    /* Tarjetas de opciones */
    .config-title {
        font-size: 20px;
        font-weight: 700;
        color: #333;
        margin-bottom: 20px;
        margin-top: 10px;
    }

    .options-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .option-card {
        background: white;
        border-radius: 16px;
        padding: 25px;
        display: flex;
        align-items: flex-start;
        gap: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        transition: all 0.3s;
        cursor: pointer;
        border: 2px solid transparent;
        text-decoration: none;
        color: inherit;
    }

    .option-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
        border-color: #667eea;
    }

    .option-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        flex-shrink: 0;
    }

    .option-icon.blue {
        background: #e3f2fd;
        color: #667eea;
    }

    .option-icon.purple {
        background: #f3e5f5;
        color: #9c27b0;
    }

    .option-icon.green {
        background: #e8f5e9;
        color: #4caf50;
    }

    .option-content h5 {
        margin: 0 0 5px 0;
        font-size: 16px;
        font-weight: 700;
        color: #333;
    }

    .option-content p {
        margin: 0;
        font-size: 13px;
        color: #6c757d;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 25px;
        text-align: center;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        transition: all 0.3s;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
    }

    .stat-icon {
        font-size: 40px;
        margin-bottom: 15px;
    }

    .stat-number {
        font-size: 36px;
        font-weight: 700;
        color: #667eea;
        margin-bottom: 5px;
    }

    .stat-label {
        color: #6c757d;
        font-size: 14px;
        font-weight: 600;
    }

    .settings-card {
        background: white;
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    }

    .settings-card h4 {
        color: #667eea;
        font-weight: 700;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
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

    .btn-actualizar {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 14px 30px;
        font-weight: 600;
        transition: all 0.3s;
    }

    .btn-actualizar:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        color: white;
    }

    .section-divider {
        border-top: 2px solid #f1f3f5;
        margin: 30px 0;
    }

    /* Ocultar formulario inicialmente */
    .settings-card {
        display: none;
    }

    .settings-card.active {
        display: block;
    }
</style>

<div class="container mt-4">

    <!-- Header del perfil -->
    <div class="profile-header">
        <div class="profile-avatar">
            <?php 
                $iniciales = strtoupper(substr($usuario['nombre'], 0, 2));
                echo $iniciales;
            ?>
        </div>
        <div class="profile-name"><?= htmlspecialchars($usuario['nombre']); ?></div>
        <div class="profile-email">
            <i class="bi bi-envelope-fill"></i> <?= htmlspecialchars($usuario['correo']); ?>
        </div>
    </div>

    <!-- Título de configuración -->
    <h3 class="config-title">Configuración de cuenta</h3>

    <!-- Tarjetas de opciones -->
    <div class="options-grid">
        <div class="option-card" onclick="toggleSection('editarPerfil')">
            <div class="option-icon blue">
                <i class="bi bi-person-circle"></i>
            </div>
            <div class="option-content">
                <h5>Editar perfil</h5>
                <p>Actualiza tu información personal</p>
            </div>
        </div>

        <div class="option-card" onclick="alert('Función de notificaciones próximamente')">
            <div class="option-icon purple">
                <i class="bi bi-bell-fill"></i>
            </div>
            <div class="option-content">
                <h5>Notificaciones</h5>
                <p>Configura tus preferencias de notificaciones</p>
            </div>
        </div>

        <div class="option-card" onclick="alert('Función de privacidad próximamente')">
            <div class="option-icon green">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <div class="option-content">
                <h5>Privacidad</h5>
                <p>Controla tu privacidad y seguridad</p>
            </div>
        </div>
    </div>

    <!-- Estadísticas -->
    <h3 class="config-title">Tus estadísticas</h3>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon"></div>
            <div class="stat-number"><?= $total_proyectos ?></div>
            <div class="stat-label">Proyectos creados</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"></div>
            <div class="stat-number"><?= $total_tareas ?></div>
            <div class="stat-label">Tareas asignadas</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon"></div>
            <div class="stat-number"><?= $total_completadas ?></div>
            <div class="stat-label">Tareas completadas</div>
        </div>
    </div>

    <!-- Formulario de edición (oculto por defecto) -->
    <div class="settings-card" id="editarPerfil">
        <h4>
            <i class="bi bi-gear-fill"></i>
            Editar información personal
        </h4>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?= $tipo_mensaje ?> alert-dismissible fade show" role="alert" style="border-radius: 12px;">
                <i class="bi bi-<?= $tipo_mensaje === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?>"></i>
                <?= htmlspecialchars($mensaje) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="post">
            <!-- Información básica -->
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        <i class="bi bi-person-fill"></i>
                        Nombre completo
                    </label>
                    <input 
                        type="text" 
                        name="nombre" 
                        class="form-control form-control-modern" 
                        value="<?= htmlspecialchars($usuario['nombre']) ?>"
                        required
                    >
                </div>

                <div class="col-md-6 mb-3">
                    <label class="form-label">
                        <i class="bi bi-envelope-fill"></i>
                        Correo electrónico
                    </label>
                    <input 
                        type="email" 
                        name="correo" 
                        class="form-control form-control-modern" 
                        value="<?= htmlspecialchars($usuario['correo']) ?>"
                        required
                    >
                </div>
            </div>

            <div class="section-divider"></div>

            <!-- Cambiar contraseña -->
            <h5 class="mb-3" style="color: #667eea; font-weight: 600;">
                <i class="bi bi-key-fill"></i> Cambiar contraseña (opcional)
            </h5>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">
                        <i class="bi bi-lock-fill"></i>
                        Contraseña actual
                    </label>
                    <input 
                        type="password" 
                        name="password_actual" 
                        class="form-control form-control-modern" 
                        placeholder="Tu contraseña actual"
                    >
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">
                        <i class="bi bi-shield-lock-fill"></i>
                        Nueva contraseña
                    </label>
                    <input 
                        type="password" 
                        name="password_nueva" 
                        class="form-control form-control-modern" 
                        placeholder="Mínimo 6 caracteres"
                        minlength="6"
                    >
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label">
                        <i class="bi bi-check-circle-fill"></i>
                        Confirmar nueva contraseña
                    </label>
                    <input 
                        type="password" 
                        name="confirmar_password" 
                        class="form-control form-control-modern" 
                        placeholder="Repite la contraseña"
                    >
                </div>
            </div>

            <small class="text-muted d-block mb-4">
                <i class="bi bi-info-circle"></i> 
                Deja los campos de contraseña vacíos si no deseas cambiarla
            </small>

            <input type="hidden" name="actualizar_perfil" value="1">

            <button type="submit" class="btn-actualizar">
                <i class="bi bi-check-circle-fill"></i>
                Guardar cambios
            </button>
        </form>
    </div>

</div>

<script>
function toggleSection(sectionId) {
    const section = document.getElementById(sectionId);
    
    // Toggle display
    if (section.classList.contains('active')) {
        section.classList.remove('active');
    } else {
        // Ocultar todas las secciones
        document.querySelectorAll('.settings-card').forEach(s => s.classList.remove('active'));
        // Mostrar la seleccionada
        section.classList.add('active');
        // Scroll suave a la sección
        section.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

// Si hay mensaje, mostrar el formulario automáticamente
<?php if ($mensaje): ?>
    document.getElementById('editarPerfil').classList.add('active');
<?php endif; ?>
</script>

<?php include("../includes/footer.php"); ?>