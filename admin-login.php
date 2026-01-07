<?php
session_start();
require_once 'config.php';

// Check if already logged in
if (isset($_SESSION['admin_logged_in'])) {
    header('Location: admin-panel.php');
    exit();
}

$error = '';

// Hardcoded admin credentials (change these!)
$admin_credentials = [
    'admin' => [
        'password' => 'admin123', // Change this password!
        'name' => 'Administrator',
        'role' => 'super_admin'
    ],
    'editor' => [
        'password' => 'editor123', // Change this password!
        'name' => 'Content Editor',
        'role' => 'editor'
    ],
    'author' => [
        'password' => 'author123', // Change this password!
        'name' => 'Blog Author',
        'role' => 'author'
    ]
];

// Handle login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Validate credentials
    if (isset($admin_credentials[$username]) && $admin_credentials[$username]['password'] === $password) {
        // Login successful
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_name'] = $admin_credentials[$username]['name'];
        $_SESSION['admin_role'] = $admin_credentials[$username]['role'];
        $_SESSION['login_time'] = time();
        
        // Log the login attempt (optional)
        error_log("Admin login successful: $username - IP: " . $_SERVER['REMOTE_ADDR'] . " - Time: " . date('Y-m-d H:i:s'));
        
        header('Location: admin-panel.php');
        exit();
    } else {
        $error = "Invalid username or password!";
        
        // Log failed attempt (optional)
        error_log("Failed admin login attempt: $username - IP: " . $_SERVER['REMOTE_ADDR'] . " - Time: " . date('Y-m-d H:i:s'));
    }
}

// Check for brute force protection (optional)
$max_attempts = 5;
$lockout_time = 15 * 60; // 15 minutes in seconds

if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= $max_attempts) {
    if (isset($_SESSION['last_attempt_time']) && (time() - $_SESSION['last_attempt_time']) < $lockout_time) {
        $remaining_time = $lockout_time - (time() - $_SESSION['last_attempt_time']);
        $error = "Too many failed attempts. Please try again in " . ceil($remaining_time / 60) . " minutes.";
    } else {
        // Reset attempts after lockout period
        unset($_SESSION['login_attempts']);
        unset($_SESSION['last_attempt_time']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: var(--gradient-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .login-container {
            width: 100%;
            max-width: 450px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .login-header {
            background: var(--gradient-primary);
            padding: 3rem 2rem;
            text-align: center;
            color: white;
        }
        
        .login-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .login-header p {
            opacity: 0.9;
            font-size: 0.95rem;
        }
        
        .login-body {
            padding: 3rem 2rem;
        }
        
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        label {
            font-weight: 500;
            color: #374151;
            font-size: 0.9rem;
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-with-icon i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }
        
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .btn-login {
            background: var(--gradient-primary);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 1rem;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
        }
        
        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 1rem;
            border-radius: 10px;
            border-left: 4px solid #dc2626;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1rem;
            animation: slideIn 0.3s ease;
        }
        
        .warning-message {
            background: #fef3c7;
            color: #d97706;
            padding: 1rem;
            border-radius: 10px;
            border-left: 4px solid #f59e0b;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 1rem;
            animation: slideIn 0.3s ease;
        }
        
        .demo-credentials {
            background: #dbeafe;
            color: #1e40af;
            padding: 1rem;
            border-radius: 10px;
            border-left: 4px solid #3b82f6;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }
        
        .demo-credentials h4 {
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .demo-credentials ul {
            list-style: none;
            padding-left: 0;
        }
        
        .demo-credentials li {
            margin-bottom: 0.3rem;
            padding: 0.3rem 0;
            border-bottom: 1px dashed rgba(30, 64, 175, 0.2);
        }
        
        .demo-credentials li:last-child {
            border-bottom: none;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-footer {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        @media (max-width: 480px) {
            .login-container {
                max-width: 100%;
            }
            
            .login-header,
            .login-body {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1><i class="fas fa-lock"></i> Admin Login</h1>
            <p><?php echo SITE_NAME; ?> - Content Management System</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= 3): ?>
                <div class="warning-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Warning:</strong> <?php echo $_SESSION['login_attempts']; ?> failed login attempts.
                </div>
            <?php endif; ?>
            
            <div class="demo-credentials">
                <h4><i class="fas fa-key"></i> Demo Credentials</h4>
                <ul>
                    <li><strong>Username:</strong> admin &nbsp;|&nbsp; <strong>Password:</strong> admin123</li>
                    <li><strong>Username:</strong> editor &nbsp;|&nbsp; <strong>Password:</strong> editor123</li>
                    <li><strong>Username:</strong> author &nbsp;|&nbsp; <strong>Password:</strong> author123</li>
                </ul>
            </div>
            
            <form method="POST" action="" class="login-form" id="loginForm">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="username" name="username" required 
                               placeholder="Enter admin username">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" required 
                               placeholder="Enter admin password">
                    </div>
                </div>
                
                <button type="submit" class="btn-login" id="loginBtn">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>
            
            <div class="login-footer">
                <p><i class="fas fa-shield-alt"></i> Secure Admin Access</p>
                <p style="font-size: 0.8rem; margin-top: 0.5rem; color: #9ca3af;">
                    Session will expire after 30 minutes of inactivity
                </p>
            </div>
        </div>
    </div>
    
    <script>
        // Add focus effect to inputs
        const inputs = document.querySelectorAll('input[type="text"], input[type="password"]');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.querySelector('i').style.color = '#6366f1';
            });
            
            input.addEventListener('blur', function() {
                if (!this.value) {
                    this.parentElement.querySelector('i').style.color = '#9ca3af';
                }
            });
        });
        
        // Add loading state to login button
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            const originalText = btn.innerHTML;
            
            // Simple client-side validation
            const username = document.getElementById('username').value;
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                e.preventDefault();
                alert('Please enter both username and password');
                return;
            }
            
            // Show loading state
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Authenticating...';
            btn.disabled = true;
            
            // If form takes too long, reset button
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }, 5000);
        });
        
        // Auto-focus on username field
        document.getElementById('username').focus();
        
        // Password visibility toggle (optional)
        const passwordInput = document.getElementById('password');
        const passwordIcon = passwordInput.parentElement.querySelector('i');
        
        // Change icon on focus
        passwordInput.addEventListener('focus', function() {
            passwordIcon.className = 'fas fa-lock';
        });
        
        // Add show/hide password toggle
        const toggleBtn = document.createElement('span');
        toggleBtn.innerHTML = '<i class="fas fa-eye"></i>';
        toggleBtn.style.position = 'absolute';
        toggleBtn.style.right = '1rem';
        toggleBtn.style.top = '50%';
        toggleBtn.style.transform = 'translateY(-50%)';
        toggleBtn.style.cursor = 'pointer';
        toggleBtn.style.color = '#9ca3af';
        toggleBtn.style.zIndex = '2';
        
        toggleBtn.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.querySelector('i').className = type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
        });
        
        passwordInput.parentElement.appendChild(toggleBtn);
    </script>
</body>
</html>
<?php 
// Track login attempts
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $error) {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 1;
    } else {
        $_SESSION['login_attempts']++;
    }
    $_SESSION['last_attempt_time'] = time();
}
?>