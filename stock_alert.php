<?php
include 'config.php';

// 只有 admin 可以访问
require_admin();

$low_stock_threshold = 5;

// Get low stock products
$stm = $_db->prepare("
    SELECT p.*, c.name as cat_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.stock <= ? AND p.stock > 0 
    ORDER BY p.stock ASC
");
$stm->execute([$low_stock_threshold]);
$low_stock = $stm->fetchAll();

// Get out of stock
$stm = $_db->prepare("
    SELECT p.*, c.name as cat_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.stock = 0 
    ORDER BY p.name ASC
");
$stm->execute();
$out_of_stock = $stm->fetchAll();

// Get well stocked stats
$stm = $_db->prepare("
    SELECT COUNT(*) as count, MIN(stock) as min_stock, MAX(stock) as max_stock, AVG(stock) as avg_stock
    FROM products 
    WHERE stock > ?
");
$stm->execute([$low_stock_threshold]);
$stock_stats = $stm->fetch();

$cart_count = get_cart_count();
$wishlist_count = isset($_SESSION['user_id']) ? getWishlistCount($_SESSION['user_id']) : 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Stock Alert - Phone Shop</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .stat-cards { display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap; }
        .stat-card { flex: 1; background: white; padding: 20px; border-radius: 12px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #eee; }
        .stat-card h3 { margin-bottom: 10px; color: #666; }
        .stat-card .number { font-size: 32px; font-weight: bold; color: #2c3e50; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .danger { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .stock-badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .stock-low { background: #ffc107; color: #333; }
        .stock-zero { background: #dc3545; color: white; }
        .stock-ok { background: #2ecc71; color: white; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📊 Stock Alert System</h1>
        <div class="nav">
            <a href="index.php" class="btn btn-p">🏠 Home</a>
            <a href="products.php" class="btn btn-s">Products</a>
            <a href="orders_admin.php" class="btn btn-s">Orders Admin</a>
            <a href="users.php" class="btn btn-s">Users</a>
            <a href="wishlist.php" class="btn btn-s">❤️ Wishlist (<?= $wishlist_count ?>)</a>
            <a href="cart.php" class="btn btn-s">🛒 Cart (<?= $cart_count ?>)</a>
            <a href="profile.php" class="btn btn-s">👤 My Profile</a>
            <a href="logout.php" class="btn btn-s">Logout</a>
        </div>
    </div>
    
    <h2>Inventory Overview</h2>
    
    <div class="stat-cards">
        <div class="stat-card">
            <h3>📦 Low Stock Items</h3>
            <div class="number"><?= count($low_stock) ?></div>
            <small>Stock ≤ <?= $low_stock_threshold ?></small>
        </div>
        <div class="stat-card">
            <h3>❌ Out of Stock</h3>
            <div class="number"><?= count($out_of_stock) ?></div>
            <small>Need immediate restock</small>
        </div>
        <div class="stat-card">
            <h3>📊 Average Stock</h3>
            <div class="number"><?= round($stock_stats->avg_stock ?? 0) ?></div>
            <small>Across all products</small>
        </div>
    </div>
    
    <?php if (!empty($out_of_stock)): ?>
    <div class="danger">
        <h3>⚠️ CRITICAL: Out of Stock Items</h3>
        <table class="table">
            <thead>
                <tr><th>Product</th><th>Category</th><th>Stock</th><th>Action</th>
            </thead>
            <tbody>
                <?php foreach ($out_of_stock as $p): ?>
                <tr>
                    <td><?= h($p->name) ?></td>
                    <td><?= h($p->cat_name ?? '-') ?></td>
                    <td><span class="stock-badge stock-zero">0</span></td>
                    <td><a href="products.php?edit_product=<?= $p->id ?>" class="btn btn-p">Restock</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($low_stock)): ?>
    <div class="warning">
        <h3>⚠️ Low Stock Alert (≤ <?= $low_stock_threshold ?> units)</h3>
        <table class="table">
            <thead>
                <tr><th>Product</th><th>Category</th><th>Stock</th><th>Action</th>
            </thead>
            <tbody>
                <?php foreach ($low_stock as $p): ?>
                <tr>
                    <td><?= h($p->name) ?></td>
                    <td><?= h($p->cat_name ?? '-') ?></td>
                    <td><span class="stock-badge stock-low"><?= $p->stock ?></span></td>
                    <td><a href="products.php?edit_product=<?= $p->id ?>" class="btn btn-e">Update Stock</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <?php if (empty($low_stock) && empty($out_of_stock)): ?>
    <div class="alert alert-s">
        ✅ All products have sufficient stock! Great job!
    </div>
    <?php endif; ?>
    
    <div style="margin-top: 20px; display: flex; gap: 10px;">
        <a href="products.php" class="btn btn-p">Manage Products</a>
        <a href="products.php?add_product=1" class="btn btn-s">+ Add New Product</a>
    </div>
    
    <div style="margin-top: 30px; padding: 15px 20px; background: #f8f9fa; border-left: 4px solid #3498db; border-radius: 5px; font-size: 14px;">
        <p><strong>📌 About Stock Alert:</strong></p>
        <p>✅ <span style="color: #2ecc71; font-weight: bold;">Normal Stock</span> - Stock &gt; <?= $low_stock_threshold ?> units</p>
        <p>⚠️ <span style="color: #f39c12; font-weight: bold;">Low Stock</span> - Stock ≤ <?= $low_stock_threshold ?> units (needs attention)</p>
        <p>❌ <span style="color: #e74c3c; font-weight: bold;">Out of Stock</span> - Stock = 0 (immediate action required)</p>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="js/main.js"></script>
</body>
</html>