-- Database Setup for Online Food Shop
CREATE DATABASE IF NOT EXISTS food_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE food_shop;

-- Admin table
CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(255) DEFAULT 'Admin',
    avatar LONGBLOB,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert default admin
INSERT INTO admin (email, password, name) VALUES 
('67319010077@swdtcmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Admin');
-- Password is '1234' hashed with bcrypt

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image LONGBLOB,
    category VARCHAR(100),
    is_available TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    location_link TEXT,
    payment_method ENUM('cash', 'transfer') DEFAULT 'cash',
    payment_slip LONGBLOB,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'confirmed', 'cooking', 'delivering', 'completed', 'cancelled') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Order Items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Sample products
INSERT INTO products (name, description, price, category, image) VALUES
('ข้าวผัดกระเพรา', 'ข้าวผัดกระเพราหมูสับไข่ดาว', 50.00, 'อาหารจานเดียว', LOAD_FILE('c:/xampp1/htdocs/1412/uploads/1768292119_1.jpg')),
('ผัดไทย', 'ผัดไทยกุ้งสด', 60.00, 'อาหารจานเดียว', LOAD_FILE('c:/xampp1/htdocs/1412/uploads/1768292155_2.jpg')),
('ต้มยำกุ้ง', 'ต้มยำกุ้งน้ำข้น', 80.00, 'อาหารตามสั่ง', LOAD_FILE('c:/xampp1/htdocs/1412/uploads/1768292190_3.jpg')),
('ส้มตำ', 'ส้มตำไทย', 40.00, 'อาหารตามสั่ง', LOAD_FILE('c:/xampp1/htdocs/1412/uploads/1768292223_4.jpg')),
('แกงเขียวหวาน', 'แกงเขียวหวานไก่', 70.00, 'อาหารตามสั่ง', LOAD_FILE('c:/xampp1/htdocs/1412/uploads/1768292270_5.jpg')),
('ข้าวมันไก่', 'ข้าวมันไก่ตอน', 45.00, 'อาหารจานเดียว', LOAD_FILE('c:/xampp1/htdocs/1412/uploads/1768292359_6.jpg'));
