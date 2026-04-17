<?php
include 'config.php';

// 只有 admin 可以管理商品
require_admin();

// ============================================
// 商品管理 - 删除
// ============================================
if (get('delete_product')) {
    $id = intval(get('delete_product'));
    $stm = $_db->prepare("SELECT image_path FROM products WHERE id = ?");
    $stm->execute([$id]);
    $p = $stm->fetch();
    $stm = $_db->prepare("DELETE FROM products WHERE id = ?");
    $stm->execute([$id]);
    if ($p && $p->image_path && file_exists("image/".$p->image_path)) {
        unlink("image/".$p->image_path);
    }
    temp('info', 'Product deleted successfully');
    redirect('products.php');
}

// ============================================
// 商品管理 - 添加
// ============================================
if (is_post() && post('action') == 'add_product') {
    $img = '';
    $f = get_file('image');
    if ($f) {
        $ext = strtolower(pathinfo($f->name, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            if (!file_exists('image/')) mkdir('image/', 0777, true);
            $img = uniqid() . '.' . $ext;
            move_uploaded_file($f->tmp_name, 'image/' . $img);
        }
    }
    $stm = $_db->prepare("INSERT INTO products (name, description, price, stock, category_id, image_path) VALUES (?,?,?,?,?,?)");
    $stm->execute([post('name'), post('desc'), post('price'), post('stock'), post('cat'), $img]);
    temp('info', 'Product added successfully');
    redirect('products.php');
}

// ============================================
// 商品管理 - 编辑
// ============================================
if (is_post() && post('action') == 'edit_product') {
    $id = intval(post('id'));
    $img = post('old_img');
    $f = get_file('image');
    if ($f) {
        $ext = strtolower(pathinfo($f->name, PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
            if ($img && file_exists("image/$img")) unlink("image/$img");
            $img = uniqid() . '.' . $ext;
            move_uploaded_file($f->tmp_name, 'image/' . $img);
        }
    }
    $stm = $_db->prepare("UPDATE products SET name=?, description=?, price=?, stock=?, category_id=?, image_path=? WHERE id=?");
    $stm->execute([post('name'), post('desc'), post('price'), post('stock'), post('cat'), $img, $id]);
    temp('info', 'Product updated successfully');
    redirect('products.php');
}

// ============================================
// 获取数据
// ============================================
$search = get('search');
$cat_filter = get('cat');
$sql = "SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE 1=1";
$params = [];
if ($search) { $sql .= " AND p.name LIKE ?"; $params[] = "%$search%"; }
if ($cat_filter) { $sql .= " AND p.category_id = ?"; $params[] = $cat_filter; }
$sql .= " ORDER BY p.id DESC";
$stm = $_db->prepare($sql);
$stm->execute($params);
$products = $stm->fetchAll();

$categories = getCats();

// 编辑商品
$edit_product = null;
if (get('edit_product')) {
    $stm = $_db->prepare("SELECT * FROM products WHERE id = ?");
    $stm->execute([intval(get('edit_product'))]);
    $edit_product = $stm->fetch();
}

$cart_count = get_cart_count();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Product Management - Phone Shop</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .stat-cards { display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap; }
        .stat-card { flex: 1; background: white; padding: 20px; border-radius: 12px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); border: 1px solid #eee; }
        .stat-card .number { font-size: 32px; font-weight: bold; color: #2c3e50; }
    </style>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="js/main.js"></script>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>📱 Product Management</h1>
        <div class="nav">
    <a href="index.php" class="btn btn-p">🏠 Home</a>
    <a href="orders_admin.php" class="btn btn-s">Orders Admin</a>
    <a href="stock_alert.php" class="btn btn-s">Stock Alert</a>
    <a href="users.php" class="btn btn-s">Users</a>
    <a href="cart.php" class="btn btn-s">🛒 Cart (<?= $cart_count ?>)</a>
    <a href="profile.php" class="btn btn-s">👤 My Profile</a>
    <a href="logout.php" class="btn btn-s">Logout (<?= h($_SESSION['user_name'] ?? '') ?>)</a>
</div>
    </div>
    
    <?php if ($msg = temp('info')): ?>
        <div class="alert alert-s"><?= h($msg) ?></div>
    <?php endif; ?>
    
    <!-- 统计卡片 -->
    <div class="stat-cards">
        <div class="stat-card">
            <h3>📦 Total Products</h3>
            <div class="number"><?= count($products) ?></div>
        </div>
        <div class="stat-card">
            <h3>📁 Categories</h3>
            <div class="number"><?= count($categories) ?></div>
        </div>
    </div>
    
    <?php if ($edit_product): ?>
        <!-- 编辑商品表单 -->
        <div>
            <h2>✏️ Edit Product</h2>
            <a href="products.php" class="btn btn-s">← Cancel</a>
            <form method="POST" enctype="multipart/form-data" class="form" style="margin-top: 20px;">
                <input type="hidden" name="action" value="edit_product">
                <input type="hidden" name="id" value="<?= $edit_product->id ?>">
                <input type="hidden" name="old_img" value="<?= $edit_product->image_path ?>">
                <div class="row">
                    <div class="half"><label>Name</label><input type="text" name="name" value="<?= h($edit_product->name) ?>" required></div>
                    <div class="half"><label>Price</label><input type="number" name="price" step="0.01" value="<?= $edit_product->price ?>" required></div>
                </div>
                <div class="row">
                    <div class="half"><label>Stock</label><input type="number" name="stock" value="<?= $edit_product->stock ?>" required></div>
                    <div class="half"><label>Category</label>
                        <select name="cat">
                            <option value="">Select</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= $c->id ?>" <?= $edit_product->category_id == $c->id ? 'selected' : '' ?>><?= h($c->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div><label>Description</label><textarea name="desc" rows="3"><?= h($edit_product->description) ?></textarea></div>
                <div>
                    <label>Current Image</label>
                    <?php if ($edit_product->image_path && file_exists("image/".$edit_product->image_path)): ?>
                        <div><img src="image/<?= h($edit_product->image_path) ?>" class="thumb"><br><small><?= h($edit_product->image_path) ?></small></div>
                    <?php else: ?>
                        <p>No image uploaded</p>
                    <?php endif; ?>
                </div>
                <div><label>Change Image</label><input type="file" name="image" accept="image/*"></div>
                <div><button type="submit" class="btn btn-p">Update Product</button></div>
            </form>
        </div>
    <?php else: ?>
        <!-- 商品列表 -->
        <div>
            <div class="flex">
                <h2>📱 Product List</h2>
                <a href="products.php?add_product=1" class="btn btn-p">+ Add Product</a>
            </div>
            
            <!-- 搜索和筛选 -->
            <form method="GET" class="filter-form" style="margin-bottom: 20px;">
                <input type="text" name="search" placeholder="Search..." value="<?= h($search) ?>">
                <select name="cat">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c->id ?>" <?= $cat_filter == $c->id ? 'selected' : '' ?>><?= h($c->name) ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-p">Search</button>
                <a href="products.php" class="btn btn-s">Reset</a>
            </form>
            
            <!-- 添加商品表单 -->
            <?php if (get('add_product')): ?>
            <div style="margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                <h3>➕ Add New Product</h3>
                <form method="POST" enctype="multipart/form-data" class="form">
                    <input type="hidden" name="action" value="add_product">
                    <div class="row">
                        <div class="half"><label>Name</label><input type="text" name="name" required></div>
                        <div class="half"><label>Price</label><input type="number" name="price" step="0.01" required></div>
                    </div>
                    <div class="row">
                        <div class="half"><label>Stock</label><input type="number" name="stock" required></div>
                        <div class="half"><label>Category</label>
                            <select name="cat" required>
                                <option value="">Select</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= $c->id ?>"><?= h($c->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div><label>Description</label><textarea name="desc" rows="3"></textarea></div>
                    <div><label>Image</label><input type="file" name="image" accept="image/*"></div>
                    <div><button type="submit" class="btn btn-p">Save Product</button>
                    <a href="products.php" class="btn btn-s">Cancel</a></div>
                </form>
            </div>
            <?php endif; ?>
            
            <!-- 商品表格 -->
            <table class="table">
                <thead>
                    <tr><th>ID</th><th>Image</th><th>Name</th><th>Price</th><th>Stock</th><th>Category</th><th>Action</th>
                </thead>
                <tbody>
                    <?php foreach ($products as $p): ?>
                    <tr>
                        <td><?= $p->id ?></td>
                        <td><?php if ($p->image_path && file_exists("image/".$p->image_path)): ?><img src="image/<?= h($p->image_path) ?>" class="thumb"><?php else: ?>📱<?php endif; ?></td>
                        <td><?= h($p->name) ?></td>
                        <td><?= price($p->price) ?></td>
                        <td><?= $p->stock ?></td>
                        <td><?= h($p->cat_name ?? '-') ?></td>
                        <td>
                            <a href="products.php?edit_product=<?= $p->id ?>" class="btn btn-e">Edit</a>
                            <a href="products.php?delete_product=<?= $p->id ?>" class="btn btn-d" data-confirm="Delete this product?">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</body>
</html>