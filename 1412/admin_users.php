<?php
require_once 'db.php';

// Check if admin is logged in
if (!isAdminLoggedIn()) {
    redirect('login.php');
}

// Handle Image Content
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

// Handler: Add Admin
if (isset($_POST['add_admin'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $name = trim($_POST['name']);
    $avatar = null;

    if (isset($_FILES['avatar'])) {
        $avatar = getImgContent($_FILES['avatar']);
    }

    // Check email
    $check = $pdo->prepare("SELECT id FROM admin WHERE email = ?");
    $check->execute([$email]);
    if ($check->rowCount() > 0) {
        $error = "อีเมลนี้มีอยู่ในระบบแล้ว";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO admin (email, password, name, avatar) VALUES (?, ?, ?, ?)");
        $stmt->execute([$email, $hash, $name, $avatar]);
        redirect('admin_users.php?msg=added');
    }
}

// Handler: Update Admin
if (isset($_POST['update_admin'])) {
    $id = (int) $_POST['id'];
    $email = trim($_POST['email']);
    $name = trim($_POST['name']);
    $password = $_POST['password']; // Optional

    $avatar = null;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $avatar = getImgContent($_FILES['avatar']);
    }

    $sql = "UPDATE admin SET email = ?, name = ?";
    $params = [$email, $name];

    if (!empty($password)) {
        $sql .= ", password = ?";
        $params[] = password_hash($password, PASSWORD_DEFAULT);
    }
    if ($avatar) {
        $sql .= ", avatar = ?";
        $params[] = $avatar;
    }
    $sql .= " WHERE id = ?";
    $params[] = $id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    redirect('admin_users.php?msg=updated');
}

// Handler: Delete Admin
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    // Prevent deleting self (optional but good practice)
    if ($_GET['delete'] == $_SESSION['admin_id']) {
        $error = "ไม่สามารถลบบัญชีตัวเองได้";
    } else {
        $stmt = $pdo->prepare("DELETE FROM admin WHERE id = ?");
        $stmt->execute([(int) $_GET['delete']]);
        redirect('admin_users.php?msg=deleted');
    }
}

// Get Admins
$search = $_GET['search'] ?? '';
$sql = "SELECT id, email, name, created_at, (avatar IS NOT NULL) as has_avatar FROM admin";
if ($search) {
    $sql .= " WHERE name LIKE :s OR email LIKE :s";
}
$stmt = $pdo->prepare($sql);
if ($search) {
    $stmt->execute([':s' => "%$search%"]);
} else {
    $stmt->execute();
}
$admins = $stmt->fetchAll();

// Get Edit Admin
$editAdmin = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM admin WHERE id = ?");
    $stmt->execute([(int) $_GET['edit']]);
    $editAdmin = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ดูแลระบบ</title>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background: linear-gradient(135deg, #fce4ec 0%, #f8bbd9 100%);
            min-height: 100vh;
            margin: 0;
        }

        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #880e4f 0%, #4a0e32 100%);
            min-height: 100vh;
            position: fixed;
            padding: 30px 20px;
            color: white;
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

        .card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 24px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            color: white;
            background: #ec407a;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .avatar-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
</head>

<body>
    <aside class="sidebar">
        <div style="margin-bottom:40px;font-size:24px;font-weight:700;">🍳 ร้านอาหาร</div>
        <nav class="sidebar-menu">
            <a href="admin.php">📦 จัดการเมนู</a>
            <a href="admin_orders.php">📋 รายการสั่งซื้อ</a>
            <a href="admin_users.php" class="active">👥 ผู้ดูแลระบบ</a>
            <a href="index.php">🏠 หน้าร้าน</a>
            <a href="admin.php?logout=1">🚪 ออกจากระบบ</a>
        </nav>
    </aside>

    <main class="main-content">
        <h1>จัดการผู้ดูแลระบบ</h1>

        <?php if (isset($error)): ?>
            <div style="background:#fee2e2;color:#dc2626;padding:15px;border-radius:8px;margin-bottom:20px;">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3>
                <?= $editAdmin ? 'แก้ไขผู้ดูแล' : 'เพิ่มผู้ดูแลใหม่' ?>
            </h3>
            <form method="POST" enctype="multipart/form-data">
                <?php if ($editAdmin): ?>
                    <input type="hidden" name="id" value="<?= $editAdmin['id'] ?>">
                <?php endif; ?>
                <div class="form-group">
                    <label>ชื่อ</label>
                    <input type="text" name="name" required value="<?= $editAdmin['name'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label>อีเมล</label>
                    <input type="email" name="email" required value="<?= $editAdmin['email'] ?? '' ?>">
                </div>
                <div class="form-group">
                    <label>รหัสผ่าน
                        <?= $editAdmin ? '(เว้นว่างหากไม่เปลี่ยน)' : '*' ?>
                    </label>
                    <input type="password" name="password" <?= $editAdmin ? '' : 'required' ?>>
                </div>
                <div class="form-group">
                    <label>รูปโปรไฟล์</label>
                    <input type="file" name="avatar" accept="image/*">
                    <?php if ($editAdmin && !empty($editAdmin['avatar'])): ?>
                        <div style="margin-top:10px;">
                            <img src="view_image.php?table=admin&col=avatar&id=<?= $editAdmin['id'] ?>" class="avatar-img">
                        </div>
                    <?php endif; ?>
                </div>
                <button type="submit" name="<?= $editAdmin ? 'update_admin' : 'add_admin' ?>"
                    class="btn">บันทึก</button>
                <?php if ($editAdmin): ?>
                    <a href="admin_users.php" style="margin-left:10px;text-decoration:none;color:#666;">ยกเลิก</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="card">
            <h3>รายชื่อผู้ดูแล</h3>
            <table>
                <thead>
                    <tr>
                        <th>รูป</th>
                        <th>ชื่อ</th>
                        <th>อีเมล</th>
                        <th>วันที่สร้าง</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td>
                                <?php if ($admin['has_avatar']): ?>
                                    <img src="view_image.php?table=admin&col=avatar&id=<?= $admin['id'] ?>" class="avatar-img">
                                <?php else: ?>
                                    <div class="avatar-img"
                                        style="background:#ddd;display:flex;align-items:center;justify-content:center;">👤</div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($admin['name']) ?>
                            </td>
                            <td>
                                <?= htmlspecialchars($admin['email']) ?>
                            </td>
                            <td>
                                <?= date('d/m/Y', strtotime($admin['created_at'])) ?>
                            </td>
                            <td>
                                <a href="?edit=<?= $admin['id'] ?>">✏️ แ้ก้ไข</a>
                                <?php if ($admin['id'] != $_SESSION['admin_id']): ?>
                                    <a href="?delete=<?= $admin['id'] ?>" onclick="return confirm('ยืนยัน?')"
                                        style="color:red;margin-left:10px;">🗑️ ลบ</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>

</html>