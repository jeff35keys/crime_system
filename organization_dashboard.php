<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'organization') {
    header("Location: login.html");
    exit();
}

include "db.php";

$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

// Get statistics
$total_requests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM verification_requests WHERE organization_id=$user_id"))['count'];
$approved_requests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM verification_requests WHERE organization_id=$user_id AND status='approved'"))['count'];
$pending_requests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM verification_requests WHERE organization_id=$user_id AND status='pending'"))['count'];
$downloads = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM download_logs WHERE user_id=$user_id"))['count'];

// Get recent verification requests
$requests_sql = "SELECT vr.*, cr.full_name, cr.crime_type, cr.status as record_status 
                 FROM verification_requests vr 
                 LEFT JOIN crime_records cr ON vr.nin = cr.nin 
                 WHERE vr.organization_id = $user_id 
                 ORDER BY vr.request_date DESC LIMIT 10";
$requests_result = mysqli_query($conn, $requests_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, sans-serif;
        }

        body {
            background: #f0f2f5;
            display: flex;
        }

        .sidebar {
            width: 250px;
            background: #1a365d;
            min-height: 100vh;
            color: white;
            position: fixed;
            left: 0;
            top: 0;
        }

        .sidebar-header {
            padding: 20px;
            background: #0f1c2e;
            text-align: center;
        }

        .sidebar-header h3 {
            font-size: 18px;
            margin-top: 10px;
        }

        .nav-menu {
            list-style: none;
            padding: 20px 0;
        }

        .nav-item {
            padding: 15px 25px;
            cursor: pointer;
            transition: 0.3s;
            border-left: 3px solid transparent;
        }

        .nav-item:hover, .nav-item.active {
            background: #2d4a73;
            border-left-color: #60a5fa;
        }

        .nav-item i {
            margin-right: 10px;
            width: 20px;
        }

        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 20px;
        }

        .top-bar {
            background: white;
            padding: 15px 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .logout-btn {
            padding: 10px 20px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }

        .stat-card.blue { border-left: 4px solid #3b82f6; }
        .stat-card.green { border-left: 4px solid #10b981; }
        .stat-card.orange { border-left: 4px solid #f59e0b; }
        .stat-card.purple { border-left: 4px solid #8b5cf6; }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
        }

        .stat-label {
            color: #6b7280;
            font-size: 14px;
        }

        .stat-icon {
            position: absolute;
            right: 20px;
            top: 20px;
            font-size: 40px;
            opacity: 0.1;
        }

        .content-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f3f4f6;
        }

        .card-header h2 {
            font-size: 20px;
            color: #1f2937;
        }

        .btn-primary {
            padding: 10px 20px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary:hover {
            background: #2563eb;
        }

        .btn-success {
            padding: 8px 15px;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }

        .btn-success:hover {
            background: #059669;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th {
            background: #f9fafb;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }

        table td {
            padding: 12px;
            border-bottom: 1px solid #f3f4f6;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-approved { background: #d1fae5; color: #065f46; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-rejected { background: #fee2e2; color: #991b1b; }

        .search-box {
            background: #f9fafb;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .search-box input {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 14px;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-building" style="font-size: 40px;"></i>
            <h3>Organization Portal</h3>
        </div>
        <ul class="nav-menu">
            <li class="nav-item active" onclick="location.href='organization_dashboard.php'"><i class="fas fa-home"></i> Dashboard</li>
            <li class="nav-item" onclick="location.href='search_record.php'"><i class="fas fa-search"></i> Search Records</li>
            <li class="nav-item" onclick="location.href='org_requests.php'"><i class="fas fa-file-alt"></i> My Requests</li>
            <li class="nav-item" onclick="location.href='org_downloads.php'"><i class="fas fa-download"></i> Downloads</li>
            <li class="nav-item" onclick="location.href='org_settings.php'"><i class="fas fa-cog"></i> Settings</li>
        </ul>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div>
                <h2>🏢 <?php echo htmlspecialchars($user['organization_name']); ?></h2>
                <p style="color: #6b7280; font-size: 14px;"><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <div class="stats-grid">
            <div class="stat-card blue">
                <i class="fas fa-file-alt stat-icon" style="color: #3b82f6;"></i>
                <div class="stat-label">Total Requests</div>
                <div class="stat-number"><?php echo $total_requests; ?></div>
            </div>
            <div class="stat-card green">
                <i class="fas fa-check-circle stat-icon" style="color: #10b981;"></i>
                <div class="stat-label">Approved</div>
                <div class="stat-number"><?php echo $approved_requests; ?></div>
            </div>
            <div class="stat-card orange">
                <i class="fas fa-clock stat-icon" style="color: #f59e0b;"></i>
                <div class="stat-label">Pending</div>
                <div class="stat-number"><?php echo $pending_requests; ?></div>
            </div>
            <div class="stat-card purple">
                <i class="fas fa-download stat-icon" style="color: #8b5cf6;"></i>
                <div class="stat-label">Downloads</div>
                <div class="stat-number"><?php echo $downloads; ?></div>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header">
                <h2><i class="fas fa-search"></i> Search Crime Records</h2>
            </div>
            <div class="search-box">
                <form action="search_record.php" method="GET">
                    <input type="text" name="nin" placeholder="Enter NIN to search for crime records..." required>
                    <button type="submit" class="btn-primary" style="margin-top: 10px; width: 100%;">
                        <i class="fas fa-search"></i> Search & Request Verification
                    </button>
                </form>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> Recent Verification Requests</h2>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>NIN</th>
                        <th>Full Name</th>
                        <th>Crime Type</th>
                        <th>Request Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($request = mysqli_fetch_assoc($requests_result)): ?>
                    <tr>
                        <td>#<?php echo str_pad($request['id'], 5, '0', STR_PAD_LEFT); ?></td>
                        <td><?php echo htmlspecialchars($request['nin']); ?></td>
                        <td><?php echo $request['full_name'] ? htmlspecialchars($request['full_name']) : 'N/A'; ?></td>
                        <td><?php echo $request['crime_type'] ? htmlspecialchars($request['crime_type']) : 'No Record'; ?></td>
                        <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                        <td><span class="status-badge status-<?php echo $request['status']; ?>"><?php echo ucfirst($request['status']); ?></span></td>
                        <td>
                            <?php if($request['status'] == 'approved' && $request['full_name']): ?>
                                <a href="download_record.php?nin=<?php echo $request['nin']; ?>" class="btn-success">
                                    <i class="fas fa-download"></i> Download
                                </a>
                            <?php else: ?>
                                <span style="color: #9ca3af;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>

<?php
mysqli_stmt_close($stmt);
mysqli_close($conn);
?>