<?php
include 'config.php';

// Redirect to login if trying to checkout without login
if (isset($_GET['checkout']) && !isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = 'cart.php?checkout=1';
    redirect('login.php');
}

// Handle POST actions
if (is_post()) {
    $action = post('action');
    
    if ($action == 'update' && post('cart_id') && post('quantity') !== null) {
        update_cart_item(post('cart_id'), (int)post('quantity'));
        redirect('cart.php');
    }
    
    if ($action == 'remove' && post('cart_id')) {
        remove_from_cart(post('cart_id'));
        redirect('cart.php');
    }
    
    if ($action == 'clear') {
        clear_cart();
        redirect('cart.php');
    }
    
    if ($action == 'checkout') {
        $errors = [];
        $address = trim(post('address'));
        $phone = trim(post('phone'));
        $notes = trim(post('notes'));
        
        if (empty($address)) {
            $errors['address'] = 'Shipping address is required';
        }
        if (empty($phone)) {
            $errors['phone'] = 'Phone number is required';
        } elseif (!preg_match('/^[0-9]{10,11}$/', $phone)) {
            $errors['phone'] = 'Invalid phone number (10-11 digits)';
        }
        
        if (empty($errors)) {
            $order_id = create_order_from_cart($address, $phone, $notes);
            if ($order_id) {
                redirect("order_success.php?id=$order_id");
            } else {
                $errors['general'] = 'Failed to create order. Please try again.';
            }
        }
    }
}

