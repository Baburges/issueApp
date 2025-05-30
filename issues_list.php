<?php
// Start session and ensure the user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    session_destroy();
    header("Location: login.php");
    exit(); // Without exit(), the page would continue running
}

// Connect to the database
require '../database/database.php';
$pdo = Database::connect();
$error_message = "";

// Set filter (open or all issues) and search term if given
$filter = $_GET['filter'] ?? 'open';
$search_name = trim($_GET['search_name'] ?? '');

// Handle POST form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Add a new comment
    if (isset($_POST['add_comment'])) {
        $short = trim($_POST['short_comment']);
        $long = trim($_POST['long_comment']);
        $issue_id = (int)$_POST['issue_id'];
        $date = date('Y-m-d');
        $per_id = $_SESSION['user_id']; // Logged-in user

        if ($short && $long) {
            $stmt = $pdo->prepare("INSERT INTO iss_comments (short_comment, long_comment, posted_date, iss_id, per_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$short, $long, $date, $issue_id, $per_id]);
            header("Location: issues_list.php");
            exit();
        }
    }

    // Delete a comment
    if (isset($_POST['delete_comment'])) {
        $comment_id = (int)$_POST['comment_id'];
        $stmt = $pdo->prepare("DELETE FROM iss_comments WHERE id = ?");
        $stmt->execute([$comment_id]);
        header("Location: issues_list.php");
        exit();
    }

    // Start editing a comment (redirect to open the edit form)
    if (isset($_POST['edit_comment_modal'])) {
        $comment_id = (int)$_POST['comment_id'];
        $issue_id = (int)$_POST['issue_id'];

        header("Location: issues_list.php?filter=open&edit_comment=$comment_id&issue_id=$issue_id");
        exit();
    }

    // Save edited comment
    if (isset($_POST['save_comment_edit'])) {
        $comment_id = (int)$_POST['comment_id'];
        $short = trim($_POST['short_comment']);
        $long = trim($_POST['long_comment']);

        if ($short && $long) {
            $stmt = $pdo->prepare("UPDATE iss_comments SET short_comment = ?, long_comment = ? WHERE id = ?");
            $stmt->execute([$short, $long, $comment_id]);
        }

        unset($_SESSION['edit_comment_id']); // Clear any editing session
        header("Location: issues_list.php");
        exit();
    }
}

// Fetch all persons (for dropdowns, assigning issues)
$persons_sql = "SELECT id, fname, lname FROM dsr_persons ORDER BY lname ASC";
$persons_stmt = $pdo->query($persons_sql);
$persons = $persons_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle file uploads and adding a new issue
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Handle file attachment (PDF)
    if (isset($_FILES['pdf_attachment']) && $_FILES['pdf_attachment']['error'] == UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['pdf_attachment']['tmp_name'];
        $fileName = $_FILES['pdf_attachment']['name'];
        $fileSize = $_FILES['pdf_attachment']['size'];
        $fileType = $_FILES['pdf_attachment']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // Validate file type and size
        if ($fileExtension !== 'pdf') {
            die("Only PDF files allowed");
        }
        if ($fileSize > 2 * 1024 * 1024) {
            die("File size exceeds 2MB limit");
        }

        // Save the uploaded file
        $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
        $uploadFileDir = './uploads/';
        $dest_path = $uploadFileDir . $newFileName;

        if (!is_dir($uploadFileDir)) {
            mkdir($uploadFileDir, 0755, true);
        }
        if (move_uploaded_file($fileTmpPath, $dest_path)) {
            $attachmentPath = $dest_path;
        } else {
            die("Error moving file");
        }
    } else {
        $attachmentPath = null; // No file uploaded
    }

    // Handle adding an issue
    if (isset($_POST['add_issue'])) {
        $short_description = trim($_POST['short_description']);
        $long_description = trim($_POST['long_description']);
        $open_date = $_POST['open_date'];
        $close_date = $_POST['close_date'];
        $priority = $_POST['priority'];
        $org = trim($_POST['organization']);
        $project = trim($_POST['project']);
        $per_id = $_POST['person_id']; // Assigned person
        $pdf_attachment = isset($attachmentPath) ? $attachmentPath : null;

        $sql = "INSERT INTO iss_issues (short_description, long_description, open_date, close_date, priority, org, project, per_id, pdf_attachment)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $short_description,
            $long_description,
            $open_date,
            $close_date,
            $priority,
            $org,
            $project,
            $per_id,
            $pdf_attachment
        ]);

        header("Location: issues_list.php");
        exit();
    }
}

