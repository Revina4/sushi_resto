<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Handle tambah/edit/hapus menu
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        $stmt = $pdo->prepare("DELETE FROM menu WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        header('Location: manage_menu.php');
        exit;
    }
    
    $id = $_POST['id'] ?? null;
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $image_url = $_POST['image_url'];
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    
    if ($id) {
        // Update
        $stmt = $pdo->prepare("UPDATE menu SET name=?, description=?, price=?, image_url=?, is_available=? WHERE id=?");
        $stmt->execute([$name, $description, $price, $image_url, $is_available, $id]);
    } else {
        // Insert
        $stmt = $pdo->prepare("INSERT INTO menu (name, description, price, image_url, is_available) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $description, $price, $image_url, $is_available]);
    }
    header('Location: manage_menu.php');
    exit;
}

$menu = $pdo->query("SELECT * FROM menu ORDER BY id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Menu - Sushi Modern</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="logo">🍣 Sushi Modern Admin</div>
            <div class="nav-links">
                <a href="dashboard.php">Pesanan</a>
                <a href="manage_menu.php" class="active">Kelola Menu</a>
                <a href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="admin-container">
        <h1 class="section-title">Kelola Menu Sushi</h1>
        
        <!-- Form Tambah/Edit -->
        <div style="background: #f9f5ff; padding: 20px; border-radius: var(--radius-sm); margin-bottom: 30px;">
            <h3>Tambah Menu Baru</h3>
            <form method="POST" style="display: grid; gap: 15px; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
                <input type="hidden" name="id" value="">
                <input type="text" name="name" placeholder="Nama Menu" required>
                <textarea name="description" placeholder="Deskripsi" rows="2"></textarea>
                <input type="number" step="0.01" name="price" placeholder="Harga" required>
                <input type="url" name="image_url" placeholder="URL Gambar">
                <label><input type="checkbox" name="is_available" value="1" checked> Tersedia</label>
                <button type="submit" class="btn-add">Simpan Menu</button>
            </form>
        </div>
        
        <!-- Daftar Menu -->
        <table class="admin-table">
            <thead>
                <tr><th>ID</th><th>Nama</th><th>Harga</th><th>Status</th><th>Aksi</th></tr>
            </thead>
            <tbody>
                <?php foreach ($menu as $item): ?>
                <tr>
                    <td><?= $item['id'] ?></td>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td>Rp <?= number_format($item['price'], 0, ',', '.') ?></td>
                    <td><?= $item['is_available'] ? 'Tersedia' : 'Tidak Tersedia' ?></td>
                    <td>
                        <button onclick="editMenu(<?= htmlspecialchars(json_encode($item)) ?>)" class="btn-add" style="padding: 5px 10px;">Edit</button>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="id" value="<?= $item['id'] ?>">
                            <button type="submit" name="delete" value="1" class="btn-add" style="background: #ff6b6b; padding: 5px 10px;" onclick="return confirm('Hapus menu ini?')">Hapus</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
    function editMenu(item) {
        const form = document.querySelector('form');
        form.querySelector('[name="id"]').value = item.id;
        form.querySelector('[name="name"]').value = item.name;
        form.querySelector('[name="description"]').value = item.description;
        form.querySelector('[name="price"]').value = item.price;
        form.querySelector('[name="image_url"]').value = item.image_url;
        form.querySelector('[name="is_available"]').checked = item.is_available == 1;
        form.querySelector('button[type="submit"]').innerText = 'Update Menu';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    </script>
</body>
</html>