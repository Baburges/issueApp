<?php
// Connect to the database
require_once '../database/database.php';

// Initialize message for user feedback
$message = '';

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize input fields
    $fname = $_POST['fname'] ?? '';
    $lname = $_POST['lname'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate that all fields are filled
    if (!empty($fname) && !empty($lname) && !empty($email) && !empty($password) && !empty($confirm_password)) {
        // Check if passwords match
        if ($password === $confirm_password) {
            // Create a random salt and hash the password
            $salt = bin2hex(random_bytes(8)); // 16-character random salt
            $hashedPassword = md5($password . $salt); // Salted password hash

            // Insert user into the database
            $pdo = Database::connect();
            $sql = "INSERT INTO iss_persons (fname, lname, email, pwd_hash, pwd_salt, admin) VALUES (?, ?, ?, ?, ?, 'N')"; // Always create non-admin
            $stmt = $pdo->prepare($sql);

            try {
                // Execute insertion
                $stmt->execute([$fname, $lname, $email, $hashedPassword, $salt]);
                $message = "✅ Account created! <a href='login.php'>Log in here</a>";
            } catch (PDOException $e) {
                // Handle duplicate email or other DB errors
                $message = "❌ Error: That email may already be in use.";
            }

            // Disconnect from database
            Database::disconnect();
        } else {
            // Passwords do not match
            $message = "⚠️ Passwords do not match.";
        }
    } else {
        // Some fields are missing
        $message = "⚠️ Please fill in all fields.";
    }
}
?>

<!-- ===== HTML PART: Registration Form ===== -->
<!DOCTYPE html>
<html>
<head>
    <title>Create New User</title>
</head>
<body>
    <h2>Create New Account</h2>

    <!-- Registration Form -->
    <form method="POST">
        <label>First Name:</label><br>
        <input type="text" name="fname" required><br><br>

        <label>Last Name:</label><br>
        <input type="text" name="lname" required><br><br>

        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>

        <label>Password:</label><br>
        <input type="password" name="password" required><br><br>

        <label>Confirm Password:</label><br>
        <input type="password" name="confirm_password" required><br><br>

        <button type="submit">Register</button>
    </form>

    <!-- Display any success or error messages -->
    <p><?= $message ?></p>
</body>
</html>
