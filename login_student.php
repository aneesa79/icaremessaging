<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $password = $_POST["password"];

    // Database connection
    $conn = new mysqli("localhost", "root", "", "icaremessagingdb");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Check if the email and password match a volunteer user
    $stmt = $conn->prepare("SELECT id, email, password FROM users WHERE email = ? AND user_type = 'student'");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $user_email, $hashed_password);
        $stmt->fetch();

        // Verify the password
        if (password_verify($password, $hashed_password)) {
            // Password is correct, perform login actions (e.g., set session, redirect)
            session_start();
            $_SESSION["user_id"] = $user_id;
            $_SESSION["user_email"] = $user_email;
            header("Location: chat_student.php"); // Redirect to the volunteer chat page
            exit();
        } else {
            echo "<script>alert('Incorrect password. Please try again.');</script>";
        }
    } else {
        echo "<script>alert('No student found with the provided email.');</script>";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Student</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        form {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
            text-align: center;
        }

        h2 {
            color: #333;
        }

        input {
            width: 100%;
            padding: 10px;
            margin: 8px 0;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        input[type="submit"] {
            background-color: #4caf50;
            color: white;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #45a049;
        }

        a {
            text-decoration: none;
            color: #007bff;
        }

        p {
            margin: 10px 0;
            color: #555;
        }

        label {
            font-weight: bold;
            text-align: left;
            display: block;
            margin-top: 10px;
        }

        .login-link {
            margin-top: 10px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <form method="post" action="">
        <h2>Login Student</h2>
        <label for="email">Email:</label>
        <input type="email" name="email" required><br>
        <label for="password">Password:</label>
        <input type="password" name="password" required><br>
        <input type="submit" value="Login">
        <p class="login-link">Not a student? <a href="login_volunteer.php">Login as a Volunteer</a></p>
    </form>
</body>
</html>
