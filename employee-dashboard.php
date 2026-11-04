<?php
session_start();

if (
    !isset($_SESSION['user_id']) ||
    ($_SESSION['user_type'] ?? '') !== 'employee'
) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

date_default_timezone_set('Asia/Dhaka');

$database = new Database();
$db = $database->getConnection();

$userId = $_SESSION['user_id'];

// Fetch User Info
$stmt = $db->prepare("SELECT first_name, last_name, profile_picture, position, department, employee_id FROM users WHERE id = :id");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$firstName = $_SESSION['first_name'] ?? ($user['first_name'] ?? 'Employee');
$lastName = $_SESSION['last_name'] ?? ($user['last_name'] ?? '');
$fullName = trim($firstName . ' ' . $lastName);
$initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
$pic = $_SESSION['profile_picture'] ?? ($user['profile_picture'] ?? '');
$position = $user['position'] ?? 'Employee';
$department = $user['department'] ?? 'N/A';
$employeeId = $user['employee_id'] ?? 'N/A';

$isUrl = !empty($pic) && (strpos($pic, 'http://') === 0 || strpos($pic, 'https://') === 0);
$isLocal = !empty($pic) && !$isUrl && file_exists(__DIR__ . '/' . $pic);
$profileImage = '';
if ($isUrl) { $profileImage = $pic; } 
elseif ($isLocal) { $profileImage = '/' . trim($pic, '/'); }

// Unread Notifications
$stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$userId]);
$unread_count = (int) $stmt->fetchColumn();

// Quick Stats (Pending Submissions)
$stmt = $db->prepare("SELECT COUNT(*) FROM employee_submissions WHERE employee_id = ? AND status = 'pending'");
$stmt->execute([$userId]);
$pending_tasks = (int) $stmt->fetchColumn();

