<?php
session_start();
require_once 'config/database.php';

// Inisialisasi keranjang jika belum ada
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Ambil data menu dari database
$stmt = $pdo->query("SELECT * FROM menu WHERE is_available = 1 ORDER BY id");
$menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Ambil detail keranjang
$cart_details = [];
$total_price = 0;
foreach ($_SESSION['cart'] as $menu_id => $quantity) {
    $stmt = $pdo->prepare("SELECT * FROM menu WHERE id = ?");
    $stmt->execute([$menu_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($item) {
        $item['quantity'] = $quantity;
        $item['subtotal'] = $item['price'] * $quantity;
        $cart_details[] = $item;
        $total_price += $item['subtotal'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sushi Modern | Restoran Sushi Premium</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="logo">
                <span>🍣</span> Sushi Modern
            </div>
            <div class="nav-links">
                <a href="index.php" class="active">Menu</a>
                <a href="admin/login.php">Admin</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="main-grid">
            <!-- Bagian Menu -->
            <div>
                <h1 class="section-title">Our Premium Sushi</h1>
                <div class="menu-grid" id="menuGrid">
                    <?php foreach ($menu_items as $item): ?>
                    <div class="menu-card" data-id="<?= $item['id'] ?>">
                        <!-- PERBAIKAN: tambahkan onerror fallback -->
                        <img src="<?= htmlspecialchars($item['image_url']) ?>" 
                             alt="<?= htmlspecialchars($item['name']) ?>" 
                             class="menu-image"
                             onerror="this.onerror=null; this.src='https://placehold.co/400x300/9b6bcf/white?text=' + encodeURIComponent('<?= htmlspecialchars($item['name']) ?>');">
                        <div class="menu-info">
                            <h3 class="menu-name"><?= htmlspecialchars($item['name']) ?></h3>
                            <p class="menu-desc"><?= htmlspecialchars($item['description']) ?></p>
                            <div class="menu-footer">
                                <span class="menu-price">Rp <?= number_format($item['price'], 0, ',', '.') ?></span>
                                <button class="btn-add" onclick="addToCart(<?= $item['id'] ?>)">
                                    <i class="fas fa-plus"></i> Pesan
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Sidebar Keranjang -->
            <div class="cart-sidebar">
                <div class="cart-header">
                    <i class="fas fa-shopping-bag"></i> Keranjang Anda
                </div>
                <div id="cartItemsContainer">
                    <?php if (empty($cart_details)): ?>
                        <div class="empty-cart">
                            <i class="fas fa-sushi" style="font-size: 48px; opacity: 0.5;"></i>
                            <p>Keranjang masih kosong</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($cart_details as $item): ?>
                        <div class="cart-item" data-id="<?= $item['id'] ?>">
                            <div class="cart-item-info">
                                <h4><?= htmlspecialchars($item['name']) ?></h4>
                                <div class="cart-item-price">Rp <?= number_format($item['price'], 0, ',', '.') ?></div>
                            </div>
                            <div class="cart-item-actions">
                                <button class="qty-btn" onclick="updateQuantity(<?= $item['id'] ?>, 'decrease')">-</button>
                                <span class="item-qty" id="qty-<?= $item['id'] ?>"><?= $item['quantity'] ?></span>
                                <button class="qty-btn" onclick="updateQuantity(<?= $item['id'] ?>, 'increase')">+</button>
                                <i class="fas fa-trash-alt remove-item" onclick="removeFromCart(<?= $item['id'] ?>)"></i>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="cart-total" id="cartTotal">
                    <div class="total-row">
                        <span>Total:</span>
                        <span class="total-price">Rp <?= number_format($total_price, 0, ',', '.') ?></span>
                    </div>
                </div>

                <form id="checkoutForm" class="checkout-form">
                    <div class="form-group">
                        <input type="text" id="customerName" placeholder="Nama Lengkap" required>
                    </div>
                    <div class="form-group">
                        <input type="tel" id="customerPhone" placeholder="Nomor Telepon" required>
                    </div>
                    <button type="submit" class="btn-checkout" id="checkoutBtn">
                        <i class="fas fa-arrow-right"></i> Checkout Sekarang
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
    // Fungsi untuk update keranjang via AJAX
    async function updateCart(action, menuId, quantity = null) {
        const formData = new FormData();
        formData.append('action', action);
        formData.append('menu_id', menuId);
        if (quantity !== null) formData.append('quantity', quantity);
        
        const response = await fetch('api/cart.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            // Refresh tampilan keranjang
            document.getElementById('cartItemsContainer').innerHTML = data.cart_html;
            document.querySelector('.total-price').innerText = 'Rp ' + data.total_price_formatted;
            showToast(data.message);
        }
    }
    
    function addToCart(menuId) {
        updateCart('add', menuId);
    }
    
    function updateQuantity(menuId, action) {
        if (action === 'increase') {
            updateCart('update', menuId, 1);
        } else {
            updateCart('update', menuId, -1);
        }
    }
    
    function removeFromCart(menuId) {
        updateCart('remove', menuId);
    }
    
    // Proses checkout
    document.getElementById('checkoutForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const name = document.getElementById('customerName').value;
        const phone = document.getElementById('customerPhone').value;
        
        const response = await fetch('checkout.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `name=${encodeURIComponent(name)}&phone=${encodeURIComponent(phone)}`
        });
        const result = await response.json();
        
        if (result.success) {
            showToast(result.message);
            // Refresh keranjang
            document.getElementById('cartItemsContainer').innerHTML = '<div class="empty-cart"><i class="fas fa-sushi" style="font-size: 48px; opacity: 0.5;"></i><p>Keranjang masih kosong</p></div>';
            document.querySelector('.total-price').innerText = 'Rp 0';
            document.getElementById('customerName').value = '';
            document.getElementById('customerPhone').value = '';
        } else {
            showToast(result.message, 'error');
        }
    });
    
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> ${message}`;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }
    </script>
</body>
</html>