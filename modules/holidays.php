<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employee') {
    header('Location: ../login.php');
    exit;
}

require_once '../config/database.php';
date_default_timezone_set('Asia/Dhaka');

$database = new Database();
$db = $database->getConnection();
$userId = $_SESSION['user_id'];

// Get User Data for Topbar
$stmt = $db->prepare("SELECT first_name, last_name, profile_picture, position FROM users WHERE id = :id");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$firstName = $_SESSION['first_name'] ?? ($user['first_name'] ?? 'Employee');
$lastName = $_SESSION['last_name'] ?? ($user['last_name'] ?? '');
$initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
$pic = $_SESSION['profile_picture'] ?? ($user['profile_picture'] ?? '');
$position = $user['position'] ?? ($_SESSION['position'] ?? 'Employee');

$isUrl = !empty($pic) && (strpos($pic, 'http://') === 0 || strpos($pic, 'https://') === 0);
$isLocal = !empty($pic) && !$isUrl && file_exists(__DIR__ . '/../' . $pic);
$profileImage = '';
if ($isUrl) { $profileImage = $pic; } 
elseif ($isLocal) { $profileImage = '/' . trim($pic, '/'); }

// Unread Notifications
$stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$userId]);
$unread_count = (int) $stmt->fetchColumn();

function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }

