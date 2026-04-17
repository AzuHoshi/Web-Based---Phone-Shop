<?php
include 'config.php';

// 只有 admin 可以访问
require_admin();

// 处理锁定/解锁操作
if (get('block')) {
    $user_id = intval(get('block'));
    blockUser($user_id);
    temp('info', 'User has been blocked successfully');
    redirect('users.php');
}

if (get('unblock')) {
    $user_id = intval(get('unblock'));
    unblockUser($user_id);
    temp('info', 'User has been unblocked successfully');
    redirect('users.php');
}

// 搜索
$search = get('search');
$users = getAllUsers($search);
$cart_count = get_cart_count();
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Management - Phone Shop</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-active { background: #2ecc71; color: white; }
        .status-blocked { background: #e74c3c; color: white; }
        .status-locked { background: #f39c12; color: white; }
        
        .note-box {
            margin-top: 30px;
            padding: 15px 20px;
            background: #f8f9fa;
            border-left: 4px solid #3498db;
            border-radius: 5px;
            font-size: 14px;
        }
        .note-box p {
            margin: 5px 0;
        }
        .note-box .status-active-note { color: #2ecc71; font-weight: bold; }
        .note-box .status-locked-note { color: #f39c12; font-weight: bold; }
        .note-box .status-blocked-note { color: #e74c3c; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>👥 User Management</h1>
        <div class="nav">
            <a href="index.php" class="btn btn-p">🏠 Home</a>
            <a href="products.php" class="btn btn-s">Products</a>
            <a href="orders_admin.php" class="btn btn-s">Orders Admin</a>
            <a href="stock_alert.php" class="btn btn-s">Stock Alert</a>
            <a href="cart.php" class="btn btn-s">🛒 Cart (<?= $cart_count ?>)</a>
            <a href="profile.php" class="btn btn-s">👤 My Profile</a>
            <a href="logout.php" class="btn btn-s">Logout</a>
        </div>
    </div>
    
    <?php if ($msg = temp('info')): ?>
        <div class="alert alert-s"><?= h($msg) ?></div>
    <?php endif; ?>
    
    <h2>Member Accounts</h2>
    
    <!-- 搜索表单 -->
    <form method="GET" class="filter-form" style="margin-bottom: 20px;">
        <input type="text" name="search" placeholder="Search by name or email..." value="<?= h($search) ?>" style="width: 250px;">
        <button type="submit" class="btn btn-p">Search</button>
        <a href="users.php" class="btn btn-s">Reset</a>
    </form>
    
    <!-- 用户表格 -->
    <table class="table">
        <thead>
            <tr><th>ID</th><th>Name</th><th>Email</th><th>Status</th><th>Login Attempts</th><th>Registered</th><th>Action</th>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr><td colspan="7" style="text-align: center;">No users found</td></tr>
            <?php else: ?>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= $u->id ?></td>
                    <td><?= h($u->name) ?></td>
                    <td><?= h($u->email) ?></td>
                    <td>
                        <?php if ($u->is_blocked == 1): ?>
                            <span class="status-badge status-blocked">Blocked</span>
                        <?php elseif ($u->locked_until && strtotime($u->locked_until) > time()): ?>
                            <span class="status-badge status-locked">Temp Locked</span>
                        <?php else: ?>
                            <span class="status-badge status-active">Active</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $u->login_attempts ?> / 3</td>
                    <td><?= date('Y-m-d', strtotime($u->created_at)) ?></td>
                    <td>
                        <?php if ($u->is_blocked == 1): ?>
                            <a href="users.php?unblock=<?= $u->id ?>" class="btn btn-e" data-confirm="Unblock this user?">Unblock</a>
                        <?php else: ?>
                            <a href="users.php?block=<?= $u->id ?>" class="btn btn-d" data-confirm="Block this user? They will not be able to login.">Block</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- 说明框 -->
    <div class="note-box">
        <p><strong>📌 Status Explanation:</strong></p>
        <p>✅ <span class="status-active-note">Active</span>     - User can login normally</p>
        <p>⚠️ <span class="status-locked-note">Temp Locked</span> - User failed 3 login attempts, locked for 15 minutes</p>
        <p>❌ <span class="status-blocked-note">Blocked</span>    - Admin manually blocked the user</p>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="js/main.js"></script>
</body>
</html>