<?php include('../templates/cabecera.php'); ?>
<?php include('../secciones/tareas.php');   ?>

Control de tareas
<div class="col-md-5">

<form action="" method="post">
<div class="card">
    <div class="card-header">
        Cursos
    </div>
    <div class="card-body">
    <div class="mb-3">
    <label for="" class="form-label">ID</label>
    <input type="text"
        class="form-control"
        name="id"
        id="id"
        aria-describedby="helpId" placeholder="ID">
</div>     
<div class="mb-3">
        <label for="nombre_tarea" class="form-label">Nombre de la tarea</label>
        <input
            type="text"
            class="form-control"
            name="nombre_tarea"
            id="nombre_tarea"
            aria-describedby="helpId"
            placeholder="Tarea"/>
    </div>

    <div class="btn-group" role="group" aria-label="Button group name">
        <button
            type="button"
            class="btn btn-success">
            Agregar
        </button>
        <button
            type="button"
            class="btn btn-warning">
            Editar
        </button>
        <button
            type="button"
            class="btn btn-danger">
            Borrar
        </button>
    </div>
    



</div>


</form>

</div>
<div class="col-md-7">

</div>


     
<?php include('../templates/pie.php'); ?>