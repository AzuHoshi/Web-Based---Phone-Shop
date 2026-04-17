<?php
date_default_timezone_set('Asia/Kuala_Lumpur');
session_start();

$host = 'localhost';
$dbname = 'phone_shop';
$username = 'root';
$password = '';

try {
    $_db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ
    ]);
} catch (PDOException $e) {
    die('Connection failed: ' . $e->getMessage());
}

function h($v) { return htmlspecialchars($v, ENT_QUOTES); }
function get($k, $d='') { return trim($_GET[$k] ?? $d); }
function post($k, $d='') { return trim($_POST[$k] ?? $d); }
function is_post() { return $_SERVER['REQUEST_METHOD'] === 'POST'; }
function redirect($u) { header("Location: $u"); exit; }
function price($p) { return 'RM ' . number_format($p, 2); }
function temp($k, $v=null) { if($v!==null) $_SESSION["temp_$k"]=$v; else { $t=$_SESSION["temp_$k"]??null; unset($_SESSION["temp_$k"]); return $t; } }
function get_file($k) { $f=$_FILES[$k]??null; return ($f && $f['error']==0) ? (object)$f : null; }

// 上传图片到 image 文件夹
function uploadImage($f) {
    if (!$f || $f['error'] != 0) return null;
    $ext = strtolower(pathinfo($f->name, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','gif','webp'])) return null;
    if ($f['size'] > 2 * 1024 * 1024) return null;
    if (!file_exists('image/')) mkdir('image/', 0777, true);
    $name = uniqid() . '.' . $ext;
    move_uploaded_file($f->tmp_name, 'image/' . $name);
    return $name;
}

// ============================================
// 商品函数
// ============================================
function getProducts($search='', $cat='', $min='', $max='') {
    global $_db;
    $sql = "SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE 1=1";
    $ps = [];
    if($search) { $sql .= " AND p.name LIKE ?"; $ps[] = "%$search%"; }
    if($cat) { $sql .= " AND p.category_id = ?"; $ps[] = $cat; }
    if($min !== '') { $sql .= " AND p.price >= ?"; $ps[] = floatval($min); }
    if($max !== '') { $sql .= " AND p.price <= ?"; $ps[] = floatval($max); }
    $sql .= " ORDER BY p.id DESC";
    $stm = $_db->prepare($sql);
    $stm->execute($ps);
    return $stm->fetchAll();
}

function getProduct($id) {
    global $_db;
    $stm = $_db->prepare("SELECT * FROM products WHERE id = ?");
    $stm->execute([$id]);
    return $stm->fetch();
}

function getCats() {
    global $_db;
    return $_db->query("SELECT * FROM categories ORDER BY name")->fetchAll();
}

// ============================================
// 订单函数
// ============================================
function getAllOrders($search='', $status='') {
    global $_db;
    $sql = "SELECT o.*, u.name as user_name, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE 1=1";
    $ps = [];
    if($search) {
        $sql .= " AND (o.order_no LIKE ? OR u.name LIKE ? OR u.email LIKE ?)";
        $ps[] = "%$search%"; $ps[] = "%$search%"; $ps[] = "%$search%";
    }
    if($status) { $sql .= " AND o.status = ?"; $ps[] = $status; }
    $sql .= " ORDER BY o.id DESC";
    $stm = $_db->prepare($sql);
    $stm->execute($ps);
    return $stm->fetchAll();
}

function getOrder($id) {
    global $_db;
    $stm = $_db->prepare("SELECT o.*, u.name as user_name, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    $stm->execute([$id]);
    return $stm->fetch();
}

function getOrderItems($oid) {
    global $_db;
    $stm = $_db->prepare("SELECT oi.*, p.name as pname FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
    $stm->execute([$oid]);
    return $stm->fetchAll();
}

function updateStatus($id, $s) {
    global $_db;
    $stm = $_db->prepare("UPDATE orders SET status = ? WHERE id = ?");
    return $stm->execute([$s, $id]);
}

function getUserOrders($uid) {
    global $_db;
    $stm = $_db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
    $stm->execute([$uid]);
    return $stm->fetchAll();
}

function getUserOrder($oid, $uid) {
    global $_db;
    $stm = $_db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stm->execute([$oid, $uid]);
    return $stm->fetch();
}

function cancelOrder($oid, $uid) {
    global $_db;
    $stm = $_db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ? AND status IN ('pending', 'paid')");
    $stm->execute([$oid, $uid]);
    if($stm->fetch()) {
        $stm = $_db->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
        return $stm->execute([$oid]);
    }
    return false;
}

// ============================================
// 购物车函数
// ============================================
function get_cart_session_id() {
    if (!isset($_SESSION['cart_id'])) {
        $_SESSION['cart_id'] = session_id() . '_' . uniqid();
    }
    return $_SESSION['cart_id'];
}

function get_cart_count() {
    global $_db;
    $session_id = get_cart_session_id();
    $user_id = $_SESSION['user_id'] ?? null;
    
    if ($user_id) {
        $stm = $_db->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $stm->execute([$user_id]);
    } else {
        $stm = $_db->prepare("SELECT SUM(quantity) as total FROM cart WHERE session_id = ?");
        $stm->execute([$session_id]);
    }
    $result = $stm->fetch();
    return (int)($result->total ?? 0);
}

function get_cart_items() {
    global $_db;
    $session_id = get_cart_session_id();
    $user_id = $_SESSION['user_id'] ?? null;
    
    if ($user_id) {
        $stm = $_db->prepare("
            SELECT c.*, p.name, p.price, p.image_path, p.stock
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ?
            ORDER BY c.created_at DESC
        ");
        $stm->execute([$user_id]);
    } else {
        $stm = $_db->prepare("
            SELECT c.*, p.name, p.price, p.image_path, p.stock
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.session_id = ?
            ORDER BY c.created_at DESC
        ");
        $stm->execute([$session_id]);
    }
    return $stm->fetchAll();
}

function get_cart_total() {
    $items = get_cart_items();
    $total = 0;
    foreach ($items as $item) {
        $total += floatval($item->price) * intval($item->quantity);
    }
    return $total;
}

function add_to_cart($product_id, $quantity = 1, $storage = '', $color = '') {
    global $_db;
    $session_id = get_cart_session_id();
    $user_id = $_SESSION['user_id'] ?? null;
    
    if ($user_id) {
        $stm = $_db->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND storage = ? AND color = ?");
        $stm->execute([$user_id, $product_id, $storage, $color]);
    } else {
        $stm = $_db->prepare("SELECT id, quantity FROM cart WHERE session_id = ? AND product_id = ? AND storage = ? AND color = ?");
        $stm->execute([$session_id, $product_id, $storage, $color]);
    }
    
    $existing = $stm->fetch();
    
    if ($existing) {
        $new_quantity = $existing->quantity + $quantity;
        $stm = $_db->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?");
        return $stm->execute([$new_quantity, $existing->id]);
    } else {
        if ($user_id) {
            $stm = $_db->prepare("INSERT INTO cart (session_id, user_id, product_id, quantity, storage, color) VALUES (?, ?, ?, ?, ?, ?)");
            return $stm->execute([$session_id, $user_id, $product_id, $quantity, $storage, $color]);
        } else {
            $stm = $_db->prepare("INSERT INTO cart (session_id, product_id, quantity, storage, color) VALUES (?, ?, ?, ?, ?)");
            return $stm->execute([$session_id, $product_id, $quantity, $storage, $color]);
        }
    }
}

function update_cart_item($cart_id, $quantity) {
    global $_db;
    if ($quantity <= 0) {
        return remove_from_cart($cart_id);
    }
    
    $user_id = $_SESSION['user_id'] ?? null;
    if ($user_id) {
        $stm = $_db->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
        return $stm->execute([$quantity, $cart_id, $user_id]);
    } else {
        $session_id = get_cart_session_id();
        $stm = $_db->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ? AND session_id = ?");
        return $stm->execute([$quantity, $cart_id, $session_id]);
    }
}

function remove_from_cart($cart_id) {
    global $_db;
    $user_id = $_SESSION['user_id'] ?? null;
    
    if ($user_id) {
        $stm = $_db->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        return $stm->execute([$cart_id, $user_id]);
    } else {
        $session_id = get_cart_session_id();
        $stm = $_db->prepare("DELETE FROM cart WHERE id = ? AND session_id = ?");
        return $stm->execute([$cart_id, $session_id]);
    }
}

function clear_cart() {
    global $_db;
    $user_id = $_SESSION['user_id'] ?? null;
    
    if ($user_id) {
        $stm = $_db->prepare("DELETE FROM cart WHERE user_id = ?");
        return $stm->execute([$user_id]);
    } else {
        $session_id = get_cart_session_id();
        $stm = $_db->prepare("DELETE FROM cart WHERE session_id = ?");
        return $stm->execute([$session_id]);
    }
}

function merge_cart_to_user($user_id) {
    global $_db;
    $session_id = get_cart_session_id();
    
    $stm = $_db->prepare("SELECT * FROM cart WHERE session_id = ? AND user_id IS NULL");
    $stm->execute([$session_id]);
    $guest_items = $stm->fetchAll();
    
    foreach ($guest_items as $item) {
        $stm = $_db->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ? AND storage = ? AND color = ?");
        $stm->execute([$user_id, $item->product_id, $item->storage, $item->color]);
        $existing = $stm->fetch();
        
        if ($existing) {
            $new_qty = $existing->quantity + $item->quantity;
            $stm = $_db->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $stm->execute([$new_qty, $existing->id]);
            $stm = $_db->prepare("DELETE FROM cart WHERE id = ?");
            $stm->execute([$item->id]);
        } else {
            $stm = $_db->prepare("UPDATE cart SET user_id = ? WHERE id = ?");
            $stm->execute([$user_id, $item->id]);
        }
    }
}

function create_order_from_cart($shipping_address, $shipping_phone, $notes = '') {
    global $_db;
    $user_id = $_SESSION['user_id'] ?? null;
    
    if (!$user_id) {
        return false;
    }
    
    $cart_items = get_cart_items();
    if (empty($cart_items)) {
        return false;
    }
    
    $total = 0;
    foreach ($cart_items as $item) {
        $total += floatval($item->price) * intval($item->quantity);
    }
    
    $order_no = 'ORD' . date('Ymd') . rand(1000, 9999);
    
    try {
        $_db->beginTransaction();
        
        $stm = $_db->prepare("
            INSERT INTO orders (order_no, user_id, total_amount, status, created_at) 
            VALUES (?, ?, ?, 'pending', NOW())
        ");
        $stm->execute([$order_no, $user_id, $total]);
        $order_id = $_db->lastInsertId();
        
        $stm = $_db->prepare("
            INSERT INTO order_items (order_id, product_id, quantity, price) 
            VALUES (?, ?, ?, ?)
        ");
        
        foreach ($cart_items as $item) {
            $stm->execute([$order_id, $item->product_id, $item->quantity, $item->price]);
            $stm2 = $_db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
            $stm2->execute([$item->quantity, $item->product_id]);
        }
        
        clear_cart();
        $_db->commit();
        return $order_id;
        
    } catch (Exception $e) {
        $_db->rollBack();
        return false;
    }
}

// ============================================
// 用户函数
// ============================================
function getUserByEmail($email) {
    global $_db;
    $stm = $_db->prepare("SELECT * FROM users WHERE email = ?");
    $stm->execute([$email]);
    return $stm->fetch();
}

function getUserById($id) {
    global $_db;
    $stm = $_db->prepare("SELECT * FROM users WHERE id = ?");
    $stm->execute([$id]);
    return $stm->fetch();
}

// ============================================
// 权限检查函数
// ============================================
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin';
}

function require_login() {
    if (!is_logged_in()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        redirect('login.php');
    }
}

function require_admin() {
    require_login();
    if (!is_admin()) {
        redirect('index.php');
    }
}

// ============================================
// 登录锁定函数
// ============================================
function recordLoginFailure($email) {
    global $_db;
    $stm = $_db->prepare("UPDATE users SET login_attempts = login_attempts + 1 WHERE email = ?");
    $stm->execute([$email]);
}

function resetLoginAttempts($email) {
    global $_db;
    $stm = $_db->prepare("UPDATE users SET login_attempts = 0, locked_until = NULL WHERE email = ?");
    $stm->execute([$email]);
}

function isAccountLocked($email) {
    global $_db;
    $stm = $_db->prepare("SELECT login_attempts, locked_until, is_blocked FROM users WHERE email = ?");
    $stm->execute([$email]);
    $user = $stm->fetch();
    
    if ($user) {
        if ($user->is_blocked == 1) {
            return true;
        }
        if ($user->login_attempts >= 3) {
            if ($user->locked_until && strtotime($user->locked_until) > time()) {
                return true;
            } else {
                resetLoginAttempts($email);
                return false;
            }
        }
    }
    return false;
}

function lockAccount($email) {
    global $_db;
    $locked_until = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    $stm = $_db->prepare("UPDATE users SET locked_until = ? WHERE email = ?");
    $stm->execute([$locked_until, $email]);
}

function getLockedMinutesRemaining($email) {
    global $_db;
    $stm = $_db->prepare("SELECT locked_until FROM users WHERE email = ?");
    $stm->execute([$email]);
    $user = $stm->fetch();
    
    if ($user && $user->locked_until) {
        $remaining = strtotime($user->locked_until) - time();
        if ($remaining > 0) {
            return ceil($remaining / 60);
        }
    }
    return 0;
}

// ============================================
// 用户管理函数 (Admin Block/Unblock)
// ============================================
function getAllUsers($search = '') {
    global $_db;
    $sql = "SELECT id, name, email, role, is_blocked, login_attempts, locked_until, created_at FROM users WHERE role != 'admin'";
    $params = [];
    if ($search) {
        $sql .= " AND (name LIKE ? OR email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    $sql .= " ORDER BY id DESC";
    $stm = $_db->prepare($sql);
    $stm->execute($params);
    return $stm->fetchAll();
}

function blockUser($user_id) {
    global $_db;
    $stm = $_db->prepare("UPDATE users SET is_blocked = 1, login_attempts = 0, locked_until = NULL WHERE id = ?");
    return $stm->execute([$user_id]);
}

function unblockUser($user_id) {
    global $_db;
    $stm = $_db->prepare("UPDATE users SET is_blocked = 0, login_attempts = 0, locked_until = NULL WHERE id = ?");
    return $stm->execute([$user_id]);
}

function isBlockedByAdmin($user_id) {
    global $_db;
    $stm = $_db->prepare("SELECT is_blocked FROM users WHERE id = ?");
    $stm->execute([$user_id]);
    $user = $stm->fetch();
    return $user && $user->is_blocked == 1;
}

// ============================================
// WISHLIST FUNCTIONS
// ============================================

// 获取用户收藏夹中的商品
function getWishlistItems($user_id) {
    global $_db;
    $stm = $_db->prepare("
        SELECT w.*, p.name, p.price, p.image_path, p.stock, p.ram, p.storage
        FROM wishlist w
        JOIN products p ON w.product_id = p.id
        WHERE w.user_id = ?
        ORDER BY w.created_at DESC
    ");
    $stm->execute([$user_id]);
    return $stm->fetchAll();
}

// 检查商品是否已在收藏夹
function isInWishlist($user_id, $product_id) {
    global $_db;
    $stm = $_db->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stm->execute([$user_id, $product_id]);
    return $stm->fetch() !== false;
}

// 添加到收藏夹
function addToWishlist($user_id, $product_id) {
    global $_db;
    if (isInWishlist($user_id, $product_id)) {
        return false;
    }
    $stm = $_db->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
    return $stm->execute([$user_id, $product_id]);
}

// 从收藏夹移除
function removeFromWishlist($user_id, $product_id) {
    global $_db;
    $stm = $_db->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
    return $stm->execute([$user_id, $product_id]);
}

// 获取收藏夹商品数量
function getWishlistCount($user_id) {
    global $_db;
    $stm = $_db->prepare("SELECT COUNT(*) as total FROM wishlist WHERE user_id = ?");
    $stm->execute([$user_id]);
    $result = $stm->fetch();
    return (int)($result->total ?? 0);
}
?>