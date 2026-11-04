<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Get user data
$stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmt->bindParam(':id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$is_hr = ($_SESSION['user_type'] == 'hr');
$dashboardUrl = $is_hr ? 'hr-dashboard.php' : 'employee-dashboard.php';

// Avatar Logic
$pic = $user['profile_picture'] ?? '';
$isUrl = !empty($pic) && (strpos($pic, 'http://') === 0 || strpos($pic, 'https://') === 0);
$isLocal = !empty($pic) && !$isUrl && file_exists(__DIR__ . '/' . $pic);
$profileImage = '';
if ($isUrl) { $profileImage = $pic; } 
elseif ($isLocal) { $profileImage = $pic; }

$initials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
$fullName = trim($user['first_name'] . ' ' . $user['last_name']);
$position = $user['position'] ?? ($is_hr ? 'HR Manager' : 'Employee');

function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | KormoShathi</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        :root {
            --navy: #14243a; --primary: #356df3; --primary-soft: #edf3ff;
            --ink: #17263c; --muted: #7b8798; --line: #e9edf3;
            --surface: #ffffff; --page: #f5f7fb; --sidebar-width: 270px;
            --shadow: 0 8px 26px rgba(26, 43, 70, .045);
            --green: #10b981; --orange: #f59e0b; --red: #ef4444; --purple: #8b5cf6;
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--page); color: var(--ink); font-family: 'DM Sans', sans-serif; font-size: 15px; overflow-x: hidden; }
        a { text-decoration: none; }
        button, input { font: inherit; }

        /* Sidebar */
        .sidebar { width: var(--sidebar-width); position: fixed; inset: 0 auto 0 0; z-index: 1040; display: flex; flex-direction: column; color: #d8e2f0; background: linear-gradient(180deg, var(--navy) 0%, #102037 100%); padding: 23px 16px 18px; overflow-y: auto; transition: transform .25s ease; }
        .brand { display: flex; align-items: center; gap: 11px; padding: 0 9px 23px; border-bottom: 1px solid rgba(255,255,255,.09); margin-bottom: 20px; }
        .brand-mark { width: 44px; height: 44px; display: grid; place-items: center; color: #fff; background: linear-gradient(145deg, #4e8bff, #2860dc); border-radius: 13px; font-size: 20px; box-shadow: 0 7px 18px rgba(53,109,243,.28); }
        .brand-name { color: #fff; font: 800 19px 'Plus Jakarta Sans', sans-serif; letter-spacing: -.5px; line-height: 1.15; }
        .brand-sub { color: #91a4bd; font-size: 11px; margin-top: 4px; letter-spacing: .55px; }
        .nav-caption { color: #71859f; font-size: 11px; font-weight: 700; letter-spacing: 1.15px; text-transform: uppercase; padding: 13px 12px 8px; }
        .side-nav { list-style: none; padding: 0; margin: 0; } .side-nav li { margin: 3px 0; }
        .side-link { display: flex; align-items: center; gap: 13px; min-height: 46px; padding: 10px 13px; border-radius: 9px; color: #aebdd0; font-size: 14px; font-weight: 500; transition: .18s ease; }
        .side-link i { width: 20px; text-align: center; font-size: 15px; } .side-link:hover { color: #fff; background: rgba(255,255,255,.075); }
        .side-link.active { color: #fff; background: linear-gradient(100deg, #3978f5, #2e66e6); box-shadow: 0 7px 18px rgba(31,91,218,.23); }
        .sidebar-bottom { margin-top: auto; padding-top: 16px; }
        .sidebar-help { margin: 13px 4px 15px; padding: 14px; background: rgba(255,255,255,.065); border: 1px solid rgba(255,255,255,.07); border-radius: 12px; }
        .sidebar-help .help-icon { width: 32px; height: 32px; display: grid; place-items: center; background: rgba(83,139,255,.2); color: #8eb5ff; border-radius: 9px; margin-bottom: 9px; }
        .sidebar-help strong { display: block; color: #fff; font-size: 13px; } .sidebar-help small { color: #91a4bd; font-size: 11px; display: block; margin: 4px 0 9px; } .sidebar-help a { color: #9fc0ff; font-size: 12px; font-weight: 700; }

        /* Main Content */
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; padding: 0 30px 30px; transition: margin 0.3s ease; }
        
        /* Topbar */
        .topbar { min-height: 78px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--line); margin-bottom: 27px; gap: 20px; }
        .topbar-left, .topbar-right { display: flex; align-items: center; gap: 14px; }
        .mobile-menu { display: none; width: 42px; height: 42px; border: 1px solid var(--line); background: #fff; color: var(--ink); border-radius: 10px; }
        .search-wrap { position: relative; width: min(370px, 42vw); }
        .search-wrap i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9aa6b6; }
        .search-wrap input { width: 100%; height: 44px; padding: 0 15px 0 41px; background: #fff; border: 1px solid var(--line); border-radius: 10px; outline: none; color: var(--ink); font-size: 14px; }
        .top-icon { position: relative; display: grid; place-items: center; width: 42px; height: 42px; border: 1px solid var(--line); background: #fff; color: #637187; border-radius: 10px; cursor: pointer; }
        .top-icon .dot { position: absolute; width: 8px; height: 8px; border-radius: 50%; background: #ed6470; right: 8px; top: 7px; border: 1px solid #fff; }
        .notification-badge { position: absolute; top: -5px; right: -5px; background: var(--red); color: white; font-size: 10px; font-weight: 700; border-radius: 50%; min-width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; border: 2px solid white; }
        .top-divider { height: 34px; border-left: 1px solid var(--line); margin: 0 2px; }
        .profile-button { display: flex; align-items: center; gap: 10px; border: 0; background: transparent; text-align: left; color: var(--ink); padding: 2px; }
        .avatar { width: 42px; height: 42px; flex: 0 0 42px; border-radius: 50%; object-fit: cover; display: flex; align-items: center; justify-content: center; color: #fff; background: linear-gradient(145deg, #6594ff, #356df3); font-weight: 700; font-size: 16px; }
        .profile-name { display: block; font-size: 14px; font-weight: 700; } .profile-role { display: block; color: var(--muted); font-size: 11px; margin-top: 2px; }

        /* Page Heading */
        .page-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; margin-bottom: 22px; }
        .eyebrow { color: var(--primary); font-size: 11px; font-weight: 800; letter-spacing: 1.25px; text-transform: uppercase; margin-bottom: 7px; }
        h1 { font-family: 'Plus Jakarta Sans', sans-serif; color: var(--ink); font-size: 28px; font-weight: 800; letter-spacing: -.8px; margin: 0; }
        .page-heading p { color: var(--muted); margin: 6px 0 0; font-size: 14px; }

        /* Cards & UI */
        .panel { background: var(--surface); border: 1px solid var(--line); border-radius: 16px; box-shadow: var(--shadow); padding: 24px; margin-bottom: 24px; }
        .panel-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; border-bottom: 1px solid var(--line); padding-bottom: 15px; }
        .panel-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 16px; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 8px; }
        .panel-title i { color: var(--primary); }

        .profile-img-large { width: 140px; height: 140px; border-radius: 50%; object-fit: cover; border: 4px solid #fff; box-shadow: 0 8px 20px rgba(0,0,0,0.08); margin: 0 auto 20px; display: block; }
        .profile-initials-large { width: 140px; height: 140px; border-radius: 50%; background: var(--primary-soft); color: var(--primary); display: grid; place-items: center; font-size: 40px; font-weight: 800; margin: 0 auto 20px; border: 4px solid #fff; box-shadow: 0 8px 20px rgba(0,0,0,0.08); }
        
        .form-label { font-weight: 600; font-size: 13px; color: #435168; margin-bottom: 8px; }
        .form-control { border: 1px solid var(--line); padding: 12px 15px; border-radius: 10px; font-size: 14px; background: #fbfcfe; }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(53,109,243,0.1); background: #fff; }
        .btn-primary { background: var(--primary); border: none; padding: 12px 24px; border-radius: 10px; font-weight: 600; font-size: 14px; transition: 0.2s; color: white; }
        .btn-primary:hover { background: #2859d9; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(53,109,243,0.3); color: white; }
        .btn-success { background: var(--green); border: none; padding: 12px 24px; border-radius: 10px; font-weight: 600; font-size: 14px; transition: 0.2s; color: white; }
        .btn-success:hover { background: #059669; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(16,185,129,0.3); color: white; }

        .info-list p { margin-bottom: 12px; font-size: 14px; display: flex; justify-content: space-between; border-bottom: 1px dashed var(--line); padding-bottom: 8px; }
        .info-list p:last-child { border-bottom: none; }
        .info-list strong { color: var(--muted); font-weight: 600; }
        .info-list span { font-weight: 600; color: var(--ink); text-align: right; }
        .signature-preview { max-height: 80px; border: 1px dashed var(--line); padding: 10px; border-radius: 8px; background: #f8fafc; }

        /* Notifications */
        .notification-item { display: flex; align-items: flex-start; gap: 12px; padding: 14px; border: 1px solid #edf0f5; border-radius: 10px; background: #fbfcfe; cursor: pointer; transition: .18s; }
        .notification-item:hover { border-color: #d8e4ff; background: #f7faff; }
        .notification-item.unread { background: #f4f8ff; border-color: #e2ebff; }
        .notification-icon { width: 34px; height: 34px; flex: 0 0 34px; display: grid; place-items: center; border-radius: 9px; color: var(--primary); background: #eaf1ff; font-size: 14px; }
        .notification-title { color: #344258; font-size: 13px; font-weight: 700; }
        .notification-message { color: #8591a1; font-size: 12px; margin-top: 4px; line-height: 1.45; }
        .notification-time { margin-left: auto; color: #9aa4b2; font-size: 10px; white-space: nowrap; }

        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 0 20px 20px; }
            .mobile-menu { display: inline-grid; place-items: center; }
            .profile-meta { display: none; }
            .search-wrap { display: none; }
        }
    </style>
</head>
<body>

    <div class="sidebar-overlay" id="mobileOverlay" onclick="toggleSidebar()"></div>

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <a href="<?php echo $dashboardUrl; ?>" class="brand">
            <span class="brand-mark"><i class="fa-solid fa-people-group"></i></span>
            <span><span class="brand-name d-block">KormoShathi</span><span class="brand-sub d-block">HR MANAGEMENT SYSTEM</span></span>
        </a>
        
        <?php if ($is_hr): ?>
            <!-- HR Sidebar Links -->
            <div class="nav-caption">Workspace</div>
            <ul class="side-nav">
                <li><a href="hr-dashboard.php" class="side-link"><i class="fa-solid fa-table-cells-large"></i> Dashboard</a></li>
                <li><a href="modules/chat.php" class="side-link"><i class="fa-regular fa-comments"></i> Messages</a></li>
            </ul>
            <div class="nav-caption">HR Operations</div>
            <ul class="side-nav">
                <li><a href="modules/add-employee.php" class="side-link"><i class="fa-solid fa-user-plus"></i> Add Employee</a></li>
                <li><a href="modules/employees.php" class="side-link"><i class="fa-solid fa-users"></i> Employees</a></li>
                <li><a href="modules/attendance.php" class="side-link"><i class="fa-solid fa-fingerprint"></i> Attendance</a></li>
                <li><a href="modules/leave.php" class="side-link"><i class="fa-regular fa-calendar-check"></i> Leave Management</a></li>
                <li><a href="modules/payroll.php" class="side-link"><i class="fa-solid fa-wallet"></i> Payroll</a></li>
                <li><a href="modules/payroll-config.php" class="side-link"><i class="fa-solid fa-sliders"></i> Payroll Configuration</a></li>
                <li><a href="modules/hr-holidays.php" class="side-link"><i class="fa-solid fa-umbrella-beach"></i> Manage Holidays</a></li>
            </ul>
            <div class="nav-caption">Recruitment</div>
            <ul class="side-nav">
                <li><a href="modules/ai-screening-form.php" class="side-link"><i class="fa-solid fa-robot"></i> AI Screening</a></li>
                <li><a href="modules/hr-submissions.php" class="side-link"><i class="fa-solid fa-file-circle-check"></i> Submissions</a></li>
            </ul>
            <div class="nav-caption">People & Insights</div>
            <ul class="side-nav">
                <li><a href="modules/performance.php" class="side-link"><i class="fa-solid fa-chart-line"></i> Performance</a></li>
                <li><a href="modules/reports.php" class="side-link"><i class="fa-solid fa-chart-pie"></i> Reports</a></li>
                <li><a href="modules/notice-board.php" class="side-link"><i class="fa-solid fa-bullhorn"></i> Notice Board</a></li>
                <li><a href="modules/employee-id-card-list.php" class="side-link"><i class="fa-regular fa-id-card"></i> Employee ID Cards</a></li>
            </ul>
            <div class="nav-caption">System</div>
            <ul class="side-nav">
                <li><a href="modules/hr-settings.php" class="side-link"><i class="fa-solid fa-gear"></i> Settings</a></li>
            </ul>
        <?php else: ?>
            <!-- Employee Sidebar Links (Matched exactly with employee-dashboard.php) -->
            <div class="nav-caption">Workspace</div>
            <ul class="side-nav">
                <li><a href="employee-dashboard.php" class="side-link"><i class="fa-solid fa-table-cells-large"></i> Dashboard</a></li>
                <li><a href="modules/chat.php" class="side-link"><i class="fa-regular fa-comments"></i> Messages</a></li>
                <li><a href="modules/my-attendance.php" class="side-link"><i class="fa-solid fa-fingerprint"></i> My Attendance</a></li>
                <li><a href="modules/leave.php" class="side-link"><i class="fa-regular fa-calendar-check"></i> Leave Management</a></li>
                <li><a href="modules/submit-work.php" class="side-link"><i class="fa-solid fa-cloud-arrow-up"></i> Submit Work</a></li>
            </ul>

            <div class="nav-caption">My Workspace</div>
            <ul class="side-nav">
                <li><a href="modules/my-payroll.php" class="side-link"><i class="fa-solid fa-wallet"></i> My Salary & Payslip</a></li>
                <li><a href="modules/tasks.php" class="side-link"><i class="fa-solid fa-list-check"></i> My Tasks</a></li>
                <li><a href="modules/performance.php" class="side-link"><i class="fa-solid fa-chart-line"></i> Performance</a></li>
                <li><a href="modules/employee-settings.php" class="side-link"><i class="fa-solid fa-gear"></i> Settings</a></li>
                <li><a href="profile.php" class="side-link active"><i class="fa-solid fa-user"></i> My Profile</a></li>
            </ul>

            <div class="nav-caption">Company</div>
            <ul class="side-nav">
                <li><a href="modules/notice-board.php" class="side-link"><i class="fa-solid fa-bullhorn"></i> Notice & Events</a></li>
                <li><a href="modules/documents.php" class="side-link"><i class="fa-regular fa-file-pdf"></i> My Documents</a></li>
                <li><a href="modules/holidays.php" class="side-link"><i class="fa-solid fa-umbrella-beach"></i> Holiday Calendar</a></li>
            </ul>
        <?php endif; ?>

        <div class="sidebar-bottom">
            <ul class="side-nav">
                <li><a href="logout.php" class="side-link text-danger"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li>
            </ul>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <header class="topbar">
            <?php if ($is_hr): ?>
                <!-- HR Topbar -->
                <div class="topbar-left">
                    <button class="mobile-menu" type="button" onclick="toggleSidebar()" aria-label="Open menu"><i class="fa-solid fa-bars"></i></button>
                    <h4 class="fw-bold m-0 d-flex align-items-center gap-2 me-3" style="font-family: 'Plus Jakarta Sans', sans-serif;"><i class="fa-solid fa-user text-primary"></i> My Profile</h4>
                    <div class="search-wrap">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="search" placeholder="Search employees, modules..." aria-label="Search">
                    </div>
                </div>
                <div class="topbar-right">
                    <button class="top-icon" type="button" onclick="openNotifications()" title="Notifications" aria-label="Notifications">
                        <i class="fa-regular fa-bell"></i>
                        <span class="dot" id="notificationDot" style="display:none"></span>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationBadge" style="display:none;font-size:10px">0</span>
                    </button>
                    <span class="top-divider"></span>
                    <button class="profile-button" type="button" aria-label="HR profile">
                        <?php if ($profileImage): ?>
                            <img src="<?php echo e($profileImage); ?>" class="avatar" alt="Profile">
                        <?php else: ?>
                            <span class="avatar"><?php echo e($initials); ?></span>
                        <?php endif; ?>
                        <span class="profile-meta">
                            <span class="profile-name"><?php echo e($fullName); ?></span>
                            <span class="profile-role">HR Manager</span>
                        </span>
                        <i class="fa-solid fa-chevron-down ms-1" style="font-size:11px;color:#98a3b2"></i>
                    </button>
                </div>
            <?php else: ?>
                <!-- Employee Topbar (Matched exactly with employee-dashboard.php) -->
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-light d-lg-none" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
                    <h4 class="fw-bold m-0 d-flex align-items-center gap-2" style="font-family: 'Plus Jakarta Sans', sans-serif;"><i class="fa-solid fa-user text-primary"></i> My Profile</h4>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <button class="top-icon" onclick="openNotifications(event)">
                        <i class="fa-regular fa-bell"></i>
                        <span class="notification-badge" id="noticeBadge" style="display:none;">
                            0
                        </span>
                    </button>
                    
                    <div class="d-flex align-items-center gap-2 ps-2">
                        <?php if ($profileImage): ?>
                            <img src="<?php echo e($profileImage); ?>" class="avatar" alt="Profile">
                        <?php else: ?>
                            <div class="avatar"><?php echo e($initials); ?></div>
                        <?php endif; ?>
                        <div class="d-none d-md-block">
                            <span class="profile-name"><?php echo e($user['first_name']); ?></span>
                            <span class="profile-role"><?php echo e($position); ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </header>

        <section class="page-heading">
            <div>
                <div class="eyebrow">Account Information</div>
                <h1>My Profile</h1>
                <p class="text-muted mb-0">Manage your personal details and profile picture.</p>
            </div>
        </section>

        <div class="row g-4">
            <!-- Left Column: Profile Picture & Signature -->
            <div class="col-lg-4">
                <!-- Profile Picture Card -->
                <div class="panel text-center">
                    <div class="panel-header justify-content-center">
                        <h5 class="panel-title"><i class="fa-solid fa-camera"></i> Profile Picture</h5>
                    </div>
                    
                    <?php if ($profileImage): ?>
                        <img src="<?php echo e($profileImage); ?>" class="profile-img-large" id="profileImage">
                    <?php else: ?>
                        <div class="profile-initials-large" id="profileImage"><?php echo e($initials); ?></div>
                    <?php endif; ?>
                    
                    <form id="profilePicForm" enctype="multipart/form-data" class="mt-4">
                        <div class="mb-3">
                            <input type="file" class="form-control form-control-sm" name="profile_picture" accept="image/*" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fa-solid fa-upload me-2"></i>Update Picture
                        </button>
                    </form>
                </div>

                <!-- Signature Card (Only for HR) -->
                <?php if ($is_hr): ?>
                <div class="panel">
                    <div class="panel-header justify-content-center">
                        <h5 class="panel-title"><i class="fa-solid fa-signature text-success"></i> HR Signature</h5>
                    </div>
                    <form id="signatureForm" enctype="multipart/form-data">
                        <div class="mb-3 text-center">
                            <?php if(file_exists(__DIR__ . '/uploads/signature.png')): ?>
                                <img src="uploads/signature.png" class="signature-preview mb-2" alt="Current Signature">
                                <p class="text-muted small">Current Signature</p>
                            <?php else: ?>
                                <div class="p-3 bg-light rounded text-muted small mb-2">No signature uploaded</div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <input type="file" class="form-control form-control-sm" name="signature" accept="image/png,image/jpg,image/jpeg" required>
                            <small class="text-muted d-block mt-1" style="font-size:11px;">Upload signature (PNG/JPG, transparent preferred)</small>
                        </div>
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fa-solid fa-upload me-2"></i>Upload Signature
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right Column: Personal Information -->
            <div class="col-lg-8">
                <div class="panel h-100">
                    <div class="panel-header">
                        <h5 class="panel-title"><i class="fa-solid fa-address-card"></i> Personal Information</h5>
                    </div>
                    
                    <div class="info-list mt-3">
                        <p><strong>Full Name</strong> <span><?php echo e($user['first_name'] . ' ' . $user['last_name']); ?></span></p>
                        <p><strong>Employee ID</strong> <span class="badge bg-primary bg-opacity-10 text-primary border border-primary px-3"><?php echo e($user['employee_id']); ?></span></p>
                        <p><strong>Email Address</strong> <span><?php echo e($user['email']); ?></span></p>
                        <p><strong>Phone Number</strong> <span><?php echo e($user['phone'] ?? 'N/A'); ?></span></p>
                        <p><strong>Department</strong> <span><?php echo e($user['department'] ?? 'N/A'); ?></span></p>
                        <p><strong>Position / Designation</strong> <span><?php echo e($user['position'] ?? 'N/A'); ?></span></p>
                        <p><strong>Blood Group</strong> <span><?php echo e($user['blood_group'] ?? 'N/A'); ?></span></p>
                        <p><strong>Account Role</strong> <span class="text-primary"><?php echo ucfirst($user['user_type']); ?></span></p>
                        <p><strong>Joining Date</strong> <span><?php echo date('d M Y', strtotime($user['join_date'] ?? 'now')); ?></span></p>
                    </div>

                    <div class="alert alert-info mt-4 border-0" style="background:#f0f7ff; color:#1e3a8a; font-size:13px;">
                        <i class="fa-solid fa-circle-info me-2"></i> Some information like Employee ID, Department, and Position are controlled by HR. Please contact HR if you need to update them.
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Notification Modal -->
    <div class="modal fade" id="notificationModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content" style="border:none;border-radius:16px;">
                <div class="modal-header bg-light border-bottom-0">
                    <h5 class="modal-title fw-bold"><i class="fa-regular fa-bell text-primary me-2"></i> Notifications</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="notificationList" style="background:#f8fafc;">
                    <div class="text-center py-4 text-muted">Loading...</div>
                </div>
                <div class="modal-footer border-top-0 bg-white">
                    <button class="btn btn-primary btn-sm rounded-pill px-3 fw-bold" onclick="markAllAsReadAndClose()">
                        <i class="fa-solid fa-check-double me-1"></i> Mark All as Read
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let notificationModal = null;
        function toggleSidebar() { document.getElementById('sidebar').classList.toggle('active'); document.getElementById('mobileOverlay').classList.toggle('active'); }

        // ==========================================
        // 🟢 Real-time Notification Logic
        // ==========================================
        async function loadNotificationCount() {
            try {
                const response = await fetch('api/notifications.php?count=1');
                const data = await response.json();
                const unread = Number(data.unread) || 0;

                // HR UI Update
                const hrBadge = document.getElementById('notificationBadge');
                const hrDot = document.getElementById('notificationDot');
                if (hrBadge && hrDot) {
                    if (unread > 0) {
                        hrBadge.style.display = 'inline-block'; 
                        hrBadge.textContent = unread; 
                        hrDot.style.display = 'block';
                    } else {
                        hrBadge.style.display = 'none'; 
                        hrDot.style.display = 'none';
                    }
                }

                // Employee UI Update
                const empBadge = document.getElementById('noticeBadge');
                if (empBadge) {
                    empBadge.textContent = unread;
                    empBadge.style.display = unread > 0 ? 'flex' : 'none';
                }
            } catch (error) {}
        }

        async function openNotifications() {
            if (!notificationModal) notificationModal = new bootstrap.Modal(document.getElementById('notificationModal'));
            await loadNotifications(); notificationModal.show();
        }

        async function loadNotifications() {
            const container = document.getElementById('notificationList');
            container.innerHTML = `<div class="text-center py-4"><i class="fa-solid fa-spinner fa-spin"></i></div>`;
            try {
                const response = await fetch('api/notifications.php');
                const result = await response.json();
                const notifs = result.data || [];

                if (notifs.length === 0) {
                    container.innerHTML = `<div class="text-center py-5 text-muted"><i class="fa-regular fa-bell-slash fs-1 mb-3"></i><h6 class="fw-bold">All caught up!</h6></div>`;
                    return;
                }

                container.innerHTML = notifs.map(n => {
                    const unread = Number(n.is_read) === 0;
                    const icon = n.type === 'success' ? 'fa-circle-check text-success' : (n.type === 'danger' ? 'fa-circle-exclamation text-danger' : 'fa-circle-info text-primary');
                    return `<div class="notification-item ${unread ? 'unread' : ''} mb-2" onclick="markAsRead(${n.id})" style="padding:12px; border:1px solid ${unread ? '#bfdbfe' : '#e2e8f0'}; border-radius:8px; background:${unread ? '#eff6ff' : '#fff'}; cursor:pointer;"><div class="d-flex align-items-start gap-2"><i class="fa-solid ${icon} mt-1"></i><div><div class="fw-bold text-dark" style="font-size:13px;">${escapeHTML(n.title)}</div><div class="text-muted" style="font-size:12px;">${escapeHTML(n.message)}</div><div class="text-secondary" style="font-size:10px; margin-top:4px;">${formatTime(n.created_at)}</div></div></div></div>`;
                }).join('');
            } catch (error) { container.innerHTML = '<p class="text-danger text-center">Error loading.</p>'; }
        }

        async function markAsRead(id) {
            try {
                await fetch('api/notifications.php', { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }) });
                await loadNotifications(); await loadNotificationCount();
            } catch (error) {}
        }

        async function markAllAsReadAndClose() {
            try {
                await fetch('api/notifications.php', { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: 0 }) });
                await loadNotifications(); await loadNotificationCount(); 
                if (notificationModal) notificationModal.hide();
            } catch (error) {}
        }

        function escapeHTML(str) { return String(str || '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
        function formatTime(dateString) { if(!dateString) return ''; return new Date(dateString).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }); }


        // ==========================================
        // Profile Upload Logic
        // ==========================================
        document.getElementById('profilePicForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Uploading...';
            btn.disabled = true;
            
            try {
                const response = await fetch('api/upload-profile.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    const img = document.getElementById('profileImage');
                    if (img.tagName === 'IMG') {
                        img.src = result.image_path + '?t=' + new Date().getTime();
                    } else {
                        img.outerHTML = `<img src="${result.image_path}?t=${new Date().getTime()}" class="profile-img-large" id="profileImage">`;
                    }
                    alert('✅ Profile picture updated successfully!');
                } else {
                    alert('✗ Error: ' + result.message);
                }
            } catch(error) {
                alert('Connection error! Please try again.');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });

        // ==========================================
        // Signature Upload Logic (Only for HR)
        // ==========================================
        document.getElementById('signatureForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Uploading...';
            btn.disabled = true;
            
            try {
                const response = await fetch('api/upload-signature.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                alert(result.message);
                if(result.success) location.reload();
            } catch(e) {
                alert('Error uploading signature');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            loadNotificationCount();
            setInterval(loadNotificationCount, 5000);
        });
    </script>
</body>
</html>
