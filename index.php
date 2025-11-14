<?php
// Si el usuario ya inició sesión, lo manda al dashboard
session_start();

if (isset($_SESSION['usuario'])) {
    header("Location: app/dashboard.php");
    exit;
} else {
    // Si no hay sesión, lo manda al login
    header("Location: app/login.php");
    exit;
}
?>