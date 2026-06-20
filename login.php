<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header('Location: index.php');
            exit;
        } else {
            $error = "Неправильний пароль";
        }
    } else {
        $error = "Користувача не знайдено";
    }
}

$page_title = "Авторизація";
include 'header.php';
?>

    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="bi bi-p-circle-fill"></i>
                </div>
                <h2 class="auth-title">Вхід до системи</h2>
                <p class="auth-subtitle">Увійдіть для управління парковками</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="alert alert-custom alert-danger-custom mb-4">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="auth-form">
                <div class="form-group-custom">
                    <label class="form-label-custom">Ім'я користувача</label>
                    <div class="input-group-custom">
                        <i class="bi bi-person input-icon"></i>
                        <input type="text" class="form-control-custom" name="username" placeholder="Введіть логін" required autofocus>
                    </div>
                </div>

                <div class="form-group-custom">
                    <label class="form-label-custom">Пароль</label>
                    <div class="input-group-custom">
                        <i class="bi bi-lock input-icon"></i>
                        <input type="password" class="form-control-custom" name="password" placeholder="Введіть пароль" required>
                    </div>
                </div>

                <button type="submit" class="btn-auth btn-auth-primary">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Увійти
                </button>
            </form>

            <div class="auth-footer">
                <p class="text-muted mb-0">
                    Ще немає акаунту? 
                    <button type="button" class="link-auth" data-bs-toggle="modal" data-bs-target="#registerModal">
                        Зареєструватися
                    </button>
                </p>
            </div>
        </div>
    </div>

    <!-- Модальне вікно реєстрації -->
    <div class="modal fade" id="registerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-xl rounded-4">
                <div class="modal-header gradient-primary border-0 py-3">
                    <h5 class="modal-title fw-bold text-white"><i class="bi bi-person-plus me-2"></i>Реєстрація</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body py-4">
                    <form id="registerForm" method="POST" action="register.php">
                        <div class="form-section mb-3">
                            <label class="form-section-label">Ім'я користувача</label>
                            <div class="form-floating-custom">
                                <input type="text" class="form-control form-control-lg" name="username" required placeholder="username">
                                <i class="bi bi-person form-floating-icon"></i>
                            </div>
                        </div>
                        <div class="form-section mb-3">
                            <label class="form-section-label">Email</label>
                            <div class="form-floating-custom">
                                <input type="email" class="form-control form-control-lg" name="email" required placeholder="email@example.com">
                                <i class="bi bi-envelope form-floating-icon"></i>
                            </div>
                        </div>
                        <div class="form-section">
                            <label class="form-section-label">Пароль</label>
                            <div class="form-floating-custom">
                                <input type="password" class="form-control form-control-lg" name="password" required placeholder="Мінімум 6 символів" minlength="6">
                                <i class="bi bi-lock form-floating-icon"></i>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0 bg-light rounded-bottom-4 px-4 py-3">
                    <button type="button" class="btn btn-lg btn-cancel" data-bs-dismiss="modal">Скасувати</button>
                    <button type="submit" class="btn btn-lg btn-confirm" form="registerForm">
                        <i class="bi bi-check-lg me-2"></i>Зареєструватися
                    </button>
                </div>
            </div>
        </div>
    </div>

<?php include 'footer.php'; ?>