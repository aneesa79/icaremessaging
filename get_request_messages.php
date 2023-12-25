<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login_volunteer.php");
    exit();
}

$user_id = $_SESSION["user_id"];

$conn = new mysqli("localhost", "root", "", "icaremessagingdb");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$request_id = $_GET['request_id'];

$stmt = $conn->prepare("SELECT id, user_id, message, timestamp FROM messages WHERE request_id = ? ORDER BY timestamp ASC");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$stmt->bind_result($msg_id, $user_id, $message, $timestamp);

$messages = [];
while ($stmt->fetch()) {
    $messages[] = [
        'id' => $msg_id,
        'sender_name' => $sender_name,
        'user_id' => $sender_id,
        'message' => $message,
        'timestamp' => $timestamp
    ];
}

echo json_encode($messages);

$stmt->close();
$conn->close();

?>
