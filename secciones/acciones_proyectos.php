<?php
session_start();

if (!isset($_SESSION['proyectos'])) {
    $_SESSION['proyectos'] = [];
}

if ($_POST) {
    $accion = $_POST['accion'] ?? '';
    $nombre = $_POST['nombre_proyecto'] ?? '';

    if ($accion == 'agregar' && $nombre != '') {
        $nuevo = [
            'id' => count($_SESSION['proyectos']) + 1,
            'nombre' => $nombre
        ];
        $_SESSION['proyectos'][] = $nuevo;
    }
}

$listaProyectos = $_SESSION['proyectos'];
?>