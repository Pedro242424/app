<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
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

        /* Navbar mejorado */
        .navbar-modern {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            padding: 12px 0;
        }

        /* Logo y brand */
        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.5rem;
            font-weight: 700;
            color: #667eea !important;
            transition: all 0.3s;
        }

        .navbar-brand:hover {
            transform: translateY(-2px);
        }

        .logo-img {
            height: 40px;
            width: auto;
            transition: all 0.3s;
        }

        .navbar-brand:hover .logo-img {
            transform: scale(1.05);
        }

        /* Botones de navegación */
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
        }

        .nav-btn-inicio {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .nav-btn-inicio:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .nav-btn-proyectos,
        .nav-btn-perfil {
            background: transparent;
            color: #495057;
            border: 2px solid #e9ecef;
        }

        .nav-btn-proyectos:hover,
        .nav-btn-perfil:hover {
            background: #f8f9fa;
            border-color: #667eea;
            color: #667eea;
        }

        /* Avatar de usuario */
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
            transition: all 0.3s;
            border: 3px solid white;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }

        .user-avatar:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.5);
        }

        .user-name {
            font-size: 14px;
            color: #495057;
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-btn {
                padding: 8px 12px;
                font-size: 13px;
            }
            
            .user-name {
                display: none;
            }
        }
    </style>
</head>

<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-modern">
    <div class="container-fluid px-4">

        <!-- Logo y Brand -->
        <a class="navbar-brand" href="dashboard.php">
            <img src="../assets/img/logo_sharktask.png" class="logo-img" alt="SharkTask">
            <span>SharkTask</span>
        </a>

        <!-- Navegación y Usuario -->
        <div class="d-flex align-items-center gap-2">
            <?php if (isset($_SESSION['usuario'])): ?>

                <!-- Botón Inicio -->
                <a href="dashboard.php" class="nav-btn nav-btn-inicio">
                    <i class="bi bi-house-door-fill"></i>
                    <span class="d-none d-md-inline">Inicio</span>
                </a>

                <!-- Botón Proyectos -->
                <a href="proyectos.php" class="nav-btn nav-btn-proyectos">
                    <i class="bi bi-folder2-open"></i>
                    <span class="d-none d-md-inline">Proyectos</span>
                </a>

                <!-- Botón Perfil -->
                <a href="perfil.php" class="nav-btn nav-btn-perfil">
                    <i class="bi bi-person"></i>
                    <span class="d-none d-md-inline">Perfil</span>
                </a>

                <!-- Avatar de usuario -->
                <div class="dropdown">
                    <div 
                        class="user-avatar dropdown-toggle" 
                        data-bs-toggle="dropdown" 
                        aria-expanded="false"
                        title="<?= htmlspecialchars($_SESSION['usuario']); ?>"
                    >
                        <?php 
                            // Primera letra del usuario
                            $iniciales = strtoupper(substr($_SESSION['usuario'], 0, 1));
                            echo $iniciales;
                        ?>
                    </div>
                    
                    <!-- Dropdown menú -->
                    <ul class="dropdown-menu dropdown-menu-end" style="border-radius: 12px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.15);">
                        <li class="px-3 py-2">
                            <small class="text-muted">Sesión iniciada como</small>
                            <div class="fw-bold text-dark"><?= htmlspecialchars($_SESSION['usuario']); ?></div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item" href="perfil.php" style="border-radius: 8px;">
                                <i class="bi bi-person"></i> Mi perfil
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="dashboard.php" style="border-radius: 8px;">
                                <i class="bi bi-house"></i> Dashboard
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="logout.php" style="border-radius: 8px;">
                                <i class="bi bi-box-arrow-right"></i> Cerrar sesión
                            </a>
                        </li>
                    </ul>
                </div>

            <?php endif; ?>
        </div>

    </div>
</nav>

<div class="container mt-4">
