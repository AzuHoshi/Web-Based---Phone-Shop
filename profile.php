<?php
require 'config.php';
require_login(); 

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);


$cart_count = get_cart_count();
$wishlist_count = getWishlistCount($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Phone Shop - My Profile</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <div class="container">
        
        <div class="header">
            <h1>📱 Phone Shop</h1>
            <div class="nav">
                <a href="index.php" class="btn btn-p">🏠 Home</a>
                
                <?php if (is_admin()): ?>
                    <a href="orders_admin.php" class="btn btn-s">Orders Admin</a>
                    <a href="stock_alert.php" class="btn btn-s">Stock Alert</a>
                    <a href="users.php" class="btn btn-s">Users</a>
                <?php else: ?>
                    <a href="orders_member.php" class="btn btn-s">My Orders</a>
                <?php endif; ?>
                <a href="wishlist.php" class="btn btn-s">❤️ Wishlist (<?= $wishlist_count ?>)</a>
                <a href="cart.php" class="btn btn-s">🛒 Cart (<?= $cart_count ?>)</a>
                <a href="profile.php" class="btn btn-p">👤 My Profile</a>
                <a href="logout.php" class="btn btn-s">Logout (<?= h($_SESSION['user_name'] ?? '') ?>)</a>
            </div>
        </div>

        <h2 style="margin-top: 20px;">My Account</h2>
        
        <?php if ($msg = temp('info')): ?>
            <div style="color: green; margin-bottom: 15px; font-weight: bold;"><?= $msg ?></div>
        <?php endif; ?>

        <div style="text-align: center; margin-bottom: 30px;">
            <?php if (!empty($user->profile_photo)): ?>
                <img src="image/<?= htmlspecialchars($user->profile_photo) ?>" style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%; margin-bottom: 15px;" alt="Profile Photo">
            <?php else: ?>
                <img src="image/default_avatar.png" style="width: 150px; height: 150px; object-fit: cover; border-radius: 50%; margin-bottom: 15px;" alt="Default Photo">
            <?php endif; ?>

            <h3><?= htmlspecialchars($user->name) ?></h3>
            <p>Email: <?= htmlspecialchars($user->email) ?></p>
            <p>Member since: <?= htmlspecialchars(ucfirst($user->created_at)) ?></p>
            
            <br><br>
            <a href="profile_update.php" class="btn btn-p">Edit Profile</a>
        </div>

    </div>

</body>
</html>