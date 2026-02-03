<?php
include 'db.php';

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate inputs
    $phone = sanitize_input($_POST['phone']);
    $gmail = sanitize_input($_POST['gmail']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];

    // Validate phone number
    if (!preg_match("/^[0-9]{10,15}$/", $phone)) {
        $errors[] = "Invalid phone number format. Please enter 10-15 digits.";
    }

    // Validate email
    if (!filter_var($gmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    // Validate password strength
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }

    // Check if passwords match
    if ($password !== $confirm) {
        $errors[] = "Passwords do not match!";
    }

    // If no validation errors, proceed with database operations
    if (empty($errors)) {
        // Check if user already exists
        $check_sql = "SELECT * FROM users WHERE gmail = ? OR phone = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $gmail, $phone);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo "<script>alert('User with this email or phone already exists!'); window.location.href='signup.html';</script>";
            exit();
        }
        $check_stmt->close();

        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user
        $sql = "INSERT INTO users (phone, gmail, password) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("sss", $phone, $gmail, $hashed_password);

            if ($stmt->execute()) {
                echo "<script>alert('Account created successfully!'); window.location.href='login.html';</script>";
                exit();
            } else {
                echo "<script>alert('Error: " . $stmt->error . "'); window.location.href='signup.html';</script>";
            }
            $stmt->close();
        } else {
            echo "<script>alert('Database error. Please try again.'); window.location.href='signup.html';</script>";
        }
    } else {
        // Display errors
        $error_message = implode("\\n", $errors);
        echo "<script>alert('$error_message'); window.location.href='signup.html';</script>";
    }
}
$conn->close();
?>