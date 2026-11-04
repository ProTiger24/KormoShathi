<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$force_change = isset($_SESSION['force_password_change']) && $_SESSION['force_password_change'] === true;
$user_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$user_type = $_SESSION['user_type'] ?? 'employee';
$dashboard_link = ($user_type === 'hr') ? 'hr-dashboard.php' : 'employee-dashboard.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password | KormoShathi</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        :root {
            --navy: #14243a;
            --primary: #356df3;
            --primary-dark: #1e3d72;
            --primary-soft: #edf3ff;
            --ink: #17263c;
            --muted: #64748b;
            --line: #cbd5e1;
            --surface: #ffffff;
            --shadow: 0 30px 60px -10px rgba(0,0,0,0.6);
            --green: #10b981;
            --red: #ef4444;
            --orange: #f59e0b;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0; padding: 0;
            font-family: 'DM Sans', sans-serif;
            background: radial-gradient(circle at center, #1c3452 0%, #0b1525 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            padding: 15px; /* Added padding for smaller screens */
        }

        /* --- 3D Floating Background Shapes --- */
        .bg-shapes { position: fixed; inset: 0; overflow: hidden; z-index: 1; }
        .shape {
            position: absolute; border-radius: 50%;
            background: linear-gradient(135deg, rgba(83,139,255,0.2), rgba(53,109,243,0.05));
            box-shadow: inset 0 0 20px rgba(255,255,255,0.1), 0 20px 40px rgba(0,0,0,0.3);
            backdrop-filter: blur(5px); border: 1px solid rgba(255,255,255,0.05);
        }
        .shape:nth-child(1) { width: 300px; height: 300px; top: -10%; left: 10%; animation: float 8s infinite ease-in-out; }
        .shape:nth-child(2) { width: 200px; height: 200px; bottom: -5%; right: 15%; animation: float 10s infinite ease-in-out reverse; }
        .shape:nth-child(3) { width: 150px; height: 150px; top: 40%; right: 5%; animation: float 7s infinite ease-in-out alternate; }
        .shape:nth-child(4) { width: 100px; height: 100px; bottom: 20%; left: 5%; animation: float 9s infinite ease-in-out reverse; }

        @keyframes float {
            0% { transform: translateY(0) rotate(0deg) scale(1); }
            50% { transform: translateY(-30px) rotate(15deg) scale(1.05); }
            100% { transform: translateY(0) rotate(0deg) scale(1); }
        }

        /* --- Glass Card Wrapper --- */
        .card-3d-container {
            position: relative; z-index: 10; width: 100%; max-width: 500px; /* Reduced width slightly */
        }

        .password-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
            border-radius: 20px;
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .card-header-custom {
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            padding: 25px 25px 20px; /* Reduced padding */
            text-align: center;
            border-bottom: 1.5px solid var(--line);
        }

        .icon-circle {
            width: 60px; height: 60px; /* Reduced size */
            background: var(--primary-soft); color: var(--primary);
            border-radius: 50%; display: grid; place-items: center;
            font-size: 24px; margin: 0 auto 12px;
            box-shadow: 0 8px 20px rgba(53,109,243,0.15);
        }

        .card-header-custom h4 {
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-weight: 800; font-size: 22px; color: var(--navy); margin: 0 0 5px;
        }

        .card-header-custom p {
            color: var(--muted); font-size: 14px; font-weight: 500; margin: 0;
        }

        .card-body-custom { padding: 25px; } /* Reduced padding */

        /* Alerts */
        .alert-warning-custom {
            background: #fffbeb; border-left: 4px solid var(--orange);
            padding: 12px 15px; border-radius: 8px; font-size: 14px; color: #92400e;
            margin-bottom: 20px; display: flex; align-items: flex-start; gap: 10px;
            font-weight: 500; line-height: 1.4;
        }

        /* Inputs */
        .form-label { font-weight: 700; font-size: 14px; color: #1e293b; margin-bottom: 6px; }
        
        .input-wrap { position: relative; width: 100%; }
        .input-wrap > i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 15px; z-index: 4; }
        
        .form-control {
            border: 1.5px solid var(--line); 
            padding: 12px 45px 12px 45px; /* Reduced padding */
            border-radius: 10px;
            font-size: 15px; color: #1e293b; font-weight: 500;
            background: #f8fafc; transition: 0.2s;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
        }
        .form-control::placeholder { color: #94a3b8; font-size: 14px; font-weight: 400; }
        .form-control:focus { 
            border-color: var(--primary); 
            background: #fff;
            box-shadow: 0 0 0 3px rgba(53,109,243,0.15); 
        }
        
        .password-toggle { 
            position: absolute; right: 15px; top: 50%; transform: translateY(-50%); 
            background: none; border: none; color: #94a3b8; cursor: pointer; z-index: 5; padding: 0; font-size: 15px;
        }
        .password-toggle:hover { color: var(--primary); }

        /* Password Requirements */
        .password-requirements {
            background: #f8fafc; border: 1px solid var(--line);
            border-radius: 10px; padding: 12px 15px; margin-top: 12px;
        }
        .requirement-item {
            display: flex; align-items: center; gap: 10px;
            font-size: 13px; font-weight: 600; color: var(--muted);
            padding: 3px 0; transition: 0.2s;
        }
        .requirement-item i { font-size: 13px; }
        .req-valid { color: var(--green); }
        .req-invalid { color: var(--red); }

        /* 3D Button */
        .btn-3d {
            position: relative; width: 100%; padding: 14px; border: none; border-radius: 12px; /* Reduced padding */
            background: linear-gradient(180deg, #4a7fff 0%, #356df3 100%); color: white;
            font-weight: 700; font-size: 16px; letter-spacing: 0.5px;
            box-shadow: 0 5px 0 #1e3d72, 0 8px 15px rgba(53,109,243,0.3);
            transition: all 0.1s ease;
            margin-top: 20px;
        }
        .btn-3d:active {
            box-shadow: 0 2px 0 #1e3d72, 0 4px 8px rgba(53,109,243,0.3);
            transform: translateY(3px);
        }

        .back-link { 
            color: var(--primary); text-decoration: none; font-weight: 700; font-size: 14px; 
            display: inline-flex; align-items: center; gap: 6px; transition: 0.2s; 
        }
        .back-link:hover { color: var(--primary-dark); text-decoration: underline; }

        .match-text { font-size: 13px; font-weight: 700; margin-top: 4px; display: block; }
    </style>
</head>
<body>

    <div class="bg-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="card-3d-container">
        <div class="password-card">
            
            <div class="card-header-custom">
                <div class="icon-circle">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h4><?php echo $force_change ? 'Change Default Password' : 'Account Security'; ?></h4>
                <p><?php echo $force_change ? 'For security, please update your default password.' : 'Keep your account secure by updating your password.'; ?></p>
            </div>
            
            <div class="card-body-custom">
                <?php if ($force_change): ?>
                <div class="alert-warning-custom">
                    <i class="fas fa-exclamation-triangle mt-1"></i>
                    <div>
                        <strong>Security Alert:</strong> You are using the default password. Please change it.
                    </div>
                </div>
                <?php endif; ?>

                <form id="changePasswordForm">
                    <div class="mb-3">
                        <label class="form-label">Current Password</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-lock"></i>
                            <input type="password" class="form-control" name="current_password" id="currentPassword" placeholder="Enter current password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('currentPassword', 'toggleCurrent')">
                                <i class="fas fa-eye" id="toggleCurrent"></i>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-key"></i>
                            <input type="password" class="form-control" name="new_password" id="newPassword" placeholder="Enter new strong password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('newPassword', 'toggleNew')">
                                <i class="fas fa-eye" id="toggleNew"></i>
                            </button>
                        </div>
                        
                        <div class="password-requirements" id="passwordRequirements">
                            <div class="requirement-item" id="reqLength">
                                <i class="fas fa-times-circle req-invalid"></i>
                                <span>At least 8 characters long</span>
                            </div>
                            <div class="requirement-item" id="reqUpper">
                                <i class="fas fa-times-circle req-invalid"></i>
                                <span>At least 1 uppercase letter (A-Z)</span>
                            </div>
                            <div class="requirement-item" id="reqLower">
                                <i class="fas fa-times-circle req-invalid"></i>
                                <span>At least 1 lowercase letter (a-z)</span>
                            </div>
                            <div class="requirement-item" id="reqNumber">
                                <i class="fas fa-times-circle req-invalid"></i>
                                <span>At least 1 number (0-9)</span>
                            </div>
                            <div class="requirement-item" id="reqSpecial">
                                <i class="fas fa-times-circle req-invalid"></i>
                                <span>At least 1 special character (!@#$%^&*)</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label">Confirm New Password</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-check-double"></i>
                            <input type="password" class="form-control" name="confirm_password" id="confirmPassword" placeholder="Confirm new password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword', 'toggleConfirm')">
                                <i class="fas fa-eye" id="toggleConfirm"></i>
                            </button>
                        </div>
                        <div id="passwordMatch" class="mt-1"></div>
                    </div>

                    <button type="submit" class="btn-3d">
                        <i class="fas fa-save me-2"></i>Update Password
                    </button>
                </form>

                <div class="text-center mt-3">
                    <a href="<?php echo $dashboard_link; ?>" class="back-link">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(inputId, toggleId) {
            const input = document.getElementById(inputId);
            const toggle = document.getElementById(toggleId);
            if (input.type === 'password') {
                input.type = 'text';
                toggle.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                toggle.className = 'fas fa-eye';
            }
        }

        document.getElementById('newPassword').addEventListener('input', function() {
            const password = this.value;
            
            const requirements = [
                { id: 'reqLength', test: password.length >= 8 },
                { id: 'reqUpper', test: /[A-Z]/.test(password) },
                { id: 'reqLower', test: /[a-z]/.test(password) },
                { id: 'reqNumber', test: /[0-9]/.test(password) },
                { id: 'reqSpecial', test: /[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/.test(password) }
            ];

            requirements.forEach(req => {
                const el = document.getElementById(req.id);
                const icon = el.querySelector('i');
                if (req.test) {
                    icon.className = 'fas fa-check-circle req-valid';
                    el.style.color = 'var(--green)';
                } else {
                    icon.className = 'fas fa-times-circle req-invalid';
                    el.style.color = 'var(--muted)';
                }
            });
        });

        document.getElementById('confirmPassword').addEventListener('input', function() {
            const password = document.getElementById('newPassword').value;
            const confirm = this.value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (confirm.length === 0) {
                matchDiv.innerHTML = '';
                return;
            }
            
            if (password === confirm) {
                matchDiv.innerHTML = '<i class="fas fa-check-circle text-success me-1"></i><span class="match-text" style="color:var(--green);">Passwords match</span>';
            } else {
                matchDiv.innerHTML = '<i class="fas fa-times-circle text-danger me-1"></i><span class="match-text" style="color:var(--red);">Passwords do not match</span>';
            }
        });

        document.getElementById('changePasswordForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const data = {
                current_password: formData.get('current_password'),
                new_password: formData.get('new_password'),
                confirm_password: formData.get('confirm_password')
            };

            if (data.new_password !== data.confirm_password) {
                alert('✗ Passwords do not match!');
                return;
            }

            const password = data.new_password;
            if (password.length < 8 || !/[A-Z]/.test(password) || !/[a-z]/.test(password) || 
                !/[0-9]/.test(password) || !/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/.test(password)) {
                alert('✗ Password does not meet security requirements!');
                return;
            }

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';
            submitBtn.disabled = true;

            try {
                const response = await fetch('api/auth.php?action=change_password', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    alert('✅ Password changed successfully!');
                    window.location.href = '<?php echo $dashboard_link; ?>';
                } else {
                    alert('✗ ' + result.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Connection error. Please try again.');
            } finally {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });
    </script>
</body>
</html>
