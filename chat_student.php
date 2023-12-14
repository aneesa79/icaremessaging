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

// Fetch user profile information
$userProfileStmt = $conn->prepare("SELECT first_name, last_name,email, user_pic, user_type FROM users WHERE id = ?");
$userProfileStmt->bind_param("i", $user_id);
$userProfileStmt->execute();
$userProfileStmt->bind_result($first_name, $last_name, $email, $user_pic, $user_type);
$userProfileStmt->fetch();
$userProfileStmt->close();

// Handle message sending (including file uploads)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $message = $_POST["message"];

    $stmt = $conn->prepare("INSERT INTO messages (sender_name, user_id, message) VALUES (?, ?, ?)");
    $stmt->bind_param("sis", $user_name, $user_id, $message);
    $user_name = $first_name . " " . $last_name; // Use the user's full name
    $stmt->execute();
    $stmt->close();
}

// Fetch all messages with sender's name
$stmt = $conn->prepare("SELECT id, sender_name, user_id, message, timestamp FROM messages ORDER BY timestamp ASC");
$stmt->execute();
$stmt->bind_result($msg_id, $sender_name, $sender_id, $message, $timestamp);

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

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Chat</title>

    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
            overflow: hidden; /* Hide page overflow */
        }

        .chat-container {
            display: flex;
            flex: 1;
            overflow: hidden; /* Hide content overflow */
        }

        .profile-container {
            width: 250px;
            padding: 20px;
            background-color: #2ecc71;
            color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start; /* Align content at the top */
            position: sticky;
            top: 0; /* Stick to the top */
            height: 100vh;
        }

        .profile-pic {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin-bottom: 10px;
        }

        .profile-details {
            text-align: center;
        }

        .profile-details p {
            margin: 5px 0;
        }

        #logout-btn {
            font-size: 16px;
            cursor: pointer;
            padding: 10px;
            border: none;
            border-radius: 4px;
            margin-top: auto; /* Push the button to the bottom */
            background-color: #2980b9;
            color: #fff;
            display: block;
            text-decoration: none; /* Remove underline */
            transition: background-color 0.3s;
        }

        #logout-btn:hover {
            background-color: #21618c;
        }

        .chat-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto; /* Enable vertical scroll */
            height: 100vh;
        }

        .message {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 15px;
        }

        .message p {
            margin: 0;
        }

        .message strong {
            color: #3498db;
        }

        .message .timestamp {
            color: #777;
            font-size: 12px;
        }

        .actions a {
            margin-right: 10px;
            color: #3498db;
            text-decoration: none;
            cursor: pointer;
        }

        .actions a:hover {
            text-decoration: underline;
        }

        .report-icon, .delete-icon {
            color: #e74c3c;
        }

        #reply-text {
            width: calc(80% - 20px);
            box-sizing: border-box;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            display: inline-block; /* Display the textarea and button inline */
            vertical-align: top; /* Align the textarea to the top */
        }

        #reply-text:focus {
            outline: none;
            border-color: #3498db;
        }

        #send-button {
            background-color: #3498db;
            color: #fff;
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: inline-block; /* Display the textarea and button inline */
            vertical-align: top; /* Align the button to the top */
        }

        #send-button:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-content">
            <h2>Welcome to the Student Chat</h2>
            <?php foreach ($messages as $msg): ?>
                <div class="message">
                    <p>
                        <span class="timestamp"><?= $msg['timestamp'] ?></span><br>
                        <strong><?= $msg['sender_name'] ?>:</strong> <?= nl2br($msg['message']) ?>
                    </p>
                    <div class="actions">
                        <a href="#" class="report-icon" onclick="reportMessage(<?= $msg['id'] ?>)">&#128681; Report</a>
                        <?php if ($user_id == $msg['user_id']): ?>
                            <a href="#" class="delete-icon" onclick="confirmDelete(<?= $msg['id'] ?>)">&#128465; Delete</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <form method="post" action="send_message_student.php" enctype="multipart/form-data">
                <textarea id="reply-text" name="message" rows="4" required></textarea>
                <input id="send-button" type="submit" value="Send">
            </form>
        </div>

        <div class="profile-container">
            <!-- User Profile Information -->
            <img src="<?= $user_pic ?>" alt="Profile Picture" class="profile-pic">
            <div class="profile-details">
                <p><strong>Name:</strong> <?= $first_name . " " . $last_name ?></p>
                <p><strong>Email:</strong> <?= $email ?></p>
                <p><strong>Type:</strong> <?= $user_type ?></p>
                <a id="logout-btn" href="#" onclick="logoutUser()">Logout</a>
            </div>
        </div>
    </div>

    <script>
        function reportMessage(messageId) {
            var confirmReport = confirm("Do you want to report this message?");
            if (confirmReport) {
                window.location.href = "report_message_student.php?id=" + messageId;
            }
        }

        function confirmDelete(messageId) {
            var confirmDelete = confirm("Are you sure you want to delete this message?");
            if (confirmDelete) {
                window.location.href = "delete_message_student.php?id=" + messageId;
            }
        }

        function logoutUser() {
            // You can redirect the user to the logout page or perform any other logout operations here
            window.location.href = "login_student.php";
        }

        function logoutUser() {
        var confirmLogout = confirm("Are you sure you want to logout?");
        if (confirmLogout) {
            // You can redirect the user to the logout page or perform any other logout operations here
            window.location.href = "login_student.php";
        }
    }
    </script>

</body>
</html>




