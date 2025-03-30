<?php
require_once '../database/database.php';
$pdo = Database::connect();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_person'])) {
    $email = $_POST['email'];
    $full_name = trim($_POST['full_name']);
    $parts = explode(' ', $full_name, 2);
    $fname = $parts[0];
    $lname = $parts[1] ?? '';
    $admin = $_POST['admin'] ?? 0; // ✅ define admin correctly

    $sql = "INSERT INTO iss_persons (fname, lname, email, admin) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$fname, $lname, $email, $admin]);

    header("Location: persons.php");
    exit();
}





if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_person'])) {
    $id = $_POST['id'];
    $email = $_POST['email'];
    $full_name = trim($_POST['full_name']);
    $parts = explode(' ', $full_name, 2);
    $fname = $parts[0];
    $lname = $parts[1] ?? '';

    $sql = "UPDATE iss_persons SET fname=?, lname=?, email=? WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$fname, $lname, $email, $id]);

    header("Location: persons.php");
    exit();
}



// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $sql = "DELETE FROM iss_persons WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    header("Location: persons.php");
    exit();
}

// Fetch all persons
$sql = "SELECT * FROM iss_persons";
$stmt = $pdo->query($sql);
$persons = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Manage Persons</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <h2 class="mb-4">Persons</h2>

    <!-- Add Person Button -->
    <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addPersonModal">+ Add Person</button>

    <!-- Persons Table -->
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th><th>Name</th><th>Email</th><th>Actions</th>
            </tr>
        </thead>
        
        <tbody>
    <?php foreach ($persons as $person): ?>
        <tr>
            <td><?= $person['id'] ?></td>
            <td><?= htmlspecialchars(($person['fname'] ?? '') . ' ' . ($person['lname'] ?? '')) ?></td>

            <td><?= htmlspecialchars($person['email'] ?? '') ?></td>
            <td><?= $person['admin'] ? 'Admin' : 'User' ?></td>

            <td>
                <!-- Update Button -->
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#updatePerson<?= $person['id'] ?>">Edit</button>

                <!-- Delete Link -->
                <a href="?delete=<?= $person['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this person?')">Delete</a>
            </td>
        </tr>

        <!-- Update Modal -->
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
                            <input type="email" name="email" class="form-control mb-2" value="<?= htmlspecialchars($person['email'] ?? '') ?>" required>
                            <button type="submit" name="update_person" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    <?php endforeach; ?>
</tbody>

        </tbody>
    </table>

    <!-- Add Person Modal -->
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
                    
                    <label class="form-label">Admin?</label>
                    <select name="is_admin" class="form-control mb-2">
                    <option value="0">User</option>
                    <option value="1">Admin</option>
                    </select>

                        <button type="submit" name="add_person" class="btn btn-success">Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap Script -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
