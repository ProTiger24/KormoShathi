<?php
session_start();
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] === 'hr') {
        header('Location: hr-dashboard.php');
    } else {
        header('Location: employee-dashboard.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | KormoShathi</title>
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
            overflow: hidden;
        }

        /* --- 3D Floating Background Shapes --- */
        .bg-shapes { position: absolute; inset: 0; overflow: hidden; z-index: 1; }
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

        /* --- Glass Login Card --- */
        .login-wrapper {
            position: relative; z-index: 10; width: 100%; max-width: 460px; padding: 20px;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 30px 60px -10px rgba(0,0,0,0.6), inset 0 2px 5px rgba(255,255,255,1);
            border: 1px solid rgba(255, 255, 255, 0.5);
            padding: 45px 35px;
        }

        .brand-logo {
            width: 75px; height: 75px; border-radius: 20px;
            background: linear-gradient(135deg, #4e8bff, #2860dc);
            color: white; display: grid; place-items: center;
            font-size: 32px; margin: 0 auto 20px;
            box-shadow: 0 10px 20px rgba(53,109,243,0.3), inset 0 -3px 0 rgba(0,0,0,0.2);
        }

        .login-title { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; font-size: 28px; color: var(--navy); text-align: center; margin-bottom: 6px; }
        .login-subtitle { color: #475569; font-size: 16px; font-weight: 500; text-align: center; margin-bottom: 35px; }

        /* --- Inputs --- */
        .form-label { font-weight: 700; font-size: 15px; color: #1e293b; margin-bottom: 8px; }
        .input-wrap { position: relative; width: 100%; }
        .input-wrap > i { position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 16px; z-index: 4; }
        
        .form-control {
            border: 1.5px solid #cbd5e1; 
            padding: 14px 48px 14px 48px; /* Bigger padding for bigger text */
            border-radius: 12px;
            font-size: 16px; /* Increased font size */
            color: #1e293b;
            font-weight: 500;
            background: #f8fafc; transition: 0.2s;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
        }
        .form-control::placeholder { color: #94a3b8; font-size: 15px; font-weight: 400; }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(53,109,243,0.15), inset 0 2px 4px rgba(0,0,0,0.02); background: #fff; }
        
        .password-toggle { 
            position: absolute; right: 16px; top: 50%; transform: translateY(-50%); 
            background: none; border: none; color: #94a3b8; cursor: pointer; z-index: 5; padding: 0; font-size: 16px;
        }
        .password-toggle:hover { color: var(--primary); }

        /* --- 3D Button --- */
        .btn-3d {
            position: relative; width: 100%; padding: 16px; border: none; border-radius: 12px;
            background: linear-gradient(180deg, #4a7fff 0%, #356df3 100%); color: white;
            font-weight: 700; font-size: 17px; letter-spacing: 0.5px;
            box-shadow: 0 6px 0 #1e3d72, 0 10px 20px rgba(53,109,243,0.3);
            transition: all 0.1s ease;
            margin-top: 25px;
        }
        .btn-3d:active {
            box-shadow: 0 2px 0 #1e3d72, 0 5px 10px rgba(53,109,243,0.3);
            transform: translateY(4px);
        }

        /* --- Info Notice --- */
        .security-notice {
            background: #fffbeb; border-left: 4px solid #f59e0b;
            padding: 15px 18px; border-radius: 8px; font-size: 14px; color: #92400e;
            margin-top: 25px; font-weight: 500; line-height: 1.5;
            box-shadow: 0 4px 6px rgba(0,0,0,0.02);
        }

        /* --- Footer --- */
        .footer-links { display: flex; justify-content: center; gap: 15px; margin-top: 30px; font-size: 15px; font-weight: 600; }
        .footer-links a { color: var(--primary); text-decoration: none; transition: 0.2s; }
        .footer-links a:hover { color: var(--primary-dark); text-decoration: underline; }
    </style>
</head>
<body>

    <!-- 3D Floating Background Shapes -->
    <div class="bg-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
    </div>

    <div class="login-wrapper">
        <div class="login-card">
            
            <div class="brand-logo">
                <i class="fa-solid fa-people-group"></i>
            </div>
            
            <h1 class="login-title">KormoShathi</h1>
            <p class="login-subtitle">HR Management System</p>

            <form id="loginForm">
                <div class="mb-4">
                    <label class="form-label">Email Address</label>
                    <div class="input-wrap">
                        <i class="fa-regular fa-envelope"></i>
                        <input type="email" class="form-control" name="email" placeholder="name@company.com" required autofocus>
                    </div>
                </div>
                
                <div class="mb-2">
                    <label class="form-label">Password</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" class="form-control" name="password" id="passwordInput" placeholder="Enter password" required>
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="security-notice">
                    <i class="fas fa-shield-alt me-1"></i>
                    <strong>Default Password:</strong> 123456 <br>
                    <span style="opacity:0.8;">You will be asked to change it on first login.</span>
                </div>

                <button type="submit" class="btn-3d" id="submitBtn">
                    Log In <i class="fas fa-arrow-right ms-2"></i>
                </button>
            </form>

            <div class="footer-links">
                <a href="index.php"><i class="fas fa-arrow-left me-1"></i> Back to Home</a>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const pwd = document.getElementById('passwordInput');
            const icon = document.getElementById('toggleIcon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                pwd.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }

        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            const originalText = btn.innerHTML;
            
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Authenticating...';
            btn.disabled = true;

            const formData = new FormData(this);
            const data = { email: formData.get('email'), password: formData.get('password') };

            try {
                const response = await fetch('api/auth.php?action=login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    if (result.force_password_change) {
                        alert('⚠️ Please change your default password for security.');
                        window.location.href = 'change-password.php';
                    } else {
                        window.location.href = result.user_type === 'hr' ? 'hr-dashboard.php' : 'employee-dashboard.php';
                    }
                } else {
                    alert('✗ ' + result.message);
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            } catch (error) {
                alert('Connection error! Please try again.');
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });
    </script>
</body>
</html>
