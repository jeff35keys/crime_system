<?php
session_start();
include "db.php";

// Check if user is logged in as police
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'police') {
    die("This script can only be run by police officers. Please login as police first.");
}

$police_id = $_SESSION['user_id'];

echo "<h2>Setup Demo Crime Records</h2>";
echo "<p>This will create sample crime records for testing.</p>";

// Sample crime records
$demo_records = [
    [
        'nin' => '12345678901',
        'full_name' => 'John Doe',
        'crime_type' => 'Theft',
        'crime_description' => 'Stole a laptop from the office premises',
        'date_of_crime' => '2024-01-15',
        'arrest_date' => '2024-01-20',
        'release_date' => NULL,
        'years' => 0,
        'months' => 6,
        'days' => 0,
        'status' => 'serving',
        'address' => '123 Main Street, Lagos',
        'phone' => '+234 800 123 4567'
    ],
    [
        'nin' => '98765432109',
        'full_name' => 'Jane Smith',
        'crime_type' => 'Fraud',
        'crime_description' => 'Credit card fraud involving multiple victims',
        'date_of_crime' => '2023-06-10',
        'arrest_date' => '2023-06-15',
        'release_date' => '2024-12-15',
        'years' => 1,
        'months' => 6,
        'days' => 0,
        'status' => 'released',
        'address' => '456 Oak Avenue, Abuja',
        'phone' => '+234 800 987 6543'
    ],
    [
        'nin' => '55566677788',
        'full_name' => 'Mike Johnson',
        'crime_type' => 'Assault',
        'crime_description' => 'Physical assault causing bodily harm',
        'date_of_crime' => '2025-01-01',
        'arrest_date' => '2025-01-02',
        'release_date' => NULL,
        'years' => 0,
        'months' => 0,
        'days' => 5,
        'status' => 'pending',
        'address' => '789 Pine Road, Port Harcourt',
        'phone' => '+234 800 555 6677'
    ]
];

$success_count = 0;
$error_count = 0;

foreach($demo_records as $record) {
    // Check if NIN already exists
    $check_sql = "SELECT id FROM crime_records WHERE nin = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "s", $record['nin']);
    mysqli_stmt_execute($check_stmt);
    mysqli_stmt_store_result($check_stmt);
    
    if(mysqli_stmt_num_rows($check_stmt) > 0) {
        echo "<p style='color:orange;'>⚠️ Record with NIN {$record['nin']} already exists - skipped</p>";
        $error_count++;
        continue;
    }
    
    // Insert record
    $sql = "INSERT INTO crime_records (nin, full_name, crime_type, crime_description, date_of_crime, 
            arrest_date, release_date, years_in_prison, months_in_prison, days_in_prison, status, 
            address, phone, added_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssssssiissssi", 
        $record['nin'], 
        $record['full_name'], 
        $record['crime_type'], 
        $record['crime_description'],
        $record['date_of_crime'], 
        $record['arrest_date'], 
        $record['release_date'], 
        $record['years'], 
        $record['months'], 
        $record['days'], 
        $record['status'], 
        $record['address'], 
        $record['phone'], 
        $police_id
    );
    
    if(mysqli_stmt_execute($stmt)) {
        echo "<p style='color:green;'>✅ Created record for {$record['full_name']} (NIN: {$record['nin']})</p>";
        $success_count++;
    } else {
        echo "<p style='color:red;'>❌ Failed to create record for {$record['full_name']}: " . mysqli_error($conn) . "</p>";
        $error_count++;
    }
}

echo "<hr>";
echo "<h3>Summary:</h3>";
echo "<p>✅ Successfully created: <strong>$success_count</strong> records</p>";
echo "<p>❌ Errors/Skipped: <strong>$error_count</strong> records</p>";

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "<ol>
        <li>Login as an Organization</li>
        <li>Search for one of these NIINs: <strong>12345678901</strong>, <strong>98765432109</strong>, or <strong>55566677788</strong></li>
        <li>Request verification</li>
        <li>Come back and login as Police</li>
        <li>Go to Verification page and approve the request</li>
        <li>Login as Organization again</li>
        <li>Go to My Requests and click Download</li>
      </ol>";

echo "<br><a href='police_dashboard.php' style='display:inline-block; padding:10px 20px; background:#3498db; color:white; text-decoration:none; border-radius:5px;'>Back to Dashboard</a>";
echo " ";
echo "<a href='police_crime_records.php' style='display:inline-block; padding:10px 20px; background:#2ecc71; color:white; text-decoration:none; border-radius:5px;'>View Crime Records</a>";

mysqli_close($conn);
?>