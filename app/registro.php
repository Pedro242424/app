<?php
include("../config/bd.php");
$mensaje = "";
$tipo_mensaje = "danger";

if ($_POST) {
    $nombre = trim($_POST['nombre']);
    $correo = trim($_POST['correo']);
    $password = $_POST['password'];
    $confirmar_password = $_POST['confirmar_password'];

    // Validaciones básicas
    if (empty($nombre) || empty($correo) || empty($password)) {
        $mensaje = "Todos los campos son obligatorios";
    } elseif ($password !== $confirmar_password) {
        $mensaje = "Las contraseñas no coinciden";
    } elseif (strlen($password) < 6) {
        $mensaje = "La contraseña debe tener al menos 6 caracteres";
    } else {
        // Verificar si el correo ya existe
        $check = $conexion->prepare("SELECT id FROM usuarios WHERE correo = :correo");
        $check->bindParam(":correo", $correo);
        $check->execute();
        
        if ($check->rowCount() > 0) {
            $mensaje = "Este correo ya está registrado";
        } else {
            // Registrar usuario
            $query = $conexion->prepare("INSERT INTO usuarios (nombre, correo, password) VALUES (:nombre, :correo, :password)");
            $query->bindParam(":nombre", $nombre);
            $query->bindParam(":correo", $correo);
            $query->bindParam(":password", $password);

            if ($query->execute()) {
                header("Location: login.php?registro=ok");
                exit;
            } else {
                $mensaje = "Error al registrarte. Intenta de nuevo.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - SharkTask</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
            padding: 20px;
        }

        .registro-card {
            background: white;
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 420px;
            width: 100%;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-section img {
            width: 80px;
            height: auto;
            margin-bottom: 15px;
        }

        .logo-section h2 {
            color: #667eea;
            font-weight: 700;
            font-size: 28px;
            margin: 0 0 5px 0;
        }

        .logo-section p {
            color: #6c757d;
            font-size: 14px;
            margin: 0;
        }

        .form-label {
            color: #495057;
            font-weight: 500;
            font-size: 14px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .form-label i {
            font-size: 16px;
        }

        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
        }

        .form-control::placeholder {
            color: #adb5bd;
        }

        .btn-registro {
            width: 100%;
            padding: 14px;
            border-radius: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-registro:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .link-login {
            text-align: center;
            margin-top: 20px;
            color: #6c757d;
            font-size: 14px;
        }

        .link-login a {
            color: #667eea;
            font-weight: 600;
            text-decoration: none;
        }

        .link-login a:hover {
            text-decoration: underline;
        }

        .alert {
            border-radius: 12px;
            border: none;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .alert-danger {
            background: #fee;
            color: #c33;
        }

        small.text-muted {
            font-size: 12px;
            color: #6c757d !important;
        }
    </style>
</head>
<body>
    <div class="registro-card">
        <!-- Logo y título -->
        <div class="logo-section">
            <img src="../assets/img/logo_sharktask.png" alt="SharkTask">
            <h2>SharkTask</h2>
            <p>Regístrate para continuar</p>
        </div>

        <!-- Mensaje de error -->
        <?php if($mensaje): ?>
            <div class="alert alert-<?= $tipo_mensaje ?>">
                <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($mensaje) ?>
            </div>
        <?php endif; ?>

        <!-- Formulario -->
        <form method="post">
            <!-- Nombre -->
            <div class="mb-3">
                <label class="form-label">
                    <i class="bi bi-person"></i> Nombre
                </label>
                <input 
                    type="text" 
                    name="nombre" 
                    class="form-control" 
                    placeholder="Tu nombre completo"
                    required
                    value="<?= isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : '' ?>"
                >
            </div>

            <!-- Correo -->
            <div class="mb-3">
                <label class="form-label">
                    <i class="bi bi-envelope"></i> Correo
                </label>
                <input 
                    type="email" 
                    name="correo" 
                    class="form-control" 
                    placeholder="tu@correo.com"
                    required
                    value="<?= isset($_POST['correo']) ? htmlspecialchars($_POST['correo']) : '' ?>"
                >
            </div>

            <!-- Contraseña -->
            <div class="mb-3">
                <label class="form-label">
                    <i class="bi bi-lock"></i> Contraseña
                </label>
                <input 
                    type="password" 
                    name="password" 
                    class="form-control" 
                    placeholder="Mínimo 6 caracteres"
                    required
                    minlength="6"
                >
                <small class="text-muted">Debe tener al menos 6 caracteres</small>
            </div>

            <!-- Confirmar contraseña -->
            <div class="mb-3">
                <label class="form-label">
                    <i class="bi bi-shield-lock"></i> Confirmar contraseña
                </label>
                <input 
                    type="password" 
                    name="confirmar_password" 
                    class="form-control" 
                    placeholder="Repite tu contraseña"
                    required
                >
            </div>

            <!-- Botón -->
            <button type="submit" class="btn-registro">
                <i class="bi bi-box-arrow-in-right"></i> Registrarse
            </button>
        </form>

        <!-- Link a login -->
        <div class="link-login">
            ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>