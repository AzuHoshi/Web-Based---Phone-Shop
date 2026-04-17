<?php
include 'config.php';

$step = isset($_GET['step']) ? $_GET['step'] : 1;
$error = '';
$success = '';
$email = '';
$security_question = '';
$user_id = '';

// 步骤1：输入邮箱并显示安全问题
if ($step == 1 && is_post() && post('action') == 'verify_email') {
    $email = trim(post('email'));
    
    if (empty($email)) {
        $error = 'Please enter your email address';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } else {
        $stm = $_db->prepare("SELECT id, name, security_question FROM users WHERE email = ?");
        $stm->execute([$email]);
        $user = $stm->fetch();
        
        if ($user && $user->security_question) {
            $_SESSION['reset_user_id'] = $user->id;
            $_SESSION['reset_email'] = $email;
            redirect('forgot_password.php?step=2');
        } else {
            $error = 'Email not found or no security question set. Please contact admin.';
        }
    }
}

// 步骤2：验证安全问题答案
if ($step == 2 && is_post() && post('action') == 'verify_answer') {
    $answer = trim(post('answer'));
    $user_id = $_SESSION['reset_user_id'] ?? 0;
    
    if ($user_id) {
        $stm = $_db->prepare("SELECT id, security_answer FROM users WHERE id = ?");
        $stm->execute([$user_id]);
        $user = $stm->fetch();
        
        if ($user && strtolower($answer) == $user->security_answer) {
            redirect('reset_password.php');
        } else {
            $error = 'Incorrect answer. Please try again.';
        }
    } else {
        redirect('forgot_password.php');
    }
}

// 获取用户信息用于步骤2显示
if ($step == 2 && isset($_SESSION['reset_user_id'])) {
    $stm = $_db->prepare("SELECT name, security_question FROM users WHERE id = ?");
    $stm->execute([$_SESSION['reset_user_id']]);
    $user = $stm->fetch();
    if ($user) {
        $security_question = $user->security_question;
    } else {
        redirect('forgot_password.php');
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password - Phone Shop</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .forgot-container { max-width: 500px; margin: 80px auto; padding: 30px; background: white; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .forgot-container h2 { text-align: center; margin-bottom: 30px; color: #2c3e50; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; }
        .error-msg { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
        .back-link { text-align: center; margin-top: 20px; }
        .question-box { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .question-box p { margin: 5px 0; }
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
    
    <div class="forgot-container">
        <h2>Reset Password</h2>
        
        <?php if ($step == 1): ?>
            <p style="text-align: center; margin-bottom: 20px;">Enter your email address to verify your identity.</p>
            
            <?php if ($error): ?>
                <div class="error-msg"><?= h($error) ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <input type="hidden" name="action" value="verify_email">
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" required>
                </div>
                <button type="submit" class="btn btn-p" style="width: 100%;">Continue</button>
            </form>
            
        <?php elseif ($step == 2): ?>
            <p style="text-align: center; margin-bottom: 20px;">Please answer your security question to continue.</p>
            
            <?php if ($error): ?>
                <div class="error-msg"><?= h($error) ?></div>
            <?php endif; ?>
            
            <div class="question-box">
                <p><strong>Security Question:</strong></p>
                <p><?= h($security_question) ?></p>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="verify_answer">
                <div class="form-group">
                    <label>Your Answer</label>
                    <input type="text" name="answer" required autocomplete="off">
                </div>
                <button type="submit" class="btn btn-p" style="width: 100%;">Verify Answer</button>
            </form>
            
        <?php endif; ?>
        
        <div class="back-link">
            <a href="login.php">← Back to Login</a>
        </div>
    </div>
</div>
</body>
</html>