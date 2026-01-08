<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'organization') {
    die("Please login as an organization");
}

include "db.php";
$org_id = $_SESSION['user_id'];

echo "<h2>Download Debug Information</h2>";
echo "<p>Organization ID: " . $org_id . "</p>";
echo "<hr>";

// Check verification requests
echo "<h3>Your Verification Requests:</h3>";
$requests = mysqli_query($conn, "SELECT * FROM verification_requests WHERE organization_id = $org_id");

if(mysqli_num_rows($requests) > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>NIN</th><th>Status</th><th>Request Date</th><th>Action</th></tr>";
    while($req = mysqli_fetch_assoc($requests)) {
        echo "<tr>";
        echo "<td>" . $req['id'] . "</td>";
        echo "<td>" . $req['nin'] . "</td>";
        echo "<td>" . $req['status'] . "</td>";
        echo "<td>" . $req['request_date'] . "</td>";
        echo "<td>";
        if($req['status'] == 'approved') {
            echo "<a href='download_record.php?nin=" . $req['nin'] . "' target='_blank'>Try Download</a>";
        } else {
            echo "Not approved yet";
        }
        echo "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:orange;'>No verification requests found. You need to:</p>";
    echo "<ol>
            <li>Go to Search Records</li>
            <li>Search for a NIN</li>
            <li>Click 'Request Full Record Access'</li>
            <li>Wait for police approval</li>
          </ol>";
}

echo "<hr>";

// Check crime records
echo "<h3>Crime Records in Database:</h3>";
$records = mysqli_query($conn, "SELECT nin, full_name, crime_type, status FROM crime_records LIMIT 5");

if(mysqli_num_rows($records) > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>NIN</th><th>Name</th><th>Crime Type</th><th>Status</th></tr>";
    while($rec = mysqli_fetch_assoc($records)) {
        echo "<tr>";
        echo "<td>" . $rec['nin'] . "</td>";
        echo "<td>" . $rec['full_name'] . "</td>";
        echo "<td>" . $rec['crime_type'] . "</td>";
        echo "<td>" . $rec['status'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>No crime records in database! Police need to add records first.</p>";
}

echo "<hr>";
echo "<h3>Steps to Download a Record:</h3>";
echo "<ol>
        <li><strong>Police must add a crime record</strong> (with NIN)</li>
        <li><strong>Organization searches</strong> for that NIN</li>
        <li><strong>Organization requests</strong> verification</li>
        <li><strong>Police approves</strong> the request</li>
        <li><strong>Organization can download</strong> the record</li>
      </ol>";

echo "<br><a href='organization_dashboard.php' style='padding:10px 20px; background:#3498db; color:white; text-decoration:none; border-radius:5px;'>Back to Dashboard</a>";
?>