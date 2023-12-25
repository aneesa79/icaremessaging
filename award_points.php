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
// Include your database connection code here
// Example:
// $conn = new mysqli("localhost", "username", "password", "your_database");

// Check if a POST request was made
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Retrieve the user_id from the client
    $data = json_decode(file_get_contents("php://input"));
    $user_id = $data->user_id;

    // Validate the user_id (e.g., ensure it's an integer)
    if (!is_numeric($user_id)) {
        $response = [
            "success" => false,
            "message" => "Invalid user ID.",
        ];
        http_response_code(400); // Bad Request
        echo json_encode($response);
        exit();
    }

    // Perform the logic to award points (you can customize this part)
    $points = 1; // You can change this value as needed
    $success = awardPointsToUser($user_id, $points);

    if ($success) {
        $response = [
            "success" => true,
            "message" => "Points awarded successfully.",
        ];
        echo json_encode($response);
    } else {
        $response = [
            "success" => false,
            "message" => "Failed to award points. Please try again later.",
        ];
        http_response_code(500); // Internal Server Error
        echo json_encode($response);
    }
} else {
    // Handle non-POST requests here, if needed
    http_response_code(405); // Method Not Allowed
    echo "Method Not Allowed";
}

// Function to award points to a user in the database (customize this based on your database structure)
function awardPointsToUser($user_id, $points)
{
    global $conn;

    // Example query: Update the user's points in the "users" table
    $sql = "UPDATE users SET points = points + ? WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $points, $user_id);

    if ($stmt->execute()) {
        $stmt->close();
        return true;
    } else {
        $stmt->close();
        return false;
    }
}
?>
