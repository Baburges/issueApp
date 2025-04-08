<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    session_destroy();
    header("Location: login.php");
    exit(); // ← without this, the rest of the page still loads!
}


require '../database/database.php'; // Database connection

$pdo = Database::connect();
$error_message = "";

// Fetch persons for dropdown list
$persons_sql = "SELECT id, fname, lname FROM dsr_persons ORDER BY lname ASC";
$persons_stmt = $pdo->query($persons_sql);
$persons = $persons_stmt->fetchAll(PDO::FETCH_ASSOC);
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    if(isset($_FILES['pdf_attachment'])){
        $fileTmpPath=$_FILES['pdf_attachment']['tmp_name'];
        $fileName=$_FILES['pdf_attachment']['name'];
        $fileSize=$_FILES['pdf_attachment']['size'];
        $fileType=$_FILES['pdf_attachment']['type'];
        $fileNameCmps=explode(".",$fileName);
        $fileExtension=strtolower(end($fileNameCmps));
        if($fileExtension !=='pdf'){
            die("Only PDF files allowed");
        }
        if($fileSize>2*1024*1024){
            die("File size exceeds 2MB limit");

        }
        $newFileName=MD5(time() . $fileName).'.' . $fileExtension;
        $uploadFileDir='./uploads/';
        $dest_path=$uploadFileDir . $newFileName;

        if(!is_dir($uploadFileDir)){
            mkdir($uploadFileDir,0755,true);
        }
        if(move_uploaded_file($fileTmpPath,$dest_path)){
            $attachmentPath=$dest_path;

        }
        else
            die("error moving file");
        
    }


    if (isset($_POST['add_issue'])) {
        $short_description = trim($_POST['short_description']);
        $long_description = trim($_POST['long_description']);
        $open_date = $_POST['open_date'];
        $close_date = $_POST['close_date'];
        $priority = $_POST['priority'];
        $org = trim($_POST['organization']);
        $project = trim($_POST['project']);
        $per_id = $_POST['person_id'];
        $pdf_attachment = isset($attachmentPath) ? $attachmentPath : null;


        $sql = "INSERT INTO iss_issues (short_description, long_description, open_date, close_date, priority, org, project, per_id,pdf_attachment)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?,?)";
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

// Handle issue operations (Update, Delete)
if (isset($_POST['update_issue'])) {
    if (!($_SESSION['admin'] == "Y" || $_SESSION['user_id'] == $_POST['person_id'])) {
        header("Location:issues_list.php");
        exit();
    }
       
    $id = $_POST['id'];
    $short_description = trim($_POST['short_description']);
    $long_description = trim($_POST['long_description']);
    $open_date = $_POST['open_date'];
    $close_date = $_POST['close_date'];
    $priority = $_POST['priority'];
    $org = trim($_POST['organization']);
    $project = trim($_POST['project']);
    $per_id = $_POST['person_id'];

    $sql = "UPDATE iss_issues SET short_description=?, long_description=?, open_date=?, close_date=?, priority=?, org=?, project=?, per_id=? WHERE id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$short_description, $long_description, $open_date, $close_date, $priority, $org, $project, $per_id, $id]);

    header("Location: issues_list.php");
    exit();
}


    if (isset($_POST['delete_issue'])) {
        $id = $_POST['id'];
        $sql = "DELETE FROM iss_issues WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);

        header("Location: issues_list.php");
        exit();
    }


// Fetch all issues
$sql = "SELECT * FROM iss_issues ORDER BY open_date DESC";
$issues = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
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


       
        <!-- "+" Button to Add Issue -->
        <div class="d-flex justify-content-between align-items-center mt-3">
            <h3>All Issues</h3>
            <a href="logout.php" class="btn btn-warning">Logout</a>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addIssueModal">+</button>
        </div>

        <table class="table table-striped table-sm mt-2">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Short Description</th>
                    <th>Open Date</th>
                    <th>Close Date</th>
                    <th>Priority</th>
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
</body>
</html>

<?php Database::disconnect(); ?>
