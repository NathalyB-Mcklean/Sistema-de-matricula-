<?php
// app/views/estudiante/encuesta.php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'estudiante') {
    header('Location: ../../auth/login.php');
    exit();
}

require_once '../../config/conexion.php';

$id_estudiante = $_SESSION['id_estudiante'];

// Variables para los partials
$titulo_pagina = 'Encuesta de Satisfacción - Sistema UTP';
$pagina_activa = 'encuesta';

// Obtener información del estudiante para mostrar en sidebar
$query_estudiante = "SELECT e.*, CONCAT(u.nombre, ' ', u.apellido) as nombre_completo 
                     FROM estudiantes e 
                     JOIN usuario u ON e.id_usuario = u.id_usuario 
                     WHERE e.id_estudiante = ?";
$stmt_info = $conexion->prepare($query_estudiante);
$stmt_info->bind_param('i', $id_estudiante);
$stmt_info->execute();
$estudiante_info = $stmt_info->get_result()->fetch_assoc();

// Verificar si ya existe una encuesta
$query_encuesta = "SELECT * FROM encuestas WHERE id_estudiante = ?";
$stmt = $conexion->prepare($query_encuesta);
$stmt->bind_param('i', $id_estudiante);
$stmt->execute();
$encuesta_existente = $stmt->get_result()->fetch_assoc();

$mensaje = '';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $satisfaccion = $_POST['satisfaccion'] ?? '';
    $observaciones = $_POST['observaciones'] ?? '';
    
    if (empty($satisfaccion)) {
        $mensaje = '<div class="alert alert-danger">Debes seleccionar un nivel de satisfacción</div>';
    } else {
        try {
            if ($encuesta_existente) {
                // Actualizar encuesta existente
                $query = "UPDATE encuestas SET satisfaccion = ?, observaciones = ?, fecha = NOW() WHERE id_encuesta = ?";
                $stmt = $conexion->prepare($query);
                $stmt->bind_param('ssi', $satisfaccion, $observaciones, $encuesta_existente['id_encuesta']);
            } else {
                // Crear nueva encuesta
                $query = "INSERT INTO encuestas (id_estudiante, satisfaccion, observaciones, fecha) VALUES (?, ?, ?, NOW())";
                $stmt = $conexion->prepare($query);
                $stmt->bind_param('iss', $id_estudiante, $satisfaccion, $observaciones);
            }
            
            $stmt->execute();
            $mensaje = '<div class="alert alert-success">Encuesta guardada exitosamente</div>';
            
            // Actualizar variable local
            $encuesta_existente = ['satisfaccion' => $satisfaccion, 'observaciones' => $observaciones];
            
        } catch (Exception $e) {
            $mensaje = '<div class="alert alert-danger">Error al guardar la encuesta: ' . $e->getMessage() . '</div>';
        }
    }
}

