<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$customer_id = $_SESSION['customer_id'];

// --- DATABASE CONNECTION ---
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "jyotidb";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- REMOVE ITEM FROM WISHLIST ---
if (isset($_GET['remove'])) {
    $wishlist_id = intval($_GET['remove']);
    $conn->query("DELETE FROM wishlist WHERE wishlist_id = $wishlist_id AND customer_id = $customer_id");
    header("Location: wishlist.php");
    exit();
}

// --- FETCH WISHLIST ITEMS ---
$sql = "
    SELECT w.wishlist_id, p.name, p.price, p.image, p.description
    FROM wishlist w
    JOIN products p ON w.product_id = p.product_id
    WHERE w.customer_id = $customer_id
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            margin: 0;
            padding: 20px;
            background: #fafafa;
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 90%;
            margin: 0 auto;
            border-collapse: collapse;
            background: #fff;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 14px;
            text-align: center;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f4f4f4;
        }
        img {
            max-width: 100px;
            max-height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
        .remove-btn {
            text-decoration: none;
            color: white;
            background: crimson;
            padding: 6px 12px;
            border-radius: 6px;
            transition: 0.3s;
        }
        .remove-btn:hover {
            background: darkred;
        }
        .back-link {
            display: block;
            margin: 20px auto;
            text-align: center;
        }
        .back-link a {
            text-decoration: none;
            padding: 10px 20px;
            background: #007BFF;
            color: #fff;
            border-radius: 6px;
            transition: 0.3s;
        }
        .back-link a:hover {
            background: #0056b3;
        }
        p.empty {
            text-align: center;
            font-size: 18px;
            color: #555;
        }
    </style>
</head>
<body>
    <h2>❤️ My Wishlist</h2>

    <?php if ($result->num_rows > 0) { ?>
        <table>
            <tr>
                <th>Image</th>
                <th>Product</th>
                <th>Description</th>
                <th>Price (Rs.)</th>
                <th>Action</th>
            </tr>
            <?php while($row = $result->fetch_assoc()) { ?>
                <tr>
                    <td>
                        <img src="<?php echo !empty($row['image']) ? htmlspecialchars($row['image']) : 'uploads/no-image.png'; ?>" alt="Product Image">
                    </td>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars(substr($row['description'], 0, 50)) . '...'; ?></td>
                    <td><?php echo number_format($row['price'], 2); ?></td>
                    <td>
                        <a class="remove-btn" href="wishlist.php?remove=<?php echo $row['wishlist_id']; ?>" onclick="return confirm('Are you sure you want to remove this item?')">Remove</a>
                    </td>
                </tr>
            <?php } ?>
        </table>
    <?php } else { ?>
        <p class="empty">Your wishlist is empty 💔</p>
    <?php } ?>

    <div class="back-link">
        <a href="welcome.php">⬅ Back to Home</a>
    </div>
</body>
</html>
