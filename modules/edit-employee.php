<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'hr') {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Fetch HR Profile for Topbar
$stmt = $db->prepare("SELECT first_name, last_name, profile_picture FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$hrUser = $stmt->fetch(PDO::FETCH_ASSOC);
$hrFirstName = $_SESSION['first_name'] ?? ($hrUser['first_name'] ?? 'HR');
$hrLastName = $_SESSION['last_name'] ?? ($hrUser['last_name'] ?? 'Manager');
$hrInitials = strtoupper(substr($hrFirstName, 0, 1) . substr($hrLastName, 0, 1));
$hrPic = $hrUser['profile_picture'] ?? '';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: employees.php');
    exit;
}

// Fetch current employee data
$stmt = $db->prepare("SELECT * FROM users WHERE id = :id AND user_type = 'employee'");
$stmt->execute([':id' => $id]);
$emp = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$emp) {
    die("Employee not found.");
}

$successMsg = '';
$errorMsg = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $department = trim($_POST['department']);
    $position = trim($_POST['position']);
    $salary = !empty($_POST['salary']) ? $_POST['salary'] : null;
    $status = $_POST['status'];
    $join_date = $_POST['join_date'];
    
    // Profile Picture Upload Logic
    $profile_picture = $emp['profile_picture']; // Keep old picture by default
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/profiles/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png'];
        
        if (in_array($file_ext, $allowed_ext)) {
            $new_filename = uniqid('emp_') . '.' . $file_ext;
            $target_file = $upload_dir . $new_filename;
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
                // Delete old picture if exists
                if (!empty($emp['profile_picture']) && file_exists('../' . $emp['profile_picture'])) {
                    unlink('../' . $emp['profile_picture']);
                }
                $profile_picture = 'uploads/profiles/' . $new_filename;
            }
        } else {
            $errorMsg = "Invalid image format. Only JPG, JPEG, and PNG are allowed.";
        }
    }

    if (empty($errorMsg)) {
        try {
            $updateStmt = $db->prepare("
                UPDATE users SET 
                    first_name = :first_name, last_name = :last_name, email = :email, 
                    phone = :phone, department = :department, position = :position, 
                    salary = :salary, status = :status, join_date = :join_date, profile_picture = :profile_picture
                WHERE id = :id AND user_type = 'employee'
            ");
            $updateStmt->execute([
                ':first_name' => $first_name, ':last_name' => $last_name, ':email' => $email,
                ':phone' => $phone, ':department' => $department, ':position' => $position,
                ':salary' => $salary, ':status' => $status, ':join_date' => $join_date,
                ':profile_picture' => $profile_picture, ':id' => $id
            ]);
            
            $successMsg = "Employee information updated successfully!";
            
            // Update local array so form shows new data
            $emp['first_name'] = $first_name;
            $emp['last_name'] = $last_name;
            $emp['email'] = $email;
            $emp['phone'] = $phone;
            $emp['department'] = $department;
            $emp['position'] = $position;
            $emp['salary'] = $salary;
            $emp['status'] = $status;
            $emp['join_date'] = $join_date;
            $emp['profile_picture'] = $profile_picture;
            
        } catch (PDOException $e) {
            $errorMsg = "Database Error: " . $e->getMessage();
        }
    }
}

