<?php
include 'config.php';

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$order_id || !isset($_SESSION['user_id'])) {
    redirect('index.php');
}

// Get order details
$stm = $_db->prepare("
    SELECT o.*, u.name as user_name, u.email 
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = ? AND o.user_id = ?
");
$stm->execute([$order_id, $_SESSION['user_id']]);
$order = $stm->fetch();

if (!$order) {
    redirect('index.php');
}

// Get order items
$stm = $_db->prepare("
    SELECT oi.*, p.name as product_name, p.image_path
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stm->execute([$order_id]);
$items = $stm->fetchAll();

$wishlist_count = isset($_SESSION['user_id']) ? getWishlistCount($_SESSION['user_id']) : 0;
$cart_count = get_cart_count();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Order Success - Phone Shop</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .success-container { max-width: 800px; margin: 50px auto; text-align: center; }
        .success-icon { font-size: 80px; color: #2ecc71; margin-bottom: 20px; }
        .order-card { background: #f8f9fa; border-radius: 12px; padding: 30px; margin-top: 30px; text-align: left; }
        .order-details { margin: 20px 0; }
        .order-details p { margin: 8px 0; }
        .status-pending { background: #fff3cd; color: #856404; padding: 4px 12px; border-radius: 20px; display: inline-block; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📱 Phone Shop</h1>
        <div class="nav">
            <a href="index.php" class="btn btn-s">Home</a>
            <a href="products.php" class="btn btn-s">Products</a>
            <?php if (is_admin()): ?>
                <a href="orders_admin.php" class="btn btn-s">Orders Admin</a>
                <a href="stock_alert.php" class="btn btn-s">Stock Alert</a>
                <a href="users.php" class="btn btn-s">Users</a>
            <?php else: ?>
                <a href="orders_member.php" class="btn btn-s">My Orders</a>
            <?php endif; ?>
            <a href="wishlist.php" class="btn btn-s">❤️ Wishlist (<?= $wishlist_count ?>)</a>
            <a href="cart.php" class="btn btn-s">🛒 Cart (<?= $cart_count ?>)</a>
            <a href="logout.php" class="btn btn-s">Logout</a>
        </div>
    </div>
    
    <div class="success-container">
        <div class="success-icon">✅</div>
        <h2>Order Placed Successfully!</h2>
        <p>Thank you for your purchase. Your order has been received.</p>
        
        <div class="order-card">
            <h3>Order #<?= h($order->order_no) ?></h3>
            <div class="order-details">
                <p><strong>Order Date:</strong> <?= date('Y-m-d H:i', strtotime($order->created_at)) ?></p>
                <p><strong>Total Amount:</strong> <?= price($order->total_amount) ?></p>
                <p><strong>Status:</strong> <span class="status-pending"><?= ucfirst($order->status) ?></span></p>
            </div>
            
            <h4>Order Items</h4>
            <table class="table">
                <thead><tr><th>Product</th><th>Quantity</th><th>Price</th><th>Subtotal</th></tr></thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= h($item->product_name) ?></td>
                        <td><?= $item->quantity ?></td>
                        <td><?= price($item->price) ?></td>
                        <td><?= price($item->price * $item->quantity) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <a href="index.php" class="btn btn-p">Continue Shopping</a>
        <a href="orders_member.php" class="btn btn-s">View My Orders</a>
    </div>
</div>
</body>
</html>