<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $first_name = $_POST["first_name"];
    $last_name = $_POST["last_name"];
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    $user_type = $_POST["user_type"];

    // Check if passwords match
    if ($password !== $confirm_password) {
        echo "Passwords do not match.";
        exit();
    }

    // Handle profile picture upload
    $user_pic = $_FILES["user_pic"];
    $user_pic_name = $user_pic["name"];
    $user_pic_tmp = $user_pic["tmp_name"];

    // Move the uploaded file to a desired directory (you should specify your own directory)
    $upload_directory = "uploads/";
    $user_pic_path = $upload_directory . $user_pic_name;

    move_uploaded_file($user_pic_tmp, $user_pic_path);

    // Database connection
    $conn = new mysqli("localhost", "root", "", "icaremessagingdb");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Check if the email is already registered
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<script>alert('Email is already registered. Please choose a different email.'); window.location='signup.php';</script>";
        exit();
    }

    $stmt->close();

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Insert the new user with profile picture path and user_type
    $stmt = $conn->prepare("INSERT INTO users (email, first_name, last_name, password, user_pic, user_type) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $email, $first_name, $last_name, $hashed_password, $user_pic_path, $user_type);
    $stmt->execute();

    $stmt->close();
    $conn->close();

    // Redirect based on user_type
    if ($user_type === 'volunteer') {
        header("Location: login_volunteer.php");
    } else {
        header("Location: login_student.php");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"> <!-- Include Font Awesome CSS -->
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100%;
            font-size: 1em; 
        }

        form {
            background-color: #fff;
            padding: 4%;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            max-width: 40%;
            width: 100%;
            text-align: center;
        }

        h2 {
            color: #333;    
            font-size: 1.5em; 
        }

        input {
            width: 100%;
            padding: 3%;
            margin: 2% 0;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 0.9em;
        }

        input[type="submit"] {
            background-color: #4caf50;
            color: white;
            cursor: pointer;
            font-size: 1em;
        }

        input[type="submit"]:hover {
            background-color: #45a049;
        }

        a {
            text-decoration: none;
            color: #007bff;
            font-size: 1em;
        }

        p {
            margin: 10px 0;
            color: #555;
            font-size: 1em; 
        }

        label {
            font-weight: bold;
            text-align: left;
            display: block;
            margin-top: 2%;
            font-size: 1em; 
        }

        .password-container {
            position: relative;
        }

        .show-password-icon {
            position: absolute;
            top: 50%;
            right: 2%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #777;
        }

        .login-link {
            margin-top: 2%;
            font-size: 0.875em;         }
    </style>
</head>
<body>
    <form method="post" action="" enctype="multipart/form-data">
        <h2>Sign Up</h2>
        <label for="email">Email:</label>
        <input type="email" name="email" required><br>
        <label for="first_name">First Name:</label>
        <input type="text" name="first_name" required><br>
        <label for="last_name">Last Name:</label>
        <input type="text" name="last_name" required><br>
        <label for="password">Password:</label>
        <div class="password-container">
            <input type="password" name="password" id="password" required>
            <i class="far fa-eye show-password-icon" onclick="showPassword()"></i>
        </div>
        <label for="confirm_password">Confirm Password:</label>
        <input type="password" name="confirm_password" required><br>
        <label for="user_pic">Profile Picture:</label>
        <input type="file" name="user_pic" accept="image/*"><br>
        <label for="user_type">User Type:</label>
        <select name="user_type" required>
            <option value="volunteer">Volunteer</option>
            <option value="student">Student</option>
        </select><br>
        <input type="submit" value="Sign Up">
        <p class="login-link">Already have an account? <a href="login_volunteer.php">Login</a></p>
    </form>

    <script>
        function showPassword() {
            var passwordField = document.getElementById("password");
            if (passwordField.type === "password") {
                passwordField.type = "text";
            } else {
                passwordField.type = "password";
            }
        }
    </script>
</body>
</html>
