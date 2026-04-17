<?php
include 'config.php';

// If already logged in, redirect to home
if (isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$error = '';

if (is_post()) {
    $email = post('email');
    $password = post('password');
    
    if ($email && $password) {
        // Check if account is locked
        if (isAccountLocked($email)) {
            $minutes = getLockedMinutesRemaining($email);
            if ($minutes > 0) {
                $error = "Too many failed attempts. Account is locked for $minutes minutes.";
            } else {
                $error = "Your account has been blocked by administrator. Please contact support.";
            }
        } else {
            $user = getUserByEmail($email);
            
            if ($user && $user->password == sha1($password)) {
                // Check if account is blocked by admin
                if ($user->is_blocked == 1) {
                    $error = 'Your account has been blocked by administrator. Please contact support.';
                } else {
                    // Login successful - reset attempts
                    resetLoginAttempts($email);
                    
                    $_SESSION['user_id'] = $user->id;
                    $_SESSION['user_name'] = $user->name;
                    $_SESSION['user_role'] = $user->role;
                    
                    // Merge guest cart to user cart
                    merge_cart_to_user($user->id);
                    
                    // Redirect to previous page if exists
                    if (isset($_SESSION['redirect_after_login'])) {
                        $redirect = $_SESSION['redirect_after_login'];
                        unset($_SESSION['redirect_after_login']);
                        redirect($redirect);
                    }
                    redirect('index.php');
                }
            } else {
                // Login failed - record attempt
                recordLoginFailure($email);
                
                // Get current attempt count
                $stm = $_db->prepare("SELECT login_attempts FROM users WHERE email = ?");
                $stm->execute([$email]);
                $userData = $stm->fetch();
                $attempts = $userData ? $userData->login_attempts : 1;
                
                // Check if this was the 3rd failed attempt - LOCK THE ACCOUNT
                if ($attempts >= 3) {
                    lockAccount($email);
                    $error = "Too many failed attempts. Your account has been locked for 15 minutes.";
                } else {
                    $remaining = 3 - $attempts;
                    $error = "Invalid email or password. $remaining attempts remaining.";
                }
            }
        }
    } else {
        $error = 'Please enter email and password';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - Phone Shop</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .login-container { max-width: 400px; margin: 80px auto; padding: 30px; background: white; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .login-container h2 { text-align: center; margin-bottom: 30px; color: #2c3e50; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; }
        .error-msg { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
        .register-link { text-align: center; margin-top: 20px; }
        .register-link a { margin: 0 5px; }
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
            <a href="register.php" class="btn btn-s">Register</a>
        </div>
    </div>
    
    <div class="login-container">
        <h2>Login to Your Account</h2>
        
        <?php if ($error): ?>
            <div class="error-msg"><?= h($error) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required value="<?= h(post('email')) ?>">
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-p" style="width: 100%;">Login</button>
        </form>
        
        <div class="register-link">
            <a href="register.php">Create New Account</a> | <a href="forgot_password.php">Forgot Password?</a>
        </div>
    </div>
</div>
</body>
</html>