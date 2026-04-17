<?php
include 'config.php';

$search = get('search');
$cat = get('cat');
$min = get('min_price');
$max = get('max_price');
$products = getProducts($search, $cat, $min, $max);
$cats = getCats();
$cart_count = get_cart_count();
$wishlist_count = isset($_SESSION['user_id']) ? getWishlistCount($_SESSION['user_id']) : 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Phone Shop</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="js/main.js"></script>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📱 Phone Shop</h1>
        <div class="nav">
            <a href="index.php" class="btn btn-p">🏠 Home</a>
            <?php if (is_admin()): ?>
                <a href="products.php" class="btn btn-s">Products</a>
                <a href="orders_admin.php" class="btn btn-s">Orders Admin</a>
                <a href="stock_alert.php" class="btn btn-s">Stock Alert</a>
                <a href="users.php" class="btn btn-s">Users</a>
            <?php else: ?>
                <a href="orders_member.php" class="btn btn-s">My Orders</a>
            <?php endif; ?>
            <a href="wishlist.php" class="btn btn-s">❤️ Wishlist (<?= $wishlist_count ?>)</a>
            <a href="cart.php" class="btn btn-s">🛒 Cart (<?= $cart_count ?>)</a>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="profile.php" class="btn btn-s">👤 My Profile</a>
                <a href="logout.php" class="btn btn-s">Logout (<?= h($_SESSION['user_name'] ?? '') ?>)</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-s">Login</a>
                <a href="register.php" class="btn btn-s">Register</a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="filter-section">
        <h3>Filter Products</h3>
        <form method="GET" style="display: flex; flex-wrap: nowrap; gap: 15px; align-items: flex-end;">
            <div style="display: flex; flex-direction: column; gap: 5px;">
                <input type="text" name="search" placeholder="Search by name..." value="<?= h($search) ?>" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; width: 180px;">
            </div>
            <div style="display: flex; flex-direction: column; gap: 5px;">
                <select name="cat" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; width: 160px;">
                    <option value="">All Categories</option>
                    <?php foreach ($cats as $c): ?>
                        <option value="<?= $c->id ?>" <?= $cat == $c->id ? 'selected' : '' ?>><?= h($c->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display: flex; flex-direction: row; align-items: center; gap: 8px;">
                <input type="number" name="min_price" placeholder="Min Price" value="<?= h($min) ?>" step="0.01" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; width: 100px;">
                <span>-</span>
                <input type="number" name="max_price" placeholder="Max Price" value="<?= h($max) ?>" step="0.01" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; width: 100px;">
            </div>
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-p">Filter</button>
                <a href="index.php" class="btn btn-s">Reset</a>
            </div>
        </form>
    </div>
    
    <h2>Featured Products</h2>
    <div class="products">
        <?php foreach ($products as $p): ?>
            <div class="card" onclick="location='product_detail.php?id=<?= $p->id ?>'">
                <?php if ($p->image_path && file_exists("image/".$p->image_path)): ?>
                    <img src="image/<?= h($p->image_path) ?>" alt="<?= h($p->name) ?>">
                <?php else: ?>
                    <div class="no-img">📱 No Image</div>
                <?php endif; ?>
                <h3><?= h($p->name) ?></h3>
                <p class="price"><?= price($p->price) ?></p>
                <p>Stock: <?= $p->stock ?></p>
                <p class="specs"><?= $p->ram ?>GB RAM | <?= h($p->storage) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</div>
</body>
</html>