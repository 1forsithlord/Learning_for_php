<?php
require 'connection.php';

$id = filter_var($_GET['id'] ?? $_POST['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) {
    die('Invalid user ID.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full-name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $password = $_POST['pwd'] ?? '';
    $status = filter_var($_POST['status'] ?? null, FILTER_VALIDATE_INT);

    if ($fullName === '' || strlen($fullName) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || !in_array($gender, ['M', 'F', 'O'], true) || !in_array($status, [0, 1], true)
        || ($password !== '' && (strlen($password) < 6 || strlen($password) > 20
        || !preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[^A-Za-z\d]).+$/', $password)))) {
        die('Please provide valid user details.');
    }

    try {
        $profilePicture = upload_profile_picture($_FILES['myfile'] ?? null);
        if ($profilePicture !== null && $password !== '') {
            $statement = mysqli_prepare($conn, 'UPDATE users SET full_name = ?, email_id = ?, gender = ?, password = ?, status = ?, profile_picture = ? WHERE id = ?');
          mysqli_stmt_bind_param($statement, 'ssssisi', $fullName, $email, $gender, $password, $status, $profilePicture, $id);
        } elseif ($profilePicture !== null) {
            $statement = mysqli_prepare($conn, 'UPDATE users SET full_name = ?, email_id = ?, gender = ?, status = ?, profile_picture = ? WHERE id = ?');
            mysqli_stmt_bind_param($statement, 'sssisi', $fullName, $email, $gender, $status, $profilePicture, $id);
        } elseif ($password !== '') {
            $statement = mysqli_prepare($conn, 'UPDATE users SET full_name = ?, email_id = ?, gender = ?, password = ?, status = ? WHERE id = ?');
          mysqli_stmt_bind_param($statement, 'ssssii', $fullName, $email, $gender, $password, $status, $id);
        } else {
            $statement = mysqli_prepare($conn, 'UPDATE users SET full_name = ?, email_id = ?, gender = ?, status = ? WHERE id = ?');
            mysqli_stmt_bind_param($statement, 'sssii', $fullName, $email, $gender, $status, $id);
        }
        mysqli_stmt_execute($statement);
        mysqli_stmt_close($statement);
    } catch (Throwable $error) {
        die('Unable to update user: ' . $error->getMessage());
    }

    header('Location: index.php?message=' . urlencode('User updated successfully.'));
    exit;
}

$statement = mysqli_prepare($conn, 'SELECT full_name, email_id, gender, status, profile_picture FROM users WHERE id = ?');
mysqli_stmt_bind_param($statement, 'i', $id);
mysqli_stmt_execute($statement);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($statement));
mysqli_stmt_close($statement);

if (!$user) {
    die('User not found.');
}

function escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit User</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
  <main class="container py-4">
    <h1 class="mb-4">Edit User</h1>
    <form method="POST" enctype="multipart/form-data" class="row g-3">
      <input type="hidden" name="id" value="<?= (int) $id ?>">
      <div class="col-md-6">
        <label for="full_name" class="form-label">Full Name</label>
        <input type="text" id="full_name" name="full-name" value="<?= escape($user['full_name']) ?>" maxlength="100" class="form-control" required>
      </div>
      <div class="col-md-6">
        <label for="email" class="form-label">Email</label>
        <input type="email" id="email" name="email" value="<?= escape($user['email_id']) ?>" maxlength="100" class="form-control" required>
      </div>
      <div class="col-md-4">
        <label for="gender" class="form-label">Gender</label>
        <select id="gender" name="gender" class="form-select" required>
          <option value="M" <?= $user['gender'] === 'M' ? 'selected' : '' ?>>Male</option>
          <option value="F" <?= $user['gender'] === 'F' ? 'selected' : '' ?>>Female</option>
          <option value="O" <?= $user['gender'] === 'O' ? 'selected' : '' ?>>Other</option>
        </select>
      </div>
      <div class="col-md-4">
        <label for="pwd" class="form-label">New Password (optional)</label>
        <input type="password" id="pwd" name="pwd" minlength="6" maxlength="20" class="form-control">
      </div>
      <div class="col-md-4">
        <label for="myfile" class="form-label">New Profile Picture</label>
        <input type="file" id="myfile" name="myfile" accept="image/jpeg,image/png" class="form-control">
      </div>
      <div class="col-12">
        <label class="form-label d-block">Status</label>
        <label class="me-3"><input type="radio" name="status" value="1" <?= $user['status'] ? 'checked' : '' ?>> Active</label>
        <label><input type="radio" name="status" value="0" <?= !$user['status'] ? 'checked' : '' ?>> Inactive</label>
      </div>
      <div class="col-12">
        <button type="submit" class="btn btn-primary">Save Changes</button>
        <a href="index.php" class="btn btn-secondary">Cancel</a>
      </div>
    </form>
  </main>
</body>
</html>
