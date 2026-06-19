<?php
session_start();
require_once 'config.php';

// Перевірка авторизації
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Отримання ролі користувача
$user_sql = "SELECT role FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $_SESSION['user_id']);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
$is_admin = ($user['role'] == 'admin');

// Збір статистики для адміна
$stats = ['revenue' => 0, 'active_bookings' => 0];
$chart_labels = [];
$chart_data = [];

// Отримання даних для відображення
$parkings_sql = "SELECT * FROM parking_places";
$parkings_result = $conn->query($parkings_sql);

if ($is_admin) {
    $vehicles_sql = "SELECT v.*, p.name as parking_name FROM vehicles v LEFT JOIN parking_places p ON v.parking_id = p.id";
    $vehicles_result = $conn->query($vehicles_sql);
    
    // --- Збір даних для Дашборду ---
    $rev_sql = "SELECT SUM(total_price) as total FROM bookings WHERE status IN ('completed', 'active')";
    $stats['revenue'] = $conn->query($rev_sql)->fetch_assoc()['total'] ?? 0;

    $act_sql = "SELECT COUNT(*) as count FROM bookings WHERE status = 'active'";
    $stats['active_bookings'] = $conn->query($act_sql)->fetch_assoc()['count'] ?? 0;

    // Дані для графіка заповненості паркінгів
    $chart_res = $conn->query("SELECT name, capacity, available FROM parking_places");
    while($c = $chart_res->fetch_assoc()) {
        $chart_labels[] = $c['name'];
        $chart_data[] = $c['capacity'] - $c['available']; // Кількість зайнятих місць
    }
} else {
    $vehicles_sql = "SELECT v.*, p.name as parking_name FROM vehicles v LEFT JOIN parking_places p ON v.parking_id = p.id WHERE v.user_id = ?";
    $v_stmt = $conn->prepare($vehicles_sql);
    $v_stmt->bind_param("i", $_SESSION['user_id']);
    $v_stmt->execute();
    $vehicles_result = $v_stmt->get_result();
}

// Базовий запит для бронювань
$bookings_sql = "SELECT b.*, u.username, p.name as parking_name, v.license_plate FROM bookings b JOIN users u ON b.user_id = u.id JOIN parking_places p ON b.parking_id = p.id JOIN vehicles v ON b.vehicle_id = v.id";

if (!$is_admin) {
    $bookings_sql .= " WHERE b.user_id = ?";
    $booking_stmt = $conn->prepare($bookings_sql);
    $booking_stmt->bind_param("i", $_SESSION['user_id']);
    $booking_stmt->execute();
    $bookings_result = $booking_stmt->get_result();
} else {
    $bookings_result = $conn->query($bookings_sql);
}

