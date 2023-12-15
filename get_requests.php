<?php
// Include database connection code here (similar to your existing code)

// Fetch all requests
$stmt = $conn->prepare("SELECT id, request_message, timestamp FROM requests WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($request_id, $request_message, $request_timestamp);

while ($stmt->fetch()) {
    echo '<div>';
    echo '<p>' . $request_message . '</p>';
    echo '<span class="timestamp">' . $request_timestamp . '</span>';
    echo '</div>';
}

$stmt->close();
$conn->close();
?>
