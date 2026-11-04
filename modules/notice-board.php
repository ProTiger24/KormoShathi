<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';
$database = new Database();
$db = $database->getConnection();

// Fetch Profile for Topbar
$stmt = $db->prepare("SELECT first_name, last_name, profile_picture, position FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$firstName = $_SESSION['first_name'] ?? ($user['first_name'] ?? 'User');
$lastName = $_SESSION['last_name'] ?? ($user['last_name'] ?? '');
$fullName = trim($firstName . ' ' . $lastName);
$initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
$pic = $user['profile_picture'] ?? '';
$position = $_SESSION['position'] ?? ($user['position'] ?? 'Employee');

$is_hr = ($_SESSION['user_type'] === 'hr');
$dashboardUrl = $is_hr ? '../hr-dashboard.php' : '../employee-dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notice Board | KormoShathi</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        :root {
            --navy: #14243a; --primary: #356df3; --primary-soft: #edf3ff; --ink: #17263c;
            --muted: #7b8798; --line: #e9edf3; --surface: #ffffff; --page: #f5f7fb;
            --green: #10b981; --orange: #f59e0b; --red: #ef4444; --purple: #8b5cf6; --blue: #3b82f6;
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

        /* ================= PAGE HEADING & PANELS ================= */
        .page-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; margin-bottom: 22px; }
        .eyebrow { color: var(--primary); font-size: 11px; font-weight: 800; letter-spacing: 1.25px; text-transform: uppercase; margin-bottom: 7px; }
        h1 { font-family: 'Plus Jakarta Sans', sans-serif; color: var(--ink); font-size: 28px; font-weight: 800; letter-spacing: -.8px; margin: 0; }
        .page-heading p { color: var(--muted); margin: 6px 0 0; font-size: 14px; }

        .panel { background: var(--surface); border: 1px solid var(--line); border-radius: 13px; box-shadow: var(--shadow); padding: 24px; margin-bottom: 24px; }
        .panel-header { border-bottom: 1px solid var(--line); padding-bottom: 15px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .panel-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 16px; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 8px; }
        .panel-title i { color: var(--primary); }

        /* Form Styles */
        .form-label { font-weight: 600; font-size: 14px; color: #435168; margin-bottom: 8px; }
        .form-control { border: 1px solid var(--line); padding: 12px 15px; border-radius: 8px; font-size: 14px; background-color: #fbfcfe; transition: 0.2s; height: 100%; }
        .form-control:focus { border-color: var(--primary); background-color: #fff; box-shadow: 0 0 0 3px rgba(53,109,243,0.1); }
        .btn-submit { background: var(--primary); border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 15px; transition: 0.2s; color: white; height: 100%; width: 100%; }
        .btn-submit:hover { background: #2859d9; transform: translateY(-2px); }

        /* Notice Card */
        .notice-card { background: #fff; border: 1px solid var(--line); border-radius: 12px; padding: 20px; margin-bottom: 15px; transition: 0.2s; border-left: 4px solid var(--muted); cursor: pointer; }
        .notice-card:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .notice-card.unread { border-left-color: var(--primary); background: #f8faff; }
        .notice-card.read { border-left-color: var(--green); background: #fff; }

        .notice-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 16px; font-weight: 800; color: var(--ink); margin-bottom: 5px; }
        .notice-message { font-size: 14px; color: #475569; line-height: 1.6; margin-bottom: 12px; }
        .notice-meta { font-size: 12px; color: var(--muted); display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
        
        .badge-read { background: #ecfdf5; color: #10b981; border: 1px solid #a7f3d0; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
        .badge-unread { background: #eff6ff; color: #3b82f6; border: 1px solid #bfdbfe; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }

        .delete-btn { background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; transition: 0.2s; cursor: pointer; }
        .delete-btn:hover { background: #ef4444; color: white; }

        /* Empty State */
        .empty-state { text-align: center; padding: 50px 20px; color: var(--muted); }
        .empty-state i { font-size: 50px; color: #cbd5e1; margin-bottom: 15px; display: block; }

        /* Pagination */
        .pagination { margin-bottom: 0; } .page-link { border: none; color: var(--muted); font-weight: 600; border-radius: 8px !important; margin: 0 3px; padding: 8px 14px; transition: 0.2s; cursor: pointer; } .page-link:hover { background: var(--primary-soft); color: var(--primary); } .page-item.active .page-link { background: var(--primary); color: white; box-shadow: 0 4px 10px rgba(53,109,243,0.3); } .page-item.disabled .page-link { background: transparent; color: #cbd5e1; cursor: not-allowed; }

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
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 0 20px 20px; }
            .sidebar-overlay.active { display: block; }
            .mobile-menu { display: inline-grid; place-items: center; }
            .profile-meta { display: none; }
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
            <span><span class="brand-name d-block">KormoShathi</span><span class="brand-sub d-block">HR MANAGEMENT SYSTEM</span></span>
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
                <li><a class="side-link active" href="notice-board.php"><i class="fa-solid fa-bullhorn"></i> Notice Board</a></li>
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
                <li><a href="my-payroll.php" class="side-link"><i class="fa-solid fa-wallet"></i> My Salary & Payslip</a></li>
                <li><a href="tasks.php" class="side-link"><i class="fa-solid fa-list-check"></i> My Tasks</a></li>
                <li><a href="performance.php" class="side-link"><i class="fa-solid fa-chart-line"></i> Performance</a></li>
                <li><a href="employee-settings.php" class="side-link"><i class="fa-solid fa-gear"></i> Settings</a></li>
                <li><a href="../profile.php" class="side-link"><i class="fa-solid fa-user"></i> My Profile</a></li>
            </ul>

            <div class="nav-caption">Company</div>
            <ul class="side-nav">
                <li><a href="notice-board.php" class="side-link active"><i class="fa-solid fa-bullhorn"></i> Notice & Events</a></li>
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
                    <h4 class="fw-bold m-0 d-flex align-items-center gap-2 me-3" style="font-family: 'Plus Jakarta Sans', sans-serif;"><i class="fa-solid fa-bullhorn text-primary"></i> Notice Board</h4>
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
                    <h4 class="fw-bold m-0 d-flex align-items-center gap-2" style="font-family: 'Plus Jakarta Sans', sans-serif;"><i class="fa-solid fa-bullhorn text-primary"></i> Notice & Events</h4>
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
                <div class="eyebrow">Announcements</div>
                <h1>Company Notices</h1>
                <p class="text-muted mb-0">Stay updated with the latest news and announcements.</p>
            </div>
        </section>

        <?php if ($is_hr): ?>
        <!-- Post Notice Form (Inline) -->
        <div class="panel mb-4">
            <div class="panel-header">
                <h5 class="panel-title"><i class="fa-solid fa-bullhorn"></i> Post a New Notice</h5>
            </div>
            <form id="noticeForm">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-3 col-md-12">
                        <label class="form-label">Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" placeholder="Enter notice title" required>
                    </div>
                    <div class="col-lg-6 col-md-12">
                        <label class="form-label">Message <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="message" placeholder="Enter notice details..." required>
                    </div>
                    <div class="col-lg-3 col-md-12">
                        <button type="submit" class="btn-submit"><i class="fa-solid fa-paper-plane me-2"></i> Publish Notice</button>
                    </div>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- All Notices List -->
        <div class="panel">
            <div class="panel-header">
                <h5 class="panel-title"><i class="fa-solid fa-list-ul"></i> Recent Announcements</h5>
                <span class="text-muted fw-bold" style="font-size: 13px;" id="noticeCount">0 notices</span>
            </div>
            
            <div id="noticesList">
                <div class="empty-state">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    <p class="mb-0">Loading notices...</p>
                </div>
            </div>

            <!-- Pagination Container -->
            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top" id="paginationContainer" style="display: none;">
                <div class="text-muted fw-medium" style="font-size: 13px;" id="paginationInfo"></div>
                <nav><ul class="pagination" id="paginationControls"></ul></nav>
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
                    <button class="btn btn-primary btn-sm rounded-pill px-3 fw-bold" onclick="markAllAsReadAndClose()"><i class="fa-solid fa-check-double me-1"></i> Mark All as Read</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let notificationModal = null;
        function toggleSidebar() { document.getElementById('sidebar').classList.toggle('active'); document.getElementById('sidebarOverlay').classList.toggle('active'); }

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
                if (notifs.length === 0) { container.innerHTML = `<div class="text-center py-5 text-muted"><i class="fa-regular fa-bell-slash fs-1 mb-3"></i><h6 class="fw-bold">All caught up!</h6></div>`; return; }
                
                container.innerHTML = notifs.map(n => {
                    const unread = Number(n.is_read) === 0;
                    const icon = n.type === 'success' ? 'fa-circle-check text-success' : (n.type === 'danger' ? 'fa-circle-exclamation text-danger' : 'fa-circle-info text-primary');
                    return `<div class="notification-item ${unread ? 'unread' : ''} mb-2" onclick="markAsRead(${n.id})" style="padding:12px; border:1px solid ${unread ? '#bfdbfe' : '#e2e8f0'}; border-radius:8px; background:${unread ? '#eff6ff' : '#fff'}; cursor:pointer;"><div class="d-flex align-items-start gap-2"><i class="fa-solid ${icon} mt-1"></i><div><div class="fw-bold text-dark" style="font-size:13px;">${escapeHTML(n.title)}</div><div class="text-muted" style="font-size:12px;">${escapeHTML(n.message)}</div><div class="text-secondary" style="font-size:10px; margin-top:4px;">${formatTime(n.created_at)}</div></div></div></div>`;
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
        // Notice Board Logic
        // ==========================================
        const isHR = <?php echo $is_hr ? 'true' : 'false'; ?>;
        let allNotices = [];
        let currentPage = 1;
        const itemsPerPage = 4;

        async function loadNotices() {
            try {
                const response = await fetch('../api/notices.php');
                const data = await response.json();
                
                if(data.success && data.data && data.data.length > 0) {
                    allNotices = data.data;
                    document.getElementById('noticeCount').textContent = allNotices.length + ' notices';
                    document.getElementById('paginationContainer').style.display = 'flex';
                    
                    if (currentPage > Math.ceil(allNotices.length / itemsPerPage) && currentPage > 1) {
                        currentPage--;
                    }
                    renderNotices(currentPage);
                } else {
                    document.getElementById('noticeCount').textContent = '0 notices';
                    document.getElementById('paginationContainer').style.display = 'none';
                    document.getElementById('noticesList').innerHTML = `
                        <div class="empty-state">
                            <i class="fa-regular fa-bell-slash"></i>
                            <h6 class="fw-bold">No Notices Found</h6>
                            <p class="mb-0">There are currently no company announcements.</p>
                        </div>
                    `;
                }
            } catch(e) { 
                document.getElementById('noticesList').innerHTML = '<p class="text-danger text-center py-3">Error loading notices</p>';
            }
        }

        function renderNotices(page) {
            const container = document.getElementById('noticesList');
            const start = (page - 1) * itemsPerPage;
            const end = start + itemsPerPage;
            const paginatedNotices = allNotices.slice(start, end);

            container.innerHTML = paginatedNotices.map(notice => {
                const isRead = notice.is_read > 0;
                const readClass = isRead ? 'read' : 'unread';
                const readBadge = isRead ? 
                    '<span class="badge-read"><i class="fa-solid fa-check me-1"></i> Read</span>' : 
                    '<span class="badge-unread"><i class="fa-solid fa-circle me-1"></i> New</span>';
                
                const deleteBtn = isHR ? 
                    `<button class="delete-btn ms-3" onclick="event.stopPropagation(); deleteNotice(${notice.id})" title="Delete">
                        <i class="fa-solid fa-trash"></i>
                    </button>` : '';
                
                return `
                    <div class="notice-card ${readClass}" onclick="markNoticeAsRead(${notice.id})">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div class="notice-title">${notice.title}</div>
                                    ${readBadge}
                                </div>
                                <div class="notice-message">${notice.message || notice.content}</div>
                                <div class="notice-meta">
                                    <span><i class="fa-regular fa-user me-1"></i> ${notice.first_name || 'HR'} ${notice.last_name || ''}</span>
                                    <span><i class="fa-regular fa-clock me-1"></i> ${new Date(notice.created_at).toLocaleString()}</span>
                                </div>
                            </div>
                            ${deleteBtn}
                        </div>
                    </div>
                `;
            }).join('');

            setupPagination(allNotices.length, page);
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
            prevLi.innerHTML = `<a class="page-link" href="#" aria-label="Previous"><i class="fa-solid fa-chevron-left" style="font-size:12px;"></i></a>`;
            prevLi.addEventListener('click', (e) => { e.preventDefault(); if (currentPage > 1) { renderNotices(currentPage - 1); } });
            paginationControls.appendChild(prevLi);

            for (let i = 1; i <= totalPages; i++) {
                const li = document.createElement('li');
                li.className = `page-item ${i === currentPage ? 'active' : ''}`;
                li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
                li.addEventListener('click', (e) => { e.preventDefault(); renderNotices(i); });
                paginationControls.appendChild(li);
            }

            const nextLi = document.createElement('li');
            nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
            nextLi.innerHTML = `<a class="page-link" href="#" aria-label="Next"><i class="fa-solid fa-chevron-right" style="font-size:12px;"></i></a>`;
            nextLi.addEventListener('click', (e) => { e.preventDefault(); if (currentPage < totalPages) { renderNotices(currentPage + 1); } });
            paginationControls.appendChild(nextLi);
        }

        async function markNoticeAsRead(id) {
            try {
                await fetch('../api/notices.php', {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ notice_id: id })
                });
                
                const noticeIndex = allNotices.findIndex(n => n.id === id);
                if(noticeIndex > -1) {
                    allNotices[noticeIndex].is_read = 1;
                    renderNotices(currentPage);
                }
            } catch(e) { 
                console.error('Error marking as read:', e);
            }
        }

        async function deleteNotice(id) {
            if(!confirm('Are you sure you want to delete this notice?')) return;
            try {
                const response = await fetch('../api/notices.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ notice_id: id })
                });
                const result = await response.json();
                alert(result.message);
                if(result.success) {
                    loadNotices();
                }
            } catch(e) { 
                alert('Error deleting notice');
            }
        }

        if (isHR) {
            document.getElementById('noticeForm')?.addEventListener('submit', async function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const data = Object.fromEntries(formData);
                
                const btn = this.querySelector('button[type="submit"]');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Publishing...';
                btn.disabled = true;

                try {
                    const response = await fetch('../api/notices.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(data)
                    });
                    const result = await response.json();
                    alert(result.message);
                    if(result.success) {
                        this.reset();
                        currentPage = 1;
                        loadNotices();
                    }
                } catch(e) {
                    alert('Error posting notice');
                } finally {
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadNotices();
            setInterval(loadNotices, 10000);
            
            loadNotificationCount();
            setInterval(loadNotificationCount, 5000);
        });
    </script>
</body>
</html>
