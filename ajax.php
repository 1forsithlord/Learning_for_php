<?php
require 'connection.php';
// The header() describes the format, while json_encode() generates the formatted data.
header('Content-Type: application/json');

function json_response($data, $status = 200)
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function get_user_by_id($conn, $id)
{
    $statement = mysqli_prepare($conn, 'SELECT id, full_name, email_id, gender, status, profile_picture FROM users WHERE id = ? AND is_deleted = 0');
    mysqli_stmt_bind_param($statement, 'i', $id);
    mysqli_stmt_execute($statement);
    $user = mysqli_fetch_assoc(mysqli_stmt_get_result($statement));
    mysqli_stmt_close($statement);
    return $user ?: null;
}

function get_users($conn, $nameSearch = '', $emailSearch = '', $genderSearch = '', $statusSearch = '')
{
    if ($genderSearch !== '') {
        $genderSearch = strtolower(trim((string) $genderSearch));
        $genderSearch = ['m' => 'male', 'f' => 'female', 'o' => 'other'][$genderSearch] ?? $genderSearch;
    }

    if ($statusSearch !== '') {
        $statusSearch = strtolower(trim((string) $statusSearch));
        $statusSearch = ['1' => 'active', '0' => 'inactive', 'active' => 'active', 'inactive' => 'inactive'][$statusSearch] ?? $statusSearch;
    }

    $query = "SELECT id, full_name, email_id, gender, status, profile_picture
        FROM users
        WHERE is_deleted = 0
        AND (? = '' OR LOWER(full_name) LIKE LOWER(CONCAT( ? , '%')))
        AND (? = '' OR LOWER(email_id) LIKE LOWER(CONCAT('%', ?, '%')))
        AND (? = '' OR LOWER(CASE gender WHEN 'M' THEN 'male' WHEN 'F' THEN 'female' WHEN 'O' THEN 'other' END) LIKE LOWER(CONCAT('%', ?, '%')))
        AND (? = '' OR LOWER(CASE status WHEN 1 THEN 'active' ELSE 'inactive' END) LIKE LOWER(CONCAT('%', ?, '%')))
        ORDER BY id DESC";
    $statement = mysqli_prepare($conn, $query);
    if (!$statement) {
        json_response(['success' => false, 'message' => 'Unable to load users.'], 500);
    }

    mysqli_stmt_bind_param(
        $statement,
        'ssssssss',
        $nameSearch,
        $nameSearch,
        $emailSearch,
        $emailSearch,
        $genderSearch,
        $genderSearch,
        $statusSearch,
        $statusSearch
    );

    mysqli_stmt_execute($statement);
    $result = mysqli_stmt_get_result($statement);
    $users = [];
    while ($user = mysqli_fetch_assoc($result)) {
        $users[] = $user;
    }
    mysqli_stmt_close($statement);
    return $users;
}