$cart_items = get_cart_items();
$cart_total = get_cart_total();
$cart_count = get_cart_count();
$is_checkout = isset($_GET['checkout']);
$wishlist_count = isset($_SESSION['user_id']) ? getWishlistCount($_SESSION['user_id']) : 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Shopping Cart - Phone Shop</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .cart-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .cart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #eee; }
        .cart-header h2 { font-size: 28px; color: #333; margin: 0; }
        .cart-count { background: #3498db; color: white; padding: 5px 12px; border-radius: 20px; font-size: 14px; }
        .cart-table { width: 100%; border-collapse: collapse; }
        .cart-table th { text-align: left; padding: 15px; background: #f8f9fa; border-bottom: 2px solid #ddd; }
        .cart-table td { padding: 15px; border-bottom: 1px solid #eee; vertical-align: middle; }
        .cart-product { display: flex; align-items: center; gap: 15px; }
        .cart-product img { width: 70px; height: 70px; object-fit: cover; border-radius: 8px; background: #f5f5f5; }
        .cart-product .no-img { width: 70px; height: 70px; display: flex; align-items: center; justify-content: center; background: #f5f5f5; border-radius: 8px; font-size: 24px; }
        .cart-product-info h4 { margin: 0 0 5px 0; font-size: 16px; }
        .cart-product-info p { margin: 0; font-size: 12px; color: #666; }
        .cart-qty { width: 80px; padding: 8px; border: 1px solid #ddd; border-radius: 5px; text-align: center; }
        .cart-price { font-weight: bold; color: #e74c3c; }
        .cart-total-row td { font-weight: bold; font-size: 18px; background: #f8f9fa; }
        .cart-actions { margin-top: 30px; display: flex; justify-content: space-between; gap: 15px; }
        .empty-cart { text-align: center; padding: 60px 20px; }
        .empty-cart .icon { font-size: 80px; margin-bottom: 20px; }
        .checkout-form { margin-top: 30px; padding: 25px; background: #f8f9fa; border-radius: 12px; }
        .checkout-form h3 { margin-bottom: 20px; color: #333; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #555; }
        .form-group input, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; }
        .error-msg { color: #e74c3c; font-size: 12px; margin-top: 5px; }
        .order-summary { margin-top: 30px; padding: 20px; background: #fff; border-radius: 12px; border: 1px solid #eee; }
        .summary-row { display: flex; justify-content: space-between; padding: 10px 0; }
        .summary-total { font-size: 20px; font-weight: bold; color: #e74c3c; border-top: 2px solid #eee; margin-top: 10px; padding-top: 15px; }
    </style>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
    $(function() {
        $('.cart-qty').on('change', function() {
            var cartId = $(this).data('id');
            var quantity = $(this).val();
            if (quantity < 1) quantity = 1;
            $('<form method="POST">')
                .append('<input type="hidden" name="action" value="update">')
                .append('<input type="hidden" name="cart_id" value="' + cartId + '">')
                .append('<input type="hidden" name="quantity" value="' + quantity + '">')
                .appendTo('body')
                .submit();
        });
        
        $('.remove-btn').on('click', function(e) {
            e.preventDefault();
            if (confirm('Remove this item from cart?')) {
                $('<form method="POST">')
                    .append('<input type="hidden" name="action" value="remove">')
                    .append('<input type="hidden" name="cart_id" value="' + $(this).data('id') + '">')
                    .appendTo('body')
                    .submit();
            }
        });
        
        $('.clear-cart').on('click', function(e) {
            e.preventDefault();
            if (confirm('Clear entire shopping cart?')) {
                $('<form method="POST">')
                    .append('<input type="hidden" name="action" value="clear">')
                    .appendTo('body')
                    .submit();
            }
        });
    });
    </script>
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
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="profile.php" class="btn btn-s">👤 My Profile</a>
                <a href="logout.php" class="btn btn-s">Logout (<?= h($_SESSION['user_name'] ?? '') ?>)</a>
            <?php else: ?>
                <a href="login.php" class="btn btn-s">Login</a>
                <a href="register.php" class="btn btn-s">Register</a>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="cart-container">
        <div class="cart-header">
            <h2>🛒 Shopping Cart</h2>
            <?php if ($cart_count > 0): ?>
                <span class="cart-count"><?= $cart_count ?> item(s)</span>
            <?php endif; ?>
        </div>
        
        <?php if (empty($cart_items)): ?>
            <div class="empty-cart">
                <div class="icon">🛒</div>
                <h3>Your cart is empty</h3>
                <p>Looks like you haven't added any items to your cart yet.</p>
                <a href="index.php" class="btn btn-p" style="margin-top: 20px; display: inline-block;">Continue Shopping</a>
            </div>
        <?php else: ?>
            
            <?php if (!$is_checkout): ?>
                <!-- Cart View Mode -->
                <table class="cart-table">
                    <thead>
                        <tr><th>Product</th><th>Quantity</th><th>Price</th><th>Subtotal</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): 
                            $subtotal = $item->price * $item->quantity;
                        ?>
                            <tr>
                                <td>
                                    <div class="cart-product">
                                        <?php if ($item->image_path && file_exists("image/".$item->image_path)): ?>
                                            <img src="image/<?= h($item->image_path) ?>" alt="<?= h($item->name) ?>">
                                        <?php else: ?>
                                            <div class="no-img">📱</div>
                                        <?php endif; ?>
                                        <div class="cart-product-info">
                                            <h4><?= h($item->name) ?></h4>
                                            <?php if($item->storage): ?><p>Storage: <?= h($item->storage) ?></p><?php endif; ?>
                                            <?php if($item->color): ?><p>Color: <?= h($item->color) ?></p><?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <input type="number" class="cart-qty" data-id="<?= $item->id ?>" 
                                           value="<?= $item->quantity ?>" min="1" max="<?= $item->stock ?>">
                                </td>
                                <td class="cart-price"><?= price($item->price) ?></td>
                                <td class="cart-price"><?= price($subtotal) ?></td>
                                <td><button class="btn btn-d remove-btn" data-id="<?= $item->id ?>" style="padding: 5px 10px;">Remove</button></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="cart-total-row">
                            <td colspan="3" style="text-align: right;"><strong>Total:</strong></td>
                            <td colspan="2"><strong><?= price($cart_total) ?></strong></td>
                        </tr>
                    </tbody>
                </table>
                
                <div class="cart-actions">
                    <a href="index.php" class="btn btn-s">← Continue Shopping</a>
                    <div>
                        <a href="#" class="btn btn-d clear-cart">Clear Cart</a>
                        <a href="cart.php?checkout=1" class="btn btn-p">Proceed to Checkout →</a>
                    </div>
                </div>
                
            <?php else: ?>
                <!-- Checkout Mode -->
                <form method="POST" class="checkout-form">
                    <h3>📋 Checkout Information</h3>
                    
                    <?php if (isset($errors['general'])): ?>
                        <div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 20px;"><?= h($errors['general']) ?></div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label>Shipping Address *</label>
                        <input type="text" name="address" value="<?= h(post('address') ?? '') ?>" placeholder="Enter your full address" required>
                        <?php if (isset($errors['address'])): ?>
                            <div class="error-msg"><?= h($errors['address']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label>Phone Number *</label>
                        <input type="tel" name="phone" value="<?= h(post('phone') ?? '') ?>" placeholder="0123456789" required>
                        <?php if (isset($errors['phone'])): ?>
                            <div class="error-msg"><?= h($errors['phone']) ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label>Order Notes (Optional)</label>
                        <textarea name="notes" rows="3" placeholder="Special instructions..."><?= h(post('notes') ?? '') ?></textarea>
                    </div>
                    
                    <div class="order-summary">
                        <h4>Order Summary</h4>
                        <?php foreach ($cart_items as $item): 
                            $subtotal = $item->price * $item->quantity;
                        ?>
                            <div class="summary-row">
                                <span><?= h($item->name) ?> x<?= $item->quantity ?></span>
                                <span><?= price($subtotal) ?></span>
                            </div>
                        <?php endforeach; ?>
                        <div class="summary-row summary-total">
                            <span>Total</span>
                            <span><?= price($cart_total) ?></span>
                        </div>
                    </div>
                    
                    <div class="cart-actions" style="margin-top: 20px;">
                        <a href="cart.php" class="btn btn-s">← Back to Cart</a>
                        <button type="submit" name="action" value="checkout" class="btn btn-p">✅ Place Order</button>
                    </div>
                </form>
            <?php endif; ?>
            
        <?php endif; ?>
    </div>
</div>
</body>
</html>