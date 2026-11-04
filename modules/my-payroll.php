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
    <title>My Salary & Payslips | KormoShathi</title>
    
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

        .panel { background: var(--surface); border: 1px solid var(--line); border-radius: 16px; box-shadow: var(--shadow); padding: 24px; margin-bottom: 24px; }
        
        .table th { background: #f8fafc; color: var(--muted); font-size: 12px; text-transform: uppercase; padding: 15px 20px; border-bottom: 1px solid var(--line); font-weight: 700; }
        .table td { vertical-align: middle; padding: 15px 20px; font-size: 14px; border-bottom: 1px solid var(--line); }
        .status-badge { padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-block; text-transform: capitalize; }
        .status-paid, .status-processed { background: #ecfdf5; color: var(--green); border: 1px solid #a7f3d0; }
        .status-pending { background: #fff7ed; color: var(--orange); border: 1px solid #fed7aa; }
        .btn-download { background: var(--primary); color: white; border-radius: 8px; padding: 8px 16px; font-weight: 600; font-size: 13px; border: none; transition: 0.2s; }
        .btn-download:hover { background: #2859d9; color: white; transform: translateY(-2px); }
        
        /* Pagination */
        .pagination { margin-bottom: 0; }
        .page-link { border: none; color: var(--muted); font-weight: 600; border-radius: 8px !important; margin: 0 3px; padding: 8px 14px; transition: 0.2s; cursor: pointer; }
        .page-link:hover { background: var(--primary-soft); color: var(--primary); }
        .page-item.active .page-link { background: var(--primary); color: white; box-shadow: 0 4px 10px rgba(53,109,243,0.3); }
        .page-item.disabled .page-link { background: transparent; color: #cbd5e1; cursor: not-allowed; }

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
            <li><a href="my-payroll.php" class="side-link active"><i class="fa-solid fa-wallet"></i> My Salary & Payslip</a></li>
            <li><a href="tasks.php" class="side-link"><i class="fa-solid fa-list-check"></i> My Tasks</a></li>
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
                <h4 class="fw-bold m-0 d-flex align-items-center gap-2 me-3" style="font-family: 'Plus Jakarta Sans', sans-serif;"><i class="fa-solid fa-wallet text-primary"></i> My Salary & Payslips</h4>
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
                <div class="eyebrow">Financials</div>
                <h1>My Payslips</h1>
                <p class="text-muted mb-0">View your salary breakdown and download payslips.</p>
            </div>
        </section>

        <div class="panel">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>Pay Period</th><th>Basic Salary</th><th>Allowances</th><th>Deductions</th><th>Net Salary</th><th>Status</th><th class="text-end">Action</th></tr>
                    </thead>
                    <tbody id="payslipTableBody">
                        <tr><td colspan="7" class="text-center py-5 text-muted"><i class="fa-solid fa-spinner fa-spin fs-3 mb-3"></i><br> Loading your payslips...</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Container -->
            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top" id="paginationContainer" style="display: none;">
                <div class="text-muted fw-medium" style="font-size: 13px;" id="paginationInfo"></div>
                <nav><ul class="pagination" id="paginationControls"></ul></nav>
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
        let allPayslips = [];
        let currentPage = 1;
        const rowsPerPage = 10;

        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('mobileOverlay').classList.toggle('active');
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
        // Payslip Logic
        // ==========================================
        async function loadMyPayslips() {
            try {
                const response = await fetch('../api/payroll.php');
                const result = await response.json();
                const tbody = document.getElementById('payslipTableBody');
                
                if(result.success && result.data && result.data.length > 0) {
                    allPayslips = result.data;
                    document.getElementById('paginationContainer').style.display = 'flex';
                    
                    if (currentPage > Math.ceil(allPayslips.length / rowsPerPage)) {
                        currentPage = 1;
                    }
                    renderTable(currentPage);
                } else {
                    document.getElementById('paginationContainer').style.display = 'none';
                    tbody.innerHTML = '<tr><td colspan="7" class="text-center py-5 text-muted">No payslips generated yet.</td></tr>';
                }
            } catch(e) { 
                document.getElementById('payslipTableBody').innerHTML = '<tr><td colspan="7" class="text-center py-5 text-danger">Error loading payslips.</td></tr>'; 
            }
        }

        function renderTable(page) {
            const tbody = document.getElementById('payslipTableBody');
            const startIndex = (page - 1) * rowsPerPage;
            const endIndex = startIndex + rowsPerPage;
            const paginatedData = allPayslips.slice(startIndex, endIndex);

            tbody.innerHTML = paginatedData.map(p => {
                const statusClass = p.status === 'paid' || p.status === 'processed' ? 'status-paid' : 'status-pending';
                return `
                    <tr>
                        <td><strong>${p.month_year || 'N/A'}</strong><br><small class="text-muted">${p.period_start || ''} to ${p.period_end || ''}</small></td>
                        <td>৳ ${parseFloat(p.basic_salary || 0).toLocaleString()}</td>
                        <td class="text-success">+৳ ${parseFloat(p.total_allowance || 0).toLocaleString()}</td>
                        <td class="text-danger">-৳ ${parseFloat(p.total_deductions || 0).toLocaleString()}</td>
                        <td><strong class="text-primary">৳ ${parseFloat(p.net_salary || 0).toLocaleString()}</strong></td>
                        <td><span class="status-badge ${statusClass}">${p.status || 'pending'}</span></td>
                        <td class="text-end"><a href="../api/download-payslip.php?id=${p.id}" class="btn-download" target="_blank"><i class="fa-solid fa-download me-1"></i> Download</a></td>
                    </tr>
                `;
            }).join('');

            setupPagination(allPayslips.length, page);
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
            prevLi.innerHTML = `<a class="page-link" href="#" aria-label="Previous"><i class="fa-solid fa-chevron-left" style="font-size:12px;"></i></a>`;
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
            nextLi.innerHTML = `<a class="page-link" href="#" aria-label="Next"><i class="fa-solid fa-chevron-right" style="font-size:12px;"></i></a>`;
            nextLi.addEventListener('click', (e) => { e.preventDefault(); if (currentPage < totalPages) { renderTable(currentPage + 1); } });
            paginationControls.appendChild(nextLi);
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadMyPayslips();
            loadNotificationCount();
            setInterval(loadNotificationCount, 5000);
        });
    </script>
</body>
</html>
