<?php
require 'connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

$errors = [];
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

if (!$id) {
    $errors[] = 'Invalid user ID.';
} else {
    $select = mysqli_prepare($conn, 'SELECT id FROM users WHERE id = ? AND is_deleted = 0');
    mysqli_stmt_bind_param($select, 'i', $id);
    mysqli_stmt_execute($select);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($select));
    mysqli_stmt_close($select);

    if (!$user) {
        $errors[] = 'User not found.';
    }
}

if ($errors) {
    http_response_code(422);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => implode("\n", $errors),
        'errors' => $errors
    ]);
    exit;
}

$delete = mysqli_prepare($conn, 'UPDATE users SET is_deleted = 1 WHERE id = ?');
mysqli_stmt_bind_param($delete, 'i', $id);
mysqli_stmt_execute($delete);
mysqli_stmt_close($delete);

header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'User deleted successfully.']);
