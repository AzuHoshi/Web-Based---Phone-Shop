<?php
include 'config.php';

// Handle add to cart
if (is_post() && post('action') == 'add_to_cart') {
    $product_id = post('product_id');
    $quantity = (int)post('quantity');
    $storage = post('storage');
    $color = post('color');
    if ($quantity > 0 && $product_id) {
        add_to_cart($product_id, $quantity, $storage, $color);
        redirect('cart.php');
    }
}

$id = intval(get('id'));
$product = getProduct($id);
if (!$product) {
    redirect('index.php');
}

// 处理添加/移除收藏
if (isset($_GET['wishlist'])) {
    if (isset($_SESSION['user_id'])) {
        if ($_GET['wishlist'] == 'add') {
            addToWishlist($_SESSION['user_id'], $product->id);
            redirect("product_detail.php?id={$product->id}");
        } elseif ($_GET['wishlist'] == 'remove') {
            removeFromWishlist($_SESSION['user_id'], $product->id);
            redirect("product_detail.php?id={$product->id}");
        }
    }
}

// Get storage options
$storageOptions = [];
if ($product->storage) {
    $storageOptions = array_map('trim', explode(',', $product->storage));
}

// Get color options
$colorOptions = [];
if ($product->colors) {
    $colorOptions = array_map('trim', explode(',', $product->colors));
}

// Parse variant prices
$variantPrices = [];
if (isset($product->variant_prices) && $product->variant_prices) {
    $variantPrices = json_decode($product->variant_prices, true);
    if (!is_array($variantPrices)) $variantPrices = [];
}

$selectedStorage = !empty($storageOptions) ? $storageOptions[0] : '';
$selectedColor = !empty($colorOptions) ? $colorOptions[0] : '';

function getCurrentPrice($storage, $variantPrices, $defaultPrice) {
    if (!empty($variantPrices) && isset($variantPrices[$storage])) {
        return (float)$variantPrices[$storage];
    }
    return (float)$defaultPrice;
}

$currentPrice = getCurrentPrice($selectedStorage, $variantPrices, $product->price);
$cart_count = get_cart_count();

// 检查是否已收藏
$is_wishlisted = false;
if (isset($_SESSION['user_id'])) {
    $is_wishlisted = isInWishlist($_SESSION['user_id'], $product->id);
}
$wishlist_count = isset($_SESSION['user_id']) ? getWishlistCount($_SESSION['user_id']) : 0;

