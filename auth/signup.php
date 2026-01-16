<?php
require_once '../config/database.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   
    $first_name = sanitize_input($_POST['first_name'] ?? '');
    $last_name = sanitize_input($_POST['last_name'] ?? '');
    $email = sanitize_input($_POST['email'] ?? '');
    $employee_id = sanitize_input($_POST['employee_id'] ?? '');
    $department = sanitize_input($_POST['department'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
   
    $errors = [];
    
    
    if (empty($first_name)) {
        $errors[] = "First Name is required.";
    } elseif (strlen($first_name) < 2 || strlen($first_name) > 30) {
        $errors[] = "First Name must be between 2 and 30 characters.";
    } elseif (!preg_match('/^[A-Z]/', $first_name)) {
        $errors[] = "First Name must start with a capital letter.";
    } elseif (preg_match('/[^a-zA-Z\s\'-]/', $first_name)) {
        $errors[] = "First Name can only contain letters, spaces, apostrophes, and hyphens.";
    } elseif (preg_match('/  /', $first_name) || preg_match('/\'\'/', $first_name) || preg_match('/--/', $first_name)) {
        $errors[] = "First Name cannot have consecutive spaces, apostrophes, or hyphens.";
    }
    
    if (empty($last_name)) {
        $errors[] = "Last Name is required.";
    } elseif (strlen($last_name) < 2 || strlen($last_name) > 30) {
        $errors[] = "Last Name must be between 2 and 30 characters.";
    } elseif (!preg_match('/^[A-Z]/', $last_name)) {
        $errors[] = "Last Name must start with a capital letter.";
    } elseif (preg_match('/[^a-zA-Z\'-]/', $last_name)) {
        $errors[] = "Last Name can only contain letters, apostrophes, and hyphens.";
    } elseif (preg_match('/\'\'/', $last_name) || preg_match('/--/', $last_name)) {
        $errors[] = "Last Name cannot have consecutive apostrophes or hyphens.";
    }
    
   
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } elseif (!preg_match('/^[a-zA-Z0-9._%+-]+@(kicd\.ac\.ke|gmail\.com)$/', $email)) {
        $errors[] = "Email must be from @kicd.ac.ke or @gmail.com domain";
    }
    
    
    if (empty($employee_id)) {
        $errors[] = "KICD ID is required.";
    } elseif (!preg_match('/^KICD\/\d{4}\/\d{3}$/', $employee_id)) {
        $errors[] = "KICD ID must be in the format KICD/2024/001";
    }
    
    
    if (empty($password)) {
        $errors[] = "Password is required.";
    } else {
        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters long.";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter.";
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter.";
        }
        if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $errors[] = "Password must contain at least one special character.";
        }
        if (strcasecmp($password, $first_name) === 0 || strcasecmp($password, $last_name) === 0 || strcasecmp($password, $email) === 0) {
            $errors[] = "Password cannot be the same as your first name, last name, or email.";
        }
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    
    if (empty($errors)) {
        
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR employee_id = ?");
        $stmt->execute([$email, $employee_id]);
        
        if ($stmt->fetch()) {
            $errors[] = 'Email or KICD ID already exists.';
        } else {
            
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("
                INSERT INTO users (employee_id, first_name, last_name, email, password_hash, role, department)
                VALUES (?, ?, ?, ?, ?, 'user', ?)
            ");

            if ($stmt->execute([$employee_id, $first_name, $last_name, $email, $password_hash, $department])) {
                $user_id = $pdo->lastInsertId();
                
                
                send_notification($user_id, 'welcome', 'Welcome to KICD Requisition System', 
                    'Your account has been created successfully. You can now create requisitions.');
                
                
                $_SESSION['user_id'] = $user_id;
                $_SESSION['employee_id'] = $employee_id;
                $_SESSION['first_name'] = $first_name;
                $_SESSION['last_name'] = $last_name;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = 'user';
                
                
                header('Location: ../user/dashboard.php');
                exit();
            } else {
                $errors[] = 'Registration failed. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KICD Requisition Registration</title>
    <link rel="stylesheet" href="../assets/css/auth.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .form-group {
            margin-bottom: 10px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 3px;
            font-weight: 600;
            color: var(--text-color);
            font-size: 12px;
        }
        
        .form-group small {
            display: block;
            margin-top: 2px;
            color: var(--text-light);
            font-size: 10px;
        }
        
        .form-row {
            display: flex;
            gap: 10px;
        }
        
        .form-row .form-group {
            flex: 1;
            margin-bottom: 10px;
        }
        
        .alert ul {
            margin: 0;
            padding-left: 10px;
        }
        
        .alert li {
            margin-bottom: 2px;
            font-size: 12px;
        }
        
        .auth-left {
            padding: 30px 20px;
        }
        
        .auth-right {
            padding: 30px 20px;
        }
        
        .auth-left h1 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        
        .auth-left h3 {
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .auth-left h2 {
            font-size: 16px;
            margin-bottom: 15px;
        }
        
        .auth-left li {
            margin-bottom: 5px;
            font-size: 12px;
        }
        
        .auth-right h2 {
            font-size: 22px;
            margin-bottom: 5px;
        }
        
        .auth-right p {
            margin-bottom: 15px;
            font-size: 12px;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="password"] {
            padding: 10px;
            font-size: 12px;
        }
        
        button {
            padding: 10px;
            font-size: 13px;
            margin-top: 5px;
        }
        
        .signin-link {
            margin-top: 10px;
            font-size: 12px;
        }
        
        .alert {
            padding: 8px;
            margin-bottom: 10px;
        }
    </style>
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
            <h2>Create Account</h2>
            <p>Register for KICD Requisition Management System</p>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <p><a href="login.php">Click here to login</a></p>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" required 
                               value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>"
                               placeholder="First Name">
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" required
                               value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>"
                               placeholder="Last Name">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                           placeholder="yourname@kicd.ac.ke or yourname@gmail.com">
                    <small>Only @kicd.ac.ke and @gmail.com domains allowed</small>
                </div>
                
                <div class="form-group">
                    <label for="employee_id">KICD ID</label>
                    <input type="text" id="employee_id" name="employee_id" required
                           value="<?php echo isset($_POST['employee_id']) ? htmlspecialchars($_POST['employee_id']) : ''; ?>"
                           placeholder="KICD/2024/001">
                    <small>Format: KICD/YYYY/XXX</small>
                </div>

                <div class="form-group">
                    <label for="department">Department</label>
                    <input type="text" id="department" name="department"
                           value="<?php echo isset($_POST['department']) ? htmlspecialchars($_POST['department']) : ''; ?>"
                           placeholder="e.g., Curriculum Development">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
<div class="password-wrapper">
    <input type="password" id="password" name="password" required
           placeholder="At least 8 characters">
    <img src="../assets/visible.png" alt="Show password" class="toggle-password" onclick="togglePassword(this, 'password')" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%);">
</div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="confirm_password" name="confirm_password" required
                               placeholder="Confirm your password">
                        <img src="../assets/visible.png" alt="Show password" class="toggle-password" onclick="togglePassword(this, 'confirm_password')">
                    </div>
                </div>
                
                <div class="form-group">
                </div>
                
                <button type="submit">Create Account</button>
                
                <div class="signin-link">
                    Already have an account? <a href="login.php">Sign In</a>
                </div>
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
