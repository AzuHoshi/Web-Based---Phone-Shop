<?php
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    redirect('login.php');
}
$user_id = $_SESSION['user_id'];

if (get('cancel')) {
    cancelOrder(get('cancel'), $user_id);
    redirect('orders_member.php');
}

$detail = null;
$items = null;
if (get('detail')) {
    $detail = getUserOrder(get('detail'), $user_id);
    if ($detail) $items = getOrderItems(get('detail'));
}

$orders = getUserOrders($user_id);
$cart_count = get_cart_count();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Orders</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="js/main.js"></script>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📋 My Orders (Member)</h1>
        <div class="nav">
            <a href="index.php" class="btn btn-s">🏠 Home</a>
            <?php if (is_admin()): ?>
                <a href="orders_admin.php" class="btn btn-s">Orders Admin</a>
                <a href="categories.php" class="btn btn-s">Categories</a>
                <a href="stock_alert.php" class="btn btn-s">Stock Alert</a>
            <?php endif; ?>
            <a href="cart.php" class="btn btn-p">🛒 Cart (<?= $cart_count ?>)</a>
            <a href="profile.php" class="btn btn-s">👤 My Profile</a>
            <a href="logout.php" class="btn btn-s">Logout (<?= h($_SESSION['user_name'] ?? '') ?>)</a>
        </div>
    </div>
    
    <?php if ($detail): ?>
        <div><a href="orders_member.php" class="btn btn-s">← Back</a>
            <h2>Order #<?= h($detail->order_no) ?></h2>
            <table class="info-table">
                <tr><th>Order No</th><td><?= h($detail->order_no) ?></td></tr>
                <tr><th>Total</th><td><?= price($detail->total_amount) ?></td></tr>
                <tr><th>Status</th><td><span class="status status-<?= $detail->status ?>"><?= ucfirst($detail->status) ?></span></td></tr>
                <tr><th>Date</th><td><?= date('Y-m-d H:i', strtotime($detail->created_at)) ?></td></tr>
            </table>
            <h3>Order Items</h3>
            <table class="table"><thead><tr><th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th></tr></thead>
            <tbody><?php foreach ($items as $i): ?><tr><td><?= h($i->pname) ?></td><td><?= price($i->price) ?></td><td><?= $i->quantity ?></td><td><?= price($i->price * $i->quantity) ?></td></tr><?php endforeach; ?></tbody>
            <tfoot><tr><th colspan="3">Total</th><th><?= price($detail->total_amount) ?></th></tr></tfoot>
            </table>
        </div>
    <?php else: ?>
        <div><h2>My Orders</h2>
            <table class="table"><thead><tr><th>Order No</th><th>Total</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
            <tbody><?php foreach ($orders as $o): ?><tr><td><?= h($o->order_no) ?></td><td><?= price($o->total_amount) ?></td><td><span class="status status-<?= $o->status ?>"><?= ucfirst($o->status) ?></span></td><td><?= date('Y-m-d H:i', strtotime($o->created_at)) ?></td>
            <td><a href="orders_member.php?detail=<?= $o->id ?>" class="btn btn-e">View</a> <?php if ($o->status == 'pending' || $o->status == 'paid'): ?><a href="orders_member.php?cancel=<?= $o->id ?>" class="btn btn-d" data-confirm="Cancel?">Cancel</a><?php endif; ?></td></tr><?php endforeach; ?></tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</body>
</html>