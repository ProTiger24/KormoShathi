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
$profileImage = img_url($pic);
function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Documents | KormoShathi</title>
    
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

        /* SIDEBAR */
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
        .panel-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; border-bottom: 1px solid var(--line); padding-bottom: 15px;}
        .panel-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 16px; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 8px; }
        .panel-title i { color: var(--primary); }

        .file-upload-area { border: 2px dashed #cbd5e1; padding: 30px 20px; text-align: center; border-radius: 12px; transition: 0.3s; cursor: pointer; background: #f8fafc; position: relative; overflow: hidden; }
        .file-upload-area:hover { border-color: var(--primary); background: var(--primary-soft); }
        .file-upload-area i { font-size: 40px; color: var(--primary); margin-bottom: 10px; }
        .file-upload-area input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }

        .table th { background: #f8fafc; color: var(--muted); font-size: 12px; text-transform: uppercase; padding: 15px 20px; border-bottom: 1px solid var(--line); font-weight: 700; }
        .table td { vertical-align: middle; padding: 15px 20px; font-size: 14px; border-bottom: 1px solid var(--line); }
        
        .doc-icon { width: 40px; height: 40px; border-radius: 8px; display: grid; place-items: center; font-size: 18px; margin-right: 12px; }
        .ic-pdf { background: #fee2e2; color: #ef4444; }
        .ic-doc { background: #dbeafe; color: #3b82f6; }
        .ic-img { background: #f3e8ff; color: #a855f7; }

        .btn-action-sm { padding: 6px 10px; border-radius: 6px; font-weight: 600; font-size: 12px; transition: 0.2s; display: inline-flex; align-items: center; justify-content: center; border: 1px solid transparent; }
        .btn-view { background: var(--primary-soft); color: var(--primary); }
        .btn-view:hover { background: var(--primary); color: white; }
        .btn-download { background: #ecfdf5; color: var(--green); border-color: #a7f3d0; }
        .btn-download:hover { background: var(--green); color: white; }
        .btn-delete { background: #fef2f2; color: var(--red); border-color: #fecaca; }
        .btn-delete:hover { background: var(--red); color: white; }

        .form-label { font-weight: 600; font-size: 13px; color: #435168; margin-bottom: 8px; }
        .form-select { border: 1px solid var(--line); padding: 10px 15px; border-radius: 10px; font-size: 14px; background-color: #fbfcfe; }
        .form-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(53,109,243,0.1); }
        .btn-primary { background: var(--primary); border: none; padding: 12px 24px; border-radius: 10px; font-weight: 600; font-size: 14px; transition: 0.2s; color: white; }
        .btn-primary:hover { background: #2859d9; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(53,109,243,0.3); }

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
            <li><a href="tasks.php" class="side-link"><i class="fa-solid fa-list-check"></i> My Tasks</a></li>
            <li><a href="performance.php" class="side-link"><i class="fa-solid fa-chart-line"></i> Performance</a></li>
            <li><a href="employee-settings.php" class="side-link"><i class="fa-solid fa-gear"></i> Settings</a></li>
            <li><a href="../profile.php" class="side-link"><i class="fa-solid fa-user"></i> My Profile</a></li>
        </ul>

        <div class="nav-caption">Company</div>
        <ul class="side-nav">
            <li><a href="notice-board.php" class="side-link"><i class="fa-solid fa-bullhorn"></i> Notice & Events</a></li>
            <li><a href="documents.php" class="side-link active"><i class="fa-regular fa-file-pdf"></i> My Documents</a></li>
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
                <h4 class="fw-bold m-0 d-flex align-items-center gap-2 me-3" style="font-family: 'Plus Jakarta Sans', sans-serif;"><i class="fa-regular fa-file-pdf text-primary"></i> My Documents</h4>
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
                <div class="eyebrow">Document Vault</div>
                <h1>My Documents</h1>
                <p class="text-muted mb-0">Access your official company documents and upload your personal files.</p>
            </div>
        </section>

        <div class="row g-4">
            <!-- Upload Section -->
            <div class="col-lg-4">
                <div class="panel h-100">
                    <div class="panel-header"><h5 class="panel-title"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Document</h5></div>
                    
                    <div class="file-upload-area">
                        <input type="file" id="documentFile" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        <i class="fa-solid fa-file-import"></i>
                        <h6 class="fw-bold mb-1">Click to upload a file</h6>
                        <p class="text-muted small mb-0">PDF, JPG, PNG (Max 5MB)</p>
                    </div>

                    <div class="mt-3">
                        <label class="form-label">Document Type</label>
                        <select id="docType" class="form-select">
                            <option value="Personal">Personal (NID, Certificates)</option>
                            <option value="Official">Official (Contract, Letter)</option>
                        </select>
                    </div>

                    <button class="btn btn-primary w-100 mt-3 fw-bold" id="uploadBtn" onclick="uploadDocument()">
                        <i class="fa-solid fa-upload me-2"></i> Upload Now
                    </button>
                </div>
            </div>

            <!-- Document List -->
            <div class="col-lg-8">
                <div class="panel h-100">
                    <div class="panel-header"><h5 class="panel-title"><i class="fa-solid fa-folder-open"></i> My Document Vault</h5></div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr><th>Document Name</th><th>Type</th><th>Date Added</th><th class="text-end">Action</th></tr>
                            </thead>
                            <tbody id="documentList">
                                <tr><td colspan="4" class="text-center py-5 text-muted"><i class="fa-solid fa-spinner fa-spin fs-3 mb-3"></i><br> Loading documents...</td></tr>
                            </tbody>
                        </table>
                    </div>
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
                const response = await fetch('../api/notifications.php?count=1', { credentials: 'same-origin' });
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
                const response = await fetch('../api/notifications.php', { credentials: 'same-origin' });
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
                await fetch('../api/notifications.php', { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id }), credentials: 'same-origin' });
                await loadNotifications(); await loadNotificationCount();
            } catch (error) {}
        }

        async function markAllAsReadAndClose() {
            try {
                await fetch('../api/notifications.php', { method: 'PUT', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: 0 }), credentials: 'same-origin' });
                await loadNotifications(); await loadNotificationCount(); 
                if (notificationModal) notificationModal.hide();
            } catch (error) {}
        }

        function escapeHTML(str) { return String(str || '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
        function formatTime(dateString) { if(!dateString) return ''; return new Date(dateString).toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }); }


        // ==========================================
        // Document Manager Logic
        // ==========================================
        async function uploadDocument() {
            const fileInput = document.getElementById('documentFile');
            const docType = document.getElementById('docType').value;
            const btn = document.getElementById('uploadBtn');

            if (fileInput.files.length === 0) { alert('Please select a file first.'); return; }

            const formData = new FormData();
            formData.append('file', fileInput.files[0]);
            formData.append('doc_type', docType);

            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Uploading...';
            btn.disabled = true;

            try {
                // 🟢 credentials: 'same-origin' যুক্ত করা হলো
                const res = await fetch('../api/documents.php', { method: 'POST', body: formData, credentials: 'same-origin' });
                const result = await res.json();
                if(result.success) {
                    alert('File uploaded successfully!');
                    fileInput.value = '';
                    loadDocuments();
                } else { alert(result.message); }
            } catch(e) { alert('Error uploading file.'); } 
            finally { btn.innerHTML = originalText; btn.disabled = false; }
        }

        async function loadDocuments() {
            const tbody = document.getElementById('documentList');
            try {
                const res = await fetch('../api/documents.php?v=2.0', { credentials: 'same-origin' });
                const result = await res.json();

                if(result.success && result.data.length > 0) {
                    tbody.innerHTML = result.data.map(doc => {
                        const date = new Date(doc.upload_date).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                        
                        let fileUrl = doc.file_path;
                        if (!fileUrl.startsWith('http://') && !fileUrl.startsWith('https://')) {
                            fileUrl = '../' + fileUrl;
                        }

                        const docNameLower = (doc.doc_name || '').toLowerCase();
                        let iconClass = 'ic-doc'; let icon = 'fa-file-word';
                        if(docNameLower.endsWith('.pdf')) { iconClass = 'ic-pdf'; icon = 'fa-file-pdf'; }
                        else if(docNameLower.match(/\.(jpg|jpeg|png|gif)$/)) { iconClass = 'ic-img'; icon = 'fa-image'; }

                        return `
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="doc-icon ${iconClass}"><i class="fa-solid ${icon}"></i></div>
                                        <div><div class="fw-bold text-dark">${doc.doc_name}</div><div class="small text-muted">Uploaded by me</div></div>
                                    </div>
                                </td>
                                <td><span class="badge bg-light text-dark border">${doc.doc_type}</span></td>
                                <td>${date}</td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <a href="${fileUrl}" target="_blank" class="btn-action-sm btn-view" title="View"><i class="fa-solid fa-eye"></i></a>
                                        <a href="${fileUrl}" download="${doc.doc_name}" class="btn-action-sm btn-download" title="Download"><i class="fa-solid fa-download"></i></a>
                                        <button onclick="deleteDocument(${doc.id})" class="btn-action-sm btn-delete" title="Delete"><i class="fa-solid fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    }).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center py-5 text-muted">No documents uploaded yet.</td></tr>';
                }
            } catch(e) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center py-5 text-danger">Failed to load documents.</td></tr>';
            }
        }

        async function deleteDocument(id) {
            if(!confirm('Are you sure you want to delete this document? This action cannot be undone.')) return;
            
            try {
                // 🟢 credentials: 'same-origin' যুক্ত করা হলো
                const res = await fetch(`../api/documents.php?id=${id}`, { method: 'DELETE', credentials: 'same-origin' });
                const result = await res.json();
                
                if(result.success) {
                    loadDocuments();
                } else {
                    alert(result.message);
                }
            } catch(e) {
                alert('Error deleting document.');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadDocuments();
            loadNotificationCount();
            setInterval(loadNotificationCount, 5000);
        });
    </script>
</body>
</html>