// 🟢 ডাটাবেস থেকে ছুটির দিনগুলো লোড করা হচ্ছে
$stmt = $db->query("SELECT * FROM holidays ORDER BY start_date ASC");
$holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Holiday Calendar | KormoShathi</title>

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
        .page-heading { display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 12px; }
        .page-heading h1 { font-size: 28px; font-weight: 800; margin: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        .date-chip { display: inline-flex; align-items: center; gap: 9px; padding: 10px 14px; border: 1px solid var(--line); background: #fff; border-radius: 10px; font-size: 13px; font-weight: 600; }

        /* PANEL & CARDS */
        .panel { background: var(--surface); border: 1px solid var(--line); border-radius: 16px; box-shadow: var(--shadow); padding: 24px; margin-bottom: 24px; }
        
        .holiday-card {
            display: flex;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 12px;
            overflow: hidden;
            transition: 0.3s;
            height: 100%;
        }
        .holiday-card:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        
        .holiday-date-box {
            background: var(--primary-soft);
            color: var(--primary);
            width: 90px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border-right: 1px solid var(--line);
            flex-shrink: 0;
        }
        .holiday-date-box .month { font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; }
        .holiday-date-box .day { font-size: 32px; font-weight: 800; line-height: 1; font-family: 'Plus Jakarta Sans', sans-serif; margin-top: 2px; }
        
        .holiday-info { padding: 15px 20px; display: flex; flex-direction: column; justify-content: center; }
        .holiday-info h6 { margin: 0 0 5px 0; font-weight: 800; font-size: 15px; color: var(--ink); }
        .holiday-info .day-name { font-size: 12px; color: var(--muted); margin-bottom: 8px; font-weight: 600; }
        
        .badge-type { font-size: 10px; font-weight: 700; padding: 4px 10px; border-radius: 20px; display: inline-block; width: fit-content; text-transform: uppercase; }
        .badge-type.primary { background: #edf3ff; color: var(--primary); }
        .badge-type.success { background: #ecfdf5; color: var(--green); }
        .badge-type.warning { background: #fff7ed; color: var(--orange); }
        .badge-type.danger { background: #fef2f2; color: var(--red); }

        /* MOBILE */
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 1030; }
        @media (max-width: 991px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.active { transform: translateX(0); }
            .main-content { margin-left: 0; padding: 0 20px 20px; }
            .sidebar-overlay.active { display: block; }
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
            <li><a href="employee-settings.php" class="side-link"><i class="fa-solid fa-gear"></i> Settings</a></li>
            <li><a href="../profile.php" class="side-link"><i class="fa-solid fa-user"></i> My Profile</a></li>
        </ul>

        <div class="nav-caption">Company</div>
        <ul class="side-nav">
            <li><a href="notice-board.php" class="side-link"><i class="fa-solid fa-bullhorn"></i> Notice & Events</a></li>
            <li><a href="documents.php" class="side-link"><i class="fa-regular fa-file-pdf"></i> My Documents</a></li>
            <li><a href="holidays.php" class="side-link active"><i class="fa-solid fa-umbrella-beach"></i> Holiday Calendar</a></li>
        </ul>

        <div class="sidebar-bottom">
            <ul class="side-nav">
                <li><a href="../logout.php" class="side-link text-danger"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li>
            </ul>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- TOPBAR -->
        <header class="topbar">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light d-lg-none" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
                <h4 class="fw-bold m-0 d-flex align-items-center gap-2" style="font-family: 'Plus Jakarta Sans', sans-serif;"><i class="fa-solid fa-umbrella-beach text-primary"></i> Holiday Calendar</h4>
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
                <div class="text-primary fw-bold" style="font-size: 12px; letter-spacing: 1px; text-transform: uppercase;">Company Calendar</div>
                <p class="text-muted mb-0 mt-1">View all upcoming public and company holidays.</p>
            </div>
        </div>

        <!-- MAIN CONTENT AREA -->
        <div class="row g-4">
            <div class="col-12">
                <div class="panel">
                    <div class="panel-header d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                        <h5 class="panel-title fw-bold m-0"><i class="fa-solid fa-calendar-days text-primary me-2"></i> Upcoming Holidays</h5>
                        <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-bold"><?php echo count($holidays); ?> Total Holidays</span>
                    </div>

                    <div class="row g-3">
                        <?php if (count($holidays) > 0): ?>
                            <?php foreach($holidays as $holiday): 
                                $startTimestamp = strtotime($holiday['start_date']);
                                $endTimestamp = strtotime($holiday['end_date']);
                                
                                $startMonth = date('M', $startTimestamp);
                                $startDay = date('d', $startTimestamp);
                                
                                // 🟢 তারিখ ফরম্যাট করা (একাধিক দিন থাকলে রেঞ্জ দেখাবে)
                                if ($holiday['start_date'] === $holiday['end_date'] || empty($holiday['end_date'])) {
                                    $dateDisplay = date('d M Y', $startTimestamp);
                                } else {
                                    $dateDisplay = date('d M', $startTimestamp) . ' - ' . date('d M Y', $endTimestamp);
                                }
                                
                                // কালার সেট করা
                                $color = 'primary';
                                if ($holiday['holiday_type'] == 'National Holiday') $color = 'success';
                                if ($holiday['holiday_type'] == 'Festival') $color = 'warning';
                                if ($holiday['holiday_type'] == 'Company Event') $color = 'danger';
                            ?>
                                <div class="col-md-6 col-xl-4">
                                    <div class="holiday-card">
                                        <div class="holiday-date-box">
                                            <span class="month"><?php echo $startMonth; ?></span>
                                            <span class="day"><?php echo $startDay; ?></span>
                                        </div>
                                        <div class="holiday-info">
                                            <h6><?php echo htmlspecialchars($holiday['holiday_name']); ?></h6>
                                            <div class="day-name"><i class="fa-regular fa-calendar me-1"></i> <?php echo $dateDisplay; ?></div>
                                            <div class="badge-type <?php echo $color; ?>">
                                                <?php echo htmlspecialchars($holiday['holiday_type']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12 text-center py-5 text-muted">
                                <i class="fa-regular fa-calendar-xmark fs-1 mb-3"></i>
                                <h6>No holidays scheduled yet.</h6>
                                <p class="small">HR has not added any holidays to the calendar.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

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
        const BD_TIME_ZONE = 'Asia/Dhaka';

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('mobileOverlay').classList.toggle('active');
        }

        function escapeHTML(value) {
            return String(value ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[c]));
        }

        function formatBDTime(value) {
            if (!value) return '--:--';
            const raw = String(value).trim();

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

        // ==========================================
        // NOTIFICATIONS (same as employee dashboard)
        // ==========================================
        async function loadNotificationCount() {
            try {
                const response = await fetch('../api/notifications.php?count=1', { cache: 'no-store' });
                const data = await response.json();
                const unread = Number(data.unread) || 0;
                const badge = document.getElementById('noticeBadge');
                badge.textContent = unread;
                badge.style.display = unread > 0 ? 'flex' : 'none';
            } catch (error) {}
        }

        async function openNotifications(e) {
            if (e) e.preventDefault();
            await loadNotifications();
            notificationModal.show();
        }

        async function loadNotifications() {
            const container = document.getElementById('notificationList');
            container.innerHTML = `<div class="text-center py-4"><i class="fa-solid fa-spinner fa-spin"></i></div>`;
            try {
                const response = await fetch('../api/notifications.php', { cache: 'no-store' });
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
                await fetch('../api/notifications.php', {
                    method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id })
                });
                await loadNotifications(); await loadNotificationCount();
            } catch (error) {}
        }

        async function markAllAsReadAndClose() {
            try {
                await fetch('../api/notifications.php', {
                    method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: 0 })
                });
                await loadNotifications(); await loadNotificationCount(); notificationModal.hide();
            } catch (error) {}
        }

        document.addEventListener('DOMContentLoaded', () => {
            notificationModal = new bootstrap.Modal(document.getElementById('notificationModal'));

            loadNotificationCount();
            setInterval(loadNotificationCount, 15000);
        });
    </script>
</body>
</html>