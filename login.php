<?php
session_start();
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $emailOrPhone = sanitize_input($_POST['email']);
    $password = $_POST['password'];

    // Prepare SQL statement to prevent SQL injection
    $sql = "SELECT * FROM users WHERE gmail=? OR phone=?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("ss", $emailOrPhone, $emailOrPhone);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            // Verify password
            if (password_verify($password, $row['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['gmail'] = $row['gmail'];
                
                // Redirect to products page
                header("Location: product.php");
                exit();
            } else {
                echo "<script>alert('Invalid password!'); window.location.href='login.html';</script>";
            }
        } else {
            echo "<script>alert('User not found!'); window.location.href='login.html';</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('Database error. Please try again.'); window.location.href='login.html';</script>";
    }
}
$conn->close();
?>