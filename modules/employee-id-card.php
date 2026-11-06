<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'hr') {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';
$db = (new Database())->getConnection();

$employee_id = $_GET['id'] ?? 0;

if (!$employee_id) {
    header('Location: employees.php');
    exit;
}

// Get employee details
$stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND user_type = 'employee'");
$stmt->execute([$employee_id]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    header('Location: employees.php');
    exit;
}

// Get HR user info for topbar
$stmt_hr = $db->prepare("SELECT first_name, last_name, profile_picture FROM users WHERE id = ?");
$stmt_hr->execute([$_SESSION['user_id']]);
$hr_user = $stmt_hr->fetch(PDO::FETCH_ASSOC);

$hrName = $_SESSION['first_name'] ?? ($hr_user['first_name'] ?? 'HR');
$hrLast = $_SESSION['last_name'] ?? ($hr_user['last_name'] ?? 'Manager');
$hrFullName = trim($hrName . ' ' . $hrLast);
$hrInitials = strtoupper(substr($hrName, 0, 1) . substr($hrLast, 0, 1));
$hrPic = $hr_user['profile_picture'] ?? '';

// Generate QR Code data
$qr_data = json_encode([
    'id' => $employee['employee_id'],
    'name' => $employee['first_name'] . ' ' . $employee['last_name'],
    'department' => $employee['department'],
    'position' => $employee['position'],
    'email' => $employee['email'],
    'phone' => $employee['phone']
]);

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee ID Card | KormoShathi</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

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
        .top-icon { position: relative; display: grid; place-items: center; width: 42px; height: 42px; border: 1px solid var(--line); background: #fff; color: #637187; border-radius: 10px; cursor: pointer; }
        .top-icon .dot { position: absolute; width: 8px; height: 8px; border-radius: 50%; background: #ed6470; right: 8px; top: 7px; border: 1px solid #fff; }
        .top-divider { height: 34px; border-left: 1px solid var(--line); margin: 0 2px; }
        .profile-button { display: flex; align-items: center; gap: 10px; border: 0; background: transparent; text-align: left; color: var(--ink); padding: 2px; }
        .avatar { width: 42px; height: 42px; flex: 0 0 42px; border-radius: 50%; object-fit: cover; display: flex; align-items: center; justify-content: center; color: #fff; background: linear-gradient(145deg, #6594ff, #356df3); font-weight: 700; font-size: 16px; }
        .profile-name { display: block; font-size: 14px; font-weight: 700; } .profile-role { display: block; color: var(--muted); font-size: 11px; margin-top: 2px; }

        /* ================= PAGE HEADING & CONTENT ================= */
        .page-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; margin-bottom: 22px; }
        .eyebrow { color: var(--primary); font-size: 11px; font-weight: 800; letter-spacing: 1.25px; text-transform: uppercase; margin-bottom: 7px; }
        h1 { font-family: 'Plus Jakarta Sans', sans-serif; color: var(--ink); font-size: 28px; font-weight: 800; letter-spacing: -.8px; margin: 0; }
        .page-heading p { color: var(--muted); margin: 6px 0 0; font-size: 14px; }

        /* ID CARD DESIGN */
        .id-card-wrapper { display: flex; flex-direction: column; align-items: center; padding: 20px 0; }
        
        .id-card {
            width: 380px;
            background: var(--surface);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(26, 43, 70, 0.08);
            overflow: hidden;
            position: relative;
            border: 1px solid var(--line);
        }
        
        .id-header {
            background: linear-gradient(135deg, #356df3 0%, #1d3d70 100%);
            padding: 25px 20px 45px;
            text-align: center;
            color: white;
            position: relative;
        }
        .id-header::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 20px;
            background: var(--surface);
            border-radius: 50% 50% 0 0 / 100% 100% 0 0;
        }
        .id-header h5 { margin: 0; font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 20px; letter-spacing: 0.5px; }
        .id-header small { opacity: 0.85; font-size: 11px; letter-spacing: 1px; text-transform: uppercase; }
        
        .id-body { padding: 0 25px 25px; text-align: center; }
        
        .profile-wrap { margin-top: -50px; margin-bottom: 15px; position: relative; z-index: 2; }
        .profile-img { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 4px solid var(--surface); box-shadow: 0 5px 15px rgba(0,0,0,0.1); background: #fff; }
        .profile-initials { width: 100px; height: 100px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 700; color: white; background: linear-gradient(145deg, #6594ff, #356df3); border: 4px solid var(--surface); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        
        .id-name { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 20px; color: var(--ink); margin-bottom: 2px; }
        .id-role { color: var(--primary); font-weight: 600; font-size: 13px; margin-bottom: 20px; }
        
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; text-align: left; margin-bottom: 20px; }
        .info-item { background: #f8fafc; padding: 10px 12px; border-radius: 8px; border: 1px solid var(--line); }
        .info-label { font-size: 10px; color: var(--muted); text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; margin-bottom: 3px; }
        .info-value { font-size: 13px; font-weight: 600; color: var(--ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        
        .id-footer { display: flex; justify-content: space-between; align-items: flex-end; padding-top: 15px; border-top: 1px dashed var(--line); }
        .qr-code-box { width: 70px; height: 70px; padding: 5px; background: white; border: 1px solid var(--line); border-radius: 8px; }
        .signature-box { text-align: center; }
        .signature-line { width: 100px; border-bottom: 2px solid var(--ink); margin: 0 auto 5px; }
        .signature-text { font-size: 10px; color: var(--muted); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }

        /* ACTION BUTTONS */
        .action-btns { margin-top: 30px; display: flex; gap: 12px; }
        .btn-action { padding: 12px 25px; border-radius: 10px; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 8px; transition: 0.2s; border: none; }
        .btn-print { background: var(--primary); color: white; }
        .btn-print:hover { background: #2859d9; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(53,109,243,0.3); }
        .btn-download { background: white; color: var(--ink); border: 1px solid var(--line); }
        .btn-download:hover { background: #f8fafc; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }

        /* PRINT STYLES */
        @media print {
            .no-print { display: none !important; }
            .sidebar, .topbar, .page-heading { display: none !important; }
            .main-content { margin-left: 0 !important; padding: 0 !important; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
            .id-card-wrapper { padding: 0; }
            .id-card { box-shadow: none !important; border: 1px solid #ccc !important; }
        }

        /* Notification */
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
            .sidebar { transform: translateX(-100%); } .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 0 20px 20px; } .sidebar-overlay.active { display: block; }
            .mobile-menu { display: inline-grid; place-items: center; } .profile-meta { display: none; }
        }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="../hr-dashboard.php">
            <span class="brand-mark"><i class="fa-solid fa-people-group"></i></span>
            <span><span class="brand-name d-block">KormoShathi</span><span class="brand-sub d-block">HR MANAGEMENT SYSTEM</span></span>
        </a>
        
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
            <li><a class="side-link active" href="employee-id-card-list.php"><i class="fa-regular fa-id-card"></i> Employee ID Cards</a></li>
        </ul>

        <div class="nav-caption">System</div>
        <ul class="side-nav">
            <li><a class="side-link" href="hr-settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
        </ul>

        <div class="sidebar-bottom">
            <div class="sidebar-help">
                <div class="help-icon"><i class="fa-regular fa-circle-question"></i></div>
                <strong>Need a hand?</strong>
                <small>Find help with your HR workspace.</small>
                <a href="notice-board.php">Open notice board <i class="fa-solid fa-arrow-right ms-1"></i></a>
            </div>
            <ul class="side-nav">
                <li><a class="side-link" href="../logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li>
            </ul>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <header class="topbar no-print">
            <div class="topbar-left">
                <button class="mobile-menu" type="button" onclick="toggleSidebar()" aria-label="Open menu"><i class="fa-solid fa-bars"></i></button>
                <h4 class="fw-bold m-0 d-flex align-items-center gap-2 me-3" style="font-family: 'Plus Jakarta Sans', sans-serif;"><i class="fa-regular fa-id-card text-primary"></i> Employee ID Card</h4>
            </div>
            <div class="topbar-right">
                <button class="top-icon" type="button" onclick="openNotifications()" title="Notifications" aria-label="Notifications">
                    <i class="fa-regular fa-bell"></i>
                    <span class="dot" id="notificationDot" style="display:none"></span>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationBadge" style="display:none;font-size:10px">0</span>
                </button>
                <span class="top-divider"></span>
                
                <button class="profile-button" type="button" aria-label="HR profile">
                    <?php if (!empty($hrPic)): ?>
                        <img src="<?php echo e(img_url($hrPic)); ?>" class="avatar" alt="Profile">
                    <?php else: ?>
                        <span class="avatar"><?php echo e($hrInitials); ?></span>
                    <?php endif; ?>
                    <span class="profile-meta">
                        <span class="profile-name"><?php echo e($hrFullName); ?></span>
                        <span class="profile-role">HR Manager</span>
                    </span>
                    <i class="fa-solid fa-chevron-down ms-1" style="font-size:11px;color:#98a3b2"></i>
                </button>
            </div>
        </header>

        <section class="page-heading no-print">
            <div>
                <div class="eyebrow">Employee Management</div>
                <h1>ID Card Preview</h1>
                <p>View, print or download the ID card for <?php echo e($employee['first_name'] . ' ' . $employee['last_name']); ?>.</p>
            </div>
            <a href="employees.php" class="btn btn-outline-secondary fw-bold rounded-pill px-4" style="font-size:13px;">
                <i class="fa-solid fa-arrow-left me-2"></i>Back to Employees
            </a>
        </section>

        <div class="id-card-wrapper">
            <!-- ID CARD -->
            <div class="id-card" id="idCard">
                <div class="id-header">
                    <h5><i class="fa-solid fa-building me-2"></i>KormoShathi</h5>
                    <small>Employee ID Card</small>
                </div>
                
                <div class="id-body">
                    <div class="profile-wrap">
                        <?php if(!empty($employee['profile_picture']) && img_ok($employee['profile_picture'])): ?>
                            <img src="<?php echo e(img_url($employee['profile_picture'])); ?>" class="profile-img" alt="Profile">
                        <?php else: ?>
                            <div class="profile-initials">
                                <?php echo strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <h4 class="id-name"><?php echo e($employee['first_name'] . ' ' . $employee['last_name']); ?></h4>
                    <div class="id-role"><?php echo e($employee['position'] ?? 'Employee'); ?></div>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Employee ID</div>
                            <div class="info-value"><?php echo e($employee['employee_id']); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Department</div>
                            <div class="info-value"><?php echo e($employee['department'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Blood Group</div>
                            <div class="info-value"><?php echo e($employee['blood_group'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Phone</div>
                            <div class="info-value"><?php echo e($employee['phone'] ?? 'N/A'); ?></div>
                        </div>
                        <div class="info-item" style="grid-column: span 2;">
                            <div class="info-label">Email</div>
                            <div class="info-value" style="font-size:12px;"><?php echo e($employee['email']); ?></div>
                        </div>
                        <div class="info-item" style="grid-column: span 2;">
                            <div class="info-label">Joining Date</div>
                            <div class="info-value"><?php echo date('d M Y', strtotime($employee['join_date'] ?? 'now')); ?></div>
                        </div>
                    </div>
                    
                    <div class="id-footer">
                        <div class="qr-code-box" id="qrcode"></div>
                        <div class="signature-box">
                            <div class="signature-line"></div>
                            <div class="signature-text">Authorized Signature</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- BUTTONS -->
            <div class="action-btns no-print">
                <button class="btn-action btn-print" onclick="window.print()">
                    <i class="fa-solid fa-print"></i> Print Card
                </button>
                <button class="btn-action btn-download" onclick="downloadCard()">
                    <i class="fa-solid fa-download"></i> Download Image
                </button>
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
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script>
        let notificationModal = null;

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
                const badge = document.getElementById('notificationBadge');
                const dot = document.getElementById('notificationDot');
                
                if (data.success && Number(data.unread) > 0) {
                    badge.style.display = 'inline-block'; badge.textContent = data.unread; dot.style.display = 'block';
                } else {
                    badge.style.display = 'none'; dot.style.display = 'none';
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
        // ID Card Logic (QR & Download)
        // ==========================================
        document.addEventListener('DOMContentLoaded', function() {
            const qrData = <?php echo json_encode($qr_data); ?>;
            new QRCode(document.getElementById("qrcode"), {
                text: JSON.stringify(qrData),
                width: 60,
                height: 60,
                colorDark: "#14243a",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });

            loadNotificationCount();
            setInterval(loadNotificationCount, 5000);
        });

        function downloadCard() {
            const card = document.getElementById('idCard');
            html2canvas(card, {
                scale: 3,
                backgroundColor: null,
                useCORS: true
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = 'ID_Card_<?php echo e($employee['employee_id']); ?>.png';
                link.href = canvas.toDataURL('image/png');
                link.click();
            });
        }
    </script>
</body>
</html>
