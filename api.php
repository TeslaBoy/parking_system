<?php
session_start();
require_once 'config.php';
require_once 'classes/Uploader.php'; 

// Утиліта для синхронізації вільних місць
function sync_parking_availability($conn, $parking_id = null) {
    $sql = "UPDATE parking_places p SET available = capacity - (
        SELECT COUNT(*) FROM bookings b 
        WHERE b.parking_id = p.id AND b.status IN ('pending', 'active') 
        AND b.start_time <= NOW() AND b.end_time > NOW()
    )";
    if ($parking_id) $sql .= " WHERE p.id = " . (int)$parking_id;
    $conn->query($sql);
}

$action = $_GET['action'] ?? '';

if ($action !== 'export_bookings') {
    header('Content-Type: application/json');
}

if ($action === 'register') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        echo json_encode(['error' => 'Користувач вже існує']); exit;
    }

    $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $username, $email, $password);
    
    if ($stmt->execute()) echo json_encode(['success' => true]); 
    else echo json_encode(['error' => $conn->error]);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    if ($action === 'export_bookings') die('Необхідна авторизація');
    http_response_code(401);
    echo json_encode(['error' => 'Необхідна авторизація']); exit;
}

$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

switch ($action) {
    case 'add_parking':
        if (!$is_admin) { http_response_code(403); echo json_encode(['error' => 'Доступ заборонено']); exit; }
        $name = $_POST['name']; $address = $_POST['address']; $capacity = $_POST['capacity'];
        $available = $_POST['available']; $price_per_hour = $_POST['price_per_hour'] ?? 0;
        
        $uploader = new Uploader();
        try { $image = $uploader->uploadImage('image'); } catch (Exception $e) { echo json_encode(['error' => $e->getMessage()]); exit; }

        $stmt = $conn->prepare("INSERT INTO parking_places (name, address, capacity, available, price_per_hour, image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiids", $name, $address, $capacity, $available, $price_per_hour, $image);
        if ($stmt->execute()) echo json_encode(['success' => true, 'id' => $stmt->insert_id]); else echo json_encode(['error' => $conn->error]);
        break;

    case 'update_parking':
        if (!$is_admin) { http_response_code(403); echo json_encode(['error' => 'Доступ заборонено']); exit; }
        $id = $_POST['id']; $name = $_POST['name']; $address = $_POST['address'];
        $capacity = $_POST['capacity']; $available = $_POST['available']; $price_per_hour = $_POST['price_per_hour'] ?? 0;

        $stmt = $conn->prepare("UPDATE parking_places SET name = ?, address = ?, capacity = ?, available = ?, price_per_hour = ? WHERE id = ?");
        $stmt->bind_param("ssiidi", $name, $address, $capacity, $available, $price_per_hour, $id);
        if ($stmt->execute()) echo json_encode(['success' => true]); else echo json_encode(['error' => $conn->error]);
        break;

    case 'delete_parking':
        if (!$is_admin) { http_response_code(403); echo json_encode(['error' => 'Доступ заборонено']); exit; }
        $stmt = $conn->prepare("DELETE FROM parking_places WHERE id = ?");
        $stmt->bind_param("i", $_GET['id']);
        if ($stmt->execute()) echo json_encode(['success' => true]); else echo json_encode(['error' => $conn->error]);
        break;

    case 'add_vehicle':
        $user_id = $_SESSION['user_id'];
        $license_plate = $_POST['license_plate']; $brand = $_POST['brand']; $model = $_POST['model']; $color = $_POST['color'];
        $parking_id = !empty($_POST['parking_id']) ? $_POST['parking_id'] : null;

        $uploader = new Uploader();
        try { $image = $uploader->uploadImage('image'); } catch (Exception $e) { echo json_encode(['error' => $e->getMessage()]); exit; }

        $stmt = $conn->prepare("INSERT INTO vehicles (license_plate, brand, model, color, parking_id, image, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssisi", $license_plate, $brand, $model, $color, $parking_id, $image, $user_id);
        if ($stmt->execute()) echo json_encode(['success' => true, 'id' => $stmt->insert_id]); else echo json_encode(['error' => $conn->error]);
        break;

    case 'update_vehicle':
        $id = $_POST['id']; $license_plate = $_POST['license_plate']; $brand = $_POST['brand']; $model = $_POST['model']; $color = $_POST['color'];
        $parking_id = !empty($_POST['parking_id']) ? $_POST['parking_id'] : null;

        $stmt = $conn->prepare("UPDATE vehicles SET license_plate = ?, brand = ?, model = ?, color = ?, parking_id = ? WHERE id = ?");
        $stmt->bind_param("ssssii", $license_plate, $brand, $model, $color, $parking_id, $id);
        if ($stmt->execute()) echo json_encode(['success' => true]); else echo json_encode(['error' => $conn->error]);
        break;

    case 'delete_vehicle':
        $stmt = $conn->prepare("DELETE FROM vehicles WHERE id = ?");
        $stmt->bind_param("i", $_GET['id']);
        if ($stmt->execute()) echo json_encode(['success' => true]); else echo json_encode(['error' => $conn->error]);
        break;

    case 'add_booking':
        $user_id = $_SESSION['user_id'];
        $parking_id = $_POST['parking_id'];
        $vehicle_id = $_POST['vehicle_id'];
        $start_time = $_POST['start_time'];
        $end_time = $_POST['end_time'];

        $conn->begin_transaction();
        try {
            $start_timestamp = strtotime($start_time);
            $end_timestamp = strtotime($end_time);
            
            if ($start_timestamp % 1800 !== 0 || $end_timestamp % 1800 !== 0) throw new Exception("Час має бути кратним 30 хвилинам.");
            if ($end_timestamp <= $start_timestamp) throw new Exception("Час виїзду має бути пізнішим за час заїзду.");
            if (($end_timestamp - $start_timestamp) < 1800) throw new Exception("Мінімальний час паркування становить 30 хвилин.");

            // Перевірка авто
            $veh_stmt = $conn->prepare("SELECT p.name FROM bookings b JOIN parking_places p ON b.parking_id = p.id WHERE b.vehicle_id = ? AND b.status IN ('pending', 'active') AND b.start_time < ? AND b.end_time > ?");
            $veh_stmt->bind_param("iss", $vehicle_id, $end_time, $start_time);
            $veh_stmt->execute();
            $veh_res = $veh_stmt->get_result();
            if ($veh_res->num_rows > 0) throw new Exception("Авто вже заброньовано на цей час (Локація: " . $veh_res->fetch_assoc()['name'] . ").");

            // Перевірка місткості
            $park_check = $conn->prepare("SELECT capacity, price_per_hour FROM parking_places WHERE id = ? FOR UPDATE");
            $park_check->bind_param("i", $parking_id);
            $park_check->execute();
            $parking = $park_check->get_result()->fetch_assoc();

            $cap_stmt = $conn->prepare("SELECT COUNT(*) as concurrent FROM bookings WHERE parking_id = ? AND status IN ('pending', 'active') AND start_time < ? AND end_time > ?");
            $cap_stmt->bind_param("iss", $parking_id, $end_time, $start_time);
            $cap_stmt->execute();
            if ($cap_stmt->get_result()->fetch_assoc()['concurrent'] >= $parking['capacity']) {
                throw new Exception("На цей час паркінг повністю заповнений.");
            }

            $total_price = (($end_timestamp - $start_timestamp) / 3600) * $parking['price_per_hour'];

            $stmt = $conn->prepare("INSERT INTO bookings (user_id, parking_id, vehicle_id, start_time, end_time, total_price, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param("iiissd", $user_id, $parking_id, $vehicle_id, $start_time, $end_time, $total_price);
            $stmt->execute();
            $new_id = $stmt->insert_id;

            $conn->commit();
            sync_parking_availability($conn, $parking_id);
            echo json_encode(['success' => true, 'id' => $new_id, 'total_price' => $total_price]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'update_booking':
        $id = $_POST['id']; $new_parking_id = $_POST['parking_id']; $vehicle_id = $_POST['vehicle_id'];
        $start_time = $_POST['start_time']; $end_time = $_POST['end_time']; $status = $_POST['status'];

        $conn->begin_transaction();
        try {
            $start_timestamp = strtotime($start_time);
            $end_timestamp = strtotime($end_time);
            
            if ($start_timestamp % 1800 !== 0 || $end_timestamp % 1800 !== 0) throw new Exception("Час має бути кратним 30 хвилинам.");
            if (($end_timestamp - $start_timestamp) < 1800) throw new Exception("Мінімум 30 хвилин.");

            $veh_stmt = $conn->prepare("SELECT id FROM bookings WHERE vehicle_id = ? AND id != ? AND status IN ('pending', 'active') AND start_time < ? AND end_time > ?");
            $veh_stmt->bind_param("iiss", $vehicle_id, $id, $end_time, $start_time);
            $veh_stmt->execute();
            if ($veh_stmt->get_result()->num_rows > 0) throw new Exception("Авто вже заброньовано деінде на цей час.");

            $cap_stmt = $conn->prepare("SELECT COUNT(*) as concurrent FROM bookings WHERE parking_id = ? AND id != ? AND status IN ('pending', 'active') AND start_time < ? AND end_time > ?");
            $cap_stmt->bind_param("iiss", $new_parking_id, $id, $end_time, $start_time);
            $cap_stmt->execute();
            $concurrent = $cap_stmt->get_result()->fetch_assoc()['concurrent'];

            $p_sql = $conn->prepare("SELECT capacity, price_per_hour FROM parking_places WHERE id = ? FOR UPDATE");
            $p_sql->bind_param("i", $new_parking_id);
            $p_sql->execute();
            $parking = $p_sql->get_result()->fetch_assoc();

            if ($concurrent >= $parking['capacity'] && in_array($status, ['pending', 'active'])) {
                throw new Exception("На цей час паркінг повністю заповнений.");
            }

            $total_price = (($end_timestamp - $start_timestamp) / 3600) * $parking['price_per_hour'];

            $get_old = $conn->prepare("SELECT parking_id FROM bookings WHERE id = ?");
            $get_old->bind_param("i", $id); $get_old->execute();
            $old_parking_id = $get_old->get_result()->fetch_assoc()['parking_id'];

            $stmt = $conn->prepare("UPDATE bookings SET parking_id = ?, vehicle_id = ?, start_time = ?, end_time = ?, total_price = ?, status = ? WHERE id = ?");
            $stmt->bind_param("iissdsi", $new_parking_id, $vehicle_id, $start_time, $end_time, $total_price, $status, $id);
            $stmt->execute();

            $conn->commit();
            sync_parking_availability($conn, $old_parking_id);
            if($old_parking_id != $new_parking_id) sync_parking_availability($conn, $new_parking_id);
            
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'delete_booking':
        $id = $_GET['id'];
        $get_stmt = $conn->prepare("SELECT parking_id FROM bookings WHERE id = ?");
        $get_stmt->bind_param("i", $id); $get_stmt->execute();
        $pid = $get_stmt->get_result()->fetch_assoc()['parking_id'];

        $stmt = $conn->prepare("DELETE FROM bookings WHERE id = ?");
        $stmt->bind_param("i", $id); $stmt->execute();
        sync_parking_availability($conn, $pid);
        echo json_encode(['success' => true]);
        break;

    case 'change_booking_status':
        if (!$is_admin) { http_response_code(403); echo json_encode(['error' => 'Доступ заборонено']); exit; }
        $id = $_POST['id']; $status = $_POST['status'];

        $get_stmt = $conn->prepare("SELECT parking_id FROM bookings WHERE id = ?");
        $get_stmt->bind_param("i", $id); $get_stmt->execute();
        $pid = $get_stmt->get_result()->fetch_assoc()['parking_id'];

        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id); $stmt->execute();
        
        sync_parking_availability($conn, $pid);
        echo json_encode(['success' => true]);
        break;

    case 'get_parking':
        $stmt = $conn->prepare("SELECT * FROM parking_places WHERE id = ?");
        $stmt->bind_param("i", $_GET['id']); $stmt->execute(); echo json_encode($stmt->get_result()->fetch_assoc()); break;
    case 'get_vehicle':
        $stmt = $conn->prepare("SELECT * FROM vehicles WHERE id = ?");
        $stmt->bind_param("i", $_GET['id']); $stmt->execute(); echo json_encode($stmt->get_result()->fetch_assoc()); break;
    case 'get_booking':
        $stmt = $conn->prepare("SELECT * FROM bookings WHERE id = ?");
        $stmt->bind_param("i", $_GET['id']); $stmt->execute(); echo json_encode($stmt->get_result()->fetch_assoc()); break;

    case 'export_bookings':
        if (!$is_admin) { http_response_code(403); exit("Доступ заборонено"); }
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=bookings_report_' . date('Y-m-d') . '.csv');
        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF");
        fputcsv($output, ['ID', 'Користувач', 'Паркінг', 'Номер Авто', 'Початок', 'Кінець', 'Сума (грн)', 'Статус'], ';');
        $sql = "SELECT b.id, u.username, p.name as parking_name, v.license_plate, b.start_time, b.end_time, b.total_price, b.status 
                FROM bookings b JOIN users u ON b.user_id = u.id JOIN parking_places p ON b.parking_id = p.id JOIN vehicles v ON b.vehicle_id = v.id ORDER BY b.id DESC";
        $res = $conn->query($sql);
        while ($row = $res->fetch_assoc()) fputcsv($output, [$row['id'], $row['username'], $row['parking_name'], $row['license_plate'], $row['start_time'], $row['end_time'], $row['total_price'], $row['status']], ';');
        fclose($output); exit;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}
?>