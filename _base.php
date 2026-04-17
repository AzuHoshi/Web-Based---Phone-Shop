<?php
// Database connection
try {
    $_db = new PDO('mysql:dbname=phone_shop;host=localhost', 'root', '', [
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper functions
function is_get() {
    return $_SERVER['REQUEST_METHOD'] == 'GET';
}

function is_post() {
    return $_SERVER['REQUEST_METHOD'] == 'POST';
}

function redirect($url = null) {
    $url ??= $_SERVER['REQUEST_URI'];
    header("Location: $url");
    exit();
}

function h($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// FIXED: Price formatting function
function price($value) {
    if ($value === null || $value === '') {
        return 'RM 0.00';
    }
    $num = floatval($value);
    return 'RM ' . number_format($num, 2);
}

function get_file($key) {
    $f = $_FILES[$key] ?? null;
    if ($f && $f['error'] == 0) {
        return (object)$f;
    }
    return null;
}

function get($key, $default = null) {
    return $_GET[$key] ?? $default;
}

function post($key, $default = null) {
    return $_POST[$key] ?? $default;
}

// Get categories for filter
function getCats() {
    global $_db;
    $stm = $_db->query("SELECT * FROM categories ORDER BY name");
    return $stm->fetchAll();
}

// Get products with filters
function getProducts($search = '', $cat = '', $min = '', $max = '') {
    global $_db;
    $sql = "SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE 1=1";
    $params = [];
    
    if ($search) {
        $sql .= " AND p.name LIKE ?";
        $params[] = "%$search%";
    }
    if ($cat) {
        $sql .= " AND p.category_id = ?";
        $params[] = $cat;
    }
    if ($min !== '' && $min !== null && $min > 0) {
        $sql .= " AND p.price >= ?";
        $params[] = $min;
    }
    if ($max !== '' && $max !== null && $max > 0) {
        $sql .= " AND p.price <= ?";
        $params[] = $max;
    }
    
    $sql .= " ORDER BY p.id DESC";
    $stm = $_db->prepare($sql);
    $stm->execute($params);
    return $stm->fetchAll();
}

// Get single product
function getProduct($id) {
    global $_db;
    $stm = $_db->prepare("SELECT p.*, c.name as cat_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
    $stm->execute([$id]);
    return $stm->fetch();
}

// Get all orders (admin)
function getAllOrders($search = '', $status = '') {
    global $_db;
    $sql = "SELECT o.*, u.name as user_name, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE 1=1";
    $params = [];
    if ($search) {
        $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR o.order_no LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    if ($status) {
        $sql .= " AND o.status = ?";
        $params[] = $status;
    }
    $sql .= " ORDER BY o.created_at DESC";
    $stm = $_db->prepare($sql);
    $stm->execute($params);
    return $stm->fetchAll();
}

// Get single order (admin)
function getOrder($id) {
    global $_db;
    $stm = $_db->prepare("SELECT o.*, u.name as user_name, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
    $stm->execute([$id]);
    return $stm->fetch();
}

// Get order items
function getOrderItems($order_id) {
    global $_db;
    $stm = $_db->prepare("SELECT oi.*, p.name as pname FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
    $stm->execute([$order_id]);
    return $stm->fetchAll();
}

// Update order status (admin)
function updateStatus($order_id, $status) {
    global $_db;
    $stm = $_db->prepare("UPDATE orders SET status = ? WHERE id = ?");
    return $stm->execute([$status, $order_id]);
}

// Get user orders (member)
function getUserOrders($user_id) {
    global $_db;
    $stm = $_db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
    $stm->execute([$user_id]);
    return $stm->fetchAll();
}

// Get single user order (member)
function getUserOrder($order_id, $user_id) {
    global $_db;
    $stm = $_db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
    $stm->execute([$order_id, $user_id]);
    return $stm->fetch();
}

// Cancel order (member)
function cancelOrder($order_id, $user_id) {
    global $_db;
    $stm = $_db->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ? AND user_id = ? AND status IN ('pending', 'paid')");
    return $stm->execute([$order_id, $user_id]);
}

// Temporary session messages
function temp($key, $value = null) {
    if ($value === null) {
        $val = $_SESSION['temp'][$key] ?? null;
        unset($_SESSION['temp'][$key]);
        return $val;
    }
    $_SESSION['temp'][$key] = $value;
}

// ============================================
// SHOPPING CART FUNCTIONS
// ============================================

// Get or create cart session ID
function get_cart_session_id() {
    if (!isset($_SESSION['cart_id'])) {
        $_SESSION['cart_id'] = session_id() . '_' . uniqid();
    }
    return $_SESSION['cart_id'];
}

// Get total number of items in cart
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

// Get all cart items with product details
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

// Calculate cart total
function get_cart_total() {
    $items = get_cart_items();
    $total = 0;
    foreach ($items as $item) {
        $total += floatval($item->price) * intval($item->quantity);
    }
    return $total;
}

// Add item to cart
function add_to_cart($product_id, $quantity = 1) {
    global $_db;
    $session_id = get_cart_session_id();
    $user_id = $_SESSION['user_id'] ?? null;
    
    // Check if product already in cart
    if ($user_id) {
        $stm = $_db->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $stm->execute([$user_id, $product_id]);
    } else {
        $stm = $_db->prepare("SELECT id, quantity FROM cart WHERE session_id = ? AND product_id = ?");
        $stm->execute([$session_id, $product_id]);
    }
    
    $existing = $stm->fetch();
    
    if ($existing) {
        $new_quantity = $existing->quantity + $quantity;
        $stm = $_db->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?");
        return $stm->execute([$new_quantity, $existing->id]);
    } else {
        if ($user_id) {
            $stm = $_db->prepare("INSERT INTO cart (session_id, user_id, product_id, quantity) VALUES (?, ?, ?, ?)");
            return $stm->execute([$session_id, $user_id, $product_id, $quantity]);
        } else {
            $stm = $_db->prepare("INSERT INTO cart (session_id, product_id, quantity) VALUES (?, ?, ?)");
            return $stm->execute([$session_id, $product_id, $quantity]);
        }
    }
}

// Update cart item quantity
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

// Remove single item from cart
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

// Clear entire cart
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

// Merge guest cart to user cart after login
function merge_cart_to_user($user_id) {
    global $_db;
    $session_id = get_cart_session_id();
    
    $stm = $_db->prepare("SELECT * FROM cart WHERE session_id = ? AND user_id IS NULL");
    $stm->execute([$session_id]);
    $guest_items = $stm->fetchAll();
    
    foreach ($guest_items as $item) {
        $stm = $_db->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
        $stm->execute([$user_id, $item->product_id]);
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

// Create order from cart
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

// Get user by email (for login)
function getUserByEmail($email) {
    global $_db;
    $stm = $_db->prepare("SELECT * FROM users WHERE email = ?");
    $stm->execute([$email]);
    return $stm->fetch();
}
?>