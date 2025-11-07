<?php include('../templates/cabecera.php'); ?>


<h3>Gestión de Proyectos</h3>

<div class="row">
  <div class="col-md-5">
    <form action="acciones_proyecto.php" method="post">
      <div class="card">
        <div class="card-header">Nuevo Proyecto</div>
        <div class="card-body">
          <div class="mb-3">
            <label for="nombre_proyecto" class="form-label">Nombre del proyecto</label>
            <input type="text" class="form-control" name="nombre_proyecto" id="nombre_proyecto" placeholder="Ej. Programación Web">
          </div>
          <button type="submit" name="accion" value="agregar" class="btn btn-success">Agregar</button>
        </div>
      </div>
    </form>
  </div>

  <div class="col-md-7">
    <h5>Proyectos activos</h5>
    <table class="table table-bordered">
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
        </tr>
      </thead>
      <tbody>
        <?php
          include('acciones_proyectos.php');
          foreach($listaProyectos as $proyecto) { ?>
            <tr>
              <td><?php echo $proyecto['id']; ?></td>
              <td><?php echo $proyecto['nombre']; ?></td>
            </tr>
        <?php } ?>
      </tbody>
    </table>
  </div>
</div>

<?php include('../templates/pie.php'); ?>
