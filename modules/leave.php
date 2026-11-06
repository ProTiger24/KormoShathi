<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Fetch user info for topbar
$stmt = $db->prepare("SELECT first_name, last_name, profile_picture, position FROM users WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$firstName = $_SESSION['first_name'] ?? ($user['first_name'] ?? 'User');
$lastName = $_SESSION['last_name'] ?? ($user['last_name'] ?? '');
$fullName = trim($firstName . ' ' . $lastName);
$initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
$pic = $user['profile_picture'] ?? '';
$position = $user['position'] ?? 'Employee';
$dashboardUrl = ($user_type === 'hr') ? '../hr-dashboard.php' : '../employee-dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leave Management | KormoShathi</title>

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
            --green: #10b981;
            --orange: #f59e0b;
            --red: #ef4444;
            --purple: #8b5cf6;
            --sidebar-width: 270px;
            --shadow: 0 8px 26px rgba(26, 43, 70, .045);
        }

        * { box-sizing: border-box; }
        body { margin: 0; background: var(--page); color: var(--ink); font-family: 'DM Sans', sans-serif; font-size: 15px; overflow-x: hidden; }
        a { text-decoration: none; }
        button, input { font: inherit; }

        /* ================= SIDEBAR ================= */
        .sidebar { width: var(--sidebar-width); position: fixed; inset: 0 auto 0 0; z-index: 1040; display: flex; flex-direction: column; color: #d8e2f0; background: linear-gradient(180deg, var(--navy) 0%, #102037 100%); padding: 23px 16px 18px; overflow-y: auto; transition: transform .25s ease; }
        .brand { display: flex; align-items: center; gap: 11px; padding: 0 9px 23px; border-bottom: 1px solid rgba(255,255,255,.09); margin-bottom: 20px; }
        .brand-mark { width: 44px; height: 44px; display: grid; place-items: center; color: #fff; background: linear-gradient(145deg, #4e8bff, #2860dc); border-radius: 13px; font-size: 20px; box-shadow: 0 7px 18px rgba(53,109,243,.28); }
        .brand-name { color: #fff; font: 800 19px 'Plus Jakarta Sans', sans-serif; letter-spacing: -.5px; line-height: 1.15; }
        .brand-sub { color: #91a4bd; font-size: 11px; margin-top: 4px; letter-spacing: .55px; }
        .nav-caption { color: #71859f; font-size: 11px; font-weight: 700; letter-spacing: 1.15px; text-transform: uppercase; padding: 13px 12px 8px; }
        .side-nav { list-style: none; padding: 0; margin: 0; }
        .side-nav li { margin: 3px 0; }
        .side-link { display: flex; align-items: center; gap: 13px; min-height: 46px; padding: 10px 13px; border-radius: 9px; color: #aebdd0; font-size: 14px; font-weight: 500; transition: .18s ease; }
        .side-link i { width: 20px; text-align: center; font-size: 15px; }
        .side-link:hover { color: #fff; background: rgba(255,255,255,.075); }
        .side-link.active { color: #fff; background: linear-gradient(100deg, #3978f5, #2e66e6); box-shadow: 0 7px 18px rgba(31,91,218,.23); }
        .sidebar-bottom { margin-top: auto; padding-top: 16px; }
        .sidebar-help { margin: 13px 4px 15px; padding: 14px; background: rgba(255,255,255,.065); border: 1px solid rgba(255,255,255,.07); border-radius: 12px; }
        .sidebar-help .help-icon { width: 32px; height: 32px; display: grid; place-items: center; background: rgba(83,139,255,.2); color: #8eb5ff; border-radius: 9px; margin-bottom: 9px; }
        .sidebar-help strong { display: block; color: #fff; font-size: 13px; }
        .sidebar-help small { color: #91a4bd; font-size: 11px; display: block; margin: 4px 0 9px; }
        .sidebar-help a { color: #9fc0ff; font-size: 12px; font-weight: 700; }

        /* ================= MAIN CONTENT ================= */
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; padding: 0 30px 30px; transition: margin 0.3s ease; }
        
        /* ================= TOPBAR ================= */
        .topbar { min-height: 78px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--line); margin-bottom: 27px; gap: 20px; }
        .topbar-left, .topbar-right { display: flex; align-items: center; gap: 14px; }
        .mobile-menu { display: none; width: 42px; height: 42px; border: 1px solid var(--line); background: #fff; color: var(--ink); border-radius: 10px; }
        .search-wrap { position: relative; width: min(370px, 42vw); }
        .search-wrap i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9aa6b6; }
        .search-wrap input { width: 100%; height: 44px; padding: 0 15px 0 41px; background: #fff; border: 1px solid var(--line); border-radius: 10px; outline: none; color: var(--ink); font-size: 14px; }
        .top-icon { position: relative; display: grid; place-items: center; width: 42px; height: 42px; border: 1px solid var(--line); background: #fff; color: #637187; border-radius: 10px; }
        .top-icon .dot { position: absolute; width: 8px; height: 8px; border-radius: 50%; background: #ed6470; right: 8px; top: 7px; border: 1px solid #fff; }
        /* Employee Badge Style */
        .notification-badge { position: absolute; top: -5px; right: -5px; background: var(--red); color: white; font-size: 10px; font-weight: 700; border-radius: 50%; min-width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; border: 2px solid white; }
        
        .top-divider { height: 34px; border-left: 1px solid var(--line); margin: 0 2px; }
        .profile-button { display: flex; align-items: center; gap: 10px; border: 0; background: transparent; text-align: left; color: var(--ink); padding: 2px; }
        .avatar { width: 42px; height: 42px; flex: 0 0 42px; border-radius: 50%; object-fit: cover; display: flex; align-items: center; justify-content: center; color: #fff; background: linear-gradient(145deg, #6594ff, #356df3); font-weight: 700; font-size: 16px; }
        .profile-name { display: block; font-size: 14px; font-weight: 700; }
        .profile-role { display: block; color: var(--muted); font-size: 11px; margin-top: 2px; }

        /* ================= PAGE HEADING ================= */
        .page-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; margin-bottom: 22px; }
        .eyebrow { color: var(--primary); font-size: 11px; font-weight: 800; letter-spacing: 1.25px; text-transform: uppercase; margin-bottom: 7px; }
        h1 { font-family: 'Plus Jakarta Sans', sans-serif; color: var(--ink); font-size: 28px; font-weight: 800; letter-spacing: -.8px; margin: 0; }
        .page-heading p { color: var(--muted); margin: 6px 0 0; font-size: 14px; }

        /* ================= PANELS & STATS ================= */
        .panel { background: var(--surface); border: 1px solid var(--line); border-radius: 13px; box-shadow: var(--shadow); padding: 24px; margin-bottom: 24px; }
        .panel-header { border-bottom: 1px solid var(--line); padding-bottom: 15px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;}
        .panel-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 16px; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 8px; }
        .panel-title i { color: var(--primary); }

        .balance-box { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 50px; font-weight: 800; color: var(--primary); line-height: 1; margin-bottom: 5px; }
        .progress { height: 8px; border-radius: 10px; background: #edf1f6; margin-top: 15px; }

        /* Form Styles */
        .form-label { font-weight: 600; font-size: 14px; color: #435168; margin-bottom: 8px; }
        .form-control, .form-select { border: 1px solid var(--line); padding: 12px 15px; border-radius: 8px; font-size: 14px; background-color: #fbfcfe; transition: 0.2s; }
        .form-control:focus, .form-select:focus { border-color: var(--primary); background-color: #fff; box-shadow: 0 0 0 3px rgba(53,109,243,0.1); }
        .btn-submit { background: var(--primary); border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; transition: 0.2s; }
        .btn-submit:hover { background: #2859d9; transform: translateY(-2px); }

        /* Leave Item */
        .leave-item { border: 1px solid var(--line); border-radius: 12px; padding: 20px; margin-bottom: 15px; background: #fff; transition: 0.2s; border-left: 4px solid var(--primary); }
        .leave-item:hover { box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .leave-item.pending { border-left-color: var(--orange); }
        .leave-item.approved { border-left-color: var(--green); }
        .leave-item.rejected { border-left-color: var(--red); }

        /* Badges */
        .badge-type { background: #edf3ff; color: var(--primary); border: 1px solid #cbdcff; padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; }
        .badge-status { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; }
        .status-pending { background: #fff7ed; color: var(--orange); border: 1px solid #fed7aa; }
        .status-approved { background: #ecfdf5; color: var(--green); border: 1px solid #a7f3d0; }
        .status-rejected { background: #fef2f2; color: var(--red); border: 1px solid #fecaca; }

        .rejection-alert { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; padding: 10px 15px; border-radius: 8px; font-size: 13px; margin-top: 10px; display: flex; align-items: flex-start; gap: 8px; }

        /* Action Buttons */
        .btn-approve { background: #ecfdf5; color: var(--green); border: 1px solid #a7f3d0; font-weight: 600; font-size: 13px; padding: 6px 15px; border-radius: 6px; transition: 0.2s; }
        .btn-approve:hover { background: var(--green); color: #fff; }
        .btn-reject { background: #fef2f2; color: var(--red); border: 1px solid #fecaca; font-weight: 600; font-size: 13px; padding: 6px 15px; border-radius: 6px; transition: 0.2s; }
        .btn-reject:hover { background: var(--red); color: #fff; }

        /* Pagination */
        .pagination { margin-bottom: 0; } 
        .page-link { border: none; color: var(--muted); font-weight: 600; border-radius: 8px !important; margin: 0 3px; padding: 8px 14px; transition: 0.2s; cursor: pointer; } 
        .page-link:hover { background: var(--primary-soft); color: var(--primary); } 
        .page-item.active .page-link { background: var(--primary); color: white; box-shadow: 0 4px 10px rgba(53,109,243,0.3); } 
        .page-item.disabled .page-link { background: transparent; color: #cbd5e1; cursor: not-allowed; }

        /* Notifications */
        .notification-item { display: flex; align-items: flex-start; gap: 12px; padding: 14px; border: 1px solid #edf0f5; border-radius: 10px; background: #fbfcfe; cursor: pointer; transition: .18s; }
        .notification-item:hover { border-color: #d8e4ff; background: #f7faff; }
        .notification-item.unread { background: #f4f8ff; border-color: #e2ebff; }
        .notification-icon { width: 34px; height: 34px; flex: 0 0 34px; display: grid; place-items: center; border-radius: 9px; color: var(--primary); background: #eaf1ff; font-size: 14px; }
        .notification-title { color: #344258; font-size: 13px; font-weight: 700; }
        .notification-message { color: #8591a1; font-size: 12px; margin-top: 4px; line-height: 1.45; }
        .notification-time { margin-left: auto; color: #9aa4b2; font-size: 10px; white-space: nowrap; }

        /* Mobile */
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 1030; }
        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 0 20px 20px; }
            .sidebar-overlay.active { display: block; }
            .mobile-menu { display: inline-grid; place-items: center; }
            .search-wrap { display: none; }
            .profile-meta { display: none; }
        }
    </style>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="<?php echo $dashboardUrl; ?>">
            <span class="brand-mark"><i class="fa-solid fa-people-group"></i></span>
            <span>
                <span class="brand-name d-block">KormoShathi</span>
                <span class="brand-sub d-block">HR MANAGEMENT SYSTEM</span>
            </span>
        </a>

        <?php if($user_type == 'hr'): ?>
            <!-- HR Sidebar Links -->
            <div class="nav-caption">Workspace</div>
            <ul class="side-nav">
                <li><a class="side-link" href="../hr-dashboard.php"><i class="fa-solid fa-table-cells-large"></i> Dashboard</a></li>
                <li><a class="side-link" href="chat.php"><i class="fa-regular fa-comments"></i> Messages</a></li>
            </ul>
            <div class="nav-caption">HR Operations</div>
            <ul class="side-nav">
                <li><a class="side-link" href="add-employee.php"><i class="fa-solid fa-user-plus"></i> Add Employee</a></li>
                <li><a class="side-link" href="employees.php"><i class="fa-solid fa-users"></i> Employees</a></li>
                <li><a class="side-link" href="attendance.php"><i class="fa-solid fa-fingerprint"></i> Attendance</a></li>
                <li><a class="side-link active" href="leave.php"><i class="fa-regular fa-calendar-check"></i> Leave Management</a></li>
                <li><a class="side-link" href="payroll.php"><i class="fa-solid fa-wallet"></i> Payroll</a></li>
                <li><a class="side-link" href="payroll-config.php"><i class="fa-solid fa-sliders"></i> Payroll Configuration</a></li>
                <li><a class="side-link" href="hr-holidays.php"><i class="fa-solid fa-umbrella-beach"></i> Manage Holidays</a></li>
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
            <div class="nav-caption">System</div>
            <ul class="side-nav">
                <li><a class="side-link" href="hr-settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
            </ul>
        <?php else: ?>
            <!-- Employee Sidebar Links (Matched exactly with employee-dashboard.php) -->
            <div class="nav-caption">Workspace</div>
            <ul class="side-nav">
                <li><a href="../employee-dashboard.php" class="side-link"><i class="fa-solid fa-table-cells-large"></i> Dashboard</a></li>
                <li><a href="chat.php" class="side-link"><i class="fa-regular fa-comments"></i> Messages</a></li>
                <li><a href="my-attendance.php" class="side-link"><i class="fa-solid fa-fingerprint"></i> My Attendance</a></li>
                <li><a href="leave.php" class="side-link active"><i class="fa-regular fa-calendar-check"></i> Leave Management</a></li>
                <li><a href="submit-work.php" class="side-link"><i class="fa-solid fa-cloud-arrow-up"></i> Submit Work</a></li>
            </ul>

            <div class="nav-caption">My Workspace</div>
            <ul class="side-nav">
                <li><a href="my-payroll.php" class="side-link"><i class="fa-solid fa-wallet"></i> My Salary & Payslip</a></li>
                <li><a href="tasks.php" class="side-link"><i class="fa-solid fa-list-check"></i> My Tasks</a></li>
                <li><a href="performance.php" class="side-link"><i class="fa-solid fa-chart-line"></i> Performance</a></li>
                <li><a href="employee-settings.php" class="side-link"><i class="fa-solid fa-gear"></i> Settings</a></li>
                <li><a href="../profile.php" class="side-link"><i class="fa-solid fa-user"></i> My Profile</a></li>
            </ul>

            <div class="nav-caption">Company</div>
            <ul class="side-nav">
                <li><a href="notice-board.php" class="side-link"><i class="fa-solid fa-bullhorn"></i> Notice & Events</a></li>
                <li><a href="documents.php" class="side-link"><i class="fa-regular fa-file-pdf"></i> My Documents</a></li>
                <li><a href="holidays.php" class="side-link"><i class="fa-solid fa-umbrella-beach"></i> Holiday Calendar</a></li>
            </ul>
        <?php endif; ?>

        <div class="sidebar-bottom">
            <?php if($user_type == 'hr'): ?>
            <div class="sidebar-help">
                <div class="help-icon"><i class="fa-regular fa-circle-question"></i></div>
                <strong>Need a hand?</strong>
                <small>Find help with your HR workspace.</small>
                <a href="notice-board.php">Open notice board <i class="fa-solid fa-arrow-right ms-1"></i></a>
            </div>
            <?php endif; ?>
            <ul class="side-nav">
                <li><a class="side-link" href="../logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li>
            </ul>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <header class="topbar">
            <?php if ($user_type === 'hr'): ?>
                <!-- HR Topbar -->
                <div class="topbar-left">
                    <button class="mobile-menu" type="button" onclick="toggleSidebar()" aria-label="Open menu"><i class="fa-solid fa-bars"></i></button>
                    <h4 class="fw-bold m-0 d-flex align-items-center gap-2 me-3" style="font-family: 'Plus Jakarta Sans', sans-serif;"><i class="fa-regular fa-calendar-check text-primary"></i> Leave Management</h4>
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
                    <button class="profile-button" type="button" aria-label="Profile">
                        <?php if (!empty($pic)): ?>
                            <img src="<?php echo htmlspecialchars(img_url($pic), ENT_QUOTES, 'UTF-8'); ?>" class="avatar" alt="Profile">
                        <?php else: ?>
                            <span class="avatar"><?php echo htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                        <span class="profile-meta">
                            <span class="profile-name"><?php echo htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8'); ?></span>
                            <span class="profile-role">HR Manager</span>
                        </span>
                        <i class="fa-solid fa-chevron-down ms-1" style="font-size:11px;color:#98a3b2"></i>
                    </button>
                </div>
            <?php else: ?>
                <!-- Employee Topbar (Matched exactly with employee-dashboard.php) -->
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-light d-lg-none" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
                    <h4 class="fw-bold m-0 d-flex align-items-center gap-2" style="font-family: 'Plus Jakarta Sans', sans-serif;"><i class="fa-regular fa-calendar-check text-primary"></i> Leave Management</h4>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <button class="top-icon" onclick="openNotifications(event)">
                        <i class="fa-regular fa-bell"></i>
                        <span class="notification-badge" id="noticeBadge" style="display:none;">
                            0
                        </span>
                    </button>
                    
                    <div class="d-flex align-items-center gap-2 ps-2">
                        <?php if ($pic): ?>
                            <img src="<?php echo htmlspecialchars(img_url($pic), ENT_QUOTES, 'UTF-8'); ?>" class="avatar" alt="Profile">
                        <?php else: ?>
                            <div class="avatar"><?php echo htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endif; ?>
                        <div class="d-none d-md-block">
                            <span class="profile-name"><?php echo htmlspecialchars($firstName); ?></span>
                            <span class="profile-role"><?php echo htmlspecialchars($position); ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </header>

        <section class="page-heading">
            <div>
                <div class="eyebrow">Time Off</div>
                <h1><?php echo $user_type == 'hr' ? 'All Leave Requests' : 'My Leaves'; ?></h1>
                <p class="text-muted mb-0"><?php echo $user_type == 'hr' ? 'Manage and review employee leave applications.' : 'Apply for leave and track your application status.'; ?></p>
            </div>
        </section>

        <!-- Employee Balance & Form -->
        <?php if($user_type == 'employee'): ?>
        <div class="row g-4 mb-4">
            <div class="col-lg-4">
                <div class="panel h-100 d-flex flex-column justify-content-center text-center">
                    <h5 class="fw-bold text-muted mb-4" style="font-size: 14px;">AVAILABLE LEAVE BALANCE</h5>
                    <div class="balance-box" id="leaveBalance">0</div>
                    <p class="text-muted fw-medium mb-0">Days Remaining</p>
                    <div class="progress">
                        <div id="balanceProgress" class="progress-bar bg-success" style="width: 100%"></div>
                    </div>
                    <p class="text-muted mt-3 mb-0" style="font-size: 12px;">Total Allocated: 20 days/year</p>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="panel h-100">
                    <div class="panel-header">
                        <h5 class="panel-title"><i class="fa-solid fa-paper-plane"></i> Apply for Leave</h5>
                    </div>
                    <form id="leaveRequestForm">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Leave Type</label>
                                <select class="form-select" name="leave_type" required>
                                    <option value="casual">Casual Leave</option>
                                    <option value="sick">Sick Leave</option>
                                    <option value="earned">Earned Leave</option>
                                    <option value="maternity">Maternity Leave</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Total Days *</label>
                                <input type="number" class="form-control" name="total_days" id="totalDays" required readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Start Date *</label>
                                <input type="date" class="form-control" name="start_date" id="startDate" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">End Date *</label>
                                <input type="date" class="form-control" name="end_date" id="endDate" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Reason *</label>
                                <textarea class="form-control" name="reason" rows="2" required placeholder="Briefly explain the reason for your leave..."></textarea>
                            </div>
                            <div class="col-12 mt-4">
                                <button type="submit" class="btn btn-submit text-white w-100"><i class="fa-solid fa-paper-plane me-2"></i>Submit Application</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Leave Requests List -->
        <div class="panel">
            <div class="panel-header">
                <h5 class="panel-title"><i class="fa-regular fa-list-alt"></i> <?php echo $user_type == 'hr' ? 'Recent Applications' : 'My Application History'; ?></h5>
                <span class="text-muted fw-bold" style="font-size: 13px;" id="leaveCount">0 requests</span>
            </div>
            
            <div id="leaveRequestsList">
                <div class="text-center py-5 text-muted">
                    <i class="fa-solid fa-spinner fa-spin fs-3 mb-3"></i><br> Loading requests...
                </div>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top" id="paginationContainer" style="display: none;">
                <div class="text-muted fw-medium" style="font-size: 13px;" id="paginationInfo"></div>
                <nav><ul class="pagination" id="paginationControls"></ul></nav>
            </div>
        </div>
    </main>

    <!-- Reject Reason Modal -->
    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 13px; border: none;">
                <div class="modal-header bg-light border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold text-danger"><i class="fa-solid fa-triangle-exclamation me-2"></i>Reject Leave</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Please provide a brief reason for rejecting this leave request. This will be visible to the employee.</p>
                    <textarea class="form-control" id="rejectionReason" rows="3" placeholder="Enter rejection reason here..." style="background: #fff;"></textarea>
                    <input type="hidden" id="rejectLeaveId">
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button class="btn btn-light fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-danger fw-bold px-4" onclick="confirmReject()">Confirm Reject</button>
                </div>
            </div>
        </div>
    </div>

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
        let rejectModalInstance = null;

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        }

        // ==========================================
        // 🟢 Real-time Notification Logic
        // ==========================================
        async function loadNotificationCount() {
            try {
                const response = await fetch('../api/notifications.php?count=1');
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
            if (!notificationModal) {
                notificationModal = new bootstrap.Modal(document.getElementById('notificationModal'));
            }
            await loadNotifications();
            notificationModal.show();
        }

        async function loadNotifications() {
            const container = document.getElementById('notificationList');
            container.innerHTML = `<div class="text-center py-4"><i class="fa-solid fa-spinner fa-spin"></i></div>`;
            try {
                const response = await fetch('../api/notifications.php');
                const result = await response.json();
                const notifs = result.data || [];

                if (notifs.length === 0) {
                    container.innerHTML = `<div class="text-center py-5 text-muted"><i class="fa-regular fa-bell-slash fs-1 mb-3"></i><h6 class="fw-bold">All caught up!</h6></div>`;
                    return;
                }

                container.innerHTML = notifs.map(n => {
                    const unread = Number(n.is_read) === 0;
                    const icon = n.type === 'success' ? 'fa-circle-check text-success' : (n.type === 'danger' ? 'fa-circle-exclamation text-danger' : 'fa-circle-info text-primary');
                    return `
                        <div class="notification-item ${unread ? 'unread' : ''} mb-2" onclick="markAsRead(${n.id})" style="padding:12px; border:1px solid ${unread ? '#bfdbfe' : '#e2e8f0'}; border-radius:8px; background:${unread ? '#eff6ff' : '#fff'}; cursor:pointer;">
                            <div class="d-flex align-items-start gap-2">
                                <i class="fa-solid ${icon} mt-1"></i>
                                <div>
                                    <div class="fw-bold text-dark" style="font-size:13px;">${escapeHTML(n.title)}</div>
                                    <div class="text-muted" style="font-size:12px;">${escapeHTML(n.message)}</div>
                                    <div class="text-secondary" style="font-size:10px; margin-top:4px;">${formatTime(n.created_at)}</div>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
            } catch (error) { container.innerHTML = '<p class="text-danger text-center">Error loading.</p>'; }
        }

        async function markAsRead(id) {
            try {
                await fetch('../api/notifications.php', {
                    method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id })
                });
                await loadNotifications(); await loadNotificationCount();
            } catch (error) {}
        }

        async function markAllAsReadAndClose() {
            try {
                await fetch('../api/notifications.php', {
                    method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: 0 })
                });
                await loadNotifications(); await loadNotificationCount(); 
                if (notificationModal) notificationModal.hide();
            } catch (error) {}
        }

        function escapeHTML(str) { return String(str || '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
        function formatTime(dateString) {
            if(!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' });
        }

        // ==========================================
        // Leave Management Logic
        // ==========================================
        document.getElementById('startDate')?.addEventListener('change', calculateDays);
        document.getElementById('endDate')?.addEventListener('change', calculateDays);

        function calculateDays() {
            const start = document.getElementById('startDate').value;
            const end = document.getElementById('endDate').value;
            if (start && end) {
                const startDate = new Date(start);
                const endDate = new Date(end);
                const days = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24)) + 1;
                document.getElementById('totalDays').value = days > 0 ? days : 0;
            }
        }

        async function loadLeaveBalance() {
            try {
                const response = await fetch('../api/leave-balance.php');
                const data = await response.json();
                if (data.success) {
                    const balanceEl = document.getElementById('leaveBalance');
                    if(balanceEl) {
                        balanceEl.innerText = data.balance;
                        const percentage = (data.balance / 20) * 100;
                        const progressEl = document.getElementById('balanceProgress');
                        progressEl.style.width = percentage + '%';
                        if (percentage < 30) progressEl.className = 'progress-bar bg-danger';
                        else if (percentage < 60) progressEl.className = 'progress-bar bg-warning';
                    }
                }
            } catch(e) { console.error(e); }
        }

        let allLeaves = [];
        let currentPage = 1;
        const itemsPerPage = 4; 

        async function loadLeaveRequests() {
            try {
                const response = await fetch('../api/leave-requests.php');
                const data = await response.json();
                
                if (data && data.length > 0) {
                    allLeaves = data;
                    document.getElementById('leaveCount').textContent = allLeaves.length + ' requests';
                    document.getElementById('paginationContainer').style.display = 'flex';
                    renderLeaves(currentPage);
                } else {
                    document.getElementById('leaveCount').textContent = '0 requests';
                    document.getElementById('paginationContainer').style.display = 'none';
                    document.getElementById('leaveRequestsList').innerHTML = `
                        <div class="text-center py-5 text-muted">
                            <i class="fa-regular fa-folder-open fs-2 mb-3 text-secondary"></i>
                            <h6 class="fw-bold">No Leave Requests Found</h6>
                            <p class="mb-0">There are currently no leave applications to display.</p>
                        </div>
                    `;
                }
            } catch(e) { 
                document.getElementById('leaveRequestsList').innerHTML = `<div class="text-center py-4 text-danger">Failed to load leave requests.</div>`;
            }
        }

        function renderLeaves(page) {
            const container = document.getElementById('leaveRequestsList');
            const start = (page - 1) * itemsPerPage;
            const end = start + itemsPerPage;
            const paginatedLeaves = allLeaves.slice(start, end);

            container.innerHTML = paginatedLeaves.map(leave => {
                let actions = '';
                if ('<?php echo $user_type; ?>' == 'hr' && leave.status == 'pending') {
                    actions = `
                        <div class="mt-2 d-flex gap-2">
                            <button class="btn-approve" onclick="approveLeave(${leave.id})"><i class="fa-solid fa-check me-1"></i> Approve</button>
                            <button class="btn-reject" onclick="openRejectModal(${leave.id})"><i class="fa-solid fa-xmark me-1"></i> Reject</button>
                        </div>
                    `;
                }
                
                let rejectionHtml = '';
                if (leave.status == 'rejected' && leave.rejection_reason) {
                    rejectionHtml = `<div class="rejection-alert mt-3"><i class="fa-solid fa-circle-exclamation mt-1"></i><div><strong>Rejection Reason:</strong> ${leave.rejection_reason}</div></div>`;
                }
                
                const employeeName = leave.employee_name || 'You';
                const appliedDate = new Date(leave.applied_on).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
                const startDate = new Date(leave.start_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });
                const endDate = new Date(leave.end_date).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
                
                return `
                    <div class="leave-item ${leave.status}">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge-type">${leave.leave_type.toUpperCase()}</span>
                                    <span class="badge-status status-${leave.status}">${leave.status.toUpperCase()}</span>
                                </div>
                                <h6 class="fw-bold mb-1" style="font-size: 16px;"><i class="fa-regular fa-user text-muted me-1"></i> ${employeeName}</h6>
                                <div class="text-muted fw-medium" style="font-size: 14px;">
                                    <i class="fa-regular fa-calendar me-1"></i> ${startDate} - ${endDate} <span class="mx-2">|</span> <strong class="text-dark">${leave.total_days} Days</strong>
                                </div>
                            </div>
                            <div class="text-end d-flex flex-column align-items-end">
                                <small class="text-muted fw-medium bg-light px-2 py-1 rounded mb-2">Applied: ${appliedDate}</small>
                                ${actions}
                            </div>
                        </div>
                        <div class="mt-3 p-3 bg-light rounded" style="font-size: 14px;"><strong>Reason:</strong> ${leave.reason || 'No reason provided'}</div>
                        ${rejectionHtml}
                    </div>
                `;
            }).join('');

            setupPagination(allLeaves.length, page);
        }

        function setupPagination(totalItems, currentPage) {
            const totalPages = Math.ceil(totalItems / itemsPerPage);
            const paginationControls = document.getElementById('paginationControls');
            const paginationInfo = document.getElementById('paginationInfo');

            const start = (currentPage - 1) * itemsPerPage;
            const end = start + itemsPerPage;
            paginationInfo.textContent = `Showing ${start + 1} to ${Math.min(end, totalItems)} of ${totalItems} entries`;

            paginationControls.innerHTML = '';

            const prevLi = document.createElement('li');
            prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
            prevLi.innerHTML = `<a class="page-link" href="#"><i class="fa-solid fa-chevron-left" style="font-size:12px;"></i></a>`;
            prevLi.addEventListener('click', (e) => { e.preventDefault(); if (currentPage > 1) { renderLeaves(currentPage - 1); } });
            paginationControls.appendChild(prevLi);

            for (let i = 1; i <= totalPages; i++) {
                const li = document.createElement('li');
                li.className = `page-item ${i === currentPage ? 'active' : ''}`;
                li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
                li.addEventListener('click', (e) => { e.preventDefault(); renderLeaves(i); });
                paginationControls.appendChild(li);
            }

            const nextLi = document.createElement('li');
            nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
            nextLi.innerHTML = `<a class="page-link" href="#"><i class="fa-solid fa-chevron-right" style="font-size:12px;"></i></a>`;
            nextLi.addEventListener('click', (e) => { e.preventDefault(); if (currentPage < totalPages) { renderLeaves(currentPage + 1); } });
            paginationControls.appendChild(nextLi);
        }

        document.getElementById('leaveRequestForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const leaveData = Object.fromEntries(formData);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Submitting...';
            submitBtn.disabled = true;
            
            try {
                const response = await fetch('../api/leave-requests.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(leaveData)
                });
                const result = await response.json();
                if (result.success) {
                    alert('✓ ' + result.message);
                    this.reset();
                    loadLeaveBalance();
                    loadLeaveRequests();
                } else {
                    alert('✗ ' + result.message);
                }
            } catch(error) { alert('Error submitting request'); } 
            finally { submitBtn.innerHTML = originalText; submitBtn.disabled = false; }
        });

        async function approveLeave(id) {
            if(confirm('Are you sure you want to approve this leave request?')) {
                try {
                    const response = await fetch('../api/leave-requests.php', {
                        method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: id, status: 'approved' })
                    });
                    const result = await response.json();
                    alert(result.message);
                    loadLeaveRequests();
                } catch(e) { alert('Error'); }
            }
        }

        function openRejectModal(id) {
            document.getElementById('rejectLeaveId').value = id;
            document.getElementById('rejectionReason').value = '';
            if(!rejectModalInstance) {
                rejectModalInstance = new bootstrap.Modal(document.getElementById('rejectModal'));
            }
            rejectModalInstance.show();
        }

        async function confirmReject() {
            const id = document.getElementById('rejectLeaveId').value;
            const reason = document.getElementById('rejectionReason').value.trim();
            
            if (!reason) { alert('Please provide a reason for rejection.'); return; }
            
            try {
                const response = await fetch('../api/leave-requests.php', {
                    method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: id, status: 'rejected', rejection_reason: reason })
                });
                const result = await response.json();
                alert(result.message);
                rejectModalInstance.hide();
                loadLeaveRequests();
            } catch(e) { alert('Error'); }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            <?php if($user_type == 'employee'): ?>loadLeaveBalance();<?php endif; ?>
            loadLeaveRequests();
            loadNotificationCount();
            
            setInterval(() => {
                loadLeaveRequests();
                loadNotificationCount();
            }, 5000);
        });
    </script>
</body>
</html>
