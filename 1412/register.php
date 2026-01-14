<?php
require_once 'db.php';

$error = '';
$success = '';

// If already logged in, redirect
if (isCustomerLoggedIn()) {
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } elseif ($password !== $confirmPassword) {
        $error = 'รหัสผ่านไม่ตรงกัน';
    } elseif (strlen($password) < 4) {
        $error = 'รหัสผ่านต้องมีอย่างน้อย 4 ตัวอักษร';
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM customers WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'อีเมลนี้ถูกใช้งานแล้ว';
        } else {
            // Create new customer
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO customers (name, email, phone, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $phone, $hashedPassword]);

            $success = 'สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - ร้านอาหารตามสั่ง</title>
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
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #fce4ec 0%, #f48fb1 50%, #ec407a 100%);
            padding: 20px;
        }

        .register-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 50px 40px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 25px 50px rgba(236, 64, 122, 0.3);
        }

        .register-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .register-header .icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #ec407a 0%, #d81b60 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 36px;
        }

        .register-header h1 {
            color: #1a1a2e;
            font-size: 24px;
            font-weight: 600;
        }

        .register-header p {
            color: #666;
            margin-top: 8px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid #f8bbd9;
            border-radius: 12px;
            font-size: 16px;
            font-family: inherit;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .form-group input:focus {
            outline: none;
            border-color: #ec407a;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(236, 64, 122, 0.1);
        }

        .btn-register {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #ec407a 0%, #d81b60 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(236, 64, 122, 0.4);
        }

        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 14px 20px;
            border-radius: 10px;
            margin-bottom: 24px;
            font-size: 14px;
            text-align: center;
        }

        .success-message {
            background: #d1fae5;
            color: #059669;
            padding: 14px 20px;
            border-radius: 10px;
            margin-bottom: 24px;
            font-size: 14px;
            text-align: center;
        }

        .links {
            text-align: center;
            margin-top: 24px;
        }

        .links a {
            color: #ec407a;
            text-decoration: none;
            font-size: 14px;
        }

        .links a:hover {
            text-decoration: underline;
        }

        .divider {
            margin: 0 10px;
            color: #ccc;
        }
    </style>
</head>

<body>
    <div class="register-container">
        <div class="register-header">
            <div class="icon">👤</div>
            <h1>สมัครสมาชิก</h1>
            <p>สร้างบัญชีเพื่อสั่งอาหารได้ง่ายขึ้น</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="name">ชื่อ-นามสกุล *</label>
                <input type="text" id="name" name="name" placeholder="กรอกชื่อของคุณ"
                    value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="email">อีเมล *</label>
                <input type="email" id="email" name="email" placeholder="example@email.com"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="phone">เบอร์โทรศัพท์</label>
                <input type="tel" id="phone" name="phone" placeholder="0xx-xxx-xxxx"
                    value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="password">รหัสผ่าน *</label>
                <input type="password" id="password" name="password" placeholder="อย่างน้อย 4 ตัวอักษร" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">ยืนยันรหัสผ่าน *</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="กรอกรหัสผ่านอีกครั้ง"
                    required>
            </div>
            <button type="submit" class="btn-register">สมัครสมาชิก</button>
        </form>

        <div class="links">
            <a href="login.php">มีบัญชีแล้ว? เข้าสู่ระบบ</a>
            <span class="divider">|</span>
            <a href="index.php">← กลับหน้าร้าน</a>
        </div>
    </div>
</body>

</html>