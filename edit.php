<?php
require 'connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$errors = [];
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    $errors[] = 'Invalid user ID.';
} else {
    $checkStatement = mysqli_prepare($conn, 'SELECT id FROM users WHERE id = ? AND is_deleted = 0');
    mysqli_stmt_bind_param($checkStatement, 'i', $id);
    mysqli_stmt_execute($checkStatement);
    $userExists = mysqli_stmt_get_result($checkStatement)->num_rows > 0;
    mysqli_stmt_close($checkStatement);

    if (!$userExists) {
        $errors[] = 'User not found.';
    }
}

if (empty($errors)) {
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

    $statusInput = $_POST['status'] ?? null;
    $status = is_scalar($statusInput) ? filter_var($statusInput, FILTER_VALIDATE_INT) : false;
    if (!in_array($status, [0, 1], true)) {
        $errors[] = 'Status is required.';
    }

    $passwordInput = $_POST['pwd'] ?? null;
    $password = is_string($passwordInput) ? $passwordInput : '';
    if ($password !== '') {
        if (strlen($password) < 6 || strlen($password) > 20) {
            $errors[] = 'Password must be between 6 and 20 characters.';
        } elseif (!preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/', $password)) {
            $errors[] = 'Use uppercase, lowercase, a number, and a special character.';
        }
    }

    $confirmPasswordInput = $_POST['confirm_password'] ?? null;
    $confirmPassword = is_string($confirmPasswordInput) ? $confirmPasswordInput : '';
    if ($password !== '' && $confirmPassword === '') {
        $errors[] = 'Confirm Password is required.';
    } elseif ($password !== '' && $password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
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
            finfo_close($fileInfo);

            if (!in_array($mimeType, ['image/jpeg', 'image/png'], true)) {
                $errors[] = 'Only JPG and PNG pictures are allowed.';
            }
        }
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

try {
    // Only upload if a new file is provided cleanly
    $profilePicture = null;
    if (isset($_FILES['myfile']) && $_FILES['myfile']['error'] === UPLOAD_ERR_OK) {
        $profilePicture = upload_profile_picture($_FILES['myfile']);
    }

    $fields = ['full_name = ?', 'email_id = ?', 'gender = ?', 'status = ?'];
    $types = 'sssi';
    $params = [$fullName, $email, $gender, $status];

    if ($password !== '') {
        $fields[] = 'password = ?';
        $types .= 's';
        $params[] = $password;
    }

    if ($profilePicture !== null) {
        $fields[] = 'profile_picture = ?';
        $types .= 's';
        $params[] = $profilePicture;
    }
    $types .= 'i';
    $params[] = $id;

    $query = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';

    $statement = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($statement, $types, ...$params);
    
    mysqli_stmt_execute($statement);
    mysqli_stmt_close($statement);

} catch (Throwable $error) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unable to update user.']);
    exit;
}


header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'User updated successfully.']);
