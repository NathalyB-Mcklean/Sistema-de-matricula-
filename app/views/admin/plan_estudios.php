<?php
// app/views/admin/plan_estudios.php

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

require_once '../../config/conexion.php';

$mensaje = '';
$accion = $_GET['accion'] ?? 'listar';

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $id_carrera = trim($_POST['id_carrera']);
        $id_materia = trim($_POST['id_materia']);
        $semestre = trim($_POST['semestre']);
        $tipo = trim($_POST['tipo']); // obligatoria, electiva, etc.
        $creditos = trim($_POST['creditos']);
        $pre_requisitos = trim($_POST['pre_requisitos']);
        
        // Validaciones
        if (empty($id_carrera)) throw new Exception("Debe seleccionar una carrera");
        if (empty($id_materia)) throw new Exception("Debe seleccionar una materia");
        if ($semestre < 1 || $semestre > 10) throw new Exception("Semestre inválido (1-10)");
        
        // Verificar si ya existe esta combinación
        $stmt_check = $conexion->prepare("SELECT id_plan FROM plan_estudios WHERE id_carrera = ? AND id_materia = ?");
        $stmt_check->bind_param("ii", $id_carrera, $id_materia);
        $stmt_check->execute();
        
        if (isset($_POST['id_plan'])) {
            // Actualizar
            if ($stmt_check->get_result()->num_rows > 0) {
                $existing = $stmt_check->get_result()->fetch_assoc();
                if ($existing['id_plan'] != $_POST['id_plan']) {
                    throw new Exception("Esta materia ya está asignada a esta carrera");
                }
            }
            
            $stmt = $conexion->prepare("UPDATE plan_estudios SET id_carrera = ?, id_materia = ?, semestre = ?, tipo = ?, creditos = ?, pre_requisitos = ? WHERE id_plan = ?");
            $stmt->bind_param("iiisisi", $id_carrera, $id_materia, $semestre, $tipo, $creditos, $pre_requisitos, $_POST['id_plan']);
            $mensaje = '<div class="alert alert-success">Plan de estudios actualizado</div>';
        } else {
            // Insertar nuevo
            if ($stmt_check->get_result()->num_rows > 0) {
                throw new Exception("Esta materia ya está asignada a esta carrera");
            }
            
            $stmt = $conexion->prepare("INSERT INTO plan_estudios (id_carrera, id_materia, semestre, tipo, creditos, pre_requisitos) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiisis", $id_carrera, $id_materia, $semestre, $tipo, $creditos, $pre_requisitos);
            $mensaje = '<div class="alert alert-success">Materia agregada al plan de estudios</div>';
        }
        
        if ($stmt->execute()) {
            header('Location: plan_estudios.php?mensaje=' . urlencode('Operación realizada correctamente'));
            exit();
        } else {
            throw new Exception("Error: " . $conexion->error);
        }
        
    } catch (Exception $e) {
        $mensaje = '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
    }
}

// Obtener datos para editar
$plan_editar = null;
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $stmt = $conexion->prepare("SELECT * FROM plan_estudios WHERE id_plan = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    $plan_editar = $stmt->get_result()->fetch_assoc();
}

// Obtener listas para selectores
$carreras = $conexion->query("SELECT id_carrera, nombre, codigo FROM carreras WHERE estado = 'activa' ORDER BY nombre");
$materias = $conexion->query("SELECT id_materia, nombre, codigo FROM materias ORDER BY nombre");

// Obtener planes de estudio con información de carrera y materia
$query = "SELECT ps.*, 
                 c.nombre as carrera_nombre, 
                 c.codigo as carrera_codigo,
                 m.nombre as materia_nombre,
                 m.codigo as materia_codigo,
                 m.descripcion as materia_descripcion
          FROM plan_estudios ps
          JOIN carreras c ON ps.id_carrera = c.id_carrera
          JOIN materias m ON ps.id_materia = m.id_materia
          ORDER BY c.nombre, ps.semestre";

