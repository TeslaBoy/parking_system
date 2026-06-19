<?php
$servername = "localhost";
$username = "root";
$password = "";

// Створення підключення
$conn = new mysqli($servername, $username, $password);

// Перевірка підключення
if ($conn->connect_error) {
    die("Помилка підключення: " . $conn->connect_error);
}

$dbname = "parking_system";
$conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
$conn->select_db($dbname);

// Створення таблиці користувачів
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(30) NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(50),
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql);

// Створення початкових користувачів
$check_users = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$roles = [];
while($row = $check_users->fetch_assoc()) $roles[$row['role']] = $row['count'];

if (!isset($roles['admin'])) {
    $admin_username = 'admin';
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users (username, password, role) VALUES ('$admin_username', '$admin_password', 'admin')");
}

if (!isset($roles['user'])) {
    $test_user = 'user';
    $test_pass = password_hash('user123', PASSWORD_DEFAULT);
    $conn->query("INSERT INTO users (username, password, email, role) VALUES ('$test_user', '$test_pass', 'user@example.com', 'user')");
}

// Створення таблиці паркінгів (ДОДАНО price_per_hour)
$sql = "CREATE TABLE IF NOT EXISTS parking_places (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    address VARCHAR(200) NOT NULL,
    capacity INT(4) NOT NULL,
    available INT(4) NOT NULL,
    price_per_hour DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    image VARCHAR(200),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$conn->query($sql);

// Додавання початкових парковок
$check_parkings = $conn->query("SELECT COUNT(*) as count FROM parking_places");
$parkings_count = $check_parkings->fetch_assoc()['count'];
if ($parkings_count == 0) {
    $sql_initial_parkings = "INSERT INTO parking_places (name, address, capacity, available, price_per_hour, image) VALUES 
        ('Центральний паркінг', 'м. Кам\'янське, вул. Будівельників, 1', 50, 50, 20.00, 'images/central.jpg'),
        ('Парковка біля ЦУМу', 'м. Кам\'янське, пр. Свободи, 35', 30, 30, 30.00, 'images/tsum.jpeg'),
        ('Паркінг Залізничний', 'м. Кам\'янське, вул. Залізнична, 10', 20, 20, 15.00, 'images/station.jpg')";
    $conn->query($sql_initial_parkings);
}

// Створення таблиці транспорту
$sql = "CREATE TABLE IF NOT EXISTS vehicles (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    license_plate VARCHAR(20) NOT NULL,
    brand VARCHAR(50) NOT NULL,
    model VARCHAR(50) NOT NULL,
    color VARCHAR(30) NOT NULL,
    user_id INT(6) UNSIGNED,
    parking_id INT(6) UNSIGNED,
    image VARCHAR(200),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parking_id) REFERENCES parking_places(id)
)";
$conn->query($sql);

// Додавання початкових авто для користувача
$check_vehicles = $conn->query("SELECT COUNT(*) as count FROM vehicles");
if ($check_vehicles->fetch_assoc()['count'] == 0) {
    $user_res = $conn->query("SELECT id FROM users WHERE username = 'user' LIMIT 1");
    if ($user_res->num_rows > 0) {
        $u_id = $user_res->fetch_assoc()['id'];
        $sql_initial_vehicles = "INSERT INTO vehicles (license_plate, brand, model, color, user_id, image) VALUES 
            ('AE 1234 BT', 'Tesla', 'Model 3', 'Червоний', $u_id, 'images/tesla.jpg'),
            ('AE 5678 CP', 'BMW', 'X5', 'Чорний', $u_id, 'images/bmw.jpg')";
        $conn->query($sql_initial_vehicles);
    }
}

// Створення таблиці бронювань (Додано ON DELETE CASCADE)
$sql = "CREATE TABLE IF NOT EXISTS bookings (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT(6) UNSIGNED,
    parking_id INT(6) UNSIGNED,
    vehicle_id INT(6) UNSIGNED,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    status ENUM('pending', 'active', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parking_id) REFERENCES parking_places(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
)";
$conn->query($sql);
?>