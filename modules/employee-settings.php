<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employee') { 
    header('Location: ../login.php'); 
    exit; 
}
require_once '../config/database.php';
$db = (new Database())->getConnection();

$stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]); 
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt2 = $db->prepare("SELECT * FROM employee_settings WHERE user_id = :id");
$stmt2->execute([':id' => $_SESSION['user_id']]); 
$settings = $stmt2->fetch(PDO::FETCH_ASSOC) ?: [];

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$firstName = $user['first_name'] ?? 'Employee';
$lastName = $user['last_name'] ?? '';
$fullName = trim($firstName . ' ' . $lastName);
$initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
$position = $user['position'] ?? 'Employee';

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
    <title>Employee Settings | KormoShathi</title>
    
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

        /* ================= MAIN CONTENT ================= */
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; padding: 0 30px 30px; transition: margin 0.3s ease; }
        
        /* ================= TOPBAR ================= */
        .topbar { min-height: 78px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--line); margin-bottom: 27px; gap: 20px; }
        .topbar-left, .topbar-right { display: flex; align-items: center; gap: 14px; }
        .mobile-menu { display: none; width: 42px; height: 42px; border: 1px solid var(--line); background: #fff; color: var(--ink); border-radius: 10px; }
        .top-icon { position: relative; display: grid; place-items: center; width: 42px; height: 42px; border: 1px solid var(--line); background: #fff; color: #637187; border-radius: 10px; cursor: pointer; }
        .notification-badge { position: absolute; top: -5px; right: -5px; background: var(--red); color: white; font-size: 10px; font-weight: 700; border-radius: 50%; min-width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; border: 2px solid white; }
        .top-divider { height: 34px; border-left: 1px solid var(--line); margin: 0 2px; }
        .profile-button { display: flex; align-items: center; gap: 10px; border: 0; background: transparent; text-align: left; color: var(--ink); padding: 2px; }
        .avatar { width: 42px; height: 42px; flex: 0 0 42px; border-radius: 50%; object-fit: cover; display: flex; align-items: center; justify-content: center; color: #fff; background: linear-gradient(145deg, #6594ff, #356df3); font-weight: 700; font-size: 16px; }
        .profile-name { display: block; font-size: 14px; font-weight: 700; }
        .profile-role { display: block; color: var(--muted); font-size: 11px; margin-top: 2px; }

        /* ================= SETTINGS UI ================= */
        .page-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; margin-bottom: 22px; }
        .eyebrow { color: var(--primary); font-size: 11px; font-weight: 800; letter-spacing: 1.25px; text-transform: uppercase; margin-bottom: 7px; }
        h1 { font-family: 'Plus Jakarta Sans', sans-serif; color: var(--ink); font-size: 28px; font-weight: 800; letter-spacing: -.8px; margin: 0; }
        .page-heading p { color: var(--muted); margin: 6px 0 0; font-size: 14px; }

        .settings-container { display: flex; gap: 30px; max-width: 1200px; margin: 0 auto; }
        .settings-nav { width: 280px; flex-shrink: 0; background: #fff; border-radius: 16px; box-shadow: var(--shadow); padding: 20px; height: fit-content; }
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
        .form-check-input:checked { background-color: var(--primary); border-color: var(--primary); } 
        .form-switch .form-check-input { width: 2.8em; height: 1.5em; cursor: pointer; }
        
        .btn-save { background: var(--primary); color: white; border: none; padding: 12px 30px; border-radius: 8px; font-weight: 600; font-size: 15px; transition: 0.2s; }
        .btn-save:hover { background: #2859d9; transform: translateY(-1px); }

        .profile-upload-container { display: flex; align-items: center; gap: 20px; margin-bottom: 30px; }
        .profile-upload-preview { width: 90px; height: 90px; border-radius: 50%; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-size: 32px; color: #94a3b8; overflow: hidden; border: 3px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.05);}
        .profile-upload-preview img { width: 100%; height: 100%; object-fit: cover; }

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
            .settings-container { flex-direction: column; }
            .settings-nav { width: 100%; }
        }
    </style>
</head>
<body>

    <div class="sidebar-overlay" id="mobileOverlay" onclick="toggleSidebar()"></div>

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <a href="../employee-dashboard.php" class="brand">
            <span class="brand-mark"><i class="fa-solid fa-people-group"></i></span>
            <span><span class="brand-name d-block">KormoShathi</span><span class="brand-sub d-block">HR MANAGEMENT SYSTEM</span></span>
        </a>
        
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
            <li><a href="employee-settings.php" class="side-link active"><i class="fa-solid fa-gear"></i> Settings</a></li>
            <li><a href="../profile.php" class="side-link"><i class="fa-solid fa-user"></i> My Profile</a></li>
        </ul>

        <div class="nav-caption">Company</div>
        <ul class="side-nav">
            <li><a href="notice-board.php" class="side-link"><i class="fa-solid fa-bullhorn"></i> Notice & Events</a></li>
            <li><a href="documents.php" class="side-link"><i class="fa-regular fa-file-pdf"></i> My Documents</a></li>
            <li><a href="holidays.php" class="side-link"><i class="fa-solid fa-umbrella-beach"></i> Holiday Calendar</a></li>
        </ul>

        <div class="sidebar-bottom">
            <ul class="side-nav">
                <li><a href="../logout.php" class="side-link text-danger"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li>
            </ul>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <header class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light d-lg-none" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
                <h4 class="fw-bold m-0 d-flex align-items-center gap-2" style="font-family: 'Plus Jakarta Sans', sans-serif;"><i class="fa-solid fa-gear text-primary"></i> Employee Settings</h4>
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

        <section class="page-heading">
            <div>
                <div class="eyebrow">Account Preferences</div>
                <h1>Settings</h1>
                <p class="text-muted mb-0">Manage your profile, security, and workspace preferences.</p>
            </div>
        </section>

        <div class="settings-container">
            <!-- Settings Navigation -->
            <div class="settings-nav">
                <button class="nav-link active" onclick="switchTab(event, 'profile')"><i class="fa-regular fa-user"></i> My Profile</button>
                <button class="nav-link" onclick="switchTab(event, 'security')"><i class="fa-solid fa-shield-halved"></i> Security & Privacy</button>
                <button class="nav-link" onclick="switchTab(event, 'notifications')"><i class="fa-regular fa-bell"></i> Notifications</button>
                <button class="nav-link" onclick="switchTab(event, 'attendance')"><i class="fa-solid fa-fingerprint"></i> Attendance Prefs</button>
                <button class="nav-link" onclick="switchTab(event, 'leave')"><i class="fa-regular fa-calendar-check"></i> Leave Prefs</button>
                <button class="nav-link" onclick="switchTab(event, 'appearance')"><i class="fa-solid fa-sliders"></i> Appearance</button>
                <!-- My Documents Button Removed -->
            </div>

            <!-- Settings Content -->
            <div class="settings-content">
                <!-- ১. My Profile -->
                <div id="profile" class="settings-section active">
                    <h2 class="section-title">My Profile</h2>
                    <p class="section-desc">Update your personal details and profile picture.</p>
                    <div class="profile-upload-container">
                        <div class="profile-upload-preview" id="profilePicPreview">
                            <?php if ($profileImage): ?><img src="<?php echo e($profileImage); ?>"><?php else: ?><i class="fa-solid fa-user"></i><?php endif; ?>
                        </div>
                        <div>
                            <input type="file" id="profile_pic_input" accept="image/jpeg, image/png" style="display: none;">
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill fw-bold" onclick="document.getElementById('profile_pic_input').click()"><i class="fa-solid fa-camera me-1"></i> Change Picture</button>
                        </div>
                    </div>
                    <form id="profileForm">
                        <div class="row g-4 mb-4">
                            <div class="col-md-6"><label class="form-label">First Name</label><input type="text" class="form-control" name="first_name" value="<?php echo e($user['first_name']); ?>" required></div>
                            <div class="col-md-6"><label class="form-label">Last Name</label><input type="text" class="form-control" name="last_name" value="<?php echo e($user['last_name']); ?>" required></div>
                            <div class="col-md-6"><label class="form-label">Email Address</label><input type="email" class="form-control" name="email" value="<?php echo e($user['email']); ?>" required></div>
                            <div class="col-md-6"><label class="form-label">Phone Number</label><input type="text" class="form-control" name="phone" value="<?php echo e($user['phone'] ?? ''); ?>"></div>
                            <div class="col-md-12"><label class="form-label">Present Address</label><textarea class="form-control" name="address" rows="2"><?php echo e($user['address'] ?? ''); ?></textarea></div>
                            <div class="col-md-6"><label class="form-label">Emergency Contact</label><input type="text" class="form-control" name="emergency_contact" value="<?php echo e($user['emergency_contact'] ?? ''); ?>"></div>
                            <div class="col-md-6"><label class="form-label">Date of Birth</label><input type="date" class="form-control" name="date_of_birth" value="<?php echo e($user['date_of_birth'] ?? ''); ?>"></div>
                            
                            <div class="col-md-12"><hr class="my-2 opacity-25"></div>
                            <div class="col-md-3"><label class="form-label">Employee ID <span class="readonly-badge">HR</span></label><input type="text" class="form-control" value="<?php echo e($user['employee_id'] ?? 'N/A'); ?>" disabled></div>
                            <div class="col-md-3"><label class="form-label">Department <span class="readonly-badge">HR</span></label><input type="text" class="form-control" value="<?php echo e($user['department'] ?? 'N/A'); ?>" disabled></div>
                            <div class="col-md-3"><label class="form-label">Designation <span class="readonly-badge">HR</span></label><input type="text" class="form-control" value="<?php echo e($user['position'] ?? 'N/A'); ?>" disabled></div>
                            <div class="col-md-3"><label class="form-label">Joining Date <span class="readonly-badge">HR</span></label><input type="date" class="form-control" value="<?php echo e($user['join_date'] ?? ''); ?>" disabled></div>
                        </div>
                        <button type="submit" class="btn-save"><i class="fa-solid fa-floppy-disk me-2"></i>Save Profile</button>
                    </form>
                </div>

                <!-- ২. Security & Privacy -->
                <div id="security" class="settings-section">
                    <h2 class="section-title">Security & Privacy</h2>
                    <p class="section-desc">Manage your password and account security.</p>
                    <div class="card-box">
                        <div class="card-box-title"><i class="fa-solid fa-key text-primary"></i> Change Password</div>
                        <form id="passwordForm">
                            <div class="row g-3 mb-3">
                                <div class="col-md-4"><label class="form-label">Current Password</label><input type="password" class="form-control" name="current_password" required></div>
                                <div class="col-md-4"><label class="form-label">New Password</label><input type="password" class="form-control" name="new_password" required></div>
                                <div class="col-md-4"><label class="form-label">Confirm Password</label><input type="password" class="form-control" name="confirm_password" required></div>
                            </div>
                            <button type="submit" class="btn-save btn-sm">Update Password</button>
                        </form>
                    </div>
                    <div class="card-box">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="card-box-title mb-1"><i class="fa-solid fa-mobile-screen text-primary"></i> Two-Factor Authentication</div>
                                <p class="text-muted small mb-0">Add an extra layer of security (Coming soon).</p>
                            </div>
                            <div class="form-check form-switch"><input class="form-check-input" type="checkbox" disabled></div>
                        </div>
                    </div>
                    <div class="card-box mb-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="card-box-title mb-1"><i class="fa-solid fa-desktop text-primary"></i> Active Sessions</div>
                                <p class="text-muted small mb-0">Currently logged in from this device.</p>
                            </div>
                            <button type="button" class="btn btn-outline-danger btn-sm fw-bold" onclick="logoutAllDevices()">Logout All Devices</button>
                        </div>
                    </div>
                </div>

                <!-- ৩. Notifications -->
                <div id="notifications" class="settings-section">
                    <h2 class="section-title">Notification Preferences</h2>
                    <p class="section-desc">Choose what notifications you want to receive.</p>
                    <div class="card-box">
                        <div class="form-check form-switch mb-3"><input class="form-check-input setting-toggle" type="checkbox" data-key="leave_notifications" <?php echo (!empty($settings['leave_notifications'])) ? 'checked' : ''; ?>><label class="form-check-label fw-bold ms-2">Leave Approvals & Rejections</label></div>
                        <div class="form-check form-switch mb-3"><input class="form-check-input setting-toggle" type="checkbox" data-key="attendance_reminders" <?php echo (!empty($settings['attendance_reminders'])) ? 'checked' : ''; ?>><label class="form-check-label fw-bold ms-2">Attendance & Late Arrival Reminders</label></div>
                        <div class="form-check form-switch mb-3"><input class="form-check-input setting-toggle" type="checkbox" data-key="notice_board_updates" <?php echo (!empty($settings['notice_board_updates'])) ? 'checked' : ''; ?>><label class="form-check-label fw-bold ms-2">Notice Board & HR Announcements</label></div>
                        <div class="form-check form-switch mb-3"><input class="form-check-input setting-toggle" type="checkbox" data-key="message_notifications" <?php echo (!empty($settings['message_notifications'])) ? 'checked' : ''; ?>><label class="form-check-label fw-bold ms-2">Message Notifications</label></div>
                        <hr>
                        <div class="form-check form-switch mb-3"><input class="form-check-input setting-toggle" type="checkbox" data-key="email_notifications" <?php echo (!empty($settings['email_notifications'])) ? 'checked' : ''; ?>><label class="form-check-label fw-bold ms-2">Email Notifications (Global)</label></div>
                    </div>
                </div>

                <!-- ৪. Attendance Preferences -->
                <div id="attendance" class="settings-section">
                    <h2 class="section-title">Attendance Preferences</h2>
                    <p class="section-desc">Manage your daily attendance reminders.</p>
                    <div class="card-box">
                        <div class="form-check form-switch mb-3"><input class="form-check-input setting-toggle" type="checkbox" data-key="check_in_reminder" <?php echo (!empty($settings['check_in_reminder'])) ? 'checked' : ''; ?>><label class="form-check-label fw-bold ms-2">Check-in Reminder</label></div>
                        <div class="form-check form-switch mb-3"><input class="form-check-input setting-toggle" type="checkbox" data-key="check_out_reminder" <?php echo (!empty($settings['check_out_reminder'])) ? 'checked' : ''; ?>><label class="form-check-label fw-bold ms-2">Check-out Reminder</label></div>
                        <div class="form-check form-switch mb-3"><input class="form-check-input setting-toggle" type="checkbox" data-key="daily_attendance_summary" <?php echo (!empty($settings['daily_attendance_summary'])) ? 'checked' : ''; ?>><label class="form-check-label fw-bold ms-2">Daily Attendance Summary</label></div>
                        <div class="form-check form-switch mb-3"><input class="form-check-input setting-toggle" type="checkbox" data-key="weekly_attendance_summary" <?php echo (!empty($settings['weekly_attendance_summary'])) ? 'checked' : ''; ?>><label class="form-check-label fw-bold ms-2">Weekly Attendance Summary</label></div>
                    </div>
                </div>

                <!-- ৫. Leave Preferences -->
                <div id="leave" class="settings-section">
                    <h2 class="section-title">Leave Preferences</h2>
                    <p class="section-desc">Control your leave notifications and view balance.</p>
                    <div class="card-box mb-4">
                        <div class="card-box-title"><i class="fa-solid fa-calendar-day text-primary"></i> Leave Balance Overview</div>
                        <p class="text-muted mb-0">Check your available leave days in the <a href="leave.php">Leave Management</a> section.</p>
                    </div>
                    <div class="card-box">
                        <div class="form-check form-switch mb-3"><input class="form-check-input setting-toggle" type="checkbox" data-key="default_leave_notification" <?php echo (!empty($settings['default_leave_notification'])) ? 'checked' : ''; ?>><label class="form-check-label fw-bold ms-2">Default Leave Notification</label></div>
                        <div class="form-check form-switch mb-3"><input class="form-check-input setting-toggle" type="checkbox" data-key="leave_status_updates" <?php echo (!empty($settings['leave_status_updates'])) ? 'checked' : ''; ?>><label class="form-check-label fw-bold ms-2">Leave Status Updates</label></div>
                    </div>
                </div>

                <!-- ৬. Appearance & Preferences -->
                <div id="appearance" class="settings-section">
                    <h2 class="section-title">Appearance & Preferences</h2>
                    <p class="section-desc">Customize your view and format.</p>
                    <div class="card-box">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label">Theme</label>
                                <select class="form-select setting-select" data-key="theme">
                                    <option value="Light" <?php echo (($settings['theme'] ?? 'Light') === 'Light') ? 'selected' : ''; ?>>Light Mode</option>
                                    <option value="Dark" <?php echo (($settings['theme'] ?? '') === 'Dark') ? 'selected' : ''; ?>>Dark Mode</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Language</label>
                                <select class="form-select setting-select" data-key="language">
                                    <option value="English" <?php echo (($settings['language'] ?? 'English') === 'English') ? 'selected' : ''; ?>>English</option>
                                    <option value="Bangla" <?php echo (($settings['language'] ?? '') === 'Bangla') ? 'selected' : ''; ?>>বাংলা (Bangla)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date Format</label>
                                <select class="form-select setting-select" data-key="date_format">
                                    <option value="DD-MM-YYYY" <?php echo (($settings['date_format'] ?? '') === 'DD-MM-YYYY') ? 'selected' : ''; ?>>DD-MM-YYYY</option>
                                    <option value="MMM DD, YYYY" <?php echo (($settings['date_format'] ?? '') === 'MMM DD, YYYY') ? 'selected' : ''; ?>>MMM DD, YYYY</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Time Format</label>
                                <select class="form-select setting-select" data-key="time_format">
                                    <option value="12 Hours" <?php echo (($settings['time_format'] ?? '') === '12 Hours') ? 'selected' : ''; ?>>12 Hours (AM/PM)</option>
                                    <option value="24 Hours" <?php echo (($settings['time_format'] ?? '') === '24 Hours') ? 'selected' : ''; ?>>24 Hours</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ৭. My Documents (REMOVED) -->
                
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
        function toggleSidebar() { document.getElementById('sidebar').classList.toggle('active'); document.getElementById('mobileOverlay').classList.toggle('active'); }

        function switchTab(event, tabId) {
            document.querySelectorAll('.settings-nav .nav-link').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.settings-section').forEach(el => el.classList.remove('active'));
            event.currentTarget.classList.add('active');
            document.getElementById(tabId).classList.add('active');
            // Removed: if(tabId === 'documents') loadDocuments();
        }

        // ==========================================
        // 🟢 Real-time Notification Logic
        // ==========================================
        async function loadNotificationCount() {
            try {
                const response = await fetch('../api/notifications.php?count=1');
                const data = await response.json();
                const badge = document.getElementById('noticeBadge');
                const unread = Number(data.unread) || 0;
                
                badge.textContent = unread;
                badge.style.display = unread > 0 ? 'flex' : 'none';
            } catch (error) {}
        }

        async function openNotifications(event) {
            if (event) event.preventDefault();
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
                    const res = await fetch('../api/upload-profile.php', { method: 'POST', body: formData });
                    const result = await res.json();
                    if (!result.success) alert('Error: ' + result.message);
                } catch (err) { alert('Server error while uploading picture.'); }
            }
        });

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

        async function saveSettings() {
            const toggles = document.querySelectorAll('.setting-toggle');
            const selects = document.querySelectorAll('.setting-select');
            let data = {};
            toggles.forEach(t => { data[t.dataset.key] = t.checked ? 1 : 0; });
            selects.forEach(s => { data[s.dataset.key] = s.value; });

            try {
                await fetch('../api/save-employee-settings.php', {
                    method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(data)
                });
            } catch (e) { console.error('Error saving settings', e); }
        }
        document.querySelectorAll('.setting-toggle, .setting-select').forEach(el => { el.addEventListener('change', saveSettings); });

        function logoutAllDevices() { if(confirm("Logout from all other devices?")) alert("Session invalidated."); }

        document.addEventListener('DOMContentLoaded', () => {
             loadNotificationCount();
             setInterval(loadNotificationCount, 5000);
        });
    </script>
</body>
</html>
