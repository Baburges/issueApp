<?php
session_start();  // Start the session to store user data after successful login

// Database connection setup
$host = 'localhost';
$dbname = 'dsr_db';  // replace with your actual database name
$username = 'root';  // replace with your actual database username
$password = '';  // replace with your actual database password
$conn = new mysqli($host, $username, $password, $dbname);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize user input
    $email = trim($_POST['email']);
    $input_password = trim($_POST['password']);
    
    // Query the database to retrieve the salt and hashed password
    $stmt = $conn->prepare("SELECT pwd_hash, pwd_salt, admin FROM iss_persons WHERE email = ?");
    $stmt->bind_param("s", $email);  // Bind the email parameter
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($stored_pwd_hash, $stored_salt, $admin_status);
        $stmt->fetch();

        // Generate the hash of the input password with the stored salt
        $input_pwd_hash = md5($input_password . $stored_salt);
        
        // Check if the password hashes match
        if ($input_pwd_hash === $stored_pwd_hash) {
            // Successful login
            $_SESSION['email'] = $email;  // Store email in session to keep track of the user
            $_SESSION['admin'] = $admin_status;  // Store admin status in session
            header("Location: issues_list.php");  // Redirect to issues list
            exit;
        } else {
            // Incorrect password
            $error_message = "Invalid email or password.";
        }
    } else {
        // Email does not exist
        $error_message = "Invalid email or password.";
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
    <title>Login - Department Status Report (DSR)</title>
</head>
<body>
    <h2>Login</h2>
    <?php if (isset($error_message)): ?>
        <p style="color: red;"><?php echo $error_message; ?></p>
    <?php endif; ?>
    <form action="login.php" method="post">
        <label for="email">Email: </label>
        <input type="email" id="email" name="email" required><br><br>
        <label for="password">Password: </label>
        <input type="password" id="password" name="password" required><br><br>
        <button type="submit">Login</button>
    </form>
</body>
</html>

