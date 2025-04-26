<?php
// Start session
session_start();

// Connect to the database
require_once '../database/database.php';
$pdo = Database::connect();

// ===== Handle Adding a New Person =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_person'])) {
    $email = $_POST['email'];
    $full_name = trim($_POST['full_name']);
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $admin = 'N'; // Default new users to regular "User" (not Admin)

    // Split full name into first and last name
    $parts = explode(' ', $full_name, 2);
    $fname = $parts[0];
    $lname = $parts[1] ?? '';

    // Ensure all fields are filled
    if (!empty($fname) && !empty($lname) && !empty($email) && !empty($password) && !empty($confirm_password)) {
        if ($password === $confirm_password) {
            // Create salt and hashed password
            $salt = bin2hex(random_bytes(8)); // Generates random 16 character hex
            $hashedPassword = md5($password . $salt);

            // Insert new person into database
            $sql = "INSERT INTO iss_persons (fname, lname, email, pwd_hash, pwd_salt, admin) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$fname, $lname, $email, $hashedPassword, $salt, $admin]);

            // Redirect back to persons list
            header("Location: persons.php");
            exit();
        } else {
            // Passwords did not match
            $message = "⚠️ Passwords do not match.";
        }
    } else {
        // Missing fields
        $message = "⚠️ Please fill in all fields.";
    }
}

// ===== Handle Updating a Person =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_person'])) {
    $id = $_POST['id'];
    $email = $_POST['email'];
    $full_name = trim($_POST['full_name']);
    $parts = explode(' ', $full_name, 2);
    $fname = $parts[0];
    $lname = $parts[1] ?? '';

    // Update person in database
    $sql = "UPDATE iss_persons SET fname=?, lname=?, email=? WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$fname, $lname, $email, $id]);

    header("Location: persons.php");
    exit();
}

// ===== Handle Deleting a Person =====
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    // Delete person from database
    $sql = "DELETE FROM iss_persons WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);

    header("Location: persons.php");
    exit();
}

// ===== Fetch all Persons =====
$sql = "SELECT * FROM iss_persons";
$stmt = $pdo->query($sql);
$persons = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- ===== HTML PART: Manage Persons Page ===== -->

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Persons</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">

<!-- Header and Navigation Buttons -->
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Persons</h2>
    <div>
        <a href="issues_list.php" class="btn btn-secondary btn-sm">Back to Issues</a>
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addPersonModal">+ Add Person</button>
    </div>
</div>

<!-- Persons Table -->
<table class="table table-bordered">
    <thead class="table-dark">
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Email</th>
        <th>Admin</th> 
        <th>Actions</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($persons as $person): ?>
        <tr>
            <td><?= $person['id'] ?></td>
            <td><?= htmlspecialchars(($person['fname'] ?? '') . ' ' . ($person['lname'] ?? '')) ?></td>
            <td><?= htmlspecialchars($person['email'] ?? '') ?></td>
            <td><?= ($person['admin'] == 'Y') ? 'Admin' : 'User' ?></td>

            <td>
                <?php if ($_SESSION['admin'] == 'Y' || $_SESSION['user_id'] == $person['id']): ?>
                    <!-- Only admin or the person themselves can edit/delete -->

                    <!-- Edit Button -->
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#updatePerson<?= $person['id'] ?>">Edit</button>

                    <!-- Delete Button -->
                    <a href="?delete=<?= $person['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this person?')">Delete</a>
                <?php endif; ?>
            </td>
        </tr>

        <!-- ===== Update Person Modal ===== -->
        <div class="modal fade" id="updatePerson<?= $person['id'] ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Person</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="id" value="<?= $person['id'] ?>">

                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control mb-2"
                            value="<?= htmlspecialchars(($person['fname'] ?? '') . ' ' . ($person['lname'] ?? '')) ?>" required>

                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control mb-2" 
                            value="<?= htmlspecialchars($person['email'] ?? '') ?>" required>

                            <button type="submit" name="update_person" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    </tbody>
</table>

<!-- ===== Add New Person Modal ===== -->
<div class="modal fade" id="addPersonModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Person</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-control mb-2" required>

                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control mb-2" required>

                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control mb-2" required>

                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control mb-2" required>

                    <button type="submit" name="add_person" class="btn btn-success">Add</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

