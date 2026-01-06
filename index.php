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
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Commerce Store</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        /* Header Styles */
        header {
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 15px 0;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #4a4a4a;
        }
        
        .logo span {
            color: #3498db;
        }
        
        nav ul {
            display: flex;
            list-style: none;
        }
        
        nav ul li {
            margin-left: 20px;
        }
        
        nav ul li a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            padding: 5px 10px;
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        nav ul li a:hover {
            background-color: #3498db;
            color: white;
        }
        
        .user-actions a {
            margin-left: 10px;
            text-decoration: none;
            color: #333;
            padding: 5px 10px;
            border-radius: 4px;
            transition: all 0.3s;
        }
        
        .user-actions a:hover {
            background-color: #3498db;
            color: white;
        }
        
        .cart-count {
            background-color: #e74c3c;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
        }
        
        /* Hero Section */
        .hero {
            background-color: #3498db;
            color: white;
            text-align: center;
            padding: 60px 0;
            margin-bottom: 30px;
        }
        
        .hero h1 {
            font-size: 36px;
            margin-bottom: 15px;
        }
        
        .hero p {
            font-size: 18px;
            margin-bottom: 20px;
        }
        
        .btn {
            display: inline-block;
            background-color: #fff;
            color: #3498db;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background-color: #f1f1f1;
            transform: translateY(-2px);
        }
        
        /* Content Sections */
        .section-title {
            text-align: center;
            margin-bottom: 25px;
            font-size: 28px;
            color: #333;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }
        
        .categories, .products {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .category-card, .product-card {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            width: 250px;
            transition: transform 0.3s;
        }
        
        .category-card:hover, .product-card:hover {
            transform: translateY(-5px);
        }
        
        .card-img {
            height: 150px;
            background-color: #f1f1f1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #777;
        }
        
        .card-info {
            padding: 15px;
            text-align: center;
        }
        
        .card-info h3 {
            margin-bottom: 10px;
            color: #333;
        }
        
        .product-price {
            color: #e74c3c;
            font-weight: bold;
            margin: 10px 0;
        }
        
        /* Footer */
        footer {
            background-color: #333;
            color: white;
            padding: 30px 0;
            margin-top: 40px;
        }
        
        .footer-content {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }
        
        .footer-section {
            flex: 1;
            min-width: 200px;
            margin-bottom: 20px;
        }
        
        .footer-section h3 {
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .footer-section ul {
            list-style: none;
        }
        
        .footer-section ul li {
            margin-bottom: 8px;
        }
        
        .footer-section ul li a {
            color: #ddd;
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer-section ul li a:hover {
            color: #3498db;
        }
        
        .copyright {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #444;
        }
    </style>
</head>
<body>
    <!-- Header Section -->
    <header>
        <div class="container header-content">
            <div class="logo">
                E<span>Store</span>
            </div>
            
            <nav>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="categories.php">Categories</a></li>
                    <li><a href="wishlist.php">Wishlist</a></li>
                </ul>
            </nav>
            
            <div class="user-actions">
                <?php if(isset($_SESSION['customer_id'])): ?>
                    <a href="cart.php">Cart 
                        <?php
                        // Count cart items
                        try {
                            $stmt = $conn->prepare("SELECT COUNT(*) FROM shopping_cart WHERE customer_id = ?");
                            $stmt->execute([$_SESSION['customer_id']]);
                            $cart_count = $stmt->fetchColumn();
                            if ($cart_count > 0) {
                                echo '<span class="cart-count">' . $cart_count . '</span>';
                            }
                        } catch(PDOException $e) {
                            // Silent fail - don't show count if there's an error
                        }
                        ?>
                    </a>
                    <a href="user.php">My Account</a>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="user.php">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1>Welcome to Our E-Commerce Store</h1>
            <p>Discover amazing products at great prices</p>
            <a href="products.php" class="btn">Shop Now</a>
        </div>
    </section>

    <!-- Main Content -->
    <main class="container">
        <!-- Featured Categories -->
        <section>
            <h2 class="section-title">Shop by Category</h2>
            <div class="categories">
                <?php
                // Fetch categories from database
                try {
                    $stmt = $conn->query("SELECT * FROM categories WHERE parent_id IS NULL LIMIT 4");
                    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if(count($categories) > 0) {
                        foreach($categories as $category) {
                            echo '
                            <div class="category-card">
                                <div class="card-img">
                                    ' . htmlspecialchars($category['name']) . '
                                </div>
                                <div class="card-info">
                                    <h3>' . htmlspecialchars($category['name']) . '</h3>
                                    <a href="products.php?category=' . $category['category_id'] . '" class="btn">View Products</a>
                                </div>
                            </div>';
                        }
                    } else {
                        echo '<p>No categories found.</p>';
                    }
                } catch(PDOException $e) {
                    echo "<p>Unable to load categories.</p>";
                }
                ?>
            </div>
        </section>

        <!-- Featured Products -->
        <section>
            <h2 class="section-title">Featured Products</h2>
            <div class="products">
                <?php
                // Fetch featured products from database
                try {
                    $stmt = $conn->query("SELECT * FROM products LIMIT 8");
                    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if(count($products) > 0) {
                        foreach($products as $product) {
                            echo '
                            <div class="product-card">
                                <div class="card-img">
                                    Product Image
                                </div>
                                <div class="card-info">
                                    <h3>' . htmlspecialchars($product['name']) . '</h3>
                                    <div class="product-price">$' . number_format($product['price'], 2) . '</div>';
                            
                            if(isset($_SESSION['customer_id'])) {
                                echo '<a href="cart.php?action=add&product_id=' . $product['product_id'] . '" class="btn">Add to Cart</a>';
                            } else {
                                echo '<a href="login.php" class="btn">Login to Buy</a>';
                            }
                            
                            echo '
                                </div>
                            </div>';
                        }
                    } else {
                        echo '<p>No products found.</p>';
                    }
                } catch(PDOException $e) {
                    echo "<p>Unable to load products.</p>";
                }
                ?>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="products.php">Products</a></li>
                        <li><a href="categories.php">Categories</a></li>
                        <li><a href="cart.php">Shopping Cart</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>User Account</h3>
                    <ul>
                        <li><a href="user.php">Register</a></li>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="user.php">My Account</a></li>
                        <li><a href="wishlist.php">Wishlist</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h3>Contact Us</h3>
                    <ul>
                        <li>Email: support@estore.com</li>
                        <li>Phone: (123) 456-7890</li>
                        <li>Address: 123 Commerce St, City</li>
                    </ul>
                </div>
            </div>
            
            <div class="copyright">
                <p>&copy; <?php echo date('Y'); ?> E-Commerce Store. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>