<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$paginaActual = basename($_SERVER['PHP_SELF']); // Detecta el archivo actual
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SharkTask</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        body { 
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Navbar */
        .navbar-modern {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 12px 0;
        }

        /* Brand */
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea !important;
        }

        .logo-img {
            height: 40px;
            width: auto;
        }

        /* Botones */
        .nav-btn {
            border-radius: 12px;
            padding: 10px 20px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
            border: none;
            display: flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }

        /* Botón morado (activo) */
        .nav-btn-active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            box-shadow: 0 4px 16px rgba(102, 126, 234, 0.3);
        }

        /* Botón normal */
        .nav-btn-default {
            background: transparent;
            color: #495057;
            border: 2px solid #e9ecef;
        }

        .nav-btn-default:hover {
            background: #f8f9fa;
            border-color: #667eea;
            color: #667eea;
        }

        /* Avatar */
        .user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 16px;
            cursor: pointer;
            border: 3px solid white;
        }

    </style>
</head>

<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-modern">
    <div class="container-fluid px-4">

        <!-- Logo y nombre -->
        <a class="navbar-brand" href="dashboard.php">
            <img src="../assets/img/logo_sharktask.png" class="logo-img" alt="SharkTask">
            <span>SharkTask</span>
        </a>

        <div class="d-flex align-items-center gap-2">
            <?php if (isset($_SESSION['usuario'])): ?>

                <!-- Inicio -->
                <a href="dashboard.php" 
                   class="nav-btn <?= $paginaActual == 'dashboard.php' ? 'nav-btn-active' : 'nav-btn-default' ?>">
                    <i class="bi bi-house-door-fill"></i> <span>Inicio</span>
                </a>

                <!-- Proyectos -->
                <a href="proyectos.php" 
                   class="nav-btn <?= $paginaActual == 'proyectos.php' ? 'nav-btn-active' : 'nav-btn-default' ?>">
                    <i class="bi bi-folder2-open"></i> <span>Proyectos</span>
                </a>

                <!-- Mis Tareas -->
                <a href="mis_tareas.php" 
                   class="nav-btn <?= $paginaActual == 'mis_tareas.php' ? 'nav-btn-active' : 'nav-btn-default' ?>">
                    <i class="bi bi-list-check"></i> <span>Mis Tareas</span>
                </a>

                <!-- Perfil -->
                <a href="perfil.php" 
                   class="nav-btn <?= $paginaActual == 'perfil.php' ? 'nav-btn-active' : 'nav-btn-default' ?>">
                    <i class="bi bi-person"></i> <span>Perfil</span>
                </a>

                <!-- Avatar -->
                <div class="dropdown">
                    <div class="user-avatar dropdown-toggle" data-bs-toggle="dropdown">
                        <?= strtoupper(substr($_SESSION['usuario'], 0, 1)); ?>
                    </div>

                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="px-3 py-2">
                            <small class="text-muted">Sesión iniciada como</small>
                            <div class="fw-bold"><?= htmlspecialchars($_SESSION['usuario']); ?></div>
                        </li>
                        <li><hr></li>
                        <li><a class="dropdown-item" href="logout.php">Cerrar sesión</a></li>
                    </ul>
                </div>

            <?php endif; ?>
        </div>

    </div>
</nav>

<div class="container mt-4">
