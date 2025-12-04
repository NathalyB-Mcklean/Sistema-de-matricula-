<?php
// app/views/admin/dashboard.php
?>
<div class="dashboard">
    <h1>Panel de Administración</h1>
    <p>Bienvenido, <?php echo htmlspecialchars(Session::get('user_name')); ?></p>
    
    <div class="stats-grid">
        <div class="stat-card">
            <h3>Estudiantes</h3>
            <p class="number"><?php echo $totalEstudiantes; ?></p>
            <a href="index.php?page=admin&action=usuarios">Ver todos</a>
        </div>
        
        <div class="stat-card">
            <h3>Materias</h3>
            <p class="number"><?php echo $totalMaterias; ?></p>
            <a href="index.php?page=admin&action=materias">Gestionar</a>
        </div>
        
        <div class="stat-card">
            <h3>Carreras</h3>
            <p class="number"><?php echo $totalCarreras; ?></p>
            <a href="index.php?page=admin&action=carreras">Gestionar</a>
        </div>
        
        <div class="stat-card">
            <h3>Matrículas Hoy</h3>
            <p class="number"><?php echo $matriculasHoy; ?></p>
            <a href="index.php?page=admin&action=matriculas">Ver todas</a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <h3>Actividad Reciente</h3>
            <div class="activity-list">
                <?php foreach ($ultimasActividades as $actividad): ?>
                <div class="activity-item">
                    <strong><?php echo htmlspecialchars($actividad['usuario']); ?></strong>
                    <?php echo htmlspecialchars($actividad['accion']); ?>
                    <small><?php echo date('H:i', strtotime($actividad['fecha'])); ?></small>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="col-md-6">
            <h3>Materias más Populares</h3>
            <div class="popular-list">
                <?php foreach ($materiasPopulares as $materia): ?>
                <div class="popular-item">
                    <?php echo htmlspecialchars($materia['nombre']); ?>
                    <span class="badge"><?php echo $materia['total_matriculas']; ?> inscritos</span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>