// CSS adicional para esta página
$css_adicional = "
    .encuesta-container {
        max-width: 800px;
        margin: 0 auto;
    }
    
    .opcion-satisfaccion {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px;
        border: 2px solid #ddd;
        border-radius: 8px;
        margin-bottom: 10px;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .opcion-satisfaccion:hover {
        border-color: #2c5282;
        background: #f7fafc;
    }
    
    .opcion-satisfaccion.selected {
        border-color: #38a169;
        background: #f0fff4;
    }
    
    .opcion-satisfaccion input {
        display: none;
    }
    
    .icono-opcion {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }
    
    .opcion-excelente .icono-opcion {
        background: #38a169;
        color: white;
    }
    
    .opcion-conforme .icono-opcion {
        background: #3182ce;
        color: white;
    }
    
    .opcion-inconforme .icono-opcion {
        background: #e53e3e;
        color: white;
    }
    
    .opcion-no-respondida .icono-opcion {
        background: #a0aec0;
        color: white;
    }
    
    .detalles-opcion {
        flex: 1;
    }
    
    .detalles-opcion h4 {
        margin: 0 0 5px 0;
    }
    
    .detalles-opcion p {
        margin: 0;
        color: #666;
        font-size: 0.9rem;
    }
    
    textarea {
        width: 100%;
        padding: 15px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-family: inherit;
        font-size: 1rem;
        resize: vertical;
        min-height: 120px;
        margin-top: 10px;
    }
    
    .form-actions {
        margin-top: 30px;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }
    
    .alert {
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
    }
    
    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
";

// Ahora incluimos el header y sidebar
require_once 'partials/header.php';
require_once 'partials/sidebar.php';
?>

<!-- Main Content -->
<main class="main-content">
    <div class="breadcrumb">
        <a href="dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a>
        <span> / </span>
        <span>Encuesta de Satisfacción</span>
    </div>
    
    <div class="header-actions">
        <h1>
            <i class="bi bi-clipboard-check"></i>
            Encuesta de Satisfacción
        </h1>
        <a href="dashboard.php" class="btn-action">Volver al Dashboard</a>
    </div>
    
    <?php echo $mensaje; ?>
    
    <div class="encuesta-container">
        <form method="POST" action="">
            <div style="margin-bottom: 30px;">
                <h2>Tu opinión es importante para nosotros</h2>
                <p>Por favor, califica tu experiencia en el sistema de matrícula.</p>
            </div>
            
            <div style="margin-bottom: 30px;">
                <h3>Nivel de Satisfacción</h3>
                <p>Selecciona una opción:</p>
                
                <div class="opciones-satisfaccion">
                    <label class="opcion-satisfaccion opcion-excelente <?php echo ($encuesta_existente['satisfaccion'] ?? '') == 'Excelente' ? 'selected' : ''; ?>">
                        <input type="radio" name="satisfaccion" value="Excelente" 
                            <?php echo ($encuesta_existente['satisfaccion'] ?? '') == 'Excelente' ? 'checked' : ''; ?>>
                        <div class="icono-opcion">
                            <i class="bi bi-emoji-laughing"></i>
                        </div>
                        <div class="detalles-opcion">
                            <h4>Excelente</h4>
                            <p>La experiencia superó todas mis expectativas</p>
                        </div>
                    </label>
                    
                    <label class="opcion-satisfaccion opcion-conforme <?php echo ($encuesta_existente['satisfaccion'] ?? '') == 'Conforme' ? 'selected' : ''; ?>">
                        <input type="radio" name="satisfaccion" value="Conforme" 
                            <?php echo ($encuesta_existente['satisfaccion'] ?? '') == 'Conforme' ? 'checked' : ''; ?>>
                        <div class="icono-opcion">
                            <i class="bi bi-emoji-smile"></i>
                        </div>
                        <div class="detalles-opcion">
                            <h4>Conforme</h4>
                            <p>La experiencia fue satisfactoria</p>
                        </div>
                    </label>
                    
                    <label class="opcion-satisfaccion opcion-inconforme <?php echo ($encuesta_existente['satisfaccion'] ?? '') == 'Inconforme' ? 'selected' : ''; ?>">
                        <input type="radio" name="satisfaccion" value="Inconforme" 
                            <?php echo ($encuesta_existente['satisfaccion'] ?? '') == 'Inconforme' ? 'checked' : ''; ?>>
                        <div class="icono-opcion">
                            <i class="bi bi-emoji-frown"></i>
                        </div>
                        <div class="detalles-opcion">
                            <h4>Inconforme</h4>
                            <p>La experiencia no fue satisfactoria</p>
                        </div>
                    </label>
                    
                    <label class="opcion-satisfaccion opcion-no-respondida <?php echo ($encuesta_existente['satisfaccion'] ?? '') == 'No respondida' ? 'selected' : ''; ?>">
                        <input type="radio" name="satisfaccion" value="No respondida" 
                            <?php echo ($encuesta_existente['satisfaccion'] ?? '') == 'No respondida' ? 'checked' : ''; ?>>
                        <div class="icono-opcion">
                            <i class="bi bi-dash-circle"></i>
                        </div>
                        <div class="detalles-opcion">
                            <h4>No respondida</h4>
                            <p>Prefiero no responder</p>
                        </div>
                    </label>
                </div>
            </div>
            
            <div style="margin-bottom: 30px;">
                <h3>Observaciones (Opcional)</h3>
                <p>Comparte tus comentarios, sugerencias o inquietudes:</p>
                <textarea name="observaciones" placeholder="Escribe tus observaciones aquí..."><?php echo htmlspecialchars($encuesta_existente['observaciones'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-actions">
                <a href="dashboard.php" class="btn-action">Cancelar</a>
                <button type="submit" class="btn-action btn-green">
                    <i class="bi bi-save"></i> Guardar Encuesta
                </button>
            </div>
        </form>
    </div>
</main>

<script>
    // Selección visual de opciones
    document.querySelectorAll('.opcion-satisfaccion').forEach(opcion => {
        opcion.addEventListener('click', function() {
            // Deseleccionar todas
            document.querySelectorAll('.opcion-satisfaccion').forEach(o => o.classList.remove('selected'));
            // Seleccionar esta
            this.classList.add('selected');
            // Marcar el radio button
            const radio = this.querySelector('input[type="radio"]');
            radio.checked = true;
        });
    });
</script>

<?php
$conexion->close();
require_once 'partials/footer.php';
?>