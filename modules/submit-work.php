<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';
date_default_timezone_set('Asia/Dhaka');

$database = new Database();
$db = $database->getConnection();

$userId = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// --- STATS FOR LEFT CARD ---
$stmtStats = $db->prepare("SELECT status, COUNT(*) as count FROM employee_submissions WHERE employee_id = ? GROUP BY status");
$stmtStats->execute([$userId]);
$statsData = $stmtStats->fetchAll(PDO::FETCH_KEY_PAIR);
$total_subs = array_sum($statsData);
$approved_subs = $statsData['approved'] ?? 0;
$pending_subs = $statsData['pending'] ?? 0;

// --- PAGINATION LOGIC ---
$limit = 4;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$countStmt = $db->prepare("SELECT COUNT(*) FROM employee_submissions WHERE employee_id = ?");
$countStmt->execute([$userId]);
$total_records = (int)$countStmt->fetchColumn();
$total_pages = max(1, (int)ceil($total_records / $limit));

if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $limit;

$stmt = $db->prepare("SELECT * FROM employee_submissions WHERE employee_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
$stmt->bindValue(1, $userId, PDO::PARAM_INT);
$stmt->bindValue(2, $limit, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$submissions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get User Data for Topbar
$stmt = $db->prepare("SELECT first_name, last_name, profile_picture, position FROM users WHERE id = :id");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$firstName = $_SESSION['first_name'] ?? ($user['first_name'] ?? 'Employee');
$lastName = $_SESSION['last_name'] ?? ($user['last_name'] ?? '');
$fullName = trim($firstName . ' ' . $lastName);
$initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
$pic = $_SESSION['profile_picture'] ?? ($user['profile_picture'] ?? '');
$position = $_SESSION['position'] ?? ($user['position'] ?? 'Employee');
$dashboardUrl = ($user_type === 'hr') ? '../hr-dashboard.php' : '../employee-dashboard.php';

$isUrl = !empty($pic) && (strpos($pic, 'http://') === 0 || strpos($pic, 'https://') === 0);
$isLocal = !empty($pic) && !$isUrl && file_exists(__DIR__ . '/../' . $pic);
$profileImage = '';
if ($isUrl) { $profileImage = $pic; } 
elseif ($isLocal) { $profileImage = '/../' . trim($pic, '/'); }

function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Work | KormoShathi</title>

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
        body {
            margin: 0; background: var(--page); color: var(--ink);
            font-family: 'DM Sans', sans-serif; font-size: 15px; overflow-x: hidden;
        }
        a { text-decoration: none; }
        button { font-family: inherit; }

        /* SIDEBAR */
        .sidebar {
            width: var(--sidebar-width); position: fixed; inset: 0 auto 0 0; z-index: 1040;
            display: flex; flex-direction: column; color: #d8e2f0;
            background: linear-gradient(180deg, var(--navy) 0%, #102037 100%);
            padding: 23px 16px 18px; overflow-y: auto; transition: transform .25s ease;
        }
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

        /* MAIN CONTENT */
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; padding: 0 30px 30px; transition: margin 0.3s ease; }
        
        /* TOPBAR */
        .topbar { min-height: 78px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--line); margin-bottom: 27px; gap: 20px; }
        .topbar-left, .topbar-right { display: flex; align-items: center; gap: 14px; }
        .mobile-menu { display: none; width: 42px; height: 42px; border: 1px solid var(--line); background: #fff; color: var(--ink); border-radius: 10px; }
        .search-wrap { position: relative; width: min(370px, 42vw); }
        .search-wrap i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9aa6b6; }
        .search-wrap input { width: 100%; height: 44px; padding: 0 15px 0 41px; background: #fff; border: 1px solid var(--line); border-radius: 10px; outline: none; color: var(--ink); font-size: 14px; }
        .top-icon { position: relative; display: grid; place-items: center; width: 42px; height: 42px; border: 1px solid var(--line); background: #fff; color: #637187; border-radius: 10px; }
        .top-icon .dot { position: absolute; width: 8px; height: 8px; border-radius: 50%; background: #ed6470; right: 8px; top: 7px; border: 1px solid #fff; }
        .notification-badge { position: absolute; top: -5px; right: -5px; background: var(--red); color: white; font-size: 10px; font-weight: 700; border-radius: 50%; min-width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; border: 2px solid white; }
        .top-divider { height: 34px; border-left: 1px solid var(--line); margin: 0 2px; }
        .profile-button { display: flex; align-items: center; gap: 10px; border: 0; background: transparent; text-align: left; color: var(--ink); padding: 2px; }
        .avatar { width: 42px; height: 42px; flex: 0 0 42px; border-radius: 50%; object-fit: cover; display: flex; align-items: center; justify-content: center; color: #fff; background: linear-gradient(145deg, #6594ff, #356df3); font-weight: 700; font-size: 16px; }
        .profile-name { display: block; font-size: 14px; font-weight: 700; }
        .profile-role { display: block; color: var(--muted); font-size: 11px; margin-top: 2px; }

        /* PAGE HEADING & PANELS */
        .page-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; margin-bottom: 22px; }
        .eyebrow { color: var(--primary); font-size: 11px; font-weight: 800; letter-spacing: 1.25px; text-transform: uppercase; margin-bottom: 7px; }
        h1 { font-family: 'Plus Jakarta Sans', sans-serif; color: var(--ink); font-size: 28px; font-weight: 800; letter-spacing: -.8px; margin: 0; }
        .page-heading p { color: var(--muted); margin: 6px 0 0; font-size: 14px; }

        .panel { background: var(--surface); border: 1px solid var(--line); border-radius: 16px; box-shadow: var(--shadow); padding: 24px; margin-bottom: 24px; }
        .panel-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; border-bottom: 1px solid var(--line); padding-bottom: 15px; }
        .panel-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 16px; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 8px; }
        .panel-title i { color: var(--primary); }

        /* FORM STYLES */
        .form-label { font-weight: 600; font-size: 13px; color: #435168; margin-bottom: 8px; }
        .form-control, .form-select { border: 1px solid var(--line); padding: 12px 15px; border-radius: 10px; font-size: 14px; background-color: #fbfcfe; transition: 0.2s; }
        .form-control:focus, .form-select:focus { border-color: var(--primary); background-color: #fff; box-shadow: 0 0 0 3px rgba(53,109,243,0.1); }
        .btn-primary { background: var(--primary); border: none; padding: 14px 24px; border-radius: 10px; font-weight: 600; font-size: 15px; transition: 0.2s; }
        .btn-primary:hover { background: #2859d9; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(53,109,243,0.3); }

        /* OVERVIEW CARD */
        .overview-card { text-align: center; display: flex; flex-direction: column; justify-content: center; height: 100%; min-height: 350px; }
        .overview-title { font-size: 13px; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; }
        .overview-number { font-size: 56px; font-weight: 800; color: var(--primary); line-height: 1; margin-bottom: 5px; font-family: 'Plus Jakarta Sans', sans-serif; }
        .overview-subtitle { font-size: 15px; color: var(--ink); font-weight: 600; margin-bottom: 20px; }
        .stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 20px; padding: 0 20px; }
        .stat-box { background: #f8fafc; border: 1px solid var(--line); padding: 12px; border-radius: 10px; text-align: center; }
        .stat-box h4 { margin: 0; font-size: 20px; font-weight: 800; }
        .stat-box p { margin: 0; font-size: 11px; font-weight: 600; color: var(--muted); text-transform: uppercase; }
        .stat-box.approved h4 { color: var(--green); }
        .stat-box.pending h4 { color: var(--orange); }

        /* FILE UPLOAD AREA */
        .file-upload-area {
            border: 2px dashed #cbd5e1;
            padding: 25px 20px;
            text-align: center;
            border-radius: 12px;
            transition: 0.3s;
            cursor: pointer;
            background: #f8fafc;
        }
        .file-upload-area:hover, .file-upload-area.dragover { border-color: var(--primary); background: var(--primary-soft); }
        .file-upload-area i { font-size: 35px; color: var(--primary); margin-bottom: 10px; }

        /* HISTORY LIST */
        .history-card { border: 1px solid var(--line); border-radius: 12px; padding: 20px; margin-bottom: 15px; background: #fff; position: relative; }
        .history-card::before { content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 4px; border-radius: 12px 0 0 12px; }
        .history-card.pending::before { background: var(--orange); }
        .history-card.approved::before { background: var(--green); }
        .history-card.rejected::before { background: var(--red); }
        .history-card.reviewed::before { background: var(--primary); }

        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-badge.pending { background: #fef3c7; color: #b45309; }
        .status-badge.approved { background: #d1fae5; color: #047857; }
        .status-badge.rejected { background: #fee2e2; color: #b91c1c; }
        .status-badge.reviewed { background: #dbeafe; color: #1d4ed8; }

        .reason-box { background: #f8fafc; border: 1px solid var(--line); padding: 12px 15px; border-radius: 8px; font-size: 14px; color: #475569; }
        .feedback-box { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 12px 15px; border-radius: 8px; font-size: 14px; }
        .feedback-box.approved-feedback { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }

        /* PAGINATION */
        .custom-pagination { margin-bottom: 0; }
        .custom-pagination .page-link { color: var(--muted); border: none; background: transparent; border-radius: 8px; margin: 0 3px; display: flex; align-items: center; justify-content: center; min-width: 32px; height: 32px; font-weight: 600; font-size: 14px; transition: 0.2s; }
        .custom-pagination .page-item.active .page-link { background-color: var(--primary); color: white; box-shadow: 0 4px 10px rgba(53, 109, 243, 0.3); }
        .custom-pagination .page-item:not(.disabled):not(.active):hover .page-link { background-color: var(--primary-soft); color: var(--primary); }
        .custom-pagination .page-item.disabled .page-link { color: #cbd5e1; cursor: not-allowed; }

        /* Notifications */
        .notification-item { display: flex; align-items: flex-start; gap: 12px; padding: 14px; border: 1px solid #edf0f5; border-radius: 10px; background: #fbfcfe; cursor: pointer; transition: .18s; }
        .notification-item:hover { border-color: #d8e4ff; background: #f7faff; }
        .notification-item.unread { background: #f4f8ff; border-color: #e2ebff; }
        .notification-icon { width: 34px; height: 34px; flex: 0 0 34px; display: grid; place-items: center; border-radius: 9px; color: var(--primary); background: #eaf1ff; font-size: 14px; }
        .notification-title { color: #344258; font-size: 13px; font-weight: 700; }
        .notification-message { color: #8591a1; font-size: 12px; margin-top: 4px; line-height: 1.45; }
        .notification-time { margin-left: auto; color: #9aa4b2; font-size: 10px; white-space: nowrap; }

        /* MOBILE */
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

    <div class="sidebar-overlay" id="mobileOverlay" onclick="toggleSidebar()"></div>

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <a href="<?php echo $dashboardUrl; ?>" class="brand">
            <span class="brand-mark"><i class="fa-solid fa-people-group"></i></span>
            <span><span class="brand-name d-block">KormoShathi</span><span class="brand-sub d-block">HR MANAGEMENT SYSTEM</span></span>
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
                <li><a class="side-link" href="leave.php"><i class="fa-regular fa-calendar-check"></i> Leave Management</a></li>
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
            <!-- Employee Sidebar Links -->
            <div class="nav-caption">Workspace</div>
            <ul class="side-nav">
                <li><a href="../employee-dashboard.php" class="side-link"><i class="fa-solid fa-table-cells-large"></i> Dashboard</a></li>
                <li><a href="chat.php" class="side-link"><i class="fa-regular fa-comments"></i> Messages</a></li>
                <li><a href="my-attendance.php" class="side-link"><i class="fa-solid fa-fingerprint"></i> My Attendance</a></li>
                <li><a href="leave.php" class="side-link"><i class="fa-regular fa-calendar-check"></i> Leave Management</a></li>
                <li><a href="submit-work.php" class="side-link active"><i class="fa-solid fa-cloud-arrow-up"></i> Submit Work</a></li>
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
                <li><a href="../logout.php" class="side-link text-danger"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li>
            </ul>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- TOPBAR -->
        <header class="topbar">
            <?php if ($user_type === 'hr'): ?>
                <!-- HR Topbar -->
                <div class="topbar-left">
                    <button class="mobile-menu" type="button" onclick="toggleSidebar()" aria-label="Open menu"><i class="fa-solid fa-bars"></i></button>
                    <h4 class="fw-bold m-0 d-flex align-items-center gap-2 me-3" style="font-family: 'Plus Jakarta Sans', sans-serif;"><i class="fa-solid fa-cloud-arrow-up text-primary"></i> Work Submissions</h4>
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
                <!-- Employee Topbar -->
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-light d-lg-none" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
                    <h4 class="fw-bold m-0 d-flex align-items-center gap-2" style="font-family: 'Plus Jakarta Sans', sans-serif;"><i class="fa-solid fa-cloud-arrow-up text-primary"></i> Submit Work</h4>
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
                            <img src="<?php echo htmlspecialchars($profileImage); ?>" class="avatar" alt="Profile">
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

        <!-- PAGE SUB-HEADING -->
        <div class="page-heading">
            <div>
                <div class="eyebrow">Task Management</div>
                <h1>Submit Work</h1>
                <p class="text-muted mb-0">Upload your completed tasks and view feedback from HR.</p>
            </div>
        </div>

        <!-- ROW 1: OVERVIEW & FORM -->
        <div class="row g-4 mb-4">
            
            <!-- LEFT COLUMN: OVERVIEW STATS -->
            <div class="col-lg-5">
                <div class="panel overview-card">
                    <div class="overview-title">Submission Overview</div>
                    <div class="overview-number"><?php echo $total_subs; ?></div>
                    <div class="overview-subtitle">Total Tasks Submitted</div>
                    
                    <div class="stats-grid">
                        <div class="stat-box approved">
                            <h4><?php echo $approved_subs; ?></h4>
                            <p>Approved</p>
                        </div>
                        <div class="stat-box pending">
                            <h4><?php echo $pending_subs; ?></h4>
                            <p>Pending</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT COLUMN: SUBMIT FORM -->
            <div class="col-lg-7">
                <div class="panel h-100">
                    <div class="panel-header">
                        <h5 class="panel-title"><i class="fa-solid fa-cloud-arrow-up"></i> Submit New Work</h5>
                    </div>
                    
                    <form id="submitForm" enctype="multipart/form-data">
                        <div class="row g-3 mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Work Title <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="title" placeholder="e.g., Website Landing Page Design" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="2" placeholder="Briefly describe what you have done..."></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">Attach File <span class="text-danger">*</span></label>
                            <div class="file-upload-area" id="fileUploadArea">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                                <h6 class="fw-bold mb-1">Click to upload or drag & drop</h6>
                                <p class="text-muted small mb-0">PDF, DOC, XLS, PNG, JPG (Max 10MB)</p>
                                <input type="file" class="d-none" name="file" id="fileInput" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg" required>
                            </div>
                            
                            <div id="fileInfo" class="mt-3" style="display:none;">
                                <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded border">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-file text-primary fs-4"></i>
                                        <div>
                                            <div class="fw-bold text-dark" style="font-size: 13px;" id="fileName">file.pdf</div>
                                            <div class="text-muted" style="font-size: 11px;" id="fileSize">0 KB</div>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger border-0" onclick="removeFile()">
                                        <i class="fa-solid fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100" id="submitBtn">
                            <i class="fa-solid fa-paper-plane me-2"></i> Submit Work
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- ROW 2: SUBMISSION HISTORY -->
        <div class="row">
            <div class="col-12">
                <div class="panel">
                    <div class="panel-header">
                        <h5 class="panel-title"><i class="fa-solid fa-list-check"></i> My Submission History</h5>
                        <span class="text-muted fw-bold" style="font-size: 13px;"><?php echo $total_records; ?> requests</span>
                    </div>
                    
                    <div class="submission-list">
                        <?php if (count($submissions) > 0): ?>
                            <?php foreach ($submissions as $sub): ?>
                                <div class="history-card <?php echo e($sub['status']); ?>">
                                    
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-light text-primary border" style="font-size: 11px; padding: 6px 12px; border-radius: 6px; text-transform: uppercase;">
                                                TASK
                                            </span>
                                            <span class="status-badge <?php echo e($sub['status']); ?>">
                                                <?php echo e(strtoupper($sub['status'])); ?>
                                            </span>
                                        </div>
                                        <div class="text-muted fw-bold" style="font-size: 12px;">
                                            Submitted: <?php echo date('d M Y', strtotime($sub['created_at'])); ?>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="fw-bold mb-0 text-dark fs-5"><?php echo htmlspecialchars($sub['title']); ?></h6>
                                        <?php if ($sub['file_path']): ?>
                                            <a href="../<?php echo htmlspecialchars($sub['file_path']); ?>" target="_blank" class="btn btn-sm btn-outline-primary fw-bold" style="border-radius: 6px;">
                                                <i class="fa-solid fa-download me-1"></i> View File
                                            </a>
                                        <?php endif; ?>
                                    </div>

                                    <div class="d-flex align-items-center gap-2 mb-3 text-muted" style="font-size: 13px;">
                                        <i class="fa-regular fa-clock"></i>
                                        <span><?php echo date('d M Y, h:i A', strtotime($sub['created_at'])); ?></span>
                                    </div>
                                    
                                    <div class="reason-box">
                                        <strong>Description:</strong> <?php echo htmlspecialchars($sub['description'] ?: 'No description provided.'); ?>
                                    </div>
                                    
                                    <?php if ($sub['feedback']): ?>
                                        <div class="feedback-box mt-3 <?php echo ($sub['status'] == 'approved' || $sub['status'] == 'reviewed') ? 'approved-feedback' : ''; ?>">
                                            <strong class="d-block mb-1">
                                                <i class="fa-solid fa-comment-dots me-1"></i> HR Feedback:
                                            </strong>
                                            <?php echo htmlspecialchars($sub['feedback']); ?>
                                        </div>
                                    <?php endif; ?>

                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fa-regular fa-folder-open fs-1 text-muted mb-3"></i>
                                <h6 class="fw-bold text-dark">No submissions yet</h6>
                                <p class="text-muted small">Your submitted work will appear here.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- PAGINATION FOOTER -->
                    <?php if ($total_records > 0):
                        $range = 2;
                        $start = max(1, $page - $range);
                        $end   = min($total_pages, $page + $range);
                    ?>
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-4 pt-3 border-top">
                        <div class="text-muted" style="font-size: 13px;">
                            Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $limit, $total_records); ?> of <?php echo $total_records; ?> entries
                        </div>

                        <?php if ($total_pages > 1): ?>
                        <ul class="pagination custom-pagination">
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo ($page <= 1) ? '#' : '?page=' . ($page - 1); ?>" aria-label="Previous">
                                    <i class="fa-solid fa-chevron-left" style="font-size: 12px;"></i>
                                </a>
                            </li>

                            <?php if ($start > 1): ?>
                                <li class="page-item"><a class="page-link" href="?page=1">1</a></li>
                                <?php if ($start > 2): ?>
                                    <li class="page-item disabled"><span class="page-link">…</span></li>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $start; $i <= $end; $i++): ?>
                                <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($end < $total_pages): ?>
                                <?php if ($end < $total_pages - 1): ?>
                                    <li class="page-item disabled"><span class="page-link">…</span></li>
                                <?php endif; ?>
                                <li class="page-item"><a class="page-link" href="?page=<?php echo $total_pages; ?>"><?php echo $total_pages; ?></a></li>
                            <?php endif; ?>

                            <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo ($page >= $total_pages) ? '#' : '?page=' . ($page + 1); ?>" aria-label="Next">
                                    <i class="fa-solid fa-chevron-right" style="font-size: 12px;"></i>
                                </a>
                            </li>
                        </ul>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

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

        // --- FILE UPLOAD DRAG & DROP LOGIC ---
        const uploadArea = document.getElementById('fileUploadArea');
        const fileInput = document.getElementById('fileInput');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        const fileSize = document.getElementById('fileSize');

        uploadArea.addEventListener('click', () => fileInput.click());

        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                updateFileInfo(e.dataTransfer.files[0]);
            }
        });

        fileInput.addEventListener('change', function() {
            if (this.files.length) {
                updateFileInfo(this.files[0]);
            }
        });

        function updateFileInfo(file) {
            fileInfo.style.display = 'block';
            fileName.textContent = file.name;
            const size = (file.size / 1024).toFixed(1);
            fileSize.textContent = size > 1024 ? (size / 1024).toFixed(1) + ' MB' : size + ' KB';
            uploadArea.style.display = 'none';
        }

        function removeFile() {
            fileInput.value = '';
            fileInfo.style.display = 'none';
            uploadArea.style.display = 'block';
        }

        // --- SUBMIT FORM LOGIC ---
        document.getElementById('submitForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const btn = document.getElementById('submitBtn');
            const originalText = btn.innerHTML;
            
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Uploading...';
            btn.disabled = true;
            
            try {
                const response = await fetch('../api/submit-work.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                alert(result.message || (result.success ? 'Work submitted successfully!' : 'Submission failed'));
                if (result.success) {
                    window.location.href = 'submit-work.php?page=1';
                }
            } catch(e) {
                alert('Network error! Please check your connection and try again.');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });

        // --- MOBILE SIDEBAR ---
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('mobileOverlay').classList.toggle('active');
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

        document.addEventListener('DOMContentLoaded', () => {
            loadNotificationCount();
            setInterval(loadNotificationCount, 5000);
        });
    </script>
</body>
</html>
