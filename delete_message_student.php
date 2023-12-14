<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login_student.php");
    exit();
}

$user_id = $_SESSION["user_id"];

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["id"])) {
    $message_id = $_GET["id"];

    $conn = new mysqli("localhost", "root", "", "icaremessagingdb");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Check if the user has permission to delete the message
    $stmt = $conn->prepare("SELECT user_id FROM messages WHERE id = ?");
    $stmt->bind_param("i", $message_id);
    $stmt->execute();
    $stmt->bind_result($message_user_id);
    $stmt->fetch();
    $stmt->close();

    if ($message_user_id == $user_id) {
        // Delete the message
        $stmt = $conn->prepare("DELETE FROM messages WHERE id = ?");
        $stmt->bind_param("i", $message_id);
        $stmt->execute();
        $stmt->close();
    }

    $conn->close();
}

header("Location: chat_student.php");
exit();
?>
