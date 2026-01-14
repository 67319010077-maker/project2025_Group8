-- =====================================================
-- ร้านอาหารตามสั่งออนไลน์ - Database Setup
-- =====================================================
-- รันไฟล์นี้ใน phpMyAdmin หรือ MySQL CLI
-- =====================================================

-- สร้างฐานข้อมูล
CREATE DATABASE IF NOT EXISTS food_shop 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE food_shop;

-- =====================================================
-- ตารางแอดมิน (Admin)
-- =====================================================
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) DEFAULT 'แอดมิน',
    avatar VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- เพิ่มแอดมินเริ่มต้น (password: 1234)
INSERT INTO admin (email, password, name) VALUES 
('67319010077@swdtcmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'แอดมินร้าน')
ON DUPLICATE KEY UPDATE email = email;

-- =====================================================
-- ตารางลูกค้า (Customers)
-- =====================================================
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    -- ข้อมูลที่อยู่
    address TEXT,
    province VARCHAR(100),
    district VARCHAR(100),
    subdistrict VARCHAR(100),
    postal_code VARCHAR(10),
    -- พิกัด GPS
    latitude DECIMAL(10, 8) DEFAULT NULL,
    longitude DECIMAL(11, 8) DEFAULT NULL,
    location_link TEXT,
    -- สถานะ
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================================================
-- ตารางหมวดหมู่อาหาร (Categories)
-- =====================================================
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- เพิ่มหมวดหมู่ตัวอย่าง
INSERT INTO categories (name, description, sort_order) VALUES 
('อาหารจานเดียว', 'อาหารจานเดียวพร้อมข้าว', 1),
('อาหารตามสั่ง', 'อาหารตามสั่งทั่วไป', 2),
('เครื่องดื่ม', 'น้ำดื่ม เครื่องดื่มเย็น ร้อน', 3),
('ของหวาน', 'ขนมหวาน ของทานเล่น', 4)
ON DUPLICATE KEY UPDATE name = name;

-- =====================================================
-- ตารางสินค้า/เมนูอาหาร (Products)
-- =====================================================
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT DEFAULT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    sale_price DECIMAL(10, 2) DEFAULT NULL,
    image VARCHAR(255),
    -- รูปภาพเพิ่มเติม (เก็บเป็น JSON)
    gallery TEXT,
    -- ข้อมูลเพิ่มเติม
    ingredients TEXT,
    calories INT DEFAULT NULL,
    spicy_level TINYINT(1) DEFAULT 0,
    -- สถานะ
    is_available TINYINT(1) DEFAULT 1,
    is_recommended TINYINT(1) DEFAULT 0,
    sort_order INT DEFAULT 0,
    -- สถิติ
    view_count INT DEFAULT 0,
    order_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- เพิ่มเมนูตัวอย่าง
INSERT INTO products (name, description, price, category_id, is_recommended) VALUES 
('ข้าวผัดกระเพรา', 'ข้าวผัดกระเพราหมูสับไข่ดาว หอมกระเพราสด', 50.00, 1, 1),
('ผัดไทย', 'ผัดไทยกุ้งสด เส้นจันท์', 60.00, 1, 1),
('ต้มยำกุ้ง', 'ต้มยำกุ้งน้ำข้น รสจัดจ้าน', 80.00, 2, 1),
('ส้มตำ', 'ส้มตำไทย รสชาติดั้งเดิม', 40.00, 2, 0),
('ข้าวมันไก่', 'ข้าวมันไก่ พร้อมน้ำจิ้มสูตรพิเศษ', 50.00, 1, 1),
('กะเพราหมูกรอบ', 'กะเพราหมูกรอบไข่ดาว', 60.00, 2, 0),
('ชาเย็น', 'ชาไทยเย็น หวานมัน', 25.00, 3, 0),
('น้ำมะนาว', 'น้ำมะนาวสด', 20.00, 3, 0)
ON DUPLICATE KEY UPDATE name = name;

-- =====================================================
-- ตารางคำสั่งซื้อ (Orders)
-- =====================================================
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) UNIQUE,
    customer_id INT DEFAULT NULL,
    -- ข้อมูลลูกค้า (เก็บซ้ำเผื่อลูกค้าแก้ไขข้อมูล)
    customer_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(255),
    -- ที่อยู่จัดส่ง
    address TEXT NOT NULL,
    province VARCHAR(100),
    district VARCHAR(100),
    subdistrict VARCHAR(100),
    postal_code VARCHAR(10),
    -- พิกัด GPS
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    location_link TEXT,
    delivery_instructions TEXT,
    -- การชำระเงิน
    payment_method ENUM('cash', 'transfer', 'promptpay', 'credit_card') DEFAULT 'cash',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    payment_slip VARCHAR(255),
    payment_date TIMESTAMP NULL,
    -- ราคา
    subtotal DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    delivery_fee DECIMAL(10, 2) DEFAULT 0.00,
    discount DECIMAL(10, 2) DEFAULT 0.00,
    total_amount DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    -- สถานะออเดอร์
    status ENUM('pending', 'confirmed', 'cooking', 'ready', 'delivering', 'completed', 'cancelled') DEFAULT 'pending',
    cancelled_reason TEXT,
    -- หมายเหตุ
    notes TEXT,
    admin_notes TEXT,
    -- เวลา
    confirmed_at TIMESTAMP NULL,
    cooking_at TIMESTAMP NULL,
    ready_at TIMESTAMP NULL,
    delivering_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    estimated_delivery TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- ตารางรายการในคำสั่งซื้อ (Order Items)
