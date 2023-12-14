<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (!isset($_SESSION["user_id"])) {
        die("Unauthorized access");
    }

    $user_id = $_SESSION["user_id"];
    $message_id = $_GET["id"];

    $conn = new mysqli("localhost", "root", "", "icaremessagingdb");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("UPDATE messages SET reports = reports + 1 WHERE id = ?");
    $stmt->bind_param("i", $message_id);
    $stmt->execute();

    $stmt->close();
    $conn->close();

    header("Location: chat_volunteer.php");
    exit();
}
?>
