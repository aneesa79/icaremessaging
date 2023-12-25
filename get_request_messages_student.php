<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login_student.php");
    exit();
}

$user_id = $_SESSION["user_id"];

$conn = new mysqli("localhost", "root", "", "icaremessagingdb");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$request_id = $_GET['request_id'];

$stmt = $conn->prepare("SELECT user_id, sender_name, message, timestamp FROM messages WHERE request_id = ? ORDER BY timestamp ASC");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$stmt->bind_result($user_id, $sender_name, $message, $timestamp);

$messages = [];
while ($stmt->fetch()) {
    $messages[] = [
        'user_id' => $user_id,
        'sender_name' => $sender_name,
        'message' => $message,
        'timestamp' => $timestamp
    ];
}
$stmt->close();

echo json_encode($messages);
?>
