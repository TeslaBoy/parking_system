<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Parking System'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <?php if (isset($_SESSION['user_id'])): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php"><?php echo ($is_admin ?? false) ? 'Admin Panel' : 'Smart Parking'; ?></a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">Вітаємо, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a class="nav-link" href="logout.php">Вихід</a>
            </div>
        </div>
    </nav>
    <?php endif; ?>