<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>SharkTask</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .navbar-brand { display: flex; align-items: center; gap: 6px;}
        .navbar-brand i { font-size: 1.3rem; }

        .custom-navbar {
        background-color: #513174;
        }
    </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark custom-navbar shadow-sm">
    <div class="container-fluid px-4">
        <a class="navbar-brand fw-bold text-primary" href="dashboard.php">
            <i class="bi bi-shield-shaded text-primary"></i> SharkTask
        </a>
        <div class="d-flex align-items-center gap-2">
            <?php if (isset($_SESSION['usuario'])): ?>
                <!-- Nombre del usuario -->
                <span class="text-light me-2">
                    <i class="bi bi-person-circle"></i>
                    <?= htmlspecialchars($_SESSION['usuario']); ?>
                </span>

                <!-- Acceso a proyectos -->
                <a href="proyectos.php" class="btn btn-sm btn-light">
                    <i class="bi bi-folder2-open"></i> Proyectos
                </a>

                <!-- Acceso a perfil -->
                <a href="perfil.php" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-person"></i> Perfil
                </a>

                <!-- Cerrar sesión -->
                <a href="logout.php" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-box-arrow-right"></i> Cerrar sesión
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<div class="container mt-4">




