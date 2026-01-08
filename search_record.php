<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'organization') {
    header("Location: login.html");
    exit();
}

include "db.php";

$record = null;
$error = null;
$success = null;

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['nin'])) {
    $nin = trim($_GET['nin']);
    
    // Search for crime record
    $sql = "SELECT * FROM crime_records WHERE nin = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $nin);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $record = mysqli_fetch_assoc($result);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_verification'])) {
    $nin = trim($_POST['nin']);
    $org_id = $_SESSION['user_id'];
    
    // Check if request already exists
    $check_sql = "SELECT * FROM verification_requests WHERE organization_id = ? AND nin = ? AND status = 'pending'";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "is", $org_id, $nin);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if(mysqli_num_rows($check_result) > 0) {
        $error = "You already have a pending verification request for this NIN.";
    } else {
        // Insert verification request
        $insert_sql = "INSERT INTO verification_requests (organization_id, nin, status) VALUES (?, ?, 'pending')";
        $insert_stmt = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($insert_stmt, "is", $org_id, $nin);
        
        if(mysqli_stmt_execute($insert_stmt)) {
            $success = "Verification request submitted successfully! Awaiting approval.";
        } else {
            $error = "Error submitting request.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Crime Records</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0f172a, #1e3a8a);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .search-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        h2 {
            color: #1f2937;
            margin-bottom: 20px;
        }

        .search-box {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .search-box input {
            flex: 1;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 5px;
            font-size: 14px;
        }

        .search-box button {
            padding: 12px 24px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .alert {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
        }

        .record-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
        }

        .record-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f3f4f6;
        }

        .record-photo {
            width: 120px;
            height: 120px;
            border-radius: 8px;
            object-fit: cover;
            border: 2px solid #e5e7eb;
        }

        .record-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .detail-item {
            padding: 10px;
            background: #f9fafb;
            border-radius: 5px;
        }

        .detail-label {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 5px;
        }

        .detail-value {
            font-size: 14px;
            color: #1f2937;
            font-weight: 500;
        }

        .btn-request {
            width: 100%;
            padding: 12px;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-serving { background: #fee2e2; color: #991b1b; }
        .status-released { background: #d1fae5; color: #065f46; }
        .status-pending { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body>

<div class="container">
    <a href="organization_dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>

    <div class="search-card">
        <h2><i class="fas fa-search"></i> Search Crime Records</h2>
        
        <form method="GET" class="search-box">
            <input type="text" name="nin" placeholder="Enter National ID Number (NIN)" required 
                   value="<?php echo isset($_GET['nin']) ? htmlspecialchars($_GET['nin']) : ''; ?>">
            <button type="submit"><i class="fas fa-search"></i> Search</button>
        </form>

        <?php if($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if(isset($_GET['nin']) && !$record && !$success): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No crime record found for NIN: <?php echo htmlspecialchars($_GET['nin']); ?>
                <br><small>This person has no criminal record in the system.</small>
            </div>
        <?php endif; ?>
    </div>

    <?php if($record): ?>
    <div class="record-card">
        <div class="record-header">
            <div>
                <h2><?php echo htmlspecialchars($record['full_name']); ?></h2>
                <p style="color: #6b7280;">NIN: <?php echo htmlspecialchars($record['nin']); ?></p>
                <span class="status-badge status-<?php echo $record['status']; ?>">
                    <?php echo ucfirst($record['status']); ?>
                </span>
            </div>
            <?php if($record['face_photo']): ?>
                <img src="<?php echo htmlspecialchars($record['face_photo']); ?>" 
                     alt="Photo" class="record-photo">
            <?php else: ?>
                <div class="record-photo" style="background: #e5e7eb; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-user" style="font-size: 40px; color: #9ca3af;"></i>
                </div>
            <?php endif; ?>
        </div>

        <div class="record-details">
            <div class="detail-item">
                <div class="detail-label">Crime Type</div>
                <div class="detail-value"><?php echo htmlspecialchars($record['crime_type']); ?></div>
            </div>

            <div class="detail-item">
                <div class="detail-label">Date of Crime</div>
                <div class="detail-value"><?php echo date('F j, Y', strtotime($record['date_of_crime'])); ?></div>
            </div>

            <div class="detail-item">
                <div class="detail-label">Arrest Date</div>
                <div class="detail-value"><?php echo date('F j, Y', strtotime($record['arrest_date'])); ?></div>
            </div>

            <div class="detail-item">
                <div class="detail-label">Time Served</div>
                <div class="detail-value">
                    <?php echo $record['years_in_prison'].'Y '.$record['months_in_prison'].'M '.$record['days_in_prison'].'D'; ?>
                </div>
            </div>

            <?php if($record['release_date']): ?>
            <div class="detail-item">
                <div class="detail-label">Release Date</div>
                <div class="detail-value"><?php echo date('F j, Y', strtotime($record['release_date'])); ?></div>
            </div>
            <?php endif; ?>

            <div class="detail-item">
                <div class="detail-label">Phone</div>
                <div class="detail-value"><?php echo $record['phone'] ? htmlspecialchars($record['phone']) : 'N/A'; ?></div>
            </div>

            <div class="detail-item" style="grid-column: 1 / -1;">
                <div class="detail-label">Address</div>
                <div class="detail-value"><?php echo $record['address'] ? htmlspecialchars($record['address']) : 'N/A'; ?></div>
            </div>

            <div class="detail-item" style="grid-column: 1 / -1;">
                <div class="detail-label">Crime Description</div>
                <div class="detail-value"><?php echo htmlspecialchars($record['crime_description']); ?></div>
            </div>
        </div>

        <form method="POST">
            <input type="hidden" name="nin" value="<?php echo htmlspecialchars($record['nin']); ?>">
            <button type="submit" name="request_verification" class="btn-request">
                <i class="fas fa-paper-plane"></i> Request Full Record Access
            </button>
        </form>
    </div>
    <?php endif; ?>
</div>

</body>
</html>