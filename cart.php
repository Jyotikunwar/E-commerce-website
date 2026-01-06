<?php
include 'db.php';
session_start();

$customer_id = isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : 1;

// ---------------- Add / Update / Delete Cart Logic ----------------
if (isset($_POST['add_cart']) || isset($_POST['update_cart']) || isset($_GET['delete'])) {
    if (isset($_POST['add_cart']) || isset($_POST['update_cart'])) {
        $product_id = (int)$_POST['product_id'];
        $qty = (int)$_POST['quantity'];

        $stmt = $conn->prepare("SELECT stock FROM products WHERE id=?");
        $stmt->execute([$product_id]);
        $stock = (int)$stmt->fetchColumn();

        if ($stock > 0) {
            if ($qty > $stock) $qty = $stock;
            if ($qty > 0) {
                $stmt = $conn->prepare("SELECT quantity FROM shopping_cart WHERE customer_id=? AND product_id=?");
                $stmt->execute([$customer_id, $product_id]);
                $existing = $stmt->fetchColumn();
                if ($existing !== false) {
                    $newQty = min($existing + $qty, $stock);
                    $stmt = $conn->prepare("UPDATE shopping_cart SET quantity=? WHERE customer_id=? AND product_id=?");
                    $stmt->execute([$newQty, $customer_id, $product_id]);
                } else {
                    $stmt = $conn->prepare("INSERT INTO shopping_cart (customer_id, product_id, quantity) VALUES (?,?,?)");
                    $stmt->execute([$customer_id, $product_id, $qty]);
                }
            } else {
                $stmt = $conn->prepare("DELETE FROM shopping_cart WHERE customer_id=? AND product_id=?");
                $stmt->execute([$customer_id, $product_id]);
            }
        } else {
            $stmt = $conn->prepare("DELETE FROM shopping_cart WHERE customer_id=? AND product_id=?");
            $stmt->execute([$customer_id, $product_id]);
        }
        header("Location: cart.php");
        exit;
    }

    if (isset($_GET['delete'])) {
        $product_id = (int)$_GET['delete'];
        $stmt = $conn->prepare("DELETE FROM shopping_cart WHERE customer_id=? AND product_id=?");
        $stmt->execute([$customer_id, $product_id]);
        header("Location: cart.php");
        exit;
    }
}

// ---------------- Fetch cart items ----------------
$stmt = $conn->prepare("SELECT sc.*, p.name, p.price, p.stock 
                        FROM shopping_cart sc 
                        JOIN products p ON sc.product_id=p.id 
                        WHERE sc.customer_id=?");
$stmt->execute([$customer_id]);
$cart = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Your Cart</title>
<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #e6f0ff;
    color: #003366;
    margin: 0;
    padding: 20px;
}

h2 {
    text-align: center;
    margin-bottom: 20px;
    color: #003366;
}

.card {
    background: #cce0ff;
    padding: 25px;
    border-radius: 12px;
    max-width: 1000px;
    margin: 0 auto 30px auto;
    box-shadow: 0 6px 15px rgba(0,0,0,0.1);
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

th, td {
    border: 1px solid #99c2ff;
    padding: 12px;
    text-align: center;
}

th {
    background: #3366cc;
    color: white;
    position: sticky;
    top: 0;
}

tr:hover { background-color: #b3c6ff; }

input[type=number] {
    width: 60px;
    padding: 6px;
    border: 1px solid #3366cc;
    border-radius: 5px;
    text-align: center;
}

button, input[type=submit], a.button {
    padding: 6px 12px;
    background: #3366cc;
    color: #fff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
    transition: 0.3s;
}

button:hover, input[type=submit]:hover, a.button:hover { background: #254a99; }

.delete-button {
    background: #cc3333;
    color: #fff;
    padding: 6px 10px;
    border-radius: 5px;
    text-decoration: none;
    cursor: pointer;
    display: inline-block;
}

.delete-button:hover { background: #992626; }

p.stock-warning {
    color: #cc0000;
    font-size: 12px;
    margin: 4px 0 0;
}

/* Responsive */
@media (max-width: 900px) {
    body { padding: 10px; }
    table, th, td { font-size: 14px; }
    input[type=number] { width: 50px; }
    button, input[type=submit], a.button { padding: 5px 10px; font-size: 14px; }
}
</style>
</head>
<body>

<div class="card">
<h2>Your Cart</h2>

<?php if (!empty($cart)): ?>
<table>
<tr>
    <th>Product</th>
    <th>Price</th>
    <th>Quantity</th>
    <th>Total</th>
    <th>Action</th>
</tr>
<?php
$total = 0;
foreach ($cart as $c):
    $subtotal = $c['price'] * $c['quantity'];
    $total += $subtotal;
?>
<tr>
    <td><?= htmlspecialchars($c['name']) ?></td>
    <td><?= number_format($c['price'], 2) ?></td>
    <td>
        <form method="POST" style="margin:0; display:flex; justify-content:center; align-items:center; gap:5px;">
            <input type="hidden" name="product_id" value="<?= $c['product_id'] ?>">
            <input type="number" name="quantity" value="<?= $c['quantity'] ?>" min="1" max="<?= $c['stock'] ?>">
            <button type="submit" name="update_cart">Update</button>
        </form>
        <?php if ($c['quantity'] >= $c['stock']): ?>
            <p class="stock-warning">Only <?= $c['stock'] ?> in stock!</p>
        <?php endif; ?>
    </td>
    <td><?= number_format($subtotal, 2) ?></td>
    <td>
        <a href="?delete=<?= $c['product_id'] ?>" class="delete-button" onclick="return confirm('Remove this product from cart?')">Delete</a>
    </td>
</tr>
<?php endforeach; ?>
<tr>
    <td colspan="3"><b>Grand Total</b></td>
    <td colspan="2"><b><?= number_format($total, 2) ?></b></td>
</tr>
</table>
<?php else: ?>
<p style="text-align:center;">Your cart is empty.</p>
<?php endif; ?>
</div>

</body>
</html>