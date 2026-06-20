<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$conn->begin_transaction();
try {
    $conn->query("UPDATE bookings SET status = 'completed' WHERE status = 'active' AND end_time < NOW()");
    $conn->query("UPDATE parking_places p SET available = capacity - (
        SELECT COUNT(*) FROM bookings b 
        WHERE b.parking_id = p.id AND b.status IN ('pending', 'active') 
        AND b.start_time <= NOW() AND b.end_time > NOW()
    )");
    $conn->commit();
} catch (Exception $e) { $conn->rollback(); }

// === ЛОГІКА ФІЛЬТРАЦІЇ ЧАСУ ===
$check_time = isset($_GET['time']) ? $_GET['time'] : date('Y-m-d H:i');
// Перетворюємо у безпечний SQL формат
$check_time_sql = date('Y-m-d H:i:s', strtotime($check_time));
// ===============================

$user_stmt = $conn->prepare("SELECT username, role FROM users WHERE id = ?");
$user_stmt->bind_param("i", $_SESSION['user_id']); $user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();
$is_admin = ($user['role'] == 'admin');

$stats = ['revenue' => 0, 'active_bookings' => 0, 'total_vehicles' => 0, 'total_capacity' => 0, 'total_available' => 0, 'occupancy_rate' => 0, 'completed_today' => 0];
$chart_labels = []; $chart_data = []; $chart_colors = [];

// Динамічний підрахунок вільних місць саме на обраний час!
$parkings_sql = "SELECT p.*, 
    (p.capacity - (SELECT COUNT(*) FROM bookings b WHERE b.parking_id = p.id AND b.status IN ('pending', 'active') AND b.start_time <= '$check_time_sql' AND b.end_time > '$check_time_sql')) as dynamic_available 
    FROM parking_places p ORDER BY p.id DESC";
$parkings_result = $conn->query($parkings_sql);

