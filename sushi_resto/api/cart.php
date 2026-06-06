<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
    exit;
}

$action = $_POST['action'] ?? '';
$menu_id = $_POST['menu_id'] ?? 0;

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

switch ($action) {
    case 'add':
        if (isset($_SESSION['cart'][$menu_id])) {
            $_SESSION['cart'][$menu_id]++;
        } else {
            $_SESSION['cart'][$menu_id] = 1;
        }
        $message = 'Item ditambahkan ke keranjang';
        break;
        
    case 'update':
        $change = (int)$_POST['quantity'];
        $new_qty = ($_SESSION['cart'][$menu_id] ?? 0) + $change;
        if ($new_qty <= 0) {
            unset($_SESSION['cart'][$menu_id]);
            $message = 'Item dihapus dari keranjang';
        } else {
            $_SESSION['cart'][$menu_id] = $new_qty;
            $message = 'Jumlah diperbarui';
        }
        break;
        
    case 'remove':
        unset($_SESSION['cart'][$menu_id]);
        $message = 'Item dihapus';
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Aksi tidak valid']);
        exit;
}

// Generate HTML keranjang terbaru
$cart_details = [];
$total_price = 0;
foreach ($_SESSION['cart'] as $id => $qty) {
    $stmt = $pdo->prepare("SELECT * FROM menu WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($item) {
        $item['quantity'] = $qty;
        $item['subtotal'] = $item['price'] * $qty;
        $cart_details[] = $item;
        $total_price += $item['subtotal'];
    }
}

ob_start();
if (empty($cart_details)): ?>
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
<?php endif;
$cart_html = ob_get_clean();

echo json_encode([
    'success' => true,
    'message' => $message,
    'cart_html' => $cart_html,
    'total_price' => $total_price,
    'total_price_formatted' => number_format($total_price, 0, ',', '.')
]);
?>