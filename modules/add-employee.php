<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'hr') { header('Location: ../login.php'); exit; }
require_once '../config/database.php';
$database = new Database(); $db = $database->getConnection();
$stmt = $db->prepare("SELECT first_name, last_name, profile_picture FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]); $user = $stmt->fetch(PDO::FETCH_ASSOC);
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
    <title>Add Employee | KormoShathi</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root { --navy: #14243a; --primary: #356df3; --primary-soft: #edf3ff; --ink: #17263c; --muted: #7b8798; --line: #e9edf3; --surface: #ffffff; --page: #f5f7fb; --sidebar-width: 270px; --shadow: 0 8px 26px rgba(26, 43, 70, .045); }
        * { box-sizing: border-box; } body { margin: 0; background: var(--page); color: var(--ink); font-family: 'DM Sans', sans-serif; font-size: 15px; overflow-x: hidden; } a { text-decoration: none; } button, input { font: inherit; }
        
        .sidebar { width: var(--sidebar-width); position: fixed; inset: 0 auto 0 0; z-index: 1040; display: flex; flex-direction: column; color: #d8e2f0; background: linear-gradient(180deg, var(--navy) 0%, #102037 100%); padding: 23px 16px 18px; overflow-y: auto; transition: transform .25s ease; }
        .brand { display: flex; align-items: center; gap: 11px; padding: 0 9px 23px; border-bottom: 1px solid rgba(255,255,255,.09); margin-bottom: 20px; }
        .brand-mark { width: 44px; height: 44px; display: grid; place-items: center; color: #fff; background: linear-gradient(145deg, #4e8bff, #2860dc); border-radius: 13px; font-size: 20px; box-shadow: 0 7px 18px rgba(53,109,243,.28); }
        .brand-name { color: #fff; font: 800 19px 'Plus Jakarta Sans', sans-serif; letter-spacing: -.5px; line-height: 1.15; } .brand-sub { color: #91a4bd; font-size: 11px; margin-top: 4px; letter-spacing: .55px; }
        .nav-caption { color: #71859f; font-size: 11px; font-weight: 700; letter-spacing: 1.15px; text-transform: uppercase; padding: 13px 12px 8px; }
        .side-nav { list-style: none; padding: 0; margin: 0; } .side-nav li { margin: 3px 0; } .side-link { display: flex; align-items: center; gap: 13px; min-height: 46px; padding: 10px 13px; border-radius: 9px; color: #aebdd0; font-size: 14px; font-weight: 500; transition: .18s ease; }
        .side-link i { width: 20px; text-align: center; font-size: 15px; } .side-link:hover { color: #fff; background: rgba(255,255,255,.075); } .side-link.active { color: #fff; background: linear-gradient(100deg, #3978f5, #2e66e6); box-shadow: 0 7px 18px rgba(31,91,218,.23); }
        .sidebar-bottom { margin-top: auto; padding-top: 16px; } .sidebar-help { margin: 13px 4px 15px; padding: 14px; background: rgba(255,255,255,.065); border: 1px solid rgba(255,255,255,.07); border-radius: 12px; }
        .sidebar-help .help-icon { width: 32px; height: 32px; display: grid; place-items: center; background: rgba(83,139,255,.2); color: #8eb5ff; border-radius: 9px; margin-bottom: 9px; }
        .sidebar-help strong { display: block; color: #fff; font-size: 13px; } .sidebar-help small { color: #91a4bd; font-size: 11px; display: block; margin: 4px 0 9px; } .sidebar-help a { color: #9fc0ff; font-size: 12px; font-weight: 700; }

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

        .page-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; margin-bottom: 22px; }
        .eyebrow { color: var(--primary); font-size: 11px; font-weight: 800; letter-spacing: 1.25px; text-transform: uppercase; margin-bottom: 7px; }
        h1 { font-family: 'Plus Jakarta Sans', sans-serif; color: var(--ink); font-size: 28px; font-weight: 800; letter-spacing: -.8px; margin: 0; }
        
        .panel { background: var(--surface); border: 1px solid var(--line); border-radius: 13px; box-shadow: var(--shadow); padding: 40px; }
        .section-title { font-size: 16px; font-weight: 700; color: var(--primary); border-bottom: 2px solid var(--primary-soft); padding-bottom: 8px; margin-bottom: 25px; display: flex; align-items: center; gap: 8px; font-family: 'Plus Jakarta Sans', sans-serif; }
        .form-label { font-weight: 600; font-size: 14px; color: #435168; margin-bottom: 8px; }
        .form-control, .form-select { border: 1px solid var(--line); padding: 14px 18px; border-radius: 8px; font-size: 15px; background-color: #fbfcfe; transition: 0.2s; }
        .form-control:focus, .form-select:focus { border-color: var(--primary); background-color: #fff; box-shadow: 0 0 0 3px rgba(53,109,243,0.1); }
        .help-text { font-size: 12px; color: var(--muted); margin-top: 5px; display: block; }
        .btn-primary { background: var(--primary); border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; font-size: 15px; transition: 0.2s; }
        .btn-primary:hover { background: #2859d9; transform: translateY(-2px); }
        .btn-light { border: 1px solid var(--line); font-weight: 600; padding: 12px 24px; border-radius: 8px; }

        .step-indicator { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; padding: 0 10px; }
        .step { flex: 1; position: relative; display: flex; flex-direction: column; align-items: center; gap: 9px; cursor: pointer; user-select: none; }
        .step:not(:last-child)::after { content: ''; position: absolute; top: 20px; left: 50%; width: 100%; height: 2px; background: var(--line); z-index: 0; }
        .step.completed::after, .step.active::after { background: var(--primary-soft); }
        .step-num { position: relative; z-index: 1; width: 40px; height: 40px; border-radius: 50%; background: #fff; border: 2px solid var(--line); color: var(--muted); display: grid; place-items: center; font-weight: 700; font-size: 15px; transition: 0.2s ease; }
        .step-label { font-size: 12px; font-weight: 600; color: var(--muted); text-align: center; transition: 0.2s; }
        .step:hover .step-num { border-color: #c8d3e6; }
        .step.active .step-num { background: var(--primary); border-color: var(--primary); color: #fff; box-shadow: 0 0 0 4px rgba(53,109,243,.15); }
        .step.active .step-label { color: var(--primary); }
        .step.completed .step-num { background: var(--primary-soft); border-color: var(--primary); color: transparent; }
        .step.completed .step-num::after { content: '\f00c'; font-family: 'Font Awesome 6 Free'; font-weight: 900; color: var(--primary); font-size: 14px; position: absolute; inset: 0; display: grid; place-items: center; }
        .step.completed .step-label { color: var(--primary); }

        .form-step { display: none; animation: fadeIn .25s ease; }
        .form-step.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
        .step-footer { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-top: 40px; padding-top: 25px; border-top: 1px solid var(--line); }
        .step-footer-right { display: flex; gap: 10px; }

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
        <a class="brand" href="../hr-dashboard.php"><span class="brand-mark"><i class="fa-solid fa-people-group"></i></span><span><span class="brand-name d-block">KormoShathi</span><span class="brand-sub d-block">HR MANAGEMENT SYSTEM</span></span></a>
        <div class="nav-caption">Workspace</div>
        <ul class="side-nav">
            <li><a class="side-link" href="../hr-dashboard.php"><i class="fa-solid fa-table-cells-large"></i> Dashboard</a></li>
            <li><a class="side-link" href="chat.php"><i class="fa-regular fa-comments"></i> Messages</a></li>
        </ul>
        <div class="nav-caption">HR Operations</div>
        <ul class="side-nav">
            <li><a class="side-link active" href="add-employee.php"><i class="fa-solid fa-user-plus"></i> Add Employee</a></li>
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
                <h4 class="fw-bold m-0 d-flex align-items-center gap-2 me-3" style="font-family: 'Plus Jakarta Sans', sans-serif;"><i class="fa-solid fa-user-plus text-primary"></i> Add Employee</h4>
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
        </header>

        <section class="page-heading">
            <div>
                <div class="eyebrow">Employee Onboarding</div>
                <h1>Create Employee Profile</h1>
                <p class="text-muted mb-0">Fill in the details below to add a new member to your organization.</p>
            </div>
            <a href="employees.php" class="btn btn-light"><i class="fa-solid fa-arrow-left me-2"></i> Back to List</a>
        </section>

        <div class="panel">
            <form id="addEmployeeForm" novalidate>
                <div class="step-indicator" id="stepIndicator">
                    <div class="step active" data-step="1"><span class="step-num">1</span><span class="step-label">Basic Info</span></div>
                    <div class="step" data-step="2"><span class="step-num">2</span><span class="step-label">Job Info</span></div>
                    <div class="step" data-step="3"><span class="step-num">3</span><span class="step-label">Personal</span></div>
                    <div class="step" data-step="4"><span class="step-num">4</span><span class="step-label">Account</span></div>
                </div>
                <div class="form-step active" data-step="1">
                    <div class="section-title"><i class="fa-solid fa-user-circle"></i> Basic Information</div>
                    <div class="row g-4 mb-5"> 
                        <div class="col-md-6"><label class="form-label">First Name <span class="text-danger">*</span></label><input type="text" class="form-control" name="first_name" required placeholder="Enter first name"></div>
                        <div class="col-md-6"><label class="form-label">Last Name <span class="text-danger">*</span></label><input type="text" class="form-control" name="last_name" required placeholder="Enter last name"></div>
                        <div class="col-md-6"><label class="form-label">Email Address <span class="text-danger">*</span></label><input type="email" class="form-control" name="email" required placeholder="Enter email address"></div>
                        <div class="col-md-6"><label class="form-label">Phone Number</label><input type="tel" class="form-control" name="phone" placeholder="Enter phone number"></div>
                    </div>
                </div>
                <div class="form-step" data-step="2">
                    <div class="section-title"><i class="fa-solid fa-briefcase"></i> Job Information</div>
                    <div class="row g-4 mb-5">
                        <div class="col-md-6"><label class="form-label">Department</label><select class="form-select" name="department"><option value="">Select Department</option><option value="IT">IT</option><option value="HR">HR</option><option value="Finance">Finance</option><option value="Marketing">Marketing</option><option value="Sales">Sales</option><option value="Operations">Operations</option></select></div>
                        <div class="col-md-6"><label class="form-label">Position / Designation</label><input type="text" class="form-control" name="position" placeholder="e.g., Software Engineer"></div>
                        <div class="col-md-6"><label class="form-label">Monthly Salary (BDT)</label><input type="number" class="form-control" name="salary" placeholder="0.00" step="0.01"></div>
                        <div class="col-md-6"><label class="form-label">Join Date</label><input type="date" class="form-control" name="join_date"></div>
                    </div>
                </div>
                <div class="form-step" data-step="3">
                    <div class="section-title"><i class="fa-solid fa-id-badge"></i> Personal Information</div>
                    <div class="row g-4 mb-5">
                        <div class="col-md-4"><label class="form-label">Blood Group</label><select class="form-select" name="blood_group"><option value="">Select</option><option value="A+">A+</option><option value="A-">A-</option><option value="B+">B+</option><option value="B-">B-</option><option value="AB+">AB+</option><option value="AB-">AB-</option><option value="O+">O+</option><option value="O-">O-</option></select></div>
                        <div class="col-md-4"><label class="form-label">Emergency Contact</label><input type="text" class="form-control" name="emergency_contact" placeholder="Emergency phone number"></div>
                        <div class="col-md-4"><label class="form-label">Date of Birth</label><input type="date" class="form-control" name="date_of_birth"></div>
                        <div class="col-md-12 mt-4"><label class="form-label">Address</label><textarea class="form-control" name="address" rows="3" placeholder="Current address"></textarea></div>
                    </div>
                </div>
                <div class="form-step" data-step="4">
                    <div class="section-title"><i class="fa-solid fa-shield-halved"></i> Account Information</div>
                    <div class="row g-4 mb-5">
                        <div class="col-md-6"><label class="form-label">User Type <span class="text-danger">*</span></label><select class="form-select" name="user_type" required><option value="employee">Employee</option><option value="hr">HR Manager</option></select><small class="help-text">Select "HR Manager" to give HR portal access.</small></div>
                        <div class="col-md-6"><label class="form-label">Password <span class="text-danger">*</span></label><input type="text" class="form-control" name="password" value="123456" required><small class="help-text">Default password: 123456 (User can change later)</small></div>
                    </div>
                </div>
                <div class="step-footer">
                    <a href="employees.php" class="btn btn-light">Cancel</a>
                    <div class="step-footer-right">
                        <button type="button" class="btn btn-light" id="prevBtn" style="display:none;"><i class="fa-solid fa-arrow-left me-2"></i>Previous</button>
                        <button type="button" class="btn btn-primary" id="nextBtn">Next <i class="fa-solid fa-arrow-right ms-2"></i></button>
                        <button type="submit" class="btn btn-primary" id="saveBtn" style="display:none;"><i class="fa-solid fa-floppy-disk me-2"></i> Save Employee</button>
                    </div>
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

        // 🟢 Notification Logic
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

        // Multi-step Logic
        const form = document.getElementById('addEmployeeForm');
        const formSteps = form.querySelectorAll('.form-step');
        const indicators = document.querySelectorAll('#stepIndicator .step');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const saveBtn = document.getElementById('saveBtn');
        const totalSteps = formSteps.length;
        let currentStep = 1;

        function showStep(n) {
            currentStep = n;
            formSteps.forEach(s => s.classList.toggle('active', Number(s.dataset.step) === n));
            indicators.forEach(i => { const step = Number(i.dataset.step); i.classList.toggle('active', step === n); i.classList.toggle('completed', step < n); });
            prevBtn.style.display = (n === 1) ? 'none' : 'inline-block';
            nextBtn.style.display = (n === totalSteps) ? 'none' : 'inline-block';
            saveBtn.style.display = (n === totalSteps) ? 'inline-block' : 'none';
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function validateStep(n) {
            const step = form.querySelector(`.form-step[data-step="${n}"]`);
            const inputs = step.querySelectorAll('input, select, textarea');
            for (const input of inputs) { if (!input.checkValidity()) { if (n !== currentStep) showStep(n); input.reportValidity(); return false; } }
            return true;
        }

        nextBtn.addEventListener('click', () => { if (validateStep(currentStep)) showStep(currentStep + 1); });
        prevBtn.addEventListener('click', () => { if (currentStep > 1) showStep(currentStep - 1); });
        indicators.forEach(ind => { ind.addEventListener('click', () => { const target = Number(ind.dataset.step); if (target === currentStep) return; if (target < currentStep) { showStep(target); } else { for (let s = currentStep; s < target; s++) { if (!validateStep(s)) return; } showStep(target); } }); });

        form.addEventListener('submit', async function (e) {
            e.preventDefault();
            for (let s = 1; s <= totalSteps; s++) { if (!validateStep(s)) return; }
            const formData = new FormData(this); const employeeData = Object.fromEntries(formData.entries());
            const originalText = saveBtn.innerHTML; saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Saving...'; saveBtn.disabled = true;
            try {
                const response = await fetch('../api/add-employee.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(employeeData) });
                const result = await response.json();
                if (result.success) { alert('✓ ' + (result.user_type === 'hr' ? 'HR' : 'Employee') + ' added successfully! ID: ' + result.employee_id); window.location.href = 'employees.php'; } 
                else { alert('✗ Error: ' + result.message); }
            } catch (error) { alert('Connection error! Please try again.'); } 
            finally { saveBtn.innerHTML = originalText; saveBtn.disabled = false; }
        });

        document.addEventListener('DOMContentLoaded', () => {
            loadNotificationCount();
            setInterval(loadNotificationCount, 5000); // 🟢 ৫ সেকেন্ড পর পর চেক করবে
        });
    </script>
</body>
</html>
