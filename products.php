<?php
session_start();

// --- DATABASE CONNECTION ---
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ecommerce_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$feedback_message = '';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['customer_id']);
$customer_id = $isLoggedIn ? $_SESSION['customer_id'] : null;

/* ----------------- HANDLE ADD TO WISHLIST ----------------- */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_to_wishlist'])) {
    if (!$isLoggedIn) {
        $feedback_message = "<div class='error'>Please login to add products to wishlist.</div>";
    } else {
        $product_id = (int)$_POST['product_id'];
        
        // Check if product already exists in wishlist
        $check_stmt = $conn->prepare("SELECT * FROM wishlist WHERE customer_id = ? AND product_id = ?");
        $check_stmt->bind_param("ii", $customer_id, $product_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $feedback_message = "<div class='info'>Product is already in your wishlist.</div>";
        } else {
            // Add to wishlist
            $insert_stmt = $conn->prepare("INSERT INTO wishlist (customer_id, product_id) VALUES (?, ?)");
            $insert_stmt->bind_param("ii", $customer_id, $product_id);
            
            if ($insert_stmt->execute()) {
                $feedback_message = "<div class='success'>Product added to wishlist successfully! ❤️</div>";
            } else {
                $feedback_message = "<div class='error'>Error adding to wishlist: " . $insert_stmt->error . "</div>";
            }
            $insert_stmt->close();
        }
        $check_stmt->close();
    }
}

/* ----------------- HANDLE ADD TO CART ----------------- */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_to_cart'])) {
    if (!$isLoggedIn) {
        $feedback_message = "<div class='error'>Please login to add products to cart.</div>";
    } else {
        $product_id = (int)$_POST['product_id'];
        $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
        
        // Check if product exists and has enough stock
        $product_stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
        $product_stmt->bind_param("i", $product_id);
        $product_stmt->execute();
        $product_result = $product_stmt->get_result();
        
        if ($product_result->num_rows > 0) {
            $product = $product_result->fetch_assoc();
            
            if ($quantity > $product['stock']) {
                $feedback_message = "<div class='error'>Not enough stock available. Only " . $product['stock'] . " items left.</div>";
            } else {
                // Check if product already exists in cart
                $check_stmt = $conn->prepare("SELECT * FROM shopping_cart WHERE customer_id = ? AND product_id = ?");
                $check_stmt->bind_param("ii", $customer_id, $product_id);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();
                
                if ($check_result->num_rows > 0) {
                    // Update quantity if product exists
                    $update_stmt = $conn->prepare("UPDATE shopping_cart SET quantity = quantity + ? WHERE customer_id = ? AND product_id = ?");
                    $update_stmt->bind_param("iii", $quantity, $customer_id, $product_id);
                    if ($update_stmt->execute()) {
                        $feedback_message = "<div class='success'>Product quantity updated in cart!</div>";
                    } else {
                        $feedback_message = "<div class='error'>Error updating cart: " . $update_stmt->error . "</div>";
                    }
                    $update_stmt->close();
                } else {
                    // Add new item to cart
                    $insert_stmt = $conn->prepare("INSERT INTO shopping_cart (customer_id, product_id, quantity) VALUES (?, ?, ?)");
                    $insert_stmt->bind_param("iii", $customer_id, $product_id, $quantity);
                    if ($insert_stmt->execute()) {
                        $feedback_message = "<div class='success'>Product added to cart successfully!</div>";
                    } else {
                        $feedback_message = "<div class='error'>Error adding to cart: " . $insert_stmt->error . "</div>";
                    }
                    $insert_stmt->close();
                }
                $check_stmt->close();
            }
        } else {
            $feedback_message = "<div class='error'>Product not found.</div>";
        }
        $product_stmt->close();
    }
}

