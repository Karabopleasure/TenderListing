<?php
require_once 'db_connect.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['first_name'];
        header("Location: index.php");
        exit();
    } else {
        header("Location: index.php?login_error=1");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Login & Registration</title>
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
    flex: 1;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    align-items: center;
    justify-content: center;
    padding-top: 80px; /* Leaves space for fixed top-nav */
}

        .top-nav {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    background-color: #25559D;
    padding: 12px 0;
    z-index: 1000;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.centered-nav {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: center;
    gap: 30px;
    align-items: center;
}

.centered-nav a {
    color: #fff;
    text-decoration: none;
    font-size: 15px;
    font-weight: 500;
    transition: color 0.3s ease;
}

.centered-nav a i {
    margin-right: 5px;
}

.centered-nav a:hover {
    color: #FE8900;
}

.login-container {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(37, 85, 157, 0.1);
    overflow: hidden;
    width: 100%;
    max-width: 900px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    min-height: 600px;
    
    position: relative;
    /* push down below fixed top-nav */
    margin: 0 auto; /* center horizontally */
}

        .left-panel {
            background: linear-gradient(135deg, #25559D 0%, #1c4279 100%);
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: white;
            position: relative;
        }

        .left-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .logo {
            position: relative;
            z-index: 1;
            margin-bottom: 30px;
        }

        .logo img {
            max-width: 200px;
            height: auto;
        }

        .welcome-text {
            position: relative;
            z-index: 1;
        }

        .welcome-text h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 15px;
            opacity: 0.95;
        }

        .welcome-text p {
            font-size: 16px;
            opacity: 0.8;
            line-height: 1.6;
        }

        .right-panel {
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-container {
            max-width: 400px;
            width: 100%;
        }

        .form-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .form-header h2 {
            color: #25559D;
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .form-header p {
            color: #666;
            font-size: 14px;
        }

        .form-toggle {
            display: flex;
            background: #f8f9fa;
            border-radius: 12px;
            padding: 4px;
            margin-bottom: 30px;
        }

        .toggle-btn {
            flex: 1;
            padding: 12px;
            background: none;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #666;
        }

        .toggle-btn.active {
            background: #25559D;
            color: white;
            box-shadow: 0 2px 8px rgba(37, 85, 157, 0.2);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .input-group {
            position: relative;
        }

        .input-field {
            width: 100%;
            padding: 16px 20px 16px 50px;
            border: 2px solid #e5e5e5;
            border-radius: 12px;
            font-size: 15px;
            background: #fafafa;
            transition: all 0.3s ease;
            color: #333;
        }

        .input-field:focus {
            outline: none;
            border-color: #25559D;
            background: white;
            box-shadow: 0 0 0 3px rgba(37, 85, 157, 0.1);
        }

        .input-field::placeholder {
            color: #999;
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 18px;
        }

        .input-field:focus + .input-icon {
            color: #25559D;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            font-size: 14px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #25559D;
        }

        .checkbox-group label {
            color: #666;
            font-size: 14px;
        }

        .forgot-password {
            color: #25559D;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .forgot-password:hover {
            color: #FE8900;
        }

        .submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #25559D 0%, #1c4279 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(37, 85, 157, 0.3);
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(37, 85, 157, 0.4);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .form-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }

        .form-footer a {
            color: #25559D;
            text-decoration: none;
            font-weight: 500;
        }

        .form-footer a:hover {
            color: #FE8900;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
        }

        .alert.error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }

        .alert.success {
            background: #efe;
            color: #363;
            border: 1px solid #cfc;
        }

        .login-form, .register-form {
            display: none;
        }

        .login-form.active, .register-form.active {
            display: block;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .login-container {
                grid-template-columns: 1fr;
                max-width: 450px;
            }

            .left-panel {
                padding: 40px 30px;
                min-height: 200px;
            }

            .right-panel {
                padding: 40px 30px;
            }

            .welcome-text h1 {
                font-size: 24px;
            }

            .form-header h2 {
                font-size: 28px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .right-panel {
                padding: 30px 20px;
            }

            .left-panel {
                padding: 30px 20px;
            }
        }

        /* Subtle animations */
        .form-container {
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .input-field {
            animation: fadeIn 0.4s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body>
        <div class="top-nav">
            <nav class="centered-nav">
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                <a href="saved.php"><i class="fas fa-bookmark"></i> Saved Tenders</a>
                <?php endif;?>
                <a href="subscriptions.php"><i class="fas fa-credit-card"></i> Subscriptions</a>
                
            </nav>
        </div>
        
    <div class="login-container">
        <div class="left-panel">
            <div class="logo">
                <img src="Kesho_Chartered_Accountants_Logo.png" alt="Kesho Chartered Accountants" />
            </div>
            <div class="welcome-text">
                <h1>Welcome Back</h1>
                <p>Access your tender management dashboard and stay updated with the latest opportunities in your industry.</p>
            </div>
        </div>

        <div class="right-panel">
            <div class="form-container">
                <div class="form-header">
                    <h2>Account Access</h2>
                    <p>Please sign in to your account or create a new one</p>
                </div>

                <div class="form-toggle">
                    <button class="toggle-btn active" onclick="showLogin()">Sign In</button>
                    <button class="toggle-btn" onclick="showRegister()">Sign Up</button>
                </div>

                <!-- Login Form -->
                <div class="login-form active" id="loginForm">
                    <form action="login.php" method="post">
                        <div class="form-group">
                            <div class="input-group">
                                <input type="email" name="email" class="input-field" placeholder="Email address" required>
                                <i class="bx bx-envelope input-icon"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="input-group">
                                <input type="password" name="password" class="input-field" placeholder="Password" required>
                                <i class="bx bx-lock-alt input-icon"></i>
                            </div>
                        </div>

                        <div class="form-options">
                            <div class="checkbox-group">
                                <input type="checkbox" id="remember" name="remember">
                                <label for="remember">Remember me</label>
                            </div>
                            <a href="#" class="forgot-password">Forgot password?</a>
                        </div>

                        <button type="submit" class="submit-btn">Sign In</button>
                    </form>
                </div>

                <!-- Register Form -->
                <div class="register-form" id="registerForm">
                    <form action="register.php" method="post">
                        <div class="form-row">
                            <div class="form-group">
                                <div class="input-group">
                                    <input type="text" name="first_name" class="input-field" placeholder="First Name" required>
                                    <i class="bx bx-user input-icon"></i>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="input-group">
                                    <input type="text" name="last_name" class="input-field" placeholder="Last Name" required>
                                    <i class="bx bx-user input-icon"></i>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="input-group">
                                <input type="email" name="email" class="input-field" placeholder="Email address" required>
                                <i class="bx bx-envelope input-icon"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="input-group">
                                <input type="password" name="password" class="input-field" placeholder="Password" required>
                                <i class="bx bx-lock-alt input-icon"></i>
                            </div>
                        </div>

                        <div class="form-options">
                            <div class="checkbox-group">
                                <input type="checkbox" id="terms" name="terms" required>
                                <label for="terms">I agree to the <a href="#" class="forgot-password">Terms & Conditions</a></label>
                            </div>
                        </div>

                        <button type="submit" class="submit-btn">Create Account</button>
                    </form>
                </div>

                <!-- Error/Success Messages -->
                <?php if (isset($_GET['login_error'])): ?>
                    <div class="alert error">
                        Invalid login credentials. Please check your email and password.
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['registered'])): ?>
                    <div class="alert success">
                        Registration successful! You can now sign in to your account.
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert error">
                        An error occurred during registration. Please try again.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function showLogin() {
            document.getElementById('loginForm').classList.add('active');
            document.getElementById('registerForm').classList.remove('active');
            
            // Update toggle buttons
            document.querySelectorAll('.toggle-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
        }

        function showRegister() {
            document.getElementById('registerForm').classList.add('active');
            document.getElementById('loginForm').classList.remove('active');
            
            // Update toggle buttons
            document.querySelectorAll('.toggle-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
        }

        // Add subtle form validation feedback
        document.querySelectorAll('.input-field').forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim() === '') {
                    this.style.borderColor = '#fcc';
                } else {
                    this.style.borderColor = '#cfc';
                }
            });

            input.addEventListener('focus', function() {
                this.style.borderColor = '#25559D';
            });
        });
    </script>
     <?php include 'footer.php'; ?>
</body>
</html>