function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard | KormoShathi</title>

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
            --purple: #8b5cf6;
            --red: #ef4444;
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
            padding: 23px 16px 18px; overflow-y: auto; transition: .3s;
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

        /* MAIN CONTENT */
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; padding: 0 30px 30px; }
        
        /* TOPBAR */
        .topbar { height: 78px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--line); margin-bottom: 27px; }
        .top-icon { width: 42px; height: 42px; border: 1px solid var(--line); background: #fff; color: #637187; border-radius: 10px; display: grid; place-items: center; position: relative; }
        .top-icon:hover { background: var(--primary-soft); color: var(--primary); }
        .notification-badge { position: absolute; top: -5px; right: -5px; background: var(--red); color: white; font-size: 10px; font-weight: 700; border-radius: 50%; min-width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; border: 2px solid white; }

        .avatar { width: 42px; height: 42px; border-radius: 50%; object-fit: cover; display: flex; align-items: center; justify-content: center; color: #fff; background: linear-gradient(145deg, #6594ff, #356df3); font-weight: 700; font-size: 16px; }
        .profile-name { display: block; font-size: 14px; font-weight: 700; }
        .profile-role { display: block; color: var(--muted); font-size: 11px; margin-top: 2px; }

        /* PAGE HEADING */
        .page-heading { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 24px; }
        .page-heading h1 { font-size: 28px; font-weight: 800; margin: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        .date-chip { display: inline-flex; align-items: center; gap: 9px; padding: 10px 14px; border: 1px solid var(--line); background: #fff; border-radius: 10px; font-size: 13px; font-weight: 600; }

        /* CARDS */
        .stat-card, .panel { background: var(--surface); border: 1px solid var(--line); border-radius: 16px; box-shadow: var(--shadow); }
        .stat-card { padding: 20px; display: flex; align-items: center; gap: 15px; height: 100%; }
        .stat-icon { width: 50px; height: 50px; border-radius: 12px; display: grid; place-items: center; font-size: 20px; flex-shrink: 0; }
        .ic-blue { color: var(--primary); background: #edf3ff; }
        .ic-green { color: var(--green); background: #ecfdf5; }
        .ic-orange { color: var(--orange); background: #fff7ed; }
        .ic-purple { color: var(--purple); background: #f5f3ff; }
        .stat-label { color: var(--muted); font-size: 13px; font-weight: 600; margin-bottom: 4px; }
        .stat-value { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 26px; font-weight: 800; line-height: 1.2; }

        .panel { padding: 24px; margin-bottom: 24px; }
        .panel-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
        .panel-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 16px; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 8px; }
        .panel-title i { color: var(--primary); }
        
        /* ATTENDANCE CARD */
        .attendance-widget { background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%); border: 1px solid var(--line); border-radius: 16px; padding: 25px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px; box-shadow: var(--shadow); margin-bottom: 24px; }
        .attendance-status-box { display: flex; align-items: center; gap: 15px; }
        .attendance-icon { width: 60px; height: 60px; border-radius: 16px; background: var(--primary-soft); color: var(--primary); display: grid; place-items: center; font-size: 24px; }
        .attendance-status-text { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 22px; font-weight: 800; margin: 0; }
        .attendance-subtext { color: var(--muted); font-size: 12px; margin-top: 4px; }
        
        .attendance-time-grid { display: flex; gap: 12px; margin-top: 15px; }
        .time-box { background: #fff; padding: 8px 12px; border-radius: 8px; border: 1px solid var(--line); }
        .time-box span { font-size: 10px; color: var(--muted); display: block; text-transform: uppercase; font-weight: 700; }
        .time-box strong { font-size: 14px; font-weight: 800; color: var(--ink); }

        .attendance-actions { display: flex; gap: 10px; width: 100%; max-width: 300px; }
        .btn-attendance { flex: 1; padding: 12px; border-radius: 10px; border: none; font-weight: 700; font-size: 14px; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 6px; }
        .btn-in { background: var(--green); color: white; }
        .btn-out { background: #fff; color: var(--red); border: 1px solid #fecaca; }
        .btn-attendance:not(:disabled):hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .btn-attendance:disabled { opacity: 0.5; cursor: not-allowed; }

        /* NOTEPAD TIMELINE */
        .notes-timeline { max-height: 250px; overflow-y: auto; padding-right: 10px; margin-top: 15px; }
        .notes-timeline::-webkit-scrollbar { width: 6px; }
        .notes-timeline::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        .note-item { position: relative; padding-left: 22px; padding-bottom: 18px; border-left: 2px solid var(--line); margin-left: 8px; }
        .note-item:last-child { border-left: 2px solid transparent; padding-bottom: 0; }
        .note-item::before { content: ''; position: absolute; left: -7px; top: 0; width: 12px; height: 12px; border-radius: 50%; background: var(--primary); border: 2px solid #fff; box-shadow: 0 0 0 3px var(--primary-soft); }
        .note-time { font-size: 11px; color: var(--muted); font-weight: 700; margin-bottom: 4px; }
        .note-text { font-size: 14px; color: var(--ink); background: #f8fafc; padding: 12px 15px; border-radius: 10px; border: 1px solid var(--line); }

        /* QUICK ACTIONS */
        .quick-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; }
        .quick-btn { display: flex; align-items: center; gap: 10px; padding: 12px; background: #fff; border: 1px solid var(--line); border-radius: 10px; transition: 0.2s; color: var(--ink); cursor: pointer; }
        .quick-btn:hover { border-color: var(--primary); background: var(--primary-soft); transform: translateY(-2px); }
        .quick-btn i { width: 32px; height: 32px; border-radius: 8px; display: grid; place-items: center; font-size: 14px; }
        .quick-btn strong { font-size: 12px; display: block; }
        .quick-btn small { font-size: 10px; color: var(--muted); }

        /* COMPACT PROFILE CARD */
        .profile-compact { text-align: center; }
        .profile-compact-avatar { width: 70px; height: 70px; border-radius: 50%; object-fit: cover; border: 3px solid #f8fafc; box-shadow: 0 4px 10px rgba(0,0,0,0.05); margin: 0 auto 10px; }
        .profile-compact-initials { width: 70px; height: 70px; border-radius: 50%; background: var(--primary-soft); color: var(--primary); display: grid; place-items: center; font-size: 24px; font-weight: 800; margin: 0 auto 10px; border: 3px solid #f8fafc; }

        /* RECENT ACTIVITY */
        .activity-list { list-style: none; padding: 0; margin: 0; }
        .activity-item { display: flex; gap: 12px; padding-bottom: 15px; position: relative; }
        .activity-item:last-child { padding-bottom: 0; }
        .activity-item:not(:last-child)::before { content: ''; position: absolute; left: 15px; top: 28px; bottom: 0; width: 2px; background: var(--line); }
        .activity-icon { width: 32px; height: 32px; border-radius: 50%; display: grid; place-items: center; background: var(--primary-soft); color: var(--primary); z-index: 1; flex-shrink: 0; font-size: 12px;}
        .activity-text { font-size: 12px; font-weight: 600; color: var(--ink); margin-bottom: 2px; }
        .activity-time { font-size: 10px; color: var(--muted); }

        /* PAYSLIP TABLE */
        .table th { background: #f8fafc; color: var(--muted); font-weight: 600; font-size: 12px; text-transform: uppercase; border-bottom: 1px solid var(--line); padding: 12px 15px; }
        .table td { vertical-align: middle; padding: 12px 15px; font-size: 13px; border-bottom: 1px solid var(--line); }
        .status-paid { background: #ecfdf5; color: var(--green); padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }

        /* MOBILE */
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 1030; }
        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 0 20px 20px; }
            .sidebar-overlay.active { display: block; }
            .attendance-widget { flex-direction: column; text-align: center; }
            .attendance-status-box { flex-direction: column; }
            .attendance-actions { max-width: 100%; }
        }
    </style>
</head>
<body>

    <div class="sidebar-overlay" id="mobileOverlay" onclick="toggleSidebar()"></div>

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <a href="employee-dashboard.php" class="brand">
            <span class="brand-mark"><i class="fa-solid fa-people-group"></i></span>
            <span><span class="brand-name d-block">KormoShathi</span><span class="brand-sub d-block">HR MANAGEMENT SYSTEM</span></span>
        </a>
        
        <div class="nav-caption">Workspace</div>
        <ul class="side-nav">
            <li><a href="employee-dashboard.php" class="side-link active"><i class="fa-solid fa-table-cells-large"></i> Dashboard</a></li>
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
            <li><a href="profile.php" class="side-link"><i class="fa-solid fa-user"></i> My Profile</a></li>
        </ul>

        <div class="nav-caption">Company</div>
        <ul class="side-nav">
            <li><a href="modules/notice-board.php" class="side-link"><i class="fa-solid fa-bullhorn"></i> Notice & Events</a></li>
            <li><a href="modules/documents.php" class="side-link"><i class="fa-regular fa-file-pdf"></i> My Documents</a></li>
            <li><a href="modules/holidays.php" class="side-link"><i class="fa-solid fa-umbrella-beach"></i> Holiday Calendar</a></li>
        </ul>

        <div class="sidebar-bottom">
            <ul class="side-nav">
                <li><a href="logout.php" class="side-link text-danger"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li>
            </ul>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- TOPBAR -->
        <header class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light d-lg-none" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
                <h4 class="fw-bold m-0 d-flex align-items-center gap-2" style="font-family: 'Plus Jakarta Sans', sans-serif;"><i class="fa-solid fa-gauge-high text-primary"></i> Dashboard</h4>
            </div>

            <div class="d-flex align-items-center gap-3">
                <button class="top-icon" onclick="openNotifications(event)">
                    <i class="fa-regular fa-bell"></i>
                    <span class="notification-badge" id="noticeBadge" style="<?php echo $unread_count > 0 ? '' : 'display:none;'; ?>">
                        <?php echo $unread_count; ?>
                    </span>
                </button>
                
                <div class="d-flex align-items-center gap-2 ps-2">
                    <?php if ($profileImage): ?>
                        <img src="<?php echo e($profileImage); ?>" class="avatar" alt="Profile">
                    <?php else: ?>
                        <div class="avatar"><?php echo e($initials); ?></div>
                    <?php endif; ?>
                    <div class="d-none d-md-block">
                        <span class="profile-name"><?php echo e($firstName); ?></span>
                        <span class="profile-role"><?php echo e($position); ?></span>
                    </div>
                </div>
            </div>
        </header>

        <!-- PAGE HEADING -->
        <div class="page-heading">
            <div>
                <div class="text-primary fw-bold" style="font-size: 12px; letter-spacing: 1px; text-transform: uppercase;">Employee Workspace</div>
                <h1>Welcome back, <?php echo e($firstName); ?>! 👋</h1>
                <p class="text-muted mb-0">Here is your daily overview and quick stats.</p>
            </div>
            
            <div class="d-flex gap-2">
                <div class="date-chip">
                    <i class="fa-regular fa-calendar me-2 text-primary"></i>
                    <span id="currentDate"></span>
                </div>
                <div class="date-chip" style="border-color: #a9c2ff; background: #f4f8ff;">
                    <i class="fa-regular fa-clock me-2 text-primary"></i>
                    <span id="liveClock">--:--:--</span>
                </div>
            </div>
        </div>

        <!-- KPI CARDS -->
        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-4">
                <div class="stat-card">
                    <div class="stat-icon ic-green"><i class="fa-solid fa-clock"></i></div>
                    <div>
                        <div class="stat-label">Today's Attendance</div>
                        <div class="stat-value" id="attendanceStatus">--</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="stat-card">
                    <div class="stat-icon ic-blue"><i class="fa-solid fa-calendar-check"></i></div>
                    <div>
                        <div class="stat-label">Leave Balance</div>
                        <div class="stat-value"><span id="leaveBalance">--</span> <small style="font-size:14px;">Days</small></div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-xl-4">
                <div class="stat-card">
                    <div class="stat-icon ic-purple"><i class="fa-solid fa-money-bill-wave"></i></div>
                    <div>
                        <div class="stat-label">Current Month Salary</div>
                        <div class="stat-value" style="font-size: 20px;" id="currentSalary">৳ --</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MAIN GRID -->
        <div class="row g-3 align-items-start">
            
            <!-- LEFT COLUMN -->
            <div class="col-lg-8">
                
                <!-- ATTENDANCE WIDGET -->
                <div class="attendance-widget" id="attendanceSection">
                    <div>
                        <div class="attendance-status-box">
                            <div class="attendance-icon"><i class="fa-solid fa-fingerprint"></i></div>
                            <div>
                                <h2 class="attendance-status-text" id="attendanceStatusLabel">Not Checked In</h2>
                            </div>
                        </div>
                        
                        <div class="attendance-time-grid">
                            <div class="time-box">
                                <span><i class="fa-solid fa-right-to-bracket text-success me-1"></i> Check In</span>
                                <strong id="checkInTime">--:--</strong>
                            </div>
                            <div class="time-box">
                                <span><i class="fa-solid fa-right-from-bracket text-danger me-1"></i> Check Out</span>
                                <strong id="checkOutTime">--:--</strong>
                            </div>
                        </div>
                    </div>

                    <div class="attendance-actions">
                        <button class="btn-attendance btn-in" id="checkInButton" onclick="markAttendance('check_in')">
                            <i class="fa-solid fa-arrow-right-to-bracket"></i> Check In
                        </button>
                        <button class="btn-attendance btn-out" id="checkOutButton" onclick="markAttendance('check_out')">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i> Check Out
                        </button>
                    </div>
                </div>

                <!-- DAILY ROUTINE & NOTES HISTORY -->
                <div class="panel note-widget">
                    <div class="panel-header d-flex justify-content-between align-items-center">
                        <h5 class="panel-title"><i class="fa-solid fa-pen-to-square text-primary"></i> Daily Routine & Notes</h5>
                        <button type="button" class="btn btn-sm btn-outline-danger fw-bold" id="clearHistoryBtn" style="font-size: 11px;"><i class="fa-solid fa-trash me-1"></i> Clear History</button>
                    </div>
                    
                    <div class="d-flex gap-2 mb-2">
                        <input type="text" id="newNoteInput" class="form-control" placeholder="Write a new note or routine task..." style="border-radius: 10px; background: #fbfcfe;">
                        <button type="button" id="addNoteBtn" class="btn btn-primary" style="border-radius: 10px; padding: 0 20px;"><i class="fa-solid fa-plus"></i></button>
                    </div>
                    
                    <div class="notes-timeline" id="notesTimeline">
                    </div>
                </div>

                <!-- QUICK ACTIONS -->
                <div class="panel">
                    <div class="panel-header">
                        <h5 class="panel-title"><i class="fa-solid fa-bolt text-warning"></i> Quick Actions</h5>
                    </div>
                    <div class="quick-grid">
                        <a href="modules/leave.php" class="quick-btn">
                            <i class="fa-solid fa-calendar-plus bg-orange bg-opacity-10 text-orange"></i>
                            <div><strong>Apply Leave</strong><small>Request time off</small></div>
                        </a>
                        <a href="modules/submit-work.php" class="quick-btn">
                            <i class="fa-solid fa-file-arrow-up bg-blue bg-opacity-10 text-blue"></i>
                            <div><strong>Submit Work</strong><small>Upload daily work</small></div>
                        </a>
                        <a href="modules/my-payroll.php" class="quick-btn">
                            <i class="fa-solid fa-file-invoice-dollar bg-purple bg-opacity-10 text-purple"></i>
                            <div><strong>Payslip</strong><small>Download PDF</small></div>
                        </a>
                        <!-- 🟢 Google Calendar Quick Action -->
                        <button type="button" onclick="openCalendarModal()" class="quick-btn text-start">
                            <i class="fa-brands fa-google bg-danger bg-opacity-10 text-danger"></i>
                            <div><strong>Google Calendar</strong><small>Add Task / Event</small></div>
                        </button>
                    </div>
                </div>

                <!-- MY SALARY & PAYSLIP -->
                <div class="panel mb-0">
                    <div class="panel-header">
                        <h5 class="panel-title"><i class="fa-solid fa-wallet text-primary"></i> Recent Payslips</h5>
                        <a href="modules/my-payroll.php" class="text-primary fw-bold text-decoration-none" style="font-size: 13px;">View All</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Pay Period</th>
                                    <th>Net Salary</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody id="payslipTableBody">
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- RIGHT COLUMN -->
            <div class="col-lg-4">
                
                <!-- PROFILE CARD -->
                <div class="panel mb-3">
                    <div class="profile-compact">
                        <?php if ($profileImage): ?>
                            <img src="<?php echo e($profileImage); ?>" class="profile-compact-avatar" alt="Employee">
                        <?php else: ?>
                            <div class="profile-compact-initials"><?php echo e($initials); ?></div>
                        <?php endif; ?>

                        <h5 class="fw-bold mb-1"><?php echo e($fullName); ?></h5>
                        <p class="text-muted mb-3" style="font-size: 12px;"><?php echo e($position); ?></p>

                        <div class="d-flex justify-content-between mb-2" style="font-size: 11px;">
                            <span class="text-muted">Employee ID:</span>
                            <span class="fw-bold"><?php echo e($employeeId); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3" style="font-size: 11px;">
                            <span class="text-muted">Department:</span>
                            <span class="fw-bold"><?php echo e($department); ?></span>
                        </div>

                        <a href="profile.php" class="btn btn-outline-primary w-100 rounded-pill btn-sm fw-bold" style="font-size:12px;">
                            View Full Profile
                        </a>
                    </div>
                </div>

                <!-- UPCOMING HOLIDAYS -->
                <div class="panel mb-3">
                    <div class="panel-header">
                        <h5 class="panel-title"><i class="fa-solid fa-umbrella-beach text-warning"></i> Upcoming Holidays</h5>
                    </div>
                    <ul class="list-unstyled mb-0" id="upcomingHolidaysList">
                        <li class="text-center text-muted py-3"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</li>
                    </ul>
                </div>

                <!-- RECENT ACTIVITY -->
                <div class="panel mb-0">
                    <div class="panel-header">
                        <h5 class="panel-title"><i class="fa-solid fa-clock-rotate-left text-primary"></i> Recent Activity</h5>
                        <span class="badge bg-light text-dark border" id="activityCount">0</span>
                    </div>
                    <ul class="activity-list" id="recentActivityList">
                        <li class="text-center text-muted py-3">
                            <i class="fa-solid fa-spinner fa-spin fs-5"></i>
                            <p class="mt-1 mb-0" style="font-size:12px;">Loading...</p>
                        </li>
                    </ul>
                </div>

            </div>
        </div>

    </main>

    <!-- GOOGLE CALENDAR MODAL -->
    <div class="modal fade" id="calendarModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 16px; border: none;">
                <div class="modal-header bg-light border-bottom-0">
                    <h5 class="modal-title fw-bold"><i class="fa-brands fa-google text-danger me-2"></i> Add Task to Google Calendar</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="calendarForm">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Task Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="cal_title" required placeholder="e.g. Finish Project Report">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Description</label>
                            <textarea class="form-control" id="cal_desc" rows="2" placeholder="Task details..."></textarea>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="cal_date" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Start Time <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="cal_start" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">End Time (Deadline) <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="cal_end" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Reminder</label>
                            <select class="form-select" id="cal_reminder">
                                <option value="10">10 minutes before</option>
                                <option value="30">30 minutes before</option>
                                <option value="60">1 hour before</option>
                                <option value="1440">1 day before</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary fw-bold px-4" onclick="createCalendarEvent()">Create Event <i class="fa-solid fa-arrow-right ms-1"></i></button>
                </div>
            </div>
        </div>
    </div>

    <!-- NOTIFICATION MODAL -->
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
        'use strict';

        let notificationModal = null;
        let calendarModal = null;
        let realtimeInterval = null;
        const BD_TIME_ZONE = 'Asia/Dhaka';

        // ==========================================
        // 🟢 GOOGLE CALENDAR LOGIC
        // ==========================================
        function openCalendarModal() {
            // Set default date to today
            document.getElementById('cal_date').value = new Date().toISOString().split('T')[0];
            // Set default times (Current time + 1 hour to + 2 hours)
            const now = new Date();
            now.setHours(now.getHours() + 1);
            document.getElementById('cal_start').value = now.toTimeString().slice(0, 5);
            now.setHours(now.getHours() + 1);
            document.getElementById('cal_end').value = now.toTimeString().slice(0, 5);
            
            document.getElementById('calendarForm').reset();
            document.getElementById('cal_date').value = new Date().toISOString().split('T')[0];
            
            calendarModal.show();
        }

        function createCalendarEvent() {
            const title = document.getElementById('cal_title').value.trim();
            const desc = document.getElementById('cal_desc').value.trim();
            const date = document.getElementById('cal_date').value;
            const start = document.getElementById('cal_start').value;
            const end = document.getElementById('cal_end').value;
            const reminderMins = document.getElementById('cal_reminder').value;

            if (!title || !date || !start || !end) {
                alert('Please fill in all required fields.');
                return;
            }

            // Format for Google Calendar URL (YYYYMMDDTHHMMSS)
            const startDateTime = date.replace(/-/g, '') + 'T' + start.replace(/:/g, '') + '00';
            const endDateTime = date.replace(/-/g, '') + 'T' + end.replace(/:/g, '') + '00';

            // Build Google Calendar URL
            let url = `https://calendar.google.com/calendar/render?action=TEMPLATE`;
            url += `&text=${encodeURIComponent(title)}`;
            url += `&dates=${startDateTime}/${endDateTime}`;
            if (desc) url += `&details=${encodeURIComponent(desc)}`;
            
            // Add reminder
            url += `&reminders=popup&reminder_time=${reminderMins}`;

            // Open in new tab
            window.open(url, '_blank');
            
            // Close modal
            calendarModal.hide();
        }


        // ==========================================
        // 🟢 NOTES HISTORY LOGIC (Local Storage)
        // ==========================================
        function loadNotesHistory() {
            const timeline = document.getElementById('notesTimeline');
            let notes = [];
            try {
                notes = JSON.parse(localStorage.getItem('kormoshathi_notes_history') || '[]');
            } catch(e) {
                console.error('Error parsing notes', e);
            }
            
            if (notes.length === 0) {
                timeline.innerHTML = '<p class="text-center text-muted mt-4" style="font-size:13px;">No notes added yet. Start writing your routine!</p>';
                return;
            }

            timeline.innerHTML = notes.map(n => {
                const dateObj = new Date(n.time);
                const timeStr = dateObj.toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: true });
                
                return `
                    <div class="note-item">
                        <div class="note-time"><i class="fa-regular fa-clock me-1"></i> ${timeStr}</div>
                        <div class="note-text">${escapeHTML(n.text)}</div>
                    </div>
                `;
            }).join('');
        }

        function addNote() {
            const input = document.getElementById('newNoteInput');
            const text = input.value.trim();
            
            if (!text) {
                input.focus();
                return;
            }

            try {
                const notes = JSON.parse(localStorage.getItem('kormoshathi_notes_history') || '[]');
                notes.unshift({
                    text: text,
                    time: new Date().toISOString()
                });

                localStorage.setItem('kormoshathi_notes_history', JSON.stringify(notes));
                input.value = '';
                loadNotesHistory();
            } catch(e) {
                alert("Could not save note. Local storage might be disabled or full.");
            }
        }

        function clearNotesHistory() {
            if(confirm('Are you sure you want to clear all your notes history?')) {
                localStorage.removeItem('kormoshathi_notes_history');
                loadNotesHistory();
            }
        }


        // ==========================================
        // DASHBOARD CORE LOGIC
        // ==========================================
        function updateClock() {
            const now = new Date();
            const clock = document.getElementById('liveClock');
            const date = document.getElementById('currentDate');

            if (clock) {
                clock.textContent = new Intl.DateTimeFormat('en-US', {
                    timeZone: BD_TIME_ZONE, hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true
                }).format(now);
            }
            if (date) {
                date.textContent = new Intl.DateTimeFormat('en-US', {
                    timeZone: BD_TIME_ZONE, weekday: 'short', month: 'short', day: 'numeric', year: 'numeric'
                }).format(now);
            }
        }

        function formatBDTime(value) {
            if (!value) return '--:--';
            const raw = String(value).trim();

            const timeMatch = raw.match(/^(\d{2}):(\d{2})(?::(\d{2}))?$/);
            if (timeMatch) {
                let hour = Number(timeMatch[1]);
                const minute = timeMatch[2];
                const period = hour >= 12 ? 'PM' : 'AM';
                hour = hour % 12 || 12;
                return `${hour}:${minute} ${period}`;
            }

            const mysqlMatch = raw.match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})(?::(\d{2}))?/);
            if (mysqlMatch) {
                let hour = Number(mysqlMatch[4]);
                const minute = mysqlMatch[5];
                const period = hour >= 12 ? 'PM' : 'AM';
                hour = hour % 12 || 12;
                return `${hour}:${minute} ${period}`;
            }
            
            const date = new Date(raw);
            if (Number.isNaN(date.getTime())) return '--:--';
            return new Intl.DateTimeFormat('en-US', { timeZone: BD_TIME_ZONE, hour: 'numeric', minute: '2-digit', hour12: true }).format(date);
        }

        function setAttendanceIndicator(status, checkIn, checkOut) {
            const labelElement = document.getElementById('attendanceStatusLabel');
            const statusElement = document.getElementById('attendanceStatus');
            const checkInButton = document.getElementById('checkInButton');
            const checkOutButton = document.getElementById('checkOutButton');

            let label = 'Not Checked In';
            let color = 'var(--orange)';

            if (checkOut) { label = 'Completed'; color = 'var(--green)'; }
            else if (checkIn) { label = 'Checked In'; color = 'var(--green)'; }

            statusElement.textContent = label;
            labelElement.textContent = label;
            labelElement.style.color = color;

            document.getElementById('checkInTime').textContent = checkIn ? formatBDTime(checkIn) : '--:--';
            document.getElementById('checkOutTime').textContent = checkOut ? formatBDTime(checkOut) : '--:--';

            checkInButton.disabled = Boolean(checkIn);
            checkOutButton.disabled = !checkIn || Boolean(checkOut);
        }

        async function loadDashboardData() {
            try {
                const response = await fetch('api/attendance.php?action=today_status', { cache: 'no-store' });
                const result = await response.json();
                if (result.success) {
                    const data = result.data || result;
                    const checkIn = data.check_in_time || data.check_in || null;
                    const checkOut = data.check_out_time || data.check_out || null;
                    setAttendanceIndicator(data.status || '', checkIn, checkOut);
                }
            } catch (error) { console.error('Attendance load error:', error); }
        }

        async function markAttendance(type) {
            const button = type === 'check_in' ? document.getElementById('checkInButton') : document.getElementById('checkOutButton');
            const originalHTML = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

            try {
                const response = await fetch('api/attendance.php', {
                    method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ type })
                });
                const result = await response.json();
                if (!result.success) throw new Error(result.message);
                
                alert(result.message || 'Attendance updated!');
                await loadDashboardData();
                await loadRecentActivity();
                if(typeof loadEmpMonthlyAttendance === 'function') loadEmpMonthlyAttendance();
            } catch (error) {
                alert(error.message || 'Operation failed.');
            } finally {
                button.innerHTML = originalHTML;
                await loadDashboardData();
            }
        }

        function escapeHTML(value) {
            return String(value ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c]));
        }

        async function loadRecentActivity() {
            const container = document.getElementById('recentActivityList');
            const count = document.getElementById('activityCount');
            try {
                const response = await fetch('api/employee-activity.php', { cache: 'no-store' });
                const result = await response.json();
                const activities = result.data || [];

                count.textContent = `${activities.length}`;
                if (activities.length === 0) {
                    container.innerHTML = `<li class="text-center py-3 text-muted"><i class="fa-regular fa-folder-open fs-4 mb-2"></i><p class="mb-0" style="font-size:12px;">No recent activity</p></li>`;
                    return;
                }

                container.innerHTML = activities.slice(0, 4).map(act => `
                    <li class="activity-item">
                        <div class="activity-icon"><i class="fa-solid fa-clock-rotate-left"></i></div>
                        <div>
                            <div class="activity-text">${escapeHTML(act.description || 'Activity')}</div>
                            <div class="activity-time">${formatBDTime(act.created_at)}</div>
                        </div>
                    </li>
                `).join('');
            } catch (e) {
                container.innerHTML = `<li class="text-center text-danger py-3" style="font-size:12px;">Failed to load activities</li>`;
            }
        }

        async function loadLeaveBalance() {
            try {
                const response = await fetch('api/leave-balance.php', { cache: 'no-store' });
                const data = await response.json();
                if (data.success) {
                    document.getElementById('leaveBalance').textContent = data.balance ?? 0;
                }
            } catch (error) { console.error('Leave load error:', error); }
        }

        async function loadRecentPayslips() {
            const tbody = document.getElementById('payslipTableBody');
            try {
                const response = await fetch('api/payroll.php?my_payslips=1', { cache: 'no-store' });
                const result = await response.json();
                
                if (result.success && result.data && result.data.length > 0) {
                    const recent = result.data.slice(0, 3); 
                    tbody.innerHTML = recent.map(p => `
                        <tr>
                            <td><strong>${escapeHTML(p.month_year || p.period_start)}</strong></td>
                            <td class="text-success fw-bold">৳ ${parseFloat(p.net_salary || 0).toLocaleString()}</td>
                            <td><span class="status-paid">Paid</span></td>
                            <td class="text-end">
                                <a href="api/download-payslip.php?id=${p.id}" class="btn btn-sm btn-outline-primary fw-bold" target="_blank">
                                    <i class="fa-solid fa-download me-1"></i> PDF
                                </a>
                            </td>
                        </tr>
                    `).join('');
                    
                    document.getElementById('currentSalary').textContent = '৳ ' + parseFloat(recent[0].net_salary || 0).toLocaleString();
                } else {
                    tbody.innerHTML = `<tr><td colspan="4" class="text-center py-3 text-muted">No payslips found.</td></tr>`;
                }
            } catch (e) {
                tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger">Error loading payslips.</td></tr>`;
            }
        }

        async function loadUpcomingHolidays() {
            const container = document.getElementById('upcomingHolidaysList');
            try {
                const response = await fetch('api/holidays.php', { cache: 'no-store' });
                const result = await response.json();
                
                if (result.success && result.data.length > 0) {
                    const today = new Date();
                    today.setHours(0,0,0,0);
                    
                    const upcoming = result.data.filter(h => new Date(h.start_date) >= today).slice(0, 3);
                    
                    if (upcoming.length === 0) {
                        container.innerHTML = `<li class="text-center py-3 text-muted" style="font-size:12px;">No upcoming holidays.</li>`;
                        return;
                    }
                    
                    container.innerHTML = upcoming.map((h, index) => {
                        const date = new Date(h.start_date);
                        const dateStr = date.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                        const isLast = index === upcoming.length - 1;
                        
                        return `
                            <li class="d-flex justify-content-between ${!isLast ? 'mb-2 pb-2 border-bottom' : ''}" style="font-size: 12px;">
                                <span class="fw-bold">${escapeHTML(h.holiday_name)}</span>
                                <span class="text-muted">${dateStr}</span>
                            </li>
                        `;
                    }).join('');
                } else {
                    container.innerHTML = `<li class="text-center py-3 text-muted" style="font-size:12px;">No upcoming holidays.</li>`;
                }
            } catch (error) {
                console.error('Holiday load error:', error);
                container.innerHTML = `<li class="text-center text-danger py-3" style="font-size:12px;">Failed to load holidays.</li>`;
            }
        }

        async function loadNotificationCount() {
            try {
                const response = await fetch('api/notifications.php?count=1', { cache: 'no-store' });
                const data = await response.json();
                const unread = Number(data.unread) || 0;
                const badge = document.getElementById('noticeBadge');
                badge.textContent = unread;
                badge.style.display = unread > 0 ? 'flex' : 'none';
            } catch (error) {}
        }

        async function openNotifications(e) {
            if(e) e.preventDefault();
            await loadNotifications();
            notificationModal.show();
        }

        async function loadNotifications() {
            const container = document.getElementById('notificationList');
            container.innerHTML = `<div class="text-center py-4"><i class="fa-solid fa-spinner fa-spin"></i></div>`;
            try {
                const response = await fetch('api/notifications.php', { cache: 'no-store' });
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
                                    <div class="text-secondary" style="font-size:10px; margin-top:4px;">${formatBDTime(n.created_at)}</div>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
            } catch (error) { container.innerHTML = '<p class="text-danger text-center">Error loading.</p>'; }
        }

        async function markAsRead(id) {
            try {
                await fetch('api/notifications.php', {
                    method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id })
                });
                await loadNotifications(); await loadNotificationCount();
            } catch (error) {}
        }

        async function markAllAsReadAndClose() {
            try {
                await fetch('api/notifications.php', {
                    method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: 0 })
                });
                await loadNotifications(); await loadNotificationCount(); notificationModal.hide();
            } catch (error) {}
        }

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('mobileOverlay').classList.toggle('active');
        }
        function scrollToAttendance(e) {
            e.preventDefault();
            document.getElementById('attendanceSection').scrollIntoView({ behavior: 'smooth' });
            document.getElementById('sidebar').classList.remove('active');
            document.getElementById('mobileOverlay').classList.remove('active');
        }

        document.addEventListener('DOMContentLoaded', () => {
            notificationModal = new bootstrap.Modal(document.getElementById('notificationModal'));
            calendarModal = new bootstrap.Modal(document.getElementById('calendarModal'));
            
            updateClock();
            setInterval(updateClock, 1000);
            
            const addBtn = document.getElementById('addNoteBtn');
            const clearBtn = document.getElementById('clearHistoryBtn');
            const noteInput = document.getElementById('newNoteInput');

            if(addBtn) addBtn.addEventListener('click', addNote);
            if(clearBtn) clearBtn.addEventListener('click', clearNotesHistory);
            if(noteInput) {
                noteInput.addEventListener('keypress', function (e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        addNote();
                    }
                });
            }

            loadNotesHistory(); 

            loadDashboardData();
            loadLeaveBalance();
            loadRecentActivity();
            loadNotificationCount();
            loadRecentPayslips();
            loadUpcomingHolidays();

            realtimeInterval = setInterval(() => {
                loadDashboardData(); 
                loadLeaveBalance(); 
                loadRecentActivity(); 
                loadNotificationCount();
            }, 15000);
        });
        
        window.addEventListener('beforeunload', () => clearInterval(realtimeInterval));
    </script>
</body>
</html>
