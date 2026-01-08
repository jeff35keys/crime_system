<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'police') {
    header("Location: login.html");
    exit();
}

include "db.php";

// Get statistics
$total_records = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM crime_records"))['count'];
$serving = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM crime_records WHERE status='serving'"))['count'];
$released = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM crime_records WHERE status='released'"))['count'];
$pending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM crime_records WHERE status='pending'"))['count'];

// Crime types statistics
$crime_types = mysqli_query($conn, "SELECT crime_type, COUNT(*) as count FROM crime_records GROUP BY crime_type ORDER BY count DESC");

// Monthly statistics (last 6 months)
$monthly_stats = mysqli_query($conn, "SELECT DATE_FORMAT(date_of_crime, '%Y-%m') as month, COUNT(*) as count 
                                      FROM crime_records 
                                      WHERE date_of_crime >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                                      GROUP BY month ORDER BY month DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports - Police Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, sans-serif; }
        body { background: #f0f2f5; display: flex; }
        .sidebar { width: 250px; background: #2c3e50; min-height: 100vh; color: white; position: fixed; }
        .sidebar-header { padding: 20px; background: #1a252f; text-align: center; }
        .nav-menu { list-style: none; padding: 20px 0; }
        .nav-item { padding: 15px 25px; cursor: pointer; transition: 0.3s; border-left: 3px solid transparent; }
        .nav-item:hover, .nav-item.active { background: #34495e; border-left-color: #3498db; }
        .nav-item i { margin-right: 10px; width: 20px; }
        .main-content { margin-left: 250px; flex: 1; padding: 20px; }
        .top-bar { background: white; padding: 15px 30px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-card.blue { border-left: 4px solid #3498db; }
        .stat-card.orange { border-left: 4px solid #e67e22; }
        .stat-card.green { border-left: 4px solid #2ecc71; }
        .stat-card.red { border-left: 4px solid #e74c3c; }
        .stat-number { font-size: 32px; font-weight: bold; margin: 10px 0; }
        .stat-label { color: #7f8c8d; font-size: 14px; }
        .content-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .chart-container { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .chart-item { padding: 20px; background: #f9fafb; border-radius: 8px; }
        .bar { background: #3498db; height: 30px; margin: 10px 0; border-radius: 5px; display: flex; align-items: center; padding: 0 10px; color: white; }
        .btn { padding: 10px 20px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-shield-alt" style="font-size: 40px;"></i>
            <h3>Police Portal</h3>
        </div>
        <ul class="nav-menu">
            <li class="nav-item" onclick="location.href='police_dashboard.php'"><i class="fas fa-home"></i> Dashboard</li>
            <li class="nav-item" onclick="location.href='police_crime_records.php'"><i class="fas fa-folder-open"></i> Crime Records</li>
            <li class="nav-item" onclick="location.href='police_verification.php'"><i class="fas fa-user-check"></i> Verification</li>
            <li class="nav-item active"><i class="fas fa-chart-bar"></i> Reports</li>
            <li class="nav-item" onclick="location.href='police_settings.php'"><i class="fas fa-cog"></i> Settings</li>
        </ul>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2><i class="fas fa-chart-bar"></i> Reports & Analytics</h2>
        </div>

        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-label">Total Records</div>
                <div class="stat-number"><?php echo $total_records; ?></div>
            </div>
            <div class="stat-card orange">
                <div class="stat-label">Serving Sentence</div>
                <div class="stat-number"><?php echo $serving; ?></div>
            </div>
            <div class="stat-card green">
                <div class="stat-label">Released</div>
                <div class="stat-number"><?php echo $released; ?></div>
            </div>
            <div class="stat-card red">
                <div class="stat-label">Pending</div>
                <div class="stat-number"><?php echo $pending; ?></div>
            </div>
        </div>

        <div class="chart-container">
            <div class="content-card">
                <h3><i class="fas fa-chart-pie"></i> Crime Types Distribution</h3>
                <div style="margin-top: 20px;">
                    <?php 
                    $max_count = 0;
                    $crimes = [];
                    while($row = mysqli_fetch_assoc($crime_types)) {
                        $crimes[] = $row;
                        if($row['count'] > $max_count) $max_count = $row['count'];
                    }
                    foreach($crimes as $crime): 
                        $percentage = ($crime['count'] / $max_count) * 100;
                    ?>
                        <div class="chart-item">
                            <strong><?php echo htmlspecialchars($crime['crime_type']); ?></strong> (<?php echo $crime['count']; ?> cases)
                            <div class="bar" style="width: <?php echo $percentage; ?>%;">
                                <?php echo $crime['count']; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="content-card">
                <h3><i class="fas fa-calendar"></i> Monthly Crime Reports (Last 6 Months)</h3>
                <div style="margin-top: 20px;">
                    <?php 
                    $max_monthly = 0;
                    $monthly = [];
                    while($row = mysqli_fetch_assoc($monthly_stats)) {
                        $monthly[] = $row;
                        if($row['count'] > $max_monthly) $max_monthly = $row['count'];
                    }
                    foreach($monthly as $month): 
                        $percentage = $max_monthly > 0 ? ($month['count'] / $max_monthly) * 100 : 0;
                    ?>
                        <div class="chart-item">
                            <strong><?php echo date('F Y', strtotime($month['month'] . '-01')); ?></strong>
                            <div class="bar" style="width: <?php echo $percentage; ?>%; background: #2ecc71;">
                                <?php echo $month['count']; ?> cases
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="content-card">
            <h3><i class="fas fa-download"></i> Export Reports</h3>
            <p style="color: #6b7280; margin: 15px 0;">Download detailed reports in various formats</p>
            <button class="btn" onclick="window.print()"><i class="fas fa-print"></i> Print Report</button>
            <button class="btn" style="background: #2ecc71;"><i class="fas fa-file-excel"></i> Export to Excel</button>
            <button class="btn" style="background: #e74c3c;"><i class="fas fa-file-pdf"></i> Export to PDF</button>
        </div>
    </div>

</body>
</html>