// 颜色映射函数
function getColorBg($colorName) {
    $lower = strtolower($colorName);
    
    if (strpos($lower, 'black') !== false || $lower == 'obsidian' || $lower == 'onyx black' || $lower == 'silky black') {
        return '#1a1a1a';
    }
    if (strpos($lower, 'white') !== false || $lower == 'porcelain') {
        return '#f5f5f5';
    }
    if (strpos($lower, 'natural titanium') !== false) {
        return '#d4c5a9';
    }
    if (strpos($lower, 'blue titanium') !== false) {
        return '#5d8aa8';
    }
    if (strpos($lower, 'titanium gray') !== false || $lower == 'titanium grey') {
        return '#a9a9a9';
    }
    if (strpos($lower, 'titanium violet') !== false) {
        return '#8b5cf6';
    }
    if (strpos($lower, 'titanium yellow') !== false) {
        return '#fbbf24';
    }
    if (strpos($lower, 'blue') !== false || $lower == 'bay') {
        return '#3498db';
    }
    if (strpos($lower, 'green') !== false || $lower == 'jade green') {
        return '#2ecc71';
    }
    if (strpos($lower, 'emerald') !== false || $lower == 'flowy emerald') {
        return '#059669';
    }
    if (strpos($lower, 'purple') !== false || $lower == 'violet') {
        return '#9b59b6';
    }
    if (strpos($lower, 'cobalt violet') !== false) {
        return '#a855f7';
    }
    if (strpos($lower, 'yellow') !== false || $lower == 'amber yellow') {
        return '#f59e0b';
    }
    if (strpos($lower, 'pink') !== false) {
        return '#fd79a8';
    }
    if (strpos($lower, 'rose') !== false || $lower == 'rose gold') {
        return '#e8b4b4';
    }
    if (strpos($lower, 'lime') !== false || $lower == 'awesome lime') {
        return '#a3e635';
    }
    if (strpos($lower, 'graphite') !== false || $lower == 'awesome graphite') {
        return '#4b5563';
    }
    if (strpos($lower, 'marble gray') !== false || strpos($lower, 'marble grey') !== false) {
        return '#c0c0c0';
    }
    if (strpos($lower, 'red') !== false) {
        return '#e74c3c';
    }
    
    return '#e0e0e0';
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= h($product->name) ?> - Phone Shop</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .detail { display: flex; gap: 40px; flex-wrap: wrap; margin-top: 30px; padding: 20px; background: white; border-radius: 12px; }
        .detail-img { flex: 1; min-width: 250px; text-align: center; }
        .detail-img img { width: 100%; max-width: 350px; border-radius: 12px; }
        .detail-info { flex: 2; }
        .no-img { width: 100%; max-width: 350px; height: 300px; display: flex; align-items: center; justify-content: center; background: #f5f5f5; border-radius: 12px; color: #999; font-size: 48px; margin: 0 auto; }
        .option-section { margin: 20px 0; }
        .option-section label { font-weight: bold; display: block; margin-bottom: 10px; }
        .option-buttons { display: flex; flex-wrap: wrap; gap: 12px; }
        .storage-btn, .color-btn { padding: 10px 24px; border: 2px solid #ddd; background: white; border-radius: 8px; cursor: pointer; transition: all 0.2s; }
        .storage-btn:hover, .color-btn:hover { border-color: #3498db; background: #f0f8ff; }
        .storage-btn.active, .color-btn.active { border-color: #3498db; background: #3498db; color: white; }
        .color-dot { width: 16px; height: 16px; border-radius: 50%; display: inline-block; margin-right: 8px; vertical-align: middle; border: 1px solid rgba(0,0,0,0.1); }
        .selected-info { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 8px; }
        .final-price { font-size: 28px; color: #e74c3c; font-weight: bold; margin-top: 10px; }
        .storage-price { font-size: 11px; color: #e74c3c; display: block; }
        .buy-btn { background: #3498db; color: white; border: none; padding: 12px 30px; font-size: 16px; border-radius: 8px; cursor: pointer; }
        .buy-btn:hover { background: #2980b9; }
        .wishlist-btn { background: #95a5a6; color: white; border: none; padding: 12px 20px; font-size: 16px; border-radius: 8px; cursor: pointer; text-decoration: none; display: inline-block; }
        .wishlist-btn.active { background: #e74c3c; }
        .spec { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .spec th, .spec td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .spec th { background: #f8f9fa; width: 120px; }
        .button-group { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; margin-top: 15px; }
    </style>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
    const variantPrices = <?= json_encode($variantPrices) ?>;
    const defaultPrice = <?= (float)$product->price ?>;
    
    function selectStorage(btn, storage) {
        document.querySelectorAll('.storage-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('selected-storage').innerHTML = storage;
        document.getElementById('cart-storage').value = storage;
        updatePrice(storage);
    }
    
    function updatePrice(storage) {
        var newPrice = variantPrices[storage] || defaultPrice;
        document.getElementById('price-amount').innerHTML = 'RM ' + parseFloat(newPrice).toFixed(2);
    }
    
    function selectColor(btn, color) {
        document.querySelectorAll('.color-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('selected-color').innerHTML = color;
        document.getElementById('cart-color').value = color;
    }
    
    $(function() {
        var activeStorage = document.querySelector('.storage-btn.active');
        if(activeStorage) {
            var storageText = activeStorage.innerText.trim().split('\n')[0];
            document.getElementById('selected-storage').innerHTML = storageText;
            document.getElementById('cart-storage').value = storageText;
            updatePrice(storageText);
        }
        var activeColor = document.querySelector('.color-btn.active');
        if(activeColor) {
            document.getElementById('selected-color').innerHTML = activeColor.innerText.trim();
            document.getElementById('cart-color').value = activeColor.innerText.trim();
        }
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
    
    <a href="index.php" class="btn btn-s" style="margin: 20px 0; display: inline-block;">← Back to Products</a>
    
    <div class="detail">
        <div class="detail-img">
            <?php if ($product->image_path && file_exists("image/".$product->image_path)): ?>
                <img src="image/<?= h($product->image_path) ?>" alt="<?= h($product->name) ?>">
            <?php else: ?>
                <div class="no-img">📱 No Image</div>
            <?php endif; ?>
        </div>
        
        <div class="detail-info">
            <h2 style="font-size: 28px; margin-bottom: 10px;"><?= h($product->name) ?></h2>
            
            <?php if (!empty($storageOptions)): ?>
            <div class="option-section">
                <label>📦 Storage:</label>
                <div class="option-buttons">
                    <?php foreach ($storageOptions as $s): 
                        $storageVal = trim($s);
                        $activeClass = ($storageVal == $selectedStorage) ? 'active' : '';
                        $storagePrice = isset($variantPrices[$storageVal]) ? $variantPrices[$storageVal] : $product->price;
                    ?>
                        <button type="button" class="storage-btn <?= $activeClass ?>" onclick="selectStorage(this, '<?= h($storageVal) ?>')">
                            <?= h($storageVal) ?>
                            <span class="storage-price"><?= price($storagePrice) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($colorOptions)): ?>
            <div class="option-section">
                <label>🎨 Color:</label>
                <div class="option-buttons">
                    <?php foreach ($colorOptions as $col): 
                        $colorName = trim($col);
                        $activeClass = ($colorName == $selectedColor) ? 'active' : '';
                        $bgColor = getColorBg($colorName);
                    ?>
                        <button type="button" class="color-btn <?= $activeClass ?>" onclick="selectColor(this, '<?= h($colorName) ?>')">
                            <span class="color-dot" style="background: <?= $bgColor ?>;"></span>
                            <?= h($colorName) ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="selected-info">
                <p><strong>Selected Configuration:</strong></p>
                <p>📦 Storage: <span id="selected-storage"><?= h($selectedStorage) ?></span></p>
                <p>🎨 Color: <span id="selected-color"><?= h($selectedColor) ?></span></p>
                <p class="final-price">💰 Price: <span id="price-amount"><?= price($currentPrice) ?></span></p>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="add_to_cart">
                <input type="hidden" name="product_id" value="<?= $product->id ?>">
                <input type="hidden" name="storage" id="cart-storage" value="<?= h($selectedStorage) ?>">
                <input type="hidden" name="color" id="cart-color" value="<?= h($selectedColor) ?>">
                
                <div class="button-group">
                    <div>
                        <label style="font-weight: bold;">Quantity:</label>
                        <input type="number" name="quantity" value="1" min="1" max="<?= $product->stock ?>" 
                               style="width: 80px; padding: 10px; border-radius: 8px; border: 1px solid #ddd; margin-left: 10px;">
                    </div>
                    <button type="submit" class="buy-btn">🛒 Add to Cart</button>
                </div>
            </form>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <div class="button-group" style="margin-top: 10px;">
                    <?php if ($is_wishlisted): ?>
                        <a href="product_detail.php?id=<?= $product->id ?>&wishlist=remove" class="wishlist-btn active" style="background: #e74c3c;">❤️ Remove from Wishlist</a>
                    <?php else: ?>
                        <a href="product_detail.php?id=<?= $product->id ?>&wishlist=add" class="wishlist-btn">🤍 Add to Wishlist</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <table class="spec">
                <?php if($product->processor): ?><tr><th>Processor</th><td><?= h($product->processor) ?></td><?php endif; ?>
                <?php if($product->ram): ?><tr><th>RAM</th><td><?= $product->ram ?> GB</td><?php endif; ?>
                <?php if($product->battery): ?><tr><th>Battery</th><td><?= $product->battery ?> mAh</td><?php endif; ?>
                <?php if($product->camera): ?><tr><th>Camera</th><td><?= h($product->camera) ?></td><?php endif; ?>
                <?php if($product->display): ?><tr><th>Display</th><td><?= h($product->display) ?></td><?php endif; ?>
                <tr><th>Description</th><td><?= h($product->description) ?></td>
            </table>
        </div>
    </div>
</div>
</body>
</html>