<?php
include 'config.php';

// 检查是否有重置权限（来自安全问题验证）
if (!isset($_SESSION['reset_user_id'])) {
    redirect('forgot_password.php');
}

$error = '';
$success = '';

if (is_post()) {
    $password = post('password');
    $confirm_password = post('confirm_password');
    
    if (empty($password)) {
        $error = 'Please enter a password';
    } elseif (strlen($password) < 4) {
        $error = 'Password must be at least 4 characters';
    } elseif ($password != $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        $user_id = $_SESSION['reset_user_id'];
        $stm = $_db->prepare("UPDATE users SET password = ? WHERE id = ?");
        if ($stm->execute([sha1($password), $user_id])) {
            // 清除 session
            unset($_SESSION['reset_user_id']);
            unset($_SESSION['reset_email']);
            $success = "Password has been reset successfully! You can now login with your new password.";
        } else {
            $error = "Failed to reset password. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password - Phone Shop</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .reset-container { max-width: 500px; margin: 80px auto; padding: 30px; background: white; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .reset-container h2 { text-align: center; margin-bottom: 30px; color: #2c3e50; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; }
        .error-msg { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
        .success-msg { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
        .login-link { text-align: center; margin-top: 20px; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📱 Phone Shop</h1>
        <div class="nav">
            <a href="index.php" class="btn btn-s">Home</a>
            <a href="products.php" class="btn btn-s">Products</a>
            <a href="cart.php" class="btn btn-s">Cart</a>
            <a href="login.php" class="btn btn-s">Login</a>
        </div>
    </div>
    
    <div class="reset-container">
        <h2>Reset Password</h2>
        
        <?php if ($error): ?>
            <div class="error-msg"><?= h($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-msg"><?= h($success) ?></div>
            <div class="login-link">
                <a href="login.php">Click here to login</a>
            </div>
        <?php else: ?>
            <p style="text-align: center; margin-bottom: 20px;">Enter your new password below.</p>
            <form method="POST">
                <div class="form-group">
                    <label>New Password (min 4 characters)</label>
                    <input type="password" name="password" required>
                </div>
                <div class="form-group">
                    <label>Confirm New Password</label>
                    <input type="password" name="confirm_password" required>
                </div>
                <button type="submit" class="btn btn-p" style="width: 100%;">Reset Password</button>
            </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>