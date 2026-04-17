<?php
include 'config.php';

// If already logged in, redirect to home
if (isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$error = '';
$success = '';

// 安全问题列表
$security_questions = [
    'What is your mother\'s maiden name?',
    'What was the name of your first pet?',
    'What is your favorite book?',
    'What city were you born in?',
    'What is your favorite color?',
    'What is the name of your best friend?',
    'What is your favorite food?',
    'What is your dream job?'
];

if (is_post()) {
    $name = trim(post('name'));
    $email = trim(post('email'));
    $password = post('password');
    $confirm_password = post('confirm_password');
    $security_question = post('security_question');
    $security_answer = trim(post('security_answer'));
    
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } elseif (strlen($password) < 4) {
        $error = 'Password must be at least 4 characters';
    } elseif ($password != $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (empty($security_question)) {
        $error = 'Please select a security question';
    } elseif (empty($security_answer)) {
        $error = 'Please enter an answer to your security question';
    } else {
        // Check if email already exists
        $existing = getUserByEmail($email);
        if ($existing) {
            $error = 'Email already registered';
        } else {
            $stm = $_db->prepare("INSERT INTO users (name, email, password, security_question, security_answer, role) VALUES (?, ?, ?, ?, ?, 'member')");
            if ($stm->execute([$name, $email, sha1($password), $security_question, strtolower(trim($security_answer))])) {
                $success = 'Registration successful! Please login.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - Phone Shop</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .register-container { max-width: 500px; margin: 80px auto; padding: 30px; background: white; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .register-container h2 { text-align: center; margin-bottom: 30px; color: #2c3e50; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
        .form-group input, .form-group select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; }
        .error-msg { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
        .success-msg { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
        .login-link { text-align: center; margin-top: 20px; }
        .note { font-size: 12px; color: #666; margin-top: 5px; }
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
    
    <div class="register-container">
        <h2>Create New Account</h2>
        
        <?php if ($error): ?>
            <div class="error-msg"><?= h($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success-msg"><?= h($success) ?> <a href="login.php">Login now</a></div>
        <?php endif; ?>
        
        <?php if (!$success): ?>
        <form method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" required value="<?= h(post('name')) ?>">
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" required value="<?= h(post('email')) ?>">
            </div>
            <div class="form-group">
                <label>Password (min 4 characters)</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required>
            </div>
            
            <div class="form-group">
                <label>Security Question (for password reset)</label>
                <select name="security_question" required>
                    <option value="">-- Select a question --</option>
                    <?php foreach ($security_questions as $q): ?>
                        <option value="<?= h($q) ?>" <?= post('security_question') == $q ? 'selected' : '' ?>><?= h($q) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Your Answer</label>
                <input type="text" name="security_answer" required placeholder="Enter your answer" value="<?= h(post('security_answer')) ?>">
                <div class="note">* This will be used to verify your identity if you forget your password.</div>
            </div>
            
            <button type="submit" class="btn btn-p" style="width: 100%;">Register</button>
        </form>
        <?php endif; ?>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
</div>
</body>
</html>