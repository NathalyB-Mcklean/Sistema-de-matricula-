<?php
// app/views/estudiante/partials/sidebar.php

if (!isset($estudiante_info)) {
    $estudiante_info = ['nombre_completo' => 'Estudiante', 'carrera_nombre' => 'Estudiante'];
}

$nombre = $estudiante_info['nombre_completo'] ?? 'Estudiante';
$nombre_parts = explode(' ', $nombre);
$iniciales = '';

if (count($nombre_parts) >= 2) {
    $iniciales = strtoupper(substr($nombre_parts[0], 0, 1) . substr($nombre_parts[1], 0, 1));
} elseif (!empty($nombre_parts[0])) {
    $iniciales = strtoupper(substr($nombre_parts[0], 0, 1));
} else {
    $iniciales = 'E';
}
?>
<aside class="sidebar">
    <div class="logo">
        <h2>UTP Estudiante</h2>
        <small>Sistema de Matrícula</small>
    </div>
    
    <div class="user-info">
        <div class="avatar">
            <?php echo $iniciales; ?>
        </div>
        <h3><?php echo htmlspecialchars($nombre); ?></h3>
        <p><?php echo htmlspecialchars($estudiante_info['carrera_nombre'] ?? 'Estudiante'); ?></p>
    </div>
    
    <nav class="nav-menu">
        <a href="dashboard.php" class="nav-item <?php echo $pagina_activa == 'dashboard' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>
        <a href="materias.php" class="nav-item <?php echo $pagina_activa == 'materias' ? 'active' : ''; ?>">
            <i class="bi bi-journal-text"></i>
            <span>Mis Materias</span>
        </a>
        <a href="horario.php" class="nav-item <?php echo $pagina_activa == 'horario' ? 'active' : ''; ?>">
            <i class="bi bi-calendar-week"></i>
            <span>Mi Horario</span>
        </a>
        <?php if ($periodo): ?>
        <a href="matricular.php" class="nav-item <?php echo $pagina_activa == 'matricular' ? 'active' : ''; ?>">
            <i class="bi bi-pencil-square"></i>
            <span>Matricularme</span>
        </a>
        <?php endif; ?>
        <a href="encuesta.php" class="nav-item <?php echo $pagina_activa == 'encuesta' ? 'active' : ''; ?>">
            <i class="bi bi-clipboard-check"></i>
            <span>Encuesta</span>
        </a>
        
        <div class="logout">
            <a href="../auth/logout.php" class="nav-item">
                <i class="bi bi-box-arrow-right"></i>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </nav>
</aside>