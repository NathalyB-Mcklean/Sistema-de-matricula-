<?php
// app/views/admin/partials/sidebar.php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar">
    <div class="logo">
        <h2>UTP Admin</h2>
        <small>Sistema de Matrícula</small>
    </div>
    
    <div class="user-info">
        <div class="avatar">
            <i class="bi bi-person-circle"></i>
        </div>
        <h3><?php echo $_SESSION['user_name']; ?></h3>
        <p>Administrador</p>
    </div>
    
    <nav class="nav-menu">
        <a href="dashboard.php" class="nav-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="estudiantes.php" class="nav-item <?php echo $current_page == 'estudiantes.php' ? 'active' : ''; ?>">
            <i class="bi bi-people"></i> Estudiantes
        </a>
        <a href="docentes.php" class="nav-item <?php echo $current_page == 'docentes.php' ? 'active' : ''; ?>">
            <i class="bi bi-person-video"></i> Docentes
        </a>
        <a href="materias.php" class="nav-item <?php echo $current_page == 'materias.php' ? 'active' : ''; ?>">
            <i class="bi bi-journal-text"></i> Materias
        </a>
        <a href="matriculas.php" class="nav-item <?php echo $current_page == 'matriculas.php' ? 'active' : ''; ?>">
            <i class="bi bi-pencil-square"></i> Matrículas
        </a>
        <a href="carreras.php" class="nav-item <?php echo $current_page == 'carreras.php' ? 'active' : ''; ?>">
            <i class="bi bi-mortarboard"></i> Carreras
        </a>
        <a href="periodos.php" class="nav-item <?php echo $current_page == 'periodos.php' ? 'active' : ''; ?>">
            <i class="bi bi-calendar-range"></i> Períodos
        </a>
        <a href="reportes.php" class="nav-item <?php echo $current_page == 'reportes.php' ? 'active' : ''; ?>">
            <i class="bi bi-graph-up"></i> Reportes
        </a>
        <a href="auditoria.php" class="nav-item <?php echo $current_page == 'auditoria.php' ? 'active' : ''; ?>">
            <i class="bi bi-clipboard-data"></i> Auditoría
        </a>
        
        <div class="logout">
            <a href="../auth/logout.php" class="nav-item">
                <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
            </a>
        </div>
    </nav>
</aside>