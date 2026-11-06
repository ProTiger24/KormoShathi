<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'hr') {
    header('Location: ../login.php');
    exit;
}
$config_id = $_GET['id'] ?? 0;
require_once '../config/database.php';
$db = (new Database())->getConnection();

$stmt = $db->prepare("SELECT first_name, last_name, profile_picture FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$firstName = $_SESSION['first_name'] ?? ($user['first_name'] ?? 'HR');
$lastName = $_SESSION['last_name'] ?? ($user['last_name'] ?? 'Manager');
$fullName = trim($firstName . ' ' . $lastName);
$initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
$pic = $user['profile_picture'] ?? '';

if (!$config_id) {
    $stmt = $db->prepare("SELECT id FROM ai_screening_config WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($config) { header('Location: ai-shortlist.php?id=' . $config['id']); exit; }
}

$stmt = $db->prepare("SELECT * FROM ai_screening_config WHERE id = ?");
$stmt->execute([$config_id]);
$config = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$config) {
    $has_config = false;
} else {
    $has_config = true;
    $stmt = $db->prepare("SELECT * FROM ai_screening_results WHERE config_id = ? AND recommendation = 'shortlist' ORDER BY match_score DESC");
    $stmt->execute([$config_id]);
    $shortlisted = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("SELECT * FROM ai_screening_results WHERE config_id = ? ORDER BY match_score DESC");
    $stmt->execute([$config_id]);
    $all_candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $interview_date = $config['interview_date'] ?? '';
    $interview_location = $config['interview_location'] ?? '';
    $interview_type = $config['interview_type'] ?? 'physical';
    $meeting_link = $config['meeting_link'] ?? '';
    $interviewer_name = $config['interviewer_name'] ?? 'HR Manager';
    $notes = $config['notes'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Screening Results | KormoShathi</title>
    
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

        .panel { background: var(--surface); border: 1px solid var(--line); border-radius: 13px; box-shadow: var(--shadow); padding: 24px; margin-bottom: 24px; }
        .panel-header { border-bottom: 1px solid var(--line); padding-bottom: 15px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .panel-title { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 17px; font-weight: 800; margin: 0; display: flex; align-items: center; gap: 10px; }

        .job-banner { background: linear-gradient(115deg, #356df3, #1d3d70); border-radius: 15px; padding: 24px 30px; color: white; margin-bottom: 24px; box-shadow: 0 8px 25px rgba(53,109,243,.17); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .job-banner h2 { font-family: 'Plus Jakarta Sans', sans-serif; font-size: 24px; font-weight: 800; margin: 0 0 5px; }
        .job-banner p { margin: 0; opacity: 0.9; font-size: 14px; }
        
        .details-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .detail-item { background: #f8fafc; padding: 15px; border-radius: 10px; border-left: 4px solid var(--primary); }
        .detail-label { font-size: 11px; text-transform: uppercase; font-weight: 700; color: var(--muted); letter-spacing: 0.5px; margin-bottom: 5px; }
        .detail-value { font-weight: 600; color: var(--ink); font-size: 14px; }

        .table th { background: #f8fafc; color: var(--muted); font-weight: 600; font-size: 13px; text-transform: uppercase; padding: 15px 20px; border-bottom: 1px solid var(--line); }
        .table td { vertical-align: middle; padding: 15px 20px; font-size: 14px; border-bottom: 1px solid var(--line); }
        
        .score-badge { padding: 6px 12px; border-radius: 20px; font-size: 14px; font-weight: 800; display: inline-block; }
        .score-high { background: #ecfdf5; color: #10b981; border: 1px solid #a7f3d0; }
        .score-mid { background: #fff7ed; color: #f59e0b; border: 1px solid #fed7aa; }
        .score-low { background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; }
        
        .skill-badge { background: var(--primary-soft); color: var(--primary); border: 1px solid #cbdcff; padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 600; margin-right: 4px; display: inline-block; margin-bottom: 4px; }
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-block; }
        .status-scheduled { background: #ecfdf5; color: #10b981; border: 1px solid #a7f3d0; }
        .status-pending { background: #f8fafc; color: var(--muted); border: 1px solid var(--line); }

        .form-check-input { width: 18px; height: 18px; cursor: pointer; border-color: #cbd5e1; }
        .form-check-input:checked { background-color: var(--primary); border-color: var(--primary); }
        .btn-submit { background: var(--primary); border: none; padding: 14px 24px; border-radius: 8px; font-weight: 600; font-size: 15px; transition: 0.2s; width: 100%; color: white; }
        .btn-submit:hover { background: #2859d9; transform: translateY(-2px); }
        .btn-review { background: #f8fafc; color: var(--primary); border: 1px solid var(--primary); font-weight: 600; font-size: 13px; padding: 6px 15px; border-radius: 6px; transition: 0.2s; }
        .btn-review:hover { background: var(--primary); color: white; }

        .pagination { margin-bottom: 0; } .page-link { border: none; color: var(--muted); font-weight: 600; border-radius: 8px !important; margin: 0 3px; padding: 8px 14px; transition: 0.2s; } .page-link:hover { background: var(--primary-soft); color: var(--primary); } .page-item.active .page-link { background: var(--primary); color: white; box-shadow: 0 4px 10px rgba(53,109,243,0.3); } .page-item.disabled .page-link { background: transparent; color: #cbd5e1; }

        .modal-content { border-radius: 16px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
        .modal-header { background: #f8fafc; border-radius: 16px 16px 0 0; padding: 20px 25px; border-bottom: 1px solid var(--line); }
        .modal-body { padding: 25px; }
        .modal-footer { border-top: 1px solid var(--line); padding: 15px 25px; background: #f8fafc; border-radius: 0 0 16px 16px; }
        .resume-detail-box { background: #f8fafc; border: 1px solid var(--line); border-radius: 8px; padding: 15px; margin-bottom: 15px; }

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
                <h4 class="fw-bold m-0 d-flex align-items-center gap-2 me-3" style="font-family: 'Plus Jakarta Sans', sans-serif;"><i class="fa-solid fa-robot text-primary"></i> AI Screening Results</h4>
            </div>
            <div class="topbar-right">
                <a href="ai-screening-form.php" class="btn btn-outline-primary fw-bold btn-sm px-3 rounded-pill d-none d-md-block"><i class="fa-solid fa-plus me-1"></i> New Screening</a>
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

        <?php if ($has_config && $config): ?>
            <div class="job-banner">
                <div>
                    <div class="text-uppercase fw-bold mb-1" style="font-size: 12px; letter-spacing: 1px; opacity: 0.8;">Job Role</div>
                    <h2><?php echo htmlspecialchars($config['job_title'] ?? 'Job Title'); ?></h2>
                    <p><i class="fa-regular fa-calendar me-2"></i> Screening Period: <?php echo date('d M Y', strtotime($config['start_date'])); ?> - <?php echo date('d M Y', strtotime($config['end_date'])); ?></p>
                </div>
                <div class="d-flex gap-3">
                    <div class="text-center bg-white bg-opacity-10 px-4 py-2 rounded-3 border border-white border-opacity-25">
                        <h3 class="mb-0 fw-bold"><?php echo count($shortlisted); ?></h3>
                        <div style="font-size: 12px; opacity: 0.9;">Shortlisted</div>
                    </div>
                    <div class="text-center bg-white bg-opacity-10 px-4 py-2 rounded-3 border border-white border-opacity-25">
                        <h3 class="mb-0 fw-bold"><?php echo count($all_candidates); ?></h3>
                        <div style="font-size: 12px; opacity: 0.9;">Total Applied</div>
                    </div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h5 class="panel-title"><i class="fa-solid fa-calendar-check text-primary"></i> Interview Configuration</h5>
                </div>
                <?php if (!empty($interview_date)): ?>
                <div class="details-grid">
                    <div class="detail-item"><div class="detail-label">Date & Time</div><div class="detail-value"><i class="fa-regular fa-clock text-primary me-2"></i><?php echo date('d M Y, h:i A', strtotime($interview_date)); ?></div></div>
                    <div class="detail-item"><div class="detail-label">Location / Type</div><div class="detail-value"><i class="fa-solid fa-location-dot text-primary me-2"></i><?php echo htmlspecialchars($interview_location ?: 'N/A'); ?> (<?php echo ucfirst($interview_type); ?>)</div></div>
                    <?php if ($meeting_link): ?>
                    <div class="detail-item"><div class="detail-label">Meeting Link</div><div class="detail-value"><a href="<?php echo $meeting_link; ?>" target="_blank" class="text-primary text-decoration-underline">Join Meeting</a></div></div>
                    <?php endif; ?>
                    <div class="detail-item"><div class="detail-label">Interviewer</div><div class="detail-value"><i class="fa-solid fa-user-tie text-primary me-2"></i><?php echo htmlspecialchars($interviewer_name); ?></div></div>
                </div>
                <?php if ($notes): ?>
                <div class="mt-3 p-3 bg-light rounded text-muted" style="font-size: 14px; border-left: 4px solid var(--muted);"><strong>Notes:</strong> <?php echo htmlspecialchars($notes); ?></div>
                <?php endif; ?>
                <?php else: ?>
                <div class="alert alert-warning mb-0 rounded-3 border-0"><i class="fa-solid fa-triangle-exclamation me-2"></i> Interview details are not set. Please configure them in the <a href="ai-screening-form.php" class="alert-link">AI Screening Form</a>.</div>
                <?php endif; ?>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h5 class="panel-title"><i class="fa-solid fa-star text-success"></i> Shortlisted Candidates</h5>
                    <span class="badge bg-success rounded-pill px-3 py-2">Ready for Interview</span>
                </div>
                <?php if (!empty($shortlisted)): ?>
                <form id="interviewForm">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th style="width: 40px;"><input type="checkbox" class="form-check-input" id="selectAll"></th><th>Candidate Name</th><th>Email</th><th>Match Score</th><th>Matched Skills</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($shortlisted as $c): 
                                    $isScheduled = ($c['status'] ?? '') == 'interview_scheduled';
                                    $skills_str = $c['skills_found'] ?? '';
                                    $skills = is_string($skills_str) ? array_map('trim', explode(',', $skills_str)) : (is_array($skills_str) ? $skills_str : []);
                                    $skills = array_filter($skills);
                                ?>
                                <tr class="candidate-row" style="<?php echo $isScheduled ? 'background: #f8fafc; opacity: 0.7;' : ''; ?>">
                                    <td><?php if (!$isScheduled): ?><input type="checkbox" class="form-check-input candidate-checkbox" name="result_ids[]" value="<?php echo $c['id']; ?>"><?php else: ?><i class="fa-solid fa-check-circle text-success fs-5"></i><?php endif; ?></td>
                                    <td><div class="fw-bold text-dark"><?php echo htmlspecialchars($c['candidate_name']); ?></div></td>
                                    <td><?php echo htmlspecialchars($c['candidate_email']); ?></td>
                                    <td><span class="score-badge <?php echo $c['match_score'] >= 80 ? 'score-high' : ($c['match_score'] >= 50 ? 'score-mid' : 'score-low'); ?>"><?php echo $c['match_score']; ?>%</span></td>
                                    <td><?php foreach (array_slice($skills, 0, 3) as $s): ?><span class="skill-badge"><?php echo htmlspecialchars($s); ?></span><?php endforeach; ?><?php if (count($skills) > 3): ?><span class="skill-badge" style="background:#f1f5f9; color:#64748b; border-color:#cbd5e1;">+<?php echo count($skills) - 3; ?></span><?php endif; ?></td>
                                    <td><?php if ($isScheduled): ?><span class="status-badge status-scheduled"><i class="fa-solid fa-check me-1"></i>Invited</span><?php else: ?><span class="status-badge status-pending">Pending</span><?php endif; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (!empty($interview_date)): ?>
                    <div class="mt-4 pt-3 border-top"><button type="submit" class="btn-submit" id="sendBtn"><i class="fa-solid fa-paper-plane me-2"></i> Send Interview Invitations</button></div>
                    <?php endif; ?>
                </form>
                <?php else: ?>
                <div class="text-center py-5 text-muted"><i class="fa-solid fa-users-slash fs-1 mb-3 text-secondary"></i><h6 class="fw-bold">No Shortlisted Candidates Found</h6><p class="mb-0">Candidates will appear here after AI screening is complete.</p></div>
                <?php endif; ?>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h5 class="panel-title"><i class="fa-solid fa-list-ul text-primary"></i> All Applicants (Review & Manage)</h5>
                    <span class="text-muted fw-bold" style="font-size: 13px;">Total: <?php echo count($all_candidates); ?></span>
                </div>
                <?php if (!empty($all_candidates)): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="allCandidatesTable">
                        <thead><tr><th>Candidate Name</th><th>Email</th><th>Match Score</th><th>Recommendation</th><th>Applied On</th><th class="text-end">Action</th></tr></thead>
                        <tbody id="allCandidatesBody">
                            <?php foreach ($all_candidates as $c): 
                                $rec = $c['recommendation'] ?? 'review';
                                $recClass = $rec == 'shortlist' ? 'score-high' : ($rec == 'review' ? 'score-mid' : 'score-low');
                            ?>
                            <tr class="candidate-item">
                                <td><div class="fw-bold text-dark"><?php echo htmlspecialchars($c['candidate_name']); ?></div></td>
                                <td><?php echo htmlspecialchars($c['candidate_email']); ?></td>
                                <td><span class="score-badge <?php echo $recClass; ?>"><?php echo $c['match_score'] ?? 0; ?>%</span></td>
                                <td><span class="status-badge <?php echo $recClass; ?>" style="text-transform: capitalize;"><?php echo $rec; ?></span></td>
                                <td class="text-muted"><?php echo date('d M Y', strtotime($c['created_at'] ?? 'now')); ?></td>
                                <td class="text-end">
                                    <button class="btn-review" onclick='openReviewModal(<?php echo json_encode($c, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_INVALID_UTF8_SUBSTITUTE); ?>)'><i class="fa-solid fa-eye me-1"></i> Review</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top">
                    <div class="text-muted fw-medium" style="font-size: 13px;" id="paginationInfo">Showing 1 to 5 of <?php echo count($all_candidates); ?> entries</div>
                    <nav><ul class="pagination" id="paginationControls"></ul></nav>
                </div>
                <?php else: ?>
                <div class="text-center py-5 text-muted"><i class="fa-solid fa-inbox fs-1 mb-3 text-secondary"></i><h6 class="fw-bold">No Candidates Yet</h6><p class="mb-3">Share the submission link to start receiving applications.</p><a href="../candidate/submit-resume.php" target="_blank" class="btn btn-primary rounded-pill px-4"><i class="fa-solid fa-link me-2"></i>Get Submission Link</a></div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="panel text-center py-5">
                <i class="fa-solid fa-robot text-primary mb-3" style="font-size: 60px; opacity: 0.5;"></i>
                <h4 class="fw-bold">No Active AI Screening Found</h4>
                <p class="text-muted mb-4">Start a new screening job to evaluate candidates automatically.</p>
                <a href="ai-screening-form.php" class="btn btn-primary rounded-pill px-4"><i class="fa-solid fa-plus me-2"></i> Create New Screening</a>
            </div>
        <?php endif; ?>
    </main>

    <!-- Review Modal -->
    <div class="modal fade" id="reviewModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title fw-bold"><i class="fa-solid fa-user-check text-primary me-2"></i> Candidate Review</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-8"><h4 class="fw-bold mb-1" id="rv_name">Name</h4><p class="text-muted mb-2" id="rv_email">email@example.com</p><span class="score-badge score-mid" id="rv_score">0% Match</span></div>
                        <div class="col-md-4 text-end"><div class="detail-label">Matched Skills</div><div id="rv_skills" class="mt-2 text-start"></div></div>
                    </div>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6"><div class="resume-detail-box"><div class="detail-label">Education</div><div class="detail-value" id="rv_education">N/A</div></div></div>
                        <div class="col-md-6"><div class="resume-detail-box"><div class="detail-label">Experience</div><div class="detail-value" id="rv_experience">N/A</div></div></div>
                    </div>
                    <div class="mb-3">
                        <div class="detail-label mb-2"><i class="fa-solid fa-file-lines me-1"></i> Extracted Resume Text (From PDF Parser)</div>
                        <div class="resume-detail-box" style="max-height:300px; overflow-y:auto; background:#1e293b; color:#e2e8f0; padding:15px; white-space:pre-wrap; font-family:'Courier New',Courier,monospace; font-size:13px; line-height:1.6; border-radius:8px;" id="rv_resume_text"></div>
                        <div id="pdf_download_container" class="mt-3 text-center" style="display:none;"><a href="" id="rv_pdf_link" target="_blank" class="btn btn-outline-danger fw-bold"><i class="fa-solid fa-file-pdf me-2"></i> View Original PDF Resume</a></div>
                    </div>
                    <div id="reject_section" style="display:none;" class="mt-3"><label class="form-label text-danger fw-bold">Rejection Summary (Will be emailed to candidate)</label><textarea class="form-control" id="rv_reject_reason" rows="3" placeholder="Explain why the candidate is not a good fit..." style="background:#fff; border-color:#fca5a5;"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light fw-bold" onclick="toggleRejectInput()" id="btn_toggle_reject">Reject</button>
                    <button type="button" class="btn btn-danger fw-bold px-4" id="btn_confirm_reject" style="display:none;" onclick="submitReview('reject')">Confirm Reject</button>
                    <button type="button" class="btn btn-success fw-bold px-4 ms-auto" onclick="submitReview('accept')"><i class="fa-solid fa-check me-1"></i> Accept & Schedule Interview</button>
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
                <div class="modal-body" id="notificationList" style="background:#f8fafc;"><div class="text-center py-4 text-muted">Loading...</div></div>
                <div class="modal-footer border-top-0 bg-white"><button class="btn btn-primary btn-sm rounded-pill px-3 fw-bold" onclick="markAllAsReadAndClose()"><i class="fa-solid fa-check-double me-1"></i> Mark All as Read</button></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let notificationModal = null;
        let reviewModalInstance;

        document.addEventListener('DOMContentLoaded', () => {
            reviewModalInstance = new bootstrap.Modal(document.getElementById('reviewModal'));
            loadNotificationCount();
            setInterval(loadNotificationCount, 5000);
        });

        function toggleSidebar() { document.getElementById('sidebar').classList.toggle('active'); document.getElementById('sidebarOverlay').classList.toggle('active'); }

        // Notification Logic
        async function loadNotificationCount() {
            try {
                const response = await fetch('../api/notifications.php?count=1');
                const data = await response.json();
                const badge = document.getElementById('notificationBadge');
                const dot = document.getElementById('notificationDot');
                if (data.success && Number(data.unread) > 0) { badge.style.display = 'inline-block'; badge.textContent = data.unread; dot.style.display = 'block'; } 
                else { badge.style.display = 'none'; dot.style.display = 'none'; }
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

        // AI Shortlist Logic (Preserved)
        const selectAllCheckbox = document.getElementById('selectAll');
        if(selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() { document.querySelectorAll('.candidate-checkbox').forEach(cb => { cb.checked = this.checked; cb.closest('tr').style.background = this.checked ? '#f4f8ff' : ''; }); });
        }
        document.querySelectorAll('.candidate-checkbox').forEach(cb => { cb.addEventListener('change', function() { this.closest('tr').style.background = this.checked ? '#f4f8ff' : ''; }); });

        document.getElementById('interviewForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const selected = document.querySelectorAll('.candidate-checkbox:checked');
            if (selected.length === 0) { alert('⚠️ Please select at least one candidate.'); return; }
            const interviewData = {
                result_ids: Array.from(selected).map(cb => cb.value),
                interview_date: '<?php echo $interview_date; ?>', interview_location: '<?php echo htmlspecialchars($interview_location); ?>',
                interview_type: '<?php echo $interview_type; ?>', meeting_link: '<?php echo htmlspecialchars($meeting_link); ?>',
                interviewer_name: '<?php echo htmlspecialchars($interviewer_name); ?>', notes: '<?php echo htmlspecialchars($notes); ?>'
            };
            const btn = document.getElementById('sendBtn'); const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Sending Invitations...'; btn.disabled = true;
            try {
                const response = await fetch('../api/send-interview.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(interviewData) });
                const result = await response.json(); alert(result.message); if (result.success) location.reload();
            } catch(e) { alert('❌ Error: ' + e.message); } finally { btn.innerHTML = originalText; btn.disabled = false; }
        });

        function openReviewModal(candidate) {
            currentCandidateId = candidate.id;
            document.getElementById('rv_name').textContent = candidate.candidate_name;
            document.getElementById('rv_email').textContent = candidate.candidate_email;
            document.getElementById('rv_score').textContent = candidate.match_score + '% Match';
            let skills = candidate.skills_found ? candidate.skills_found.split(',') : [];
            let skillsHtml = '';
            skills.forEach(s => { if(s.trim()) skillsHtml += `<span class="badge bg-primary me-1 mb-1">${s.trim()}</span>`; });
            document.getElementById('rv_skills').innerHTML = skillsHtml || '<span class="text-muted">None detected</span>';
            document.getElementById('rv_education').textContent = candidate.education || 'Not specified in resume';
            document.getElementById('rv_experience').textContent = candidate.experience || 'Not specified in resume';
            const resumeText = (candidate.resume_text || '').trim();
            const resumeBox = document.getElementById('rv_resume_text');
            const pdfContainer = document.getElementById('pdf_download_container');
            const pdfLink = document.getElementById('rv_pdf_link');
            
            if (resumeText) { resumeBox.textContent = resumeText; } 
            else { resumeBox.innerHTML = '<span class="text-warning"><i class="fa-solid fa-triangle-exclamation me-1"></i> No resume text saved.</span>'; }
            
            // 🟢 Cloudinary URL হ্যান্ডেল করার সঠিক লজিক
            if (candidate.resume_path) { 
                let resumeUrl = candidate.resume_path;
                if (!resumeUrl.startsWith('http://') && !resumeUrl.startsWith('https://')) {
                    resumeUrl = '../' + resumeUrl; // লোকাল ফাইল হলে
                }
                pdfLink.href = resumeUrl; 
                pdfContainer.style.display = 'block'; 
            } else { 
                pdfContainer.style.display = 'none'; 
            }

            document.getElementById('reject_section').style.display = 'none';
            document.getElementById('btn_toggle_reject').style.display = 'block';
            document.getElementById('btn_confirm_reject').style.display = 'none';
            document.getElementById('rv_reject_reason').value = '';
            reviewModalInstance.show();
        }

        function toggleRejectInput() {
            document.getElementById('reject_section').style.display = 'block';
            document.getElementById('btn_toggle_reject').style.display = 'none';
            document.getElementById('btn_confirm_reject').style.display = 'block';
        }

        async function submitReview(action) {
            let reason = '';
            if(action === 'reject') { reason = document.getElementById('rv_reject_reason').value.trim(); if(!reason) { alert('Please write a summary for rejection.'); return; } }
            const btn = action === 'accept' ? document.querySelector('.btn-success') : document.getElementById('btn_confirm_reject');
            const originalText = btn.innerHTML; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-2"></i> Processing...'; btn.disabled = true;
            try {
                const response = await fetch('../api/review-candidate.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ result_id: currentCandidateId, action: action, rejection_reason: reason, interview_date: '<?php echo $interview_date; ?>', meeting_link: '<?php echo $meeting_link; ?>' }) });
                const result = await response.json();
                if(result.success) { alert(result.message); location.reload(); } 
                else { alert('Error: ' + result.message); btn.innerHTML = originalText; btn.disabled = false; }
            } catch(e) { alert('Server error. Please try again.'); btn.innerHTML = originalText; btn.disabled = false; }
        }

        // Pagination
        const rowsPerPage = 5; let currentPage = 1; const tableBody = document.getElementById('allCandidatesBody');
        if (tableBody) {
            const rows = Array.from(tableBody.querySelectorAll('.candidate-item'));
            const totalPages = Math.ceil(rows.length / rowsPerPage);
            const paginationControls = document.getElementById('paginationControls');
            const paginationInfo = document.getElementById('paginationInfo');
            function displayTable(page) {
                rows.forEach(row => row.style.display = 'none');
                const start = (page - 1) * rowsPerPage; const end = start + rowsPerPage;
                rows.slice(start, end).forEach(row => row.style.display = 'table-row');
                paginationInfo.textContent = `Showing ${start + 1} to ${Math.min(end, rows.length)} of ${rows.length} entries`;
                renderPagination(page);
            }
            function renderPagination(page) {
                paginationControls.innerHTML = '';
                const prevLi = document.createElement('li'); prevLi.className = `page-item ${page === 1 ? 'disabled' : ''}`; prevLi.innerHTML = `<a class="page-link" href="#"><i class="fa-solid fa-chevron-left" style="font-size:12px;"></i></a>`;
                prevLi.addEventListener('click', (e) => { e.preventDefault(); if (page > 1) { currentPage = page - 1; displayTable(currentPage); } }); paginationControls.appendChild(prevLi);
                for (let i = 1; i <= totalPages; i++) { const li = document.createElement('li'); li.className = `page-item ${i === page ? 'active' : ''}`; li.innerHTML = `<a class="page-link" href="#">${i}</a>`; li.addEventListener('click', (e) => { e.preventDefault(); currentPage = i; displayTable(currentPage); }); paginationControls.appendChild(li); }
                const nextLi = document.createElement('li'); nextLi.className = `page-item ${page === totalPages ? 'disabled' : ''}`; nextLi.innerHTML = `<a class="page-link" href="#"><i class="fa-solid fa-chevron-right" style="font-size:12px;"></i></a>`;
                nextLi.addEventListener('click', (e) => { e.preventDefault(); if (page < totalPages) { currentPage = page + 1; displayTable(currentPage); } }); paginationControls.appendChild(nextLi);
            }
            displayTable(currentPage);
        }
    </script>
</body>
</html>
