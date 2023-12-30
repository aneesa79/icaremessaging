
<?php
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login_student.php");
    exit();
}

$user_id = $_SESSION["user_id"];
// $_SESSION["user_password"] = $password;

$conn = new mysqli("localhost", "root", "", "icaremessagingdb");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user profile information
$userProfileStmt = $conn->prepare("SELECT first_name, last_name, email, user_pic, user_type FROM users WHERE id = ?");
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

$requestStmt = $conn->prepare("SELECT requests.id, requests.user_id, requests.request_message, requests.timestamp, users.first_name, users.last_name, users.user_pic FROM requests LEFT JOIN users ON requests.user_id = users.id ORDER BY requests.timestamp ASC");
$requestStmt->execute();
$requestStmt->bind_result($req_id, $req_user_id, $request_message, $req_timestamp, $req_first_name, $req_last_name, $req_user_pic);

$requests = [];
while ($requestStmt->fetch()) {
    $requests[] = [
        'id' => $req_id,
        'user_id' => $req_user_id,
        'request_message' => $request_message,
        'timestamp' => $req_timestamp,
        'first_name' => $req_first_name,
        'last_name' => $req_last_name,
        'user_pic' => $req_user_pic
    ];
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<!-- <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet"> -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Chat</title>
    <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f0f0f0;
                margin: 0;
                padding: 0;
                display: flex;
                height: 100vh;
                overflow: visible;
            }

        .parent-container {
            display: flex; 
            overflow: visible; 
        }

        .left-container {
            width: 37%;
            /* padding: 5%; */
            background-color: #075e54;
            color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            position: sticky;
            top: 0;
            min-height: 100vh;
            overflow: hidden;
        }

        @media (max-width: 600px) {
            .left-container {
                width: 70%; /* Take up the full width on small screens */
                flex-direction: row; /* Display content in a row */
                width: 37%;
                padding: 20px;
                background-color: #075e54;
                color: #fff;
                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                display: flex;
                align-items: center;
                justify-content: flex-start;
                position: sticky;
                top: 0;
                height: 100vh;
                overflow: hidden;
            }
        }

        .logo {
            width: 24%;
            margin: 10px 0px 0px 0px;
            margin-top: 2%;
        }

        .left-container-account-details {
            display: flex;
            align-items: center;
            margin-right: auto; 
            margin-left: 40px; 
            margin-top:0;
        }

        .left-container-profile-pic {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin: 4%;
        }

        .left-container-details p {
            font-size: 12px; /* Adjust as needed */
            margin: 0;
        }

        .list-box {
            margin-top: 3%;
            position: sticky;
            width: 80%;
            height:70%;
        }

        .requests-list {
            overflow-y: auto;
            max-height: 70%;;
            width: 95%;
            background-color: #D3D3D3; 
            border: 1px solid black; 
            border-radius: 10px; 
            padding: 2%; 
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        #mySearch {
            box-sizing: border-box; 
            background-image: url('/css/searchicon.png');
            background-position: 10px 12px;
            background-repeat: no-repeat;
            width: 100%;
            height: 4%;
            font-size: 16px;
            padding: 10px 20px 6px 40px; 
            border: 1px solid #ddd;
            border-radius: 10px;
            margin-bottom:2%;
        }

        .request-item {
            padding: 10px;
            cursor: pointer;
        }

        .request-item-border-line {
            width: 99%;
            border-bottom: 2px solid #A9A9A9;
            margin-left: auto;
            margin-right: auto;
        }

        .request-item:hover {
            background-color: #ececec;
        }

        .request-item p {
            margin: 5px;
            font-size: 14px;
        }

        .request-item .timestamp {
            display: block;
            font-size: 12px;
            color: #777;
        }

        .request-message {
            background-color: #FFFFFF; /* Light blue background for distinction */
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 8px;
            border-left: 5px solid #075e54; /* A solid left border for emphasis */
        }

        .request-message strong {
            color: #106e6e; /* Darker text for the 'Request' label */
        }

        #add-request-button {
            position:absolute;
            font: 3vw;
            bottom: 8px; 
            width: 12%; 
            height: 10%; 
            border-radius: 100%; 
            border: 1px #777;
        }

        #add-request-button:hover {
            background-color: #ccc; 
        }

        #add-request-button:active {
            background-color: #7ABCB4;
        }

        .chat-container {
            display: flex;
            flex-direction: column;
            flex: 1;
            position:sticky;
            overflow: visible;
        }

        .chat-header {
            background-color: #075e54;
            color: #fff;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
        }

        .user-profile-pic {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .user-details {
            flex: 1;
            text-align: left;
        }

        .user-details h1 {
            margin: 0;
            font-size: 16px;
            font-weight: bold;
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

        .message-history-container {
            overflow-y: auto;
            flex-grow: 1;
            padding: 20px;
        }

        .message {
            max-width: 70%;
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 8px;
        }

        .message-user {
            background-color: #DCF8C6; /* Light green background, similar to WhatsApp */
            margin-left: auto; /* Align to the right */
            border-bottom-right-radius: 0; /* Optional: style adjustment */
        }

        .message-other {
            background-color: #fff; /* White background for others' messages */
            margin-right: auto; /* Align to the left */
            border-bottom-left-radius: 0; /* Optional: style adjustment */
        }

        .message p {
            margin: 0;
            word-wrap: break-word;
        }

        .timestamp {
            display: block;
            font-size: 12px;
            color: #777;
            text-align: right;
        }

        .message strong {
            color: #075e54;
        }

        .message-form-container {
            padding: 10px;
            background-color: #f0f0f0;
            box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.1);
            margin-top: auto;
        }

        .message-form {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        #reply-text {
            width: calc(100% - 50px);
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            resize: none;
        }

        #send-button {
            width: 40px;
            height: 40px;
            border: none;
            background-color: transparent;
            cursor: pointer;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #075e54;
        }

        #send-button:hover {
            color: #128C7E;
        }

        #send-button i {
            font-size: 24px;
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

        .popup {
            display: none; 
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5); 
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000; 
        }

        .popup-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
        }

        .close {
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
        }

        .action-button {
            font-size: 16px;
            cursor: pointer;
            padding: 10px;
            border: none;
            border-radius: 4px;
            margin-top: 10px;
            background-color: #106e6e;
            color: #fff;
            display: block;
            text-decoration: none;
            transition: background-color 0.3s;
            width: 100%; /* Ensure the buttons take up the full width */
            text-align: center; /* Center the button text horizontally */
        }

        .action-button:hover {
            background-color: #0a4a4a;
        }

        #coupon-deals-btn {
            margin-bottom: 10px; 
        }

        #award-points-popup {
            display: none;
        }

        #coupon-deals-popup {
            display: none;
        }

        #request-popup {
            display: none;
        }
    </style>

