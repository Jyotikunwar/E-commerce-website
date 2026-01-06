<?php
session_start();

// --- DATABASE CONNECTION ---
// Put the connection code directly in this file to fix the error.
$servername = "localhost";
$username = "root"; // Your database username (default is root)
$password = "";     // Your database password (default is empty)
$dbname = "ecommerce_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// --- END OF DATABASE CONNECTION ---

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_identifier = $_POST['username']; // This will handle either username or email
    $password_input = $_POST['password'];

    // Use a prepared statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT customer_id, full_name, username, password FROM customers WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $user_identifier, $user_identifier);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $customer = $result->fetch_assoc();
        
        // IMPORTANT: Verify the hashed password.
        // For this to work, you must use password_hash() when you register the user.
        // Example for registration: $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        // If you are still storing plain text passwords (NOT RECOMMENDED), use this line instead:
        // if ($password_input === $customer['password']) {
        
        if (password_verify($password_input, $customer['password'])) {
            // Password is correct, start the session
            $_SESSION['customer_id'] = $customer['customer_id'];
            $_SESSION['full_name'] = $customer['full_name'];
            
            // Redirect to a dashboard or home page
            header("Location: welcome.php");
            exit();
        } else {
            $error_message = "Invalid password. Please try again.";
        }
    } else {
        $error_message = "No account found with that username or email.";
    }

    $stmt->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="login.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <h2>Welcome Back 👋</h2>
            
            <?php if (!empty($error_message)): ?>
                <p class="error-message"><?php echo $error_message; ?></p>
            <?php endif; ?>

            <form action="login.php" method="post">
                <div class="input-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <a href="#" class="forgot-password">Forgot password?</a>
                <button type="submit" class="login-button">Login</button>
            </form>
            <div class="separator">or continue with</div>
            <button class="google-button">
                <img src="https://upload.wikimedia.org/wikipedia/commons/c/c1/Google_%22G%22_logo.svg" alt="Google icon">
                Continue with Google
            </button>
            <p class="signup-link">
                Don't have an account? <a href="user.php">Sign Up</a>
            </p>
        </div>
    </div>
</body>
</html>