// Handle updating or deleting issues
if (isset($_POST['update_issue'])) {
    $id = (int)$_POST['id'];

    // Confirm user can edit the issue
    $stmt = $pdo->prepare("SELECT per_id FROM iss_issues WHERE id = ?");
    $stmt->execute([$id]);
    $issue = $stmt->fetch();

    if (!$issue) {
        header("Location: issues_list.php");
        exit();
    }

    if (!($_SESSION['admin'] == "Y" || $_SESSION['user_id'] == $issue['per_id'])) {
        header("Location: issues_list.php");
        exit();
    }

    // Update issue
    $short_description = trim($_POST['short_description']);
    $long_description = trim($_POST['long_description']);
    $open_date = $_POST['open_date'];
    $close_date = $_POST['close_date'];
    $priority = $_POST['priority'];
    $org = trim($_POST['organization']);
    $project = trim($_POST['project']);
    $per_id = $_POST['person_id'];

    $sql = "UPDATE iss_issues 
            SET short_description=?, long_description=?, open_date=?, close_date=?, 
                priority=?, org=?, project=?, per_id=? 
            WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $short_description, $long_description, $open_date, $close_date,
        $priority, $org, $project, $per_id, $id
    ]);

    header("Location: issues_list.php");
    exit();
}

// Handle delete issue
if (isset($_POST['delete_issue'])) {
    $id = $_POST['id'];

    $sql = "DELETE FROM iss_issues WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);

    header("Location: issues_list.php");
    exit();
}

// Fetch all issues for display, with optional search and filter
$filter = $_GET['filter'] ?? 'open';
$search_name = trim($_GET['search_name'] ?? '');

if ($filter === 'all') {
    $sql = "
        SELECT i.*, p.fname, p.lname
        FROM iss_issues i
        LEFT JOIN iss_persons p ON i.per_id = p.id
    ";
} else {
    $sql = "
        SELECT i.*, p.fname, p.lname
        FROM iss_issues i
        LEFT JOIN iss_persons p ON i.per_id = p.id
        WHERE (i.close_date IS NULL OR i.close_date = '')
    ";
}

// Add search filter for person's name
if (!empty($search_name)) {
    $search = "%$search_name%";
    if (strpos($sql, 'WHERE') !== false) {
        $sql .= " AND (p.fname LIKE :search OR p.lname LIKE :search)";
    } else {
        $sql .= " WHERE (p.fname LIKE :search OR p.lname LIKE :search)";
    }
}

// Order by priority and then date
$sql .= "
    ORDER BY 
        CASE 
            WHEN i.priority = 'Critical' THEN 1
            WHEN i.priority = 'High' THEN 2
            WHEN i.priority = 'Medium' THEN 3
            WHEN i.priority = 'Low' THEN 4
            ELSE 5
        END,
        i.open_date DESC
";

// Execute final SQL
$stmt = $pdo->prepare($sql);
if (!empty($search_name)) {
    $stmt->bindValue(':search', $search);
}
$stmt->execute();
$issues = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Issues List - DSR</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">


</head>
<body>
    <div class="container mt-3">
        <h2 class="text-center">Issues List</h2>
        <div class="d-flex justify-content-between align-items-center mt-3">
    <h3>Issues</h3>
    <div>
    <a href="issues_list.php?filter=all" class="btn btn-<?= ($filter === 'all') ? 'primary' : 'secondary' ?> btn-sm">All Issues</a>
        <a href="issues_list.php?filter=open" class="btn btn-<?= ($filter === 'open') ? 'primary' : 'secondary' ?> btn-sm">Open Issues</a>
        
        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addIssueModal">Add Issue</button>
        <a href="persons.php" class="btn btn-info btn-sm">Manage Persons</a>
        <a href="logout.php" class="btn btn-warning btn-sm">Logout</a>
       

       
        
    </div>
