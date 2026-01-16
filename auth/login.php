<?php
require_once '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
          
            $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
           
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['employee_id'] = $user['employee_id'];
            
            
            switch ($user['role']) {
                case 'admin':
                    header('Location: ../admin/dashboard.php');
                    break;
                case 'procurement':
                    header('Location: ../procurement/dashboard.php');
                    break;
                case 'supervisor':
                    header('Location: ../supervisor/dashboard.php');
                    break;
                default:
                    header('Location: ../user/dashboard.php');
            }
            exit();
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KICD Login</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body>
    <div class="auth-container">
        <div class="auth-left">
            <img src="../assets/images.jpeg" alt="KICD Logo" class="logo">
            <h1>Kenya Institute of Curriculum Development</h1>
            <h3>Requisition Management System</h3>
            <h2>Streamlined Procurement for Educational Excellence</h2>
            <ul>
                <li> Secure multi-level approval workflows ensuring compliance</li>
                <li> Comprehensive item catalog with detailed specifications</li>
                <li> Real-time tracking and reporting for efficient resource management</li>
            </ul>
        </div>
        
        <div class="auth-right">
            <h2>Welcome Back</h2>
            <p>Sign in to your KICD Requisition account</p>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <label>Official Email Address</label>
                <input type="email" name="email" placeholder="yourname@kicd.ac.ke" required>
                
                <label>Password</label>
<div class="password-wrapper">
    <input type="password" name="password" placeholder="Password" required>
    <img src="../assets/visible.png" alt="Show password" class="toggle-password" onclick="togglePassword(this, 'password')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%);">
</div>
                
               
                <button type="submit">Sign In</button>

                <p class="links">
                    Don't have an account? <a href="signup.php">Sign up here</a>
                </p>
            </form>
        </div>
    </div>

    <script>
        function togglePassword(toggleImg, inputName) {
            const passwordInput = document.querySelector(`input[name="${inputName}"]`);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleImg.src = '../assets/hide.png';
                toggleImg.alt = 'Hide password';
            } else {
                passwordInput.type = 'password';
                toggleImg.src = '../assets/visible.png';
                toggleImg.alt = 'Show password';
            }
        }
        
        function toggleAllPasswords(button) {
            const passwordInputs = document.querySelectorAll('input[type="password"]');
            const isShowing = button.textContent === 'Hide All Passwords';
            button.textContent = isShowing ? 'Show All Passwords' : 'Hide All Passwords';
            
            passwordInputs.forEach(input => {
                const toggleImg = input.nextElementSibling;
                input.type = isShowing ? 'password' : 'text';
                toggleImg.src = isShowing ? '../assets/visible.png' : '../assets/hide.png';
                toggleImg.alt = isShowing ? 'Show password' : 'Hide password';
            });
        }
    </script>
</body>
</html>
