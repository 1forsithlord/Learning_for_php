<?php

// Place this at the absolute top of your PHP page
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'connection.php';

// echo "<pre>";
// var_dump($conn);



// print_r($_POST);

$full_name = $_POST['full-name'];
$email = $_POST['email'];
$gender = $_POST['gender'];
$pwd = $_POST['pwd'];
$status = $_POST['status'];


$sql = "INSERT INTO users
    (full_name, email_id, gender, password, status)
    VALUES
    ('$full_name', '$email', '$gender', '$pwd', '$status')";

if (mysqli_query($conn, $sql)) {
    echo "User added successfully!";
} else {
    echo "Error: " . mysqli_error($conn);
}
// echo "form submited";
