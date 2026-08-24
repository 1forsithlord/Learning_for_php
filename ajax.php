<?php
require 'connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method not allowed.';
    exit;
}

$fullName = trim($_POST['full-name'] ?? '');
$email = trim($_POST['email'] ?? '');
$gender = $_POST['gender'] ?? '';
$password = $_POST['pwd'] ?? '';
$status = filter_var($_POST['status'] ?? null, FILTER_VALIDATE_INT);

if ($fullName === '' || strlen($fullName) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || !in_array($gender, ['M', 'F', 'O'], true) || strlen($password) < 6 || strlen($password) > 20
    || !preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/', $password)
    || !in_array($status, [0, 1], true)) {
    die('Please provide valid user details.');
}

try {
    $profilePicture = upload_profile_picture($_FILES['myfile'] ?? null);
    $statement = mysqli_prepare($conn, 'INSERT INTO users (full_name, email_id, gender, password, status, profile_picture) VALUES (?, ?, ?, ?, ?, ?)');
    mysqli_stmt_bind_param($statement, 'ssssis', $fullName, $email, $gender, $password, $status, $profilePicture);
    mysqli_stmt_execute($statement);
    mysqli_stmt_close($statement);
} catch (Throwable $error) {
    die('Unable to add user: ' . $error->getMessage());
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'User added successfully.']);
