<?php
// Database Setup Script - Run this once to create the database

$host = 'localhost';
$username = 'root';
$password = '';

echo "<h1>🍳 ติดตั้งฐานข้อมูลร้านอาหาร</h1>";

try {
    // Connect without database first
    $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS food_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p>✅ สร้างฐานข้อมูล food_shop สำเร็จ</p>";

    // Use the database
    $pdo->exec("USE food_shop");

    // Create admin table
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p>✅ สร้างตาราง admin สำเร็จ</p>";

    // Check if admin exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM admin");
    if ($stmt->fetchColumn() == 0) {
        // Insert default admin (password: 1234)
        $hashedPassword = password_hash('1234', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO admin (email, password) VALUES (?, ?)")
            ->execute(['67319010077@swdtcmail.com', $hashedPassword]);
        echo "<p>✅ สร้างบัญชีแอดมินสำเร็จ</p>";
    } else {
        echo "<p>ℹ️ บัญชีแอดมินมีอยู่แล้ว</p>";
    }

    // Create products table
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10, 2) NOT NULL,
        image VARCHAR(255),
        category VARCHAR(100),
        is_available TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "<p>✅ สร้างตาราง products สำเร็จ</p>";

    // Create orders table
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_name VARCHAR(255) NOT NULL,
        phone VARCHAR(20) NOT NULL,
        address TEXT NOT NULL,
        latitude DECIMAL(10, 8),
        longitude DECIMAL(11, 8),
        location_link TEXT,
        payment_method ENUM('cash', 'transfer') DEFAULT 'cash',
        payment_slip VARCHAR(255),
        total_amount DECIMAL(10, 2) NOT NULL,
        status ENUM('pending', 'confirmed', 'cooking', 'delivering', 'completed', 'cancelled') DEFAULT 'pending',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "<p>✅ สร้างตาราง orders สำเร็จ</p>";

    // Create order_items table
    $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        quantity INT NOT NULL DEFAULT 1,
        price DECIMAL(10, 2) NOT NULL,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )");
    echo "<p>✅ สร้างตาราง order_items สำเร็จ</p>";

    // Create customers table
    $pdo->exec("CREATE TABLE IF NOT EXISTS customers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL UNIQUE,
        phone VARCHAR(20),
        password VARCHAR(255) NOT NULL,
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p>✅ สร้างตาราง customers สำเร็จ</p>";

    // Update orders table to link with customers (optional)
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN customer_id INT NULL AFTER id");
    } catch (PDOException $e) {
        // Column might already exist
    }

    // Add sample products if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    if ($stmt->fetchColumn() == 0) {
        $sampleProducts = [
            ['ข้าวผัดกระเพรา', 'ข้าวผัดกระเพราหมูสับไข่ดาว', 50.00, 'อาหารจานเดียว'],
            ['ผัดไทย', 'ผัดไทยกุ้งสด', 60.00, 'อาหารจานเดียว'],
            ['ต้มยำกุ้ง', 'ต้มยำกุ้งน้ำข้น', 80.00, 'อาหารตามสั่ง'],
            ['ส้มตำ', 'ส้มตำไทย', 40.00, 'อาหารตามสั่ง'],
            ['ข้าวมันไก่', 'ข้าวมันไก่ พร้อมน้ำจิ้ม', 50.00, 'อาหารจานเดียว'],
            ['กะเพราหมูกรอบ', 'กะเพราหมูกรอบไข่ดาว', 60.00, 'อาหารตามสั่ง'],
        ];

        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, category) VALUES (?, ?, ?, ?)");
        foreach ($sampleProducts as $product) {
            $stmt->execute($product);
        }
        echo "<p>✅ เพิ่มเมนูตัวอย่างสำเร็จ</p>";
    }

    // Create uploads directory
    if (!file_exists('uploads')) {
        mkdir('uploads', 0777, true);
        echo "<p>✅ สร้างโฟลเดอร์ uploads สำเร็จ</p>";
    }
    if (!file_exists('uploads/slips')) {
        mkdir('uploads/slips', 0777, true);
        echo "<p>✅ สร้างโฟลเดอร์ uploads/slips สำเร็จ</p>";
    }

    echo "<hr>";
    echo "<h2>🎉 ติดตั้งสำเร็จ!</h2>";
    echo "<p><strong>Admin Login:</strong></p>";
    echo "<ul>";
    echo "<li>Email: <code>67319010077@swdtcmail.com</code></li>";
    echo "<li>Password: <code>1234</code></li>";
    echo "</ul>";
    echo "<p><a href='index.php' style='display:inline-block;padding:12px 24px;background:linear-gradient(135deg, #ec407a 0%, #d81b60 100%);color:white;text-decoration:none;border-radius:8px;'>🏠 ไปหน้าร้าน</a> ";
    echo "<a href='login.php' style='display:inline-block;padding:12px 24px;background:linear-gradient(135deg, #ec407a 0%, #d81b60 100%);color:white;text-decoration:none;border-radius:8px;margin-left:10px;'>🔐 เข้าสู่ระบบ</a></p>";

} catch (PDOException $e) {
    echo "<p style='color:red;'>❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "</p>";
    echo "<p>กรุณาตรวจสอบว่า:</p>";
    echo "<ul>";
    echo "<li>XAMPP MySQL กำลังทำงานอยู่</li>";
    echo "<li>ชื่อผู้ใช้และรหัสผ่านถูกต้อง (default: root / ไม่มีรหัส)</li>";
    echo "</ul>";
}
?>