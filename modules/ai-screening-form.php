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
    <title>AI Screening | KormoShathi</title>
    
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
        .top-icon { position: relative; display: grid; place-items: center; width: 42px; height: 42px; border: 1px solid var(--line); background: #fff; color: #637187; border-radius: 10px; cursor: pointer; }
        .top-icon .dot { position: absolute; width: 8px; height: 8px; border-radius: 50%; background: #ed6470; right: 8px; top: 7px; border: 1px solid #fff; }
        .top-divider { height: 34px; border-left: 1px solid var(--line); margin: 0 2px; }
        .profile-button { display: flex; align-items: center; gap: 10px; border: 0; background: transparent; text-align: left; color: var(--ink); padding: 2px; }
        .avatar { width: 42px; height: 42px; flex: 0 0 42px; border-radius: 50%; object-fit: cover; display: flex; align-items: center; justify-content: center; color: #fff; background: linear-gradient(145deg, #6594ff, #356df3); font-weight: 700; font-size: 16px; }
        .profile-name { display: block; font-size: 14px; font-weight: 700; } .profile-role { display: block; color: var(--muted); font-size: 11px; margin-top: 2px; }

        /* Page Heading & Panels */
        .page-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; margin-bottom: 22px; }
        .eyebrow { color: var(--primary); font-size: 11px; font-weight: 800; letter-spacing: 1.25px; text-transform: uppercase; margin-bottom: 7px; }
        h1 { font-family: 'Plus Jakarta Sans', sans-serif; color: var(--ink); font-size: 28px; font-weight: 800; letter-spacing: -.8px; margin: 0; }
        .page-heading p { color: var(--muted); margin: 6px 0 0; font-size: 14px; }

        .panel { background: var(--surface); border: 1px solid var(--line); border-radius: 13px; box-shadow: var(--shadow); padding: 30px; }
        .section-title { font-size: 16px; font-weight: 800; color: var(--primary); padding-bottom: 10px; border-bottom: 2px solid var(--primary-soft); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-family: 'Plus Jakarta Sans', sans-serif; }
        .form-label { font-weight: 600; font-size: 14px; color: #435168; margin-bottom: 8px; }
        .form-control, .form-select { border: 1px solid var(--line); padding: 12px 15px; border-radius: 8px; font-size: 14px; background-color: #fbfcfe; transition: 0.2s; }
        .form-control:focus, .form-select:focus { border-color: var(--primary); background-color: #fff; box-shadow: 0 0 0 3px rgba(53,109,243,0.1); }
        .help-text { font-size: 12px; color: var(--muted); margin-top: 5px; display: block; }
        .btn-submit { background: var(--primary); border: none; padding: 14px 24px; border-radius: 8px; font-weight: 600; font-size: 15px; transition: 0.2s; width: 100%; color: white; }
        .btn-submit:hover { background: #2859d9; transform: translateY(-2px); }

        .rule-box { background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid var(--line); border-left: 4px solid #8b5cf6; margin-bottom: 24px; }
        .rule-badge { font-size: 13px; padding: 6px 12px; border-radius: 20px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; }
        .badge-shortlist { background: #ecfdf5; color: #10b981; border: 1px solid #a7f3d0; }
        .badge-review { background: #fff7ed; color: #f59e0b; border: 1px solid #fed7aa; }
        .badge-reject { background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; }

        /* Notification */
        .notification-item { display: flex; align-items: flex-start; gap: 12px; padding: 14px; border: 1px solid #edf0f5; border-radius: 10px; background: #fbfcfe; cursor: pointer; transition: .18s; }
        .notification-item:hover { border-color: #d8e4ff; background: #f7faff; }
        .notification-item.unread { background: #f4f8ff; border-color: #e2ebff; }
        .notification-icon { width: 34px; height: 34px; flex: 0 0 34px; display: grid; place-items: center; border-radius: 9px; color: var(--primary); background: #eaf1ff; font-size: 14px; }
        .notification-title { color: #344258; font-size: 13px; font-weight: 700; }
        .notification-message { color: #8591a1; font-size: 12px; margin-top: 4px; line-height: 1.45; }
        .notification-time { margin-left: auto; color: #9aa4b2; font-size: 10px; white-space: nowrap; }

        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 1030; }
        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); } .sidebar.active { transform: translateX(0); } .main-content { margin-left: 0; padding: 0 20px 20px; }
            .sidebar-overlay.active { display: block; } .mobile-menu { display: inline-grid; place-items: center; } .profile-meta { display: none; }
        }
    </style>
</head>
<body>
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

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
            <li><a class="side-link active" href="ai-screening-form.php"><i class="fa-solid fa-robot"></i> AI Screening</a></li>
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
                <h4 class="fw-bold m-0 d-flex align-items-center gap-2 me-3" style="font-family: 'Plus Jakarta Sans', sans-serif;"><i class="fa-solid fa-robot text-primary"></i> AI Auto Screening</h4>
            </div>
            <div class="topbar-right">
                <a href="ai-shortlist.php" class="btn btn-outline-primary fw-bold btn-sm px-3 rounded-pill d-none d-md-block"><i class="fa-solid fa-star me-1"></i> View Shortlist</a>
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
                <div class="eyebrow">Recruitment</div>
                <h1>Setup AI Screening</h1>
                <p class="text-muted mb-0">Configure job requirements and let AI automatically shortlist candidates.</p>
            </div>
        </section>

        <div class="panel">
            <form id="aiScreeningForm">
                <div class="section-title"><i class="fa-solid fa-briefcase"></i> Job Information</div>
                <div class="row g-4 mb-4">
                    <div class="col-md-8"><label class="form-label">Job Title <span class="text-danger">*</span></label><input type="text" class="form-control" name="job_title" placeholder="e.g., Senior PHP Developer" required></div>
                    <div class="col-md-4"><label class="form-label">Department <span class="text-danger">*</span></label><select class="form-select" name="department" required><option value="">Select Department</option><option value="IT">IT</option><option value="HR">Human Resources</option><option value="Finance">Finance</option><option value="Marketing">Marketing</option><option value="Sales">Sales</option></select></div>
                </div>
                <div class="section-title mt-5"><i class="fa-regular fa-calendar-days"></i> Screening Period</div>
                <div class="row g-4 mb-4">
                    <div class="col-md-6"><label class="form-label">Start Date <span class="text-danger">*</span></label><input type="date" class="form-control" name="start_date" required></div>
                    <div class="col-md-6"><label class="form-label">End Date <span class="text-danger">*</span></label><input type="date" class="form-control" name="end_date" required></div>
                </div>
                <div class="section-title mt-5"><i class="fa-solid fa-list-check"></i> Job Requirements</div>
                <div class="row g-4 mb-4">
                    <div class="col-12"><label class="form-label">Required Skills <span class="text-danger">*</span></label><textarea class="form-control" name="required_skills" rows="3" placeholder="PHP, Laravel, MySQL, JavaScript, Git, Docker, AWS" required></textarea><small class="help-text">Separate skills with commas.</small></div>
                    <div class="col-12"><label class="form-label">Priority Skills (Extra Weight)</label><textarea class="form-control" name="priority_skills" rows="2" placeholder="Laravel, Docker, AWS"></textarea><small class="help-text text-primary fw-bold"><i class="fa-solid fa-bolt me-1"></i> Candidates with these skills will get a higher score.</small></div>
                    <div class="col-md-6"><label class="form-label">Minimum Experience (Years)</label><input type="number" class="form-control" name="min_experience" value="0" min="0"></div>
                    <div class="col-md-6"><label class="form-label">Preferred Education</label><input type="text" class="form-control" name="preferred_education" placeholder="e.g., B.Sc in CSE/IT"></div>
                </div>
                <div class="section-title mt-5"><i class="fa-solid fa-wand-magic-sparkles"></i> AI Screening Rules</div>
                <div class="rule-box">
                    <div class="row g-4 mb-4">
                        <div class="col-md-6"><label class="form-label">Auto Shortlist Score (%)</label><input type="number" class="form-control" name="auto_shortlist_score" value="80" min="0" max="100"><small class="help-text text-success fw-bold">Score above this will be automatically shortlisted.</small></div>
                        <div class="col-md-6"><label class="form-label">Auto Reject Score (%)</label><input type="number" class="form-control" name="auto_reject_score" value="40" min="0" max="100"><small class="help-text text-danger fw-bold">Score below this will be automatically rejected.</small></div>
                    </div>
                    <div class="d-flex gap-2 flex-wrap mt-2">
                        <span class="rule-badge badge-shortlist"><i class="fa-solid fa-check-circle"></i> Shortlist: ≥ 80%</span>
                        <span class="rule-badge badge-review"><i class="fa-solid fa-eye"></i> Review: 40% - 80%</span>
                        <span class="rule-badge badge-reject"><i class="fa-solid fa-times-circle"></i> Reject: ≤ 40%</span>
                    </div>
                </div>
                <div class="section-title mt-5"><i class="fa-solid fa-phone"></i> Interview Details</div>
                <div class="row g-4 mb-4">
                    <div class="col-md-6"><label class="form-label">Interview Date & Time</label><input type="datetime-local" class="form-control" name="interview_date"></div>
                    <div class="col-md-6"><label class="form-label">Interview Location</label><input type="text" class="form-control" name="interview_location" placeholder="e.g., Dhaka, Bangladesh"></div>
                    <div class="col-md-4"><label class="form-label">Interview Type</label><select class="form-select" name="interview_type"><option value="physical">Physical (Face to Face)</option><option value="online">Online (Virtual)</option></select></div>
                    <div class="col-md-4" id="meetingLinkDiv"><label class="form-label">Meeting Link</label><input type="url" class="form-control" name="meeting_link" placeholder="https://meet.google.com/..."></div>
                    <div class="col-md-4"><label class="form-label">Interviewer Name</label><input type="text" class="form-control" name="interviewer_name" placeholder="HR Manager"></div>
                    <div class="col-12"><label class="form-label">Notes for Candidates</label><textarea class="form-control" name="notes" rows="2" placeholder="e.g., Bring your CV, portfolio..."></textarea></div>
                </div>
                <div class="mt-5 pt-4 border-top">
                    <button type="submit" class="btn-submit"><i class="fa-solid fa-rocket me-2"></i> Start AI Screening Now</button>
                </div>
            </form>
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

        // Notification Logic
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
        async function markAsRead(id) { try { await fetch('../api/notifications.php', { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }) }); await loadNotifications(); await loadNotificationCount(); } catch (error) {} }
        async function markAllAsReadAndClose() { try { await fetch('../api/notifications.php', { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: 0 }) }); await loadNotifications(); await loadNotificationCount(); if (notificationModal) notificationModal.hide(); } catch (error) {} }
        function escapeHTML(str) { return String(str || '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
        function formatTime(dateString) { if(!dateString) return ''; return new Date(dateString).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }); }

        // Form Logic
        document.querySelector('select[name="interview_type"]').addEventListener('change', function() {
            const meetingLinkDiv = document.getElementById('meetingLinkDiv');
            if(this.value === 'online') { meetingLinkDiv.style.display = 'block'; } 
            else { meetingLinkDiv.style.display = 'none'; document.querySelector('input[name="meeting_link"]').value = ''; }
        });
        document.querySelector('select[name="interview_type"]').dispatchEvent(new Event('change'));

        document.getElementById('aiScreeningForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            data.min_experience = parseInt(data.min_experience) || 0;
            data.auto_shortlist_score = parseInt(data.auto_shortlist_score) || 80;
            data.auto_reject_score = parseInt(data.auto_reject_score) || 40;
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Initializing AI Engine...'; submitBtn.disabled = true;
            
            try {
                const response = await fetch('../api/ai-screening.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
                const result = await response.json(); alert(result.message);
                if (result.success) { window.location.href = 'ai-shortlist.php?id=' + result.config_id; }
            } catch(e) { alert('Error: ' + e.message); } 
            finally { submitBtn.innerHTML = originalText; submitBtn.disabled = false; }
        });

        document.addEventListener('DOMContentLoaded', () => {
            loadNotificationCount();
            setInterval(loadNotificationCount, 5000);
        });
    </script>
</body>
</html>
