<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "jyotidb";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['customer_id']);
$customer_id = $isLoggedIn ? $_SESSION['customer_id'] : null;

// Function to get cart items from database
function getCartItems($customer_id, $conn) {
    $stmt = $conn->prepare("
        SELECT sc.*, p.name, p.price, p.image, p.stock 
        FROM shopping_cart sc 
        JOIN products p ON sc.product_id = p.product_id 
        WHERE sc.customer_id = :customer_id
    ");
    $stmt->bindParam(':customer_id', $customer_id);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle cart operations only if user is logged in
if (isset($_GET['action']) && $isLoggedIn) {
    $action = $_GET['action'];
    $productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    switch ($action) {
        case 'update':
            if ($productId > 0) {
                // Get product details to check stock
                $stmt = $conn->prepare("SELECT stock FROM products WHERE product_id = :id");
                $stmt->bindParam(':id', $productId);
                $stmt->execute();
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($product && $quantity > $product['stock']) {
                    $quantity = $product['stock'];
                }
                
                $update_stmt = $conn->prepare("UPDATE shopping_cart SET quantity = :quantity WHERE customer_id = :customer_id AND product_id = :product_id");
                $update_stmt->bindParam(':quantity', $quantity);
                $update_stmt->bindParam(':customer_id', $customer_id);
                $update_stmt->bindParam(':product_id', $productId);
                $update_stmt->execute();
            }
            break;

        case 'remove':
            if ($productId > 0) {
                $delete_stmt = $conn->prepare("DELETE FROM shopping_cart WHERE customer_id = :customer_id AND product_id = :product_id");
                $delete_stmt->bindParam(':customer_id', $customer_id);
                $delete_stmt->bindParam(':product_id', $productId);
                $delete_stmt->execute();
            }
            break;

        case 'clear':
            $clear_stmt = $conn->prepare("DELETE FROM shopping_cart WHERE customer_id = :customer_id");
            $clear_stmt->bindParam(':customer_id', $customer_id);
            $clear_stmt->execute();
            break;
    }
    
    // Redirect to prevent form resubmission
    header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}

// Redirect to login if trying to access cart without being logged in
if (isset($_GET['action']) && !$isLoggedIn) {
    $_SESSION['login_redirect'] = 'cart.php';
    header('Location: login.php?message=Please login to manage your cart');
    exit();
}

// Get cart items from database if logged in
$cartItems = [];
if ($isLoggedIn) {
    $cartItems = getCartItems($customer_id, $conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f4; }
        .container { max-width: 1200px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1, h2 { color: #333; }
        .user-info { text-align: right; margin-bottom: 20px; padding: 10px; background: #f8f9fa; border-radius: 5px; }
        .user-info a { color: #007bff; text-decoration: none; margin-left: 15px; }
        .user-info a:hover { text-decoration: underline; }
        
        .cart-table { width: 100%; border-collapse: collapse; margin-top: 30px; }
        .cart-table th, .cart-table td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .cart-table th { background-color: #f2f2f2; }
        .cart-item-img { max-width: 80px; height: auto; border-radius: 4px; }
        .quantity-control { display: flex; align-items: center; }
        .quantity-control input[type="number"] { width: 50px; text-align: center; margin: 0 5px; padding: 5px; border: 1px solid #ddd; border-radius: 4px; }
        .quantity-control button { background-color: #007bff; color: white; border: none; padding: 5px 10px; cursor: pointer; border-radius: 4px; }
        .quantity-control button:hover { opacity: 0.9; }
        .remove-btn { background-color: #f44336; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; display: inline-block; }
        .remove-btn:hover { opacity: 0.9; }
        .cart-total { text-align: right; font-size: 1.2em; font-weight: bold; margin-top: 20px; }
        .cart-actions { text-align: right; margin-top: 20px; }
        .cart-actions a { background-color: #f44336; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px; margin-left: 10px; }
        .cart-actions a:hover { opacity: 0.9; }
        .cart-actions .checkout-btn { background-color: #007bff; }
        .cart-actions .checkout-btn:hover { background-color: #0056b3; }
        .empty-cart-message { text-align: center; margin-top: 30px; color: #888; }
        .login-required { background-color: #ffebee; color: #c62828; padding: 10px; border-radius: 4px; text-align: center; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="user-info">
            <?php if ($isLoggedIn): ?>
                Welcome! | 
                <a href="welcome.php">⬅ Back to Home</a>
            <?php else: ?>
                <a href="login.php">Login</a> | 
                <a href="user.php">Register</a>
            <?php endif; ?>
        </div>

        <h1>Your Shopping Cart</h1>
        
        <?php if (!$isLoggedIn): ?>
            <div class="login-required">
                Please <a href="login.php">login</a> to view and manage your shopping cart.
            </div>
        <?php elseif (!empty($cartItems)): ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $cartTotal = 0;
                    foreach ($cartItems as $item):
                        $itemTotal = $item['price'] * $item['quantity'];
                        $cartTotal += $itemTotal;
                    ?>
                        <tr>
                            <td>
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item-img">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </td>
                            <td>Rs.<?php echo number_format($item['price'], 2); ?></td>
                            <td>
                                <form action="?action=update&id=<?php echo $item['product_id']; ?>" method="post" class="quantity-control">
                                    <input type="number" name="quantity" value="<?php echo htmlspecialchars($item['quantity']); ?>" min="1" max="<?php echo htmlspecialchars($item['stock']); ?>">
                                    <button type="submit">Update</button>
                                </form>
                            </td>
                            <td>Rs.<?php echo number_format($itemTotal, 2); ?></td>
                            <td>
                                <a href="?action=remove&id=<?php echo $item['product_id']; ?>" class="remove-btn">Remove</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="cart-total">
                Grand Total: Rs.<?php echo number_format($cartTotal, 2); ?>
            </div>
            <div class="cart-actions">
                <a href="?action=clear">Clear Cart</a>
                <a href="checkout.php" class="checkout-btn">Proceed to Checkout</a>
            </div>
        <?php else: ?>
            <p class="empty-cart-message">Your cart is empty.</p>
            <p style="text-align: center;"><a href="products.php">Browse Products</a></p>
        <?php endif; ?>
    </div>
</body>
</html>