function validate_user_input($conn, $mode, $id)
{
    $errors = [];
    if ($mode === 'edit') {
        if (!$id) {
            $errors[] = 'Invalid user ID.';
        } elseif (!get_user_by_id($conn, $id)) {
            $errors[] = 'User not found.';
        }
    }

    $fullNameInput = $_POST['full-name'] ?? null;
    $fullName = is_string($fullNameInput) ? trim($fullNameInput) : '';
    if ($fullName === '') {
        $errors[] = 'Full Name is required.';
    } elseif (strlen($fullName) > 100) {
        $errors[] = 'Name must be under 100 characters.';
    }

    $emailInput = $_POST['email'] ?? null;
    $email = is_string($emailInput) ? trim($emailInput) : '';
    if ($email === '') {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }

    $genderInput = $_POST['gender'] ?? null;
    $gender = is_string($genderInput) ? $genderInput : '';
    if (!in_array($gender, ['M', 'F', 'O'], true)) {
        $errors[] = 'Gender is required.';
    }

    $passwordInput = $_POST['pwd'] ?? null;
    $password = is_string($passwordInput) ? $passwordInput : '';
    if ($mode === 'add' && $password === '') {
        $errors[] = 'Password is required.';
    } elseif ($password !== '' && (strlen($password) < 6 || strlen($password) > 20 || !preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/', $password))) {
        $errors[] = 'Use uppercase, lowercase, a number, and a special character.';
    }

    $confirmPasswordInput = $_POST['confirm_password'] ?? null;
    $confirmPassword = is_string($confirmPasswordInput) ? $confirmPasswordInput : '';
    if ($mode === 'add' && $confirmPassword === '') {
        $errors[] = 'Confirm Password is required.';
    } elseif ($password !== '' && $confirmPassword === '') {
        $errors[] = 'Confirm Password is required.';
    } elseif ($password !== '' && $password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    $statusInput = $_POST['status'] ?? null;
    $status = is_scalar($statusInput) ? filter_var($statusInput, FILTER_VALIDATE_INT) : false;
    if (!in_array($status, [0, 1], true)) {
        $errors[] = 'Status is required.';
    }

    $file = $_FILES['myfile'] ?? null;
    if ($file !== null && $file['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Unable to upload the profile picture.';
        } elseif ($file['size'] > 1024 * 1024) {
            $errors[] = 'Profile picture must be smaller than 1 MB.';
        } else {
            $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($fileInfo, $file['tmp_name']);
            if (!in_array($mimeType, ['image/jpeg', 'image/png'], true)) {
                $errors[] = 'Only JPG and PNG pictures are allowed.';
            }
        }
    }

    return [$errors, $fullName, $email, $gender, $password, $status, $file];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? 'list_users';
    if ($action === 'get_user') {
        // Step 2: Find the selected active user and return it as JSON.
        $id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        $user = $id ? get_user_by_id($conn, $id) : null;
        json_response($user ? ['success' => true, 'user' => $user] : ['success' => false, 'message' => 'User not found.'], $user ? 200 : 404);
    }
    if ($action === 'list_users') {
        $nameSearch = trim((string) ($_GET['name'] ?? ''));
        $emailSearch = trim((string) ($_GET['email'] ?? ''));
        $genderSearch = trim((string) ($_GET['gender'] ?? ''));
        $statusSearch = trim((string) ($_GET['status'] ?? ''));
        json_response(['success' => true, 'users' => get_users($conn, $nameSearch, $emailSearch, $genderSearch, $statusSearch)]);
    }
    json_response(['success' => false, 'message' => 'Unknown action.'], 400);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Method not allowed.'], 405);
}

if (($_POST['action'] ?? 'save') === 'delete') {
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id || !get_user_by_id($conn, $id)) {
        json_response(['success' => false, 'message' => 'User not found.'], 422);
    }
    $delete = mysqli_prepare($conn, 'UPDATE users SET is_deleted = 1 WHERE id = ?');
    mysqli_stmt_bind_param($delete, 'i', $id);
    mysqli_stmt_execute($delete);
    mysqli_stmt_close($delete);
    json_response(['success' => true, 'message' => 'User deleted successfully.']);
}

$mode = (isset($_POST['id']) && $_POST['id'] !== '') ? 'edit' : 'add';
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
// An ID means edit mode; no ID means add mode.
[$errors, $fullName, $email, $gender, $password, $status, $file] = validate_user_input($conn, $mode, $id);
if ($errors) {
    json_response(['success' => false, 'message' => implode("\n", $errors), 'errors' => $errors], 422);
}

if ($mode === 'edit') {
    $currentUser = get_user_by_id($conn, $id);
    $hasNewProfilePicture = $file !== null && $file['error'] === UPLOAD_ERR_OK;
    $hasChanges = $currentUser
        && ($currentUser['full_name'] !== $fullName
            || $currentUser['email_id'] !== $email
            || $currentUser['gender'] !== $gender
            || (int) $currentUser['status'] !== $status
            || $password !== ''
            || $hasNewProfilePicture);

    if (!$hasChanges) {
        json_response(['success' => true, 'changed' => false]);
    }
}

try {
    if ($mode === 'add') {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $profilePicture = upload_profile_picture($file);
        $statement = mysqli_prepare($conn, 'INSERT INTO users (full_name, email_id, gender, password, status, profile_picture) VALUES (?, ?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($statement, 'ssssis', $fullName, $email, $gender, $hashedPassword, $status, $profilePicture);
        mysqli_stmt_execute($statement);
        mysqli_stmt_close($statement);
        json_response(['success' => true, 'message' => 'User added successfully.']);
    }

    // Step 6: Update the existing database row for edit mode.
    $profilePicture = null;
    if ($file !== null && $file['error'] === UPLOAD_ERR_OK) {
        $profilePicture = upload_profile_picture($file);
    }
    $fields = ['full_name = ?', 'email_id = ?', 'gender = ?', 'status = ?'];
    $types = 'sssi';
    $params = [$fullName, $email, $gender, $status];
    if ($password !== '') {
        $fields[] = 'password = ?';
        $types .= 's';
        $params[] = password_hash($password, PASSWORD_DEFAULT);
    }
    if ($profilePicture !== null) {
        $fields[] = 'profile_picture = ?';
        $types .= 's';
        $params[] = $profilePicture;
    }
    $types .= 'i';
    $params[] = $id;
    $statement = mysqli_prepare($conn, 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?');
    mysqli_stmt_bind_param($statement, $types, ...$params);
    mysqli_stmt_execute($statement);
    mysqli_stmt_close($statement);
    // Step 7: Tell the browser that the database update succeeded.
    json_response(['success' => true, 'message' => 'User updated successfully.']);
} catch (Throwable $error) {
    json_response(['success' => false, 'message' => 'Unable to save user.'], 500);
}
