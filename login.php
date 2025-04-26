<?php
// Start a new session or resume existing one
session_start();

// Include database connection
require '../database/database.php';
$pdo = Database::connect();

// Initialize error message variable
$error = '';

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get and sanitize user inputs
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Check if both email and password were entered
    if (!empty($email) && !empty($password)) {
        try {
            // Prepare SQL statement to fetch user by email
            $stmt = $pdo->prepare("SELECT id, fname, lname, pwd_hash, pwd_salt, admin FROM iss_persons WHERE email = :email");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            
            // Check if exactly one user was found
            if ($stmt->rowCount() == 1) {
                // Fetch user details
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // Extract needed fields
                $id = $user['id'];
                $fname = $user['fname'];
                $lname = $user['lname'];
                $stored_hash = $user['pwd_hash'];
                $stored_salt = $user['pwd_salt'];
                $admin = $user['admin'];
                
                // Hash the input password using the stored salt
                $hashed_input_pwd = md5($password . $stored_salt);

                // Compare the stored hash with the input password hash
                if ($hashed_input_pwd === $stored_hash) {
                    // Password is correct → Set session variables
                    $_SESSION['user_id'] = $id;
                    $_SESSION['user_name'] = $fname . ' ' . $lname;
                    $_SESSION['email'] = $email;
                    $_SESSION['admin'] = $admin;

                    // Disconnect from database
                    Database::disconnect();

                    // Redirect to the main issues list
                    header("Location: issues_list.php");
                    exit();
                } else {
                    // Password does not match
                    $error = "Invalid email or password.";
                }
            } else {
                // No user found with that email
                $error = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            // Database error occurred
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        // Missing email or password fields
        $error = "Please enter both email and password.";
    }
}

// Close the database connection
Database::disconnect();
?>

<!-- HTML PART: Login Form -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ISS</title>
</head>
<body>
    <h2>Issue Tracking System - Login</h2>

    <!-- Display error message if any -->
    <?php if ($error): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>

    <!-- Login Form -->
    <form method="POST" action="login.php">
        <label>Email:</label>
        <input type="email" name="email" required>
        <br>

        <label>Password:</label>
        <input type="password" name="password" required>
        <br>

        <button type="submit">Login</button>
    </form>

    <!-- Link to Register a New Account -->
    <p>Don't have an account? <a href="register.php" class="btn btn-link">Create New User</a></p>
</body>
</html>


