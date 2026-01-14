<?php
require_once 'db.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    redirect('login.php');
}

// Handle image upload (Returns binary data)
function getImgContent($file)
{
    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (in_array($file['type'], $allowedTypes)) {
            return file_get_contents($file['tmp_name']);
        }
    }
    return null;
}

// Handle delete product
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    redirect('admin.php?msg=deleted');
}

// Handle add/edit product
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $category = trim($_POST['category'] ?? '');
    $is_available = isset($_POST['is_available']) ? 1 : 0;

    $imgContent = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imgContent = getImgContent($_FILES['image']);
    }

    if ($id) {
        // Update
        if ($imgContent) {
            $stmt = $pdo->prepare("UPDATE products SET name=?, description=?, price=?, image=?, category=?, is_available=? WHERE id=?");
            $stmt->execute([$name, $description, $price, $imgContent, $category, $is_available, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE products SET name=?, description=?, price=?, category=?, is_available=? WHERE id=?");
            $stmt->execute([$name, $description, $price, $category, $is_available, $id]);
        }
        redirect('admin.php?msg=updated');
    } else {
        // Insert
        // If no image uploaded for new product, it will be null
        $stmt = $pdo->prepare("INSERT INTO products (name, description, price, image, category, is_available) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $price, $imgContent, $category, $is_available]);
        redirect('admin.php?msg=added');
    }
}

// Get product for editing
$editProduct = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT id, name, description, price, category, is_available FROM products WHERE id = ?");
    $stmt->execute([(int) $_GET['edit']]);
    $editProduct = $stmt->fetch();
}

// Get all products (Selecting id, name ... image is heavy so maybe load only if needed, but for listing we might need it. 
// Ideally we select image ID or just check if not null to show placeholder, then load via view_image.php)
// Here we select all but we won't output the blob directly to HTML.
$products = $pdo->query("SELECT id, name, description, price, category, is_available, (image IS NOT NULL) as has_image FROM products ORDER BY created_at DESC")->fetchAll();

// Get orders count
$pendingOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แอดมิน - ร้านอาหารตามสั่ง</title>
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

        .header-actions {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #ec407a 0%, #d81b60 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(236, 64, 122, 0.3);
        }

        .btn-danger {
            background: #fee2e2;
            color: #dc2626;
        }

        .btn-danger:hover {
            background: #dc2626;
            color: white;
        }

        .btn-secondary {
            background: #e5e7eb;
            color: #374151;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .stat-card h3 {
            color: #666;
            font-size: 14px;
            font-weight: 400;
            margin-bottom: 8px;
        }

        .stat-card .value {
            font-size: 32px;
            font-weight: 700;
            color: #1a1a2e;
        }

        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 24px;
        }

        .card-header {
            padding: 20px 24px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header h2 {
            font-size: 18px;
            color: #1a1a2e;
        }

        .card-body {
            padding: 24px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group.full-width {
            grid-column: span 2;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
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
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #ec407a;
            box-shadow: 0 0 0 4px rgba(236, 64, 122, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: #ec407a;
        }

        .image-preview {
            width: 120px;
            height: 120px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid #e0e0e0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 16px;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }

        th {
            color: #666;
            font-weight: 500;
            font-size: 13px;
            text-transform: uppercase;
        }

        td {
            color: #333;
        }

        .product-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .product-cell img {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
        }

        .product-cell .placeholder {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-available {
            background: #d1fae5;
            color: #059669;
        }

        .status-unavailable {
            background: #fee2e2;
            color: #dc2626;
        }

        .actions {
            display: flex;
            gap: 8px;
        }

        .actions a {
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 13px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .actions .edit {
            background: #fce4ec;
            color: #ec407a;
        }

        .actions .delete {
            background: #fee2e2;
            color: #dc2626;
        }

        .actions a:hover {
            transform: scale(1.05);
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

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full-width {
                grid-column: span 1;
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
            <a href="admin.php" class="active">📦 <span>จัดการเมนู</span></a>
            <a href="admin_orders.php">📋 <span>รายการสั่งซื้อ</span>
                <?php if ($pendingOrders > 0): ?><span
                        style="background:#dc2626;padding:2px 8px;border-radius:10px;font-size:12px;">
                        <?= $pendingOrders ?>
                    </span>
                <?php endif; ?>
            </a>
            <a href="admin_users.php">👥 <span>ผู้ดูแลระบบ</span></a>
            <a href="index.php">🏠 <span>หน้าร้าน</span></a>
            <a href="?logout=1">🚪 <span>ออกจากระบบ</span></a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="header">
            <h1>จัดการเมนูอาหาร</h1>
            <div class="header-actions">
                <span>👋 สวัสดี,
                    <?= htmlspecialchars($_SESSION['admin_email']) ?>
                </span>
            </div>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success">
                <?php
                $messages = [
                    'added' => '✅ เพิ่มเมนูสำเร็จ!',
                    'updated' => '✅ แก้ไขเมนูสำเร็จ!',
                    'deleted' => '✅ ลบเมนูสำเร็จ!'
                ];
                echo $messages[$_GET['msg']] ?? '';
                ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>เมนูทั้งหมด</h3>
                <div class="value">
                    <?= count($products) ?>
                </div>
            </div>
            <div class="stat-card">
                <h3>เมนูที่พร้อมขาย</h3>
                <div class="value">
                    <?= count(array_filter($products, fn($p) => $p['is_available'])) ?>
                </div>
            </div>
            <div class="stat-card">
                <h3>รอดำเนินการ</h3>
                <div class="value">
                    <?= $pendingOrders ?>
                </div>
            </div>
        </div>

        <!-- Add/Edit Form -->
        <div class="card">
            <div class="card-header">
                <h2>
                    <?= $editProduct ? '✏️ แก้ไขเมนู' : '➕ เพิ่มเมนูใหม่' ?>
                </h2>
                <?php if ($editProduct): ?>
                    <a href="admin.php" class="btn btn-secondary">ยกเลิก</a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <?php if ($editProduct): ?>
                        <input type="hidden" name="id" value="<?= $editProduct['id'] ?>">
                    <?php endif; ?>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name">ชื่อเมนู *</label>
                            <input type="text" id="name" name="name"
                                value="<?= htmlspecialchars($editProduct['name'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="price">ราคา (บาท) *</label>
                            <input type="number" id="price" name="price" step="0.01"
                                value="<?= $editProduct['price'] ?? '' ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="category">หมวดหมู่</label>
                            <input type="text" id="category" name="category"
                                value="<?= htmlspecialchars($editProduct['category'] ?? '') ?>"
                                placeholder="เช่น อาหารจานเดียว, เครื่องดื่ม">
                        </div>
                        <div class="form-group">
                            <label for="image">รูปภาพ</label>
                            <input type="file" id="image" name="image" accept="image/*">
                            <?php if ($editProduct): ?>
                                <!-- Check if image exists via a separate query or try to load it. Since we don't have is_null check in edit query easily without fetching blob, we can try to just show it if we are editing. -->
                                <!-- Better: we should have selected if image is not null. Let's assume we want to show it. If it's empty it might show broken image or we can add a check in view_image.php -->
                                <p style="margin-top:8px;font-size:12px;color:#666;">รูปปัจจุบัน:</p>
                                <img src="view_image.php?table=products&id=<?= $editProduct['id'] ?>" class="image-preview"
                                    style="margin-top:8px;">
                            <?php endif; ?>
                        </div>
                        <div class="form-group full-width">
                            <label for="description">รายละเอียด</label>
                            <textarea id="description" name="description"
                                placeholder="รายละเอียดเมนู..."><?= htmlspecialchars($editProduct['description'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="is_available" name="is_available"
                                    <?= ($editProduct['is_available'] ?? 1) ? 'checked' : '' ?>>
                                <label for="is_available" style="margin:0;">พร้อมขาย</label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <?= $editProduct ? '💾 บันทึกการแก้ไข' : '➕ เพิ่มเมนู' ?>
                    </button>
                </form>
            </div>
        </div>

        <!-- Products Table -->
        <div class="card">
            <div class="card-header">
                <h2>📋 รายการเมนูทั้งหมด</h2>
            </div>
            <div class="card-body" style="padding:0;">
                <table>
                    <thead>
                        <tr>
                            <th>เมนู</th>
                            <th>หมวดหมู่</th>
                            <th>ราคา</th>
                            <th>สถานะ</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($products)): ?>
                            <tr>
                                <td colspan="5" style="text-align:center;padding:40px;color:#999;">ยังไม่มีเมนู</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <div class="product-cell">
                                            <?php if ($product['has_image']): ?>
                                                <img src="view_image.php?table=products&id=<?= $product['id'] ?>" alt="">
                                            <?php else: ?>
                                                <div class="placeholder">🍽️</div>
                                            <?php endif; ?>
                                            <div>
                                                <strong>
                                                    <?= htmlspecialchars($product['name']) ?>
                                                </strong>
                                                <?php if ($product['description']): ?>
                                                    <p style="font-size:12px;color:#666;margin-top:4px;">
                                                        <?= htmlspecialchars(mb_substr($product['description'], 0, 50)) ?>...
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($product['category'] ?: '-') ?>
                                    </td>
                                    <td><strong>฿
                                            <?= number_format($product['price'], 2) ?>
                                        </strong></td>
                                    <td>
                                        <span
                                            class="status-badge <?= $product['is_available'] ? 'status-available' : 'status-unavailable' ?>">
                                            <?= $product['is_available'] ? 'พร้อมขาย' : 'ไม่พร้อม' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="actions">
                                            <a href="?edit=<?= $product['id'] ?>" class="edit">✏️ แก้ไข</a>
                                            <a href="?delete=<?= $product['id'] ?>" class="delete"
                                                onclick="return confirm('ยืนยันการลบ?')">🗑️ ลบ</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>

</html>