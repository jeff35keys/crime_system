<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'organization') {
    header("Location: login.html");
    exit();
}

include "db.php";
$org_id = $_SESSION['user_id'];

// Get download history
$sql = "SELECT dl.*, cr.nin, cr.full_name, cr.crime_type 
        FROM download_logs dl
        JOIN crime_records cr ON dl.record_id = cr.id
        WHERE dl.user_id = $org_id
        ORDER BY dl.download_date DESC";
$result = mysqli_query($conn, $sql);

$total_downloads = mysqli_num_rows($result);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Downloads - Organization Portal</title>
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
        .stats-card { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
        .stats-card .number { font-size: 48px; font-weight: bold; color: #8b5cf6; }
        .stats-card .label { color: #6b7280; margin-top: 10px; }
        .content-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table th { background: #f9fafb; padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e5e7eb; }
        table td { padding: 12px; border-bottom: 1px solid #f3f4f6; }
        .btn { padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-success { background: #10b981; color: white; }
        .download-icon { color: #10b981; font-size: 20px; }
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
            <li class="nav-item" onclick="location.href='org_requests.php'"><i class="fas fa-file-alt"></i> My Requests</li>
            <li class="nav-item active"><i class="fas fa-download"></i> Downloads</li>
            <li class="nav-item" onclick="location.href='org_settings.php'"><i class="fas fa-cog"></i> Settings</li>
        </ul>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2><i class="fas fa-download"></i> Download History</h2>
        </div>

        <div class="stats-card">
            <div class="number"><?php echo $total_downloads; ?></div>
            <div class="label">Total Records Downloaded</div>
        </div>

        <div class="content-card">
            <h3><i class="fas fa-history"></i> Download History</h3>
            
            <?php if($total_downloads > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Download Date</th>
                        <th>NIN</th>
                        <th>Full Name</th>
                        <th>Crime Type</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    mysqli_data_seek($result, 0); // Reset pointer
                    while($row = mysqli_fetch_assoc($result)): 
                    ?>
                    <tr>
                        <td>
                            <i class="fas fa-calendar download-icon"></i>
                            <?php echo date('M d, Y H:i', strtotime($row['download_date'])); ?>
                        </td>
                        <td><?php echo htmlspecialchars($row['nin']); ?></td>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['crime_type']); ?></td>
                        <td>
                            <a href="download_record.php?nin=<?php echo $row['nin']; ?>" class="btn btn-success">
                                <i class="fas fa-download"></i> Re-download
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div style="text-align: center; padding: 60px 20px; color: #6b7280;">
                <i class="fas fa-download" style="font-size: 64px; opacity: 0.3; margin-bottom: 20px;"></i>
                <h3>No Downloads Yet</h3>
                <p>You haven't downloaded any records yet.</p>
                <a href="search_record.php" class="btn btn-primary" style="margin-top: 20px;">
                    <i class="fas fa-search"></i> Search Records
                </a>
            </div>
            <?php endif; ?>
        </div>

        <div class="content-card" style="margin-top: 20px;">
            <h3><i class="fas fa-info-circle"></i> Download Guidelines</h3>
            <ul style="line-height: 2; color: #4b5563; margin-top: 15px;">
                <li><i class="fas fa-check" style="color: #10b981;"></i> All downloads are logged for security purposes</li>
                <li><i class="fas fa-check" style="color: #10b981;"></i> Downloaded records are for authorized use only</li>
                <li><i class="fas fa-check" style="color: #10b981;"></i> You can re-download previously accessed records</li>
                <li><i class="fas fa-check" style="color: #10b981;"></i> Records contain sensitive information - handle with care</li>
            </ul>
        </div>
    </div>

</body>
</html>