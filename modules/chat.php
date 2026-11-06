<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
require_once '../config/database.php';
$database = new Database(); $db = $database->getConnection();
$stmt = $db->prepare("SELECT first_name, last_name, profile_picture, user_type FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]); $user = $stmt->fetch(PDO::FETCH_ASSOC);
$firstName = $user['first_name'] ?? 'User';
$lastName = $user['last_name'] ?? '';
$fullName = trim($firstName . ' ' . $lastName);
$initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
$pic = $user['profile_picture'] ?? '';
$userType = $_SESSION['user_type'];
$dashboardUrl = ($userType === 'hr') ? '../hr-dashboard.php' : '../employee-dashboard.php';

$profileImage = img_url($pic);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | KormoShathi</title>
    
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
        .top-icon { position: relative; display: grid; place-items: center; width: 42px; height: 42px; border: 1px solid var(--line); background: #fff; color: #637187; border-radius: 10px; cursor: pointer; }
        .top-icon .dot { position: absolute; width: 8px; height: 8px; border-radius: 50%; background: #ed6470; right: 8px; top: 7px; border: 1px solid #fff; }
        .top-divider { height: 34px; border-left: 1px solid var(--line); margin: 0 2px; }
        .profile-button { display: flex; align-items: center; gap: 10px; border: 0; background: transparent; text-align: left; color: var(--ink); padding: 2px; }
        .avatar { width: 42px; height: 42px; flex: 0 0 42px; border-radius: 50%; object-fit: cover; display: flex; align-items: center; justify-content: center; color: #fff; background: linear-gradient(145deg, #6594ff, #356df3); font-weight: 700; font-size: 16px; }
        .profile-name { display: block; font-size: 14px; font-weight: 700; } .profile-role { display: block; color: var(--muted); font-size: 11px; margin-top: 2px; }

        /* ================= CHAT UI ================= */
        .chat-container { display: flex; background: #fff; border-radius: 16px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); height: calc(100vh - 150px); overflow: hidden; border: 1px solid var(--line); }
        .chat-sidebar { width: 320px; border-right: 1px solid var(--line); display: flex; flex-direction: column; background: #fafbfc; }
        .chat-search { padding: 15px; border-bottom: 1px solid var(--line); }
        .chat-search input { width: 100%; padding: 10px 15px; border-radius: 8px; border: 1px solid var(--line); outline: none; }
        .contact-list { flex: 1; overflow-y: auto; }
        .contact-item { display: flex; align-items: center; gap: 12px; padding: 15px; border-bottom: 1px solid var(--line); cursor: pointer; transition: 0.2s; }
        .contact-item:hover, .contact-item.active { background: var(--primary-soft); }
        .contact-info h6 { margin: 0; font-size: 14px; font-weight: 700; color: var(--ink); }
        .contact-info small { color: var(--muted); font-size: 12px; }
        .status-dot { width: 10px; height: 10px; border-radius: 50%; background: #cbd5e1; display: inline-block; }
        .status-dot.online { background: var(--green); box-shadow: 0 0 0 3px rgba(16,185,129,0.2); }

        .chat-area { flex: 1; display: flex; flex-direction: column; background: #fff; position: relative; }
        .chat-header { padding: 15px 20px; border-bottom: 1px solid var(--line); display: flex; align-items: center; gap: 10px; background: #fff; }
        .chat-messages { flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 15px; background: #f5f7fb; }
        .message { max-width: 70%; padding: 10px 15px; border-radius: 12px; font-size: 14px; position: relative; word-wrap: break-word; }
        .message.sent { background: var(--primary); color: white; align-self: flex-end; border-bottom-right-radius: 2px; }
        .message.received { background: white; color: var(--ink); align-self: flex-start; border-bottom-left-radius: 2px; border: 1px solid var(--line); }
        .message-time { font-size: 10px; margin-top: 5px; opacity: 0.7; text-align: right; }
        .message.received .message-time { text-align: left; }
        .sender-name { font-size: 11px; font-weight: 700; margin-bottom: 3px; color: var(--muted); }
        
        .attachment-box { margin-top: 8px; background: rgba(0,0,0,0.05); padding: 8px; border-radius: 6px; display: flex; align-items: center; gap: 8px; }
        .message.sent .attachment-box { background: rgba(255,255,255,0.2); }
        .attachment-box a { color: inherit; font-weight: 600; font-size: 12px; text-decoration: underline; }
        .attachment-img { max-width: 100%; border-radius: 8px; margin-top: 8px; max-height: 200px; object-fit: cover; cursor: pointer; }

        .chat-input { padding: 15px; border-top: 1px solid var(--line); display: flex; gap: 10px; background: #fff; align-items: center; }
        .chat-input input[type="text"] { flex: 1; padding: 12px 15px; border-radius: 8px; border: 1px solid var(--line); outline: none; }
        .chat-input .file-btn { background: #f1f5f9; color: var(--muted); padding: 12px 15px; border-radius: 8px; cursor: pointer; transition: 0.2s; }
        .chat-input .file-btn:hover { background: var(--primary-soft); color: var(--primary); }
        .chat-input button.send-btn { background: var(--primary); color: white; border: none; padding: 0 20px; border-radius: 8px; font-weight: bold; cursor: pointer; height: 45px; }

        .chat-empty { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; color: var(--muted); }

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

        <div class="nav-caption">Workspace</div>
        <ul class="side-nav">
            <li><a class="side-link" href="<?php echo $dashboardUrl; ?>"><i class="fa-solid fa-table-cells-large"></i> Dashboard</a></li>
            <li><a class="side-link active" href="chat.php"><i class="fa-regular fa-comments"></i> Messages</a></li>
        </ul>

        <?php if ($userType === 'hr'): ?>
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

            <div class="sidebar-bottom">
                <div class="sidebar-help">
                    <div class="help-icon"><i class="fa-regular fa-circle-question"></i></div>
                    <strong>Need a hand?</strong>
                    <small>Find help with your HR workspace.</small>
                    <a href="notice-board.php">Open notice board <i class="fa-solid fa-arrow-right ms-1"></i></a>
                </div>
                <ul class="side-nav"><li><a href="../logout.php" class="side-link text-danger"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li></ul>
            </div>
        <?php else: ?>
            <div class="nav-caption">Workspace</div>
            <ul class="side-nav">
                <li><a class="side-link" href="my-attendance.php"><i class="fa-solid fa-fingerprint"></i> My Attendance</a></li>
                <li><a class="side-link" href="leave.php"><i class="fa-regular fa-calendar-check"></i> Leave Management</a></li>
                <li><a class="side-link" href="submit-work.php"><i class="fa-solid fa-cloud-arrow-up"></i> Submit Work</a></li>
            </ul>

            <div class="nav-caption">My Workspace</div>
            <ul class="side-nav">
                <li><a class="side-link" href="my-payroll.php"><i class="fa-solid fa-wallet"></i> My Salary & Payslip</a></li>
                <li><a class="side-link" href="tasks.php"><i class="fa-solid fa-list-check"></i> My Tasks</a></li>
                <li><a class="side-link" href="performance.php"><i class="fa-solid fa-chart-line"></i> Performance</a></li>
                <li><a class="side-link" href="../profile.php"><i class="fa-solid fa-user"></i> My Profile</a></li>
            </ul>

            <div class="nav-caption">Company</div>
            <ul class="side-nav">
                <li><a class="side-link" href="notice-board.php"><i class="fa-solid fa-bullhorn"></i> Notice & Events</a></li>
                <li><a class="side-link" href="documents.php"><i class="fa-regular fa-file-pdf"></i> My Documents</a></li>
                <li><a class="side-link" href="holidays.php"><i class="fa-solid fa-umbrella-beach"></i> Holiday Calendar</a></li>
            </ul>
            
            <div class="sidebar-bottom">
                <ul class="side-nav"><li><a href="../logout.php" class="side-link text-danger"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li></ul>
            </div>
        <?php endif; ?>
    </aside>

    <!-- ================= MAIN CONTENT ================= -->
    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <button class="mobile-menu" type="button" onclick="toggleSidebar()" aria-label="Open menu"><i class="fa-solid fa-bars"></i></button>
                <h4 class="fw-bold m-0 d-flex align-items-center gap-2 me-3" style="font-family: 'Plus Jakarta Sans', sans-serif;"><i class="fa-regular fa-comments text-primary"></i> Internal Messages</h4>
            </div>
            <div class="topbar-right">
                <button class="top-icon" type="button" onclick="openNotifications()" title="Notifications" aria-label="Notifications">
                    <i class="fa-regular fa-bell"></i>
                    <span class="dot" id="notificationDot" style="display:none"></span>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notificationBadge" style="display:none;font-size:10px">0</span>
                </button>
                <span class="top-divider"></span>
                
                <button class="profile-button" type="button" aria-label="Profile">
                    <?php if ($profileImage): ?>
                        <img src="<?php echo htmlspecialchars($profileImage); ?>" class="avatar" alt="Profile">
                    <?php else: ?>
                        <span class="avatar"><?php echo $initials; ?></span>
                    <?php endif; ?>
                    <span class="profile-meta">
                        <span class="profile-name"><?php echo htmlspecialchars($fullName); ?></span>
                        <span class="profile-role"><?php echo $userType === 'hr' ? 'HR Manager' : 'Employee'; ?></span>
                    </span>
                    <i class="fa-solid fa-chevron-down ms-1" style="font-size:11px;color:#98a3b2"></i>
                </button>
            </div>
        </header>

        <div class="chat-container">
            <div class="chat-sidebar">
                <div class="chat-search">
                    <input type="text" id="searchContact" placeholder="Search..." oninput="filterContacts()">
                </div>
                <div class="contact-list" id="contactList">
                    <div class="text-center py-4 text-muted"><i class="fa-solid fa-spinner fa-spin"></i> Loading...</div>
                </div>
            </div>

            <div class="chat-area" id="chatArea">
                <div class="chat-empty">
                    <i class="fa-regular fa-comments fs-1 mb-3 text-muted"></i>
                    <h5>Your Messages</h5>
                    <p>Select a contact or group to start chatting.</p>
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
                    <button class="btn btn-primary btn-sm rounded-pill px-3 fw-bold" onclick="markAllAsReadAndClose()"><i class="fa-solid fa-check-double me-1"></i> Mark All as Read</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let activeContactId = null;
        let activeIsGroup = 0;
        let chatInterval = null;
        let contactInterval = null;
        let allContacts = [];
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
        // Chat Logic (Preserved)
        // ==========================================
        async function loadContacts() {
            try {
                const res = await fetch('../api/chat.php?action=get_contacts');
                const data = await res.json();
                if (data.success) {
                    allContacts = data.data;
                    renderContacts(allContacts);
                    
                    if (activeContactId) {
                        const activeUniqueId = (activeIsGroup ? 'g_' : 'u_') + activeContactId;
                        document.getElementById('contact-' + activeUniqueId)?.classList.add('active');
                    }
                }
            } catch (e) { console.error(e); }
        }

        function renderContacts(contacts) {
            const list = document.getElementById('contactList');
            if (contacts.length === 0) {
                list.innerHTML = '<div class="text-center py-4 text-muted">No contacts found.</div>';
                return;
            }

            list.innerHTML = contacts.map(c => {
                let statusHtml = '';
                let dotHtml = '';
                let avatarHtml = '';
                
                // 🟢 Cloudinary বা লোকাল পাথ হ্যান্ডেল করা
                if (c.profile_picture && !c.is_group) {
                    const isUrl = c.profile_picture.startsWith('http://') || c.profile_picture.startsWith('https://');
                    const imgSrc = isUrl ? c.profile_picture : '../' + c.profile_picture;
                    avatarHtml = `<img src="${imgSrc}" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">`;
                } else {
                    avatarHtml = `<div class="avatar" style="width:40px;height:40px;font-size:14px; ${c.is_group ? 'background: #8b5cf6;' : ''}">${c.initials}</div>`;
                }

                if (c.is_group) {
                    statusHtml = '<span class="text-muted" style="font-size:11px;">Group Chat</span>';
                } else {
                    if (c.is_online) {
                        statusHtml = '<span class="text-success" style="font-size:11px;font-weight:600;"><i class="fa-solid fa-circle" style="font-size:8px;"></i> Active now</span>';
                        dotHtml = '<span class="status-dot online"></span>';
                    } else {
                        statusHtml = `<span class="text-muted" style="font-size:11px;">${c.last_seen}</span>`;
                        dotHtml = '<span class="status-dot"></span>';
                    }
                }

                return `
                <div class="contact-item" onclick="openChat('${c.unique_id}', ${c.id}, ${c.is_group ? 1 : 0})" id="contact-${c.unique_id}">
                    ${avatarHtml}
                    <div class="contact-info w-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6>${c.first_name} ${c.last_name} ${c.is_group ? '<i class="fa-solid fa-users ms-1" style="font-size:10px;"></i>' : ''}</h6>
                            ${dotHtml}
                        </div>
                        ${statusHtml}
                    </div>
                </div>
                `;
            }).join('');
        }

        function filterContacts() {
            const term = document.getElementById('searchContact').value.toLowerCase();
            const filtered = allContacts.filter(c => c.first_name.toLowerCase().includes(term) || (c.last_name && c.last_name.toLowerCase().includes(term)));
            renderContacts(filtered);
        }

        async function openChat(uniqueId, contactId, isGroup) {
            activeContactId = contactId;
            activeIsGroup = isGroup;
            
            document.querySelectorAll('.contact-item').forEach(el => el.classList.remove('active'));
            document.getElementById('contact-' + uniqueId)?.classList.add('active');

            const contact = allContacts.find(c => c.unique_id === uniqueId);
            
            let avatarHtml = '';
            // 🟢 হেডারের ছবির জন্য ক্লাউডিনারি চেক
            if (contact.profile_picture && !isGroup) {
                const isUrl = contact.profile_picture.startsWith('http://') || contact.profile_picture.startsWith('https://');
                const imgSrc = isUrl ? contact.profile_picture : '../' + contact.profile_picture;
                avatarHtml = `<img src="${imgSrc}" style="width:35px;height:35px;border-radius:50%;object-fit:cover;">`;
            } else {
                avatarHtml = `<div class="avatar" style="width:35px;height:35px;font-size:13px; ${isGroup ? 'background: #8b5cf6;' : ''}">${contact.initials}</div>`;
            }

            document.getElementById('chatArea').innerHTML = `
                <div class="chat-header">
                    ${avatarHtml}
                    <h6 class="m-0 fw-bold">${contact.first_name} ${contact.last_name}</h6>
                </div>
                <div class="chat-messages" id="messageList">
                    <div class="text-center py-4 text-muted"><i class="fa-solid fa-spinner fa-spin"></i></div>
                </div>
                <div class="chat-input">
                    <label class="file-btn" title="Attach File">
                        <i class="fa-solid fa-paperclip"></i>
                        <input type="file" id="chatFile" style="display: none;" onchange="updateFileLabel()">
                    </label>
                    <span id="fileNamePreview" style="font-size:12px; color:var(--primary); max-width:100px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"></span>
                    <input type="text" id="messageInput" placeholder="Type a message..." onkeydown="if(event.key === 'Enter') { event.preventDefault(); sendMessage(); }">
                    <button class="send-btn" id="sendBtn" onclick="sendMessage()"><i class="fa-solid fa-paper-plane"></i></button>
                </div>
            `;

            loadMessages();
            if (chatInterval) clearInterval(chatInterval);
            chatInterval = setInterval(loadMessages, 3000);
        }

        function updateFileLabel() {
            const file = document.getElementById('chatFile').files[0];
            document.getElementById('fileNamePreview').textContent = file ? file.name : '';
        }

        async function loadMessages() {
            if (!activeContactId) return;
            try {
                const res = await fetch(`../api/chat.php?action=get_messages&contact_id=${activeContactId}&is_group=${activeIsGroup}`);
                const data = await res.json();
                
                if (data.success) {
                    const list = document.getElementById('messageList');
                    if (!list) return;
                    
                    if (data.messages.length === 0) {
                        list.innerHTML = '<div class="text-center py-4 text-muted">No messages yet. Say hi!</div>';
                        return;
                    }

                    list.innerHTML = data.messages.map(m => {
                        const isMe = m.sender_id == <?php echo $_SESSION['user_id']; ?>;
                        const time = new Date(m.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                        
                        let fileHtml = '';
                        if (m.attachment) {
                            // 🟢 Cloudinary লিংক এবং লোকাল ফাইল হ্যান্ডেল করার লজিক
                            let finalUrl = m.attachment;
                            if (!finalUrl.startsWith('http://') && !finalUrl.startsWith('https://')) {
                                finalUrl = '../' + finalUrl;
                            }
                            
                            // URL থেকে এক্সটেনশন বের করা
                            const cleanUrl = m.attachment.split('?')[0]; 
                            const ext = cleanUrl.split('.').pop().toLowerCase();

                            if(['jpg','png','jpeg','gif','webp'].includes(ext)) {
                                fileHtml = `<a href="${finalUrl}" target="_blank"><img src="${finalUrl}" class="attachment-img"></a>`;
                            } else {
                                fileHtml = `<div class="attachment-box">
                                    <i class="fa-solid fa-file fs-5"></i>
                                    <a href="${finalUrl}" download="${m.file_name || 'Download File'}">${m.file_name || 'Download File'}</a>
                                </div>`;
                            }
                        }

                        return `
                            <div class="message ${isMe ? 'sent' : 'received'}">
                                ${!isMe && activeIsGroup ? `<div class="sender-name">${m.first_name}</div>` : ''}
                                ${m.message ? m.message : ''}
                                ${fileHtml}
                                <div class="message-time">${time} ${isMe && m.is_read ? '<i class="fa-solid fa-check-double"></i>' : ''}</div>
                            </div>
                        `;
                    }).join('');
                    
                    list.scrollTop = list.scrollHeight;
                }
            } catch (e) { console.error(e); }
        }

        async function sendMessage() {
            const input = document.getElementById('messageInput');
            const fileInput = document.getElementById('chatFile');
            const text = input.value.trim();
            const sendBtn = document.getElementById('sendBtn');
            
            if (!text && (!fileInput || !fileInput.files[0])) return;

            sendBtn.disabled = true;
            sendBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

            const formData = new FormData();
            formData.append('receiver_id', activeIsGroup ? 0 : activeContactId);
            formData.append('conversation_id', activeIsGroup ? activeContactId : 0);
            formData.append('is_group', activeIsGroup);
            formData.append('message', text);
            
            if (fileInput && fileInput.files[0]) {
                formData.append('file', fileInput.files[0]);
            }

            input.value = '';
            if (fileInput) fileInput.value = '';
            document.getElementById('fileNamePreview').textContent = '';

            try {
                const res = await fetch('../api/chat.php', { method: 'POST', body: formData });
                const resultText = await res.text();
                try {
                    const data = JSON.parse(resultText);
                    if (data.success) {
                        loadMessages(); 
                        loadContacts(); 
                    } else {
                        alert('Failed to send: ' + (data.error || 'Unknown error'));
                    }
                } catch(parseError) {
                    console.error("Server returned invalid JSON:", resultText);
                    alert('Server error. Please check console.');
                }
            } catch (e) { 
                console.error(e);
                alert('Network error occurred.');
            } finally {
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<i class="fa-solid fa-paper-plane"></i>';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadContacts();
            contactInterval = setInterval(loadContacts, 5000);

            loadNotificationCount();
            setInterval(loadNotificationCount, 5000);
        });
    </script>
</body>
</html>
