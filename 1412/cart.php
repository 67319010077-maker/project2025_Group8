<?php
require_once 'db.php';

// Handle remove from cart
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $productId = (int) $_GET['remove'];
    if (isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
    }
    redirect('cart.php');
}

// Handle update quantity
if (isset($_POST['update_cart'])) {
    foreach ($_POST['quantities'] as $productId => $qty) {
        $qty = max(0, (int) $qty);
        if ($qty === 0) {
            unset($_SESSION['cart'][$productId]);
        } else {
            $_SESSION['cart'][$productId] = $qty;
        }
    }
    redirect('cart.php');
}

// Handle order submission
$orderError = '';
$orderSuccess = false;
if (isset($_POST['place_order'])) {
    $name = trim($_POST['customer_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $latitude = floatval($_POST['latitude'] ?? 0);
    $longitude = floatval($_POST['longitude'] ?? 0);
    $locationLink = trim($_POST['location_link'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? 'cash';
    $notes = trim($_POST['notes'] ?? '');

    if (empty($name) || empty($phone) || empty($address)) {
        $orderError = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } elseif (empty($_SESSION['cart'])) {
        $orderError = 'ตะกร้าว่างเปล่า';
    } else {
        // Calculate total
        $cartItems = [];
        $total = 0;
        $productIds = array_keys($_SESSION['cart']);
        if (!empty($productIds)) {
            $placeholders = implode(',', array_fill(0, count($productIds), '?'));
            $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
            $stmt->execute($productIds);
            $products = $stmt->fetchAll();
            foreach ($products as $p) {
                $qty = $_SESSION['cart'][$p['id']];
                $total += $p['price'] * $qty;
                $cartItems[] = ['product' => $p, 'quantity' => $qty];
            }
        }

        // Handle payment slip upload (BLOB)
        $paymentSlip = null;
        if ($paymentMethod === 'transfer' && isset($_FILES['payment_slip']) && $_FILES['payment_slip']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (in_array($_FILES['payment_slip']['type'], $allowedTypes)) {
                $paymentSlip = file_get_contents($_FILES['payment_slip']['tmp_name']);
            }
        }

        // Create order
        $stmt = $pdo->prepare("INSERT INTO orders (customer_name, phone, address, latitude, longitude, location_link, payment_method, payment_slip, total_amount, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $phone, $address, $latitude, $longitude, $locationLink, $paymentMethod, $paymentSlip, $total, $notes]);
        $orderId = $pdo->lastInsertId();

        // Create order items
        foreach ($cartItems as $item) {
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$orderId, $item['product']['id'], $item['quantity'], $item['product']['price']]);
        }

        // Clear cart
        $_SESSION['cart'] = [];
        $orderSuccess = true;
    }
}

// Get cart items
$cartItems = [];
$total = 0;
if (!empty($_SESSION['cart'])) {
    $productIds = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    // Update query to check has_image
    $stmt = $pdo->prepare("SELECT id, name, price, (image IS NOT NULL) as has_image FROM products WHERE id IN ($placeholders)");
    $stmt->execute($productIds);
    $products = $stmt->fetchAll();
    foreach ($products as $p) {
        $qty = $_SESSION['cart'][$p['id']];
        $total += $p['price'] * $qty;
        $cartItems[] = ['product' => $p, 'quantity' => $qty];
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตะกร้าสินค้า - ร้านอาหารตามสั่ง</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Prompt', sans-serif;
            background: linear-gradient(135deg, #fce4ec 0%, #f8bbd9 100%);
            color: #1a1a2e;
            min-height: 100vh;
        }

        .header {
            background: linear-gradient(135deg, #ec407a 0%, #d81b60 100%);
            color: white;
            padding: 0 20px;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 70px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 24px;
            font-weight: 700;
            text-decoration: none;
            color: white;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        h1 {
            font-size: 32px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .cart-layout {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
        }

        @media (max-width: 900px) {
            .cart-layout {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid #f0f0f0;
            font-weight: 600;
            font-size: 18px;
        }

        .card-body {
            padding: 24px;
        }

        /* Cart Items */
        .cart-item {
            display: flex;
            gap: 16px;
            padding: 16px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .cart-item-image {
            width: 80px;
            height: 80px;
            border-radius: 12px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .cart-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .cart-item-details {
            flex: 1;
        }

        .cart-item-name {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 4px;
        }

        .cart-item-price {
            color: #ec407a;
            font-weight: 600;
        }

        .cart-item-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 10px;
        }

        .qty-input {
            width: 60px;
            padding: 8px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            text-align: center;
            font-family: inherit;
            font-size: 14px;
        }

        .remove-btn {
            color: #dc2626;
            text-decoration: none;
            font-size: 13px;
        }

        .remove-btn:hover {
            text-decoration: underline;
        }

        .cart-item-subtotal {
            font-weight: 700;
            font-size: 18px;
            text-align: right;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-family: inherit;
            font-size: 14px;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #ec407a;
            box-shadow: 0 0 0 4px rgba(236, 64, 122, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .payment-options {
            display: flex;
            gap: 16px;
        }

        .payment-option {
            flex: 1;
            padding: 16px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
        }

        .payment-option:has(input:checked) {
            border-color: #ec407a;
            background: #fce4ec;
        }

        .payment-option input {
            display: none;
        }

        .payment-option span {
            display: block;
            font-size: 24px;
            margin-bottom: 8px;
        }

        .slip-upload {
            display: none;
            margin-top: 16px;
        }

        .slip-upload.show {
            display: block;
        }

        /* Location */
        .location-btn {
            background: #ec407a;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 10px;
            font-family: inherit;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
        }

        .location-btn:hover {
            background: #d81b60;
        }

        /* Summary */
        .order-summary-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .order-summary-total {
            display: flex;
            justify-content: space-between;
            padding: 20px 0 0;
            font-size: 24px;
            font-weight: 700;
        }

        .order-summary-total .price {
            color: #ec407a;
        }

        .btn-order {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #ec407a 0%, #d81b60 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-family: inherit;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.3s;
        }

        .btn-order:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(236, 64, 122, 0.3);
        }

        .btn-order:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .btn-update {
            background: #e5e7eb;
            color: #374151;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-family: inherit;
            cursor: pointer;
            margin-top: 10px;
        }

        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .alert-error {
            background: #fee2e2;
            color: #dc2626;
        }

        .alert-success {
            background: #d1fae5;
            color: #059669;
        }

        .empty-cart {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-cart span {
            font-size: 64px;
            display: block;
            margin-bottom: 20px;
        }

        .empty-cart a {
            display: inline-block;
            margin-top: 20px;
            padding: 14px 30px;
            background: linear-gradient(135deg, #ec407a 0%, #d81b60 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
        }

        /* Success Page */
        .success-page {
            text-align: center;
            padding: 80px 20px;
        }

        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            margin: 0 auto 30px;
            animation: scaleIn 0.5s ease;
        }

        @keyframes scaleIn {
            from {
                transform: scale(0);
            }

            to {
                transform: scale(1);
            }
        }

        .success-page h2 {
            font-size: 32px;
            color: #10b981;
            margin-bottom: 16px;
        }

        .success-page p {
            color: #666;
            margin-bottom: 30px;
        }
    </style>
</head>

<body>
    <header class="header">
        <div class="header-content">
            <a href="index.php" class="logo">
                <span>🍳</span>
                ร้านอาหารตามสั่ง
            </a>
        </div>
    </header>

    <div class="container">
        <?php if ($orderSuccess): ?>
            <div class="card">
                <div class="success-page">
                    <div class="success-icon">✓</div>
                    <h2>สั่งอาหารสำเร็จ!</h2>
                    <p>ขอบคุณที่ใช้บริการ ร้านจะติดต่อกลับเพื่อยืนยันออเดอร์</p>
                    <a href="index.php"
                        style="display:inline-block;padding:14px 30px;background:linear-gradient(135deg, #ec407a 0%, #d81b60 100%);color:white;text-decoration:none;border-radius:12px;font-weight:600;">กลับหน้าร้าน</a>
                </div>
            </div>
        <?php elseif (empty($cartItems)): ?>
            <div class="card">
                <div class="empty-cart">
                    <span>🛒</span>
                    <h2>ตะกร้าว่างเปล่า</h2>
                    <p>ยังไม่มีสินค้าในตะกร้า</p>
                    <a href="index.php">🍽️ เลือกเมนูอาหาร</a>
                </div>
            </div>
        <?php else: ?>
            <h1>🛒 ตะกร้าสินค้า</h1>

            <?php if ($orderError): ?>
                <div class="alert alert-error">
                    <?= htmlspecialchars($orderError) ?>
                </div>
            <?php endif; ?>

            <div class="cart-layout">
                <div>
                    <!-- Cart Items -->
                    <div class="card" style="margin-bottom:24px;">
                        <div class="card-header">รายการอาหาร (
                            <?= count($cartItems) ?> รายการ)
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <?php foreach ($cartItems as $item): ?>
                                    <div class="cart-item">
                                        <div class="cart-item-image">
                                            <?php if ($item['product']['has_image']): ?>
                                                <img src="view_image.php?table=products&id=<?= $item['product']['id'] ?>" alt=""
                                                    style="width:100%;height:100%;object-fit:cover;">
                                            <?php else: ?>
                                                🍲
                                            <?php endif; ?>
                                        </div>
                                        <div class="cart-item-details">
                                            <div class="cart-item-name">
                                                <?= htmlspecialchars($item['product']['name']) ?>
                                            </div>
                                            <div class="cart-item-price">฿
                                                <?= number_format($item['product']['price'], 0) ?>
                                            </div>
                                            <div class="cart-item-actions">
                                                <input type="number" name="quantities[<?= $item['product']['id'] ?>]"
                                                    value="<?= $item['quantity'] ?>" min="0" class="qty-input">
                                                <a href="?remove=<?= $item['product']['id'] ?>" class="remove-btn">✕ ลบ</a>
                                            </div>
                                        </div>
                                        <div class="cart-item-subtotal">
                                            ฿
                                            <?= number_format($item['product']['price'] * $item['quantity'], 0) ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                <button type="submit" name="update_cart" class="btn-update">🔄 อัปเดตตะกร้า</button>
                            </form>
                        </div>
                    </div>

                    <!-- Checkout Form -->
                    <div class="card">
                        <div class="card-header">📍 ข้อมูลการจัดส่ง</div>
                        <div class="card-body">
                            <form method="POST" enctype="multipart/form-data" id="orderForm">
                                <div class="form-group">
                                    <label>ชื่อผู้สั่ง *</label>
                                    <input type="text" name="customer_name" required placeholder="ชื่อ-นามสกุล">
                                </div>

                                <div class="form-group">
                                    <label>เบอร์โทรศัพท์ *</label>
                                    <input type="tel" name="phone" required placeholder="0xx-xxx-xxxx">
                                </div>

                                <div class="form-group">
                                    <label>ที่อยู่จัดส่ง *</label>
                                    <textarea name="address" rows="3" required
                                        placeholder="บ้านเลขที่, ซอย, ถนน, ตำบล/แขวง, อำเภอ/เขต, จังหวัด"></textarea>
                                </div>

                                <div class="form-group">
                                    <label>พิกัด GPS (ถ้ามี)</label>
                                    <div class="form-row">
                                        <input type="text" name="latitude" id="latitude" placeholder="Latitude (ละติจูด)">
                                        <input type="text" name="longitude" id="longitude"
                                            placeholder="Longitude (ลองจิจูด)">
                                    </div>
                                    <button type="button" class="location-btn" onclick="getLocation()">
                                        📍 ใช้ตำแหน่งปัจจุบัน
                                    </button>
                                </div>

                                <div class="form-group">
                                    <label>ลิงก์ Google Maps (ถ้ามี)</label>
                                    <input type="url" name="location_link" placeholder="https://maps.google.com/...">
                                </div>

                                <div class="form-group">
                                    <label>วิธีชำระเงิน *</label>
                                    <div class="payment-options">
                                        <label class="payment-option">
                                            <input type="radio" name="payment_method" value="cash" checked>
                                            <span>💵</span>
                                            เงินสด
                                        </label>
                                        <label class="payment-option">
                                            <input type="radio" name="payment_method" value="transfer">
                                            <span>📱</span>
                                            โอนเงิน
                                        </label>
                                    </div>
                                </div>

                                <div class="form-group slip-upload" id="slipUpload">
                                    <label>แนบสลิปการโอน</label>
                                    <input type="file" name="payment_slip" accept="image/*">
                                </div>

                                <div class="form-group">
                                    <label>หมายเหตุเพิ่มเติม</label>
                                    <textarea name="notes" rows="2" placeholder="เช่น ไม่ใส่ผัก, เพิ่มเผ็ด ฯลฯ"></textarea>
                                </div>
                        </div>
                    </div>
                </div>

                <!-- Order Summary -->
                <div>
                    <div class="card" style="position:sticky;top:90px;">
                        <div class="card-header">📋 สรุปคำสั่งซื้อ</div>
                        <div class="card-body">
                            <?php foreach ($cartItems as $item): ?>
                                <div class="order-summary-item">
                                    <span>
                                        <?= htmlspecialchars($item['product']['name']) ?> x
                                        <?= $item['quantity'] ?>
                                    </span>
                                    <span>฿
                                        <?= number_format($item['product']['price'] * $item['quantity'], 0) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>

                            <div class="order-summary-total">
                                <span>รวมทั้งหมด</span>
                                <span class="price">฿
                                    <?= number_format($total, 0) ?>
                                </span>
                            </div>

                            <button type="submit" name="place_order" class="btn-order">
                                🍳 สั่งอาหาร
                            </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Payment method toggle
        document.querySelectorAll('input[name="payment_method"]').forEach(input => {
            input.addEventListener('change', function () {
                document.getElementById('slipUpload').classList.toggle('show', this.value === 'transfer');
            });
        });

        // Get current location
        function getLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    position => {
                        document.getElementById('latitude').value = position.coords.latitude.toFixed(8);
                        document.getElementById('longitude').value = position.coords.longitude.toFixed(8);
                    },
                    error => {
                        alert('ไม่สามารถรับตำแหน่งได้: ' + error.message);
                    }
                );
            } else {
                alert('เบราว์เซอร์ไม่รองรับ Geolocation');
            }
        }
    </script>
</body>

</html>