<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$_POST['status'], $_POST['order_id']]);
    header('Location: dashboard.php');
    exit;
}

$orders = $pdo->query("SELECT * FROM orders ORDER BY order_date DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: white;
            width: 90%;
            max-width: 650px;
            border-radius: 20px;
            padding: 20px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ddd;
            margin-bottom: 15px;
        }
        .close {
            cursor: pointer;
            font-size: 28px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #f3e8ff;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="logo">🍣 Sushi Modern Admin</div>
            <div class="nav-links">
                <a href="dashboard.php" class="active">Pesanan</a>
                <a href="manage_menu.php">Kelola Menu</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </nav>
    <div class="admin-container">
        <h1 class="section-title">Manajemen Pesanan</h1>
        <table class="admin-table">
            <thead>
                <tr><th>ID</th><th>Pelanggan</th><th>Total</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td>#<?= $order['id'] ?></td>
                    <td><?= htmlspecialchars($order['customer_name']) ?></td>
                    <td>Rp <?= number_format($order['total_price'],0,',','.') ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <select name="status" onchange="this.form.submit()">
                                <option value="pending" <?= $order['status']=='pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="completed" <?= $order['status']=='completed' ? 'selected' : '' ?>>Completed</option>
                                <option value="cancelled" <?= $order['status']=='cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                            <input type="hidden" name="update_status" value="1">
                        </form>
                    </td>
                    <td><button onclick="showDetail(<?= $order['id'] ?>)" class="btn-add">Detail</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div id="detailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-receipt"></i> Detail Pesanan</h3>
                <span class="close">&times;</span>
            </div>
            <div id="modalBody">Loading...</div>
        </div>
    </div>

    <script>
        const modal = document.getElementById('detailModal');
        const modalBody = document.getElementById('modalBody');
        const closeSpan = document.querySelector('.close');

        closeSpan.onclick = function() { modal.style.display = 'none'; }
        window.onclick = function(e) { if (e.target === modal) modal.style.display = 'none'; }

        async function showDetail(orderId) {
            modal.style.display = 'flex';
            modalBody.innerHTML = '<p><i class="fas fa-spinner fa-spin"></i> Memuat data...</p>';
            try {
                const response = await fetch(`get_order_detail.php?id=${orderId}`);
                const data = await response.json();
                if (data.error) throw new Error(data.error);
                const order = data.order;
                const items = data.items;
                
                let itemsHtml = '<table><thead><tr><th>Menu</th><th>Harga</th><th>Jumlah</th><th>Subtotal</th></tr></thead><tbody>';
                items.forEach(item => {
                    itemsHtml += `<tr>
                        <td>${escapeHtml(item.name)}</td>
                        <td>Rp ${Number(item.price).toLocaleString('id-ID')}</td>
                        <td>${item.quantity}</td>
                        <td>Rp ${(item.price * item.quantity).toLocaleString('id-ID')}</td>
                    </tr>`;
                });
                itemsHtml += '</tbody></table>';
                
                const statusBadge = order.status === 'completed' ? '<span style="background:#4caf50; color:white; padding:3px 10px; border-radius:20px;">Completed</span>' : 
                                    (order.status === 'cancelled' ? '<span style="background:#f44336; color:white; padding:3px 10px; border-radius:20px;">Cancelled</span>' : 
                                    '<span style="background:#ff9800; color:white; padding:3px 10px; border-radius:20px;">Pending</span>');
                
                modalBody.innerHTML = `
                    <p><strong>ID Pesanan:</strong> #${order.id}</p>
                    <p><strong>Pelanggan:</strong> ${escapeHtml(order.customer_name)}</p>
                    <p><strong>Telepon:</strong> ${escapeHtml(order.customer_phone)}</p>
                    <p><strong>Tanggal:</strong> ${new Date(order.order_date).toLocaleString('id-ID')}</p>
                    <p><strong>Status:</strong> ${statusBadge}</p>
                    <h4>Item Pesanan:</h4>
                    ${itemsHtml}
                    <p style="text-align:right; font-weight:bold; margin-top:15px; padding-top:10px; border-top:2px solid #f3e8ff;">Total: Rp ${Number(order.total_price).toLocaleString('id-ID')}</p>
                `;
            } catch(err) {
                modalBody.innerHTML = `<p style="color:red"><i class="fas fa-exclamation-circle"></i> Gagal: ${err.message}<br>Pastikan file get_order_detail.php ada dan MySQL berjalan.</p>`;
            }
        }
        
        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            });
        }
    </script>
</body>
</html>