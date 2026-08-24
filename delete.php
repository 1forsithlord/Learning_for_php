<?php
require 'connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method not allowed.';
    exit;
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    die('Invalid user ID.');
}

$select = mysqli_prepare($conn, 'SELECT id FROM users WHERE id = ? AND is_deleted = 0');
mysqli_stmt_bind_param($select, 'i', $id);
mysqli_stmt_execute($select);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($select));
mysqli_stmt_close($select);

if (!$user) {
    die('User not found.');
}

$delete = mysqli_prepare($conn, 'UPDATE users SET is_deleted = 1 WHERE id = ?');
mysqli_stmt_bind_param($delete, 'i', $id);
mysqli_stmt_execute($delete);
mysqli_stmt_close($delete);

header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'User deleted successfully.']);
