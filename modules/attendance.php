<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'hr') { 
    header('Location: ../login.php'); 
    exit; 
}

require_once '../config/database.php';
$database = new Database(); 
$db = $database->getConnection();
$stmt = $db->prepare("SELECT first_name, last_name, profile_picture FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]); 
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$firstName = $_SESSION['first_name'] ?? ($user['first_name'] ?? 'HR');
$lastName = $_SESSION['last_name'] ?? ($user['last_name'] ?? 'Manager');
$fullName = trim($firstName . ' ' . $lastName);
$initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
$pic = $user['profile_picture'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management | KormoShathi</title>

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
        a { text-decoration: none; } button, input { font: inherit; }

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
        .topbar { min-height: 78px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--line); margin-bottom: 27px; gap: 20px; }
        .topbar-left, .topbar-right { display: flex; align-items: center; gap: 14px; }
        .mobile-menu { display: none; width: 42px; height: 42px; border: 1px solid var(--line); background: #fff; color: var(--ink); border-radius: 10px; }
        .search-wrap { position: relative; width: min(370px, 42vw); }
        .search-wrap i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9aa6b6; }
        .search-wrap input { width: 100%; height: 44px; padding: 0 15px 0 41px; background: #fff; border: 1px solid var(--line); border-radius: 10px; outline: none; color: var(--ink); font-size: 14px; }
        .top-icon { position: relative; display: grid; place-items: center; width: 42px; height: 42px; border: 1px solid var(--line); background: #fff; color: #637187; border-radius: 10px; cursor: pointer; }
        .top-icon .dot { position: absolute; width: 8px; height: 8px; border-radius: 50%; background: #ed6470; right: 8px; top: 7px; border: 1px solid #fff; }
        .top-divider { height: 34px; border-left: 1px solid var(--line); margin: 0 2px; }
        .profile-button { display: flex; align-items: center; gap: 10px; border: 0; background: transparent; text-align: left; color: var(--ink); padding: 2px; }
        .avatar { width: 42px; height: 42px; flex: 0 0 42px; border-radius: 50%; object-fit: cover; display: flex; align-items: center; justify-content: center; color: #fff; background: linear-gradient(145deg, #6594ff, #356df3); font-weight: 700; font-size: 16px; }
        .profile-name { display: block; font-size: 14px; font-weight: 700; } .profile-role { display: block; color: var(--muted); font-size: 11px; margin-top: 2px; }

        /* Page Heading & Stats */
        .page-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; margin-bottom: 22px; }
        .eyebrow { color: var(--primary); font-size: 11px; font-weight: 800; letter-spacing: 1.25px; text-transform: uppercase; margin-bottom: 7px; }
        h1 { font-family: 'Plus Jakarta Sans', sans-serif; color: var(--ink); font-size: 28px; font-weight: 800; letter-spacing: -.8px; margin: 0; }
        .page-heading p { color: var(--muted); margin: 6px 0 0; font-size: 14px; }

        .stat-card { background: var(--surface); border: 1px solid var(--line); border-radius: 13px; padding: 20px; display: flex; align-items: center; gap: 15px; box-shadow: var(--shadow); height: 100%; }
        .stat-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
        .ic-green { color: var(--green); background: #e9f8f2; } .ic-red { color: var(--red); background: #fef2f2; } .ic-orange { color: var(--orange); background: #fff4e7; } .ic-purple { color: var(--purple); background: #f2edff; }
        .stat-value { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 26px; font-weight: 800; line-height: 1.2; color: var(--ink); }
        .stat-label { color: var(--muted); font-size: 13px; font-weight: 600; margin-bottom: 5px; }

        /* Panels & Tables */
        .panel { background: var(--surface); border: 1px solid var(--line); border-radius: 13px; box-shadow: var(--shadow); overflow: hidden; display: flex; flex-direction: column; }
        .panel-header { padding: 20px 24px; border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center; background: #fff; flex-wrap: wrap; gap: 15px; }
        .panel-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 16px; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 8px; }
        .panel-title i { color: var(--primary); }

        .table th { background: #f8fafc; color: var(--muted); font-weight: 600; font-size: 13px; text-transform: uppercase; padding: 15px 20px; border-bottom: 1px solid var(--line); white-space: nowrap; }
        .table td { vertical-align: middle; padding: 15px 20px; font-size: 14px; border-bottom: 1px solid var(--line); }
        .user-avatar { width: 36px; height: 36px; background: var(--primary-soft); color: var(--primary); border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: 700; font-size: 13px; margin-right: 12px; }
        
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; text-transform: capitalize; }
        .status-present { background: #ecfdf5; color: var(--green); border: 1px solid #a7f3d0; } .status-absent { background: #fef2f2; color: var(--red); border: 1px solid #fecaca; } .status-late { background: #fff7ed; color: var(--orange); border: 1px solid #fed7aa; } 
        .status-leave { background: #f5f3ff; color: var(--purple); border: 1px solid #ddd6fe; } .status-off { background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; }
        .badge-monthly { background: #edf3ff; color: var(--primary); padding: 6px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; }
        .badge-absent { background: #fef2f2; color: var(--red); padding: 6px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; }
        .badge-late { background: #fff7ed; color: var(--orange); padding: 6px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; }
        
        .form-control { border: 1px solid var(--line); padding: 10px 15px; border-radius: 8px; font-size: 14px; background-color: #fbfcfe; }
        .pagination { margin-bottom: 0; } .page-link { border: none; color: var(--muted); font-weight: 600; border-radius: 8px !important; margin: 0 3px; padding: 8px 14px; transition: 0.2s; cursor: pointer; } .page-link:hover { background: var(--primary-soft); color: var(--primary); } .page-item.active .page-link { background: var(--primary); color: white; box-shadow: 0 4px 10px rgba(53,109,243,0.3); } .page-item.disabled .page-link { background: transparent; color: #cbd5e1; cursor: not-allowed; }

        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 1030; }
        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); } .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 0 20px 20px; } .sidebar-overlay.active { display: block; }
            .mobile-menu { display: inline-grid; place-items: center; } .search-wrap { display: none; } .profile-meta { display: none; }
        }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="../hr-dashboard.php"><span class="brand-mark"><i class="fa-solid fa-people-group"></i></span><span><span class="brand-name d-block">KormoShathi</span><span class="brand-sub d-block">HR MANAGEMENT SYSTEM</span></span></a>
        <div class="nav-caption">Workspace</div>
        <ul class="side-nav">
            <li><a class="side-link" href="../hr-dashboard.php"><i class="fa-solid fa-table-cells-large"></i> Dashboard</a></li>
            <li><a class="side-link" href="chat.php"><i class="fa-regular fa-comments"></i> Messages</a></li>
        </ul>
        <div class="nav-caption">HR Operations</div>
        <ul class="side-nav">
            <li><a class="side-link" href="add-employee.php"><i class="fa-solid fa-user-plus"></i> Add Employee</a></li>
            <li><a class="side-link" href="employees.php"><i class="fa-solid fa-users"></i> Employees</a></li>
            <li><a class="side-link active" href="attendance.php"><i class="fa-solid fa-fingerprint"></i> Attendance</a></li>
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
        <ul class="side-nav"><li><a class="side-link" href="hr-settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li></ul>
        <div class="sidebar-bottom">
            <div class="sidebar-help"><div class="help-icon"><i class="fa-regular fa-circle-question"></i></div><strong>Need a hand?</strong><small>Find help with your HR workspace.</small><a href="notice-board.php">Open notice board <i class="fa-solid fa-arrow-right ms-1"></i></a></div>
            <ul class="side-nav"><li><a class="side-link" href="../logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li></ul>
        </div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <button class="mobile-menu" type="button" onclick="toggleSidebar()" aria-label="Open menu"><i class="fa-solid fa-bars"></i></button>
                <h4 class="fw-bold m-0 d-flex align-items-center gap-2 me-3" style="font-family: 'Plus Jakarta Sans', sans-serif;"><i class="fa-solid fa-fingerprint text-primary"></i> Attendance Management</h4>
                <div class="search-wrap">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="search" id="topSearchInput" placeholder="Search by employee name or ID..." aria-label="Search">
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
        </header>

        <section class="page-heading">
            <div>
                <div class="eyebrow">Attendance Overview</div>
                <h1>Daily & Monthly Records</h1>
                <p class="text-muted mb-0">Monitor daily attendance and monthly totals in one place.</p>
            </div>
        </section>

        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6">
                <div class="stat-card"><div class="stat-icon ic-green"><i class="fa-solid fa-user-check"></i></div><div><div class="stat-label">Present Today</div><div class="stat-value" id="totalPresent">0</div></div></div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card"><div class="stat-icon ic-red"><i class="fa-solid fa-user-xmark"></i></div><div><div class="stat-label">Absent Today</div><div class="stat-value" id="totalAbsent">0</div></div></div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card"><div class="stat-icon ic-orange"><i class="fa-solid fa-clock-rotate-left"></i></div><div><div class="stat-label">Late Today</div><div class="stat-value" id="totalLate">0</div></div></div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card"><div class="stat-icon ic-purple"><i class="fa-solid fa-plane-departure"></i></div><div><div class="stat-label">On Leave / Off</div><div class="stat-value" id="totalLeave">0</div></div></div>
            </div>
        </div>

        <div class="panel">
            <div class="panel-header">
                <h5 class="panel-title"><i class="fa-regular fa-calendar-check"></i> Attendance List</h5>
                <div class="d-flex align-items-center gap-2">
                    <label class="fw-bold text-muted text-nowrap" style="font-size: 13px;">Filter Date:</label>
                    <input type="date" id="attendanceDate" class="form-control" style="max-width: 200px;" value="<?php echo date('Y-m-d'); ?>">
                </div>
            </div>
            
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Status (Today)</th>
                            <th class="text-center border-start border-end">Present (Month)</th>
                            <th class="text-center border-end">Late (Month)</th>
                            <th class="text-center border-end">Absent (Month)</th>
                        </tr>
                    </thead>
                    <tbody id="attendanceBody">
                        <tr><td colspan="7" class="text-center py-5 text-muted"><i class="fa-solid fa-spinner fa-spin fs-4 mb-3"></i><br> Loading records...</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-auto pt-3 border-top px-4 pb-3" id="paginationContainer" style="display: none;">
                <div class="text-muted fw-medium" style="font-size: 13px;" id="paginationInfo"></div>
                <nav><ul class="pagination mb-0" id="paginationControls"></ul></nav>
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

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        }

        // 🟢 Notification Logic
        async function loadNotificationCount() {
            try {
                const response = await fetch('../api/notifications.php?count=1');
                const data = await response.json();
                const badge = document.getElementById('notificationBadge');
                const dot = document.getElementById('notificationDot');
                
                if (data.success && Number(data.unread) > 0) {
                    badge.style.display = 'inline-block'; 
                    badge.textContent = data.unread; 
                    dot.style.display = 'block';
                } else {
                    badge.style.display = 'none'; 
                    dot.style.display = 'none';
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


        // 🟢 Attendance Logic
        let allMergedData = [];
        let currentDisplayData = [];
        let currentPage = 1;
        const rowsPerPage = 10;

        async function loadAttendance(date) {
            const tbody = document.getElementById('attendanceBody');
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-muted"><i class="fa-solid fa-spinner fa-spin fs-4 mb-3"></i><br> Loading...</td></tr>';
            document.getElementById('topSearchInput').value = '';
            
            try {
                const month = date.substring(0, 7);
                const [dailyRes, monthlyRes] = await Promise.all([
                    fetch(`../api/attendance.php?date=${date}`),
                    fetch(`../api/attendance-history.php?month=${month}`)
                ]);

                const dailyData = await dailyRes.json();
                const monthlyData = await monthlyRes.json();

                if(dailyData.success && dailyData.data && dailyData.data.length > 0) {
                    const monthlyMap = {};
                    if(monthlyData.success) {
                        monthlyData.data.forEach(m => { monthlyMap[m.employee_id] = m; });
                    }

                    allMergedData = dailyData.data.map(a => {
                        const empMonthly = monthlyMap[a.employee_id] || { total_present: 0, total_late: 0, total_absent: 0 };
                        return { ...a, ...empMonthly };
                    });

                    currentDisplayData = [...allMergedData];

                    document.getElementById('paginationContainer').style.display = 'flex';
                    if (currentPage > Math.ceil(currentDisplayData.length / rowsPerPage)) currentPage = 1;
                    renderTable(currentPage);
                    
                    if(dailyData.counts) {
                        document.getElementById('totalPresent').innerText = dailyData.counts.present || 0;
                        document.getElementById('totalAbsent').innerText = dailyData.counts.absent || 0;
                        document.getElementById('totalLate').innerText = dailyData.counts.late || 0;
                        document.getElementById('totalLeave').innerText = (dailyData.counts.leave || 0) + (dailyData.counts.off || 0);
                    }
                } else {
                    document.getElementById('paginationContainer').style.display = 'none';
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-muted">No attendance records found for this date.</td></tr>';
                }
            } catch(e) { tbody.innerHTML = '<tr><td colspan="7" class="text-center py-4 text-danger">Failed to load data.</td></tr>'; }
        }

        function renderTable(page) {
            const tbody = document.getElementById('attendanceBody');
            if(currentDisplayData.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-muted">No employees matched your search.</td></tr>';
                document.getElementById('paginationControls').innerHTML = '';
                document.getElementById('paginationInfo').textContent = '';
                return;
            }

            const startIndex = (page - 1) * rowsPerPage;
            const endIndex = startIndex + rowsPerPage;
            const paginatedData = currentDisplayData.slice(startIndex, endIndex);

            let html = '';
            paginatedData.forEach(a => {
                let checkIn = a.check_in ? new Date('1970-01-01T' + a.check_in).toLocaleTimeString('en-US', {hour:'2-digit',minute:'2-digit'}) : '-';
                let checkOut = a.check_out ? new Date('1970-01-01T' + a.check_out).toLocaleTimeString('en-US', {hour:'2-digit',minute:'2-digit'}) : '-';
                const initials = (a.first_name?.charAt(0) || '') + (a.last_name?.charAt(0) || '');
                const statusClass = a.status ? a.status.toLowerCase() : 'absent';
                
                html += `<tr>
                    <td><div class="d-flex align-items-center"><div class="user-avatar">${initials.toUpperCase()}</div><div><div class="fw-bold text-dark">${a.first_name||''} ${a.last_name||''}</div><div class="text-muted" style="font-size: 12px;">${a.employee_id || ''}</div></div></div></td>
                    <td class="fw-medium">${checkIn}</td><td class="fw-medium">${checkOut}</td>
                    <td><span class="status-badge status-${statusClass}">${statusClass}</span></td>
                    <td class="text-center border-start border-end"><span class="badge-monthly">${a.total_present || 0} Days</span></td>
                    <td class="text-center border-end"><span class="badge-late">${a.total_late || 0} Days</span></td>
                    <td class="text-center border-end"><span class="badge-absent">${a.total_absent || 0} Days</span></td>
                </tr>`;
            });
            tbody.innerHTML = html;
            setupPagination(currentDisplayData.length, page);
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
            prevLi.innerHTML = `<a class="page-link" href="#"><i class="fa-solid fa-chevron-left" style="font-size:12px;"></i></a>`;
            prevLi.addEventListener('click', (e) => { e.preventDefault(); if (currentPage > 1) { renderTable(currentPage - 1); } });
            paginationControls.appendChild(prevLi);

            for (let i = 1; i <= totalPages; i++) {
                const li = document.createElement('li');
                li.className = `page-item ${i === currentPage ? 'active' : ''}`;
                li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
                li.addEventListener('click', (e) => { e.preventDefault(); renderTable(i); });
                paginationControls.appendChild(li);
            }

            const nextLi = document.createElement('li');
            nextLi.className = `page-item ${currentPage === totalPages ? 'disabled' : ''}`;
            nextLi.innerHTML = `<a class="page-link" href="#"><i class="fa-solid fa-chevron-right" style="font-size:12px;"></i></a>`;
            nextLi.addEventListener('click', (e) => { e.preventDefault(); if (currentPage < totalPages) { renderTable(currentPage + 1); } });
            paginationControls.appendChild(nextLi);
        }

        document.getElementById('topSearchInput').addEventListener('input', function(e) {
            const term = e.target.value.toLowerCase();
            currentDisplayData = allMergedData.filter(a => (a.first_name && a.first_name.toLowerCase().includes(term)) || (a.last_name && a.last_name.toLowerCase().includes(term)) || (a.employee_id && a.employee_id.toLowerCase().includes(term)));
            currentPage = 1;
            renderTable(currentPage);
        });

        document.getElementById('attendanceDate').addEventListener('change', function() { currentPage = 1; loadAttendance(this.value); });

        document.addEventListener('DOMContentLoaded', () => { 
            loadAttendance(document.getElementById('attendanceDate').value); 
            loadNotificationCount();
            setInterval(loadNotificationCount, 5000); // 🟢 ৫ সেকেন্ড পর পর চেক করবে
        });
    </script>
</body>
</html>