/* ----------------- HANDLE PRODUCT DELETION ----------------- */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_product'])) {
    $delete_id = (int)$_POST['delete_id'];

    // Delete image if exists
    $stmt = $conn->prepare("SELECT image FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (!empty($row['image']) && file_exists($row['image'])) {
            unlink($row['image']);
        }
    }
    $stmt->close();

    // Delete product from database
    $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $feedback_message = "<div class='success'>Product deleted successfully!</div>";
    } else {
        $feedback_message = "<div class='error'>Error deleting product: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

/* ----------------- HANDLE ADD PRODUCT ----------------- */
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $category_id = $_POST['category_id'];
    $image_path = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0755, true);
        $target_file = $target_dir . time() . '_' . basename($_FILES["image"]["name"]);

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_path = $target_file;
        } else {
            $feedback_message = "<div class='error'>Sorry, there was an error uploading your file.</div>";
        }
    }

    if (empty($feedback_message)) {
        if (empty($category_id)) {
            $feedback_message = "<div class='error'>You must select a category for the product.</div>";
        } else {
            $stmt = $conn->prepare("INSERT INTO products (name, description, price, stock, category_id, image) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssdiis", $name, $description, $price, $stock, $category_id, $image_path);
            if ($stmt->execute()) {
                $feedback_message = "<div class='success'>New product '$name' was added successfully!</div>";
            } else {
                $feedback_message = "<div class='error'>Error: " . $stmt->error . "</div>";
            }
            $stmt->close();
        }
    }
}

/* ----------------- FETCH ALL CATEGORIES ----------------- */
$categories_result = $conn->query("SELECT * FROM categories ORDER BY parent_id, name ASC");
$all_categories = [];
if ($categories_result) {
    while ($row = $categories_result->fetch_assoc()) {
        $all_categories[] = $row;
    }
}

/* ----------------- FETCH PRODUCTS ----------------- */
$products = [];
$selected_category_name = '';
if (isset($_GET['category_id'])) {
    $selected_category_id = (int)$_GET['category_id'];

    $cat_stmt = $conn->prepare("SELECT name FROM categories WHERE category_id = ?");
    $cat_stmt->bind_param("i", $selected_category_id);
    $cat_stmt->execute();
    $cat_result = $cat_stmt->get_result();
    if($cat_row = $cat_result->fetch_assoc()) {
        $selected_category_name = $cat_row['name'];
    }
    $cat_stmt->close();

    $category_ids_to_fetch = getAllChildCategoryIds($all_categories, $selected_category_id);

    if (!empty($category_ids_to_fetch)) {
        $placeholders = implode(',', array_fill(0, count($category_ids_to_fetch), '?'));
        $types = str_repeat('i', count($category_ids_to_fetch));
        $sql = "SELECT * FROM products WHERE category_id IN ($placeholders) ORDER BY name ASC";
        $prod_stmt = $conn->prepare($sql);
        $prod_stmt->bind_param($types, ...$category_ids_to_fetch);
        $prod_stmt->execute();
        $products_result = $prod_stmt->get_result();
        while ($row = $products_result->fetch_assoc()) {
            $products[] = $row;
        }
        $prod_stmt->close();
    }
}

/* ----------------- HELPER FUNCTIONS ----------------- */
function getAllChildCategoryIds($categories, $parentId) {
    $ids = [$parentId];
    foreach ($categories as $category) {
        if ($category['parent_id'] == $parentId) {
            $ids = array_merge($ids, getAllChildCategoryIds($categories, $category['category_id']));
        }
    }
    return $ids;
}

function hasChildren($categories, $categoryId) {
    foreach ($categories as $category) {
        if ($category['parent_id'] == $categoryId) return true;
    }
    return false;
}

function generateCustomDropdownList($categories, $parentId = NULL) {
    foreach ($categories as $category) {
        if ($category['parent_id'] == $parentId) {
            $has_children = hasChildren($categories, $category['category_id']);
            $li_class = $has_children ? 'class="has-children"' : '';
            echo "<li {$li_class} data-value='" . $category['category_id'] . "'><div class='option-content'>";
            if ($has_children) echo "<span class='toggle'></span>";
            echo "<span class='option-name'>" . htmlspecialchars($category['name']) . "</span></div>";
            if ($has_children) { echo "<ul>"; generateCustomDropdownList($categories, $category['category_id']); echo "</ul>"; }
            echo "</li>";
        }
    }
}

