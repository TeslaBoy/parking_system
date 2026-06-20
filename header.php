<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Smart Parking'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body class="bg-light">

<?php if (isset($_SESSION['user_id'])): ?>
<!-- Сайдбар -->
<nav id="sidebar" class="sidebar">
    <div class="sidebar-brand">
        <i class="bi bi-p-circle-fill"></i>
        <span>Smart Parking</span>
    </div>

    <ul class="sidebar-nav">
        <?php if (($is_admin ?? false)): ?>
        <li class="sidebar-section">Адміністрування</li>
        <li class="sidebar-item">
            <a href="#" class="sidebar-link active" data-tab="admin-dash">
                <i class="bi bi-speedometer2"></i>
                <span>Дашборд</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="#" class="sidebar-link" data-tab="admin-parkings">
                <i class="bi bi-building"></i>
                <span>Паркінги</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="#" class="sidebar-link" data-tab="admin-vehicles">
                <i class="bi bi-car-front-fill"></i>
                <span>Транспорт</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="#" class="sidebar-link" data-tab="admin-bookings">
                <i class="bi bi-calendar-check-fill"></i>
                <span>Бронювання</span>
            </a>
        </li>
        <?php endif; ?>

        <?php if (!($is_admin ?? false)): ?>
        <li class="sidebar-section">Навігація</li>
        <li class="sidebar-item">
            <a href="#" class="sidebar-link active" data-tab="user-parkings">
                <i class="bi bi-geo-alt-fill"></i>
                <span>Паркінги</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="#" class="sidebar-link" data-tab="user-bookings">
                <i class="bi bi-ticket-detailed-fill"></i>
                <span>Мої бронювання</span>
            </a>
        </li>
        <li class="sidebar-item">
            <a href="#" class="sidebar-link" data-tab="user-vehicles">
                <i class="bi bi-car-front"></i>
                <span>Мій транспорт</span>
            </a>
        </li>
        <?php endif; ?>
    </ul>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <i class="bi bi-person-fill"></i>
            </div>
            <div class="user-details">
                <span class="user-name"><?php echo htmlspecialchars($user['username'] ?? 'Користувач'); ?></span>
                <span class="user-role"><?php echo ($is_admin ?? false) ? 'Адміністратор' : 'Користувач'; ?></span>
            </div>
            <a href="logout.php" class="logout-btn" title="Вийти">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>
</nav>

<!-- Мобільний topbar -->
<div class="mobile-topbar d-lg-none">
    <button id="sidebarToggle" class="sidebar-toggle">
        <i class="bi bi-list"></i>
    </button>
    <span class="mobile-brand">Smart Parking</span>
</div>

<!-- Основний контент -->
<main class="main-content">
<?php endif; ?>
<?php if (!isset($_SESSION['user_id'])): ?>
    <div class="auth-wrapper">
<?php else: ?>
    <div class="content-wrapper">
<?php endif; ?>