$planes = $conexion->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plan de Estudios - Sistema UTP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .plan-container {
            padding: 20px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .plan-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .plan-table th {
            background: #6B2C91;
            color: white;
            padding: 12px;
            text-align: left;
        }
        
        .plan-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        
        .plan-table tr:hover {
            background: #f9f9f9;
        }
        
        .badge-plan {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-obligatoria { background: #2d8659; color: white; }
        .badge-electiva { background: #3498db; color: white; }
        .badge-optativa { background: #9b59b6; color: white; }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <?php include 'partials/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="plan-container">
                <h1><i class="bi bi-journal-bookmark"></i> Plan de Estudios</h1>
                
                <?php if (isset($_GET['mensaje'])): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($_GET['mensaje']); ?>
                    </div>
                <?php endif; ?>
                
                <?php echo $mensaje; ?>
                
                <div style="margin: 20px 0;">
                    <a href="?accion=nuevo" class="btn-action btn-green">
                        <i class="bi bi-plus-circle"></i> Agregar Materia al Plan
                    </a>
                </div>
                
                <?php if ($accion == 'nuevo' || $accion == 'editar'): ?>
                    <!-- Formulario -->
                    <div class="card">
                        <h2><?php echo $accion == 'nuevo' ? 'Agregar Materia al Plan' : 'Editar Materia del Plan'; ?></h2>
                        
                        <form method="POST">
                            <?php if ($plan_editar): ?>
                                <input type="hidden" name="id_plan" value="<?php echo $plan_editar['id_plan']; ?>">
                            <?php endif; ?>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Carrera *</label>
                                    <select name="id_carrera" required>
                                        <option value="">Seleccionar carrera...</option>
                                        <?php while($carrera = $carreras->fetch_assoc()): ?>
                                            <option value="<?php echo $carrera['id_carrera']; ?>"
                                                <?php echo ($plan_editar['id_carrera'] ?? '') == $carrera['id_carrera'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($carrera['codigo'] . ' - ' . $carrera['nombre']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Materia *</label>
                                    <select name="id_materia" required>
                                        <option value="">Seleccionar materia...</option>
                                        <?php while($materia = $materias->fetch_assoc()): ?>
                                            <option value="<?php echo $materia['id_materia']; ?>"
                                                <?php echo ($plan_editar['id_materia'] ?? '') == $materia['id_materia'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($materia['codigo'] . ' - ' . $materia['nombre']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Semestre *</label>
                                    <select name="semestre" required>
                                        <?php for($i = 1; $i <= 10; $i++): ?>
                                            <option value="<?php echo $i; ?>"
                                                <?php echo ($plan_editar['semestre'] ?? 1) == $i ? 'selected' : ''; ?>>
                                                Semestre <?php echo $i; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Tipo de Materia</label>
                                    <select name="tipo">
                                        <option value="obligatoria" <?php echo ($plan_editar['tipo'] ?? '') == 'obligatoria' ? 'selected' : ''; ?>>Obligatoria</option>
                                        <option value="electiva" <?php echo ($plan_editar['tipo'] ?? '') == 'electiva' ? 'selected' : ''; ?>>Electiva</option>
                                        <option value="optativa" <?php echo ($plan_editar['tipo'] ?? '') == 'optativa' ? 'selected' : ''; ?>>Optativa</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Créditos</label>
                                    <input type="number" name="creditos" min="1" max="10" 
                                           value="<?php echo $plan_editar['creditos'] ?? 3; ?>">
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Prerrequisitos (separados por coma)</label>
                                <input type="text" name="pre_requisitos" 
                                       value="<?php echo htmlspecialchars($plan_editar['pre_requisitos'] ?? ''); ?>"
                                       placeholder="Ej: MAT-101, FIS-201">
                            </div>
                            
                            <div style="margin-top: 20px;">
                                <button type="submit" class="btn-action btn-green">
                                    <i class="bi bi-save"></i> Guardar
                                </button>
                                <a href="plan_estudios.php" class="btn-action">
                                    <i class="bi bi-x-circle"></i> Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                    
                <?php else: ?>
                    <!-- Tabla de planes de estudio -->
                    <table class="plan-table">
                        <thead>
                            <tr>
                                <th>Carrera</th>
                                <th>Materia</th>
                                <th>Semestre</th>
                                <th>Tipo</th>
                                <th>Créditos</th>
                                <th>Prerrequisitos</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($planes->num_rows > 0): ?>
                                <?php while($plan = $planes->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($plan['carrera_codigo']); ?></strong><br>
                                            <?php echo htmlspecialchars($plan['carrera_nombre']); ?>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($plan['materia_codigo']); ?></strong><br>
                                            <?php echo htmlspecialchars($plan['materia_nombre']); ?>
                                        </td>
                                        <td>
                                            <span class="badge" style="background: #6B2C91; color: white; padding: 5px 10px;">
                                                Semestre <?php echo $plan['semestre']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php $badge_class = 'badge-' . ($plan['tipo'] ?? 'obligatoria'); ?>
                                            <span class="badge-plan <?php echo $badge_class; ?>">
                                                <?php echo ucfirst($plan['tipo'] ?? 'obligatoria'); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $plan['creditos'] ?? '3'; ?></td>
                                        <td>
                                            <?php if (!empty($plan['pre_requisitos'])): ?>
                                                <?php echo htmlspecialchars($plan['pre_requisitos']); ?>
                                            <?php else: ?>
                                                <span style="color: #999;">Ninguno</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="?accion=editar&id=<?php echo $plan['id_plan']; ?>" 
                                               class="btn-action btn-blue btn-sm">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="?accion=eliminar&id=<?php echo $plan['id_plan']; ?>" 
                                               onclick="return confirm('¿Eliminar esta materia del plan de estudios?')"
                                               class="btn-action btn-danger btn-sm">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 40px;">
                                        <i class="bi bi-journal-x" style="font-size: 48px; color: #ddd;"></i>
                                        <h3>No hay materias en el plan de estudios</h3>
                                        <p>Comienza agregando materias al plan de estudios.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
<?php $conexion->close(); ?>