<?php
require_once 'db.php';

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $productId = (int) $_POST['product_id'];
    $quantity = max(1, (int) $_POST['quantity']);

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    if (isset($_SESSION['cart'][$productId])) {
        $_SESSION['cart'][$productId] += $quantity;
    } else {
        $_SESSION['cart'][$productId] = $quantity;
    }

    // Return JSON for AJAX
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'cartCount' => array_sum($_SESSION['cart'])]);
        exit;
    }
    redirect('index.php');
}

// Get all available products
$products = $pdo->query("SELECT * FROM products WHERE is_available = 1 ORDER BY category, name")->fetchAll();
$categories = array_unique(array_column($products, 'category'));

// Cart count
$cartCount = isset($_SESSION['cart']) ? array_sum($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ร้านอาหารตามสั่ง | สั่งอาหารออนไลน์</title>
    <meta name="description" content="ร้านอาหารตามสั่งออนไลน์ สั่งง่าย อร่อย ส่งถึงบ้าน">
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

        /* Header */
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
            max-width: 1400px;
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
        }

        .logo span {
            font-size: 32px;
        }

        .nav-links {
            display: flex;
            gap: 24px;
            align-items: center;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.3s;
        }

        .nav-links a:hover {
            opacity: 0.8;
        }

        .cart-btn {
            background: rgba(255, 255, 255, 0.2);
            padding: 10px 20px;
            border-radius: 25px;
            display: flex;
            align-items: center;
            gap: 8px;
            backdrop-filter: blur(10px);
            position: relative;
        }

        .cart-count {
            background: white;
            color: #ec407a;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            position: absolute;
            top: -5px;
            right: -5px;
        }

        .login-btn {
            background: rgba(255, 255, 255, 0.2);
            padding: 10px 20px;
            border-radius: 25px;
            backdrop-filter: blur(10px);
        }

        .login-btn:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .logout-btn {
            background: rgba(220, 38, 38, 0.8);
            padding: 10px 20px;
            border-radius: 25px;
        }

        .logout-btn:hover {
            background: rgba(220, 38, 38, 1);
        }

        .user-greeting {
            background: rgba(255, 255, 255, 0.2);
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 500;
        }

        /* Hero */
        .hero {
            background: linear-gradient(135deg, #ec407a 0%, #d81b60 50%, #ad1457 100%);
            color: white;
            padding: 80px 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }

        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 800px;
            margin: 0 auto;
        }

        .hero h1 {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 16px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        .hero p {
            font-size: 20px;
            opacity: 0.9;
            margin-bottom: 32px;
        }

        .hero-features {
            display: flex;
            justify-content: center;
            gap: 40px;
            flex-wrap: wrap;
        }

        .hero-feature {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 16px;
        }

        .hero-feature span {
            font-size: 24px;
        }

        /* Categories */
        .categories {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px 0;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            position: sticky;
            top: 70px;
            background: #f8f9fa;
            z-index: 100;
            padding-bottom: 20px;
        }

        .category-btn {
            padding: 10px 24px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            font-family: inherit;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .category-btn:hover,
        .category-btn.active {
            background: linear-gradient(135deg, #ec407a 0%, #d81b60 100%);
            color: white;
            border-color: transparent;
        }

        /* Menu */
        .menu-section {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .section-title {
            font-size: 28px;
            font-weight: 700;
            margin: 40px 0 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
        }

        .product-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s;
        }

        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        .product-image {
            height: 200px;
            background: linear-gradient(135deg, #f0f0f0 0%, #e0e0e0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 64px;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .product-card:hover .product-image img {
            transform: scale(1.1);
        }

        .product-info {
            padding: 20px;
        }

        .product-category {
            font-size: 12px;
            color: #ec407a;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .product-name {
            font-size: 20px;
            font-weight: 600;
            margin: 8px 0;
        }

        .product-desc {
            font-size: 14px;
            color: #666;
            line-height: 1.5;
            margin-bottom: 16px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .product-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .product-price {
            font-size: 24px;
            font-weight: 700;
            color: #ec407a;
        }

        .add-to-cart {
            background: linear-gradient(135deg, #ec407a 0%, #d81b60 100%);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 12px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .add-to-cart:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 20px rgba(236, 64, 122, 0.4);
        }

        .add-to-cart.added {
            background: #10b981;
        }

        /* Footer */
        footer {
            background: linear-gradient(135deg, #880e4f 0%, #4a0e32 100%);
            color: white;
            padding: 60px 20px;
            margin-top: 80px;
        }

        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
        }

        .footer-section h3 {
            font-size: 18px;
            margin-bottom: 20px;
            color: #f48fb1;
        }

        .footer-section p,
        .footer-section a {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            line-height: 1.8;
            text-decoration: none;
        }

        .footer-section a:hover {
            color: white;
        }

        /* Toast */
        .toast {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: #10b981;
            color: white;
            padding: 16px 32px;
            border-radius: 12px;
            font-weight: 500;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            z-index: 9999;
            opacity: 0;
            transition: all 0.3s;
        }

        .toast.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #666;
        }

        .empty-state span {
            font-size: 64px;
            display: block;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 32px;
            }

            .hero-features {
                gap: 20px;
            }

            .nav-links {
                gap: 12px;
            }
        }
    </style>
</head>

<body>
    <header class="header">
        <div class="header-content">
            <div class="logo">
                <span>🍳</span>
                ร้านอาหารตามสั่ง
            </div>
            <nav class="nav-links">
                <a href="index.php">หน้าแรก</a>
                <a href="#menu">เมนู</a>
                <a href="cart.php" class="cart-btn">
                    🛒 ตะกร้า
                    <?php if ($cartCount > 0): ?>
                        <span class="cart-count">
                            <?= $cartCount ?>
                        </span>
                    <?php endif; ?>
                </a>
                <?php if (isAdminLoggedIn()): ?>
                    <a href="admin.php">🔧 แอดมิน</a>
                    <a href="logout.php" class="logout-btn">🚪 ออกจากระบบ</a>
                <?php elseif (isCustomerLoggedIn()): ?>
                    <span class="user-greeting">👋 <?= htmlspecialchars(getCurrentCustomer()) ?></span>
                    <a href="logout.php" class="logout-btn">🚪 ออกจากระบบ</a>
                <?php else: ?>
                    <a href="login.php" class="login-btn">🔐 เข้าสู่ระบบ</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <section class="hero">
        <div class="hero-content">
            <h1>อาหารอร่อย ส่งถึงบ้านคุณ</h1>
            <p>สั่งอาหารตามสั่งออนไลน์ สด ใหม่ ทำสดใหม่ทุกจาน</p>
            <div class="hero-features">
                <div class="hero-feature">
                    <span>🚀</span> ส่งไว
                </div>
                <div class="hero-feature">
                    <span>👨‍🍳</span> ทำสดใหม่
                </div>
                <div class="hero-feature">
                    <span>💯</span> คุณภาพดี
                </div>
            </div>
        </div>
    </section>

    <div class="categories" id="menu">
        <button class="category-btn active" data-category="all">ทั้งหมด</button>
        <?php foreach ($categories as $cat): ?>
            <?php if ($cat): ?>
                <button class="category-btn" data-category="<?= htmlspecialchars($cat) ?>">
                    <?= htmlspecialchars($cat) ?>
                </button>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <main class="menu-section">
        <?php if (empty($products)): ?>
            <div class="empty-state">
                <span>🍽️</span>
                <h2>ยังไม่มีเมนูในขณะนี้</h2>
                <p>กรุณากลับมาใหม่ภายหลัง</p>
            </div>
        <?php else: ?>
            <h2 class="section-title">
                <span>🍽️</span> เมนูอาหาร
            </h2>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card" data-category="<?= htmlspecialchars($product['category']) ?>">
                        <div class="product-image">
                            <?php if ($product['image']): ?>
                                <img src="<?= htmlspecialchars($product['image']) ?>"
                                    alt="<?= htmlspecialchars($product['name']) ?>">
                            <?php else: ?>
                                🍲
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <div class="product-category">
                                <?= htmlspecialchars($product['category'] ?: 'อาหาร') ?>
                            </div>
                            <h3 class="product-name">
                                <?= htmlspecialchars($product['name']) ?>
                            </h3>
                            <p class="product-desc">
                                <?= htmlspecialchars($product['description'] ?: 'เมนูอร่อยจากร้าน') ?>
                            </p>
                            <div class="product-footer">
                                <div class="product-price">฿
                                    <?= number_format($product['price'], 0) ?>
                                </div>
                                <form method="POST" class="add-form">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <input type="hidden" name="add_to_cart" value="1">
                                    <button type="submit" class="add-to-cart">
                                        <span>🛒</span> เพิ่ม
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>🍳 ร้านอาหารตามสั่ง</h3>
                <p>ร้านอาหารตามสั่งออนไลน์<br>สั่งง่าย อร่อย ส่งถึงบ้าน</p>
            </div>
            <div class="footer-section">
                <h3>ติดต่อเรา</h3>
                <p>📍 ที่อยู่: 123 ถนนสุขุมวิท กรุงเทพฯ<br>
                    📞 โทร: 02-123-4567<br>
                    📱 LINE: @foodshop</p>
            </div>
            <div class="footer-section">
                <h3>เวลาเปิดให้บริการ</h3>
                <p>จันทร์ - อาทิตย์<br>
                    10:00 - 21:00 น.</p>
            </div>
        </div>
    </footer>

    <div class="toast" id="toast">✓ เพิ่มลงตะกร้าแล้ว!</div>

    <script>
        // Category filter
        document.querySelectorAll('.category-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');

                const category = this.dataset.category;
                document.querySelectorAll('.product-card').forEach(card => {
                    if (category === 'all' || card.dataset.category === category) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });

        // AJAX add to cart
        document.querySelectorAll('.add-form').forEach(form => {
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const btn = this.querySelector('.add-to-cart');
                const formData = new FormData(this);

                fetch('index.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            // Update cart count
                            let cartCount = document.querySelector('.cart-count');
                            if (!cartCount) {
                                cartCount = document.createElement('span');
                                cartCount.className = 'cart-count';
                                document.querySelector('.cart-btn').appendChild(cartCount);
                            }
                            cartCount.textContent = data.cartCount;

                            // Show toast
                            const toast = document.getElementById('toast');
                            toast.classList.add('show');
                            setTimeout(() => toast.classList.remove('show'), 2000);

                            // Button animation
                            btn.classList.add('added');
                            btn.innerHTML = '✓ เพิ่มแล้ว';
                            setTimeout(() => {
                                btn.classList.remove('added');
                                btn.innerHTML = '<span>🛒</span> เพิ่ม';
                            }, 1500);
                        }
                    });
            });
        });
    </script>
</body>

</html>