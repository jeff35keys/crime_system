<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'police') {
    header("Location: login.html");
    exit();
}

include "db.php";

$user_id = $_SESSION['user_id'];

// Get all crime records with search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';

$sql = "SELECT * FROM crime_records WHERE 1=1";
$params = array();
$types = "";

if ($search) {
    $sql .= " AND (nin LIKE ? OR full_name LIKE ? OR crime_type LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

if ($filter_status) {
    $sql .= " AND status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

$sql .= " ORDER BY added_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($types) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$records_result = mysqli_stmt_get_result($stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crime Records - Police Portal</title>
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

        .content-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .search-filter {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-filter input,
        .search-filter select {
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 5px;
            flex: 1;
            min-width: 200px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-success {
            background: #22c55e;
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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

        .action-btn {
            margin-right: 10px;
            color: #3498db;
            cursor: pointer;
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
            <li class="nav-item" onclick="location.href='police_dashboard.php'"><i class="fas fa-home"></i> Dashboard</li>
            <li class="nav-item active" onclick="location.href='police_crime_records.php'"><i class="fas fa-folder-open"></i> Crime Records</li>
            <li class="nav-item" onclick="location.href='police_verification.php'"><i class="fas fa-user-check"></i> Verification</li>
            <li class="nav-item" onclick="location.href='police_reports.php'"><i class="fas fa-chart-bar"></i> Reports</li>
            <li class="nav-item" onclick="location.href='police_settings.php'"><i class="fas fa-cog"></i> Settings</li>
        </ul>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2><i class="fas fa-folder-open"></i> Crime Records Management</h2>
            <a href="logout.php" class="btn btn-primary"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>

        <div class="content-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>All Crime Records</h3>
                <a href="add_record.php" class="btn btn-success"><i class="fas fa-plus"></i> Add New Record</a>
            </div>

            <form method="GET" class="search-filter">
                <input type="text" name="search" placeholder="Search by NIN, Name, or Crime Type" value="<?php echo htmlspecialchars($search); ?>">
                <select name="status">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="serving" <?php echo $filter_status == 'serving' ? 'selected' : ''; ?>>Serving</option>
                    <option value="released" <?php echo $filter_status == 'released' ? 'selected' : ''; ?>>Released</option>
                </select>
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                <a href="police_crime_records.php" class="btn" style="background: #6b7280; color: white;"><i class="fas fa-redo"></i> Reset</a>
            </form>

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
                    <?php if(mysqli_num_rows($records_result) > 0): ?>
                        <?php while($record = mysqli_fetch_assoc($records_result)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($record['nin']); ?></td>
                            <td><?php echo htmlspecialchars($record['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($record['crime_type']); ?></td>
                            <td><?php echo date('M d, Y', strtotime($record['date_of_crime'])); ?></td>
                            <td><?php echo $record['years_in_prison'].'Y '.$record['months_in_prison'].'M '.$record['days_in_prison'].'D'; ?></td>
                            <td><span class="status-badge status-<?php echo $record['status']; ?>"><?php echo ucfirst($record['status']); ?></span></td>
                            <td>
                                <a href="view_record.php?id=<?php echo $record['id']; ?>" class="action-btn"><i class="fas fa-eye"></i></a>
                                <a href="edit_record.php?id=<?php echo $record['id']; ?>" class="action-btn" style="color: #f39c12;"><i class="fas fa-edit"></i></a>
                                <a href="delete_record.php?id=<?php echo $record['id']; ?>" class="action-btn" style="color: #e74c3c;" onclick="return confirm('Are you sure you want to delete this record?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #6b7280;">
                                <i class="fas fa-folder-open" style="font-size: 48px; margin-bottom: 10px; display: block;"></i>
                                No crime records found
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>
</html>

<?php
mysqli_close($conn);
?>