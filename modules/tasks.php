<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'employee') {
    header('Location: ../login.php'); exit;
}
require_once '../config/database.php';
$database = new Database(); $db = $database->getConnection();
$stmt = $db->prepare("SELECT first_name, last_name, profile_picture, position FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]); $user = $stmt->fetch(PDO::FETCH_ASSOC);
$firstName = $_SESSION['first_name'] ?? ($user['first_name'] ?? 'Employee');
$lastName = $_SESSION['last_name'] ?? ($user['last_name'] ?? '');
$fullName = trim($firstName . ' ' . $lastName);
$initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
$pic = $_SESSION['profile_picture'] ?? ($user['profile_picture'] ?? '');
$position = $_SESSION['position'] ?? ($user['position'] ?? 'Employee');
$isUrl = !empty($pic) && (strpos($pic, 'http://') === 0 || strpos($pic, 'https://') === 0);
$isLocal = !empty($pic) && !$isUrl && file_exists(__DIR__ . '/../' . $pic);
$profileImage = $isUrl ? $pic : ($isLocal ? '/../' . trim($pic, '/') : '');
function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks | KormoShathi</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
            --red: #ef4444;
            --purple: #8b5cf6;
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
            padding: 23px 16px 18px; overflow-y: auto; transition: transform .25s ease;
        }
        .brand { display: flex; align-items: center; gap: 11px; padding: 0 9px 23px; border-bottom: 1px solid rgba(255,255,255,.09); margin-bottom: 20px; }
        .brand-mark { width: 44px; height: 44px; display: grid; place-items: center; color: #fff; background: linear-gradient(145deg, #4e8bff, #2860dc); border-radius: 13px; font-size: 20px; box-shadow: 0 7px 18px rgba(53,109,243,.28); }
        .brand-name { color: #fff; font: 800 19px 'Plus Jakarta Sans', sans-serif; letter-spacing: -.5px; line-height: 1.15; }
        .brand-sub { color: #91a4bd; font-size: 11px; margin-top: 4px; letter-spacing: .55px; }
        .nav-caption { color: #71859f; font-size: 11px; font-weight: 700; letter-spacing: 1.15px; text-transform: uppercase; padding: 13px 12px 8px; }
        .side-nav { list-style: none; padding: 0; margin: 0; }
        .side-nav li { margin: 3px 0; }
        .side-link { display: flex; align-items: center; gap: 13px; min-height: 46px; padding: 10px 13px; border-radius: 9px; color: #aebdd0; font-size: 14px; font-weight: 500; transition: .18s ease; }
        .side-link i { width: 20px; text-align: center; font-size: 15px; }
        .side-link:hover { color: #fff; background: rgba(255,255,255,.075); }
        .side-link.active { color: #fff; background: linear-gradient(100deg, #3978f5, #2e66e6); box-shadow: 0 7px 18px rgba(31,91,218,.23); }
        .sidebar-bottom { margin-top: auto; padding-top: 16px; border-top: 1px solid rgba(255,255,255,.08); }

        /* MAIN CONTENT */
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; padding: 0 30px 30px; transition: margin 0.3s ease; }
        
        /* TOPBAR */
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

        /* PAGE HEADING & PANELS */
        .page-heading { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; margin-bottom: 22px; }
        .eyebrow { color: var(--primary); font-size: 11px; font-weight: 800; letter-spacing: 1.25px; text-transform: uppercase; margin-bottom: 7px; }
        h1 { font-family: 'Plus Jakarta Sans', sans-serif; color: var(--ink); font-size: 28px; font-weight: 800; letter-spacing: -.8px; margin: 0; }
        .page-heading p { color: var(--muted); margin: 6px 0 0; font-size: 14px; }

        .stat-card { background: var(--surface); border: 1px solid var(--line); border-radius: 16px; box-shadow: var(--shadow); padding: 20px; display: flex; align-items: center; gap: 15px; height: 100%; }
        .stat-icon { width: 50px; height: 50px; border-radius: 12px; display: grid; place-items: center; font-size: 20px; flex-shrink: 0; }
        .ic-blue { color: var(--primary); background: #edf3ff; }
        .ic-green { color: var(--green); background: #ecfdf5; }
        .ic-orange { color: var(--orange); background: #fff7ed; }
        .stat-label { color: var(--muted); font-size: 13px; font-weight: 600; margin-bottom: 4px; }
        .stat-value { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 26px; font-weight: 800; line-height: 1.2; }

        .panel { background: var(--surface); border: 1px solid var(--line); border-radius: 16px; box-shadow: var(--shadow); padding: 24px; margin-bottom: 24px; }
        .panel-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; border-bottom: 1px solid var(--line); padding-bottom: 15px;}
        .panel-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 16px; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 8px; }
        
        /* Table & UI */
        .table th { background: #f8fafc; color: var(--muted); font-size: 12px; text-transform: uppercase; padding: 15px 20px; border-bottom: 1px solid var(--line); font-weight: 700; }
        .table td { vertical-align: middle; padding: 15px 20px; font-size: 14px; border-bottom: 1px solid var(--line); }
        
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-block; text-transform: capitalize; }
        .status-completed { background: #ecfdf5; color: var(--green); border: 1px solid #a7f3d0; }
        .status-in_progress { background: #edf3ff; color: var(--primary); border: 1px solid #bfdbfe; }
        .status-pending { background: #fff7ed; color: var(--orange); border: 1px solid #fed7aa; }

        .progress { height: 8px; border-radius: 10px; background: #e2e8f0; }
        .progress-bar { border-radius: 10px; background: var(--primary); }
        
        .btn-update { background: var(--primary-soft); color: var(--primary); border: none; padding: 6px 12px; border-radius: 6px; font-weight: 600; font-size: 12px; transition: 0.2s; }
        .btn-update:hover { background: var(--primary); color: white; }
        
        .btn-download { background: #fff; color: var(--ink); border: 1px solid var(--line); padding: 10px 18px; border-radius: 8px; font-weight: 600; font-size: 14px; transition: 0.2s; }
        .btn-download:hover { background: #f8fafc; }
        
        .btn-primary { background: var(--primary); border: none; padding: 10px 18px; border-radius: 8px; font-weight: 600; font-size: 14px; transition: 0.2s; color: white; }
        .btn-primary:hover { background: #2859d9; }

        /* Modal */
        .modal-content { border-radius: 16px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
        .form-label { font-weight: 600; font-size: 13px; color: #435168; }
        .form-control, .form-select { border: 1px solid var(--line); padding: 10px 15px; border-radius: 8px; font-size: 14px; background-color: #fbfcfe; }
        .form-control:focus, .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(53,109,243,0.1); }

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
            <li><a href="tasks.php" class="side-link active"><i class="fa-solid fa-list-check"></i> My Tasks</a></li>
            <li><a href="performance.php" class="side-link"><i class="fa-solid fa-chart-line"></i> Performance</a></li>
            <li><a href="employee-settings.php" class="side-link"><i class="fa-solid fa-gear"></i> Settings</a></li>
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
            <div class="topbar-left">
                <button class="mobile-menu" type="button" onclick="toggleSidebar()" aria-label="Open menu"><i class="fa-solid fa-bars"></i></button>
                <h4 class="fw-bold m-0 d-flex align-items-center gap-2 me-3" style="font-family: 'Plus Jakarta Sans', sans-serif;"><i class="fa-solid fa-list-check text-primary"></i> My Tasks & Projects</h4>
            </div>
            <div class="topbar-right">
                <button class="top-icon" type="button" onclick="openNotifications()" title="Notifications" aria-label="Notifications">
                    <i class="fa-regular fa-bell"></i>
                    <span class="notification-badge" id="noticeBadge" style="display:none">0</span>
                </button>
                <span class="top-divider"></span>
                
                <button class="profile-button" type="button" aria-label="Profile">
                    <?php if ($profileImage): ?>
                        <img src="<?php echo e($profileImage); ?>" class="avatar" alt="Profile">
                    <?php else: ?>
                        <span class="avatar"><?php echo e($initials); ?></span>
                    <?php endif; ?>
                    <span class="profile-meta">
                        <span class="profile-name"><?php echo e($fullName); ?></span>
                        <span class="profile-role"><?php echo e($position); ?></span>
                    </span>
                    <i class="fa-solid fa-chevron-down ms-1" style="font-size:11px;color:#98a3b2"></i>
                </button>
            </div>
        </header>

        <section class="page-heading">
            <div>
                <div class="eyebrow">Work Progress</div>
                <h1>Task Manager</h1>
                <p class="text-muted mb-0">Track your assigned projects and update progress.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn-download" onclick="downloadReport()"><i class="fa-solid fa-file-arrow-down me-2"></i> Download Report</button>
                <button class="btn-primary" onclick="openAddModal()"><i class="fa-solid fa-plus me-2"></i> Add New Task</button>
            </div>
        </section>

        <!-- Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon ic-blue"><i class="fa-solid fa-list-check"></i></div>
                    <div><div class="stat-label">Total Tasks</div><div class="stat-value" id="totalTasks">0</div></div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon ic-green"><i class="fa-solid fa-circle-check"></i></div>
                    <div><div class="stat-label">Completed</div><div class="stat-value" id="completedTasks">0</div></div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon ic-orange"><i class="fa-solid fa-spinner"></i></div>
                    <div><div class="stat-label">In Progress</div><div class="stat-value" id="inProgressTasks">0</div></div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="stat-card">
                    <div class="stat-icon" style="background:#f5f3ff; color:#8b5cf6;"><i class="fa-solid fa-clock"></i></div>
                    <div><div class="stat-label">Pending</div><div class="stat-value" id="pendingTasks">0</div></div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Chart Section -->
            <div class="col-lg-4">
                <div class="panel h-100">
                    <div class="panel-header"><h5 class="panel-title"><i class="fa-solid fa-chart-pie text-primary"></i> Progress Overview</h5></div>
                    <div style="height: 250px; position: relative;">
                        <canvas id="taskChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Task List Section -->
            <div class="col-lg-8">
                <div class="panel h-100">
                    <div class="panel-header d-flex justify-content-between align-items-center">
                        <h5 class="panel-title"><i class="fa-solid fa-list-ul text-primary"></i> To-Do List</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Project / Task</th>
                                    <th>Deadline</th>
                                    <th>Progress</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody id="taskTableBody">
                                <tr><td colspan="5" class="text-center py-5 text-muted"><i class="fa-solid fa-spinner fa-spin fs-3 mb-3"></i><br> Loading tasks...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Add Task Modal -->
    <div class="modal fade" id="addTaskModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Add New Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label">Task / Project Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="newTaskName" placeholder="e.g. Design Dashboard UI">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description (Optional)</label>
                        <textarea class="form-control" id="newTaskDesc" rows="2" placeholder="Brief details about the task..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deadline (Last Date)</label>
                        <input type="date" class="form-control" id="newTaskDeadline">
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button class="btn btn-light fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary fw-bold" onclick="saveNewTask()">Save Task</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Task Modal -->
    <div class="modal fade" id="updateTaskModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bold">Update Task Progress</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" id="updateTaskId">
                    <h6 class="fw-bold text-primary mb-3" id="updateTaskTitle"></h6>
                    
                    <div class="mb-4">
                        <label class="form-label fw-bold small">Progress Percentage (%)</label>
                        <input type="range" class="form-range" min="0" max="100" step="10" id="updateProgress" oninput="document.getElementById('progressVal').textContent = this.value + '%'">
                        <div class="text-end mt-1 fw-bold text-primary" id="progressVal">0%</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Status</label>
                        <select class="form-select" id="updateStatus">
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button class="btn btn-light fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-primary fw-bold" onclick="saveTaskProgress()">Save Changes</button>
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
        // Task Manager Logic
        // ==========================================
        let allTasks = [];
        let taskChartInstance = null;

        async function loadTasks() {
            try {
                const response = await fetch('../api/tasks.php');
                const result = await response.json();
                if(result.success) {
                    allTasks = result.data;
                    renderStatsAndChart();
                    renderTable();
                }
            } catch(e) { console.error(e); }
        }

        function renderStatsAndChart() {
            let pending = 0, progress = 0, completed = 0;
            allTasks.forEach(t => {
                if(t.status === 'completed') completed++;
                else if(t.status === 'in_progress') progress++;
                else pending++;
            });

            document.getElementById('totalTasks').textContent = allTasks.length;
            document.getElementById('completedTasks').textContent = completed;
            document.getElementById('inProgressTasks').textContent = progress;
            document.getElementById('pendingTasks').textContent = pending;

            if(taskChartInstance) taskChartInstance.destroy();
            const ctx = document.getElementById('taskChart').getContext('2d');
            taskChartInstance = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Completed', 'In Progress', 'Pending'],
                    datasets: [{
                        data: [completed, progress, pending],
                        backgroundColor: ['#10b981', '#356df3', '#f59e0b'],
                        borderWidth: 0,
                        hoverOffset: 5
                    }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '75%',
                    plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, font: {size: 11} } } }
                }
            });
        }

        function renderTable() {
            const tbody = document.getElementById('taskTableBody');
            if(allTasks.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center py-5 text-muted">No tasks assigned yet. Click "Add New Task" to create one.</td></tr>';
                return;
            }

            tbody.innerHTML = allTasks.map(t => {
                let badgeColor = t.status === 'completed' ? 'status-completed' : (t.status === 'in_progress' ? 'status-in_progress' : 'status-pending');
                let barColor = t.status === 'completed' ? '#10b981' : (t.status === 'in_progress' ? '#356df3' : '#f59e0b');
                let displayStatus = t.status.replace('_', ' ');
                
                let deadlineHtml = '<span class="text-muted">No Deadline</span>';
                if(t.deadline) {
                    const deadlineDate = new Date(t.deadline);
                    const today = new Date();
                    today.setHours(0,0,0,0);
                    const isOverdue = deadlineDate < today && t.status !== 'completed';
                    
                    const dateStr = deadlineDate.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                    deadlineHtml = `<span class="${isOverdue ? 'text-danger fw-bold' : 'text-dark'}"><i class="fa-regular fa-calendar me-1"></i>${dateStr} ${isOverdue ? '(Overdue)' : ''}</span>`;
                }

                return `
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">${t.task_name}</div>
                            <div class="text-muted small" style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${t.description || 'No description'}</div>
                        </td>
                        <td>${deadlineHtml}</td>
                        <td style="width: 20%;">
                            <div class="d-flex justify-content-between mb-1"><span class="small fw-bold text-dark">${t.progress}%</span></div>
                            <div class="progress"><div class="progress-bar" style="width: ${t.progress}%; background: ${barColor};"></div></div>
                        </td>
                        <td><span class="status-badge ${badgeColor}">${displayStatus}</span></td>
                        <td class="text-end">
                            <button class="btn-update" onclick="openUpdateModal(${t.id}, '${t.task_name.replace(/'/g, "\\'")}', ${t.progress}, '${t.status}')">
                                <i class="fa-solid fa-pen-to-square"></i> Update
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        // Add Task Logic
        function openAddModal() {
            document.getElementById('newTaskName').value = '';
            document.getElementById('newTaskDesc').value = '';
            document.getElementById('newTaskDeadline').value = '';
            new bootstrap.Modal(document.getElementById('addTaskModal')).show();
        }

        async function saveNewTask() {
            const name = document.getElementById('newTaskName').value.trim();
            const desc = document.getElementById('newTaskDesc').value.trim();
            const deadline = document.getElementById('newTaskDeadline').value;

            if(!name) { alert('Task name is required.'); return; }

            const btn = document.querySelector('#addTaskModal .btn-primary');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...'; btn.disabled = true;

            try {
                const res = await fetch('../api/tasks.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ action: 'create', task_name: name, description: desc, deadline: deadline })
                });
                const result = await res.json();
                if(result.success) {
                    bootstrap.Modal.getInstance(document.getElementById('addTaskModal')).hide();
                    loadTasks();
                } else { alert(result.message); }
            } catch(e) { alert('Error saving task'); } finally {
                btn.innerHTML = originalText; btn.disabled = false;
            }
        }

        // Update Task Logic
        function openUpdateModal(id, title, progress, status) {
            document.getElementById('updateTaskId').value = id;
            document.getElementById('updateTaskTitle').textContent = title;
            document.getElementById('updateProgress').value = progress;
            document.getElementById('progressVal').textContent = progress + '%';
            document.getElementById('updateStatus').value = status;
            new bootstrap.Modal(document.getElementById('updateTaskModal')).show();
        }

        async function saveTaskProgress() {
            const id = document.getElementById('updateTaskId').value;
            const progress = document.getElementById('updateProgress').value;
            const status = document.getElementById('updateStatus').value;

            const btn = document.querySelector('#updateTaskModal .btn-primary');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving...'; btn.disabled = true;

            try {
                const res = await fetch('../api/tasks.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ action: 'update', id: id, progress: progress, status: status })
                });
                const result = await res.json();
                if(result.success) {
                    bootstrap.Modal.getInstance(document.getElementById('updateTaskModal')).hide();
                    loadTasks();
                } else { alert(result.message); }
            } catch(e) { alert('Error updating task'); } finally {
                btn.innerHTML = originalText; btn.disabled = false;
            }
        }

        // Download Report
        function downloadReport() {
            if(allTasks.length === 0) { alert('No tasks to download.'); return; }
            let csvContent = "data:text/csv;charset=utf-8,";
            csvContent += "Task Name,Description,Deadline,Status,Progress,Created At\n";

            allTasks.forEach(t => {
                let desc = t.description ? t.description.replace(/,/g, " ") : "N/A";
                let dl = t.deadline ? t.deadline : "N/A";
                csvContent += `"${t.task_name}","${desc}","${dl}","${t.status}","${t.progress}%","${t.created_at}"\n`;
            });

            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", `my_tasks_report_${new Date().toISOString().split('T')[0]}.csv`);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadTasks();
            loadNotificationCount();
            setInterval(loadNotificationCount, 5000);
        });
    </script>
</body>
</html>