-- =====================================================
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    product_image VARCHAR(255),
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10, 2) NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    options TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- ตารางการชำระเงิน (Payments) - สำหรับบันทึกประวัติ
-- =====================================================
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    method ENUM('cash', 'transfer', 'promptpay', 'credit_card') NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    transaction_id VARCHAR(255),
    slip_image VARCHAR(255),
    bank_name VARCHAR(100),
    account_number VARCHAR(50),
    notes TEXT,
    verified_by INT DEFAULT NULL,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES admin(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- ตารางที่อยู่ลูกค้า (Customer Addresses) - หลายที่อยู่
-- =====================================================
CREATE TABLE IF NOT EXISTS customer_addresses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    label VARCHAR(100) DEFAULT 'บ้าน',
    recipient_name VARCHAR(255),
    phone VARCHAR(20),
    address TEXT NOT NULL,
    province VARCHAR(100),
    district VARCHAR(100),
    subdistrict VARCHAR(100),
    postal_code VARCHAR(10),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    location_link TEXT,
    is_default TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- ตารางรีวิว (Reviews)
-- =====================================================
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    customer_id INT NOT NULL,
    product_id INT DEFAULT NULL,
    rating TINYINT(1) NOT NULL DEFAULT 5,
    comment TEXT,
    images TEXT,
    is_approved TINYINT(1) DEFAULT 1,
    admin_reply TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- ตารางตั้งค่าร้าน (Settings)
-- =====================================================
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_type ENUM('text', 'number', 'boolean', 'json', 'image') DEFAULT 'text',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- เพิ่มการตั้งค่าเริ่มต้น
INSERT INTO settings (setting_key, setting_value, setting_type) VALUES 
('shop_name', 'ร้านอาหารตามสั่ง', 'text'),
('shop_phone', '02-123-4567', 'text'),
('shop_email', 'contact@foodshop.com', 'text'),
('shop_address', '123 ถนนสุขุมวิท กรุงเทพฯ 10110', 'text'),
('shop_line', '@foodshop', 'text'),
('shop_facebook', 'https://facebook.com/foodshop', 'text'),
('shop_latitude', '13.7563', 'text'),
('shop_longitude', '100.5018', 'text'),
('open_time', '10:00', 'text'),
('close_time', '21:00', 'text'),
('delivery_fee', '30', 'number'),
('min_order_amount', '100', 'number'),
('promptpay_number', '0812345678', 'text'),
('bank_name', 'ธนาคารกสิกรไทย', 'text'),
('bank_account', '123-4-56789-0', 'text'),
('bank_account_name', 'นายร้านอาหาร ตามสั่ง', 'text')
ON DUPLICATE KEY UPDATE setting_key = setting_key;

-- =====================================================
-- สร้าง Index สำหรับการค้นหาที่เร็วขึ้น
-- =====================================================
CREATE INDEX idx_products_category ON products(category_id);
CREATE INDEX idx_products_available ON products(is_available);
CREATE INDEX idx_orders_customer ON orders(customer_id);
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_date ON orders(created_at);
CREATE INDEX idx_order_items_order ON order_items(order_id);
CREATE INDEX idx_customer_addresses_customer ON customer_addresses(customer_id);

-- =====================================================
-- เสร็จสิ้น!
-- =====================================================
