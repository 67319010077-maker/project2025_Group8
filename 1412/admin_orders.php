<?php
require_once 'db.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

// Handle status update
if (isset($_POST['update_status'])) {
    $orderId = (int) $_POST['order_id'];
    $status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $orderId]);
    redirect('admin_orders.php?msg=updated');
}

// Handle delete order
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->execute([(int) $_GET['delete']]);
    redirect('admin_orders.php?msg=deleted');
}

// Get filters
$statusFilter = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

// Get orders (Exclude BLOB data for performance, check existence instead)
$sql = "SELECT id, customer_name, phone, address, latitude, longitude, location_link, payment_method, total_amount, status, notes, created_at, (payment_slip IS NOT NULL) as has_slip FROM orders WHERE 1=1";
$params = [];

if ($statusFilter !== 'all') {
    $sql .= " AND status = ?";
    $params[] = $statusFilter;
}

if ($search) {
    $sql .= " AND (customer_name LIKE ? OR phone LIKE ? OR id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($startDate) {
    $sql .= " AND DATE(created_at) >= ?";
    $params[] = $startDate;
}

if ($endDate) {
    $sql .= " AND DATE(created_at) <= ?";
    $params[] = $endDate;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get order items for each order
foreach ($orders as &$order) {
    $stmt = $pdo->prepare("SELECT oi.*, p.name as product_name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
    $stmt->execute([$order['id']]);
    $order['items'] = $stmt->fetchAll();
}

$statusLabels = [
    'pending' => ['label' => 'รอดำเนินการ', 'color' => '#f59e0b'],
    'confirmed' => ['label' => 'ยืนยันแล้ว', 'color' => '#3b82f6'],
    'cooking' => ['label' => 'กำลังทำ', 'color' => '#8b5cf6'],
    'delivering' => ['label' => 'กำลังส่ง', 'color' => '#06b6d4'],
    'completed' => ['label' => 'เสร็จสิ้น', 'color' => '#10b981'],
    'cancelled' => ['label' => 'ยกเลิก', 'color' => '#ef4444']
];

// Count pending orders
$pendingCount = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการออเดอร์ - แอดมิน</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Prompt', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #fce4ec 0%, #f8bbd9 100%);
        }

        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #880e4f 0%, #4a0e32 100%);
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding: 30px 20px;
            color: white;
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 40px;
        }

        .sidebar-logo span {
            font-size: 32px;
        }

        .sidebar-logo h2 {
            font-size: 18px;
            font-weight: 600;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            border-radius: 12px;
            margin-bottom: 8px;
            transition: all 0.3s;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }

        .sidebar-menu a.active {
            background: linear-gradient(135deg, #ec407a 0%, #d81b60 100%);
        }

        .main-content {
            margin-left: 260px;
            padding: 30px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 28px;
            color: #1a1a2e;
        }

        .filters {
            display: flex;
            gap: 10px;
            margin-bottom: 24px;
            flex-wrap: wrap;
            align-items: center;
            background: white;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .filter-group {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .filter-input {
            padding: 10px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
        }

        .btn-filter {
            padding: 10px 20px;
            background: linear-gradient(135deg, #ec407a 0%, #d81b60 100%);
            color: white;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-print {
            background: #374151;
            color: white;
        }

        /* Print Styles */
        @media print {

            .sidebar,
            .header-actions,
            .filters,
            .order-actions,
            .btn {
                display: none !important;
            }

            .main-content {
                margin: 0;
                padding: 0;
            }

            .card {
                box-shadow: none;
                border: 1px solid #ccc;
                break-inside: avoid;
            }

            body {
                background: white;
            }
        }

        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            overflow: hidden;
        }

        .order-header {
            padding: 20px 24px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
        }

        .order-id {
            font-weight: 700;
            font-size: 18px;
        }

        .order-date {
            color: #666;
            font-size: 14px;
        }

        .status-badge {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            color: white;
        }

        .order-body {
            padding: 24px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 24px;
        }

        @media (max-width: 1200px) {
            .order-body {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 800px) {
            .order-body {
                grid-template-columns: 1fr;
            }
        }

        .order-section h4 {
            font-size: 13px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
        }

        .order-section p {
            font-size: 14px;
            line-height: 1.8;
        }

        .order-items-list {
            list-style: none;
        }

        .order-items-list li {
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
        }

        .order-items-list li:last-child {
            border-bottom: none;
        }

        .order-total {
            font-weight: 700;
            font-size: 20px;
            color: #ec407a;
            margin-top: 16px;
        }

        .order-actions {
            padding: 16px 24px;
            background: #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .status-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .status-form select {
            padding: 10px 16px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ec407a 0%, #d81b60 100%);
            color: white;
        }

        .btn-danger {
            background: #fee2e2;
            color: #dc2626;
            text-decoration: none;
        }

        .btn-danger:hover {
            background: #dc2626;
            color: white;
        }

        .slip-preview {
            max-width: 200px;
            border-radius: 8px;
            margin-top: 10px;
        }

        .location-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #ec407a;
            text-decoration: none;
            margin-top: 8px;
        }

        .location-link:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 14px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d1fae5;
            color: #059669;
        }

        .empty-state {
            text-align: center;
            padding: 60px;
            color: #666;
        }

        .empty-state span {
            font-size: 64px;
            display: block;
            margin-bottom: 16px;
        }

        @media (max-width: 1024px) {
            .sidebar {
                width: 80px;
                padding: 20px 10px;
            }

            .sidebar-logo h2,
            .sidebar-menu a span {
                display: none;
            }

            .sidebar-menu a {
                justify-content: center;
            }

            .main-content {
                margin-left: 80px;
            }
        }
    </style>
</head>

<body>
    <aside class="sidebar">
        <div class="sidebar-logo">
            <span>🍳</span>
            <h2>ร้านอาหาร</h2>
        </div>
        <nav class="sidebar-menu">
            <a href="admin.php">📦 <span>จัดการเมนู</span></a>
            <a href="admin_orders.php" class="active">📋 <span>รายการสั่งซื้อ</span>
                <?php if ($pendingCount > 0): ?><span
                        style="background:#dc2626;padding:2px 8px;border-radius:10px;font-size:12px;">
                        <?= $pendingCount ?>
                    </span>
                <?php endif; ?>
            </a>
            <a href="admin_users.php">👥 <span>ผู้ดูแลระบบ</span></a>
            <a href="index.php">🏠 <span>หน้าร้าน</span></a>
            <a href="admin.php?logout=1">🚪 <span>ออกจากระบบ</span></a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="header">
            <h1>📋 รายการสั่งซื้อ</h1>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success">
                <?= $_GET['msg'] === 'updated' ? '✅ อัปเดตสถานะสำเร็จ!' : '✅ ลบออเดอร์สำเร็จ!' ?>
            </div>
        <?php endif; ?>

        <form method="GET" class="filters">
            <div class="filter-group">
                <select name="status" class="filter-input" onchange="this.form.submit()">
                    <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>ทุกสถานะ</option>
                    <?php foreach ($statusLabels as $key => $val): ?>
                        <option value="<?= $key ?>" <?= $statusFilter === $key ? 'selected' : '' ?>>
                            <?= $val['label'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <input type="text" name="search" placeholder="ค้นหาชื่อ, เบอร์, ID..." value="<?= htmlspecialchars($search) ?>" class="filter-input">
            </div>

            <div class="filter-group">
                <input type="date" name="start_date" value="<?= $startDate ?>" class="filter-input">
                <span>ถึง</span>
                <input type="date" name="end_date" value="<?= $endDate ?>" class="filter-input">
            </div>

            <button type="submit" class="btn btn-filter">🔍 ค้นหา</button>
            <a href="admin_orders.php" class="btn btn-filter" style="background:#6b7280;">ล้างค่า</a>
            <button type="button" onclick="window.print()" class="btn btn-filter btn-print">🖨️ พิมพ์</button>
        </form>

        <?php if (empty($orders)): ?>
            <div class="card">
                <div class="empty-state">
                    <span>📭</span>
                    <h3>ไม่มีรายการสั่งซื้อ</h3>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <div class="card">
                    <div class="order-header">
                        <div>
                            <span class="order-id">ออเดอร์ #
                                <?= $order['id'] ?>
                            </span>
                            <span class="order-date">•
                                <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                            </span>
                        </div>
                        <span class="status-badge" style="background:<?= $statusLabels[$order['status']]['color'] ?>">
                            <?= $statusLabels[$order['status']]['label'] ?>
                        </span>
                    </div>

                    <div class="order-body">
                        <div class="order-section">
                            <h4>👤 ข้อมูลลูกค้า</h4>
                            <p>
                                <strong>
                                    <?= htmlspecialchars($order['customer_name']) ?>
                                </strong><br>
                                📞
                                <?= htmlspecialchars($order['phone']) ?><br>
                                📍
                                <?= htmlspecialchars($order['address']) ?>
                            </p>
                            <?php if ($order['latitude'] && $order['longitude']): ?>
                                <a href="https://www.google.com/maps?q=<?= $order['latitude'] ?>,<?= $order['longitude'] ?>"
                                    target="_blank" class="location-link">
                                    🗺️ ดูแผนที่
                                </a>
                            <?php elseif ($order['location_link']): ?>
                                <a href="<?= htmlspecialchars($order['location_link']) ?>" target="_blank" class="location-link">
                                    🗺️ ดูแผนที่
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="order-section">
                            <h4>🍽️ รายการอาหาร</h4>
                            <ul class="order-items-list">
                                <?php foreach ($order['items'] as $item): ?>
                                    <li>
                                        <span>
                                            <?= htmlspecialchars($item['product_name']) ?> x
                                            <?= $item['quantity'] ?>
                                        </span>
                                        <span>฿
                                            <?= number_format($item['price'] * $item['quantity'], 0) ?>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <div class="order-total">รวม: ฿
                                <?= number_format($order['total_amount'], 0) ?>
                            </div>
                        </div>

                        <div class="order-section">
                            <h4>💳 การชำระเงิน</h4>
                            <p>
                                <?= $order['payment_method'] === 'cash' ? '💵 เงินสด' : '📱 โอนเงิน' ?>
                            </p>
                            <?php if ($order['has_slip']): ?>
                                <img src="view_image.php?table=orders&col=payment_slip&id=<?= $order['id'] ?>" class="slip-preview"
                                    alt="สลิป">
                            <?php endif; ?>
                            <?php if ($order['notes']): ?>
                                <p style="margin-top:16px;"><strong>หมายเหตุ:</strong>
                                    <?= htmlspecialchars($order['notes']) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="order-actions">
                        <form method="POST" class="status-form">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <select name="status">
                                <?php foreach ($statusLabels as $key => $val): ?>
                                    <option value="<?= $key ?>" <?= $order['status'] === $key ? 'selected' : '' ?>>
                                        <?= $val['label'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="update_status" class="btn btn-primary">อัปเดตสถานะ</button>
                        </form>
                        <a href="?delete=<?= $order['id'] ?>" class="btn btn-danger"
                            onclick="return confirm('ยืนยันการลบออเดอร์นี้?')">🗑️ ลบออเดอร์</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</body>

</html>