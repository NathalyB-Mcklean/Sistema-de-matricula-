<?php
// app/views/admin/dashboard.php - VERSIÓN SIMPLE
?>
<div class="dashboard">
    <h1>Panel de Administración</h1>
    <p>Bienvenido, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Administrador'); ?></p>
    
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Estudiantes</h3>
            <p class="number"><?php echo $totalEstudiantes ?? 0; ?></p>
            <a href="index.php?page=admin&action=usuarios">Ver todos</a>
        </div>
        
        <div class="stat-card">
            <h3>Materias</h3>
            <p class="number"><?php echo $totalMaterias ?? 0; ?></p>
            <a href="index.php?page=admin&action=materias">Gestionar</a>
        </div>
        
        <div class="stat-card">
            <h3>Carreras</h3>
            <p class="number"><?php echo $totalCarreras ?? 0; ?></p>
            <a href="index.php?page=admin&action=carreras">Gestionar</a>
        </div>
        
        <div class="stat-card">
            <h3>Matrículas Hoy</h3>
            <p class="number"><?php echo $matriculasHoy ?? 0; ?></p>
            <a href="index.php?page=admin&action=matriculas">Ver todas</a>
        </div>
    </div>
    
    <?php if (isset($ultimasActividades) && !empty($ultimasActividades)): ?>
    <div class="row">
        <div class="col-md-6">
            <h3>Actividad Reciente</h3>
            <div class="activity-list">
                <?php foreach ($ultimasActividades as $actividad): ?>
                <div class="activity-item">
                    <strong><?php echo htmlspecialchars($actividad['usuario'] ?? 'Sistema'); ?></strong>
                    <?php echo htmlspecialchars($actividad['accion'] ?? ''); ?>
                    <small><?php echo isset($actividad['fecha']) ? date('H:i', strtotime($actividad['fecha'])) : ''; ?></small>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>