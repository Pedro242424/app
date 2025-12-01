<?php
/**
 * CREAR PROYECTO - MODAL
 * Este archivo maneja la creación de proyectos a través de un modal cargado por AJAX.
 * Solo procesa peticiones POST (crear proyecto) y GET con ?modal=1 (mostrar formulario).
 */

session_start();
include("../config/bd.php");

// VERIFICACIÓN DE SEGURIDAD
// Si el usuario no ha iniciado sesión, devolver error JSON y detener ejecución
if (!isset($_SESSION['id_usuario'])) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$id_usuario = $_SESSION['id_usuario'];

// PROCESAR FORMULARIO (CREAR PROYECTO)
// Solo se ejecuta cuando el formulario se envía (método POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Obtener y limpiar datos del formulario
    $nombre = trim($_POST['nombre']);
    $descripcion = trim($_POST['descripcion']);
    $fecha_limite = $_POST['fecha_limite'];

    // Validar que los campos obligatorios no estén vacíos
    if (empty($nombre) || empty($descripcion) || empty($fecha_limite)) {
        echo json_encode(['success' => false, 'mensaje' => 'Todos los campos son obligatorios.']);
        exit;
    }

    try {
        // PASO 1: Insertar el proyecto en la base de datos
        $query = $conexion->prepare("
            INSERT INTO proyectos (nombre, descripcion, fecha_limite, id_usuario) 
            VALUES (:nombre, :descripcion, :fecha_limite, :id_usuario)
        ");
        $query->bindParam(":nombre", $nombre);
        $query->bindParam(":descripcion", $descripcion);
        $query->bindParam(":fecha_limite", $fecha_limite);
        $query->bindParam(":id_usuario", $id_usuario);
        $query->execute();

        // Obtener el ID del proyecto recién creado
        $id_proyecto = $conexion->lastInsertId();

        // PASO 2: Agregar automáticamente al creador como miembro del proyecto
        // Primero obtener el correo del usuario que creó el proyecto
        $query_correo = $conexion->prepare("SELECT correo FROM usuarios WHERE id = :id");
        $query_correo->bindParam(":id", $id_usuario);
        $query_correo->execute();
        $correo_creador = $query_correo->fetch(PDO::FETCH_ASSOC)['correo'];

        // Insertar al creador en la tabla de miembros
        $stmt_creador = $conexion->prepare("
            INSERT INTO miembros (id_proyecto, correo_miembro) 
            VALUES (:id_proyecto, :correo)
        ");
        $stmt_creador->bindParam(":id_proyecto", $id_proyecto);
        $stmt_creador->bindParam(":correo", $correo_creador);
        $stmt_creador->execute();

        // PASO 3: Agregar los miembros adicionales que el usuario especificó
        if (!empty($_POST['miembros'])) {
            // Limpiar el array: eliminar espacios en blanco y valores vacíos
            $miembros = array_filter(array_map('trim', $_POST['miembros']));

            foreach ($miembros as $correo) {
                // Saltar si el correo está vacío o es el mismo del creador (evitar duplicados)
                if (empty($correo) || strtolower($correo) === strtolower($correo_creador)) {
                    continue;
                }

                // Insertar cada miembro en la tabla
                $stmt = $conexion->prepare("
                    INSERT INTO miembros (id_proyecto, correo_miembro) 
                    VALUES (:id_proyecto, :correo)
                ");
                $stmt->bindParam(":id_proyecto", $id_proyecto);
                $stmt->bindParam(":correo", $correo);
                $stmt->execute();
            }
        }

        // Devolver respuesta exitosa en formato JSON
        echo json_encode(['success' => true, 'mensaje' => 'Proyecto creado correctamente']);
        exit;

    } catch (PDOException $e) {
        // Si hay error en la base de datos, devolver el mensaje de error
        echo json_encode(['success' => false, 'mensaje' => "Error: " . $e->getMessage()]);
        exit;
    }
}


// MOSTRAR FORMULARIO DEL MODAL (HTML)
?>
<style>
/* Estilo para que el botón de eliminar tenga bordes redondeados correctos */
.input-group .btn-outline-danger {
    border-radius: 0 0.375rem 0.375rem 0;
}
</style>

<!-- ENCABEZADO DEL MODAL -->
<div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 25px 25px 0 0; border: none; padding: 30px;">
    <div class="modal-header-content text-center text-white w-100">
        <h3 class="fw-bold mb-1"><i class="bi bi-folder-plus"></i> Nuevo Proyecto</h3>
        <p class="mb-0" style="opacity: 0.9;">Organiza tu trabajo</p>
    </div>
    <!-- Botón para cerrar el modal -->
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>

<!-- CUERPO DEL MODAL -->
<div class="modal-body p-4">
    <form method="post" id="formProyectoModal">
        
        <!-- CAMPO: Nombre del proyecto -->
        <div class="mb-3">
            <label class="form-label fw-semibold">
                <i class="bi bi-folder-fill text-primary"></i> Nombre del proyecto
            </label>
            <input type="text" name="nombre" class="form-control form-control-lg"
                   placeholder="Ej: Rediseño de la app" style="border-radius: 15px;" required>
        </div>

        <!-- CAMPO: Descripción -->
        <div class="mb-3">
            <label class="form-label fw-semibold">
                <i class="bi bi-text-paragraph text-primary"></i> Descripción
            </label>
            <textarea name="descripcion" class="form-control" rows="4"
                      placeholder="Describe los objetivos del proyecto..."
                      style="border-radius: 15px;" required></textarea>
        </div>

        <!-- CAMPO: Fecha límite -->
        <div class="mb-3">
            <label class="form-label fw-semibold">
                <i class="bi bi-calendar-event text-primary"></i> Fecha límite
            </label>
            <input type="date" name="fecha_limite"
                   class="form-control form-control-lg"
                   style="border-radius: 15px;" required>
        </div>

        <!-- SECCIÓN: Miembros del proyecto -->
        <div class="mb-4">
            <label class="form-label fw-semibold">
                <i class="bi bi-people-fill text-primary"></i> Miembros del proyecto
            </label>

            <!-- Contenedor donde se agregarán dinámicamente los campos de miembros -->
            <div id="miembrosContainer">
                <!-- Primer campo de miembro (siempre visible) -->
                <div class="input-group mb-2">
                    <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                    <input type="email" name="miembros[]" class="form-control" placeholder="correo@ejemplo.com">
                </div>
            </div>

            <!-- Botón para agregar más miembros -->
            <button type="button" class="btn btn-outline-primary btn-sm" id="btnAgregarMiembro"
                    style="border-radius: 10px;">
                <i class="bi bi-plus-circle"></i> Agregar otro miembro
            </button>
        </div>

        <!-- BOTONES DE ACCIÓN -->
        <div class="d-grid gap-2">
            <!-- Botón de enviar formulario -->
            <button type="submit" class="btn btn-lg text-white fw-bold"
                    style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 15px; padding: 14px;">
                <i class="bi bi-check-circle"></i> Crear proyecto
            </button>

            <!-- Botón de cancelar (cierra el modal) -->
            <button type="button" class="btn btn-outline-secondary btn-lg"
                    data-bs-dismiss="modal" style="border-radius: 15px;">
                <i class="bi bi-x-circle"></i> Cancelar
            </button>
        </div>
    </form>
</div>

<script>
/**
 * Script del modal
 * Este código se ejecuta cuando el contenido del modal se carga en el DOM.
 * Maneja la funcionalidad de agregar miembros y el envío del formulario por AJAX.
 */

// Esperar 100ms para asegurar que el DOM esté completamente renderizado
setTimeout(function() {
    
    // Obtener referencias a los elementos del DOM
    const btnAgregarMiembro = document.getElementById('btnAgregarMiembro');
    const contenedorMiembros = document.getElementById('miembrosContainer');
    const formulario = document.getElementById('formProyectoModal');
    
    // FUNCIONALIDAD: Agregar campos de miembros de forma dinamica
    if (btnAgregarMiembro && contenedorMiembros) {
        btnAgregarMiembro.addEventListener('click', function() {
            
            // Crear un nuevo div para el campo de miembro
            const nuevoDiv = document.createElement('div');
            nuevoDiv.className = 'input-group mb-2';
            
            // Agregar el HTML del nuevo campo (input + botón eliminar)
            nuevoDiv.innerHTML = `
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="miembros[]" class="form-control" placeholder="correo@ejemplo.com">
                <button type="button" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-trash"></i>
                </button>
            `;
            
            // Agregar el nuevo campo al contenedor
            contenedorMiembros.appendChild(nuevoDiv);
            
            // Agregar funcionalidad al botón de eliminar
            nuevoDiv.querySelector('.btn-outline-danger').addEventListener('click', function() {
                nuevoDiv.remove(); // Eliminar todo el campo cuando se haga clic en el botón
            });
        });
    }

    
    // FUNCIONALIDAD: Enviar formulario por AJAX
    if (formulario) {
        formulario.addEventListener('submit', function(e) {
            // Prevenir el envío tradicional del formulario (recarga de página)
            e.preventDefault();
            
            // Obtener el botón de envío para modificar su estado
            const botonEnviar = formulario.querySelector('button[type="submit"]');
            const textoOriginal = botonEnviar.innerHTML;
            
            // Deshabilitar el botón y mostrar spinner mientras se procesa
            botonEnviar.disabled = true;
            botonEnviar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Creando...';
            
            // Crear objeto FormData con todos los datos del formulario
            const datosFormulario = new FormData(formulario);
            
            // Enviar datos al servidor por AJAX
            fetch('crear_proyecto.php', {
                method: 'POST',
                body: datosFormulario
            })
            .then(response => response.json()) // Convertir respuesta a JSON
            .then(data => {
                if (data.success) {
                    // Si se creó exitosamente, enviar mensaje al padre para cerrar modal y recargar
                    window.parent.postMessage('proyecto_creado', '*');
                } else {
                    // Si hubo error, mostrar mensaje y restaurar el botón
                    alert('Error: ' + data.mensaje);
                    botonEnviar.disabled = false;
                    botonEnviar.innerHTML = textoOriginal;
                }
            })
            .catch(error => {
                // Si hubo error en la petición, mostrar mensaje y restaurar el botón
                console.error('Error:', error);
                alert('Error al crear el proyecto. Intenta nuevamente.');
                botonEnviar.disabled = false;
                botonEnviar.innerHTML = textoOriginal;
            });
        });
    }
    
}, 100); // Timeout de 100ms
</script>