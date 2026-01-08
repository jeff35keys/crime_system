<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'organization') {
    header("Location: login.html");
    exit();
}

include "db.php";
$user_id = $_SESSION['user_id'];

// Get user data
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

$message = '';
$error = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (password_verify($current_password, $user['password'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 6) {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $update_sql = "UPDATE users SET password = ? WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "si", $hashed, $user_id);
                if (mysqli_stmt_execute($update_stmt)) {
                    $message = 'Password changed successfully!';
                }
            } else {
                $error = 'Password must be at least 6 characters';
            }
        } else {
            $error = 'Passwords do not match';
        }
    } else {
        $error = 'Current password is incorrect';
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $organization_name = trim($_POST['organization_name']);
    $email = trim($_POST['email']);
    
    $update_sql = "UPDATE users SET organization_name = ?, email = ? WHERE id = ?";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "ssi", $organization_name, $email, $user_id);
    
    if (mysqli_stmt_execute($update_stmt)) {
        $message = 'Profile updated successfully!';
        $_SESSION['organization_name'] = $organization_name;
        // Refresh user data
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
    }
}

// Handle theme change
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_theme'])) {
    $theme = $_POST['theme'];
    $_SESSION['theme'] = $theme;
    $message = 'Theme changed successfully!';
}

$current_theme = $_SESSION['theme'] ?? 'light';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings - Organization Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, sans-serif; }
        body { background: #f0f2f5; display: flex; }
        body.dark { background: #1a1a1a; }
        .sidebar { width: 250px; background: #1a365d; min-height: 100vh; color: white; position: fixed; }
        body.dark .sidebar { background: #0f1419; }
        .sidebar-header { padding: 20px; background: #0f1c2e; text-align: center; }
        .nav-menu { list-style: none; padding: 20px 0; }
        .nav-item { padding: 15px 25px; cursor: pointer; transition: 0.3s; border-left: 3px solid transparent; }
        .nav-item:hover, .nav-item.active { background: #2d4a73; border-left-color: #60a5fa; }
        .nav-item i { margin-right: 10px; }
        .main-content { margin-left: 250px; flex: 1; padding: 20px; }
        .top-bar { background: white; padding: 15px 30px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        body.dark .top-bar { background: #2d2d2d; color: white; }
        .content-card { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        body.dark .content-card { background: #2d2d2d; color: white; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; color: #374151; }
        body.dark .form-group label { color: #e5e7eb; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 5px; }
        body.dark .form-group input, body.dark .form-group select { background: #3d3d3d; border-color: #555; color: white; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-success { background: #10b981; color: white; }
        .alert { padding: 12px; border-radius: 5px; margin-bottom: 15px; }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-error { background: #fee2e2; color: #991b1b; }
        .settings-tabs { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .tab-btn { padding: 10px 20px; background: #e5e7eb; border: none; cursor: pointer; border-radius: 5px; }
        body.dark .tab-btn { background: #3d3d3d; color: white; }
        .tab-btn.active { background: #3b82f6; color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .theme-option { padding: 15px; border: 2px solid #e5e7eb; border-radius: 8px; margin: 10px 0; cursor: pointer; display: flex; align-items: center; gap: 15px; }
        .theme-option.selected { border-color: #3b82f6; background: #dbeafe; }
        body.dark .theme-option { border-color: #3d3d3d; }
        body.dark .theme-option.selected { background: #1e3a8a; }
        .theme-preview { width: 60px; height: 40px; border-radius: 5px; }
    </style>
</head>
<body class="<?php echo $current_theme; ?>">

    <div class="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-building" style="font-size: 40px;"></i>
            <h3>Organization Portal</h3>
        </div>
        <ul class="nav-menu">
            <li class="nav-item" onclick="location.href='organization_dashboard.php'"><i class="fas fa-home"></i> Dashboard</li>
            <li class="nav-item" onclick="location.href='search_record.php'"><i class="fas fa-search"></i> Search Records</li>
            <li class="nav-item" onclick="location.href='org_requests.php'"><i class="fas fa-file-alt"></i> My Requests</li>
            <li class="nav-item" onclick="location.href='org_downloads.php'"><i class="fas fa-download"></i> Downloads</li>
            <li class="nav-item active"><i class="fas fa-cog"></i> Settings</li>
        </ul>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h2><i class="fas fa-cog"></i> Settings</h2>
        </div>

        <?php if($message): ?>
            <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $message; ?></div>
        <?php endif; ?>
        <?php if($error): ?>
            <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="settings-tabs">
            <button class="tab-btn active" onclick="showTab('profile')">Organization Profile</button>
            <button class="tab-btn" onclick="showTab('password')">Security</button>
            <button class="tab-btn" onclick="showTab('theme')">Appearance</button>
            <button class="tab-btn" onclick="showTab('notifications')">Notifications</button>
            <button class="tab-btn" onclick="showTab('api')">API Access</button>
        </div>

        <!-- Profile Tab -->
        <div id="profile" class="tab-content active">
            <div class="content-card">
                <h3><i class="fas fa-building"></i> Organization Information</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>Organization Name</label>
                        <input type="text" name="organization_name" value="<?php echo htmlspecialchars($user['organization_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Account Created</label>
                        <input type="text" value="<?php echo date('F j, Y', strtotime($user['created_at'])); ?>" disabled>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                </form>
            </div>
        </div>

        <!-- Password Tab -->
        <div id="password" class="tab-content">
            <div class="content-card">
                <h3><i class="fas fa-shield-alt"></i> Change Password</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" required>
                    </div>
                    <div class="form-group">
                        <label>New Password (minimum 6 characters)</label>
                        <input type="password" name="new_password" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" required minlength="6">
                    </div>
                    <button type="submit" name="change_password" class="btn btn-primary"><i class="fas fa-key"></i> Update Password</button>
                </form>
            </div>
        </div>

        <!-- Theme Tab -->
        <div id="theme" class="tab-content">
            <div class="content-card">
                <h3><i class="fas fa-palette"></i> Appearance Settings</h3>
                <form method="POST">
                    <div class="theme-option <?php echo $current_theme == 'light' ? 'selected' : ''; ?>" onclick="selectTheme('light')">
                        <div class="theme-preview" style="background: linear-gradient(to right, #fff, #f0f0f0);"></div>
                        <div>
                            <strong>Light Theme</strong>
                            <p style="color: #6b7280; font-size: 14px;">Clean and professional</p>
                        </div>
                    </div>
                    <div class="theme-option <?php echo $current_theme == 'dark' ? 'selected' : ''; ?>" onclick="selectTheme('dark')">
                        <div class="theme-preview" style="background: linear-gradient(to right, #1a1a1a, #2d2d2d);"></div>
                        <div>
                            <strong>Dark Theme</strong>
                            <p style="color: #6b7280; font-size: 14px;">Reduce eye strain</p>
                        </div>
                    </div>
                    <input type="hidden" name="theme" id="themeInput" value="<?php echo $current_theme; ?>">
                    <button type="submit" name="change_theme" class="btn btn-success"><i class="fas fa-check"></i> Apply Theme</button>
                </form>
            </div>
        </div>

        <!-- Notifications Tab -->
        <div id="notifications" class="tab-content">
            <div class="content-card">
                <h3><i class="fas fa-bell"></i> Notification Preferences</h3>
                <div class="form-group">
                    <label><input type="checkbox" checked> Email when verification request is approved</label>
                </div>
                <div class="form-group">
                    <label><input type="checkbox" checked> Email when verification request is rejected</label>
                </div>
                <div class="form-group">
                    <label><input type="checkbox"> Weekly report summary</label>
                </div>
                <div class="form-group">
                    <label><input type="checkbox"> SMS notifications</label>
                </div>
                <button class="btn btn-primary"><i class="fas fa-save"></i> Save Preferences</button>
            </div>
        </div>

        <!-- API Tab -->
        <div id="api" class="tab-content">
            <div class="content-card">
                <h3><i class="fas fa-code"></i> API Access</h3>
                <p style="color: #6b7280; margin-bottom: 20px;">Generate API keys for programmatic access to crime records</p>
                <div class="form-group">
                    <label>API Key</label>
                    <input type="text" value="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" disabled>
                </div>
                <button class="btn btn-primary"><i class="fas fa-sync"></i> Generate New Key</button>
                <button class="btn" style="background: #ef4444; color: white; margin-left: 10px;"><i class="fas fa-trash"></i> Revoke Key</button>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }
        
        function selectTheme(theme) {
            document.querySelectorAll('.theme-option').forEach(opt => opt.classList.remove('selected'));
            event.currentTarget.classList.add('selected');
            document.getElementById('themeInput').value = theme;
        }
    </script>

</body>
</html>