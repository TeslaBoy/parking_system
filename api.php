<?php
session_start();
require_once 'config.php';
require_once 'classes/Uploader.php'; // Підключаємо наш новий клас

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

// Публічні дії (доступні без входу)
if ($action === 'register') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['error' => 'Користувач вже існує']);
        exit;
    }

    $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $username, $email, $password);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => $conn->error]);
    }
    exit;
}

// Перевірка авторизації для всіх інших дій
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Необхідна авторизація']);
    exit;
}

$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

switch ($action) {
    case 'add_parking':
        if (!$is_admin) {
            http_response_code(403);
            echo json_encode(['error' => 'Доступ заборонено']);
            exit;
        }
        $name = $_POST['name'];
        $address = $_POST['address'];
        $capacity = $_POST['capacity'];
        $available = $_POST['available'];
        $price_per_hour = $_POST['price_per_hour'] ?? 0;
        
        // Нове безпечне завантаження через клас
        $uploader = new Uploader();
        try {
            $image = $uploader->uploadImage('image');
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }

        $sql = "INSERT INTO parking_places (name, address, capacity, available, price_per_hour, image) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiids", $name, $address, $capacity, $available, $price_per_hour, $image);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
        } else {
            echo json_encode(['error' => $conn->error]);
        }
        break;

    case 'update_parking':
        if (!$is_admin) {
            http_response_code(403);
            echo json_encode(['error' => 'Доступ заборонено']);
            exit;
        }
        $id = $_POST['id'];
        $name = $_POST['name'];
        $address = $_POST['address'];
        $capacity = $_POST['capacity'];
        $available = $_POST['available'];
        $price_per_hour = $_POST['price_per_hour'] ?? 0;

        $sql = "UPDATE parking_places SET name = ?, address = ?, capacity = ?, available = ?, price_per_hour = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiidi", $name, $address, $capacity, $available, $price_per_hour, $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => $conn->error]);
        }
        break;

    case 'delete_parking':
        if (!$is_admin) {
            http_response_code(403);
            echo json_encode(['error' => 'Доступ заборонено']);
            exit;
        }
        $id = $_GET['id'];
        $sql = "DELETE FROM parking_places WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => $conn->error]);
        }
        break;

    case 'add_vehicle':
        $user_id = $_SESSION['user_id'];
        $license_plate = $_POST['license_plate'];
        $brand = $_POST['brand'];
        $model = $_POST['model'];
        $color = $_POST['color'];
        $parking_id = !empty($_POST['parking_id']) ? $_POST['parking_id'] : null;

        // Нове безпечне завантаження через клас
        $uploader = new Uploader();
        try {
            $image = $uploader->uploadImage('image');
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }

        $sql = "INSERT INTO vehicles (license_plate, brand, model, color, parking_id, image, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssisi", $license_plate, $brand, $model, $color, $parking_id, $image, $user_id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'id' => $stmt->insert_id]);
        } else {
            echo json_encode(['error' => $conn->error]);
        }
        break;

    case 'update_vehicle':
        $id = $_POST['id'];
        $license_plate = $_POST['license_plate'];
        $brand = $_POST['brand'];
        $model = $_POST['model'];
        $color = $_POST['color'];
        $parking_id = !empty($_POST['parking_id']) ? $_POST['parking_id'] : null;

        $sql = "UPDATE vehicles SET license_plate = ?, brand = ?, model = ?, color = ?, parking_id = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssii", $license_plate, $brand, $model, $color, $parking_id, $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => $conn->error]);
        }
        break;

    case 'delete_vehicle':
        $id = $_GET['id'];
        $sql = "DELETE FROM vehicles WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => $conn->error]);
        }
        break;

    case 'add_booking':
        $user_id = $_SESSION['user_id'];
        $parking_id = $_POST['parking_id'];
        $vehicle_id = $_POST['vehicle_id'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];

        $conn->begin_transaction();

        try {
            // Отримуємо дані паркінгу для перевірки місць та тарифу
            $check_sql = "SELECT available, capacity, price_per_hour FROM parking_places WHERE id = ? FOR UPDATE";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $parking_id);
            $check_stmt->execute();
            $res = $check_stmt->get_result();
            $parking = $res->fetch_assoc();

            if ($parking['available'] <= 0) {
                throw new Exception("На жаль, на цьому паркінгу немає вільних місць.");
            }

            // Розрахунок вартості
            $start_timestamp = strtotime($start_time);
            $end_timestamp = strtotime($end_time);
            
            if ($end_timestamp <= $start_timestamp) {
                throw new Exception("Час завершення має бути пізнішим за час початку.");
            }

            // Округлюємо кількість годин в більшу сторону
            $hours = ceil(($end_timestamp - $start_timestamp) / 3600);
            $total_price = $hours * $parking['price_per_hour'];

            // Зменшення кількості вільних місць
            $update_sql = "UPDATE parking_places SET available = available - 1 WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $parking_id);
            $update_stmt->execute();

            $sql = "INSERT INTO bookings (user_id, parking_id, vehicle_id, start_time, end_time, total_price, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiissd", $user_id, $parking_id, $vehicle_id, $start_time, $end_time, $total_price);
            $stmt->execute();

            $conn->commit();
            echo json_encode(['success' => true, 'id' => $stmt->insert_id, 'total_price' => $total_price]);

        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'update_booking':
        $id = $_POST['id'];
        $new_parking_id = $_POST['parking_id'];
        $vehicle_id = $_POST['vehicle_id'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];
        $status = $_POST['status'];

        $conn->begin_transaction();
        try {
            $get_old = $conn->prepare("SELECT parking_id, status FROM bookings WHERE id = ? FOR UPDATE");
            $get_old->bind_param("i", $id);
            $get_old->execute();
            $old = $get_old->get_result()->fetch_assoc();

            if ($old && $old['parking_id'] != $new_parking_id && ($old['status'] == 'active' || $old['status'] == 'pending')) {
                $conn->query("UPDATE parking_places SET available = available + 1 WHERE id = " . (int)$old['parking_id']);
                $conn->query("UPDATE parking_places SET available = available - 1 WHERE id = " . (int)$new_parking_id);
            }

            // Можна додати перерахунок ціни при оновленні, але поки залишаємо як є
            $sql = "UPDATE bookings SET parking_id = ?, vehicle_id = ?, start_time = ?, end_time = ?, status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iisssi", $new_parking_id, $vehicle_id, $start_time, $end_time, $status, $id);
            $stmt->execute();

            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'delete_booking':
        $id = $_GET['id'];
        
        $conn->begin_transaction();
        
        try {
            $get_sql = "SELECT parking_id, status FROM bookings WHERE id = ?";
            $get_stmt = $conn->prepare($get_sql);
            $get_stmt->bind_param("i", $id);
            $get_stmt->execute();
            $booking = $get_stmt->get_result()->fetch_assoc();

            $sql = "DELETE FROM bookings WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();

            if ($booking && ($booking['status'] == 'active' || $booking['status'] == 'pending')) {
                $update_sql = "UPDATE parking_places SET available = available + 1 WHERE id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("i", $booking['parking_id']);
                $update_stmt->execute();
            }

            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'get_parking':
        $id = $_GET['id'];
        $sql = "SELECT * FROM parking_places WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_assoc());
        break;

    case 'get_vehicle':
        $id = $_GET['id'];
        $sql = "SELECT * FROM vehicles WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_assoc());
        break;

    case 'get_booking':
        $id = $_GET['id'];
        $sql = "SELECT * FROM bookings WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode($stmt->get_result()->fetch_assoc());
        break;

    case 'change_booking_status':
        if (!$is_admin) {
            http_response_code(403);
            echo json_encode(['error' => 'Доступ заборонено']);
            exit;
        }
        $id = $_POST['id'];
        $status = $_POST['status'];

        $conn->begin_transaction();
        try {
            $check_sql = "SELECT status, parking_id FROM bookings WHERE id = ? FOR UPDATE";
            $check_stmt = $conn->prepare($check_sql);
            $check_stmt->bind_param("i", $id);
            $check_stmt->execute();
            $booking = $check_stmt->get_result()->fetch_assoc();

            if ($status == 'cancelled' && ($booking['status'] == 'active' || $booking['status'] == 'pending')) {
                $update_place = "UPDATE parking_places SET available = available + 1 WHERE id = ?";
                $u_stmt = $conn->prepare($update_place);
                $u_stmt->bind_param("i", $booking['parking_id']);
                $u_stmt->execute();
            }

            $sql = "UPDATE bookings SET status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $status, $id);
            $stmt->execute();

            $conn->commit();
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>