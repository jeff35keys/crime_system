<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'police') {
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

// Fix for the station_name warning
$station_name = isset($user['station_name']) ? $user['station_name'] : 'Not Set';

// Get statistics
$total_records = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM crime_records"))['count'];
$serving = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM crime_records WHERE status='serving'"))['count'];
$released = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM crime_records WHERE status='released'"))['count'];
$pending_requests = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM verification_requests WHERE status='pending'"))['count'];

// Get recent crime records
$records_sql = "SELECT * FROM crime_records ORDER BY added_at DESC LIMIT 10";
$records_result = mysqli_query($conn, $records_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Police Dashboard</title>
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
            background: #2c3e50;
            min-height: 100vh;
            color: white;
            position: fixed;
            left: 0;
            top: 0;
        }

        .sidebar-header {
            padding: 20px;
            background: #1a252f;
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
            background: #34495e;
            border-left-color: #3498db;
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
        }

        .stat-card.blue { border-left: 4px solid #3498db; }
        .stat-card.green { border-left: 4px solid #2ecc71; }
        .stat-card.orange { border-left: 4px solid #e67e22; }
        .stat-card.red { border-left: 4px solid #e74c3c; }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin: 10px 0;
        }

        .stat-label {
            color: #7f8c8d;
            font-size: 14px;
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
        }

        .card-header h2 {
            font-size: 20px;
            color: #2c3e50;
        }

        .btn-primary {
            padding: 10px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th {
            background: #ecf0f1;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #2c3e50;
        }

        table td {
            padding: 12px;
            border-bottom: 1px solid #ecf0f1;
        }

        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-serving { background: #ffe6e6; color: #e74c3c; }
        .status-released { background: #d4edda; color: #2ecc71; }
        .status-pending { background: #fff3cd; color: #f39c12; }

        .user-info {
            background: #ecf0f1;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .user-info p {
            margin: 5px 0;
            color: #2c3e50;
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-shield-alt" style="font-size: 40px;"></i>
            <h3>Police Portal</h3>
        </div>
        <ul class="nav-menu">
            <li class="nav-item active" onclick="location.href='police_dashboard.php'"><i class="fas fa-home"></i> Dashboard</li>
            <li class="nav-item" onclick="location.href='police_crime_records.php'"><i class="fas fa-folder-open"></i> Crime Records</li>
            <li class="nav-item" onclick="location.href='police_verification.php'"><i class="fas fa-user-check"></i> Verification</li>
            <li class="nav-item" onclick="location.href='police_reports.php'"><i class="fas fa-chart-bar"></i> Reports</li>
            <li class="nav-item" onclick="location.href='police_settings.php'"><i class="fas fa-cog"></i> Settings</li>
        </ul>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div>
                <h2>Welcome, Officer <?php echo htmlspecialchars($user['police_id']); ?></h2>
                <p style="color: #7f8c8d; font-size: 14px;">Station: <?php echo htmlspecialchars($station_name); ?></p>
            </div>
            <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-label">Total Records</div>
                <div class="stat-number"><?php echo $total_records; ?></div>
                <i class="fas fa-database" style="font-size: 30px; color: #3498db; opacity: 0.3;"></i>
            </div>
            <div class="stat-card orange">
                <div class="stat-label">Serving Sentence</div>
                <div class="stat-number"><?php echo $serving; ?></div>
                <i class="fas fa-user-lock" style="font-size: 30px; color: #e67e22; opacity: 0.3;"></i>
            </div>
            <div class="stat-card green">
                <div class="stat-label">Released</div>
                <div class="stat-number"><?php echo $released; ?></div>
                <i class="fas fa-user-check" style="font-size: 30px; color: #2ecc71; opacity: 0.3;"></i>
            </div>
            <div class="stat-card red">
                <div class="stat-label">Pending Requests</div>
                <div class="stat-number"><?php echo $pending_requests; ?></div>
                <i class="fas fa-clock" style="font-size: 30px; color: #e74c3c; opacity: 0.3;"></i>
            </div>
        </div>

        <div class="content-card">
            <div class="card-header">
                <h2><i class="fas fa-list"></i> Recent Crime Records</h2>
                <a href="add_record.php" class="btn-primary"><i class="fas fa-plus"></i> Add New Record</a>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>NIN</th>
                        <th>Full Name</th>
                        <th>Crime Type</th>
                        <th>Date of Crime</th>
                        <th>Time Served</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($record = mysqli_fetch_assoc($records_result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($record['nin']); ?></td>
                        <td><?php echo htmlspecialchars($record['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($record['crime_type']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($record['date_of_crime'])); ?></td>
                        <td><?php echo $record['years_in_prison'].'Y '.$record['months_in_prison'].'M '.$record['days_in_prison'].'D'; ?></td>
                        <td><span class="status-badge status-<?php echo $record['status']; ?>"><?php echo ucfirst($record['status']); ?></span></td>
                        <td>
                            <a href="view_record.php?id=<?php echo $record['id']; ?>" style="color: #3498db; margin-right: 10px;"><i class="fas fa-eye"></i></a>
                            <a href="edit_record.php?id=<?php echo $record['id']; ?>" style="color: #f39c12;"><i class="fas fa-edit"></i></a>
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