<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'organization') {
    header("Location: login.html");
    exit();
}

include "db.php";

if(!isset($_GET['nin'])) {
    die("NIN parameter required");
}

$nin = $_GET['nin'];
$org_id = $_SESSION['user_id'];

// Verify that the organization has an approved request OR check if record exists
$verify_sql = "SELECT * FROM verification_requests WHERE organization_id = ? AND nin = ? AND status = 'approved'";
$verify_stmt = mysqli_prepare($conn, $verify_sql);
mysqli_stmt_bind_param($verify_stmt, "is", $org_id, $nin);
mysqli_stmt_execute($verify_stmt);
$verify_result = mysqli_stmt_get_result($verify_stmt);

// For testing: Allow download if request exists (you can remove this later)
if(mysqli_num_rows($verify_result) == 0) {
    // Check if any request exists for this NIN
    $check_sql = "SELECT * FROM verification_requests WHERE organization_id = ? AND nin = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "is", $org_id, $nin);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if(mysqli_num_rows($check_result) == 0) {
        die("<div style='text-align:center; padding:50px; font-family: Arial;'>
            <h2 style='color:#e74c3c;'>Access Denied</h2>
            <p>You need to request verification for this record first.</p>
            <a href='search_record.php' style='display:inline-block; margin-top:20px; padding:10px 20px; background:#3498db; color:white; text-decoration:none; border-radius:5px;'>Go to Search</a>
            </div>");
    } else {
        $req = mysqli_fetch_assoc($check_result);
        die("<div style='text-align:center; padding:50px; font-family: Arial;'>
            <h2 style='color:#f39c12;'>Request Pending Approval</h2>
            <p>Your verification request is currently <strong>" . ucfirst($req['status']) . "</strong>.</p>
            <p>Please wait for police approval before downloading.</p>
            <a href='org_requests.php' style='display:inline-block; margin-top:20px; padding:10px 20px; background:#3498db; color:white; text-decoration:none; border-radius:5px;'>View My Requests</a>
            </div>");
    }
}

// Get the crime record
$sql = "SELECT * FROM crime_records WHERE nin = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $nin);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$record = mysqli_fetch_assoc($result);

if(!$record) {
    die("Record not found");
}

// Log the download
$log_sql = "INSERT INTO download_logs (user_id, record_id) VALUES (?, ?)";
$log_stmt = mysqli_prepare($conn, $log_sql);
mysqli_stmt_bind_param($log_stmt, "ii", $org_id, $record['id']);
mysqli_stmt_execute($log_stmt);

// Generate PDF or display downloadable page
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crime Record - <?php echo htmlspecialchars($record['full_name']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: #f0f2f5;
            padding: 20px;
        }

        .actions {
            max-width: 800px;
            margin: 0 auto 20px;
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-back {
            background: #6b7280;
            color: white;
        }

        .btn-print {
            background: #3b82f6;
            color: white;
        }

        .document {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .header {
            text-align: center;
            border-bottom: 3px solid #1f2937;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #1f2937;
            font-size: 24px;
            margin-bottom: 5px;
        }

        .header p {
            color: #6b7280;
            font-size: 14px;
        }

        .photo-section {
            text-align: center;
            margin: 20px 0;
        }

        .photo {
            width: 150px;
            height: 150px;
            border-radius: 8px;
            border: 2px solid #e5e7eb;
            object-fit: cover;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin: 20px 0;
        }

        .info-item {
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 10px;
        }

        .info-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 14px;
            color: #1f2937;
            font-weight: 600;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-serving { background: #fee2e2; color: #991b1b; }
        .status-released { background: #d1fae5; color: #065f46; }
        .status-pending { background: #fef3c7; color: #92400e; }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }

        @media print {
            .actions { display: none; }
            body { background: white; padding: 0; }
            .document { box-shadow: none; }
        }
    </style>
</head>
<body>

<div class="actions">
    <a href="organization_dashboard.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> Back</a>
    <button onclick="window.print()" class="btn btn-print"><i class="fas fa-print"></i> Print/Save as PDF</button>
</div>

<div class="document">
    <div class="header">
        <h1>🔒 OFFICIAL CRIME RECORD</h1>
        <p>Integrated Multi-Agency Crime Information System</p>
        <p>Document Generated: <?php echo date('F j, Y g:i A'); ?></p>
    </div>

    <?php if($record['face_photo']): ?>
    <div class="photo-section">
        <img src="<?php echo htmlspecialchars($record['face_photo']); ?>" alt="Photo" class="photo">
    </div>
    <?php endif; ?>

    <div class="info-grid">
        <div class="info-item full-width">
            <div class="info-label">Full Name</div>
            <div class="info-value"><?php echo htmlspecialchars($record['full_name']); ?></div>
        </div>

        <div class="info-item">
            <div class="info-label">National ID (NIN)</div>
            <div class="info-value"><?php echo htmlspecialchars($record['nin']); ?></div>
        </div>

        <div class="info-item">
            <div class="info-label">Status</div>
            <div class="info-value">
                <span class="status-badge status-<?php echo $record['status']; ?>">
                    <?php echo ucfirst($record['status']); ?>
                </span>
            </div>
        </div>

        <div class="info-item">
            <div class="info-label">Crime Type</div>
            <div class="info-value"><?php echo htmlspecialchars($record['crime_type']); ?></div>
        </div>

        <div class="info-item">
            <div class="info-label">Date of Crime</div>
            <div class="info-value"><?php echo date('F j, Y', strtotime($record['date_of_crime'])); ?></div>
        </div>

        <div class="info-item">
            <div class="info-label">Arrest Date</div>
            <div class="info-value"><?php echo date('F j, Y', strtotime($record['arrest_date'])); ?></div>
        </div>

        <?php if($record['release_date']): ?>
        <div class="info-item">
            <div class="info-label">Release Date</div>
            <div class="info-value"><?php echo date('F j, Y', strtotime($record['release_date'])); ?></div>
        </div>
        <?php endif; ?>

        <div class="info-item">
            <div class="info-label">Time in Prison</div>
            <div class="info-value">
                <?php echo $record['years_in_prison'].' Years, '.$record['months_in_prison'].' Months, '.$record['days_in_prison'].' Days'; ?>
            </div>
        </div>

        <div class="info-item">
            <div class="info-label">Phone Number</div>
            <div class="info-value"><?php echo $record['phone'] ? htmlspecialchars($record['phone']) : 'N/A'; ?></div>
        </div>

        <div class="info-item full-width">
            <div class="info-label">Address</div>
            <div class="info-value"><?php echo $record['address'] ? htmlspecialchars($record['address']) : 'N/A'; ?></div>
        </div>

        <div class="info-item full-width">
            <div class="info-label">Crime Description</div>
            <div class="info-value" style="line-height: 1.6;">
                <?php echo nl2br(htmlspecialchars($record['crime_description'])); ?>
            </div>
        </div>

        <div class="info-item">
            <div class="info-label">Record Added</div>
            <div class="info-value"><?php echo date('F j, Y', strtotime($record['added_at'])); ?></div>
        </div>

        <div class="info-item">
            <div class="info-label">Last Updated</div>
            <div class="info-value"><?php echo date('F j, Y', strtotime($record['updated_at'])); ?></div>
        </div>
    </div>

    <div class="footer">
        <p><strong>CONFIDENTIAL DOCUMENT</strong></p>
        <p>This document contains sensitive information and should be handled according to data protection regulations.</p>
        <p>Downloaded by: <?php echo htmlspecialchars($_SESSION['email']); ?></p>
        <p>Organization: <?php 
            $org_sql = "SELECT organization_name FROM users WHERE id = ?";
            $org_stmt = mysqli_prepare($conn, $org_sql);
            mysqli_stmt_bind_param($org_stmt, "i", $org_id);
            mysqli_stmt_execute($org_stmt);
            $org_result = mysqli_stmt_get_result($org_stmt);
            $org_data = mysqli_fetch_assoc($org_result);
            echo htmlspecialchars($org_data['organization_name']);
        ?></p>
    </div>
</div>

</body>
</html>