$initials = strtoupper(substr($emp['first_name'], 0, 1) . substr($emp['last_name'], 0, 1));
$profilePicUrl = !empty($emp['profile_picture']) ? '../' . $emp['profile_picture'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Employee | KormoShathi</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        :root {
            --navy: #14243a;
            --primary: #356df3;
            --primary-soft: #edf3ff;
            --ink: #17263c;
            --muted: #7b8798;
            --line: #e9edf3;
            --surface: #ffffff;
            --page: #f5f7fb;
            --sidebar-width: 270px;
            --shadow: 0 8px 26px rgba(26, 43, 70, .045);
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: var(--page);
            color: var(--ink);
            font-family: 'DM Sans', sans-serif;
            font-size: 15px;
        }
        a { text-decoration: none; }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 1040;
            display: flex;
            flex-direction: column;
            color: #d8e2f0;
            background: linear-gradient(180deg, var(--navy) 0%, #102037 100%);
            padding: 23px 16px 18px;
            overflow-y: auto;
        }
        .brand { display: flex; align-items: center; gap: 11px; padding: 0 9px 23px; border-bottom: 1px solid rgba(255,255,255,.09); margin-bottom: 20px; }
        .brand-mark { width: 44px; height: 44px; display: grid; place-items: center; color: #fff; background: linear-gradient(145deg, #4e8bff, #2860dc); border-radius: 13px; font-size: 20px; }
        .brand-name { color: #fff; font: 800 19px 'Plus Jakarta Sans', sans-serif; }
        .brand-sub { color: #91a4bd; font-size: 11px; margin-top: 4px; }
        .nav-caption { color: #71859f; font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 13px 12px 8px; }
        .side-nav { list-style: none; padding: 0; margin: 0; }
        .side-nav li { margin: 3px 0; }
        .side-link { display: flex; align-items: center; gap: 13px; min-height: 46px; padding: 10px 13px; border-radius: 9px; color: #aebdd0; font-size: 14px; font-weight: 500; transition: .18s ease; }
        .side-link i { width: 20px; text-align: center; font-size: 15px; }
        .side-link:hover { color: #fff; background: rgba(255,255,255,.075); }
        .side-link.active { color: #fff; background: linear-gradient(100deg, #3978f5, #2e66e6); }
        .sidebar-bottom { margin-top: auto; padding-top: 16px; border-top: 1px solid rgba(255,255,255,.08); }

        /* Main Content */
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; padding: 0 30px 30px; }
        
        .topbar {
            min-height: 78px; display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid var(--line); margin-bottom: 27px;
        }
        .profile-button { display: flex; align-items: center; gap: 10px; }
        .avatar { width: 42px; height: 42px; border-radius: 50%; object-fit: cover; display: flex; align-items: center; justify-content: center; color: #fff; background: linear-gradient(145deg, #6594ff, #356df3); font-weight: 700; font-size: 16px; }
        
        .page-heading { display: flex; align-items: center; justify-content: space-between; margin-bottom: 22px; }
        .page-heading h1 { font-size: 28px; font-weight: 800; margin: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        
        .panel { background: var(--surface); border: 1px solid var(--line); border-radius: 13px; box-shadow: var(--shadow); padding: 30px; }
        
        .form-label { font-weight: 600; font-size: 14px; color: #435168; }
        .form-control, .form-select { border: 1px solid var(--line); padding: 10px 15px; border-radius: 8px; font-size: 14px; background: #fbfcfe; }
        .form-control:focus, .form-select:focus { border-color: var(--primary); background: #fff; box-shadow: 0 0 0 3px rgba(53,109,243,0.1); }
        
        .profile-upload-box {
            text-align: center; padding: 20px; border: 2px dashed var(--line);
            border-radius: 12px; background: #f8fafc; margin-bottom: 20px;
        }
        .profile-avatar { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 4px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin-bottom: 15px; }
        .profile-initials { width: 100px; height: 100px; border-radius: 50%; background: var(--primary-soft); color: var(--primary); display: grid; place-items: center; font-size: 36px; font-weight: 800; margin: 0 auto 15px; border: 4px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        
        .btn-primary { background: var(--primary); border: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; }
        .btn-primary:hover { background: #2859d9; }
        .btn-light { border: 1px solid var(--line); font-weight: 600; padding: 10px 24px; border-radius: 8px; }

        /* Mobile */
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 1030; }
        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; }
            .sidebar-overlay.active { display: block; }
        }
    </style>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Full Sidebar -->
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="../hr-dashboard.php">
            <span class="brand-mark"><i class="fa-solid fa-people-group"></i></span>
            <span><span class="brand-name d-block">KormoShathi</span><span class="brand-sub d-block">HR MANAGEMENT SYSTEM</span></span>
        </a>
        
        <div class="nav-caption">Workspace</div>
        <ul class="side-nav">
            <li><a class="side-link" href="../hr-dashboard.php"><i class="fa-solid fa-grid-2"></i> Dashboard</a></li>
        </ul>

        <div class="nav-caption">HR Operations</div>
        <ul class="side-nav">
            <li><a class="side-link" href="add-employee.php"><i class="fa-solid fa-user-plus"></i> Add Employee</a></li>
            <li><a class="side-link active" href="employees.php"><i class="fa-solid fa-users"></i> Employees</a></li>
            <li><a class="side-link" href="attendance.php"><i class="fa-solid fa-fingerprint"></i> Attendance</a></li>
            <li><a class="side-link" href="leave.php"><i class="fa-regular fa-calendar-check"></i> Leave Management</a></li>
            <li><a class="side-link" href="payroll.php"><i class="fa-solid fa-wallet"></i> Payroll</a></li>
            <li><a class="side-link" href="payroll-config.php"><i class="fa-solid fa-sliders"></i> Payroll Configuration</a></li>
        </ul>

        <div class="nav-caption">Recruitment</div>
        <ul class="side-nav">
            <li><a class="side-link" href="ai-screening-form.php"><i class="fa-solid fa-robot"></i> AI Screening</a></li>
            <li><a class="side-link" href="hr-submissions.php"><i class="fa-solid fa-file-circle-check"></i> Submissions</a></li>
        </ul>

        <div class="nav-caption">People & Insights</div>
        <ul class="side-nav">
            <li><a class="side-link" href="performance.php"><i class="fa-solid fa-chart-line"></i> Performance</a></li>
            <li><a class="side-link" href="reports.php"><i class="fa-solid fa-chart-pie"></i> Reports</a></li>
            <li><a class="side-link" href="notice-board.php"><i class="fa-solid fa-bullhorn"></i> Notice Board</a></li>
            <li><a class="side-link" href="employee-id-card-list.php"><i class="fa-regular fa-id-card"></i> Employee ID Cards</a></li>
        </ul>

        <div class="sidebar-bottom">
            <ul class="side-nav">
                <li><a class="side-link text-danger" href="../logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li>
            </ul>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="d-flex align-items-center">
                <button class="btn btn-light d-lg-none me-3" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
                <h4 class="fw-bold m-0" style="font-family: 'Plus Jakarta Sans', sans-serif;">Edit Employee</h4>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="profile-button">
                    <?php if (!empty($hrPic)): ?>
                        <img src="../<?php echo htmlspecialchars($hrPic, ENT_QUOTES, 'UTF-8'); ?>" class="avatar" alt="Profile">
                    <?php else: ?>
                        <span class="avatar"><?php echo $hrInitials; ?></span>
                    <?php endif; ?>
                    <span class="fw-bold ms-2" style="font-size: 14px;"><?php echo htmlspecialchars($hrFirstName . ' ' . $hrLastName); ?></span>
                </div>
            </div>
        </header>

        <div class="page-heading">
            <div>
                <div class="text-primary fw-bold" style="font-size: 12px; letter-spacing: 1px; text-transform: uppercase;">Employee Details</div>
                <h1>Edit <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></h1>
            </div>
            <a href="employees.php" class="btn btn-light"><i class="fa-solid fa-arrow-left me-2"></i> Back to List</a>
        </div>

        <?php if($successMsg): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-check-circle me-2"></i> <?php echo $successMsg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if($errorMsg): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fa-solid fa-exclamation-circle me-2"></i> <?php echo $errorMsg; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="panel">
            <form method="POST" enctype="multipart/form-data">
                <div class="row g-4">
                    
                    <!-- Left Column: Profile Picture & Status -->
                    <div class="col-lg-4">
                        <div class="profile-upload-box">
                            <?php if($profilePicUrl): ?>
                                <img src="<?php echo htmlspecialchars($profilePicUrl); ?>" alt="Profile" class="profile-avatar" id="previewImage">
                            <?php else: ?>
                                <div class="profile-initials" id="previewInitials"><?php echo $initials; ?></div>
                                <img src="" alt="Profile" class="profile-avatar d-none" id="previewImage">
                            <?php endif; ?>
                            
                            <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?></h5>
                            <p class="text-muted mb-3" style="font-size: 13px;"><?php echo htmlspecialchars($emp['employee_id']); ?></p>
                            
                            <label for="profile_picture" class="btn btn-outline-primary btn-sm rounded-pill">
                                <i class="fa-solid fa-camera me-1"></i> Change Photo
                            </label>
                            <input type="file" id="profile_picture" name="profile_picture" class="d-none" accept="image/png, image/jpeg, image/jpg" onchange="previewFile(this)">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Account Status</label>
                            <select name="status" class="form-select">
                                <option value="active" <?php echo ($emp['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($emp['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <!-- Right Column: Form Fields -->
                    <div class="col-lg-8">
                        <h5 class="fw-bold mb-3 border-bottom pb-2">Personal Information</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" class="form-control" value="<?php echo htmlspecialchars($emp['first_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="last_name" class="form-control" value="<?php echo htmlspecialchars($emp['last_name']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($emp['email']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($emp['phone']); ?>">
                            </div>
                        </div>

                        <h5 class="fw-bold mb-3 border-bottom pb-2">Job Information</h5>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Department</label>
                                <select name="department" class="form-select">
                                    <option value="IT" <?php echo ($emp['department'] === 'IT') ? 'selected' : ''; ?>>IT</option>
                                    <option value="HR" <?php echo ($emp['department'] === 'HR') ? 'selected' : ''; ?>>Human Resources</option>
                                    <option value="Marketing" <?php echo ($emp['department'] === 'Marketing') ? 'selected' : ''; ?>>Marketing</option>
                                    <option value="Sales" <?php echo ($emp['department'] === 'Sales') ? 'selected' : ''; ?>>Sales</option>
                                    <option value="Finance" <?php echo ($emp['department'] === 'Finance') ? 'selected' : ''; ?>>Finance</option>
                                    <option value="Operations" <?php echo ($emp['department'] === 'Operations') ? 'selected' : ''; ?>>Operations</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Position / Designation</label>
                                <input type="text" name="position" class="form-control" value="<?php echo htmlspecialchars($emp['position']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Join Date</label>
                                <input type="date" name="join_date" class="form-control" value="<?php echo htmlspecialchars($emp['join_date']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Monthly Salary (BDT)</label>
                                <input type="number" name="salary" class="form-control" value="<?php echo htmlspecialchars($emp['salary']); ?>" step="0.01">
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                            <a href="employees.php" class="btn btn-light">Cancel</a>
                            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk me-2"></i> Save Changes</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        }

        // Image preview logic
        function previewFile(input) {
            const file = input.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewImage = document.getElementById('previewImage');
                    const previewInitials = document.getElementById('previewInitials');
                    
                    previewImage.src = e.target.result;
                    previewImage.classList.remove('d-none');
                    if(previewInitials) previewInitials.classList.add('d-none');
                }
                reader.readAsDataURL(file);
            }
        }
    </script>
</body>
</html>