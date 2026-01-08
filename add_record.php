<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'police') {
    header("Location: login.html");
    exit();
}

include "db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nin = trim($_POST['nin']);
    $full_name = trim($_POST['full_name']);
    $crime_type = trim($_POST['crime_type']);
    $crime_description = trim($_POST['crime_description']);
    $date_of_crime = $_POST['date_of_crime'];
    $arrest_date = $_POST['arrest_date'];
    $release_date = !empty($_POST['release_date']) ? $_POST['release_date'] : NULL;
    $years = intval($_POST['years']);
    $months = intval($_POST['months']);
    $days = intval($_POST['days']);
    $status = $_POST['status'];
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $added_by = $_SESSION['user_id'];

    // Handle file upload
    $face_photo = NULL;
    if(isset($_FILES['face_photo']) && $_FILES['face_photo']['error'] == 0) {
        $upload_dir = 'uploads/';
        if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        
        $file_ext = pathinfo($_FILES['face_photo']['name'], PATHINFO_EXTENSION);
        $face_photo = $upload_dir . uniqid() . '.' . $file_ext;
        move_uploaded_file($_FILES['face_photo']['tmp_name'], $face_photo);
    }

    $sql = "INSERT INTO crime_records (nin, full_name, crime_type, crime_description, date_of_crime, 
            arrest_date, release_date, years_in_prison, months_in_prison, days_in_prison, status, 
            face_photo, address, phone, added_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssssssiiissssi", $nin, $full_name, $crime_type, $crime_description,
                          $date_of_crime, $arrest_date, $release_date, $years, $months, $days, 
                          $status, $face_photo, $address, $phone, $added_by);
    
    if(mysqli_stmt_execute($stmt)) {
        header("Location: police_dashboard.php?success=1");
        exit();
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Crime Record</title>
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
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        h2 {
            color: #1f2937;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3b82f6;
        }

        .back-btn {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: #6b7280;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            margin-bottom: 5px;
            color: #374151;
            font-weight: 500;
        }

        input, select, textarea {
            padding: 10px;
            border: 1px solid #d1d5db;
            border-radius: 5px;
            font-size: 14px;
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .time-group {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        button[type="submit"] {
            width: 100%;
            padding: 12px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
        }

        button[type="submit"]:hover {
            background: #2563eb;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="container">
    <a href="police_dashboard.php" class="back-btn"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    
    <h2><i class="fas fa-plus-circle"></i> Add New Crime Record</h2>

    <?php if(isset($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="form-grid">
            <div class="form-group">
                <label>NIN (National ID Number) *</label>
                <input type="text" name="nin" required>
            </div>

            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name" required>
            </div>

            <div class="form-group">
                <label>Crime Type *</label>
                <select name="crime_type" required>
                    <option value="">Select crime type</option>
                    <option value="Theft">Theft</option>
                    <option value="Robbery">Robbery</option>
                    <option value="Assault">Assault</option>
                    <option value="Fraud">Fraud</option>
                    <option value="Drug Trafficking">Drug Trafficking</option>
                    <option value="Murder">Murder</option>
                    <option value="Kidnapping">Kidnapping</option>
                    <option value="Cybercrime">Cybercrime</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="form-group">
                <label>Status *</label>
                <select name="status" required>
                    <option value="pending">Pending</option>
                    <option value="serving">Serving Sentence</option>
                    <option value="released">Released</option>
                </select>
            </div>

            <div class="form-group">
                <label>Date of Crime *</label>
                <input type="date" name="date_of_crime" required>
            </div>

            <div class="form-group">
                <label>Arrest Date *</label>
                <input type="date" name="arrest_date" required>
            </div>

            <div class="form-group">
                <label>Release Date</label>
                <input type="date" name="release_date">
            </div>

            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" name="phone">
            </div>

            <div class="form-group full">
                <label>Time in Prison</label>
                <div class="time-group">
                    <div>
                        <input type="number" name="years" placeholder="Years" min="0" value="0">
                    </div>
                    <div>
                        <input type="number" name="months" placeholder="Months" min="0" max="11" value="0">
                    </div>
                    <div>
                        <input type="number" name="days" placeholder="Days" min="0" max="30" value="0">
                    </div>
                </div>
            </div>

            <div class="form-group full">
                <label>Address</label>
                <input type="text" name="address">
            </div>

            <div class="form-group full">
                <label>Crime Description *</label>
                <textarea name="crime_description" required></textarea>
            </div>

            <div class="form-group full">
                <label>Face Photo</label>
                <input type="file" name="face_photo" accept="image/*">
            </div>
        </div>

        <button type="submit"><i class="fas fa-save"></i> Save Record</button>
    </form>
</div>

</body>
</html>