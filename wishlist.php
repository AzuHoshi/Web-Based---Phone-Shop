<?php
include 'config.php';

// 需要登录才能访问收藏夹
require_login();

$user_id = $_SESSION['user_id'];
$message = '';

// 处理移除收藏
if (isset($_GET['remove'])) {
    $product_id = intval($_GET['remove']);
    if (removeFromWishlist($user_id, $product_id)) {
        $message = '<div class="alert alert-s">✅ Product removed from wishlist!</div>';
    }
}

// 处理清空收藏夹
if (isset($_GET['clear'])) {
    global $_db;
    $stm = $_db->prepare("DELETE FROM wishlist WHERE user_id = ?");
    $stm->execute([$user_id]);
    $message = '<div class="alert alert-s">✅ Wishlist cleared!</div>';
}

// 处理添加商品到购物车
if (isset($_GET['add_to_cart'])) {
    $product_id = intval($_GET['add_to_cart']);
    add_to_cart($product_id, 1, '', '');
    redirect('cart.php');
}

$wishlist_items = getWishlistItems($user_id);
$wishlist_count = getWishlistCount($user_id);
$cart_count = get_cart_count();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Wishlist - Phone Shop</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .wishlist-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .wishlist-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #eee; }
        .wishlist-header h2 { font-size: 28px; color: #333; margin: 0; }
        .wishlist-count { background: #e74c3c; color: white; padding: 5px 12px; border-radius: 20px; font-size: 14px; }
        .wishlist-grid { display: flex; flex-wrap: wrap; gap: 20px; }
        .wishlist-card { width: calc(25% - 15px); border: 1px solid #ddd; border-radius: 8px; padding: 15px; text-align: center; background: white; transition: transform 0.2s, box-shadow 0.2s; }
        .wishlist-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .wishlist-card img { width: 100%; height: 150px; object-fit: contain; border-radius: 5px; background: #f5f5f5; }
        .wishlist-card .no-img { height: 150px; display: flex; align-items: center; justify-content: center; background: #f5f5f5; border-radius: 5px; color: #999; }
        .wishlist-card h3 { margin: 10px 0; font-size: 16px; }
        .wishlist-card .price { color: #e74c3c; font-size: 18px; font-weight: bold; margin: 5px 0; }
        .wishlist-card .specs { font-size: 12px; color: #666; margin-top: 5px; }
        .wishlist-card .actions { margin-top: 10px; display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; }
        .empty-wishlist { text-align: center; padding: 60px 20px; }
        .empty-wishlist .icon { font-size: 80px; margin-bottom: 20px; }
        .btn-heart { background: #e74c3c; color: white; padding: 8px 12px; border-radius: 5px; text-decoration: none; font-size: 12px; border: none; cursor: pointer; display: inline-block; }
        .btn-heart:hover { background: #c0392b; }
        @media (max-width: 1024px) { .wishlist-card { width: calc(33.33% - 14px); } }
        @media (max-width: 768px) { .wishlist-card { width: calc(50% - 10px); } }
        @media (max-width: 480px) { .wishlist-card { width: 100%; } }
    </style>
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
            <a href="logout.php" class="btn btn-s">Logout (<?= h($_SESSION['user_name'] ?? '') ?>)</a>
        </div>
    </div>
    
    <div class="wishlist-container">
        <div class="wishlist-header">
            <h2>❤️ My Wishlist</h2>
            <?php if ($wishlist_count > 0): ?>
                <span class="wishlist-count"><?= $wishlist_count ?> item(s)</span>
            <?php endif; ?>
        </div>
        
        <?= $message ?>
        
        <?php if (empty($wishlist_items)): ?>
            <div class="empty-wishlist">
                <div class="icon">❤️</div>
                <h3>Your wishlist is empty</h3>
                <p>Browse our products and add your favorites to wishlist!</p>
                <a href="index.php" class="btn btn-p" style="margin-top: 20px; display: inline-block;">Browse Products</a>
            </div>
        <?php else: ?>
            <div class="wishlist-grid">
                <?php foreach ($wishlist_items as $item): ?>
                    <div class="wishlist-card">
                        <?php if ($item->image_path && file_exists("image/".$item->image_path)): ?>
                            <img src="image/<?= h($item->image_path) ?>" alt="<?= h($item->name) ?>">
                        <?php else: ?>
                            <div class="no-img">📱 No Image</div>
                        <?php endif; ?>
                        <h3><?= h($item->name) ?></h3>
                        <p class="price"><?= price($item->price) ?></p>
                        <p class="specs"><?= $item->ram ?>GB RAM | <?= h($item->storage) ?></p>
                        <p>Stock: <?= $item->stock ?></p>
                        <div class="actions">
                            <a href="product_detail.php?id=<?= $item->product_id ?>" class="btn btn-e">View</a>
                            <a href="wishlist.php?add_to_cart=<?= $item->product_id ?>" class="btn btn-p">Add to Cart</a>
                            <a href="wishlist.php?remove=<?= $item->product_id ?>" class="btn-heart" onclick="return confirm('Remove from wishlist?')">Remove</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div style="margin-top: 20px; text-align: right;">
                <a href="wishlist.php?clear=1" class="btn btn-d" onclick="return confirm('Clear entire wishlist?')">Clear Wishlist</a>
            </div>
        <?php endif; ?>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="js/main.js"></script>
</body>
</html>