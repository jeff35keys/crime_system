<?php
// Test database connection and check registered users
include "db.php";

echo "<h2>Database Connection Test</h2>";

if ($conn) {
    echo "✅ Database connected successfully!<br><br>";
    
    // Check if users table exists
    $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
    if (mysqli_num_rows($check_table) > 0) {
        echo "✅ Users table exists<br><br>";
        
        // List all registered users
        echo "<h3>Registered Users:</h3>";
        $result = mysqli_query($conn, "SELECT id, role, police_id, station_name, organization_name, email, created_at FROM users");
        
        if (mysqli_num_rows($result) > 0) {
            echo "<table border='1' cellpadding='10'>";
            echo "<tr><th>ID</th><th>Role</th><th>Police ID</th><th>Station</th><th>Organization</th><th>Email</th><th>Created</th></tr>";
            
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr>";
                echo "<td>" . $row['id'] . "</td>";
                echo "<td>" . $row['role'] . "</td>";
                echo "<td>" . ($row['police_id'] ?? 'N/A') . "</td>";
                echo "<td>" . ($row['station_name'] ?? 'N/A') . "</td>";
                echo "<td>" . ($row['organization_name'] ?? 'N/A') . "</td>";
                echo "<td>" . $row['email'] . "</td>";
                echo "<td>" . $row['created_at'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "⚠️ No users registered yet. Please register first.";
        }
        
    } else {
        echo "❌ Users table does not exist! Please run the database schema SQL.";
    }
    
} else {
    echo "❌ Database connection failed: " . mysqli_connect_error();
}

echo "<br><br><a href='register.html'>Go to Register</a> | <a href='login.html'>Go to Login</a>";
?>