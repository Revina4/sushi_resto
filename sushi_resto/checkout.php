<?php
require_once 'config/database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan']);
    exit;
}

$name = $_POST['name'] ?? '';
$phone = $_POST['phone'] ?? '';

if (empty($name) || empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Nama dan nomor telepon harus diisi']);
    exit;
}

if (empty($_SESSION['cart'])) {
    echo json_encode(['success' => false, 'message' => 'Keranjang kosong']);
    exit;
}

// Hitung total
$total = 0;
$items = [];
foreach ($_SESSION['cart'] as $menu_id => $quantity) {
    $stmt = $pdo->prepare("SELECT * FROM menu WHERE id = ?");
    $stmt->execute([$menu_id]);
    $menu = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($menu) {
        $subtotal = $menu['price'] * $quantity;
        $total += $subtotal;
        $items[] = [
            'menu_id' => $menu_id,
            'quantity' => $quantity,
            'price' => $menu['price'],
            'name' => $menu['name']
        ];
    }
}

try {
    $pdo->beginTransaction();
    
    // Insert ke orders
    $stmt = $pdo->prepare("INSERT INTO orders (customer_name, customer_phone, total_price, status) VALUES (?, ?, ?, 'pending')");
    $stmt->execute([$name, $phone, $total]);
    $order_id = $pdo->lastInsertId();
    
    // Insert ke order_items
    $stmt = $pdo->prepare("INSERT INTO order_items (order_id, menu_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($items as $item) {
        $stmt->execute([$order_id, $item['menu_id'], $item['quantity'], $item['price']]);
    }
    
    $pdo->commit();
    
    // Kosongkan keranjang
    $_SESSION['cart'] = [];
    
    echo json_encode(['success' => true, 'message' => 'Pesanan berhasil! Terima kasih.']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Gagal memproses pesanan: ' . $e->getMessage()]);
}
?>