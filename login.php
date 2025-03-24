<?php
session_start();
require '../database/database.php'; // Include database connection
$pdo = Database::connect();

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (!empty($email) && !empty($password)) {
        try {
            // Prepare SQL statement
            $stmt = $pdo->prepare("SELECT id, fname, lname, pwd_hash, pwd_salt FROM iss_persons WHERE email = :email");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            
            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // Extract values
                $id = $user['id'];
                $fname = $user['fname'];
                $lname = $user['lname'];
                $stored_hash = $user['pwd_hash'];
                $stored_salt = $user['pwd_salt'];

                
                // Hash the input password with the stored salt
                $hashed_input_pwd = md5($password . $stored_salt);

                
                if ($hashed_input_pwd === $stored_hash) {
                    // Authentication successful, set session variables
                    $_SESSION['user_id'] = $id;
                    $_SESSION['user_name'] = $fname . ' ' . $lname;
                    $_SESSION['email'] = $email;

                    // Close connection
                    Database::disconnect();

                    header("Location: issues_list.php");
                    exit();
                } else {
                    $error = "Invalid email or password.";
                }
            } else {
                $error = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = "Please enter both email and password.";
    }
}

// Close database connection
Database::disconnect();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ISS</title>
</head>
<body>
    <h2>Issue Tracking System - Login</h2>
    <?php if ($error): ?>
        <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
    <?php endif; ?>
    
    <form method="POST" action="login.php">
        <label>Email:</label>
        <input type="email" name="email" required>
        <br>
        <label>Password:</label>
        <input type="password" name="password" required>
        <br>
        <button type="submit">Login</button>
    </form>
</body>
</html>

