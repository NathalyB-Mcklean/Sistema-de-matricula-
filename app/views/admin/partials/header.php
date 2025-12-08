<?php
// app/views/admin/layout/header.php
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Sistema de Matrícula</title>
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <nav class="sidebar">
            <div class="logo">
                <i class="fas fa-graduation-cap"></i>
                <h2>Admin Panel</h2>
            </div>
            
            <ul class="menu">
                <li class="<?php echo ($_GET['action'] ?? '') === 'dashboard' ? 'active' : ''; ?>">
                    <a href="index.php?page=admin&action=dashboard">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="<?php echo ($_GET['action'] ?? '') === 'usuarios' ? 'active' : ''; ?>">
                    <a href="index.php?page=admin&action=usuarios">
                        <i class="fas fa-users"></i> Usuarios
                    </a>
                </li>
                <li class="<?php echo ($_GET['action'] ?? '') === 'carreras' ? 'active' : ''; ?>">
                    <a href="index.php?page=admin&action=carreras">
                        <i class="fas fa-university"></i> Carreras
                    </a>
                </li>
                <li class="<?php echo ($_GET['action'] ?? '') === 'materias' ? 'active' : ''; ?>">
                    <a href="index.php?page=admin&action=materias">
                        <i class="fas fa-book"></i> Materias
                    </a>
                </li>
                <li class="<?php echo ($_GET['action'] ?? '') === 'horarios' ? 'active' : ''; ?>">
                    <a href="index.php?page=admin&action=horarios">
                        <i class="fas fa-clock"></i> Horarios
                    </a>
                </li>
                <li class="<?php echo ($_GET['action'] ?? '') === 'grupos' ? 'active' : ''; ?>">
                    <a href="index.php?page=admin&action=grupos">
                        <i class="fas fa-calendar-alt"></i> Grupos
                    </a>
                </li>
                <li class="<?php echo ($_GET['action'] ?? '') === 'matriculas' ? 'active' : ''; ?>">
                    <a href="index.php?page=admin&action=matriculas">
                        <i class="fas fa-file-signature"></i> Matrículas
                    </a>
                </li>
                <li class="<?php echo ($_GET['action'] ?? '') === 'encuestas' ? 'active' : ''; ?>">
                    <a href="index.php?page=admin&action=encuestas">
                        <i class="fas fa-poll"></i> Encuestas
                    </a>
                </li>
                <li class="<?php echo ($_GET['action'] ?? '') === 'auditoria' ? 'active' : ''; ?>">
                    <a href="index.php?page=admin&action=auditoria">
                        <i class="fas fa-history"></i> Auditoría
                    </a>
                </li>
                <li class="<?php echo ($_GET['action'] ?? '') === 'reportes' ? 'active' : ''; ?>">
                    <a href="index.php?page=admin&action=reportes">
                        <i class="fas fa-chart-bar"></i> Reportes
                    </a>
                </li>
            </ul>
            
            <div class="logout">
                <a href="index.php?page=logout" class="btn-logout">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </nav>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="topbar">
                <h1><?php echo $title ?? 'Administración'; ?></h1>
                <div class="user-info">
                    <span><?php echo htmlspecialchars(Session::get('user_name')); ?></span>
                    <i class="fas fa-user-circle"></i>
                </div>
            </header>
            
            <div class="content">
                <!-- Mostrar mensajes flash -->
                <?php if ($error = Session::getFlash('error')): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success = Session::getFlash('success')): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>