</div>
<form method="GET" class="d-flex mb-3">
    <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>"> <!-- 🆕 preserve filter -->

    <input type="text" name="search_name" class="form-control me-2" placeholder="Search by Person's Name" value="<?= htmlspecialchars($_GET['search_name'] ?? '') ?>">
    <button type="submit" class="btn btn-primary">Search</button>
</form>

        <table class="table table-striped table-sm mt-2">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Short Description</th>
                    <th>Open Date</th>
                    <th>Close Date</th>
                    <th>Priority</th>
                    <th>Person</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($issues as $issue) : ?>
                    <tr>
                        <td><?= htmlspecialchars($issue['id']); ?></td>
                        <td><?= htmlspecialchars($issue['short_description']); ?></td>
                        <td><?= htmlspecialchars($issue['open_date']); ?></td>
                        <td><?= htmlspecialchars($issue['close_date']); ?></td>
                        
                        <td><?= htmlspecialchars($issue['priority']); ?></td>
                        <td>
<?php
    $fullName = '';
    if (!empty($issue['fname']) || !empty($issue['lname'])) {
        $fullName = htmlspecialchars(trim(($issue['fname'] ?? '') . ' ' . ($issue['lname'] ?? '')));
    } else {
        $fullName = '<i>Unknown</i>';
    }
    echo $fullName;
