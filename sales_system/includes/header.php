<?php
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . getBaseUrl() . 'login.php');
    exit();
}

// Initialize notification handler
$notification = Notification::getInstance();
$unreadCount = $notification->getUnreadCount();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Sistema de Vendas'; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo getBaseUrl(); ?>assets/css/notifications.css" rel="stylesheet">
    
    <style>
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
        }
        
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        
        main {
            padding-top: 48px;
        }
        
        .navbar-brand {
            padding-top: .75rem;
            padding-bottom: .75rem;
            font-size: 1rem;
            background-color: rgba(0, 0, 0, .25);
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .25);
        }
    </style>
</head>
<body>
    <header class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="<?php echo getBaseUrl(); ?>">
            Sistema de Vendas
        </a>
        
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" 
                data-bs-toggle="collapse" data-bs-target="#sidebarMenu" 
                aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="w-100"></div>
        
        <div class="navbar-nav">
            <!-- Notifications Dropdown -->
            <div class="nav-item dropdown notification-dropdown">
                <a class="nav-link px-3 position-relative" href="#" id="notificationsDropdown" 
                   role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell"></i>
                    <?php if ($unreadCount > 0): ?>
                        <span class="notification-badge" id="notification-count">
                            <?php echo $unreadCount > 99 ? '99+' : $unreadCount; ?>
                        </span>
                    <?php endif; ?>
                </a>
                
                <div class="dropdown-menu dropdown-menu-end notifications-container" 
                     aria-labelledby="notificationsDropdown">
                    <div class="notification-header">
                        <h6 class="mb-0">Notificações</h6>
                        <?php if ($unreadCount > 0): ?>
                            <button class="btn btn-sm btn-link" onclick="markAllRead()">
                                Marcar todas como lidas
                            </button>
                        <?php endif; ?>
                    </div>
                    
                    <div id="notification-list">
                        <?php
                        $notifications = $notification->getUnread(null, 10);
                        if (empty($notifications)):
                        ?>
                            <div class="notifications-empty">
                                <i class="fas fa-bell-slash"></i>
                                <p class="mb-0">Nenhuma notificação</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($notifications as $n): ?>
                                <div class="notification-item" data-id="<?php echo $n['id']; ?>">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <?php
                                            $icon = 'info-circle';
                                            switch ($n['type']) {
                                                case 'warning':
                                                    $icon = 'exclamation-triangle';
                                                    break;
                                                case 'success':
                                                    $icon = 'check-circle';
                                                    break;
                                                case 'error':
                                                    $icon = 'times-circle';
                                                    break;
                                            }
                                            ?>
                                            <i class="fas fa-<?php echo $icon; ?> text-<?php echo $n['type']; ?>"></i>
                                            <?php echo h($n['message']); ?>
                                            
                                            <?php if ($n['link']): ?>
                                                <a href="<?php echo h($n['link']); ?>">Ver mais</a>
                                            <?php endif; ?>
                                            
                                            <div class="notification-time">
                                                <?php echo formatDate($n['created_at'], true); ?>
                                            </div>
                                        </div>
                                        <div class="ms-2">
                                            <button type="button" class="btn btn-sm btn-link mark-read" 
                                                    onclick="markRead(<?php echo $n['id']; ?>)">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="notification-footer">
                        <a href="<?php echo getBaseUrl(); ?>notifications.php">Ver todas</a>
                    </div>
                </div>
            </div>
            
            <!-- User Dropdown -->
            <div class="nav-item dropdown">
                <a class="nav-link px-3 dropdown-toggle" href="#" id="userDropdown" 
                   role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user"></i>
                    <?php echo h($_SESSION['username']); ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li>
                        <a class="dropdown-item" href="<?php echo getBaseUrl(); ?>profile.php">
                            <i class="fas fa-user-circle"></i> Meu Perfil
                        </a>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <a class="dropdown-item" href="<?php echo getBaseUrl(); ?>logout.php">
                            <i class="fas fa-sign-out-alt"></i> Sair
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Toast Container -->
    <div id="toast-container"></div>

    <!-- Navigation -->
    <div class="container-fluid">
        <div class="row">
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" 
                               href="<?php echo getBaseUrl(); ?>">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/sales/pos.php') !== false ? 'active' : ''; ?>" 
                               href="<?php echo getBaseUrl(); ?>modules/sales/pos.php">
                                <i class="fas fa-cash-register"></i> PDV
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/financial/') !== false ? 'active' : ''; ?>" 
                               href="<?php echo getBaseUrl(); ?>modules/financial/dashboard.php">
                                <i class="fas fa-chart-line"></i> Financeiro
                            </a>
                        </li>
                        
                        <?php if (checkAccess(['admin', 'super'])): ?>
                            <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                                <span>Administração</span>
                            </h6>
                            
                            <?php if (checkAccess('admin')): ?>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/users/') !== false ? 'active' : ''; ?>" 
                                       href="<?php echo getBaseUrl(); ?>modules/users/">
                                        <i class="fas fa-users"></i> Usuários
                                    </a>
                                </li>
                                
                                <li class="nav-item">
                                    <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/settings/') !== false ? 'active' : ''; ?>" 
                                       href="<?php echo getBaseUrl(); ?>modules/settings/">
                                        <i class="fas fa-cog"></i> Configurações
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <li class="nav-item">
                                <a class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/reports/') !== false ? 'active' : ''; ?>" 
                                   href="<?php echo getBaseUrl(); ?>modules/reports/">
                                    <i class="fas fa-file-alt"></i> Relatórios
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </nav>
        </div>
    </div>
