<?php
session_start();
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    $password = $_POST['password'];

    // Validate inputs
    if (empty($email) || empty($role) || empty($password)) {
        header("Location: login.html?error=1");
        exit();
    }

    // Validate role
    if (!in_array($role, ['police', 'organization'])) {
        header("Location: login.html?error=1");
        exit();
    }

    // Query user from database
    $sql = "SELECT * FROM users WHERE email=? AND role=?";
    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        die("Database error: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($stmt, "ss", $email, $role);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    // Debug: Check if user was found
    if (!$user) {
        // User not found with this email and role combination
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        header("Location: login.html?error=1");
        exit();
    }

    // Verify password
    if (password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];
        
        if ($role == "police") {
            $_SESSION['police_id'] = $user['police_id'];
            $_SESSION['station_name'] = $user['station_name'];
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            header("Location: police_dashboard.php");
            exit();
        } else {
            $_SESSION['organization_name'] = $user['organization_name'];
            mysqli_stmt_close($stmt);
            mysqli_close($conn);
            header("Location: organization_dashboard.php");
            exit();
        }
    } else {
        // Invalid password
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        header("Location: login.html?error=1");
        exit();
    }

    mysqli_stmt_close($stmt);
}

mysqli_close($conn);
?>