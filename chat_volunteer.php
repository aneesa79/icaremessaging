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
    <title>Volunteer Chat</title>

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
            background-color: #f2f2f2;
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

        .message .actions {
            margin-top: 8px;
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

        .report-icon {
            color: #e74c3c;
        }

        .delete-icon {
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

        .popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent black background */
            justify-content: center;
            align-items: center;
        }

        .popup-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            max-width: 400px;
            width: 80%;
            text-align: center;
        }

        .close {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 20px;
            cursor: pointer;
            color: #333;
        }

        /* Style for the coupon deals */
        .coupon-deal {
            margin-bottom: 20px;
        }

        .coupon-deal img {
            max-width: 100%;
            border-radius: 8px;
        }

        #logout-btn {
        font-size: 16px;
        cursor: pointer;
        padding: 10px;
        border: 1px solid #ddd; /* Add a border for a button-like appearance */
        border-radius: 4px;
        margin-top: auto; /* Push the button to the bottom */
        margin-bottom: 10px; /* Add margin for spacing */
        margin-right: 10px; /* Add margin for spacing */
        display: flex;
        align-items: center;
        text-decoration: none; /* Remove underline */
        color: #333; /* Set text color */
    }
    </style>

</head>
<body>
    <div class="chat-container">
        <div class="profile-container">
            <!-- User Profile Information -->
            <img src="<?= $user_pic ?>" alt="Profile Picture" class="profile-pic">
            <div class="profile-details">
                <p><strong>Name:</strong> <?= $first_name . " " . $last_name ?></p>
                <p><strong>Email:</strong> <?= $email ?></p>
                <p><strong>Type:</strong> <?= $user_type ?></p>
                <button id="coupon-deals-btn" onclick="showCouponDealsPopup()">Coupon Deals</button>
                <button id="logout-btn" onclick="logoutUser()">Logout</button>
            </div>
        </div>

        <div class="chat-content">
            <h2>Welcome to the Volunteer Chat</h2>
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

            <form method="post" action="send_message_volunteer.php" enctype="multipart/form-data">
                <textarea id="reply-text" name="message" rows="4" required></textarea>
                <input id="send-button" type="submit" value="Send">
            </form>
        </div>
    </div>

    <div id="coupon-deals-popup" class="popup">
        <div class="popup-content">
            <span class="close" onclick="hideCouponDealsPopup()">&times;</span>
            <h2>Coupon Deals</h2>
            
            <!-- Example Coupon Deal 1 -->
            <div class="coupon-deal">
                <img src="nasi ayam.jpeg" alt="Nasi Ayam">
                <p>Redeem 10 points for a delicious plate of Nasi Ayam!</p>
            </div>

            <!-- Example Coupon Deal 2 -->
            <div class="coupon-deal">
                <img src="path/to/another_coupon_image.jpg" alt="Another Coupon">
                <p>Redeem 15 points for another fantastic deal!</p>
            </div>

    <script>
        function reportMessage(messageId) {
            var confirmReport = confirm("Do you want to report this message?");
            if (confirmReport) {
                window.location.href = "report_message_volunteer.php?id=" + messageId;
            }
        }

        function confirmDelete(messageId) {
            var confirmDelete = confirm("Are you sure you want to delete this message?");
            if (confirmDelete) {
                window.location.href = "delete_message_volunteer.php?id=" + messageId;
            }
        }

        function showCouponDealsPopup() {
            var popup = document.getElementById('coupon-deals-popup');
            popup.style.display = 'flex';
        }

        // Function to hide the Coupon Deals pop-up
        function hideCouponDealsPopup() {
            var popup = document.getElementById('coupon-deals-popup');
            popup.style.display = 'none';
        }

        // Close the pop-up if the user clicks outside of it
        window.onclick = function(event) {
            var popup = document.getElementById('coupon-deals-popup');
            if (event.target == popup) {
                popup.style.display = 'none';
            }
        };

        function logoutUser() {
            // You can redirect the user to the logout page or perform any other logout operations here
            window.location.href = "login_volunteer.php";
        }

        function logoutUser() {
        var confirmLogout = confirm("Are you sure you want to logout?");
        if (confirmLogout) {
            // You can redirect the user to the logout page or perform any other logout operations here
            window.location.href = "login_volunteer.php";
        }
    }
    </script>
</body>
</html>


