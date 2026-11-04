<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php'); 
    exit;
}

require_once '../config/database.php';
$database = new Database(); $db = $database->getConnection();

$user_type = $_SESSION['user_type'];
$is_hr = ($user_type === 'hr');
$dashboardUrl = $is_hr ? '../hr-dashboard.php' : '../employee-dashboard.php';

$stmt = $db->prepare("SELECT first_name, last_name, profile_picture, position FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]); 
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$firstName = $_SESSION['first_name'] ?? ($user['first_name'] ?? 'User');
$lastName = $_SESSION['last_name'] ?? ($user['last_name'] ?? '');
$fullName = trim($firstName . ' ' . $lastName);
$initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
$pic = $user['profile_picture'] ?? '';
$position = $_SESSION['position'] ?? ($user['position'] ?? 'Employee');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll Management | KormoShathi</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        :root {
            --navy: #14243a; --primary: #356df3; --primary-soft: #edf3ff; --ink: #17263c;
            --muted: #7b8798; --line: #e9edf3; --surface: #ffffff; --page: #f5f7fb;
            --green: #10b981; --orange: #f59e0b; --red: #ef4444; --purple: #8b5cf6;
            --sidebar-width: 270px; --shadow: 0 8px 26px rgba(26, 43, 70, .045);
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
        .side-nav { list-style: none; padding: 0; margin: 0; } .side-nav li { margin: 3px 0; }
        .side-link { display: flex; align-items: center; gap: 13px; min-height: 46px; padding: 10px 13px; border-radius: 9px; color: #aebdd0; font-size: 14px; font-weight: 500; transition: .18s ease; }
        .side-link i { width: 20px; text-align: center; font-size: 15px; } .side-link:hover { color: #fff; background: rgba(255,255,255,.075); }
        .side-link.active { color: #fff; background: linear-gradient(100deg, #3978f5, #2e66e6); box-shadow: 0 7px 18px rgba(31,91,218,.23); }
        .sidebar-bottom { margin-top: auto; padding-top: 16px; }
        .sidebar-help { margin: 13px 4px 15px; padding: 14px; background: rgba(255,255,255,.065); border: 1px solid rgba(255,255,255,.07); border-radius: 12px; }
        .sidebar-help .help-icon { width: 32px; height: 32px; display: grid; place-items: center; background: rgba(83,139,255,.2); color: #8eb5ff; border-radius: 9px; margin-bottom: 9px; }
        .sidebar-help strong { display: block; color: #fff; font-size: 13px; } .sidebar-help small { color: #91a4bd; font-size: 11px; display: block; margin: 4px 0 9px; } .sidebar-help a { color: #9fc0ff; font-size: 12px; font-weight: 700; }

        /* ================= MAIN CONTENT ================= */
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; padding: 0 30px 30px; transition: margin 0.3s ease; }
        
        /* ================= TOPBAR ================= */
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

        /* ================= PAGE HEADING ================= */
        .page-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; margin-bottom: 22px; }
        .eyebrow { color: var(--primary); font-size: 11px; font-weight: 800; letter-spacing: 1.25px; text-transform: uppercase; margin-bottom: 7px; }
        h1 { font-family: 'Plus Jakarta Sans', sans-serif; color: var(--ink); font-size: 28px; font-weight: 800; letter-spacing: -.8px; margin: 0; }
        .page-heading p { color: var(--muted); margin: 6px 0 0; font-size: 14px; }

        /* ================= PANELS & TABLES ================= */
        .panel { background: var(--surface); border: 1px solid var(--line); border-radius: 13px; box-shadow: var(--shadow); padding: 24px; margin-bottom: 24px; }
        .panel-header { border-bottom: 1px solid var(--line); padding-bottom: 15px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .panel-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 16px; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 8px; }
        .panel-title i { color: var(--primary); }

        .form-label { font-weight: 600; font-size: 14px; color: #435168; margin-bottom: 8px; }
        .form-control, .form-select { border: 1px solid var(--line); padding: 12px 15px; border-radius: 8px; font-size: 14px; background-color: #fbfcfe; transition: 0.2s; }
        .form-control:focus, .form-select:focus { border-color: var(--primary); background-color: #fff; box-shadow: 0 0 0 3px rgba(53,109,243,0.1); }
        .btn-primary { background: var(--primary); border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 15px; transition: 0.2s; }
        .btn-primary:hover { background: #2859d9; transform: translateY(-2px); }
        .btn-success { background: var(--green); border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 15px; transition: 0.2s; color: white; }
        .btn-success:hover { background: #059669; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(16,185,129,0.3); }

        .table th { background: #f8fafc; color: var(--muted); font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; padding: 15px 20px; border-bottom: 1px solid var(--line); }
        .table td { vertical-align: middle; padding: 15px 20px; font-size: 14px; border-bottom: 1px solid var(--line); }
        
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-block; text-transform: capitalize; }
        .status-paid { background: #ecfdf5; color: var(--green); border: 1px solid #a7f3d0; }
        .status-processed { background: #edf3ff; color: var(--primary); border: 1px solid #bfdbfe; }
        .status-pending { background: #fff7ed; color: var(--orange); border: 1px solid #fed7aa; }

        .btn-action { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; transition: 0.2s; border: 1px solid var(--line); background: #fff; color: var(--muted); }
        .btn-action:hover { background: var(--primary-soft); color: var(--primary); border-color: var(--primary-soft); }

        /* Pagination */
        .pagination { margin-bottom: 0; } .page-link { border: none; color: var(--muted); font-weight: 600; border-radius: 8px !important; margin: 0 3px; padding: 8px 14px; transition: 0.2s; cursor: pointer; } .page-link:hover { background: var(--primary-soft); color: var(--primary); } .page-item.active .page-link { background: var(--primary); color: white; box-shadow: 0 4px 10px rgba(53,109,243,0.3); } .page-item.disabled .page-link { background: transparent; color: #cbd5e1; cursor: not-allowed; }

        /* Payslip Modal */
        .modal-content { border-radius: 16px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
        .payslip-header { background: linear-gradient(115deg, #356df3, #1d3d70); color: white; padding: 30px 20px; text-align: center; border-radius: 16px 16px 0 0; }
        .payslip-body { padding: 30px; background: #fff; }
        .payslip-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .payslip-row.total { border-bottom: 2px solid var(--primary); font-weight: 800; font-size: 18px; margin-top: 10px; padding-top: 15px; }
        .payslip-label { color: var(--muted); font-weight: 500; }
        .payslip-value { font-weight: 600; color: var(--ink); }
        .payslip-footer { background: #f8fafc; padding: 15px; text-align: center; border-radius: 0 0 16px 16px; border-top: 1px solid var(--line); }

        /* Notification */
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
            .sidebar { transform: translateX(-100%); } .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 0 20px 20px; } .sidebar-overlay.active { display: block; }
            .mobile-menu { display: inline-grid; place-items: center; } .profile-meta { display: none; }
            .search-wrap { display: none; }
        }
    </style>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- ================= SIDEBAR ================= -->
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="<?php echo $dashboardUrl; ?>">
            <span class="brand-mark"><i class="fa-solid fa-people-group"></i></span>
            <span>
                <span class="brand-name d-block">KormoShathi</span>
                <span class="brand-sub d-block">HR MANAGEMENT SYSTEM</span>
            </span>
        </a>

        <?php if($is_hr): ?>
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
                <li><a class="side-link" href="leave.php"><i class="fa-regular fa-calendar-check"></i> Leave Management</a></li>
                <li><a class="side-link active" href="payroll.php"><i class="fa-solid fa-wallet"></i> Payroll</a></li>
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
                <li><a href="leave.php" class="side-link"><i class="fa-regular fa-calendar-check"></i> Leave Management</a></li>
                <li><a href="submit-work.php" class="side-link"><i class="fa-solid fa-cloud-arrow-up"></i> Submit Work</a></li>
            </ul>

            <div class="nav-caption">My Workspace</div>
            <ul class="side-nav">
                <li><a href="my-payroll.php" class="side-link active"><i class="fa-solid fa-wallet"></i> My Salary & Payslip</a></li>
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
            <?php if($is_hr): ?>
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

    <!-- ================= MAIN CONTENT ================= -->
    <main class="main-content">
        <header class="topbar">
            <?php if ($is_hr): ?>
                <!-- HR Topbar -->
                <div class="topbar-left">
                    <button class="mobile-menu" type="button" onclick="toggleSidebar()" aria-label="Open menu"><i class="fa-solid fa-bars"></i></button>
                    <h4 class="fw-bold m-0 d-flex align-items-center gap-2 me-3" style="font-family: 'Plus Jakarta Sans', sans-serif;"><i class="fa-solid fa-wallet text-primary"></i> Payroll Management</h4>
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
                        <?php if (!empty($pic)): ?>
                            <img src="../<?php echo htmlspecialchars($pic, ENT_QUOTES, 'UTF-8'); ?>" class="avatar" alt="Profile">
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
                    <h4 class="fw-bold m-0 d-flex align-items-center gap-2" style="font-family: 'Plus Jakarta Sans', sans-serif;"><i class="fa-solid fa-wallet text-primary"></i> My Salary & Payslips</h4>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <button class="top-icon" onclick="openNotifications(event)">
                        <i class="fa-regular fa-bell"></i>
                        <span class="notification-badge" id="noticeBadge" style="display:none;">
                            0
                        </span>
                    </button>
                    
                    <div class="d-flex align-items-center gap-2 ps-2">
                        <?php if (!empty($pic)): ?>
                            <img src="../<?php echo htmlspecialchars($pic, ENT_QUOTES, 'UTF-8'); ?>" class="avatar" alt="Profile">
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
                <div class="eyebrow"><?php echo $is_hr ? 'Financials' : 'My Financials'; ?></div>
                <h1><?php echo $is_hr ? 'Payroll Processing' : 'My Payslips'; ?></h1>
                <p class="text-muted mb-0"><?php echo $is_hr ? 'Generate payslips and manage employee salaries.' : 'View your salary breakdown and download payslips.'; ?></p>
            </div>
            <?php if($is_hr): ?>
            <div>
                <button class="btn btn-success" onclick="payAllSalaries()"><i class="fa-solid fa-paper-plane me-2"></i> Pay All Salaries</button>
            </div>
            <?php endif; ?>
        </section>

        <?php if($is_hr): ?>
        <!-- Process New Payroll Form (HR Only) -->
        <div class="panel">
            <div class="panel-header"><h5 class="panel-title"><i class="fa-solid fa-calculator"></i> Process New Payroll</h5></div>
            <form id="payrollForm">
                <div class="row g-3">
                    <div class="col-md-4"><label class="form-label">Pay Period Start *</label><input type="date" class="form-control" name="period_start" required></div>
                    <div class="col-md-4"><label class="form-label">Pay Period End *</label><input type="date" class="form-control" name="period_end" required></div>
                    <div class="col-md-4"><label class="form-label">Payment Date *</label><input type="date" class="form-control" name="payment_date" required></div>
                    <div class="col-12 mt-4"><button type="submit" class="btn btn-primary"><i class="fa-solid fa-bolt me-2"></i> Generate Payroll</button></div>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Payroll Records Table -->
        <div class="panel">
            <div class="panel-header">
                <h5 class="panel-title"><i class="fa-solid fa-list-ul"></i> <?php echo $is_hr ? 'Payroll Records' : 'My Payslips'; ?></h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <?php if($is_hr): ?>
                            <th>Employee</th>
                            <?php endif; ?>
                            <th>Pay Period</th>
                            <th>Basic Salary</th>
                            <th>Allowances</th>
                            <th>Deductions</th>
                            <th>Net Salary</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="payrollTableBody">
                        <tr><td colspan="<?php echo $is_hr ? '8' : '7'; ?>" class="text-center py-5 text-muted"><i class="fa-solid fa-spinner fa-spin fs-3 mb-3"></i><br> Loading payroll records...</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top" id="paginationContainer" style="display: none;">
                <div class="text-muted fw-medium" style="font-size: 13px;" id="paginationInfo"></div>
                <nav><ul class="pagination" id="paginationControls"></ul></nav>
            </div>
        </div>
    </main>

    <!-- Payslip Modal -->
    <div class="modal fade" id="payslipModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="payslip-header">
                    <h3 class="fw-bold mb-1"><i class="fa-solid fa-building me-2"></i>KormoShathi HR</h3>
                    <p class="mb-0 opacity-75">Official Payslip for the period <span id="psPeriod"></span></p>
                </div>
                <div class="modal-body p-0">
                    <div class="payslip-body">
                        <div class="row mb-4">
                            <div class="col-6">
                                <p class="mb-1 text-muted">Employee Name</p>
                                <h6 class="fw-bold" id="psEmployee"></h6>
                                <p class="mb-0 text-muted" id="psEmployeeId"></p>
                            </div>
                            <div class="col-6 text-end">
                                <p class="mb-1 text-muted">Payment Date</p>
                                <h6 class="fw-bold" id="psPaymentDate"></h6>
                                <span id="psStatus" class="badge bg-success mt-1">Paid</span>
                            </div>
                        </div>
                        <hr class="text-muted opacity-25">
                        
                        <div class="payslip-row"><span class="payslip-label">Basic Salary</span><span class="payslip-value" id="psBasic">৳ 0</span></div>
                        <div class="payslip-row"><span class="payslip-label">House Rent Allowance</span><span class="payslip-value" id="psHouseRent">৳ 0</span></div>
                        <div class="payslip-row"><span class="payslip-label">Medical Allowance</span><span class="payslip-value" id="psMedical">৳ 0</span></div>
                        <div class="payslip-row"><span class="payslip-label">Travel Allowance</span><span class="payslip-value" id="psTravel">৳ 0</span></div>
                        <div class="payslip-row fw-bold text-success"><span>Total Allowances</span><span id="psTotalAllowance">৳ 0</span></div>
                        
                        <div class="payslip-row mt-3"><span class="payslip-label">Provident Fund (PF)</span><span class="payslip-value text-danger" id="psPF">৳ 0</span></div>
                        <div class="payslip-row"><span class="payslip-label">Tax</span><span class="payslip-value text-danger" id="psTax">৳ 0</span></div>
                        <div class="payslip-row fw-bold text-danger"><span>Total Deductions</span><span id="psTotalDeductions">৳ 0</span></div>
                        
                        <div class="payslip-row total text-primary">
                            <span>Net Salary</span>
                            <span id="psNetSalary">৳ 0</span>
                        </div>
                    </div>
                    <div class="payslip-footer">
                        <p class="text-muted mb-1" style="font-size: 12px;">This is a computer-generated payslip. No signature required.</p>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0">
                    <button class="btn btn-light fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary fw-bold px-4" id="downloadPayslipBtn"><i class="fa-solid fa-download me-2"></i>Download PDF</button>
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
        const isHR = <?php echo $is_hr ? 'true' : 'false'; ?>;
        let notificationModal = null;
        let allPayrolls = [];
        let currentPage = 1;
        const rowsPerPage = 10;

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

                const hrBadge = document.getElementById('notificationBadge');
                const hrDot = document.getElementById('notificationDot');
                if (hrBadge && hrDot) {
                    if (unread > 0) {
                        hrBadge.style.display = 'inline-block'; hrBadge.textContent = unread; hrDot.style.display = 'block';
                    } else {
                        hrBadge.style.display = 'none'; hrDot.style.display = 'none';
                    }
                }

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
                await fetch('../api/notifications.php', { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }) });
                await loadNotifications(); await loadNotificationCount();
            } catch (error) {}
        }

        async function markAllAsReadAndClose() {
            try {
                await fetch('../api/notifications.php', { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: 0 }) });
                await loadNotifications(); await loadNotificationCount(); 
                if (notificationModal) notificationModal.hide();
            } catch (error) {}
        }

        function escapeHTML(str) { return String(str || '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
        function formatTime(dateString) { if(!dateString) return ''; return new Date(dateString).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }); }


        // ==========================================
        // Payroll Logic
        // ==========================================
        async function loadPayroll() {
            try {
                const response = await fetch('../api/payroll.php');
                const result = await response.json();
                
                if(result.success && result.data && result.data.length > 0) {
                    allPayrolls = result.data;
                    document.getElementById('paginationContainer').style.display = 'flex';
                    
                    if (currentPage > Math.ceil(allPayrolls.length / rowsPerPage)) {
                        currentPage = 1;
                    }
                    renderPayrollTable(currentPage);
                } else {
                    document.getElementById('paginationContainer').style.display = 'none';
                    document.getElementById('payrollTableBody').innerHTML = `<tr><td colspan="${isHR ? '8' : '7'}" class="text-center py-5 text-muted">No payroll records found.</td></tr>`;
                }
            } catch(e) {
                console.error('Error:', e);
                document.getElementById('payrollTableBody').innerHTML = `<tr><td colspan="${isHR ? '8' : '7'}" class="text-center py-4 text-danger">Error loading payroll data.</td></tr>`;
            }
        }

        function renderPayrollTable(page) {
            const tbody = document.getElementById('payrollTableBody');
            const startIndex = (page - 1) * rowsPerPage;
            const endIndex = startIndex + rowsPerPage;
            const paginatedData = allPayrolls.slice(startIndex, endIndex);

            tbody.innerHTML = paginatedData.map(p => {
                const isPaid = p.status === 'paid';
                const statusClass = isPaid ? 'status-paid' : (p.status === 'processed' ? 'status-processed' : 'status-pending');
                
                const employeeCol = isHR ? `
                    <td>
                        <div class="fw-bold text-dark">${p.first_name || ''} ${p.last_name || ''}</div>
                        <div class="text-muted" style="font-size: 12px;">${p.employee_id || ''}</div>
                    </td>` : '';

                return `
                    <tr>
                        ${employeeCol}
                        <td><span class="fw-medium">${p.period_start || 'N/A'}</span> <br><small class="text-muted">to ${p.period_end || 'N/A'}</small></td>
                        <td>৳ ${parseFloat(p.basic_salary || 0).toLocaleString()}</td>
                        <td class="text-success">+৳ ${parseFloat(p.total_allowance || 0).toLocaleString()}</td>
                        <td class="text-danger">-৳ ${parseFloat(p.total_deductions || 0).toLocaleString()}</td>
                        <td><strong class="text-primary">৳ ${parseFloat(p.net_salary || 0).toLocaleString()}</strong></td>
                        <td><span class="status-badge ${statusClass}">${p.status || 'pending'}</span></td>
                        <td class="text-end">
                            <button class="btn-action me-1" onclick="viewPayslip(${p.id})" title="View Payslip">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                            <button class="btn-action" onclick="downloadPayslip(${p.id})" title="Download">
                                <i class="fa-solid fa-download"></i>
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');

            setupPagination(allPayrolls.length, page);
        }

        function setupPagination(totalItems, currentPage) {
            const totalPages = Math.ceil(totalItems / rowsPerPage);
            const paginationControls = document.getElementById('paginationControls');
            const paginationInfo = document.getElementById('paginationInfo');

            const startIndex = (currentPage - 1) * rowsPerPage;
            const endIndex = startIndex + rowsPerPage;
            paginationInfo.textContent = `Showing ${startIndex + 1} to ${Math.min(endIndex, totalItems)} of ${totalItems} entries`;

            paginationControls.innerHTML = '';

            const prevLi = document.createElement('li');
            prevLi.className = `page-item ${currentPage === 1 ? 'disabled' : ''}`;
            prevLi.innerHTML = `<a class="page-link" href="#" aria-label="Previous"><i class="fa-solid fa-chevron-left" style="font-size:12px;"></i></a>`;
            prevLi.addEventListener('click', (e) => { e.preventDefault(); if (currentPage > 1) { renderPayrollTable(currentPage - 1); } });
            paginationControls.appendChild(prevLi);

            for (let i = 1; i <= totalPages; i++) {
                const li = document.createElement('li');
                li.className = `page-item ${i === currentPage ? 'active' : ''}`;
                li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
                li.addEventListener('click', (e) => { e.preventDefault(); renderPayrollTable(i); });
                paginationControls.appendChild(li);
            }

            const nextLi = document.createElement('li');
            nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
            nextLi.innerHTML = `<a class="page-link" href="#" aria-label="Next"><i class="fa-solid fa-chevron-right" style="font-size:12px;"></i></a>`;
            nextLi.addEventListener('click', (e) => { e.preventDefault(); if (currentPage < totalPages) { renderPayrollTable(currentPage + 1); } });
            paginationControls.appendChild(nextLi);
        }

        async function payAllSalaries() {
            if(!confirm('Are you sure you want to transfer salaries to all employees? This will mark all processed payrolls as Paid.')) return;
            
            const btn = document.querySelector('.btn-success');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Processing...';
            btn.disabled = true;

            try {
                const res = await fetch('../api/payroll.php?action=pay_all', { method: 'POST' });
                const result = await res.json();
                
                if(result.success) {
                    alert(result.message);
                    loadPayroll();
                } else {
                    alert(result.message);
                }
            } catch(e) {
                alert('Error transferring salaries.');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        }

        document.getElementById('payrollForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const payrollData = { period_start: formData.get('period_start'), period_end: formData.get('period_end'), payment_date: formData.get('payment_date') };
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Processing...'; submitBtn.disabled = true;
            
            try {
                const response = await fetch('../api/payroll.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payrollData) });
                const result = await response.json(); alert(result.message);
                if(result.success) { this.reset(); loadPayroll(); }
            } catch(error) { alert('Error processing payroll'); } finally { submitBtn.innerHTML = originalText; submitBtn.disabled = false; }
        });

        async function viewPayslip(id) {
            try {
                const response = await fetch(`../api/get-payslip.php?id=${id}`);
                const data = await response.json();
                
                if(data.success) {
                    const p = data.data;
                    document.getElementById('psEmployee').textContent = p.first_name + ' ' + p.last_name;
                    document.getElementById('psEmployeeId').textContent = p.emp_code || p.employee_id;
                    document.getElementById('psPeriod').textContent = p.period_start + ' to ' + p.period_end;
                    document.getElementById('psPaymentDate').textContent = p.payment_date || 'N/A';
                    
                    const statusEl = document.getElementById('psStatus');
                    statusEl.textContent = p.status || 'Paid';
                    statusEl.className = 'badge ' + (p.status === 'paid' || p.status === 'processed' ? 'bg-success' : 'bg-warning text-dark');
                    
                    document.getElementById('psBasic').textContent = '৳ ' + parseFloat(p.basic_salary || 0).toLocaleString();
                    document.getElementById('psHouseRent').textContent = '৳ ' + parseFloat(p.house_rent || 0).toLocaleString();
                    document.getElementById('psMedical').textContent = '৳ ' + parseFloat(p.medical_allowance || 0).toLocaleString();
                    document.getElementById('psTravel').textContent = '৳ ' + parseFloat(p.travel_allowance || 0).toLocaleString();
                    document.getElementById('psTotalAllowance').textContent = '৳ ' + parseFloat(p.total_allowance || 0).toLocaleString();
                    document.getElementById('psPF').textContent = '৳ ' + parseFloat(p.provident_fund || 0).toLocaleString();
                    document.getElementById('psTax').textContent = '৳ ' + parseFloat(p.tax || 0).toLocaleString();
                    document.getElementById('psTotalDeductions').textContent = '৳ ' + parseFloat(p.total_deductions || 0).toLocaleString();
                    document.getElementById('psNetSalary').textContent = '৳ ' + parseFloat(p.net_salary || 0).toLocaleString();
                    
                    document.getElementById('downloadPayslipBtn').onclick = function() { downloadPayslip(id); };
                    
                    const modal = new bootstrap.Modal(document.getElementById('payslipModal'));
                    modal.show();
                } else {
                    alert('Payslip not found');
                }
            } catch(e) {
                alert('Error loading payslip details');
            }
        }

        function downloadPayslip(id) { window.open(`../api/download-payslip.php?id=${id}`, '_blank'); }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            loadPayroll();
            loadNotificationCount();
            setInterval(loadNotificationCount, 5000);
        });
    </script>
</body>
</html>