?>
</td>

                        <td>
                            <!-- R, U, D Buttons -->
                            <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#readIssue<?= $issue['id']; ?>">R</button>
                            <?php if($_SESSION['user_id'] == $issue['per_id'] || $_SESSION['admin'] == "Y") { ?>
                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#updateIssue<?= $issue['id']; ?>">U</button>
                            <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteIssue<?= $issue['id']; ?>">D</button>
                            <?php } ?>
                        </td>
                    </tr>

                    <!-- Read Modal -->
                    <div class="modal fade" id="readIssue<?= $issue['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Issue Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                <p><strong>ID:</strong> <?= htmlspecialchars($issue['id']); ?></p>
                                <p><strong>Short Description:</strong> <?= htmlspecialchars($issue['short_description']); ?></p>
                                <p><strong>Long Description:</strong> <?= htmlspecialchars($issue['long_description']); ?></p>
                                 <p><strong>Open Date:</strong> <?= htmlspecialchars($issue['open_date']); ?></p>
                                <p><strong>Close Date:</strong> <?= htmlspecialchars($issue['close_date']); ?></p>
                                <p><strong>Priority:</strong> <?= htmlspecialchars($issue['priority']); ?></p>
                                <p><strong>Organization:</strong> <?= htmlspecialchars($issue['org']); ?></p>
                                <p><strong>Project:</strong> <?= htmlspecialchars($issue['project']); ?></p>
                                <p><strong>Person ID:</strong> <?= htmlspecialchars($issue['per_id']); ?></p>

                                    <hr>
                                    <h5>Comments</h5>

                                <?php
                                // Fetch comments for this issue
                                $comment_stmt = $pdo->prepare("SELECT c.*, p.fname, p.lname 
                                   FROM iss_comments c 
                                   JOIN iss_persons p ON c.per_id = p.id 
                                   WHERE c.iss_id = ? 
                                   ORDER BY c.posted_date DESC");
                                $comment_stmt->execute([$issue['id']]);
                                 $comments = $comment_stmt->fetchAll(PDO::FETCH_ASSOC);
                                ?>

                                <?php
                                $editing_comment_id = $_GET['edit_comment'] ?? null;
                                ?>

<?php
$editing_comment_id = $_GET['edit_comment'] ?? null;
?>

<?php foreach ($comments as $comment): ?>
    <div class="border rounded p-2 mb-2">
        <small class="text-muted">
            <?= htmlspecialchars($comment['fname'] . ' ' . $comment['lname']) ?> 
            on <?= htmlspecialchars($comment['posted_date']) ?>
        </small>

        <?php if ($editing_comment_id == $comment['id'] && ($_SESSION['user_id'] == $comment['per_id'] || $_SESSION['admin'] == "Y")): ?>
            <!-- If editing this comment, show editable form -->
            <form method="post">
                <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                <input type="hidden" name="issue_id" value="<?= $issue['id'] ?>">
                <div class="mb-2">
                    <label>Short Comment</label>
                    <input type="text" name="short_comment" value="<?= htmlspecialchars($comment['short_comment']) ?>" class="form-control">
                </div>
                <div class="mb-2">
                    <label>Long Comment</label>
                    <textarea name="long_comment" class="form-control" rows="3"><?= htmlspecialchars($comment['long_comment']) ?></textarea>
                </div>
                <button type="submit" name="save_comment_edit" class="btn btn-primary btn-sm">Save Changes</button>
            </form>
        <?php else: ?>
            <!-- Normal display -->
            <p><strong>Short:</strong> <?= htmlspecialchars($comment['short_comment']) ?></p>
            <p><strong>Long:</strong><br><?= nl2br(htmlspecialchars($comment['long_comment'])) ?></p>

            <?php if ($_SESSION['user_id'] == $comment['per_id'] || $_SESSION['admin'] == "Y"): ?>
                <form method="post" class="d-inline">
                    <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                    <input type="hidden" name="issue_id" value="<?= $issue['id'] ?>">
                    <button type="submit" name="edit_comment_modal" class="btn btn-sm btn-warning">Edit</button>
                </form>
                <form method="post" class="d-inline" onsubmit="return confirm('Delete this comment?');">
                    <input type="hidden" name="comment_id" value="<?= $comment['id'] ?>">
                    <input type="hidden" name="issue_id" value="<?= $issue['id'] ?>">
                    <button type="submit" name="delete_comment" class="btn btn-sm btn-danger">Delete</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
<?php endforeach; ?>


    <hr>

    <h6>Add a Comment</h6>
    <form method="post">
        <input type="hidden" name="issue_id" value="<?= $issue['id'] ?>">
        <div class="mb-2">
            <label for="short_comment<?= $issue['id']; ?>" class="form-label">Short Comment</label>
            <input type="text" name="short_comment" id="short_comment<?= $issue['id']; ?>" class="form-control" required>
        </div>
        <div class="mb-2">
            <label for="long_comment<?= $issue['id']; ?>" class="form-label">Long Comment</label>
            <textarea name="long_comment" id="long_comment<?= $issue['id']; ?>" class="form-control" rows="3" required></textarea>
        </div>
        <button type="submit" name="add_comment" class="btn btn-primary btn-sm">Add Comment</button>
    </form>
</div>

                            </div>
                        </div>
                    </div>
<!-- Add Issue Modal -->
<div class="modal fade" id="addIssueModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Issue</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
            <form method="POST" enctype="multipart/form-data">
                    <label for="short_description_add" class="form-label">Short Description</label>
                    <input type="text" id="short_description_add" name="short_description" class="form-control mb-2" required>

                    <label for="long_description_add" class="form-label">Long Description</label>
                    <textarea id="long_description_add" name="long_description" class="form-control mb-2"></textarea>

                    <label for="open_date_add" class="form-label">Open Date</label>
                    <input type="date" id="open_date_add" name="open_date" class="form-control mb-2" required>

                    <label for="close_date_add" class="form-label">Close Date</label>
                    <input type="date" id="close_date_add" name="close_date" class="form-control mb-2">

                    <label for="priority_add" class="form-label">Priority</label>
                    <select id="priority_add" name="priority" class="form-control mb-2" required>
                        <option value="Low">Low</option>
                        <option value="Medium">Medium</option>
                        <option value="High">High</option>
                        <option value="Critical">Critical</option>
                    </select>

                    <label for="org_add" class="form-label">Organization</label>
                    <input type="text" id="org_add" name="organization" class="form-control mb-2">

                    <label for="project_add" class="form-label">Project</label>
                    <input type="text" id="project_add" name="project" class="form-control mb-2">

                    <label for="per_id_add" class="form-label">Person ID</label>
                    <input type="number" id="per_id_add" name="person_id" class="form-control mb-2" required>


                    <label for="pdf_attachement">PDF</label>
                    <input type="file" name="pdf_attachment" class="form-control mb-2" 
                    accept="application/pdf"/>

                    <button type="submit" name="add_issue" class="btn btn-primary">Add Issue</button>
                </form>
            </div>
        </div>
    </div>
</div>

                    <!-- Update Modal -->
<div class="modal fade" id="updateIssue<?= $issue['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Issue</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form method="POST">
                    <!-- Hidden ID -->
                    <input type="hidden" name="id" value="<?= $issue['id']; ?>">

                    <!-- Short Description -->
                    <label for="short_description_<?= $issue['id']; ?>" class="form-label">Short Description</label>
                    <input type="text" id="short_description_<?= $issue['id']; ?>" name="short_description" class="form-control mb-2" value="<?= htmlspecialchars($issue['short_description'] ?? ''); ?>" required>

                    <!-- Long Description -->
                    <label for="long_description_<?= $issue['id']; ?>" class="form-label">Long Description</label>
                    <textarea id="long_description_<?= $issue['id']; ?>" name="long_description" class="form-control mb-2"><?= htmlspecialchars($issue['long_description'] ?? ''); ?></textarea>

                    <!-- Open Date -->
                    <label for="open_date_<?= $issue['id']; ?>" class="form-label">Open Date</label>
                    <input type="date" id="open_date_<?= $issue['id']; ?>" name="open_date" class="form-control mb-2" value="<?= $issue['open_date'] ?? ''; ?>" required>

                    <!-- Close Date -->
                    <label for="close_date_<?= $issue['id']; ?>" class="form-label">Close Date</label>
                    <input type="date" id="close_date_<?= $issue['id']; ?>" name="close_date" class="form-control mb-2" value="<?= $issue['close_date'] ?? ''; ?>">

                    <!-- Priority -->
                    <label for="priority_<?= $issue['id']; ?>" class="form-label">Priority</label>
                    <select id="priority_<?= $issue['id']; ?>" name="priority" class="form-control mb-2" required>
                        <option value="Low" <?= ($issue['priority'] ?? '') === 'Low' ? 'selected' : ''; ?>>Low</option>
                        <option value="Medium" <?= ($issue['priority'] ?? '') === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="High" <?= ($issue['priority'] ?? '') === 'High' ? 'selected' : ''; ?>>High</option>
                        <option value="Critical" <?= ($issue['priority'] ?? '') === 'Critical' ? 'selected' : ''; ?>>Critical</option>
                    </select>

                    <!-- Organization -->
                    <label for="org_<?= $issue['id']; ?>" class="form-label">Organization</label>
                    <input type="text" id="org_<?= $issue['id']; ?>" name="organization" class="form-control mb-2" value="<?= htmlspecialchars($issue['org'] ?? ''); ?>">

                    <!-- Project -->
                    <label for="project_<?= $issue['id']; ?>" class="form-label">Project</label>
                    <input type="text" id="project_<?= $issue['id']; ?>" name="project" class="form-control mb-2" value="<?= htmlspecialchars($issue['project'] ?? ''); ?>">

                    <!-- Person ID -->
                    <label for="per_id_<?= $issue['id']; ?>" class="form-label">Person ID</label>
                    <input type="number" id="per_id_<?= $issue['id']; ?>" name="person_id" class="form-control mb-2" value="<?= htmlspecialchars($issue['per_id'] ?? ''); ?>" required>

                    <!-- Submit Button -->
                    <button type="submit" name="update_issue" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>



                    <!-- Delete Modal -->
                    <div class="modal fade" id="deleteIssue<?= $issue['id']; ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title">Confirm Deletion</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Are you sure you want to delete this issue?</p>
                                    <p><strong>Description:</strong> <?= htmlspecialchars($issue['short_description']); ?></p>
                                    <form method="POST">
                                        <input type="hidden" name="id" value="<?= $issue['id']; ?>">
                                        <button type="submit" name="delete_issue" class="btn btn-danger">Delete</button>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const openIssueId = urlParams.get('issue_id');

    if (openIssueId) {
        var myModal = new bootstrap.Modal(document.getElementById('readIssue' + openIssueId));
        myModal.show();
    }
});
</script>
</body>
</html>

</body>
</html>

<?php Database::disconnect(); ?>
