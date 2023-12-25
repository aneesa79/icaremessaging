
<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_SESSION["user_id"])) {
        die("Unauthorized access");
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $user_id = $_SESSION["user_id"];
        $message = $_POST["message"];
        $request_id = $_POST["request_id"];
    
        // Assuming the sender's name is stored in session variables
        $first_name = $_SESSION["first_name"];
        $last_name = $_SESSION["last_name"];
        $sender_name = $first_name . " " . $last_name;

    $conn = new mysqli("localhost", "root", "", "icaremessagingdb");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    
    // Fetch sender's name
    $stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($first_name, $last_name);
    $stmt->fetch();
    $sender_name = $first_name . ' ' . $last_name;
    $stmt->close();

    // Insert message into the messages table
    $stmt = $conn->prepare("INSERT INTO messages (user_id, message, request_id, sender_name) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isis", $user_id, $message, $request_id, $sender_name);
    $stmt->execute();
    $stmt->close();

}
    $conn->close();

    header("Location: chat_student.php");
    exit();
}
?>


