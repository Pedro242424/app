<?php
/**
 * PROYECTOS - Página principal
 * Muestra todos los proyectos del usuario actual en formato de tarjetas.
 * Incluye un modal para crear nuevos proyectos que se carga dinámicamente.
 */

session_start();
include("../config/bd.php");

// Verificar que el usuario haya iniciado sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit;
}

// Obtener datos del usuario actual
$id_usuario = $_SESSION['id_usuario'];
$correo_usuario = trim(strtolower($_SESSION['usuario']));

// OBTENER PROYECTOS DEL USUARIO
// Consultar todos los proyectos donde el usuario es el creador
$query = $conexion->prepare("SELECT * FROM proyectos WHERE id_usuario = :id_usuario ORDER BY id DESC");
$query->bindParam(":id_usuario", $id_usuario);
$query->execute();
$proyectos = $query->fetchAll(PDO::FETCH_ASSOC);

// CONFIGURACIÓN DE COLORES PARA LAS TARJETAS
// Array de gradientes que se asignan rotativamente a cada proyecto
$colores = [
    'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
    'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
    'linear-gradient(135deg, #fbc2eb 0%, #d4a574 100%)',
    'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)',
    'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)',
];

include("../includes/header.php");
?>

<style>
    /* ESTILOS GENERALES */
    body {
        background: #f5f5f7;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .page-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 40px 20px;
    }

    /* ENCABEZADO DE LA PÁGINA */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 30px;
    }

    .page-title-section h1 {
        font-size: 42px;
        font-weight: 700;
        color: #333;
        margin: 0;
    }

    .page-title-section p {
        color: #999;
        font-size: 16px;
        margin: 5px 0 0 0;
    }

    /* Botón para crear nuevo proyecto */
    .btn-new-project {
        background: #667eea;
        color: white;
        border: none;
        padding: 12px 28px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 15px;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .btn-new-project:hover {
        background: #5568d3;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
    }

    /* TABS DE FILTRADO */
    .tabs-container {
        display: flex;
        gap: 12px;
        margin-bottom: 30px;
    }

    .tab {
        padding: 10px 24px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s;
        border: none;
    }

    .tab.active {
        background: #667eea;
        color: white;
    }

    .tab.inactive {
        background: #e8e8ea;
        color: #666;
    }

    .tab.inactive:hover {
        background: #d8d8da;
    }

    /* GRID DE PROYECTOS */
    .projects-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 24px;
    }

    /* Tarjeta individual de proyecto */
    .project-card {
        border-radius: 20px;
        padding: 28px;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        min-height: 180px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
    }

    /* Efecto de textura en la tarjeta (patrón de cuadrícula sutil) */
    .project-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: repeating-linear-gradient(
            0deg,
            rgba(255,255,255,0.03) 0px,
            rgba(255,255,255,0.03) 1px,
            transparent 1px,
            transparent 20px
        ),
        repeating-linear-gradient(
            90deg,
            rgba(255,255,255,0.03) 0px,
            rgba(255,255,255,0.03) 1px,
            transparent 1px,
            transparent 20px
        );
        pointer-events: none;
    }

    /* Efecto hover: elevar la tarjeta */
    .project-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 12px 30px rgba(0,0,0,0.2);
    }

    .project-card-content {
        position: relative;
        z-index: 1;
    }

    .project-card h4 {
        color: white;
        font-weight: 700;
        font-size: 20px;
        margin-bottom: 15px;
        line-height: 1.3;
    }

    /* Contenedor de etiquetas (tags) del proyecto */
    .project-tags {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }

    /* Etiqueta individual con efecto glassmorphism */
    .project-tag {
        background: rgba(255, 255, 255, 0.25);
        color: white;
        padding: 5px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
        backdrop-filter: blur(10px);
    }

    /* Pie de la tarjeta (fecha límite) */
    .project-footer {
        display: flex;
        align-items: center;
        gap: 8px;
        color: rgba(255, 255, 255, 0.9);
        font-size: 14px;
        font-weight: 500;
        position: relative;
        z-index: 1;
    }

    .project-footer i {
        font-size: 16px;
    }

    /* ESTADO VACÍO (cuando no hay proyectos) */
    .empty-state {
        text-align: center;
        padding: 80px 20px;
        background: white;
        border-radius: 20px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }

    .empty-state-icon {
        font-size: 100px;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    .empty-state h3 {
        color: #333;
        font-weight: 600;
        margin-bottom: 10px;
    }

    .empty-state p {
        color: #999;
        margin-bottom: 30px;
    }

    /* MODAL */
    .modal-content {
        border-radius: 25px;
        border: none;
    }
</style>

<div class="page-container">
    <!--  ENCABEZADO DE LA PÁGINA -->
    <div class="page-header">
        <div class="page-title-section">
            <h1>Mis Proyectos</h1>
            <p><?= count($proyectos); ?> proyectos activos</p>
        </div>
        <!-- Botón que abre el modal para crear un nuevo proyecto -->
        <button class="btn-new-project" data-bs-toggle="modal" data-bs-target="#modalNuevoProyecto">
            <i class="bi bi-plus-lg"></i>
            Nuevo proyecto
        </button>
    </div>

    <!-- TABS DE FILTRADO -->
    <div class="tabs-container">
        <button class="tab active">Todos (<?= count($proyectos); ?>)</button>
        <button class="tab inactive">Activos (<?= count($proyectos); ?>)</button>
        <button class="tab inactive">Completados (0)</button>
    </div>

    <!-- GRID DE PROYECTOS O ESTADO VACÍO -->
    <?php if (count($proyectos) > 0): ?>
        <!-- Si hay proyectos, mostrar el grid -->
        <div class="projects-grid">
            <?php foreach ($proyectos as $index => $p): 
                // Asignar color del array de forma rotativa usando el módulo
                $color = $colores[$index % count($colores)];
                
                // Generar etiquetas dinámicas basadas en palabras clave del nombre
                $tags = ['Proyecto'];
                if (stripos($p['nombre'], 'diseño') !== false || stripos($p['nombre'], 'prototipo') !== false) {
                    $tags[] = 'Diseño';
                }
                if (stripos($p['nombre'], 'software') !== false || stripos($p['nombre'], 'calidad') !== false) {
                    $tags[] = 'Testing';
                }
                if (stripos($p['nombre'], 'ágil') !== false || stripos($p['nombre'], 'scrum') !== false) {
                    $tags[] = 'Scrum';
                }
            ?>
                <!-- Tarjeta de proyecto (al hacer clic redirige a las tareas) -->
                <div class="project-card" 
                     style="background: <?= $color; ?>;"
                     onclick="window.location.href='tareas.php?id=<?= $p['id']; ?>'">
                    
                    <div class="project-card-content">
                        <h4><?= htmlspecialchars($p['nombre']); ?></h4>
                        
                        <!-- Etiquetas del proyecto -->
                        <div class="project-tags">
                            <?php foreach ($tags as $tag): ?>
                                <span class="project-tag"><?= $tag; ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Pie de tarjeta con fecha límite -->
                    <div class="project-footer">
                        <i class="bi bi-calendar-check"></i>
                        <?= date('d/m/y', strtotime($p['fecha_limite'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <!-- Si no hay proyectos, mostrar mensaje de estado vacío -->
        <div class="empty-state">
            <div class="empty-state-icon">
                <i class="bi bi-folder-x"></i>
            </div>
            <h3>No tienes proyectos aún</h3>
            <p>Crea tu primer proyecto y empieza a organizarte</p>
            <button class="btn-new-project" data-bs-toggle="modal" data-bs-target="#modalNuevoProyecto">
                <i class="bi bi-folder-plus"></i>
                Crear primer proyecto
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- 
     MODAL PARA CREAR NUEVO PROYECTO
      -->
<!-- El contenido se carga dinámicamente desde crear_proyecto.php -->
<div class="modal fade" id="modalNuevoProyecto" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" id="modalContenido">
            <!-- Contenedor donde se insertará el HTML del formulario -->
        </div>
    </div>
</div>

<script>
/**
 * SCRIPT PARA CARGA DINÁMICA DEL MODAL
 * Este script maneja la carga del formulario por AJAX cuando se abre el modal
 * y la comunicación entre el modal y la página principal.
 */

// Obtener referencia al elemento del modal
const modalElement = document.getElementById('modalNuevoProyecto');

// Verificar que el modal existe
if (modalElement) {
    

    // EVENTO: Cuando se abre el modal

    modalElement.addEventListener('show.bs.modal', function () {
        
        // Obtener el contenedor donde se insertará el contenido
        const contenedor = document.getElementById('modalContenido');
        
        // Mostrar spinner mientras se carga el formulario
        contenedor.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                </div>
            </div>
        `;
        
    
        // CARGAR FORMULARIO POR AJAX
    
        fetch('crear_proyecto.php?modal=1')
            .then(response => response.text())
            .then(html => {
                // Insertar el HTML recibido en el contenedor
                contenedor.innerHTML = html;
                
            
                // RE-EJECUTAR SCRIPTS DEL MODAL
            
                // Los scripts insertados con innerHTML no se ejecutan automáticamente,
                // por lo que es necesario extraerlos y re-ejecutarlos manualmente
                
                const scripts = contenedor.querySelectorAll('script');
                scripts.forEach((scriptViejo) => {
                    // Crear un nuevo elemento script
                    const scriptNuevo = document.createElement('script');
                    
                    // Copiar el contenido del script original
                    scriptNuevo.textContent = scriptViejo.textContent;
                    
                    // Agregar el script al body para que se ejecute
                    document.body.appendChild(scriptNuevo);
                });
            })
            .catch(error => {
                // Mostrar mensaje de error si falla la carga
                console.error('Error al cargar modal:', error);
                contenedor.innerHTML = `
                    <div class="modal-body p-5 text-center">
                        <p class="text-danger">Error al cargar el formulario</p>
                        <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                `;
            });
    });
}

// ESCUCHAR MENSAJES DEL MODAL
// El modal envía un mensaje cuando se crea exitosamente un proyecto
window.addEventListener('message', function(event) {
    
    if (event.data === 'proyecto_creado') {
        
        // Cerrar el modal usando la API de Bootstrap
        const modal = bootstrap.Modal.getInstance(modalElement);
        if (modal) {
            modal.hide();
        }
        
        // Esperar 300ms para que el modal se cierre suavemente,
        // luego recargar la página para mostrar el nuevo proyecto
        setTimeout(() => {
            window.location.reload();
        }, 300);
    }
});
</script>

<?php include("../includes/footer.php"); ?>