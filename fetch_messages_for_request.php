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

$requestId = isset($_GET['requestId']) ? intval($_GET['requestId']) : 0;

// Prepare and execute a query to fetch messages for the given request ID
// Modify this query according to your database schema
$query = "SELECT message, sender_name, timestamp FROM messages WHERE request_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $requestId);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

// Close the statement and the connection
$stmt->close();
$conn->close();

// Output the messages in JSON format
header('Content-Type: application/json');
echo json_encode($messages);
