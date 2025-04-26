<?php
require_once '../database/database.php';
$pdo = Database::connect();

// Handle Create function adding new comments
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $short = !empty($_POST['short_comment']) ? trim($_POST['short_comment']) : null;
    $long = !empty($_POST['long_comment']) ? trim($_POST['long_comment']) : null;
    $date = !empty($_POST['posted_date']) ? $_POST['posted_date'] : null;
    $iss_id = !empty($_POST['iss_id']) ? (int)$_POST['iss_id'] : null;
    session_start();
$per_id = $_SESSION['user_id'];//Get current user ID from session

    //INsert into database if all fields are valid
    if ($short && $long && $date && $iss_id && $per_id) {
        $sql = "INSERT INTO iss_comments (short_comment, long_comment, posted_date, iss_id, per_id) VALUES (?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$short, $long, $date, $iss_id, $per_id]);
    }
    //Redirect to the comments page
    header("Location: comments.php");
    exit();
}

//  Handle Update unction editing the comment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_comment'])) {
    $id = $_POST['id'];
    $short = $_POST['short_comment'];
    $long = $_POST['long_comment'];
    $date = $_POST['posted_date'];
    $iss_id = $_POST['iss_id'];
    $per_id = $_POST['per_id'];
    //Update comment in database
    $sql = "UPDATE iss_comments SET short_comment=?, long_comment=?, posted_date=?, iss_id=?, per_id=? WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$short, $long, $date, $iss_id, $per_id, $id]);
    //Redirect back to comments page
    header("Location: comments.php");
    exit();
}

// Handle Delete function deleting a comment
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    //Delete comment from database
    $sql = "DELETE FROM iss_comments WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    //Redirect back to comments page
    header("Location: comments.php");
    exit();
}

//  Get all comments for the given issue
$iss_id = isset($_GET['iss_id']) ? (int)$_GET['iss_id'] : 0;
//Prepare and execute SQL to fetch comments
$sql = "SELECT * FROM iss_comments WHERE iss_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$iss_id]);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query($sql);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Comments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-5">
    <h2>Comments</h2>

    <button class="btn btn-success mb-3" data-bs-toggle="modal" data-bs-target="#addCommentModal">+ Add Comment</button>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Short Comment</th>
                <th>Long Comment</th>
                <th>Date</th>
                <th>Issue ID</th>
                <th>Person ID</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($comments as $comment): ?>
            <tr>
                <td><?= $comment['id'] ?></td>
                <td><?= htmlspecialchars($comment['short_comment']) ?></td>
                <td><?= htmlspecialchars($comment['long_comment']) ?></td>
                <td><?= $comment['posted_date'] ?></td>
                <td><?= $comment['iss_id'] ?></td>
                <td><?= $comment['per_id'] ?></td>
                <td>
                <?php
$is_admin = ($_SESSION['admin'] === 'Admin');
$can_edit = $is_admin || $comment['per_id'] == $_SESSION['user_id'];

if ($can_edit): ?>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editComment<?= $comment['id'] ?>">Edit</button>
    <a href="?iss_id=<?= $iss_id ?>&delete=<?= $comment['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this comment?')">Delete</a>
<?php endif; ?>

                </td>
            </tr>

            <!-- Edit Modal -->
            <div class="modal fade" id="editComment<?= $comment['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form method="POST">
                            <div class="modal-header">
                                <h5 class="modal-title">Edit Comment</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <input type="hidden" name="id" value="<?= $comment['id'] ?>">

                                <label class="form-label">Short Comment</label>
                                <input type="text" name="short_comment" class="form-control mb-2" value="<?= htmlspecialchars($comment['short_comment']) ?>" required>

                                <label class="form-label">Long Comment</label>
                                <textarea name="long_comment" class="form-control mb-2" required><?= htmlspecialchars($comment['long_comment']) ?></textarea>

                                <label class="form-label">Posted Date</label>
                                <input type="date" name="posted_date" class="form-control mb-2" value="<?= $comment['posted_date'] ?>" required>

                                <label class="form-label">Issue ID</label>
                                <input type="number" name="iss_id" class="form-control mb-2" value="<?= $comment['iss_id'] ?>" required>


                                <button type="submit" name="update_comment" class="btn btn-primary">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Add Comment Modal -->
    <div class="modal fade" id="addCommentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Comment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label">Short Comment</label>
                        <input type="text" name="short_comment" class="form-control mb-2" required>

                        <label class="form-label">Long Comment</label>
                        <textarea name="long_comment" class="form-control mb-2" required></textarea>

                        <label class="form-label">Posted Date</label>
                        <input type="date" name="posted_date" class="form-control mb-2" required>

                        <label class="form-label">Issue ID</label>
                        <input type="number" name="iss_id" class="form-control mb-2" required>

                        <label class="form-label">Person ID</label>
                        <input type="number" name="per_id" class="form-control mb-2" required>

                        <button type="submit" name="add_comment" class="btn btn-success">Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
