<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login_student.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION["user_id"];
    $request_message = $_POST["request_message"];

    // Insert the request into the database (you need to establish a database connection)
    $conn = new mysqli("localhost", "root", "", "icaremessagingdb");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("INSERT INTO requests (user_id, request_message) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $request_message);

    if ($stmt->execute()) {
        // Request submitted successfully
        header("Location: chat_student.php"); // Redirect back to the chat page
        exit();
    } else {
        // Handle the error
        echo "Error: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
}
?>
