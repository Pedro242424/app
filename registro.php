<?php
include("../config/bd.php");
$mensaje = "";

if ($_POST) {
    $nombre = $_POST['nombre'];
    $correo = $_POST['correo'];
    $password = $_POST['password']; //  sin encriptar

    $query = $conexion->prepare("INSERT INTO usuarios (nombre, correo, password) VALUES (:nombre, :correo, :password)");
    $query->bindParam(":nombre", $nombre);
    $query->bindParam(":correo", $correo);
    $query->bindParam(":password", $password);

    if ($query->execute()) {
        header("Location: login.php?registro=ok");
        exit;
    } else {
        $mensaje = "Error al registrarte.";
    }
}
?>

<?php include("../includes/header.php"); ?>

<div class="row justify-content-center">
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h4 class="mb-3 text-center">Registro</h4>
                <?php if($mensaje): ?>
                    <div class="alert alert-danger py-2"><?= $mensaje ?></div>
                <?php endif; ?>
                <form method="post">
                    <div class="mb-3">
                        <label>Nombre</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Correo</label>
                        <input type="email" name="correo" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Contraseña</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-success w-100">Registrar</button>
                    <p class="text-center mt-3">¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a></p>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include("../includes/footer.php"); ?>
