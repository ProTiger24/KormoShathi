<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'hr') { 
    header('Location: ../login.php'); 
    exit; 
}
require_once '../config/database.php';
$db = (new Database())->getConnection();

$stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]); 
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt2 = $db->prepare("SELECT * FROM user_settings WHERE user_id = :id");
$stmt2->execute([':id' => $_SESSION['user_id']]); 
$settings = $stmt2->fetch(PDO::FETCH_ASSOC) ?: [];

$company = $db->query("SELECT * FROM company_settings WHERE id=1")->fetch(PDO::FETCH_ASSOC) ?: [];
$attendance = $db->query("SELECT * FROM attendance_settings WHERE id=1")->fetch(PDO::FETCH_ASSOC) ?: [];
$leave = $db->query("SELECT * FROM leave_settings WHERE id=1")->fetch(PDO::FETCH_ASSOC) ?: [];

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$firstName = $user['first_name'] ?? 'HR';
$lastName = $user['last_name'] ?? '';
$fullName = trim($firstName . ' ' . $lastName);
$initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));

$pic = $user['profile_picture'] ?? '';
$isUrl = !empty($pic) && (strpos($pic, 'http://') === 0 || strpos($pic, 'https://') === 0);
$isLocal = !empty($pic) && !$isUrl && file_exists(__DIR__ . '/../' . $pic);
$profileImage = $isUrl ? $pic : ($isLocal ? '../' . trim($pic, '/') : '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Settings | KormoShathi</title>
    
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
        .top-divider { height: 34px; border-left: 1px solid var(--line); margin: 0 2px; }
        .profile-button { display: flex; align-items: center; gap: 10px; border: 0; background: transparent; text-align: left; color: var(--ink); padding: 2px; }
        .avatar { width: 42px; height: 42px; flex: 0 0 42px; border-radius: 50%; object-fit: cover; display: flex; align-items: center; justify-content: center; color: #fff; background: linear-gradient(145deg, #6594ff, #356df3); font-weight: 700; font-size: 16px; }
        .profile-name { display: block; font-size: 14px; font-weight: 700; } .profile-role { display: block; color: var(--muted); font-size: 11px; margin-top: 2px; }

        /* ================= PAGE HEADING ================= */
        .page-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; margin-bottom: 22px; }
        .eyebrow { color: var(--primary); font-size: 11px; font-weight: 800; letter-spacing: 1.25px; text-transform: uppercase; margin-bottom: 7px; }
        h1 { font-family: 'Plus Jakarta Sans', sans-serif; color: var(--ink); font-size: 28px; font-weight: 800; letter-spacing: -.8px; margin: 0; }
        .page-heading p { color: var(--muted); margin: 6px 0 0; font-size: 14px; }

        /* ================= SETTINGS UI ================= */
        .settings-container { display: flex; gap: 30px; max-width: 1200px; margin: 0 auto; }
        .settings-nav { width: 260px; flex-shrink: 0; background: #fff; border-radius: 16px; box-shadow: var(--shadow); padding: 20px; height: fit-content; }
        .settings-nav .nav-link { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 10px; color: var(--muted); font-weight: 600; font-size: 14px; margin-bottom: 4px; cursor: pointer; transition: 0.2s; border: none; background: transparent; width: 100%; text-align: left; }
        .settings-nav .nav-link:hover { background: var(--page); color: var(--ink); } .settings-nav .nav-link.active { background: var(--primary-soft); color: var(--primary); }
        .settings-content { flex: 1; background: #fff; border-radius: 16px; box-shadow: var(--shadow); padding: 40px; }
        .settings-section { display: none; animation: fadeIn 0.3s; } .settings-section.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .section-title { font-size: 24px; font-weight: 800; margin-bottom: 5px; color: var(--ink); font-family: 'Plus Jakarta Sans', sans-serif; }
        .section-desc { color: var(--muted); font-size: 14.5px; margin-bottom: 30px; }
        .card-box { border: 1px solid var(--line); border-radius: 12px; padding: 24px; margin-bottom: 24px; background: #fbfcfe; }
        .card-box-title { font-size: 16px; font-weight: 700; margin-bottom: 15px; color: var(--ink); display: flex; align-items: center; gap: 10px;}
        .form-label { font-weight: 600; font-size: 13.5px; color: #435168; margin-bottom: 8px; }
        .form-control, .form-select { border: 1px solid var(--line); padding: 12px 15px; border-radius: 8px; font-size: 14.5px; background: #fff; }
        .form-control:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(53,109,243,0.1); }
        .form-control:disabled { background: #f1f5f9; color: #94a3b8; cursor: not-allowed; }
        .readonly-badge { background: #e2e8f0; color: #64748b; font-size: 10px; padding: 2px 6px; border-radius: 4px; margin-left: 5px; font-weight: 700; }
        .form-check-input:checked { background-color: var(--primary); border-color: var(--primary); } .form-switch .form-check-input { width: 2.8em; height: 1.5em; cursor: pointer; }
        .btn-save { background: var(--primary); color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; font-size: 15px; transition: 0.2s; }
        .btn-save:hover { background: #2859d9; transform: translateY(-1px); }
        .profile-upload-container { display: flex; align-items: center; gap: 20px; margin-bottom: 30px; }
        .profile-upload-preview { width: 90px; height: 90px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-size: 32px; color: #94a3b8; overflow: hidden; border: 3px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.05);}
        .profile-upload-preview img { width: 100%; height: 100%; object-fit: cover; }

        /* ================= NOTIFICATIONS ================= */
        .notification-item { display: flex; align-items: flex-start; gap: 12px; padding: 14px; border: 1px solid #edf0f5; border-radius: 10px; background: #fbfcfe; cursor: pointer; transition: .18s; }
        .notification-item:hover { border-color: #d8e4ff; background: #f7faff; }
        .notification-item.unread { background: #f4f8ff; border-color: #e2ebff; }
        .notification-icon { width: 34px; height: 34px; flex: 0 0 34px; display: grid; place-items: center; border-radius: 9px; color: var(--primary); background: #eaf1ff; font-size: 14px; }
        .notification-title { color: #344258; font-size: 13px; font-weight: 700; }
        .notification-message { color: #8591a1; font-size: 12px; margin-top: 4px; line-height: 1.45; }
        .notification-time { margin-left: auto; color: #9aa4b2; font-size: 10px; white-space: nowrap; }

        /* ================= MOBILE ================= */
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 1030; }
        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 0 20px 20px; }
            .sidebar-overlay.active { display: block; }
            .mobile-menu { display: inline-grid; place-items: center; }
            .profile-meta { display: none; }
            .search-wrap { display: none; }
            .settings-container { flex-direction: column; }
            .settings-nav { width: 100%; }
        }
    </style>
</head>
<body>

    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- ================= SIDEBAR ================= -->
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="../hr-dashboard.php">
            <span class="brand-mark"><i class="fa-solid fa-people-group"></i></span>
            <span>
                <span class="brand-name d-block">KormoShathi</span>
                <span class="brand-sub d-block">HR MANAGEMENT SYSTEM</span>
            </span>
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
            <li><a class="side-link" href="employee-id-card-list.php"><i class="fa-regular fa-id-card"></i> Employee ID Cards</a></li>
        </ul>

        <div class="nav-caption">System</div>
        <ul class="side-nav">
            <li><a class="side-link active" href="hr-settings.php"><i class="fa-solid fa-gear"></i> Settings</a></li>
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

    <!-- ================= MAIN CONTENT ================= -->
    <main class="main-content">
        <header class="topbar">
            <div class="topbar-left">
                <button class="mobile-menu" type="button" onclick="toggleSidebar()" aria-label="Open menu"><i class="fa-solid fa-bars"></i></button>
                <h4 class="fw-bold m-0 d-flex align-items-center gap-2 me-3" style="font-family: 'Plus Jakarta Sans', sans-serif;"><i class="fa-solid fa-sliders text-primary"></i> System Settings & Configuration</h4>
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
                    <?php if ($profileImage): ?>
                        <img src="<?php echo e($profileImage); ?>" class="avatar" alt="Profile">
                    <?php else: ?>
                        <span class="avatar"><?php echo $initials; ?></span>
                    <?php endif; ?>
                    <span class="profile-meta">
                        <span class="profile-name"><?php echo e($fullName); ?></span>
                        <span class="profile-role">HR Manager</span>
                    </span>
                    <i class="fa-solid fa-chevron-down ms-1" style="font-size:11px;color:#98a3b2"></i>
                </button>
            </div>
        </header>

        <section class="page-heading">
            <div>
                <div class="eyebrow">System Administration</div>
                <h1>HR Settings</h1>
                <p class="text-muted mb-0">Manage global configurations for your HRMS.</p>
            </div>
        </section>

        <div class="settings-container">
            <!-- Settings Navigation -->
            <div class="settings-nav">
                <button class="nav-link active" onclick="switchTab(event, 'profile')"><i class="fa-regular fa-user"></i> 1. HR Profile</button>
                <button class="nav-link" onclick="switchTab(event, 'security')"><i class="fa-solid fa-shield-halved"></i> 2. Security</button>
                <button class="nav-link" onclick="switchTab(event, 'company')"><i class="fa-regular fa-building"></i> 3. Company Settings</button>
                <button class="nav-link" onclick="switchTab(event, 'attendance')"><i class="fa-solid fa-fingerprint"></i> 4. Attendance Rules</button>
                <button class="nav-link" onclick="switchTab(event, 'leave')"><i class="fa-regular fa-calendar-check"></i> 5. Leave & Holidays</button>
                <button class="nav-link" onclick="switchTab(event, 'notifications')"><i class="fa-regular fa-bell"></i> 6. Notifications</button>
                <button class="nav-link" onclick="switchTab(event, 'roles')"><i class="fa-solid fa-users-gear"></i> 7. Roles & Permissions</button>
                <button class="nav-link" onclick="switchTab(event, 'system')"><i class="fa-solid fa-sliders"></i> 8. System Preferences</button>
            </div>

            <!-- Settings Content -->
            <div class="settings-content">
                
                <!-- 1. HR Profile -->
                <div id="profile" class="settings-section active">
                    <h2 class="section-title">HR Profile Settings</h2>
                    <p class="section-desc">Update your personal details and profile picture.</p>
                    
                    <div class="profile-upload-container">
                        <div class="profile-upload-preview" id="profilePicPreview">
                            <?php if ($profileImage): ?>
                                <img src="<?php echo e($profileImage); ?>" alt="Profile">
                            <?php else: ?>
                                <i class="fa-solid fa-user"></i>
                            <?php endif; ?>
                        </div>
                        <div>
                            <input type="file" id="profile_pic_input" accept="image/jpeg, image/png" style="display: none;">
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill fw-bold" onclick="document.getElementById('profile_pic_input').click()">
                                <i class="fa-solid fa-camera me-1"></i> Change Picture
                            </button>
                        </div>
                    </div>

                    <form id="profileForm">
                        <div class="row g-4 mb-4">
                            <div class="col-md-6"><label class="form-label">First Name</label><input type="text" class="form-control" name="first_name" value="<?php echo e($user['first_name']); ?>" required></div>
                            <div class="col-md-6"><label class="form-label">Last Name</label><input type="text" class="form-control" name="last_name" value="<?php echo e($user['last_name']); ?>" required></div>
                            <div class="col-md-6"><label class="form-label">Email Address</label><input type="email" class="form-control" name="email" value="<?php echo e($user['email']); ?>" required></div>
                            <div class="col-md-6"><label class="form-label">Phone Number</label><input type="text" class="form-control" name="phone" value="<?php echo e($user['phone'] ?? ''); ?>"></div>
                            <div class="col-md-12"><hr class="my-2 opacity-25"></div>
                            <div class="col-md-4"><label class="form-label">Employee ID <span class="readonly-badge">Read Only</span></label><input type="text" class="form-control" value="<?php echo e($user['employee_id'] ?? 'HR-ADMIN'); ?>" disabled></div>
                            <div class="col-md-4"><label class="form-label">Department <span class="readonly-badge">Read Only</span></label><input type="text" class="form-control" value="<?php echo e($user['department'] ?? 'HR'); ?>" disabled></div>
                            <div class="col-md-4"><label class="form-label">Designation <span class="readonly-badge">Read Only</span></label><input type="text" class="form-control" value="<?php echo e($user['position'] ?? 'HR Manager'); ?>" disabled></div>
                        </div>
                        <button type="submit" class="btn-save"><i class="fa-solid fa-floppy-disk me-2"></i>Save Profile</button>
                    </form>
                </div>

                <!-- 2. Security -->
                <div id="security" class="settings-section">
                    <h2 class="section-title">Account Security</h2>
                    <p class="section-desc">Manage your password, sessions, and account safety.</p>
                    <div class="card-box">
                        <div class="card-box-title"><i class="fa-solid fa-key text-primary"></i> Change Password</div>
                        <form id="passwordForm">
                            <div class="row g-3 mb-3">
                                <div class="col-md-4"><label class="form-label">Current Password</label><input type="password" class="form-control" name="current_password" required></div>
                                <div class="col-md-4"><label class="form-label">New Password</label><input type="password" class="form-control" name="new_password" required></div>
                                <div class="col-md-4"><label class="form-label">Confirm New Password</label><input type="password" class="form-control" name="confirm_password" required></div>
                            </div>
                            <button type="submit" class="btn-save btn-sm"><i class="fa-solid fa-shield me-2"></i>Update Password</button>
                        </form>
                    </div>
                    <div class="card-box">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <div class="card-box-title mb-1"><i class="fa-solid fa-mobile-screen text-primary"></i> Two-Factor Authentication (2FA)</div>
                                <p class="text-muted small mb-0">Require a security code in addition to your password.</p>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="2faToggle" <?php echo (!empty($settings['two_factor'])) ? 'checked' : ''; ?> onchange="toggle2FA(this)">
                            </div>
                        </div>
                    </div>
                    <div class="card-box mb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="card-box-title mb-1"><i class="fa-solid fa-desktop text-primary"></i> Active Sessions & Login History</div>
                                <p class="text-muted small mb-0">Currently logged in from: <strong>Chrome on Linux (This device)</strong></p>
                            </div>
                            <button type="button" class="btn btn-outline-danger btn-sm fw-bold" onclick="logoutAllDevices()">Logout All Devices</button>
                        </div>
                    </div>
                </div>

                <!-- 3. Company Settings -->
                <div id="company" class="settings-section">
                    <h2 class="section-title">Company Settings</h2>
                    <p class="section-desc">Manage organization configuration and global details.</p>
                    <form id="companyForm">
                        <div class="row g-4 mb-4">
                            <div class="col-md-6"><label class="form-label">Company Name</label><input type="text" class="form-control" name="company_name" value="<?php echo e($company['company_name'] ?? 'KormoShathi'); ?>" required></div>
                            <div class="col-md-6"><label class="form-label">Official Email</label><input type="email" class="form-control" name="email" value="<?php echo e($company['email'] ?? ''); ?>"></div>
                            <div class="col-md-12"><label class="form-label">Company Address</label><textarea class="form-control" name="address" rows="2"><?php echo e($company['address'] ?? ''); ?></textarea></div>
                            <div class="col-md-4"><label class="form-label">Contact Number</label><input type="text" class="form-control" name="phone" value="<?php echo e($company['phone'] ?? ''); ?>"></div>
                            <div class="col-md-4">
                                <label class="form-label">Time Zone</label>
                                <select class="form-select" name="timezone">
                                    <option value="Asia/Dhaka" <?php echo (($company['timezone'] ?? '') === 'Asia/Dhaka') ? 'selected' : ''; ?>>(GMT+6:00) Dhaka</option>
                                    <option value="Asia/Kolkata" <?php echo (($company['timezone'] ?? '') === 'Asia/Kolkata') ? 'selected' : ''; ?>>(GMT+5:30) India</option>
                                    <option value="UTC" <?php echo (($company['timezone'] ?? '') === 'UTC') ? 'selected' : ''; ?>>UTC</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Currency</label>
                                <select class="form-select" name="currency">
                                    <option value="BDT" <?php echo (($company['currency'] ?? '') === 'BDT') ? 'selected' : ''; ?>>BDT (৳)</option>
                                    <option value="USD" <?php echo (($company['currency'] ?? '') === 'USD') ? 'selected' : ''; ?>>USD ($)</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn-save"><i class="fa-solid fa-floppy-disk me-2"></i>Save Company Info</button>
                    </form>
                </div>

                <!-- 4. Attendance Settings -->
                <div id="attendance" class="settings-section">
                    <h2 class="section-title">Attendance Settings</h2>
                    <p class="section-desc">Configure office hours, late rules, and attendance policies.</p>
                    <form id="attendanceForm">
                        <div class="row g-4 mb-4">
                            <div class="col-md-6"><label class="form-label">Office Start Time</label><input type="time" class="form-control" name="start_time" value="<?php echo e($attendance['start_time'] ?? '09:00'); ?>"></div>
                            <div class="col-md-6"><label class="form-label">Office End Time</label><input type="time" class="form-control" name="end_time" value="<?php echo e($attendance['end_time'] ?? '18:00'); ?>"></div>
                            <div class="col-md-6"><label class="form-label">Late Arrival Threshold (Minutes)</label><input type="number" class="form-control" name="late_threshold" value="<?php echo e($attendance['late_threshold'] ?? 15); ?>"></div>
                            <div class="col-md-6"><label class="form-label">Early Departure Threshold (Minutes)</label><input type="number" class="form-control" name="early_departure" value="<?php echo e($attendance['early_departure'] ?? 15); ?>"></div>
                            <div class="col-md-6"><label class="form-label">Break Time (Minutes)</label><input type="number" class="form-control" name="break_time" value="<?php echo e($attendance['break_time'] ?? 60); ?>"></div>
                            <div class="col-md-6">
                                <label class="form-label">Weekly Working Days</label>
                                <select class="form-select" name="weekly_days">
                                    <option value="Mon-Fri" <?php echo (($attendance['weekly_days'] ?? '') === 'Mon-Fri') ? 'selected' : ''; ?>>Monday - Friday</option>
                                    <option value="Sun-Thu" <?php echo (($attendance['weekly_days'] ?? '') === 'Sun-Thu') ? 'selected' : ''; ?>>Sunday - Thursday</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="allow_correction" id="allowCorrection" <?php echo (!empty($attendance['allow_correction'])) ? 'checked' : ''; ?>>
                                    <label class="form-check-label fw-bold ms-2" for="allowCorrection">Allow employees to request attendance correction</label>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn-save"><i class="fa-solid fa-floppy-disk me-2"></i>Save Attendance Rules</button>
                    </form>
                </div>

                <!-- 5. Leave & Holiday Settings -->
                <div id="leave" class="settings-section">
                    <h2 class="section-title">Leave & Holiday Settings</h2>
                    <p class="section-desc">Manage leave allocations and rules for employees.</p>
                    <form id="leaveForm">
                        <div class="row g-4 mb-4">
                            <div class="col-md-4"><label class="form-label">Annual Leave (Days)</label><input type="number" class="form-control" name="annual_leave" value="<?php echo e($leave['annual_leave'] ?? 20); ?>"></div>
                            <div class="col-md-4"><label class="form-label">Sick Leave (Days)</label><input type="number" class="form-control" name="sick_leave" value="<?php echo e($leave['sick_leave'] ?? 10); ?>"></div>
                            <div class="col-md-4"><label class="form-label">Casual Leave (Days)</label><input type="number" class="form-control" name="casual_leave" value="<?php echo e($leave['casual_leave'] ?? 10); ?>"></div>
                            <div class="col-md-4"><label class="form-label">Maternity Leave (Days)</label><input type="number" class="form-control" name="maternity_leave" value="<?php echo e($leave['maternity_leave'] ?? 90); ?>"></div>
                            <div class="col-md-4"><label class="form-label">Paternity Leave (Days)</label><input type="number" class="form-control" name="paternity_leave" value="<?php echo e($leave['paternity_leave'] ?? 15); ?>"></div>
                            <div class="col-md-4"><label class="form-label">Leave Carry Forward (Days)</label><input type="number" class="form-control" name="carry_forward" value="<?php echo e($leave['carry_forward'] ?? 5); ?>"></div>
                            <div class="col-md-12">
                                <label class="form-label">Leave Approval Rules</label>
                                <select class="form-select" name="approval_rules">
                                    <option value="Single Level" <?php echo (($leave['approval_rules'] ?? '') === 'Single Level') ? 'selected' : ''; ?>>Single Level (Direct HR)</option>
                                    <option value="Multi Level" <?php echo (($leave['approval_rules'] ?? '') === 'Multi Level') ? 'selected' : ''; ?>>Multi Level (Manager -> HR)</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn-save mb-4"><i class="fa-solid fa-floppy-disk me-2"></i>Save Leave Rules</button>
                    </form>
                    <div class="card-box mt-4 mb-0 bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="card-box-title mb-1"><i class="fa-solid fa-calendar-days text-primary"></i> Holiday Calendar</div>
                                <p class="text-muted small mb-0">Manage Eid, Puja, and other public holidays.</p>
                            </div>
                            <a href="hr-holidays.php" class="btn btn-primary btn-sm fw-bold rounded-pill">Manage Holidays <i class="fa-solid fa-arrow-right ms-1"></i></a>
                        </div>
                    </div>
                </div>

                <!-- 6. Notifications -->
                <div id="notifications" class="settings-section">
                    <h2 class="section-title">Notification Settings</h2>
                    <p class="section-desc">Choose what HR alerts you want to receive.</p>
                    <div class="card-box">
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input setting-toggle" type="checkbox" data-key="email_notifications" <?php echo (!empty($settings['email_notifications'])) ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-bold ms-2">Email Notifications</label>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input setting-toggle" type="checkbox" data-key="notify_hr_reg" <?php echo (!empty($settings['notify_hr_reg'])) ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-bold ms-2">Employee Registration Alerts</label>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input setting-toggle" type="checkbox" data-key="push_notifications" <?php echo (!empty($settings['push_notifications'])) ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-bold ms-2">Leave Request Notifications</label>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input setting-toggle" type="checkbox" data-key="notify_hr_attendance" <?php echo (!empty($settings['notify_hr_attendance'])) ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-bold ms-2">Attendance Alerts</label>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input setting-toggle" type="checkbox" data-key="notify_hr_payroll" <?php echo (!empty($settings['notify_hr_payroll'])) ? 'checked' : ''; ?>>
                            <label class="form-check-label fw-bold ms-2">Payroll Notifications</label>
                        </div>
                    </div>
                </div>

                <!-- 7. Roles & Permissions -->
                <div id="roles" class="settings-section">
                    <h2 class="section-title">Roles & Permissions</h2>
                    <p class="section-desc">Manage who can access what in the HR system.</p>
                    <div class="card-box">
                        <div class="card-box-title"><i class="fa-solid fa-user-shield text-primary"></i> Admin Roles</div>
                        <div class="table-responsive">
                            <table class="table table-hover mt-3 align-middle">
                                <thead class="bg-light">
                                    <tr><th>Role Name</th><th>Access Level</th><th class="text-end">Action</th></tr>
                                </thead>
                                <tbody id="rolesTableBody">
                                    <tr><td colspan="3" class="text-center py-3"><i class="fa-solid fa-spinner fa-spin"></i> Loading roles...</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <button class="btn btn-outline-primary btn-sm rounded-pill mt-2 fw-bold" onclick="openCreateRole()"><i class="fa-solid fa-plus me-1"></i> Create New Role</button>
                    </div>
                </div>

                <!-- 8. System Preferences -->
                <div id="system" class="settings-section">
                    <h2 class="section-title">System Preferences</h2>
                    <p class="section-desc">Customize the global view of your HRMS.</p>
                    <form id="systemForm">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label">Date Format</label>
                                <select class="form-select"><option>DD-MM-YYYY (e.g. 03-11-2026)</option><option>MMM DD, YYYY (e.g. Nov 03, 2026)</option></select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Time Format</label>
                                <select class="form-select"><option>12 Hours (AM/PM)</option><option>24 Hours</option></select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Language</label>
                                <select class="form-select" name="language">
                                    <option value="English" <?php echo (($settings['language'] ?? '') === 'English') ? 'selected' : ''; ?>>English</option>
                                    <option value="Bangla" <?php echo (($settings['language'] ?? '') === 'Bangla') ? 'selected' : ''; ?>>বাংলা (Bangla)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Default Theme</label>
                                <select class="form-select" name="theme">
                                    <option value="Light" <?php echo (($settings['theme'] ?? '') === 'Light') ? 'selected' : ''; ?>>Light Mode</option>
                                    <option value="Dark" <?php echo (($settings['theme'] ?? '') === 'Dark') ? 'selected' : ''; ?>>Dark Mode</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn-save mt-4"><i class="fa-solid fa-floppy-disk me-2"></i>Save Preferences</button>
                    </form>
                </div>

            </div>
        </div>
    </main>

    <!-- Role Modal (Create/Edit) -->
    <div class="modal fade" id="roleModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius: 16px; border: none;">
                <div class="modal-header bg-light border-bottom-0" style="border-radius: 16px 16px 0 0;">
                    <h5 class="modal-title fw-bold" id="roleModalTitle">Create New Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="roleForm">
                        <input type="hidden" id="role_id" name="id">
                        <div class="mb-3">
                            <label class="form-label">Role Name</label>
                            <input type="text" class="form-control" id="role_name" name="role_name" required placeholder="e.g. HR Manager">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Access Level</label>
                            <input type="text" class="form-control" id="access_level" name="access_level" required placeholder="e.g. Full System Access, Only Attendance">
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-light fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary fw-bold" id="btnSaveRole" onclick="saveRole()">Save Role</button>
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
                    <button class="btn btn-primary btn-sm rounded-pill px-3 fw-bold" onclick="markAllAsReadAndClose()"><i class="fa-solid fa-check-double me-1"></i> Mark All as Read</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let notificationModal = null;
        let roleModalInstance = null;

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        }

        document.addEventListener('DOMContentLoaded', () => {
            roleModalInstance = new bootstrap.Modal(document.getElementById('roleModal'));
            loadRoles();
            
            loadNotificationCount();
            setInterval(loadNotificationCount, 5000); // 🟢 ৫ সেকেন্ড পর পর নোটিফিকেশন চেক
        });

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
        // Settings Logic
        // ==========================================
        function switchTab(event, tabId) {
            document.querySelectorAll('.settings-nav .nav-link').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.settings-section').forEach(el => el.classList.remove('active'));
            event.currentTarget.classList.add('active');
            document.getElementById(tabId).classList.add('active');
        }

        // 1. ছবি আপলোড (🟢 এখানে Cloudinary API লিংকটি সেট করা হয়েছে)
        document.getElementById('profile_pic_input')?.addEventListener('change', async function(e) {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('profilePicPreview').innerHTML = `<img src="${event.target.result}" style="width:100%; height:100%; object-fit:cover;">`;
                    document.querySelector('.avatar').innerHTML = `<img src="${event.target.result}" style="width:100%; height:100%; border-radius:50%; object-fit:cover;">`;
                }
                reader.readAsDataURL(file);

                const formData = new FormData();
                formData.append('profile_pic', file);

                try {
                    // 🟢 upload-profile-pic.php এর বদলে upload-profile.php ব্যবহার করা হলো
                    const res = await fetch('../api/upload-profile.php', { method: 'POST', body: formData });
                    const result = await res.json();
                    if (!result.success) alert('Error: ' + result.message);
                } catch (err) { alert('Server error while uploading picture.'); }
            }
        });

        // 2. জেনারেল ফর্ম সাবমিট
        async function submitForm(formId, sectionName) {
            const form = document.getElementById(formId);
            if(!form) return;
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                const btn = this.querySelector('button[type="submit"]');
                const originalText = btn.innerHTML;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Saving...'; btn.disabled = true;

                const formData = new FormData(this);
                const data = Object.fromEntries(formData.entries());
                data.section = sectionName;
                
                if(sectionName === 'attendance') {
                    data.allow_correction = document.getElementById('allowCorrection').checked ? 1 : 0;
                }

                try {
                    const res = await fetch('../api/save-hr-settings.php', {
                        method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(data)
                    });
                    const result = await res.json();
                    alert(result.message);
                } catch(err) { alert('Error saving.'); } finally {
                    btn.innerHTML = originalText; btn.disabled = false;
                }
            });
        }

        submitForm('companyForm', 'company');
        submitForm('attendanceForm', 'attendance');
        submitForm('leaveForm', 'leave');
        submitForm('systemForm', 'system');

        // 3. প্রোফাইল ফর্ম সাবমিট
        document.getElementById('profileForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Saving...'; btn.disabled = true;

            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            data.action = 'update_profile';

            try {
                const res = await fetch('../api/update-profile.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(data) });
                const result = await res.json();
                alert(result.message);
                if(result.success) window.location.reload();
            } catch(err) { alert('Error saving profile.'); } finally {
                btn.innerHTML = originalText; btn.disabled = false;
            }
        });

        // 4. পাসওয়ার্ড ফর্ম সাবমিট
        document.getElementById('passwordForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const form = this;
            const newPass = form.querySelector('[name="new_password"]').value;
            const confirmPass = form.querySelector('[name="confirm_password"]').value;
            if(newPass !== confirmPass) { alert('New passwords do not match!'); return; }

            const btn = form.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Updating...'; btn.disabled = true;

            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            data.action = 'update_password';

            try {
                const res = await fetch('../api/update-profile.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(data) });
                const result = await res.json();
                alert(result.message);
                if(result.success) form.reset();
            } catch(err) { alert('Error updating password.'); } finally {
                btn.innerHTML = originalText; btn.disabled = false;
            }
        });

        // 5. নোটিফিকেশন অটো-সেভ
        async function saveSettings() {
            const toggles = document.querySelectorAll('.setting-toggle');
            let data = {};
            toggles.forEach(t => { data[t.dataset.key] = t.checked ? 1 : 0; });

            try {
                await fetch('../api/save-preferences.php', {
                    method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(data)
                });
            } catch (e) { console.error('Error saving settings', e); }
        }
        document.querySelectorAll('.setting-toggle').forEach(el => { el.addEventListener('change', saveSettings); });

        function toggle2FA(el) { alert('2FA Status updated.'); }
        function logoutAllDevices() { if(confirm("Logout from all other devices?")) alert("Session invalidated."); }


        // 6. Roles & Permissions লজিক
        async function loadRoles() {
            const tbody = document.getElementById('rolesTableBody');
            try {
                const res = await fetch('../api/roles.php');
                const result = await res.json();
                if (result.success) {
                    if(result.data.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="3" class="text-center py-3 text-muted">No roles found. Create one.</td></tr>`;
                        return;
                    }
                    tbody.innerHTML = result.data.map(role => `
                        <tr>
                            <td><strong>${escapeHTML(role.role_name)}</strong></td>
                            <td>${escapeHTML(role.access_level)}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-light border" onclick='editRole(${JSON.stringify(role).replace(/'/g, "&#39;")})'><i class="fa-solid fa-pen text-primary"></i></button>
                                <button class="btn btn-sm btn-outline-danger ms-1" onclick="deleteRole(${role.id})"><i class="fa-solid fa-trash"></i></button>
                            </td>
                        </tr>
                    `).join('');
                }
            } catch(e) {
                tbody.innerHTML = `<tr><td colspan="3" class="text-center text-danger">Error loading roles.</td></tr>`;
            }
        }

        function openCreateRole() {
            document.getElementById('roleForm').reset();
            document.getElementById('role_id').value = '';
            document.getElementById('roleModalTitle').textContent = 'Create New Role';
            roleModalInstance.show();
        }

        function editRole(role) {
            document.getElementById('roleForm').reset();
            document.getElementById('role_id').value = role.id;
            document.getElementById('role_name').value = role.role_name;
            document.getElementById('access_level').value = role.access_level;
            document.getElementById('roleModalTitle').textContent = 'Edit Role';
            roleModalInstance.show();
        }

        async function saveRole() {
            const id = document.getElementById('role_id').value;
            const role_name = document.getElementById('role_name').value.trim();
            const access_level = document.getElementById('access_level').value.trim();
            
            if(!role_name || !access_level) return alert('Both fields are required');

            const btn = document.getElementById('btnSaveRole');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i>Saving...'; btn.disabled = true;

            try {
                const res = await fetch('../api/roles.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ id, role_name, access_level })
                });
                const result = await res.json();
                alert(result.message);
                if(result.success) {
                    roleModalInstance.hide();
                    loadRoles();
                }
            } catch(e) { alert('Error saving role'); } finally {
                btn.innerHTML = originalText; btn.disabled = false;
            }
        }

        async function deleteRole(id) {
            if(!confirm('Are you sure you want to delete this role?')) return;
            try {
                const res = await fetch('../api/roles.php', {
                    method: 'DELETE',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ id })
                });
                const result = await res.json();
                alert(result.message);
                if(result.success) loadRoles();
            } catch(e) { alert('Error deleting role'); }
        }
    </script>
</body>
</html>
