<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'hr') {
    header('Location: login.php');
    exit;
}

// Existing profile lookup — backend logic retained.
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();
$stmt = $db->prepare("SELECT profile_picture, first_name, last_name FROM users WHERE id = :id");
$stmt->bindParam(':id', $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$firstName = $_SESSION['first_name'] ?? ($user['first_name'] ?? 'HR');
$lastName = $_SESSION['last_name'] ?? ($user['last_name'] ?? 'Manager');
$fullName = trim($firstName . ' ' . $lastName);
$initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));

// Simply get the picture directly from the database
$pic = '';
if ($user && !empty($user['profile_picture'])) {
    $pic = trim($user['profile_picture']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Dashboard | KormoShathi</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        :root {
            --navy: #14243a;
            --navy-2: #1c3452;
            --primary: #356df3;
            --primary-soft: #edf3ff;
            --ink: #17263c;
            --muted: #7b8798;
            --line: #e9edf3;
            --surface: #ffffff;
            --page: #f5f7fb;
            --green: #16a675;
            --orange: #f39a38;
            --red: #e45d68;
            --purple: #8262df;
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
            overflow-x: hidden;
        }
        a { text-decoration: none; }
        button, input { font: inherit; }
        .app-shell { min-height: 100vh; }

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
            transition: transform .25s ease;
        }
        .brand {
            display: flex; align-items: center; gap: 11px; padding: 0 9px 23px;
            border-bottom: 1px solid rgba(255,255,255,.09); margin-bottom: 20px;
        }
        .brand-mark {
            width: 44px; height: 44px; display: grid; place-items: center;
            color: #fff; background: linear-gradient(145deg, #4e8bff, #2860dc);
            border-radius: 13px; font-size: 20px; box-shadow: 0 7px 18px rgba(53,109,243,.28);
        }
        .brand-name {
            color: #fff; font: 800 19px 'Plus Jakarta Sans', sans-serif;
            letter-spacing: -.5px; line-height: 1.15;
        }
        .brand-sub { color: #91a4bd; font-size: 11px; margin-top: 4px; letter-spacing: .55px; }
        .nav-caption {
            color: #71859f; font-size: 11px; font-weight: 700;
            letter-spacing: 1.15px; text-transform: uppercase; padding: 13px 12px 8px;
        }
        .side-nav { list-style: none; padding: 0; margin: 0; }
        .side-nav li { margin: 3px 0; }
        .side-link {
            display: flex; align-items: center; gap: 13px; min-height: 46px; padding: 10px 13px; border-radius: 9px;
            color: #aebdd0; font-size: 14px; font-weight: 500; transition: .18s ease;
        }
        .side-link i { width: 20px; text-align: center; font-size: 15px; }
        .side-link:hover { color: #fff; background: rgba(255,255,255,.075); }
        .side-link.active {
            color: #fff; background: linear-gradient(100deg, #3978f5, #2e66e6);
            box-shadow: 0 7px 18px rgba(31,91,218,.23);
        }
        .sidebar-bottom { margin-top: auto; padding-top: 16px; }
        .sidebar-help {
            margin: 13px 4px 15px; padding: 14px;
            background: rgba(255,255,255,.065); border: 1px solid rgba(255,255,255,.07); border-radius: 12px;
        }
        .sidebar-help .help-icon {
            width: 32px; height: 32px; display: grid; place-items: center;
            background: rgba(83,139,255,.2); color: #8eb5ff; border-radius: 9px; margin-bottom: 9px;
        }
        .sidebar-help strong { display: block; color: #fff; font-size: 13px; }
        .sidebar-help small { color: #91a4bd; font-size: 11px; display: block; margin: 4px 0 9px; }
        .sidebar-help a { color: #9fc0ff; font-size: 12px; font-weight: 700; }

        /* Main and top bar */
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; padding: 0 30px 30px; }
        .topbar {
            min-height: 78px; display: flex; align-items: center; justify-content: space-between;
            border-bottom: 1px solid var(--line); margin-bottom: 27px; gap: 20px;
        }
        .topbar-left, .topbar-right { display: flex; align-items: center; gap: 14px; }
        .mobile-menu {
            display: none; width: 42px; height: 42px; border: 1px solid var(--line);
            background: #fff; color: var(--ink); border-radius: 10px;
        }
        .search-wrap { position: relative; width: min(370px, 42vw); }
        .search-wrap i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9aa6b6; }
        .search-wrap input {
            width: 100%; height: 44px; padding: 0 15px 0 41px;
            background: #fff; border: 1px solid var(--line); border-radius: 10px; outline: none; color: var(--ink); font-size: 14px;
        }
        .search-wrap input:focus { border-color: #a9c2ff; box-shadow: 0 0 0 3px rgba(53,109,243,.08); }
        .top-icon {
            position: relative; display: grid; place-items: center; width: 42px; height: 42px;
            border: 1px solid var(--line); background: #fff; color: #637187; border-radius: 10px;
        }
        .top-icon .dot {
            position: absolute; width: 8px; height: 8px; border-radius: 50%;
            background: #ed6470; right: 8px; top: 7px; border: 1px solid #fff;
        }
        .top-divider { height: 34px; border-left: 1px solid var(--line); margin: 0 2px; }
        .profile-button { display: flex; align-items: center; gap: 10px; border: 0; background: transparent; text-align: left; color: var(--ink); padding: 2px; }
        
        /* CSS Fix for Avatar */
        .avatar {
            width: 42px; height: 42px; flex: 0 0 42px; border-radius: 50%; object-fit: cover; 
            display: flex; align-items: center; justify-content: center; 
            color: #fff; background: linear-gradient(145deg, #6594ff, #356df3); font-weight: 700; font-size: 16px;
        }
        
        .profile-name { display: block; font-size: 14px; font-weight: 700; }
        .profile-role { display: block; color: var(--muted); font-size: 11px; margin-top: 2px; }

        /* Page header */
        .page-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; margin-bottom: 22px; }
        .eyebrow { color: var(--primary); font-size: 11px; font-weight: 800; letter-spacing: 1.25px; text-transform: uppercase; margin-bottom: 7px; }
        h1, h2, h3, h4, h5 { font-family: 'Plus Jakarta Sans', sans-serif; color: var(--ink); }
        .page-heading h1 { font-size: 28px; font-weight: 800; letter-spacing: -.8px; margin: 0; }
        .page-heading p { color: var(--muted); margin: 6px 0 0; font-size: 14px; }
        .date-chip {
            display: inline-flex; align-items: center; gap: 9px; padding: 10px 14px;
            border: 1px solid var(--line); background: #fff; border-radius: 10px;
            color: #59677b; font-size: 13px; font-weight: 600; white-space: nowrap;
        }
        .date-chip i { color: var(--primary); }

        /* Cards */
        .stat-card, .panel {
            background: var(--surface); border: 1px solid var(--line);
            border-radius: 13px; box-shadow: var(--shadow);
        }
        .stat-card { min-height: 125px; padding: 20px; display: flex; align-items: center; gap: 15px; }
        .stat-icon {
            width: 50px; height: 50px; flex: 0 0 50px; display: grid; place-items: center;
            border-radius: 12px; font-size: 20px;
        }
        .ic-blue { color: var(--primary); background: #edf3ff; }
        .ic-green { color: var(--green); background: #e9f8f2; }
        .ic-orange { color: var(--orange); background: #fff4e7; }
        .ic-purple { color: var(--purple); background: #f2edff; }
        .stat-label { color: var(--muted); font-size: 13px; font-weight: 600; margin-bottom: 5px; }
        .stat-value { color: var(--ink); font: 800 28px 'Plus Jakarta Sans', sans-serif; line-height: 1.1; }
        .stat-foot { margin-top: 7px; color: #8b96a6; font-size: 12px; }
        .trend { color: var(--green); font-weight: 700; }
        .panel { padding: 22px; height: 100%; }
        .panel-heading { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 20px; }
        .panel-title { display: flex; align-items: center; gap: 9px; font: 700 16px 'Plus Jakarta Sans', sans-serif; margin: 0; }
        .panel-title i { color: var(--primary); font-size: 16px; }
        .panel-subtitle { color: var(--muted); font-size: 12px; margin-top: 4px; }
        .panel-action { color: var(--primary); font-size: 12px; font-weight: 700; }

        /* Chart area */
        .chart-filter {
            border: 1px solid var(--line); background: #fff; color: #68778d;
            border-radius: 7px; padding: 6px 10px; font-size: 12px; outline: none;
        }
        .chart-box { height: 260px; position: relative; }
        .chart-legend { display: flex; gap: 16px; color: #738096; font-size: 12px; margin: 0 0 8px 3px; }
        .legend-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 5px; }

        /* Lists and activity */
        .notification-list { display: flex; flex-direction: column; gap: 10px; }
        .notification-item {
            display: flex; align-items: flex-start; gap: 12px; padding: 14px;
            border: 1px solid #edf0f5; border-radius: 10px; background: #fbfcfe; cursor: pointer; transition: .18s;
        }
        .notification-item:hover { border-color: #d8e4ff; background: #f7faff; }
        .notification-item.unread { background: #f4f8ff; border-color: #e2ebff; }
        .notification-icon {
            width: 34px; height: 34px; flex: 0 0 34px; display: grid; place-items: center;
            border-radius: 9px; color: var(--primary); background: #eaf1ff; font-size: 14px;
        }
        .notification-title { color: #344258; font-size: 13px; font-weight: 700; }
        .notification-message { color: #8591a1; font-size: 12px; margin-top: 4px; line-height: 1.45; }
        .notification-time { margin-left: auto; color: #9aa4b2; font-size: 10px; white-space: nowrap; }
        .activity-list { list-style: none; padding: 0; margin: 0; }
        .activity-list li { display: flex; gap: 12px; padding: 0 0 17px; position: relative; }
        .activity-list li:not(:last-child):before {
            content: ''; position: absolute; left: 14px; top: 30px; bottom: 0; width: 1px; background: #e8edf4;
        }
        .activity-bullet {
            z-index: 1; width: 29px; height: 29px; flex: 0 0 29px; display: grid; place-items: center;
            border-radius: 9px; color: var(--primary); background: #edf3ff; font-size: 11px;
        }
        .activity-text { color: #435168; font-size: 13px; font-weight: 600; line-height: 1.4; }
        .activity-time { color: #9aa4b2; font-size: 11px; margin-top: 4px; }

        /* Quick actions */
        .quick-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 11px; }
        .quick-action {
            display: flex; align-items: center; gap: 11px; padding: 14px 12px;
            border: 1px solid var(--line); border-radius: 10px; color: #435168;
            background: #fff; transition: .18s; min-height: 66px;
        }
        .quick-action:hover { transform: translateY(-2px); border-color: #cbdcff; box-shadow: 0 7px 17px rgba(53,109,243,.08); color: var(--primary); }
        .quick-action i { width: 34px; height: 34px; display: grid; place-items: center; border-radius: 9px; color: var(--primary); background: #edf3ff; font-size: 14px; }
        .quick-action span { font-size: 12px; font-weight: 700; line-height: 1.3; }

        /* Department and attendance */
        .dept-layout { display: flex; align-items: center; gap: 14px; }
        .dept-chart { width: 48%; height: 185px; position: relative; }
        .dept-legend { flex: 1; display: flex; flex-direction: column; gap: 12px; }
        .dept-row { display: flex; align-items: center; justify-content: space-between; gap: 8px; font-size: 12px; color: #68768a; }
        .dept-name { display: flex; align-items: center; gap: 7px; }
        .dept-row strong { color: #344258; font-size: 13px; }
        .attendance-summary { display: flex; gap: 9px; margin-top: 15px; }
        .attendance-pill { flex: 1; border-radius: 9px; padding: 12px; background: #f7f9fc; }
        .attendance-pill strong { display: block; font: 700 20px 'Plus Jakarta Sans', sans-serif; }
        .attendance-pill span { color: var(--muted); font-size: 11px; }

        .empty-state { text-align: center; padding: 24px 10px; color: #9aa5b4; font-size: 13px; }
        .empty-state i { display: block; font-size: 26px; margin-bottom: 8px; color: #c0cad7; }
        .footer-note { text-align: center; color: #a0aaba; font-size: 11px; padding: 22px 0 2px; }

        @media (max-width: 1199px) {
            :root { --sidebar-width: 240px; }
            .main-content { padding-left: 22px; padding-right: 22px; }
        }
        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); box-shadow: 15px 0 35px rgba(10,25,45,.2); }
            .main-content { margin-left: 0; }
            .mobile-menu { display: inline-grid; place-items: center; }
            .sidebar-overlay { display: none; position: fixed; inset: 0; z-index: 1030; background: rgba(9,20,35,.42); }
            .sidebar-overlay.show { display: block; }
        }
        @media (max-width: 575px) {
            .main-content { padding: 0 14px 20px; }
            .topbar { min-height: 68px; margin-bottom: 21px; }
            .search-wrap { width: min(210px, 48vw); }
            .profile-meta, .top-divider { display: none; }
            .page-heading { align-items: flex-start; flex-direction: column; }
            .page-heading h1 { font-size: 24px; }
            .date-chip { padding: 8px 10px; font-size: 12px; }
            .stat-card { min-height: 105px; padding: 14px; gap: 10px; }
            .stat-icon { width: 42px; height: 42px; flex-basis: 42px; font-size: 16px; }
            .stat-value { font-size: 22px; }
            .panel { padding: 16px; }
            .chart-box { height: 215px; }
            .dept-layout { flex-direction: column; }
            .dept-chart { width: 100%; height: 165px; }
            .dept-legend { width: 100%; }
        }
    </style>
</head>
<body>
<div class="app-shell">
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <aside class="sidebar" id="sidebar">
        <a class="brand" href="hr-dashboard.php">
            <span class="brand-mark"><i class="fa-solid fa-people-group"></i></span>
            <span>
                <span class="brand-name d-block">KormoShathi</span>
                <span class="brand-sub d-block">HR MANAGEMENT SYSTEM</span>
            </span>
        </a>

        <div class="nav-caption">Workspace</div>
        <ul class="side-nav">
            <li><a class="side-link active" href="hr-dashboard.php"><i class="fa-solid fa-table-cells-large"></i> Dashboard</a></li>
            <li><a class="side-link" href="modules/chat.php"><i class="fa-regular fa-comments"></i> Messages</a></li>
        </ul>

        <div class="nav-caption">HR Operations</div>
        <ul class="side-nav">
            <li><a class="side-link" href="modules/add-employee.php"><i class="fa-solid fa-user-plus"></i> Add Employee</a></li>
            <li><a class="side-link" href="modules/employees.php"><i class="fa-solid fa-users"></i> Employees</a></li>
            <li><a class="side-link" href="modules/attendance.php"><i class="fa-solid fa-fingerprint"></i> Attendance</a></li>
            
            <li><a class="side-link" href="modules/leave.php"><i class="fa-regular fa-calendar-check"></i> Leave Management</a></li>
            <li><a class="side-link" href="modules/payroll.php"><i class="fa-solid fa-wallet"></i> Payroll</a></li>
            <li><a class="side-link" href="modules/payroll-config.php"><i class="fa-solid fa-sliders"></i> Payroll Configuration</a></li>
            <li><a class="side-link" href="modules/hr-holidays.php"><i class="fa-solid fa-umbrella-beach"></i> Manage Holidays</a></li>
        </ul>

        <div class="nav-caption">Recruitment</div>
        <ul class="side-nav">
            <li><a class="side-link" href="modules/ai-screening-form.php"><i class="fa-solid fa-robot"></i> AI Screening</a></li>
            <li><a class="side-link" href="modules/hr-submissions.php"><i class="fa-solid fa-file-circle-check"></i> Submissions</a></li>
        </ul>

        <div class="nav-caption">People & Insights</div>
        <ul class="side-nav">
            <li><a class="side-link" href="modules/performance.php"><i class="fa-solid fa-chart-line"></i> Performance</a></li>
            <li><a class="side-link" href="modules/reports.php"><i class="fa-solid fa-chart-pie"></i> Reports</a></li>
            <li><a class="side-link" href="modules/notice-board.php"><i class="fa-solid fa-bullhorn"></i> Notice Board</a></li>
            <li><a class="side-link" href="modules/employee-id-card-list.php"><i class="fa-regular fa-id-card"></i> Employee ID Cards</a></li>
        </ul>

        <div class="nav-caption">System</div>
        <ul class="side-nav">
            <!-- 🟢 HR Settings Link -->
            <li><a class="side-link" href="modules/hr-settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
        </ul>

        <div class="sidebar-bottom">
            <div class="sidebar-help">
                <div class="help-icon"><i class="fa-regular fa-circle-question"></i></div>
                <strong>Need a hand?</strong>
                <small>Find help with your HR workspace.</small>
                <a href="modules/notice-board.php">Open notice board <i class="fa-solid fa-arrow-right ms-1"></i></a>
            </div>
            <ul class="side-nav">
                <li><a class="side-link" href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li>
            </ul>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <button class="mobile-menu" type="button" onclick="toggleSidebar()" aria-label="Open menu"><i class="fa-solid fa-bars"></i></button>
                <h4 class="fw-bold m-0 d-flex align-items-center gap-2 me-3" style="font-family: 'Plus Jakarta Sans', sans-serif;"><i class="fa-solid fa-gauge-high text-primary"></i> HR Dashboard</h4>
                <div class="search-wrap">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" id="dashboardSearch" placeholder="Search employees, modules..." aria-label="Search">
                </div>
            </div>
            <div class="topbar-right">
                <button class="top-icon" type="button" onclick="loadNotifications()" title="Notifications" aria-label="Notifications">
                    <i class="fa-regular fa-bell"></i><span class="dot" id="notificationDot" style="display:none"></span>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationBadge" style="display:none;font-size:10px">0</span>
                </button>
                <span class="top-divider"></span>
                
                <button class="profile-button" type="button" aria-label="HR profile">
                    <?php if (!empty($pic)): ?>
                        <img src="<?php echo htmlspecialchars($pic, ENT_QUOTES, 'UTF-8'); ?>" class="avatar" alt="Profile">
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
        </header>

        <section class="page-heading">
            <div>
                <div class="eyebrow">People overview</div>
                <h1>HR Dashboard</h1>
                <p>Welcome back, <?php echo htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8'); ?>. Here's what's happening with your people today.</p>
            </div>
            
            <div class="d-flex gap-2">
                <div class="date-chip"><i class="fa-regular fa-calendar"></i><?php echo date('D, M d, Y'); ?></div>
                <div class="date-chip" id="liveClock" style="border-color: #a9c2ff; background: #f4f8ff;">
                    <i class="fa-regular fa-clock" style="color: #356df3;"></i> <span>--:--:--</span>
                </div>
            </div>
        </section>

        <!-- KPI cards -->
        <section class="row g-3 mb-4">
            <div class="col-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon ic-blue"><i class="fa-solid fa-users"></i></div>
                    <div><div class="stat-label">Total Employees</div><div class="stat-value" id="totalEmployees">—</div><div class="stat-foot"><span class="trend"><i class="fa-solid fa-arrow-up"></i> Workforce</span> overview</div></div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon ic-green"><i class="fa-solid fa-user-check"></i></div>
                    <div><div class="stat-label">Present Today</div><div class="stat-value" id="presentToday">—</div><div class="stat-foot">Today's attendance</div></div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon ic-orange"><i class="fa-solid fa-plane-departure"></i></div>
                    <div><div class="stat-label">On Leave</div><div class="stat-value" id="onLeave">—</div><div class="stat-foot">Employees away today</div></div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon ic-purple"><i class="fa-solid fa-hourglass-half"></i></div>
                    <div><div class="stat-label">Pending Leave Requests</div><div class="stat-value" id="pendingLeaves">—</div><div class="stat-foot">Awaiting HR action</div></div>
                </div>
            </div>
        </section>

        <section class="row g-3 mb-3">
            <div class="col-xl-8">
                <div class="panel">
                    <div class="panel-heading">
                        <div><h2 class="panel-title"><i class="fa-solid fa-chart-line"></i> Employee Overview</h2><div class="panel-subtitle">Workforce growth throughout the year</div></div>
                        <select class="chart-filter" id="employeeChartRange" aria-label="Chart range">
                            <option value="year">This year</option>
                            <option value="six">Last 6 months</option>
                        </select>
                    </div>
                    <div class="chart-legend">
                        <span><i class="legend-dot" style="background:#356df3"></i>Employees</span>
                    </div>
                    <div class="chart-box"><canvas id="employeeChart"></canvas></div>
                </div>
            </div>
            <div class="col-xl-4">
                <div class="panel">
                    <div class="panel-heading">
                        <div><h2 class="panel-title"><i class="fa-solid fa-wand-magic-sparkles"></i> Quick Actions</h2><div class="panel-subtitle">Common HR tasks</div></div>
                    </div>
                    <div class="quick-grid">
                        <a class="quick-action" href="modules/add-employee.php"><i class="fa-solid fa-user-plus"></i><span>Add<br>Employee</span></a>
                        <a class="quick-action" href="modules/attendance.php"><i class="fa-solid fa-fingerprint"></i><span>Manage<br>Attendance</span></a>
                        <a class="quick-action" href="modules/leave.php"><i class="fa-solid fa-calendar-check"></i><span>Review<br>Leaves</span></a>
                        <a class="quick-action" href="modules/payroll.php"><i class="fa-solid fa-wallet"></i><span>Process<br>Payroll</span></a>
                        <a class="quick-action" href="modules/ai-screening-form.php"><i class="fa-solid fa-robot"></i><span>AI Resume<br>Screening</span></a>
                        <a class="quick-action" href="modules/reports.php"><i class="fa-solid fa-chart-pie"></i><span>Generate<br>Reports</span></a>
                    </div>
                    <div class="mt-3 p-3 rounded-3" style="background:#f7f9fd">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fa-solid fa-lightbulb" style="color:#e8a23e"></i>
                            <span class="fw-bold" style="font-size:12px">HR tip</span>
                        </div>
                        <div class="text-muted mt-1" style="font-size:11px;line-height:1.5">Keep employee records and attendance up to date for more accurate HR reports.</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="row g-3 mb-3">
            <div class="col-xl-7">
                <div class="panel">
                    <div class="panel-heading">
                        <div><h2 class="panel-title"><i class="fa-regular fa-bell"></i> Recent Notifications</h2><div class="panel-subtitle">Updates that may need your attention</div></div>
                        <span class="badge rounded-pill text-bg-light border" id="notificationCount" style="font-size:12px">0 notifications</span>
                    </div>
                    <div class="notification-list" id="notifications">
                        <div class="empty-state"><i class="fa-solid fa-spinner fa-spin"></i>Loading notifications...</div>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="panel">
                    <div class="panel-heading">
                        <div><h2 class="panel-title"><i class="fa-solid fa-clock-rotate-left"></i> Recent Activities</h2><div class="panel-subtitle">Latest workspace activity</div></div>
                        <a href="modules/reports.php" class="panel-action">View reports <i class="fa-solid fa-arrow-right ms-1"></i></a>
                    </div>
                    <ul class="activity-list" id="recentActivitiesList">
                        <li class="empty-state"><i class="fa-solid fa-spinner fa-spin"></i>Loading activities...</li>
                    </ul>
                </div>
            </div>
        </section>

        <section class="row g-3">
            <div class="col-xl-5">
                <div class="panel">
                    <div class="panel-heading">
                        <div><h2 class="panel-title"><i class="fa-solid fa-building"></i> Department Distribution</h2><div class="panel-subtitle">Employee distribution by department</div></div>
                    </div>
                    <div class="dept-layout">
                        <div class="dept-chart"><canvas id="deptChart"></canvas></div>
                        <div class="dept-legend" id="deptLegend">
                            <!-- Legend data will be dynamically injected here -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-7">
                <div class="panel">
                    <div class="panel-heading">
                        <div><h2 class="panel-title"><i class="fa-solid fa-chart-simple"></i> Today's Attendance Snapshot</h2><div class="panel-subtitle">Quick view of today's workforce status</div></div>
                        <a href="modules/attendance.php" class="panel-action">Open attendance <i class="fa-solid fa-arrow-right ms-1"></i></a>
                    </div>
                    <div class="attendance-summary">
                        <div class="attendance-pill"><strong id="attendancePresent" style="color:var(--green)">—</strong><span>Present</span></div>
                        <div class="attendance-pill"><strong id="attendanceLeave" style="color:var(--orange)">—</strong><span>On leave</span></div>
                        <div class="attendance-pill"><strong id="attendancePending" style="color:var(--purple)">—</strong><span>Pending leaves</span></div>
                    </div>
                    <div class="mt-4">
                        <div class="d-flex justify-content-between mb-2"><span class="text-muted" style="font-size:12px">Attendance coverage</span><strong id="attendancePercent" style="font-size:13px;color:var(--ink)">—</strong></div>
                        <div class="progress" style="height:9px;border-radius:20px;background:#edf1f6">
                            <div class="progress-bar" id="attendanceProgress" role="progressbar" style="width:0%;background:linear-gradient(90deg,#356df3,#6d9aff);border-radius:20px"></div>
                        </div>
                        <p class="text-muted mt-3 mb-0" style="font-size:12px">Attendance figures are refreshed from your dashboard statistics API.</p>
                    </div>
                </div>
            </div>
        </section>

        
<div class="footer-note">© <?php echo date('Y'); ?> KormoShathi HR Management System · People first, always.</div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    let realtimeInterval = null;
    let employeeChartInstance = null;
    let deptChartInstance = null;

    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('active');
        document.getElementById('sidebarOverlay').classList.toggle('show');
    }

    function safeNumber(value) {
        const number = Number(value);
        return Number.isFinite(number) ? number : 0;
    }

    function updateLiveClock() {
        const now = new Date();
        let hours = now.getHours();
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        
        hours = hours % 12;
        hours = hours ? hours : 12; 
        const strHours = String(hours).padStart(2, '0');
        
        document.getElementById('liveClock').innerHTML = `<i class="fa-regular fa-clock" style="color: #356df3;"></i> ${strHours}:${minutes}:${seconds} ${ampm}`;
    }

    async function loadDashboardStats() {
        try {
            const response = await fetch('api/dashboard-stats.php', { headers: { 'Accept': 'application/json' } });
            if (!response.ok) throw new Error('Dashboard stats request failed');
            const data = await response.json();

            if (data.success && data.stats) {
                const total = safeNumber(data.stats.total_employees);
                const present = safeNumber(data.stats.present_today);
                const leave = safeNumber(data.stats.on_leave);
                const pending = safeNumber(data.stats.pending_leaves);

                document.getElementById('totalEmployees').textContent = total.toLocaleString();
                document.getElementById('presentToday').textContent = present.toLocaleString();
                document.getElementById('onLeave').textContent = leave.toLocaleString();
                document.getElementById('pendingLeaves').textContent = pending.toLocaleString();

                document.getElementById('attendancePresent').textContent = present.toLocaleString();
                document.getElementById('attendanceLeave').textContent = leave.toLocaleString();
                document.getElementById('attendancePending').textContent = pending.toLocaleString();

                const percentage = total > 0 ? Math.min(100, Math.round((present / total) * 100)) : 0;
                document.getElementById('attendancePercent').textContent = percentage + '%';
                document.getElementById('attendanceProgress').style.width = percentage + '%';

                if (data.chart_data && employeeChartInstance) {
                    employeeChartInstance.data.labels = data.chart_data.labels || [];
                    employeeChartInstance.data.datasets[0].data = data.chart_data.values || [];
                    employeeChartInstance.update();
                }
                
                if (data.dept_data && deptChartInstance) {
                    deptChartInstance.data.labels = data.dept_data.labels || [];
                    deptChartInstance.data.datasets[0].data = data.dept_data.values || [];
                    deptChartInstance.update();

                    const legendContainer = document.getElementById('deptLegend');
                    legendContainer.innerHTML = '';
                    const colors = ['#356df3', '#16a675', '#f39a38', '#8262df', '#e45d68', '#17a2b8'];
                    
                    if (data.dept_data.labels && data.dept_data.values) {
                        data.dept_data.labels.forEach((label, index) => {
                            const val = data.dept_data.values[index];
                            const color = colors[index % colors.length];
                            legendContainer.innerHTML += `
                                <div class="dept-row">
                                    <span class="dept-name"><i class="legend-dot" style="background:${color}"></i>${label}</span>
                                    <strong>${val}</strong>
                                </div>`;
                        });
                    }
                }
            }
        } catch (error) {
            console.error('Error loading dashboard stats:', error);
        }
    }

    async function loadRecentActivities() {
        const container = document.getElementById('recentActivitiesList');
        try {
            const response = await fetch('api/employee-activity.php', { headers: { 'Accept': 'application/json' } });
            if(!response.ok) throw new Error('Not found');
            const data = await response.json();
            
            if (data.success && data.data && data.data.length > 0) {
                 container.innerHTML = data.data.slice(0, 4).map((act, i) => {
                     const icons = ['fa-user-plus', 'fa-fingerprint', 'fa-calendar-check', 'fa-file-invoice-dollar'];
                     const colors = ['var(--primary);background:#edf3ff', 'var(--green);background:#e9f8f2', 'var(--orange);background:#fff4e7', 'var(--purple);background:#f2edff'];
                     const icon = icons[i % icons.length];
                     const colorStyle = colors[i % colors.length];
                     return `<li>
                        <span class="activity-bullet" style="color:${colorStyle}"><i class="fa-solid ${icon}"></i></span>
                        <div>
                            <div class="activity-text">${act.description || act.message}</div>
                            <div class="activity-time">${new Date(act.created_at).toLocaleString()}</div>
                        </div>
                     </li>`;
                 }).join('');
            } else {
                container.innerHTML = '<li class="empty-state"><i class="fa-regular fa-folder-open"></i>No recent activities found.</li>';
            }
        } catch(e) {
            container.innerHTML = '<li class="empty-state"><i class="fa-regular fa-folder-open"></i>No recent activities found.</li>';
        }
    }

    async function loadNotificationCount() {
        try {
            const response = await fetch('api/notifications.php?count=1', { headers: { 'Accept': 'application/json' } });
            const data = await response.json();
            const badge = document.getElementById('notificationBadge');
            const dot = document.getElementById('notificationDot');
            if (data.success && safeNumber(data.unread) > 0) {
                badge.style.display = 'inline-block'; badge.textContent = data.unread; dot.style.display = 'block';
            } else {
                badge.style.display = 'none'; dot.style.display = 'none';
            }
        } catch (error) {}
    }

    async function loadRecentNotifications() {
        const container = document.getElementById('notifications');
        const countElement = document.getElementById('notificationCount');
        try {
            const response = await fetch('api/notifications.php', { headers: { 'Accept': 'application/json' } });
            const data = await response.json();

            if (data.success && Array.isArray(data.data) && data.data.length > 0) {
                countElement.textContent = data.data.length + ' notifications';
                container.innerHTML = data.data.slice(0, 6).map(n => {
                    let icon = 'fa-circle-info';
                    if (n.type === 'success') icon = 'fa-circle-check';
                    else if (n.type === 'danger') icon = 'fa-circle-exclamation';
                    else if (n.type === 'warning') icon = 'fa-triangle-exclamation';
                    const unreadClass = n.is_read ? '' : 'unread';
                    const title = escapeHtml(n.title || 'Notification');
                    const message = escapeHtml(n.message || '');
                    const time = n.created_at ? new Date(n.created_at).toLocaleString() : '';
                    return `<div class="notification-item ${unreadClass}" onclick="markAsRead(${Number(n.id)})">
                        <span class="notification-icon"><i class="fa-solid ${icon}"></i></span>
                        <div><div class="notification-title">${title}</div><div class="notification-message">${message}</div></div>
                        <span class="notification-time">${escapeHtml(time)}</span>
                    </div>`;
                }).join('');
            } else {
                countElement.textContent = '0 notifications';
                container.innerHTML = '<div class="empty-state"><i class="fa-regular fa-bell-slash"></i>You are all caught up. No new notifications.</div>';
            }
        } catch (error) {
            countElement.textContent = 'Unavailable';
            container.innerHTML = '<div class="empty-state"><i class="fa-solid fa-circle-exclamation"></i>Could not load notifications right now.</div>';
        }
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, char => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
        })[char]);
    }

    async function markAsRead(id) {
        try {
            await fetch('api/notifications.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ id: id })
            });
            await Promise.all([loadRecentNotifications(), loadNotificationCount()]);
        } catch (error) {}
    }

    async function loadNotifications() {
        await Promise.all([loadRecentNotifications(), loadNotificationCount()]);
        const firstNotification = document.querySelector('.notification-item');
        if (firstNotification) {
            firstNotification.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        } else {
            alert('No notifications found.');
        }
    }

    function initCharts() {
        const employeeCanvas = document.getElementById('employeeChart');
        if (employeeCanvas && window.Chart) {
            employeeChartInstance = new Chart(employeeCanvas, {
                type: 'line',
                data: {
                    labels: [], 
                    datasets: [{
                        label: 'Employees',
                        data: [], 
                        borderColor: '#356df3',
                        backgroundColor: 'rgba(53,109,243,.09)',
                        borderWidth: 2.5,
                        fill: true,
                        tension: .38,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#fff',
                        pointBorderColor: '#356df3',
                        pointBorderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false }, tooltip: { displayColors: false } },
                    scales: {
                        y: { beginAtZero: true, grid: { color: '#eef1f6', drawBorder: false }, ticks: { color: '#9aa5b4', font: { size: 12 } } },
                        x: { grid: { display: false }, ticks: { color: '#9aa5b4', font: { size: 12 } } }
                    }
                }
            });
        }

        const deptCanvas = document.getElementById('deptChart');
        if (deptCanvas && window.Chart) {
            deptChartInstance = new Chart(deptCanvas, {
                type: 'doughnut',
                data: {
                    labels: [], 
                    datasets: [{
                        data: [], 
                        backgroundColor: ['#356df3', '#16a675', '#f39a38', '#8262df', '#e45d68', '#17a2b8'],
                        borderWidth: 3,
                        borderColor: '#fff',
                        hoverOffset: 5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '72%',
                    plugins: { legend: { display: false }, tooltip: { padding: 10 } }
                }
            });
        }
    }

    document.getElementById('employeeChartRange').addEventListener('change', function () {
        // Backend কল করে range অনুযায়ী ডাটা ফিল্টার করার অপশন রাখতে পারেন ভবিষ্যতে
    });

    document.addEventListener('DOMContentLoaded', () => {
        updateLiveClock(); 
        setInterval(updateLiveClock, 1000); 

        initCharts();
        loadDashboardStats();
        loadRecentActivities();
        loadNotificationCount();
        loadRecentNotifications();
        
        realtimeInterval = setInterval(() => {
            loadDashboardStats();
            loadNotificationCount();
            loadRecentNotifications();
        }, 15000);
    });
</script>
</body>
</html>