function displayCategoryLinks($categories, $parentId = NULL) {
    $isTopLevel = is_null($parentId);
    if ($isTopLevel) echo "<ul class='category-tree'>";
    foreach ($categories as $category) {
        if ($category['parent_id'] == $parentId) {
            echo "<li><span><a href='products.php?category_id=" . $category['category_id'] . "'>" . htmlspecialchars($category['name']) . "</a></span>";
            if (hasChildren($categories, $category['category_id'])) {
                echo "<ul>"; displayCategoryLinks($categories, $category['category_id']); echo "</ul>";
            }
            echo "</li>";
        }
    }
    if ($isTopLevel) echo "</ul>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Product Management</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
body { font-family: 'Poppins', sans-serif; background-color: #f8f9fa; margin: 0; padding: 40px; color: #333; }
.container { max-width: 1200px; margin: 0 auto; display: grid; grid-template-columns: 1fr 2fr; gap: 40px; }
.card { background-color: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
h2 { font-weight: 600; color: #004d40; border-bottom: 2px solid #eef0f3; padding-bottom: 10px; margin-top: 0; }
.form-group { margin-bottom: 20px; }
label { display: block; margin-bottom: 8px; font-weight: 500; color: #555; }
input[type="text"], input[type="number"], textarea, input[type="file"] { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; box-sizing: border-box; font-size: 14px; }
.button { padding: 12px 20px; background-color: #4CAF50; border: none; border-radius: 8px; color: white; font-size: 14px; font-weight: 600; cursor: pointer; transition: background-color 0.3s; }
.button.cart { background-color: #2196F3; }
.button.wishlist { background-color: #e91e63; }
.feedback .success { background-color: #e8f5e9; color: #2e7d32; padding: 15px; border-radius: 8px; margin-bottom: 15px; }
.feedback .error { background-color: #ffebee; color: #c62828; padding: 15px; border-radius: 8px; margin-bottom: 15px; }
.feedback .info { background-color: #e3f2fd; color: #1565c0; padding: 15px; border-radius: 8px; margin-bottom: 15px; }
input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
input[type=number] { -moz-appearance: textfield; }
.custom-select-wrapper { position: relative; }
.select-selected { background-color: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 12px; cursor: pointer; user-select: none; }
.select-items { position: absolute; background-color: #fff; top: 105%; left: 0; right: 0; z-index: 99; border: 1px solid #ddd; border-radius: 8px; max-height: 250px; overflow-y: auto; }
.select-hide { display: none; }
.select-items ul, .select-items li { list-style: none; padding: 0; margin: 0; }
.select-items .option-content { display: flex; align-items: center; padding: 10px 15px; }
.select-items .toggle { width: 20px; height: 20px; cursor: pointer; position: relative; }
.select-items .toggle::before { content: '[+]'; position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%); color: #4CAF50; }
.select-items li.open > .option-content .toggle::before { content: '[-]'; }
.Select-items .option-name { cursor: pointer; flex-grow: 1; padding-left: 5px; }
.select-items li > ul { display: none; padding-left: 20px; }
.select-items li.open > ul { display: block; }
.product-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
.product-table th, .product-table td { text-align: left; padding: 12px; border-bottom: 1px solid #eef0f3; }
.product-table th { background-color: #f8f9fa; }
.product-table img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
.no-content { color: #777; text-align: center; padding: 20px; background-color: #f8f9fa; border-radius: 8px; }
.category-tree, .category-tree ul { list-style: none; }
.category-tree a { text-decoration: none; color: #007bff; }
.category-tree a:hover { text-decoration: underline; }
.delete-button { background:#e74c3c;color:white;border:none;padding:6px 12px;border-radius:5px;cursor:pointer; }
.quantity-input { width: 60px; padding: 5px; text-align: center; }
.cart-form { display: flex; gap: 10px; align-items: center; margin-top: 10px; }
.wishlist-form { margin-top: 5px; }
.login-prompt { background-color: #fff8e1; border: 1px solid #ffd54f; padding: 8px; border-radius: 4px; margin-top: 10px; text-align: center; }
.login-prompt a { color: #007bff; text-decoration: none; }
.login-prompt a:hover { text-decoration: underline; }
.user-info { text-align: right; margin-bottom: 15px; padding: 10px; background: #f8f9fa; border-radius: 5px; }
.user-info a { color: #007bff; text-decoration: none; margin-left: 15px; }
.user-info a:hover { text-decoration: underline; }
.wishlist-icon { margin-right: 5px; }
</style>
</head>
<body>

<div class="container">
    <!-- Add Product Form -->
    <div class="card add-product-form">
        <div class="user-info">
            <?php if ($isLoggedIn): ?>
                Welcome! | 
                <a href="shopping_cart.php">View Cart</a> | 
                <a href="wishlist.php">Wishlist ❤️</a> 

            <?php else: ?>
                <a href="login.php">Login</a> | 
                <a href="user.php">Register</a>
            <?php endif; ?>
        </div>

        <h2>Add New Product</h2>
        <div class="feedback"><?php echo $feedback_message; ?></div>
        <form action="products.php<?php echo isset($_GET['category_id']) ? '?category_id='.(int)$_GET['category_id'] : ''; ?>" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">Product Name</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label>Category</label>
                <div class="custom-select-wrapper">
                    <div class="select-selected">-- Select a Category --</div>
                    <div class="select-items select-hide"><ul><?php generateCustomDropdownList($all_categories); ?></ul></div>
                    <input type="hidden" name="category_id" id="category_id_hidden" value="">
                </div>
            </div>
            <div class="form-group">
                <label for="price">Price</label>
                <input type="number" id="price" name="price" step="0.01" required>
            </div>
            <div class="form-group">
                <label for="stock">Stock</label>
                <input type="number" id="stock" name="stock" required>
            </div>
            <div class="form-group">
                <label for="image">Product Image</label>
                <input type="file" id="image" name="image">
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"></textarea>
            </div>
            <button type="submit" name="add_product" class="button">Add Product</button>
        </form>
    </div>

    <!-- Product List -->
    <div class="card product-list">
        <h2>Products in: <?php echo $selected_category_name ? htmlspecialchars($selected_category_name) : 'All Categories'; ?></h2>
        <p>Select a category below to view its products.</p>
        <div style="max-height: 200px; overflow-y: auto; border: 1px solid #eee; padding: 10px; border-radius: 8px;">
            <?php displayCategoryLinks($all_categories); ?>
        </div>

        <?php if (isset($_GET['category_id'])): ?>
            <?php if (!empty($products)): ?>
                <table class="product-table">
                    <thead>
                        <tr><th>Image</th><th>Name</th><th>Price</th><th>Stock</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php if ($product['image']): ?><img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>"><?php endif; ?></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td>Rs.<?php echo htmlspecialchars(number_format($product['price'],2)); ?></td>
                                <td><?php echo htmlspecialchars($product['stock']); ?></td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 5px;">
                                        <?php if ($isLoggedIn): ?>
                                            <form action="products.php<?php echo '?category_id='.(int)$_GET['category_id']; ?>" method="post" class="cart-form">
                                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                                <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>" class="quantity-input">
                                                <button type="submit" name="add_to_cart" class="button cart">Add to Cart</button>
                                            </form>
                                            
                                            <form action="products.php<?php echo '?category_id='.(int)$_GET['category_id']; ?>" method="post" class="wishlist-form">
                                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                                <button type="submit" name="add_to_wishlist" class="button wishlist">
                                                    <span class="wishlist-icon">❤️</span> Add to Wishlist
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <div class="login-prompt">
                                                <a href="login.php">Login to Purchase</a>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <form action="products.php<?php echo '?category_id='.(int)$_GET['category_id']; ?>" method="post" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                            <input type="hidden" name="delete_id" value="<?php echo $product['product_id']; ?>">
                                            <button type="submit" name="delete_product" class="delete-button">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-content">No products found in this category or its subcategories.</p>
            <?php endif; ?>
        <?php else: ?>
            <p class="no-content">Please select a category to see the products.</p>
        <?php endif; ?>
    </div>
</div>

<script>
const wrapper = document.querySelector('.custom-select-wrapper');
const selected = wrapper.querySelector('.select-selected');
const optionsContainer = wrapper.querySelector('.select-items');
const hiddenInput = wrapper.querySelector('#category_id_hidden');
selected.addEventListener('click', () => { optionsContainer.classList.toggle('select-hide'); });
optionsContainer.querySelectorAll('.toggle').forEach(toggle => {
    toggle.addEventListener('click', function(e) { e.stopPropagation(); this.closest('li.has-children').classList.toggle('open'); });
});
optionsContainer.querySelectorAll('.option-name').forEach(nameSpan => {
    nameSpan.addEventListener('click', function() {
        const parentLi = this.closest('li');
        selected.textContent = this.textContent;
        hiddenInput.value = parentLi.dataset.value;
        optionsContainer.classList.add('select-hide');
    });
});
window.addEventListener('click', function(e) { if (!wrapper.contains(e.target)) { optionsContainer.classList.add('select-hide'); } });
</script>

</body>
</html>