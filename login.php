<?php
session_start();
include("../config/bd.php");

$mensaje = "";

if ($_POST) {
    $correo = $_POST['correo'];
    $password = $_POST['password'];

    // Busca el usuario por correo
    $query = $conexion->prepare("SELECT * FROM usuarios WHERE correo = :correo LIMIT 1");
    $query->bindParam(":correo", $correo);
    $query->execute();
    $usuario = $query->fetch(PDO::FETCH_ASSOC);

    // Verifica contraseña SIN encriptar (por ahora)
    if ($usuario && $password == $usuario['password']) {
        $_SESSION['usuario'] = $usuario['nombre'];
        $_SESSION['id_usuario'] = $usuario['id'];
        header("Location: ../app/dashboard.php");
        exit;
    } else {
        $mensaje = "Correo o contraseña incorrectos.";
    }
}
?>

<?php include("../includes/header.php"); ?>

<div class="row justify-content-center">
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h4 class="mb-3 text-center">Iniciar sesión</h4>
                <?php if($mensaje): ?>
                    <div class="alert alert-danger py-2"><?= $mensaje ?></div>
                <?php endif; ?>
                <form method="post">
                    <div class="mb-3">
                        <label>Correo</label>
                        <input type="email" name="correo" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Contraseña</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Entrar</button>
                    <p class="text-center mt-3">¿No tienes cuenta? <a href="registro.php">Regístrate</a></p>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include("../includes/footer.php"); ?>
