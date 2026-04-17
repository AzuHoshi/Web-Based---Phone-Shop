<?php
include 'config.php';

// 只有 admin 可以访问
require_admin();

$search = get('search');
$status = get('status');
$orders = getAllOrders($search, $status);

$detail = null;
$items = null;
if (get('detail')) {
    $detail = getOrder(get('detail'));
    if ($detail) $items = getOrderItems(get('detail'));
}

if (is_post() && post('action') == 'update') {
    updateStatus(post('order_id'), post('status'));
    redirect('orders_admin.php');
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Orders - Admin</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="js/main.js"></script>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📦 Order Management (Admin)</h1>
<div class="nav">
    <a href="index.php" class="btn btn-p">🏠 Home</a>
    <a href="products.php" class="btn btn-s">Products</a>
    <a href="stock_alert.php" class="btn btn-s">Stock Alert</a>
    <a href="users.php" class="btn btn-s">Users</a>
    <a href="cart.php" class="btn btn-s">🛒 Cart</a>
    <a href="profile.php" class="btn btn-s">👤 My Profile</a>
    <a href="logout.php" class="btn btn-s">Logout</a>
</div>
    </div>
    
    <?php if ($detail): ?>
        <div><a href="orders_admin.php" class="btn btn-s">← Back</a>
            <h2>Order #<?= h($detail->order_no) ?></h2>
            <table class="info-table">
                <tr><th>Order No</th><td><?= h($detail->order_no) ?></td></tr>
                <tr><th>Customer</th><td><?= h($detail->user_name) ?> (<?= h($detail->email) ?>)</td></tr>
                <tr><th>Total</th><td><?= price($detail->total_amount) ?></td></tr>
                <tr><th>Status</th><td>
                    <form method="POST" style="display:inline">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="order_id" value="<?= $detail->id ?>">
                        <select name="status">
                            <option value="pending" <?= $detail->status == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="paid" <?= $detail->status == 'paid' ? 'selected' : '' ?>>Paid</option>
                            <option value="shipped" <?= $detail->status == 'shipped' ? 'selected' : '' ?>>Shipped</option>
                            <option value="completed" <?= $detail->status == 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= $detail->status == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                        <button type="submit" class="btn btn-p">Update</button>
                    </form>
                  </td></tr>
                <tr><th>Date</th><td><?= date('Y-m-d H:i', strtotime($detail->created_at)) ?></td></tr>
            </table>
            <h3>Order Items</h3>
            <table class="table"><thead><tr><th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th></tr></thead>
            <tbody><?php foreach ($items as $i): ?><tr><td><?= h($i->pname) ?></td><td><?= price($i->price) ?></td><td><?= $i->quantity ?></td><td><?= price($i->price * $i->quantity) ?></td></tr><?php endforeach; ?></tbody>
            <tfoot><tr><th colspan="3">Total</th><th><?= price($detail->total_amount) ?></th></tr></tfoot>
            </table>
        </div>
    <?php else: ?>
        <div><h2>All Orders</h2>
            <form method="GET" class="filter-form">
                <input type="text" name="search" placeholder="Search..." value="<?= h($search) ?>">
                <select name="status">
                    <option value="">All</option>
                    <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="paid" <?= $status == 'paid' ? 'selected' : '' ?>>Paid</option>
                    <option value="shipped" <?= $status == 'shipped' ? 'selected' : '' ?>>Shipped</option>
                    <option value="completed" <?= $status == 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="cancelled" <?= $status == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
                <button type="submit" class="btn btn-p">Filter</button>
                <a href="orders_admin.php" class="btn btn-s">Reset</a>
            </form>
            <table class="table">
                <thead><tr><th>Order No</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($orders as $o): ?>
                    <tr>
                        <td><?= h($o->order_no) ?></td>
                        <td><?= h($o->user_name) ?><br><small><?= h($o->email) ?></small></td>
                        <td><?= price($o->total_amount) ?></td>
                        <td><span class="status status-<?= $o->status ?>"><?= ucfirst($o->status) ?></span></td>
                        <td><?= date('Y-m-d H:i', strtotime($o->created_at)) ?></td>
                        <td><a href="orders_admin.php?detail=<?= $o->id ?>" class="btn btn-e">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</body>
</html>