<?php
$conn = new mysqli("localhost", "root", "", "jyotidb");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$msg = "";
$lastInsertedId = 0; // store last inserted user ID

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstName = trim($_POST["first_name"]);
    $lastName  = trim($_POST["last_name"]);
    $username  = trim($_POST["username"]);
    $email     = trim($_POST["email"]);
    $password  = $_POST["password"];
    $confirm   = $_POST["confirm_password"];

    if ($firstName && $lastName && $username && $email && $password && $confirm) {
        if ($password === $confirm) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO customers (full_name, username, email, password) VALUES (?, ?, ?, ?)");
            $fullName = $firstName . " " . $lastName;
            $stmt->bind_param("ssss", $fullName, $username, $email, $hashed);
            if ($stmt->execute()) {
                $lastInsertedId = $stmt->insert_id; // save last inserted ID
                $msg = "<p class='success'>✅ User registered successfully!</p>";
            } else {
                $msg = "<p class='error'>❌ Error: " . $stmt->error . "</p>";
            }
            $stmt->close();
        } else {
            $msg = "<p class='error'>❌ Passwords do not match.</p>";
        }
    } else {
        $msg = "<p class='error'>❌ All fields are required.</p>";
    }
}

// Fetch all users
$users = $conn->query("SELECT customer_id, full_name, username, email FROM customers");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Registration & List</title>
    <link rel="stylesheet" href="user.css">
</head>
<body>
    <div class="signup-wrapper">
        <div class="signup-box">
            <h2>Register</h2>
            <p class="subtitle">Please complete to create your account.</p>
            <?= $msg ?>

            <form method="POST">
                <div class="row">
                    <input type="text" name="first_name" placeholder="First name" required>
                    <input type="text" name="last_name" placeholder="Last name" required>
                </div>
                <input type="text" name="username" placeholder="Username" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Password" required>
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>

                <label class="terms">
                    <input type="checkbox" required> I agree to all statements included in 
                    <a href="#">terms of service</a>.
                </label>

                <button type="submit">Sign Up</button>
            </form>

            <p class="footer">Already have an account? <a href="login.php">Sign in</a></p>
        </div>
    </div>

    <div class="user-list-wrapper">
        <h2>Registered Users</h2>
        <table class="user-table">
            <tr>
                <th>ID</th>
                <th>Full Name</th>
                <th>Username</th>
                <th>Email</th>
            </tr>
            <?php
            if ($users->num_rows > 0) {
                while($row = $users->fetch_assoc()) {
                    $highlight = ($row['customer_id'] == $lastInsertedId) ? "class='highlight'" : "";
                    echo "<tr $highlight>
                            <td>{$row['customer_id']}</td>
                            <td>{$row['full_name']}</td>
                            <td>{$row['username']}</td>
                            <td>{$row['email']}</td>
                          </tr>";
                }
            } else {
                echo "<tr><td colspan='4'>No users registered yet.</td></tr>";
            }
            ?>
        </table>
    </div>
</body>
</html>
