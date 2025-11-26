<?php
session_start();
include("../config/bd.php");

$mensaje = "";

if ($_POST) {
    $correo = $_POST['correo'];
    $password = $_POST['password'];

    $query = $conexion->prepare("SELECT * FROM usuarios WHERE correo = :correo LIMIT 1");
    $query->bindParam(":correo", $correo);
    $query->execute();
    $usuario = $query->fetch(PDO::FETCH_ASSOC);

    if ($usuario && $password == $usuario['password']) {
        $_SESSION['usuario'] = $usuario['nombre'];
        $_SESSION['id_usuario'] = $usuario['id'];
        header("Location: dashboard.php");
        exit;
    } else {
        $mensaje = "Correo o contraseña incorrectos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SharkTask</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #513174 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }

        .login-container {
            background: white;
            border-radius: 30px;
            padding: 50px 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 450px;
            width: 100%;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }

        /* ⬇️ AQUI SE APLICA EL TAMAÑO DEL LOGO ⬇️ */
        .logo {
            width: 120px;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        .logo-container h2 {
            font-weight: 800;
            color: #667eea;
            margin-top: 10px;
        }

        .logo-container p {
            color: #6c757d;
            font-size: 14px;
        }

        .form-control {
            border-radius: 15px;
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn-login {
            width: 100%;
            padding: 14px;
            border-radius: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .link-registro {
            text-align: center;
            margin-top: 20px;
            color: #6c757d;
        }

        .link-registro a {
            color: #667eea;
            font-weight: 600;
            text-decoration: none;
        }

        .link-registro a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-container">
            <img src="../assets/img/logo_sharktask.png" alt="SharkTask Logo" class="logo">
            <h2>SharkTask</h2>
            <p>Inicia sesión para continuar</p>
        </div>

        <?php if($mensaje): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-triangle-fill"></i> <?= $mensaje ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label class="form-label"><i class="bi bi-envelope"></i> Correo</label>
                <input type="email" name="correo" class="form-control" placeholder="tu@correo.com" required>
            </div>
            <div class="mb-3">
                <label class="form-label"><i class="bi bi-lock"></i> Contraseña</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-login">
                <i class="bi bi-box-arrow-in-right"></i> Entrar
            </button>
        </form>

        <div class="link-registro">
            ¿No tienes cuenta? <a href="registro.php">Regístrate gratis</a>
        </div>
    </div>
</body>
</html>
