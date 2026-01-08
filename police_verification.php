<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'police') {
    header("Location: login.html");
    exit();
}

include "db.php";

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $request_id = $_POST['request_id'];
    $action = $_POST['action'];
    $notes = trim($_POST['notes'] ?? '');
    $police_id = $_SESSION['user_id'];
    
    $new_status = ($action == 'approve') ? 'approved' : 'rejected';
    
    $update_sql = "UPDATE verification_requests SET status = ?, approved_by = ?, approval_date = NOW(), notes = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($stmt, "sisi", $new_status, $police_id, $notes, $request_id);
    mysqli_stmt_execute($stmt);
    
    $message = $new_status == 'approved' ? 'Request approved successfully!' : 'Request rejected.';
}

// Get all verification requests
$sql = "SELECT vr.*, u.organization_name, u.email, cr.full_name, cr.crime_type 
        FROM verification_requests vr 
        LEFT JOIN users u ON vr.organization_id = u.id 
        LEFT JOIN crime_records cr ON vr.nin = cr.nin 
        ORDER BY vr.request_date DESC";
$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verification Requests - Police Portal</title>
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
        .content-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table th { background: #ecf0f1; padding: 12px; text-align: left; font-weight: 600; }
        table td { padding: 12px; border-bottom: 1px solid #ecf0f1; }
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .status-pending { background: #fff3cd; color: #f39c12; }
        .status-approved { background: #d4edda; color: #2ecc71; }
        .status-rejected { background: #ffe6e6; color: #e74c3c; }
        .btn { padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; margin: 2px; }
        .btn-success { background: #22c55e; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
        .modal-content { background: white; margin: 10% auto; padding: 30px; width: 500px; border-radius: 10px; }
        .alert { padding: 12px; background: #d4edda; color: #2ecc71; border-radius: 5px; margin-bottom: 15px; }
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
            <li class="nav-item active"><i class="fas fa-user-check"></i> Verification</li>
            <li class="nav-item" onclick="location.href='police_reports.php'"><i class="fas fa-chart-bar"></i> Reports</li>
            <li class="nav-item" onclick="location.href='police_settings.php'"><i class="fas fa-cog"></i> Settings</li>
        </ul>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2><i class="fas fa-user-check"></i> Verification Requests</h2>
        </div>

        <?php if(isset($message)): ?>
            <div class="alert"><?php echo $message; ?></div>
        <?php endif; ?>

        <div class="content-card">
            <h3>Organization Verification Requests</h3>
            <table>
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>Organization</th>
                        <th>NIN</th>
                        <th>Name/Crime</th>
                        <th>Request Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td>#<?php echo str_pad($row['id'], 5, '0', STR_PAD_LEFT); ?></td>
                        <td><?php echo htmlspecialchars($row['organization_name']); ?><br>
                            <small style="color: #6b7280;"><?php echo htmlspecialchars($row['email']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($row['nin']); ?></td>
                        <td><?php echo $row['full_name'] ? htmlspecialchars($row['full_name']) : 'No Record'; ?><br>
                            <small><?php echo $row['crime_type'] ?? ''; ?></small>
                        </td>
                        <td><?php echo date('M d, Y H:i', strtotime($row['request_date'])); ?></td>
                        <td><span class="status-badge status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                        <td>
                            <?php if($row['status'] == 'pending'): ?>
                                <button class="btn btn-success" onclick="showModal(<?php echo $row['id']; ?>, 'approve')">Approve</button>
                                <button class="btn btn-danger" onclick="showModal(<?php echo $row['id']; ?>, 'reject')">Reject</button>
                            <?php else: ?>
                                <span style="color: #6b7280;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="actionModal" class="modal">
        <div class="modal-content">
            <h3 id="modalTitle">Approve Request</h3>
            <form method="POST">
                <input type="hidden" name="request_id" id="request_id">
                <input type="hidden" name="action" id="action">
                <label>Notes (Optional)</label>
                <textarea name="notes" rows="4" style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px;"></textarea>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-success">Submit</button>
                    <button type="button" class="btn" style="background: #6b7280; color: white;" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showModal(id, action) {
            document.getElementById('request_id').value = id;
            document.getElementById('action').value = action;
            document.getElementById('modalTitle').textContent = action === 'approve' ? 'Approve Request' : 'Reject Request';
            document.getElementById('actionModal').style.display = 'block';
        }
        function closeModal() {
            document.getElementById('actionModal').style.display = 'none';
        }
    </script>

</body>
</html>