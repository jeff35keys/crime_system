<?php
include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Sanitize and validate inputs
    $role = trim($_POST["role"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    // Validate role
    if (!in_array($role, ['police', 'organization'])) {
        die("Invalid role selected");
    }

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format");
    }

    // Validate password length
    if (strlen($password) < 6) {
        die("Password must be at least 6 characters");
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Handle role-specific fields
    $police_id = null;
    $organization_name = null;

    if ($role == "police") {
        $police_id = trim($_POST["police_id"] ?? "");
        if (empty($police_id)) {
            die("Police ID is required for police officers");
        }
    } elseif ($role == "organization") {
        $organization_name = trim($_POST["organization_name"] ?? "");
        if (empty($organization_name)) {
            die("Organization name is required");
        }
    }

    // Check if email already exists
    $check_sql = "SELECT id FROM users WHERE email = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "s", $email);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);

    if (mysqli_stmt_num_rows($check_stmt) > 0) {
        mysqli_stmt_close($check_stmt);
        die("Error: Email already exists. Please use a different email or <a href='login.html'>login</a>");
    }
    mysqli_stmt_close($check_stmt);

    // Insert new user
    $sql = "INSERT INTO users (role, police_id, organization_name, email, password)
            VALUES (?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conn, $sql);
    
    if (!$stmt) {
        die("Error preparing statement: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param(
        $stmt,
        "sssss",
        $role,
        $police_id,
        $organization_name,
        $email,
        $hashed_password
    );

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        // Redirect to login page with success message
        header("Location: login.html?registered=1");
        exit();
    } else {
        echo "Error: " . mysqli_stmt_error($stmt);
    }

    mysqli_stmt_close($stmt);
}

mysqli_close($conn);
?>