$page_title = "Головна сторінка";
include 'header.php'; 
?>
    <div class="container mt-4">
        <?php if ($is_admin): ?>
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Панель керування</h2>
            </div>

            <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="dashboard-tab" data-bs-toggle="tab" data-bs-target="#dashboard" type="button">Дашборд</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="parkings-tab" data-bs-toggle="tab" data-bs-target="#parkings" type="button">Паркінги</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="vehicles-tab" data-bs-toggle="tab" data-bs-target="#vehicles" type="button">Транспорт</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="bookings-tab" data-bs-toggle="tab" data-bs-target="#bookings" type="button">Всі бронювання</button>
                </li>
            </ul>

            <div class="tab-content" id="adminTabsContent">
                
                <div class="tab-pane fade show active" id="dashboard">
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <div class="card text-white bg-success h-100 shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="bi bi-cash-stack"></i> Загальний дохід</h5>
                                    <h2 class="display-6"><?php echo number_format($stats['revenue'], 2); ?> ₴</h2>
                                    <p class="card-text small">З активних та завершених бронювань</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card text-white bg-primary h-100 shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="bi bi-car-front-fill"></i> Активні паркування</h5>
                                    <h2 class="display-6"><?php echo $stats['active_bookings']; ?></h2>
                                    <p class="card-text small">Автомобілів зараз на стоянках</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="mb-0">Завантаженість паркінгів (Зайняті місця)</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="occupancyChart" style="max-height: 300px;"></canvas>
                        </div>
                    </div>
                    
                    <script>
                        window.chartLabels = <?php echo json_encode($chart_labels); ?>;
                        window.chartData = <?php echo json_encode($chart_data); ?>;
                    </script>
                </div>

                <div class="tab-pane fade" id="parkings">
                    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addParkingModal">
                        <i class="bi bi-plus-circle"></i> Додати паркінг
                    </button>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Назва</th>
                                    <th>Адреса</th>
                                    <th>Тариф</th>
                                    <th>Заповненість</th>
                                    <th>Дії</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $parkings_result->data_seek(0); while($row = $parkings_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['address']); ?></td>
                                    <td class="fw-bold"><?php echo number_format($row['price_per_hour'], 2); ?> ₴/год</td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <?php 
                                                $percent = 100 - (($row['available'] / $row['capacity']) * 100); 
                                                $color = $percent > 90 ? 'danger' : ($percent > 50 ? 'warning' : 'success');
                                            ?>
                                            <div class="progress-bar bg-<?php echo $color; ?>" role="progressbar" style="width: <?php echo $percent; ?>%">
                                                <?php echo (int)$row['capacity'] - (int)$row['available']; ?>/<?php echo (int)$row['capacity']; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <button class="btn btn-warning btn-sm btn-edit-parking" data-id="<?php echo $row['id']; ?>">Ред.</button>
                                        <button class="btn btn-danger btn-sm btn-delete-parking" data-id="<?php echo $row['id']; ?>">Вид.</button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="vehicles">
                    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addVehicleModal">Додати транспорт</button>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>Номер</th>
                                    <th>Авто</th>
                                    <th>Колір</th>
                                    <th>Локація</th>
                                    <th>Дії</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $vehicles_result->data_seek(0); while($row = $vehicles_result->fetch_assoc()): ?>
                                <tr>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['license_plate']); ?></span></td>
                                    <td><?php echo htmlspecialchars($row['brand']) . ' ' . htmlspecialchars($row['model']); ?></td>
                                    <td><?php echo htmlspecialchars($row['color']); ?></td>
                                    <td><?php echo $row['parking_name'] ? htmlspecialchars($row['parking_name']) : '<span class="text-muted">Немає</span>'; ?></td>
                                    <td>
                                        <button class="btn btn-warning btn-sm btn-edit-vehicle" data-id="<?php echo $row['id']; ?>">Ред.</button>
                                        <button class="btn btn-danger btn-sm btn-delete-vehicle" data-id="<?php echo $row['id']; ?>">Вид.</button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="bookings">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Користувач</th>
                                    <th>Паркінг</th>
                                    <th>Авто</th>
                                    <th>Період</th>
                                    <th>Сума</th>
                                    <th>Статус</th>
                                    <th>Дії</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $bookings_result->data_seek(0); while($row = $bookings_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo htmlspecialchars($row['parking_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['license_plate']); ?></td>
                                    <td>
                                        <small><?php echo date('d.m H:i', strtotime($row['start_time'])); ?> - <br>
                                        <?php echo date('d.m H:i', strtotime($row['end_time'])); ?></small>
                                    </td>
                                    <td class="text-success fw-bold"><?php echo number_format($row['total_price'], 2); ?> ₴</td>
                                    <td>
                                        <span class="badge bg-<?php echo $row['status'] == 'active' ? 'success' : ($row['status'] == 'pending' ? 'warning' : ($row['status'] == 'completed' ? 'secondary' : 'danger')); ?>">
                                            <?php echo $row['status'] == 'pending' ? 'Очікує' : $row['status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($row['status'] == 'pending'): ?>
                                            <button class="btn btn-success btn-sm btn-confirm-booking" data-id="<?php echo $row['id']; ?>" title="Підтвердити">
                                                <i class="bi bi-check-lg">OK</i>
                                            </button>
                                            <button class="btn btn-danger btn-sm btn-reject-booking" data-id="<?php echo $row['id']; ?>" title="Відхилити">
                                                <i class="bi bi-x-lg">X</i>
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-warning btn-sm btn-edit-booking" data-id="<?php echo $row['id']; ?>">Ред.</button>
                                            <button class="btn btn-danger btn-sm btn-delete-booking" data-id="<?php echo $row['id']; ?>">Вид.</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <div class="row">
            <div class="col-md-4 mb-4">
                <div class="d-grid gap-2 mb-4">
                    <button class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#addBookingModal">
                        Забронювати місце
                    </button>
                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addVehicleModal">
                        Додати моє авто
                    </button>
                </div>

                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Мої бронювання</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($bookings_result->num_rows > 0): ?>
                            <div id="bookingsList" class="list-group list-group-flush">
                                <?php while($row = $bookings_result->fetch_assoc()): ?>
                                    <div class="list-group-item px-0">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1"><?php echo htmlspecialchars($row['parking_name']); ?></h6>
                                            <small class="badge bg-<?php echo $row['status'] == 'active' ? 'success' : ($row['status'] == 'pending' ? 'warning' : 'secondary'); ?>">
                                                <?php echo $row['status'] == 'pending' ? 'Очікує підтвердження' : $row['status']; ?>
                                            </small>
                                        </div>
                                        <p class="mb-1 small">Авто: <span class="badge bg-secondary"><?php echo htmlspecialchars($row['license_plate']); ?></span></p>
                                        <small class="text-muted d-block mb-2">
                                            <i class="bi bi-clock"></i> <?php echo date('d.m.Y H:i', strtotime($row['start_time'])); ?> — 
                                            <?php echo date('d.m.Y H:i', strtotime($row['end_time'])); ?>
                                        </small>
                                        <div class="fw-bold text-success mb-2">
                                            До сплати: <?php echo number_format($row['total_price'], 2); ?> ₴
                                        </div>
                                        <?php if($row['status'] == 'active' || $row['status'] == 'pending'): ?>
                                            <button class="btn btn-outline-danger btn-sm btn-delete-booking w-100" data-id="<?php echo (int)$row['id']; ?>">Скасувати</button>
                                        <?php endif; ?>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center py-3">У вас поки немає бронювань</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <h4 class="mb-3">Доступні паркінги</h4>
                <div class="row row-cols-1 row-cols-md-2 g-4">
                    <?php $parkings_result->data_seek(0); while($row = $parkings_result->fetch_assoc()): ?>
                        <div class="col">
                            <div class="card h-100 shadow-sm parking-card">
                                <?php if($row['image']): ?>
                                    <img src="<?php echo htmlspecialchars($row['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($row['name']); ?>" style="height: 150px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-secondary text-white d-flex align-items-center justify-content-center" style="height: 150px;">
                                        <span class="fs-1">P</span>
                                    </div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($row['name']); ?></h5>
                                    <p class="card-text text-muted small mb-1"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($row['address']); ?></p>
                                    <p class="card-text fw-bold text-primary mb-2"><?php echo number_format($row['price_per_hour'], 2); ?> ₴ / год</p>
                                    
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <span class="badge bg-<?php echo $row['available'] > 0 ? 'success' : 'danger'; ?> rounded-pill px-3 py-2">
                                            Вільних місць: <?php echo (int)$row['available']; ?>
                                        </span>
                                        <?php if ($row['available'] > 0): ?>
                                            <button class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#addBookingModal" onclick="document.querySelector('#bookingParkingId').value='<?php echo $row['id']; ?>'; document.getElementById('bookingParkingId').dispatchEvent(new Event('change'));">
                                                Забронювати
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-secondary btn-sm px-3" disabled>Зайнято</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <?php include 'modals.php'; ?>
<?php include 'footer.php'; ?>