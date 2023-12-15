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
            background-color: #ededed;
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }

        .left-container {
            width: 250px;
            padding: 20px;
            background-color: #075e54;
            color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            position: sticky;
            top: 0;
            height: 100vh;
        }

        .logo {
            width: 150px;
            height: auto;
            margin-bottom: 20px;
        }

        .chat-container {
            display: flex;
            flex: 1;
            overflow: hidden;
        }

        .chat-content {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            height: 100vh;
        }

        .chat-header {
    background-color: #075e54;
    color: #fff;
    padding: 10px;
    width: 100%;
    text-align: center;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    margin: 0; /* Remove default margin */
    display: flex;
    align-items: center;
    position: sticky;
    top: 0;
    z-index: 100; /* Ensure the header is on top of other elements */
}

        .user-profile-pic {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .user-details h1 {
            margin: 0;
            font-size: 16px;
            font-weight: bold;
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
            color: #128C7E;
        }

        .message .timestamp {
            color: #777;
            font-size: 12px;
        }

        #reply-text {
            width: calc(80% - 20px);
            box-sizing: border-box;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            display: inline-block;
            vertical-align: top;
        }

        #reply-text:focus {
            outline: none;
            border-color: #075e54;
        }

        #send-button {
            background-color: #075e54;
            color: #fff;
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            display: inline-block;
            vertical-align: top;
        }

        #send-button:hover {
            background-color: #128C7E;
        }

        .profile-container {
            width: 250px;
            padding: 20px;
            background-color: #128C7E;
            color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            display: none;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            position: sticky;
            top: 0;
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
            margin-top: auto;
            background-color: #106e6e;
            color: #fff;
            display: block;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        #logout-btn:hover {
            background-color: #0a4a4a;
        }

        .add-request-button {
    font-size: 18px;
    cursor: pointer;
    padding: 10px;
    border: none;
    border-radius: 4px;
    margin-top: 10px; /* Adjust the margin-top value as needed */
    background-color: #106e6e;
    color: #fff;
    display: block;
    text-align: center;
    text-decoration: none;
    transition: background-color 0.3s;
}

.add-request-button:hover {
    background-color: #0a4a4a;
}

        #request-popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .close-button {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
            color: #777;
        }

        .rules-icon {
            font-size: 24px;
            cursor: pointer;
            margin-left: auto;
        }

        #rules-popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }
    </style>
</head>
<body>
    <div class="left-container">
        <!-- Your logo goes here -->
        <img src="iCarelogo3.png" alt="Logo" class="logo">
        <!-- Add this button at the bottom of the left container -->
        <div class="add-request-button" onclick="openRequestPopup()">[+]</div>
    </div>
    <div class="chat-content">
        <div class="chat-header">
            <img src="<?= $user_pic ?>" alt="Profile Picture" class="user-profile-pic" onclick="toggleProfileContainer()">
            <div class="user-details">
                <h1><?= $first_name . " " . $last_name ?></h1>
            </div>
            <div id="rules-btn" class="rules-icon" onclick="openRulesPopup()">!</div>
        </div>

    <?php foreach ($messages as $msg): ?>
        <div class="message">
            <img src="<?= $senderProfilePic ?>" alt="Sender's Profile" class="sender-profile-pic">
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

    <div id="request-popup">
    <span class="close-button" onclick="closeRequestPopup()">&times;</span>
    <h2>Make a Request</h2>
    <form method="post" action="send_request_student.php">
        <!-- Add your request form fields here -->
        <textarea name="request_message" rows="4" required></textarea>
        <br>
        <input type="submit" value="Submit Request">
    </form>
</div>

<div id="rules-popup">
    <span class="close-button" onclick="closeRulesPopup()">&times;</span>
    <h2>Chat Rules</h2>
    <p>1. Be respectful to others.</p>
    <p>2. No offensive language.</p>
    <p>3. Follow community guidelines.</p>
    <!-- Add more rules as needed -->
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
   
    function openRequestPopup() {
        // Display a popup/modal
        var requestPopup = document.getElementById("request-popup");
        requestPopup.style.display = "block";
    }

    function closeRequestPopup() {
        // Close the popup/modal
        var requestPopup = document.getElementById("request-popup");
        requestPopup.style.display = "none";
    }

    function openRulesPopup() {
        var rulesPopup = document.getElementById("rules-popup");
        rulesPopup.style.display = "block";
    }

    function closeRulesPopup() {
        var rulesPopup = document.getElementById("rules-popup");
        rulesPopup.style.display = "none";
    }

    function toggleProfileContainer() {
                var profileContainer = document.querySelector('.profile-container');
                // Toggle the visibility of the profile container
                profileContainer.style.display = (profileContainer.style.display === 'none' || profileContainer.style.display === '') ? 'flex' : 'none';
            }
    </script>

</body>
</html>