</head>
<body>
    <div class="parent-container">
        <div class="left-container">
            <!-- Your logo goes here -->
            <img src="iCarelogo3.png" alt="Logo" class="logo">
            <div class="left-container-account-details">
                <img src="<?= $user_pic ?>" alt="User Profile Picture" class="left-container-profile-pic">
                <div class="left-container-details">
                    <p><?= $first_name . " " . $last_name ?></p>
                    <p><?= $email ?></p>
                </div>
            </div>
            <div class="list-box">
                <form autocomplete="off" id="formMySearch" method="post" action="">
                    <input autocomplete="false" type="text" id="mySearch" name="mySearch" onkeyup="searchReqItem()" placeholder="Search..">
                </form>
                <div class="requests-list" style="overflow-y:auto;">
                    <?php foreach ($requests as $request): ?>
                        <div class="request-item" onclick="loadRequestMessages('<?= htmlspecialchars(json_encode($request)) ?>')">
                            <p><?= htmlspecialchars($request['request_message']) ?></p>
                            <span class="timestamp"><?= htmlspecialchars($request['timestamp']) ?></span>
                            <div class="request-item-border-line"></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <button id="add-request-button" class="add-request-button">[+]</button>
        </div>

        <!-- Add this code for the request submission pop-up -->
        <div id="request-popup" class="popup">
            <div class="popup-content">
                <span class="close" onclick="closeRequestPopup()">&times;</span>
                <h2>Submit a Request</h2>
                <form id="request-form" action="submit_request.php" method="POST">
                    <textarea id="request-text" name="request_message" rows="4" placeholder="Type your request here" required></textarea>
                    <button type="submit" class="action-button">Submit Request</button>
                </form>
            </div>
        </div>

        <div class="chat-container">
            <div class="chat-header">
                <img src="<?= $user_pic ?>" alt="Profile Picture" class="user-profile-pic" onclick="toggleProfileContainer()">
                <div class="user-details">
                    <h1><?= $first_name . " " . $last_name ?></h1>
                </div>
                <div id="rules-btn" class="rules-icon" onclick="openRulesPopup()">!</div>
            </div>
            <div class="message-history-container">
                <?php foreach ($messages as $msg): ?>
                    <div class="message <?= ($user_id == $msg['user_id']) ? 'message-user' : 'message-other' ?>">
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
            </div>
            <div class="message-form-container">
                <form method="post" action="send_message_volunteer.php" enctype="multipart/form-data" class="message-form">
                    <input type="hidden" name="request_id" id="request-id-input" value="">        
                    <textarea id="reply-text" name="message" rows="1" required></textarea>
                    <button id="send-button" type="submit">
                        <i class="fa fa-paper-plane" aria-hidden="true"></i>
                    </button>
                </form>
            </div>
        </div>

        <div id="rules-popup">
            <span class="close-button" onclick="closeRulesPopup()">&times;</span>
            <h2>Chat Rules</h2>
            <p>1. Be respectful to others.</p>
            <p>2. No offensive language.</p>
            <p>3. Follow community guidelines.</p>
        </div>

        <div id="award-points-popup" class="popup">
            <div class="popup-content">
                <span class="close" onclick="closeAwardPointsPopup()">&times;</span>
                <h2>Award Points</h2>
                <p>Are you sure you want to grant a point to the selected Mac Volunteer?</p>
                <p>(Note: Each point to be awarded should be indicated only after the assistance request task has been fully and securely completed.)</p>
                        
                <!-- Password input field -->
                <label for="password">Enter Your Password:</label>
                <input type="password" id="password" required>
                        
                <!-- Buttons to proceed or go back -->
                <button onclick="cancelAwardPoints()">Cancel</button>
                <button onclick="confirmAwardPoints()">Proceed</button>
            </div>
        </div>

        <div id="profile-container" class="profile-container">
            <img src="<?= $user_pic ?>" alt="Profile Picture" class="profile-pic">
                <div class="profile-details">
                    <h2><?= $first_name . " " . $last_name ?></h2>
                    <p><?= $email ?></p>
                    <p>User Type: <?= $user_type ?></p>
                </div>

                <button id="coupon-deals-btn">Show Coupon Deals</button>
                <button id="logout-btn" onclick="logoutUser()">Logout</button>
        </div>

        <div id="coupon-deals-popup" class="popup">
            <div class="popup-content">
                <span class="close" onclick="hideCouponDealsPopup()">&times;</span>
                <h2>Coupon Deals</h2>
                <!-- Content for your coupon deals goes here -->
            </div>
        </div>
    </div>



    <script>
        var loggedInUserId = <?= json_encode($user_id); ?>;

            function searchReqItem() {
                var input, filter, ul, li, a, i;
                input = document.getElementById("mySearch");
                filter = input.value.toUpperCase();
                ul = document.getElementById("requests-list");
                li = ul.getElementsByClassName("request-item");

                for (i = 0; i < li.length; i++) {
                    a = li[i].getElementsByTagName("p")[0];
                    if (a.innerHTML.toUpperCase().indexOf(filter) > -1) {
                        li[i].style.display = "";
                    } else {
                        li[i].style.display = "none";
                    }
                }
            }

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

            document.getElementById('coupon-deals-btn').addEventListener('click', function() {
                document.getElementById('coupon-deals-popup').style.display = 'block';
            });

            function hideCouponDealsPopup() {
                var popup = document.getElementById('coupon-deals-popup');
                popup.style.display = 'none';
            }

            // Optional: Close the popup when clicking outside of it
            window.onclick = function(event) {
                var popup = document.getElementById('coupon-deals-popup');
                if (event.target == popup) {
                    popup.style.display = 'none';
                }
            }

            function openRulesPopup() {
                var rulesPopup = document.getElementById("rules-popup");
                rulesPopup.style.display = "block";
            }

            function closeRulesPopup() {
                var rulesPopup = document.getElementById("rules-popup");
                rulesPopup.style.display = "none";
            }

            function loadRequestMessages(requestData) {
                var request = JSON.parse(requestData);
                document.getElementById('request-id-input').value = request.id;

                // Update chat header with request owner's details
                var chatHeader = document.querySelector('.chat-header');
                chatHeader.innerHTML = `
                    <img src="${request.user_pic}" alt="Profile Picture" class="user-profile-pic">
                    <div class="user-details">
                        <h1>${request.first_name} ${request.last_name}</h1>
                    </div>
                `;

                // Clear previous messages and add the request message at the top
                var messageHistoryContainer = document.querySelector('.message-history-container');
                messageHistoryContainer.innerHTML = `
                    <div class="message request-message">
                        <p>
                            <span class="timestamp">${request.timestamp}</span><br>
                            <strong>Request:</strong> ${request.request_message}
                        </p>
                    </div>
                `;

                // Load messages related to the request
                fetch('get_request_messages.php?request_id=' + request.id)
                    .then(response => response.json())
                    .then(messages => {
                        messages.forEach(msg => {
                            var messageDiv = document.createElement('div');
                            messageDiv.className = 'message ' + (msg.user_id == loggedInUserId ? 'message-user' : 'message-other');
                            messageDiv.innerHTML = `
                                <p>
                                    <span class="timestamp">${msg.timestamp}</span><br>
                                    <strong>${msg.sender_name}:</strong> ${msg.message}
                                </p>
                            `;
                            messageHistoryContainer.appendChild(messageDiv);
                        });
                    }
                );
            }

            function updateAndShowProfileContainer(firstName, lastName, pic, email, userType) {
                var profileContainer = document.getElementById("profile-container");
                profileContainer.innerHTML = `
                    <img src="${pic}" alt="Profile Picture" class="profile-pic">
                    <div class="profile-details">
                        <h2>${firstName} ${lastName}</h2>
                        <p>${email}</p>
                        <p>User Type: ${userType}</p>
                    </div>
                    <button id="logout-btn" onclick="logoutUser()">Logout</button>
                `;
                profileContainer.style.display = "block";
            }

            function openRulesPopup(requestId) {
                var rulesPopup = document.getElementById("rules-popup");
                rulesPopup.style.display = "block";

                // Dynamically set the rules button's ID in the popup so we can track which request it belongs to
                var rulesButton = document.getElementById(`rules-btn-${requestId}`);
                rulesPopup.setAttribute('data-request-id', requestId);
            }

            function closeRulesPopup() {
                var rulesPopup = document.getElementById("rules-popup");
                rulesPopup.style.display = "none";
            }

            // Add an event listener to handle clicking on the rules button in the popup
            document.getElementById("rules-popup").addEventListener("click", function(event) {
                if (event.target.id === "rules-popup") {
                    // Close the rules popup when clicking outside of it
                    closeRulesPopup();
                }
            });

            var selectedUserId; // To store the user ID of the volunteer selected for points

            document.getElementById('award-points-icon').addEventListener('click', function() {
                document.getElementById('award-points-popup').style.display = 'block';
            });

            function closeAwardPointsPopup() {
                var awardPointsPopup = document.getElementById("award-points-popup");
                awardPointsPopup.style.display = "none";
            }

            function cancelAwardPoints() {
                closeAwardPointsPopup();
                // Clear the password input field or perform other actions if needed
                document.getElementById("password").value = "";
            }

            function confirmAwardPoints() {
                var enteredPassword = document.getElementById("password").value;

                // Retrieve the stored password from the session (assuming it's named "user_password")
                var storedPassword = <?php echo json_encode($_SESSION["user_password"]); ?>;

                if (enteredPassword === storedPassword) {
                    // Passwords match, proceed with awarding points
                    // You can implement the logic to award points and submit to the database here
                    awardPoints(selectedUserId);

                    // Close the pop-up
                    closeAwardPointsPopup();
                } else {
                    // Passwords don't match, display an error message
                    alert("Incorrect password. Please try again.");
                    // Clear the password input field or display an error message
                }
            }

            // Function to award points to a volunteer (similar to your existing awardPoints function)
            function awardPoints(userId) {
                // Implement the logic to award points and submit to the database here
                // You can use AJAX to send the award points request to the server
                // Example:
                fetch('award_points.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        user_id: userId,
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Points awarded successfully
                        alert("Points awarded successfully!");
                    } else {
                        // Points awarding failed
                        alert("Failed to award points. Please try again.");
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("An error occurred while awarding points. Please try again later.");
                });
            }

            document.getElementById('add-request-button').addEventListener('click', function() {
                document.getElementById('request-popup').style.display = 'block';
            });

            function closeRequestPopup() {
                var requestPopup = document.getElementById("request-popup");
                requestPopup.style.display = "none";
            }

            function toggleProfileContainer() {
                var profileContainer = document.getElementById("profile-container");
                if (profileContainer.style.display === "block") {
                    profileContainer.style.display = "none";
                } else {
                    profileContainer.style.display = "block";
                }
            }
    </script>
</body>
</html>



