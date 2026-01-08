<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'organization') {
    header("Location: login.html");
    exit();
}

include "db.php";
$org_id = $_SESSION['user_id'];

$sql = "SELECT vr.*, cr.full_name, cr.crime_type, cr.status as record_status 
        FROM verification_requests vr 
        LEFT JOIN crime_records cr ON vr.nin = cr.nin 
        WHERE vr.organization_id = $org_id 
        ORDER BY vr.request_date DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Requests - Organization Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, sans-serif; }
        body { background: #f0f2f5; display: flex; }
        .sidebar { width: 250px; background: #1a365d; min-height: 100vh; color: white; position: fixed; }
        .sidebar-header { padding: 20px; background: #0f1c2e; text-align: center; }
        .nav-menu { list-style: none; padding: 20px 0; }
        .nav-item { padding: 15px 25px; cursor: pointer; transition: 0.3s; border-left: 3px solid transparent; }
        .nav-item:hover, .nav-item.active { background: #2d4a73; border-left-color: #60a5fa; }
        .nav-item i { margin-right: 10px; }
        .main-content { margin-left: 250px; flex: 1; padding: 20px; }
        .top-bar { background: white; padding: 15px 30px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .content-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table th { background: #f9fafb; padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e5e7eb; }
        table td { padding: 12px; border-bottom: 1px solid #f3f4f6; }
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-approved { background: #d1fae5; color: #065f46; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .btn { padding: 8px 15px; border: none; border-radius: 5px; text-decoration: none; display: inline-block; }
        .btn-success { background: #10b981; color: white; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-building" style="font-size: 40px;"></i>
            <h3>Organization Portal</h3>
        </div>
        <ul class="nav-menu">
            <li class="nav-item" onclick="location.href='organization_dashboard.php'"><i class="fas fa-home"></i> Dashboard</li>
            <li class="nav-item" onclick="location.href='search_record.php'"><i class="fas fa-search"></i> Search Records</li>
            <li class="nav-item active"><i class="fas fa-file-alt"></i> My Requests</li>
            <li class="nav-item" onclick="location.href='org_downloads.php'"><i class="fas fa-download"></i> Downloads</li>
            <li class="nav-item" onclick="location.href='org_settings.php'"><i class="fas fa-cog"></i> Settings</li>
        </ul>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2><i class="fas fa-file-alt"></i> My Verification Requests</h2>
        </div>

        <div class="content-card">
            <table>
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>NIN</th>
                        <th>Full Name</th>
                        <th>Crime Type</th>
                        <th>Request Date</th>
                        <th>Status</th>
                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td>#<?php echo str_pad($row['id'], 5, '0', STR_PAD_LEFT); ?></td>
                        <td><?php echo htmlspecialchars($row['nin']); ?></td>
                        <td><?php echo $row['full_name'] ? htmlspecialchars($row['full_name']) : 'N/A'; ?></td>
                        <td><?php echo $row['crime_type'] ?? 'No Record'; ?></td>
                        <td><?php echo date('M d, Y', strtotime($row['request_date'])); ?></td>
                        <td><span class="status-badge status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                        <td><?php echo $row['notes'] ? htmlspecialchars($row['notes']) : '-'; ?></td>
                        <td>
                            <?php if($row['status'] == 'approved' && $row['full_name']): ?>
                                <a href="download_record.php?nin=<?php echo $row['nin']; ?>" class="btn btn-success">
                                    <i class="fas fa-download"></i> Download
                                </a>
                            <?php else: ?>
                                -
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