if ($is_admin) {
    $vehicles_result = $conn->query("SELECT v.*, p.name as parking_name FROM vehicles v LEFT JOIN parking_places p ON v.parking_id = p.id ORDER BY v.id DESC");
    $stats['revenue'] = $conn->query("SELECT SUM(total_price) as total FROM bookings WHERE status IN ('completed','active')")->fetch_assoc()['total'] ?? 0;
    $stats['active_bookings'] = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'active'")->fetch_assoc()['count'] ?? 0;
    $stats['total_vehicles'] = $conn->query("SELECT COUNT(*) as count FROM vehicles")->fetch_assoc()['count'] ?? 0;
    
    $cap = $conn->query("SELECT SUM(capacity) as total FROM parking_places")->fetch_assoc();
    $stats['total_capacity'] = $cap['total'] ?? 0; 
    
    $avail_sql = "SELECT SUM(capacity - (SELECT COUNT(*) FROM bookings b WHERE b.parking_id = p.id AND b.status IN ('pending', 'active') AND b.start_time <= '$check_time_sql' AND b.end_time > '$check_time_sql')) as avail FROM parking_places p";
    $stats['total_available'] = $conn->query($avail_sql)->fetch_assoc()['avail'] ?? 0;
    
    $stats['occupancy_rate'] = $stats['total_capacity'] > 0 ? round((($stats['total_capacity'] - $stats['total_available']) / $stats['total_capacity']) * 100) : 0;
    $stats['completed_today'] = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE DATE(created_at) = CURDATE() AND status != 'cancelled'")->fetch_assoc()['count'] ?? 0;

    $chart_res = $conn->query("SELECT name, capacity, 
        (capacity - (SELECT COUNT(*) FROM bookings b WHERE b.parking_id = p.id AND b.status IN ('pending', 'active') AND b.start_time <= '$check_time_sql' AND b.end_time > '$check_time_sql')) as dynamic_available 
        FROM parking_places p ORDER BY id ASC");
    while($c = $chart_res->fetch_assoc()) {
        $chart_labels[] = $c['name']; $chart_data[] = (int)$c['capacity'] - (int)$c['dynamic_available'];
        $occ = $c['capacity'] > 0 ? ((int)$c['capacity'] - (int)$c['dynamic_available']) / $c['capacity'] : 0;
        $chart_colors[] = $occ > 0.9 ? 'rgba(220,53,69,0.7)' : ($occ > 0.5 ? 'rgba(255,193,7,0.7)' : 'rgba(25,135,84,0.7)');
    }
    $bookings_result = $conn->query("SELECT b.*, u.username, p.name as parking_name, v.license_plate FROM bookings b JOIN users u ON b.user_id = u.id JOIN parking_places p ON b.parking_id = p.id JOIN vehicles v ON b.vehicle_id = v.id ORDER BY b.start_time DESC LIMIT 10");
} else {
    $v_stmt = $conn->prepare("SELECT v.*, p.name as parking_name FROM vehicles v LEFT JOIN parking_places p ON v.parking_id = p.id WHERE v.user_id = ? ORDER BY v.id DESC");
    $v_stmt->bind_param("i", $_SESSION['user_id']); $v_stmt->execute(); $vehicles_result = $v_stmt->get_result();

    $b_stmt = $conn->prepare("SELECT b.*, u.username, p.name as parking_name, p.address as parking_address, v.license_plate, v.brand, v.model, v.color FROM bookings b JOIN users u ON b.user_id = u.id JOIN parking_places p ON b.parking_id = p.id JOIN vehicles v ON b.vehicle_id = v.id WHERE b.user_id = ? ORDER BY b.start_time DESC");
    $b_stmt->bind_param("i", $_SESSION['user_id']); $b_stmt->execute(); $bookings_result = $b_stmt->get_result();
}

$page_title = $is_admin ? "Панель адміністратора" : "Особистий кабінет";
include 'header.php';
?>

<?php if ($is_admin): ?>
<section class="dashboard-section">
    <div class="page-header">
        <div class="header-content">
            <div class="header-text"><h1 class="page-title"><i class="bi bi-speedometer2"></i> Панель адміністратора</h1><p class="page-subtitle">Огляд системи управління паркінгом</p></div>
            <div class="header-actions"><a href="api.php?action=export_bookings" class="btn btn-export"><i class="bi bi-file-earmark-excel"></i> Експорт звіту</a></div>
        </div>
    </div>

    <ul class="nav nav-tabs nav-custom mb-4 d-none" id="adminTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active fw-semibold" data-bs-toggle="tab" data-bs-target="#admin-dash" type="button">Дашборд</button></li>
        <li class="nav-item"><button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#admin-parkings" type="button">Паркінги</button></li>
        <li class="nav-item"><button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#admin-vehicles" type="button">Транспорт</button></li>
        <li class="nav-item"><button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#admin-bookings" type="button">Бронювання</button></li>
    </ul>

    <div class="tab-content" id="adminTabsContent">
        <div class="tab-pane fade show active" id="admin-dash">
            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-md-6"><div class="stat-card stat-success"><div class="stat-icon"><i class="bi bi-currency-uah"></i></div><div class="stat-content"><span class="stat-label">Дохід</span><span class="stat-value"><?php echo number_format($stats['revenue'],0); ?> ₴</span></div></div></div>
                <div class="col-xl-3 col-md-6"><div class="stat-card stat-primary"><div class="stat-icon"><i class="bi bi-car-front-fill"></i></div><div class="stat-content"><span class="stat-label">Активні паркування</span><span class="stat-value"><?php echo $stats['active_bookings']; ?></span></div></div></div>
                <div class="col-xl-3 col-md-6"><div class="stat-card stat-info"><div class="stat-icon"><i class="bi bi-car"></i></div><div class="stat-content"><span class="stat-label">Транспорту</span><span class="stat-value"><?php echo $stats['total_vehicles']; ?></span></div></div></div>
                <div class="col-xl-3 col-md-6"><div class="stat-card stat-warning"><div class="stat-icon"><i class="bi bi-building"></i></div><div class="stat-content"><span class="stat-label">Заповненість (Обраний час)</span><span class="stat-value"><?php echo $stats['occupancy_rate']; ?>%</span></div></div></div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-12">
                    <div class="card chart-card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 py-3 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
                            <h5 class="card-title mb-0"><i class="bi bi-bar-chart-fill text-primary me-2"></i>Завантаженість паркінгів</h5>
                            <div class="d-flex align-items-center bg-light p-1 px-2 rounded border">
                                <label class="me-2 small text-muted fw-bold mb-0 text-nowrap"><i class="bi bi-clock-history"></i> Станом на:</label>
                                <input type="datetime-local" class="form-control form-control-sm border-0 bg-transparent time-filter-input text-primary fw-bold p-0" style="outline: none; box-shadow: none;" value="<?php echo date('Y-m-d\TH:i', strtotime($check_time)); ?>">
                            </div>
                        </div>
                        <div class="card-body"><div class="chart-container" style="height:320px;"><canvas id="occupancyChart"></canvas></div></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="admin-parkings">
            <div class="card management-card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Управління паркінгами</h5>
                        <button class="btn btn-add" data-bs-toggle="modal" data-bs-target="#addParkingModal"><i class="bi bi-plus-lg"></i> Додати</button>
                    </div>
                </div>
                <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0" id="parkingsTable"><thead class="table-light"><tr><th class="ps-4">Паркінг</th><th>Адреса</th><th>Тариф</th><th>Місця (на обраний час)</th><th>Заповненість</th><th class="text-end pe-4">Дії</th></tr></thead><tbody>
                    <?php $parkings_result->data_seek(0); while($row = $parkings_result->fetch_assoc()):
                        // ВИКОРИСТОВУЄМО dynamic_available ЗАМІСТЬ available
                        $pct = $row['capacity'] > 0 ? round((($row['capacity'] - $row['dynamic_available']) / $row['capacity']) * 100) : 0;
                        $bc = $pct > 90 ? 'danger' : ($pct > 50 ? 'warning' : 'success');
                    ?>
                    <tr>
                        <td class="ps-4"><div class="fw-semibold"><?php echo htmlspecialchars($row['name']); ?></div></td>
                        <td class="text-muted small"><?php echo htmlspecialchars($row['address']); ?></td>
                        <td class="fw-semibold text-primary"><?php echo number_format($row['price_per_hour'],2); ?> ₴/год</td>
                        <td><span class="badge bg-<?php echo $bc; ?>"><?php echo (int)$row['dynamic_available']; ?> / <?php echo (int)$row['capacity']; ?></span></td>
                        <td><div class="progress thin-progress"><div class="progress-bar bg-<?php echo $bc; ?>" style="width:<?php echo $pct; ?>%"></div></div></td>
                        <td class="text-end pe-4"><button class="btn btn-icon btn-edit-parking" data-id="<?php echo $row['id']; ?>"><i class="bi bi-pencil-fill"></i></button><button class="btn btn-icon btn-delete btn-delete-parking" data-id="<?php echo $row['id']; ?>"><i class="bi bi-trash-fill"></i></button></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody></table></div></div>
            </div>
        </div>

        <div class="tab-pane fade" id="admin-vehicles">
            <div class="card management-card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3"><h5 class="card-title mb-0">Транспортні засоби</h5></div>
                <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0" id="vehiclesTable"><thead class="table-light"><tr><th class="ps-4">Номер</th><th>Марка</th><th>Колір</th><th>Локація</th><th class="text-end pe-4">Дії</th></tr></thead><tbody>
                    <?php $vehicles_result->data_seek(0); while($row = $vehicles_result->fetch_assoc()): ?>
                    <tr>
                        <td class="ps-4"><span class="badge bg-dark"><?php echo htmlspecialchars($row['license_plate']); ?></span></td>
                        <td class="fw-semibold"><?php echo htmlspecialchars($row['brand'].' '.$row['model']); ?></td>
                        <td><?php echo htmlspecialchars($row['color']); ?></td>
                        <td><?php echo $row['parking_name'] ? '<span class="badge bg-info text-dark">'.htmlspecialchars($row['parking_name']).'</span>' : '<span class="text-muted">Немає</span>'; ?></td>
                        <td class="text-end pe-4"><button class="btn btn-icon btn-edit-vehicle" data-id="<?php echo $row['id']; ?>"><i class="bi bi-pencil-fill"></i></button><button class="btn btn-icon btn-delete btn-delete-vehicle" data-id="<?php echo $row['id']; ?>"><i class="bi bi-trash-fill"></i></button></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody></table></div></div>
            </div>
        </div>

        <div class="tab-pane fade" id="admin-bookings">
            <div class="card management-card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3"><h5 class="card-title mb-0">Всі бронювання</h5></div>
                <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0" id="bookingsTable"><thead class="table-light"><tr><th class="ps-4">Користувач</th><th>Авто</th><th>Паркінг</th><th>Час</th><th>Сума</th><th>Статус</th><th class="text-end pe-4">Дії</th></tr></thead><tbody>
                    <?php $all_b = $conn->query("SELECT b.*, u.username, p.name as parking_name, v.license_plate FROM bookings b JOIN users u ON b.user_id = u.id JOIN parking_places p ON b.parking_id = p.id JOIN vehicles v ON b.vehicle_id = v.id ORDER BY b.start_time DESC");
                    while($row = $all_b->fetch_assoc()): ?>
                    <tr>
                        <td class="ps-4"><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><span class="badge bg-dark"><?php echo htmlspecialchars($row['license_plate']); ?></span></td>
                        <td class="small"><?php echo htmlspecialchars($row['parking_name']); ?></td>
                        <td class="small"><?php echo date('d.m H:i', strtotime($row['start_time'])); ?> - <?php echo date('H:i', strtotime($row['end_time'])); ?></td>
                        <td class="fw-semibold text-success"><?php echo number_format($row['total_price'],2); ?> ₴</td>
                        <td><span class="badge bg-<?php echo $row['status'] == 'active' ? 'success' : ($row['status'] == 'pending' ? 'warning' : ($row['status'] == 'completed' ? 'secondary' : 'danger')); ?>"><?php echo $row['status']; ?></span></td>
                        <td class="text-end pe-4">
                            <?php if ($row['status'] == 'pending'): ?><button class="btn btn-icon btn-success btn-confirm-booking" data-id="<?php echo $row['id']; ?>"><i class="bi bi-check-lg"></i></button><button class="btn btn-icon btn-delete btn-reject-booking" data-id="<?php echo $row['id']; ?>"><i class="bi bi-x-lg"></i></button><?php else: ?><button class="btn btn-icon btn-edit btn-edit-booking" data-id="<?php echo $row['id']; ?>"><i class="bi bi-pencil-fill"></i></button><button class="btn btn-icon btn-delete btn-delete-booking" data-id="<?php echo $row['id']; ?>"><i class="bi bi-trash-fill"></i></button><?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody></table></div></div>
            </div>
        </div>
    </div>
</section>

<?php else: ?>
<section class="dashboard-section">
    <div class="user-welcome">
        <div class="welcome-content"><h1 class="welcome-title">Вітаємо, <?php echo htmlspecialchars($user['username']); ?>!</h1><p class="welcome-subtitle">Керуйте своїми бронюваннями та паркуваннями</p></div>
        <div class="welcome-actions"><button class="btn btn-add" data-bs-toggle="modal" data-bs-target="#addVehicleModal"><i class="bi bi-car-front-fill"></i> Додати авто</button></div>
    </div>

    <ul class="nav nav-tabs nav-custom mb-4 d-none" id="userTabs" role="tablist">
        <li class="nav-item"><button class="nav-link active fw-semibold" data-bs-toggle="tab" data-bs-target="#user-parkings" type="button">Доступні паркінги</button></li>
        <li class="nav-item"><button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#user-bookings" type="button">Мої бронювання</button></li>
        <li class="nav-item"><button class="nav-link fw-semibold" data-bs-toggle="tab" data-bs-target="#user-vehicles" type="button">Мій транспорт</button></li>
    </ul>

    <div class="tab-content" id="userTabsContent">
        <div class="tab-pane fade show active" id="user-parkings">
            
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-4 gap-3">
                <h5 class="mb-0 text-secondary fw-bold"><i class="bi bi-geo-alt-fill me-2"></i>Оберіть локацію</h5>
                <div class="d-flex align-items-center bg-white p-2 rounded-3 shadow-sm border border-primary border-opacity-25">
                    <label class="me-2 small text-primary fw-bold mb-0 text-nowrap"><i class="bi bi-clock-history"></i> Перевірити заповненість на:</label>
                    <input type="datetime-local" class="form-control form-control-sm border-0 bg-transparent time-filter-input text-dark fw-bold p-0" style="outline: none; box-shadow: none;" value="<?php echo date('Y-m-d\TH:i', strtotime($check_time)); ?>">
                </div>
            </div>

            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
                <?php $parkings_result->data_seek(0); while($row = $parkings_result->fetch_assoc()):
                    // ВИКОРИСТОВУЄМО dynamic_available
                    $pct = $row['capacity'] > 0 ? round((($row['capacity'] - $row['dynamic_available']) / $row['capacity']) * 100) : 0;
                    $bc = $pct > 90 ? 'danger' : ($pct > 50 ? 'warning' : 'success');
                    $isFull = (int)$row['dynamic_available'] <= 0;
                ?>
                <div class="col">
                    <div class="parking-card-modern">
                        <div class="parking-card-image">
                            <?php if(!empty($row['image'])): ?><img src="<?php echo htmlspecialchars($row['image']); ?>" alt=""><?php else: ?><div class="parking-card-placeholder"><i class="bi bi-p-square"></i></div><?php endif; ?>
                            <div class="parking-card-badge"><span class="badge bg-<?php echo $bc; ?> bg-opacity-90 text-white"><?php echo (int)$row['dynamic_available']; ?> місць вільно</span></div>
                        </div>
                        <div class="parking-card-body">
                            <h5 class="parking-card-title"><?php echo htmlspecialchars($row['name']); ?></h5>
                            <p class="parking-card-address"><i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($row['address']); ?></p>
                            <div class="parking-card-price"><?php echo number_format($row['price_per_hour'],2); ?> <span>₴/год</span></div>
                            <div class="parking-card-progress">
                                <div class="d-flex justify-content-between small mb-1"><span>Зайнятість</span><span class="fw-semibold text-<?php echo $bc; ?>"><?php echo $pct; ?>%</span></div>
                                <div class="progress thin-progress"><div class="progress-bar bg-<?php echo $bc; ?>" style="width:<?php echo $pct; ?>%"></div></div>
                            </div>
                            <div class="parking-card-actions">
                                <?php if ($isFull): ?>
                                    <button class="btn btn-full w-100" disabled><i class="bi bi-x-circle me-1"></i>На обраний час місць немає</button>
                                <?php else: ?>
                                    <button class="btn btn-book w-100 btn-prepare-booking" data-bs-toggle="modal" data-bs-target="#addBookingModal" data-id="<?php echo $row['id']; ?>" data-name="<?php echo htmlspecialchars($row['name']); ?>" data-price="<?php echo $row['price_per_hour']; ?>"><i class="bi bi-calendar-check me-1"></i>Забронювати</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="tab-pane fade" id="user-bookings">
            <div class="card management-card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3"><h5 class="card-title mb-0"><i class="bi bi-ticket-detailed-fill me-2 text-primary"></i>Історія бронювань</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="userBookingsTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4">Паркінг</th>
                                    <th>Адреса</th>
                                    <th>Транспорт</th>
                                    <th>Період</th>
                                    <th>Сума</th>
                                    <th>Статус</th>
                                    <th class="text-end pe-4">Дії</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($bookings_result->num_rows > 0): $bookings_result->data_seek(0); while($row = $bookings_result->fetch_assoc()): ?>
                                <tr>
                                    <td class="ps-4"><div class="fw-semibold"><?php echo htmlspecialchars($row['parking_name']); ?></div></td>
                                    <td class="small text-muted"><?php echo htmlspecialchars($row['parking_address']); ?></td>
                                    <td><span class="badge bg-dark"><?php echo htmlspecialchars($row['license_plate']); ?></span><div class="small text-muted mt-1"><?php echo htmlspecialchars($row['brand'].' '.$row['model']); ?></div></td>
                                    <td><div class="small"><i class="bi bi-calendar-event me-1 text-muted"></i><?php echo date('d.m.Y', strtotime($row['start_time'])); ?></div><div class="small"><i class="bi bi-clock me-1 text-muted"></i><?php echo date('H:i', strtotime($row['start_time'])); ?> — <?php echo date('H:i', strtotime($row['end_time'])); ?></div></td>
                                    <td class="fw-semibold text-success"><?php echo number_format($row['total_price'],2); ?> ₴</td>
                                    <td><span class="badge bg-<?php echo $row['status'] == 'active' ? 'success' : ($row['status'] == 'pending' ? 'warning' : ($row['status'] == 'completed' ? 'secondary' : 'danger')); ?>"><?php echo $row['status'] == 'pending' ? 'Очікує' : ($row['status'] == 'active' ? 'Активне' : ($row['status'] == 'completed' ? 'Завершено' : 'Скасовано')); ?></span></td>
                                    <td class="text-end pe-4"><?php if($row['status'] == 'active' || $row['status'] == 'pending'): ?><button class="btn btn-cancel btn-sm btn-delete-booking" data-id="<?php echo (int)$row['id']; ?>"><i class="bi bi-x-circle me-1"></i>Скасувати</button><?php else: ?><span class="text-muted"><i class="bi bi-check-circle-fill"></i></span><?php endif; ?></td>
                                </tr>
                                <?php endwhile; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="user-vehicles">
            <div class="card management-card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><i class="bi bi-car-front me-2 text-primary"></i>Мій транспорт</h5>
                        <button class="btn btn-add btn-sm" data-bs-toggle="modal" data-bs-target="#addVehicleModal"><i class="bi bi-plus-lg"></i> Додати</button>
                    </div>
                </div>
                <div class="card-body p-0"><div class="table-responsive"><table class="table table-hover align-middle mb-0" id="userVehiclesTable"><thead class="table-light"><tr><th class="ps-4">Номер / Марка</th><th>Колір</th><th>Локація</th><th class="text-end pe-4">Дії</th></tr></thead><tbody>
                    <?php if ($vehicles_result->num_rows > 0): $vehicles_result->data_seek(0); while($row = $vehicles_result->fetch_assoc()): ?>
                    <tr>
                        <td class="ps-4"><div><div class="fw-semibold"><?php echo htmlspecialchars($row['brand'].' '.$row['model']); ?></div><span class="badge bg-dark mt-1"><?php echo htmlspecialchars($row['license_plate']); ?></span></div></td>
                        <td><?php echo htmlspecialchars($row['color']); ?></td>
                        <td><?php if($row['parking_name']): ?><span class="badge bg-info text-dark"><i class="bi bi-geo-alt-fill me-1"></i><?php echo htmlspecialchars($row['parking_name']); ?></span><?php else: ?><span class="text-muted small">Гараж / Не припарковано</span><?php endif; ?></td>
                        <td class="text-end pe-4"><button class="btn btn-icon btn-edit btn-edit-vehicle" data-id="<?php echo $row['id']; ?>"><i class="bi bi-pencil-fill"></i></button><button class="btn btn-icon btn-delete btn-delete-vehicle" data-id="<?php echo $row['id']; ?>"><i class="bi bi-trash-fill"></i></button></td>
                    </tr>
                    <?php endwhile; endif; ?>
                </tbody></table></div></div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
window.chartLabels = <?php echo json_encode($chart_labels); ?>;
window.chartData = <?php echo json_encode($chart_data); ?>;
window.chartColors = <?php echo json_encode($chart_colors); ?>;
</script>
<?php include 'modals.php'; ?>
<?php include 'footer.php'; ?>