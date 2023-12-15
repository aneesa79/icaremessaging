<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION["user_id"])) {
    // Redirect or handle the case where the user is not logged in
    header("Location: login_student.php");
    exit();
}

$user_id = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION["user_id"];
    $request_message = $_POST["request_message"];

    // Database connection
    $conn = new mysqli("localhost", "root", "", "icaremessagingdb");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Insert the new request
    $stmt = $conn->prepare("INSERT INTO requests (user_id, request_message) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $request_message);
    $stmt->execute();

    $stmt->close();
    $conn->close();

    // Redirect or handle success as needed
    header("Location: chat_student.php");
    exit();
}
?>
