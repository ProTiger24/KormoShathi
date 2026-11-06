<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'hr') { header('Location: ../login.php'); exit; }
require_once '../config/database.php';
$database = new Database(); $db = $database->getConnection();
$stmt = $db->prepare("SELECT first_name, last_name, profile_picture FROM users WHERE id = :id");
$stmt->execute([':id' => $_SESSION['user_id']]); $user = $stmt->fetch(PDO::FETCH_ASSOC);
$firstName = $_SESSION['first_name'] ?? ($user['first_name'] ?? 'HR');
$lastName = $_SESSION['last_name'] ?? ($user['last_name'] ?? 'Manager');
$initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
$pic = $user['profile_picture'] ?? '';
function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Monthly Attendance | KormoShathi</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root { --navy: #14243a; --primary: #356df3; --primary-soft: #edf3ff; --ink: #17263c; --muted: #7b8798; --line: #e9edf3; --surface: #ffffff; --page: #f5f7fb; --sidebar-width: 270px; --shadow: 0 8px 26px rgba(26, 43, 70, .045); --green: #10b981; --orange: #f59e0b; --red: #ef4444;}
        * { box-sizing: border-box; } body { margin: 0; background: var(--page); color: var(--ink); font-family: 'DM Sans', sans-serif; font-size: 15px; } a { text-decoration: none; }
        .sidebar { width: var(--sidebar-width); position: fixed; inset: 0 auto 0 0; z-index: 1040; display: flex; flex-direction: column; color: #d8e2f0; background: linear-gradient(180deg, var(--navy) 0%, #102037 100%); padding: 23px 16px 18px; overflow-y: auto; }
        .brand { display: flex; align-items: center; gap: 11px; padding: 0 9px 23px; border-bottom: 1px solid rgba(255,255,255,.09); margin-bottom: 20px; }
        .brand-mark { width: 44px; height: 44px; display: grid; place-items: center; color: #fff; background: linear-gradient(145deg, #4e8bff, #2860dc); border-radius: 13px; font-size: 20px; }
        .brand-name { color: #fff; font: 800 19px 'Plus Jakarta Sans', sans-serif; } .brand-sub { color: #91a4bd; font-size: 11px; margin-top: 4px; }
        .nav-caption { color: #71859f; font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 13px 12px 8px; }
        .side-nav { list-style: none; padding: 0; margin: 0; } .side-nav li { margin: 3px 0; }
        .side-link { display: flex; align-items: center; gap: 13px; min-height: 46px; padding: 10px 13px; border-radius: 9px; color: #aebdd0; font-size: 14px; font-weight: 500; transition: .18s ease; }
        .side-link i { width: 20px; text-align: center; font-size: 15px; } .side-link:hover { color: #fff; background: rgba(255,255,255,.075); } .side-link.active { color: #fff; background: linear-gradient(100deg, #3978f5, #2e66e6); }
        .sidebar-bottom { margin-top: auto; padding-top: 16px; border-top: 1px solid rgba(255,255,255,.08); }
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; padding: 0 30px 30px; }
        .topbar { height: 78px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--line); margin-bottom: 27px; }
        .avatar { width: 42px; height: 42px; border-radius: 50%; object-fit: cover; display: flex; align-items: center; justify-content: center; color: #fff; background: linear-gradient(145deg, #6594ff, #356df3); font-weight: 700; font-size: 16px; }
        .page-heading { margin-bottom: 24px; } .page-heading h1 { font-size: 28px; font-weight: 800; margin: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        .panel { background: var(--surface); border: 1px solid var(--line); border-radius: 16px; box-shadow: var(--shadow); padding: 24px; margin-bottom: 24px; }
        .table th { background: #f8fafc; color: var(--muted); font-size: 12px; text-transform: uppercase; padding: 15px 20px; border-bottom: 1px solid var(--line); font-weight: 700; }
        .table td { vertical-align: middle; padding: 15px 20px; font-size: 14px; border-bottom: 1px solid var(--line); }
        .badge-present { background: #ecfdf5; color: var(--green); padding: 6px 12px; border-radius: 20px; font-weight: 700; }
        .badge-late { background: #fff7ed; color: var(--orange); padding: 6px 12px; border-radius: 20px; font-weight: 700; }
        @media (max-width: 991px) { .sidebar { transform: translateX(-100%); } .sidebar.active { transform: translateX(0); } .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="../hr-dashboard.php"><span class="brand-mark"><i class="fa-solid fa-people-group"></i></span><span><span class="brand-name d-block">KormoShathi</span><span class="brand-sub d-block">HR MANAGEMENT SYSTEM</span></span></a>
        <div class="nav-caption">Workspace</div>
        <ul class="side-nav"><li><a class="side-link" href="../hr-dashboard.php"><i class="fa-solid fa-grid-2"></i> Dashboard</a></li></ul>
        <div class="nav-caption">HR Operations</div>
        <ul class="side-nav">
            <li><a class="side-link" href="add-employee.php"><i class="fa-solid fa-user-plus"></i> Add Employee</a></li>
            <li><a class="side-link" href="employees.php"><i class="fa-solid fa-users"></i> Employees</a></li>
            <li><a class="side-link" href="attendance.php"><i class="fa-solid fa-fingerprint"></i> Daily Attendance</a></li>
            <li><a class="side-link active" href="hr-attendance.php"><i class="fa-regular fa-calendar-days"></i> Monthly Attendance</a></li>
            <li><a class="side-link" href="leave.php"><i class="fa-regular fa-calendar-check"></i> Leave Management</a></li>
            <li><a class="side-link" href="payroll.php"><i class="fa-solid fa-wallet"></i> Payroll</a></li>
            <li><a class="side-link" href="hr-holidays.php"><i class="fa-solid fa-umbrella-beach"></i> Manage Holidays</a></li>
        </ul>
        <div class="sidebar-bottom"><ul class="side-nav"><li><a class="side-link text-danger" href="../logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li></ul></div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div class="d-flex align-items-center"><h4 class="fw-bold m-0" style="font-family: 'Plus Jakarta Sans', sans-serif;">Monthly Attendance Overview</h4></div>
            <div class="d-flex align-items-center gap-2">
                <?php if (!empty($pic)): ?><img src="<?php echo htmlspecialchars(img_url($pic), ENT_QUOTES, 'UTF-8'); ?>" class="avatar" alt="Profile"><?php else: ?><div class="avatar"><?php echo $initials; ?></div><?php endif; ?>
                <span class="fw-bold ms-2" style="font-size: 14px;"><?php echo htmlspecialchars($firstName . ' ' . $lastName); ?></span>
            </div>
        </header>

        <div class="page-heading d-flex justify-content-between align-items-end">
            <div>
                <div class="text-primary fw-bold" style="font-size: 12px; letter-spacing: 1px; text-transform: uppercase;">HR Insights</div>
                <h1>Employee Attendance Summary</h1>
                <p class="text-muted mb-0">View total present and late days for all employees.</p>
            </div>
            <div>
                <input type="month" id="monthPicker" class="form-control fw-bold" style="padding: 10px 15px; border-radius: 10px;" onchange="loadHRData()">
            </div>
        </div>

        <div class="panel">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr><th>Employee Name</th><th>Employee ID</th><th>Total Present Days</th><th>Total Late Days</th></tr>
                    </thead>
                    <tbody id="hrTableBody">
                        <tr><td colspan="4" class="text-center py-5 text-muted"><i class="fa-solid fa-spinner fa-spin fs-3 mb-3"></i><br> Loading summary...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        function toggleSidebar() { document.getElementById('sidebar').classList.toggle('active'); }

        async function loadHRData() {
            const month = document.getElementById('monthPicker').value;
            const tbody = document.getElementById('hrTableBody');
            
            try {
                const res = await fetch(`../api/attendance-history.php?month=${month}`);
                const result = await res.json();

                if(result.success && result.data.length > 0) {
                    tbody.innerHTML = result.data.map(emp => `
                        <tr>
                            <td><div class="fw-bold">${emp.first_name} ${emp.last_name}</div></td>
                            <td class="text-muted">${emp.employee_id}</td>
                            <td><span class="badge-present">${emp.total_present} Days</span></td>
                            <td><span class="badge-late">${emp.total_late} Days</span></td>
                        </tr>
                    `).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="4" class="text-center py-5 text-muted">No records found for this month.</td></tr>';
                }
            } catch(e) {
                console.error(e);
                tbody.innerHTML = '<tr><td colspan="4" class="text-center py-5 text-danger">Failed to load data.</td></tr>';
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            const now = new Date();
            const currentMonth = now.toISOString().slice(0, 7);
            document.getElementById('monthPicker').value = currentMonth;
            loadHRData();
        });
    </script>
</body>
</html>
