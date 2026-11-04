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

$stmt2 = $db->prepare("SELECT * FROM user_settings WHERE user_id = :id");
$stmt2->execute([':id' => $_SESSION['user_id']]); 
$settings = $stmt2->fetch(PDO::FETCH_ASSOC) ?: [];

function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$firstName = $user['first_name'] ?? 'Employee';
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
    <title>Employee Settings | KormoShathi</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <style>
        :root { --navy: #14243a; --primary: #356df3; --primary-soft: #edf3ff; --ink: #17263c; --muted: #7b8798; --line: #e9edf3; --page: #f5f7fb; --sidebar-width: 270px; --shadow: 0 8px 26px rgba(26, 43, 70, .045); --danger: #ef4444; }
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
        
        .main-content { margin-left: var(--sidebar-width); min-height: 100vh; padding: 0 40px 40px; }
        .topbar { height: 78px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid var(--line); margin-bottom: 30px; }
        .avatar { width: 45px; height: 45px; border-radius: 50%; object-fit: cover; display: flex; align-items: center; justify-content: center; color: #fff; background: linear-gradient(145deg, #6594ff, #356df3); font-weight: 700; font-size: 16px; }
        
        .settings-container { display: flex; gap: 30px; max-width: 1200px; margin: 0 auto; }
        .settings-nav { width: 280px; flex-shrink: 0; background: #fff; border-radius: 16px; box-shadow: var(--shadow); padding: 20px; height: fit-content; }
        .settings-nav .nav-link { display: flex; align-items: center; gap: 12px; padding: 14px 18px; border-radius: 10px; color: var(--muted); font-weight: 600; font-size: 14.5px; margin-bottom: 6px; cursor: pointer; transition: 0.2s; border: none; background: transparent; width: 100%; text-align: left; }
        .settings-nav .nav-link:hover { background: var(--page); color: var(--ink); } .settings-nav .nav-link.active { background: var(--primary-soft); color: var(--primary); }
        .settings-nav .nav-link.danger-link { color: var(--danger); }
        .settings-nav .nav-link.danger-link:hover { background: #fef2f2; } .settings-nav .nav-link.danger-link.active { background: #fef2f2; color: var(--danger); }
        
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
        
        .danger-zone { border: 1px solid #fca5a5; border-radius: 12px; padding: 24px; background: #fef2f2; }
        .danger-zone .card-box-title { color: var(--danger); }
    </style>
</head>
<body>
    <aside class="sidebar">
        <a href="../employee-dashboard.php" class="brand"><span class="brand-mark"><i class="fa-solid fa-people-group"></i></span><span><span class="brand-name d-block">KormoShathi</span><span class="brand-sub d-block">EMPLOYEE PORTAL</span></span></a>
        <div class="nav-caption">Workspace</div>
        <ul class="side-nav">
            <li><a href="../employee-dashboard.php" class="side-link"><i class="fa-solid fa-grid-2"></i> Dashboard</a></li>
            <li><a href="chat.php" class="side-link"><i class="fa-regular fa-comments"></i> Messages</a></li>
        </ul>
        <div class="nav-caption">Account</div>
        <ul class="side-nav">
            <li><a href="settings.php" class="side-link active"><i class="fa-solid fa-gear"></i> Settings</a></li>
        </ul>
        <div class="sidebar-bottom"><ul class="side-nav"><li><a href="../logout.php" class="side-link text-danger"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li></ul></div>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <h4 class="fw-bold m-0">Employee Settings</h4>
            <div class="d-flex align-items-center gap-3">
                <div class="avatar">
                    <?php if ($profileImage): ?><img src="<?php echo e($profileImage); ?>" style="width:100%; height:100%; border-radius:50%; object-fit:cover;"><?php else: ?><?php echo $initials; ?><?php endif; ?>
                </div>
                <div><div class="fw-bold" style="font-size: 15px;"><?php echo e($fullName); ?></div><div class="text-muted" style="font-size: 12px;">Employee</div></div>
            </div>
        </header>

        <div class="settings-container">
            <div class="settings-nav">
                <button class="nav-link active" onclick="switchTab(event, 'profile')"><i class="fa-regular fa-user"></i> Profile Settings</button>
                <button class="nav-link" onclick="switchTab(event, 'documents')"><i class="fa-regular fa-folder-open"></i> My Documents</button>
                <button class="nav-link" onclick="switchTab(event, 'security')"><i class="fa-solid fa-shield-halved"></i> Account & Security</button>
                <hr class="my-3 opacity-25">
                <button class="nav-link danger-link" onclick="switchTab(event, 'danger')"><i class="fa-solid fa-triangle-exclamation"></i> Danger Zone</button>
            </div>

            <div class="settings-content">
                <!-- ১. Profile Settings (Picture removed as requested) -->
                <div id="profile" class="settings-section active">
                    <h2 class="section-title">Public Profile</h2>
                    <p class="section-desc">Update your personal information. HR-controlled fields cannot be edited.</p>
                    
                    <form id="profileForm">
                        <div class="row g-4 mb-4">
                            <div class="col-md-6"><label class="form-label">First Name</label><input type="text" class="form-control" name="first_name" value="<?php echo e($user['first_name']); ?>" required></div>
                            <div class="col-md-6"><label class="form-label">Last Name</label><input type="text" class="form-control" name="last_name" value="<?php echo e($user['last_name']); ?>" required></div>
                            <div class="col-md-6"><label class="form-label">Email Address</label><input type="email" class="form-control" name="email" value="<?php echo e($user['email']); ?>" required></div>
                            <div class="col-md-6"><label class="form-label">Phone Number</label><input type="text" class="form-control" name="phone" value="<?php echo e($user['phone'] ?? ''); ?>"></div>
                            <div class="col-md-12"><label class="form-label">Present Address</label><textarea class="form-control" name="address" rows="2"><?php echo e($user['address'] ?? ''); ?></textarea></div>
                            <div class="col-md-6"><label class="form-label">Emergency Contact</label><input type="text" class="form-control" name="emergency_contact" value="<?php echo e($user['emergency_contact'] ?? ''); ?>"></div>
                            
                            <div class="col-md-12"><hr class="my-2 opacity-25"></div>
                            
                            <div class="col-md-4"><label class="form-label">Employee ID <span class="readonly-badge">HR</span></label><input type="text" class="form-control" value="<?php echo e($user['employee_id'] ?? 'N/A'); ?>" disabled></div>
                            <div class="col-md-4"><label class="form-label">Designation <span class="readonly-badge">HR</span></label><input type="text" class="form-control" value="<?php echo e($user['position'] ?? 'N/A'); ?>" disabled></div>
                            <div class="col-md-4"><label class="form-label">Department <span class="readonly-badge">HR</span></label><input type="text" class="form-control" value="<?php echo e($user['department'] ?? 'N/A'); ?>" disabled></div>
                        </div>
                        <button type="submit" class="btn-save"><i class="fa-solid fa-floppy-disk me-2"></i>Save Profile</button>
                    </form>
                </div>

                <!-- ২. My Documents (NEW - With Upload, History, Download, Delete) -->
                <div id="documents" class="settings-section">
                    <h2 class="section-title">My Documents</h2>
                    <p class="section-desc">Upload and manage your official or personal documents safely.</p>
                    
                    <div class="card-box mb-4 bg-light border-primary" style="border-style: dashed;">
                        <h6 class="fw-bold mb-3"><i class="fa-solid fa-cloud-arrow-up text-primary me-2"></i>Upload New Document</h6>
                        <form id="uploadDocForm" class="row g-3 align-items-end">
                            <div class="col-md-5">
                                <label class="form-label">Select File (PDF, JPG, DOCX)</label>
                                <input type="file" class="form-control" name="document" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Document Type</label>
                                <select class="form-select" name="document_type">
                                    <option value="Personal">Personal</option>
                                    <option value="Official">Official</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn-save w-100"><i class="fa-solid fa-upload me-2"></i>Upload</button>
                            </div>
                        </form>
                    </div>

                    <div class="card-box">
                        <div class="card-box-title"><i class="fa-regular fa-file-lines text-primary"></i> Document History</div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="bg-light">
                                    <tr><th>File Name</th><th>Type</th><th>Uploaded Date</th><th class="text-end">Action</th></tr>
                                </thead>
                                <tbody id="documentsTableBody">
                                    <tr><td colspan="4" class="text-center py-3"><i class="fa-solid fa-spinner fa-spin"></i> Loading documents...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ৩. Security -->
                <div id="security" class="settings-section">
                    <h2 class="section-title">Account & Security</h2>
                    <p class="section-desc">Manage your password and account security.</p>
                    <div class="card-box">
                        <div class="card-box-title"><i class="fa-solid fa-key text-primary"></i> Change Password</div>
                        <form id="passwordForm">
                            <div class="row g-3 mb-3">
                                <div class="col-md-4"><label class="form-label">Current Password</label><input type="password" class="form-control" name="current_password" required></div>
                                <div class="col-md-4"><label class="form-label">New Password</label><input type="password" class="form-control" name="new_password" required></div>
                                <div class="col-md-4"><label class="form-label">Confirm Password</label><input type="password" class="form-control" name="confirm_password" required></div>
                            </div>
                            <button type="submit" class="btn-save btn-sm"><i class="fa-solid fa-shield me-2"></i>Update Password</button>
                        </form>
                    </div>
                </div>

                <!-- ৪. Danger Zone (Fixed Buttons) -->
                <div id="danger" class="settings-section">
                    <h2 class="section-title text-danger">Danger Zone</h2>
                    <p class="section-desc">Irreversible actions related to your account.</p>
                    
                    <div class="danger-zone mb-4">
                        <div class="card-box-title"><i class="fa-solid fa-power-off"></i> Deactivate Account</div>
                        <p class="text-muted small mb-3">Temporarily disable your account. You can reactivate it by logging in again.</p>
                        <button class="btn btn-outline-warning fw-bold btn-sm text-dark" onclick="confirmAction('Deactivate')">Deactivate</button>
                    </div>

                    <div class="danger-zone">
                        <div class="card-box-title"><i class="fa-solid fa-trash-can"></i> Delete Account</div>
                        <p class="text-muted small mb-3">Once you delete your account, there is no going back. All your data will be permanently removed.</p>
                        <button class="btn btn-danger fw-bold btn-sm" onclick="confirmAction('Delete')">Delete My Account</button>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <script>
        function switchTab(event, tabId) {
            document.querySelectorAll('.settings-nav .nav-link').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.settings-section').forEach(el => el.classList.remove('active'));
            event.currentTarget.classList.add('active');
            document.getElementById(tabId).classList.add('active');
            if(tabId === 'documents') loadDocuments();
        }

        // 🟢 ১. Profile Form Submit
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

        // 🟢 ২. Password Form Submit
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

        // 🟢 ৩. Document Upload Logic
        document.getElementById('uploadDocForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = this.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading...'; btn.disabled = true;

            const formData = new FormData(this);
            try {
                const res = await fetch('../api/employee-documents.php', { method: 'POST', body: formData });
                const result = await res.json();
                alert(result.message);
                if(result.success) {
                    this.reset();
                    loadDocuments();
                }
            } catch (err) { alert('Upload error.'); } finally {
                btn.innerHTML = originalText; btn.disabled = false;
            }
        });

        // 🟢 ৪. Load Documents History
        async function loadDocuments() {
            const tbody = document.getElementById('documentsTableBody');
            try {
                const res = await fetch('../api/employee-documents.php');
                const result = await res.json();
                if(result.success) {
                    if(result.data.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="4" class="text-center py-3 text-muted">No documents uploaded yet.</td></tr>`;
                        return;
                    }
                    tbody.innerHTML = result.data.map(d => `
                        <tr>
                            <td class="fw-bold text-dark"><i class="fa-regular fa-file me-2 text-primary"></i>${escapeHTML(d.document_name)}</td>
                            <td><span class="badge ${d.document_type === 'Official' ? 'bg-success' : 'bg-secondary'} bg-opacity-10 ${d.document_type === 'Official' ? 'text-success' : 'text-secondary'} border">${d.document_type}</span></td>
                            <td class="text-muted" style="font-size:13px;">${new Date(d.uploaded_at).toLocaleString()}</td>
                            <td class="text-end">
                                <a href="../${d.file_path}" target="_blank" class="btn btn-sm btn-outline-primary me-1" title="Download"><i class="fa-solid fa-download"></i></a>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteDocument(${d.id})" title="Delete"><i class="fa-solid fa-trash"></i></button>
                            </td>
                        </tr>
                    `).join('');
                }
            } catch(e) {
                tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger">Failed to load documents.</td></tr>`;
            }
        }

        // 🟢 ৫. Delete Document
        async function deleteDocument(id) {
            if(!confirm('Are you sure you want to permanently delete this document?')) return;
            try {
                const res = await fetch('../api/employee-documents.php', {
                    method: 'DELETE', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ id })
                });
                const result = await res.json();
                alert(result.message);
                if(result.success) loadDocuments();
            } catch(e) { alert('Error deleting document.'); }
        }

        // 🟢 ৬. Danger Zone Buttons
        function confirmAction(action) {
            if(action === 'Delete') {
                if(confirm("WARNING: This will permanently delete your account and all data. Are you absolutely sure?")) {
                    alert("Account deletion request sent. (Backend logic pending).");
                }
            } else if(action === 'Deactivate') {
                if(confirm("Are you sure you want to deactivate your account?")) {
                    alert("Account deactivated. (Backend logic pending).");
                }
            }
        }

        function escapeHTML(str) { return String(str || '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
        
        // Load documents on page load just in case
        document.addEventListener('DOMContentLoaded', () => {
            loadDocuments();
        });
    </script>
</body>
</html>
