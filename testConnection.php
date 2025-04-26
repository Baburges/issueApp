<?php 
// Include database connection
require "../database/database.php"; 

// Connect to the database
$pdo = Database::connect();

// Set PDO to throw exceptions on errors (helps debugging)
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Prepare SQL to select one person by ID
$sql = "SELECT * FROM iss_persons WHERE id = ? LIMIT 1";
$q = $pdo->prepare($sql);

// Set ID to fetch (you can change this dynamically)
$id = 1;

// Execute the prepared statement with the ID parameter
$q->execute(array($id));

// Fetch the result as an associative array
$data = $q->fetch(PDO::FETCH_ASSOC);

// Output the result
print_r($data);

// (Optional) Close database connection
// Database::disconnect();
?>
