<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "User listing"; 

$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}else {
    // echo "connnected successfully";
}

mysqli_set_charset($conn, 'utf8mb4');

function upload_profile_picture($file)
{
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 1024 * 1024) {
        throw new RuntimeException('The profile picture must be smaller than 1 MB.');
    }

    $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($fileInfo, $file['tmp_name']);
    finfo_close($fileInfo);

    $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
    if (!isset($extensions[$mimeType])) {
        throw new RuntimeException('Only JPG and PNG profile pictures are allowed.');
    }

    $uploadDirectory = __DIR__ . '/uploads';
    if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true)) {
        throw new RuntimeException('Unable to create the upload directory. Check folder permissions.');
    }
    if (!is_writable($uploadDirectory)) {
        throw new RuntimeException('The uploads directory is not writable by Apache.');
    }

    $fileName = bin2hex(random_bytes(16)) . '.' . $extensions[$mimeType];
    if (!move_uploaded_file($file['tmp_name'], $uploadDirectory . '/' . $fileName)) {
        throw new RuntimeException('Unable to save the profile picture.');
    }

    return 'uploads/' . $fileName;
}