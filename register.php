<?php
session_start();
require "includes/db.php";

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = "";
$success = "";
$formData = [];

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = trim($_POST['phone']);
    
    // Store form data for re-filling
    $formData = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone
    ];
    
    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all required fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Email already registered. Please login or use a different email.";
        } else {
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $hashedPassword, $phone);
            
            if ($stmt->execute()) {
                $success = "Registration successful! You can now login.";
                $formData = []; // Clear form data on success
                
                // Auto login after registration (optional)
                // $user_id = $conn->insert_id;
                // $_SESSION['user_id'] = $user_id;
                // $_SESSION['user_name'] = $name;
                // header("Location: index.php");
                // exit();
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | PROGLIDE</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <style>
        :root {
            --primary: #000000;
            --secondary: #ffcc00;
            --accent: #009933;
            --light: #f8f9fa;
            --dark: #212529;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
        }
        
        .register-container {
            width: 100%;
            max-width: 1200px;
            padding: 20px;
        }
        
        .register-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideInUp 0.8s ease;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .register-left {
            background: linear-gradient(135deg, var(--primary), #333);
            padding: 60px 40px;
            color: white;
            position: relative;
            overflow: hidden;
            min-height: 600px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .register-left::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(0,153,51,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }
        
        .register-right {
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
            z-index: 1;
        }
        
        .logo h1 {
            font-family: 'Montserrat', sans-serif;
            font-size: 2.5rem;
            font-weight: 800;
            background: linear-gradient(45deg, var(--accent), #00cc66);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }
        
        .logo p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9rem;
        }
        
        .benefits {
            margin-top: 40px;
            position: relative;
            z-index: 1;
        }
        
        .benefit-item {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            opacity: 0;
            animation: fadeInRight 0.8s ease forwards;
        }
        
        .benefit-item:nth-child(1) { animation-delay: 0.2s; }
        .benefit-item:nth-child(2) { animation-delay: 0.4s; }
        .benefit-item:nth-child(3) { animation-delay: 0.6s; }
        .benefit-item:nth-child(4) { animation-delay: 0.8s; }
        
        .benefit-icon {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--accent), #00cc66);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
        }
        
        .benefit-text h4 {
            font-size: 1.1rem;
            margin-bottom: 5px;
            color: white;
        }
        
        .benefit-text p {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
            margin: 0;
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .register-header h2 {
            font-family: 'Montserrat', sans-serif;
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .register-header p {
            color: #666;
            font-size: 1rem;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
            opacity: 0;
            animation: fadeInUp 0.8s ease forwards;
        }
        
        .form-group:nth-child(1) { animation-delay: 0.3s; }
        .form-group:nth-child(2) { animation-delay: 0.5s; }
        .form-group:nth-child(3) { animation-delay: 0.7s; }
        .form-group:nth-child(4) { animation-delay: 0.9s; }
        .form-group:nth-child(5) { animation-delay: 1.1s; }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark);
            font-size: 0.95rem;
        }
        
        .form-label .required {
            color: #ff6b6b;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            z-index: 2;
        }
        
        .form-control {
            width: 100%;
            padding: 15px 15px 15px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(0, 153, 51, 0.1);
        }
        
        .form-control:focus + .input-icon {
            color: var(--accent);
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            z-index: 2;
        }
        
        .password-toggle:hover {
            color: var(--accent);
        }
        
        .password-strength {
            margin-top: 8px;
        }
        
        .strength-bar {
            height: 5px;
            background: #e0e0e0;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 5px;
        }
        
        .strength-fill {
            height: 100%;
            width: 0%;
            background: #ff6b6b;
            border-radius: 3px;
            transition: all 0.3s ease;
        }
        
        .strength-text {
            font-size: 0.8rem;
            color: #666;
        }
        
        .terms {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 25px;
            opacity: 0;
            animation: fadeInUp 0.8s ease forwards;
            animation-delay: 1.3s;
        }
        
        .terms-checkbox {
            margin-top: 5px;
        }
        
        .terms-label {
            font-size: 0.9rem;
            color: #666;
            line-height: 1.5;
        }
        
        .terms-label a {
            color: var(--accent);
            text-decoration: none;
        }
        
        .terms-label a:hover {
            text-decoration: underline;
        }
        
        .register-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--accent), #00cc66);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 25px;
            opacity: 0;
            animation: fadeInUp 0.8s ease forwards;
            animation-delay: 1.4s;
        }
        
        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 153, 51, 0.3);
        }
        
        .register-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }
        
        .register-btn:active {
            transform: translateY(0);
        }
        
        .divider {
            text-align: center;
            margin: 25px 0;
            position: relative;
            opacity: 0;
            animation: fadeInUp 0.8s ease forwards;
            animation-delay: 1.5s;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 45%;
            height: 1px;
            background: #e0e0e0;
        }
        
        .divider::after {
            content: '';
            position: absolute;
            right: 0;
            top: 50%;
            width: 45%;
            height: 1px;
            background: #e0e0e0;
        }
        
        .divider span {
            background: white;
            padding: 0 15px;
            color: #666;
            font-size: 0.9rem;
        }
        
        .social-register {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            opacity: 0;
            animation: fadeInUp 0.8s ease forwards;
            animation-delay: 1.6s;
        }
        
        .social-btn {
            flex: 1;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-weight: 500;
            color: #333;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .social-btn:hover {
            border-color: var(--accent);
            transform: translateY(-2px);
        }
        
        .social-btn.google:hover {
            border-color: #DB4437;
            color: #DB4437;
        }
        
        .social-btn.facebook:hover {
            border-color: #4267B2;
            color: #4267B2;
        }
        
        .login-link {
            text-align: center;
            margin-top: 20px;
            opacity: 0;
            animation: fadeInUp 0.8s ease forwards;
            animation-delay: 1.7s;
        }
        
        .login-link p {
            color: #666;
            margin-bottom: 10px;
        }
        
        .login-link a {
            display: inline-block;
            padding: 12px 30px;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .login-link a:hover {
            background: #333;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        /* Alert Styles */
        .alert {
            border-radius: 12px;
            border: none;
            padding: 15px;
            margin-bottom: 25px;
            animation: fadeInDown 0.5s ease;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
        }
        
        .alert-success {
            background: linear-gradient(135deg, var(--accent), #007a29);
            color: white;
        }
        
        /* Progress Steps */
        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            position: relative;
            opacity: 0;
            animation: fadeInUp 0.8s ease forwards;
            animation-delay: 0.2s;
        }
        
        .progress-steps::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 10%;
            right: 10%;
            height: 3px;
            background: #e0e0e0;
            z-index: 1;
        }
        
        .step {
            position: relative;
            z-index: 2;
            text-align: center;
            flex: 1;
        }
        
        .step-circle {
            width: 30px;
            height: 30px;
            background: white;
            border: 3px solid #e0e0e0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-weight: 600;
            color: #666;
            transition: all 0.3s ease;
        }
        
        .step.active .step-circle {
            background: var(--accent);
            border-color: var(--accent);
            color: white;
        }
        
        .step.completed .step-circle {
            background: var(--accent);
            border-color: var(--accent);
            color: white;
        }
        
        .step-label {
            font-size: 0.8rem;
            color: #666;
        }
        
        .step.active .step-label {
            color: var(--accent);
            font-weight: 600;
        }
        
        /* Animations */
        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes rotate {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }
        
        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }
        
        /* Floating Animation */
        @keyframes float {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }
        
        .floating {
            animation: float 3s ease-in-out infinite;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .register-left {
                min-height: 400px;
                padding: 40px 30px;
            }
            
            .register-right {
                padding: 40px 30px;
            }
            
            .logo h1 {
                font-size: 2rem;
            }
            
            .register-header h2 {
                font-size: 1.8rem;
            }
        }
        
        @media (max-width: 768px) {
            .register-card {
                flex-direction: column;
            }
            
            .register-left {
                order: 2;
                min-height: auto;
                padding: 40px 20px;
            }
            
            .register-right {
                order: 1;
                padding: 40px 20px;
            }
            
            .social-register {
                flex-direction: column;
            }
            
            .benefit-item {
                margin-bottom: 15px;
            }
            
            .progress-steps {
                margin-bottom: 20px;
            }
        }
        
        /* Particle Animation */
        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-card row g-0">
            <!-- Left Side - Brand & Benefits -->
            <div class="col-lg-6 register-left">
                <div class="logo">
                    <h1 class="floating">PROGLIDE</h1>
                    <p>Join our premium community</p>
                </div>
                
                <div class="benefits">
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-gift"></i>
                        </div>
                        <div class="benefit-text">
                            <h4>Welcome Bonus</h4>
                            <p>Get 10% off on your first order</p>
                        </div>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="benefit-text">
                            <h4>Quick Checkout</h4>
                            <p>Save your details for faster purchases</p>
                        </div>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="benefit-text">
                            <h4>Exclusive Deals</h4>
                            <p>Access members-only offers & discounts</p>
                        </div>
                    </div>
                    
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="fas fa-history"></i>
                        </div>
                        <div class="benefit-text">
                            <h4>Order Tracking</h4>
                            <p>Track all your orders in one place</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Side - Registration Form -->
            <div class="col-lg-6 register-right">
                <div class="register-header">
                    <h2>Create Account</h2>
                    <p>Join PROGLIDE today for exclusive benefits</p>
                </div>
                
                <!-- Progress Steps -->
                <div class="progress-steps">
                    <div class="step active">
                        <div class="step-circle">1</div>
                        <div class="step-label">Details</div>
                    </div>
                    <div class="step">
                        <div class="step-circle">2</div>
                        <div class="step-label">Account</div>
                    </div>
                    <div class="step">
                        <div class="step-circle">3</div>
                        <div class="step-label">Complete</div>
                    </div>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?= htmlspecialchars($success) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="registerForm">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label class="form-label">Full Name <span class="required">*</span></label>
                                <div class="input-group">
                                    <i class="fas fa-user input-icon"></i>
                                    <input type="text" 
                                           name="name" 
                                           class="form-control" 
                                           placeholder="Enter your full name"
                                           required
                                           value="<?= htmlspecialchars($formData['name'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Email Address <span class="required">*</span></label>
                                <div class="input-group">
                                    <i class="fas fa-envelope input-icon"></i>
                                    <input type="email" 
                                           name="email" 
                                           class="form-control" 
                                           placeholder="Enter your email"
                                           required
                                           value="<?= htmlspecialchars($formData['email'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Phone Number</label>
                                <div class="input-group">
                                    <i class="fas fa-phone input-icon"></i>
                                    <input type="tel" 
                                           name="phone" 
                                           class="form-control" 
                                           placeholder="Enter your phone"
                                           value="<?= htmlspecialchars($formData['phone'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Password <span class="required">*</span></label>
                                <div class="input-group">
                                    <i class="fas fa-lock input-icon"></i>
                                    <input type="password" 
                                           name="password" 
                                           class="form-control" 
                                           placeholder="Create password"
                                           required
                                           id="password"
                                           minlength="6">
                                    <button type="button" class="password-toggle" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="password-strength">
                                    <div class="strength-bar">
                                        <div class="strength-fill" id="strengthFill"></div>
                                    </div>
                                    <div class="strength-text" id="strengthText">Password strength: Very weak</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Confirm Password <span class="required">*</span></label>
                                <div class="input-group">
                                    <i class="fas fa-lock input-icon"></i>
                                    <input type="password" 
                                           name="confirm_password" 
                                           class="form-control" 
                                           placeholder="Confirm password"
                                           required
                                           id="confirmPassword">
                                    <button type="button" class="password-toggle" id="toggleConfirmPassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div id="passwordMatch" class="strength-text"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="terms">
                        <div class="terms-checkbox">
                            <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                        </div>
                        <label class="terms-label" for="terms">
                            I agree to the <a href="terms.php">Terms of Service</a> and <a href="privacy.php">Privacy Policy</a>. 
                            I understand that my data will be used in accordance with PROGLIDE's policies.
                        </label>
                    </div>
                    
                    <button type="submit" class="register-btn" id="submitBtn">
                        <i class="fas fa-user-plus"></i> Create Account
                    </button>
                </form>
                
                <div class="divider">
                    <span>Or sign up with</span>
                </div>
                
                <div class="social-register">
                    <a href="#" class="social-btn google">
                        <i class="fab fa-google"></i> Google
                    </a>
                    <a href="#" class="social-btn facebook">
                        <i class="fab fa-facebook-f"></i> Facebook
                    </a>
                </div>
                
                <div class="login-link">
                    <p>Already have an account?</p>
                    <a href="login.php" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i> Login to Account
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Back to Home Button -->
    <a href="index.php" class="position-fixed bottom-0 end-0 m-4 btn btn-light btn-lg rounded-circle shadow-lg" 
       style="z-index: 1000; width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;"
       data-bs-toggle="tooltip" data-bs-placement="left" title="Back to Home">
        <i class="fas fa-home"></i>
    </a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password toggle
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('confirmPassword');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Password strength checker
        const passwordInput = document.getElementById('password');
        const strengthFill = document.getElementById('strengthFill');
        const strengthText = document.getElementById('strengthText');
        const confirmPasswordInput = document.getElementById('confirmPassword');
        const passwordMatch = document.getElementById('passwordMatch');
        const submitBtn = document.getElementById('submitBtn');

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            
            // Length check
            if (password.length >= 6) strength++;
            if (password.length >= 8) strength++;
            
            // Complexity checks
            if (/[A-Z]/.test(password)) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            // Update strength bar
            let width = 0;
            let text = '';
            let color = '';
            
            switch(strength) {
                case 0:
                case 1:
                    width = 20;
                    text = 'Very weak';
                    color = '#ff6b6b';
                    break;
                case 2:
                    width = 40;
                    text = 'Weak';
                    color = '#ff9e2c';
                    break;
                case 3:
                    width = 60;
                    text = 'Fair';
                    color = '#ffd93c';
                    break;
                case 4:
                    width = 80;
                    text = 'Good';
                    color = '#6bcf7f';
                    break;
                case 5:
                case 6:
                    width = 100;
                    text = 'Strong';
                    color = '#2ecc71';
                    break;
            }
            
            strengthFill.style.width = `${width}%`;
            strengthFill.style.background = color;
            strengthText.textContent = `Password strength: ${text}`;
            strengthText.style.color = color;
            
            // Check password match
            checkPasswordMatch();
        });

        confirmPasswordInput.addEventListener('input', checkPasswordMatch);

        function checkPasswordMatch() {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            
            if (!confirmPassword) {
                passwordMatch.textContent = '';
                passwordMatch.style.color = '';
                submitBtn.disabled = false;
                return;
            }
            
            if (password === confirmPassword) {
                passwordMatch.textContent = '✓ Passwords match';
                passwordMatch.style.color = '#2ecc71';
                submitBtn.disabled = false;
            } else {
                passwordMatch.textContent = '✗ Passwords do not match';
                passwordMatch.style.color = '#ff6b6b';
                submitBtn.disabled = true;
            }
        }

        // Form validation with real-time feedback
        const form = document.getElementById('registerForm');
        const inputs = form.querySelectorAll('input[required]');
        
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            input.addEventListener('input', function() {
                if (this.classList.contains('is-invalid')) {
                    validateField(this);
                }
            });
        });

        function validateField(field) {
            const value = field.value.trim();
            let isValid = true;
            
            if (!value) {
                isValid = false;
            } else if (field.type === 'email') {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                isValid = emailRegex.test(value);
            } else if (field.name === 'password') {
                isValid = value.length >= 6;
            }
            
            if (isValid) {
                field.classList.remove('is-invalid');
                field.classList.add('is-valid');
            } else {
                field.classList.remove('is-valid');
                field.classList.add('is-invalid');
            }
            
            // Update progress steps
            updateProgressSteps();
        }

        function updateProgressSteps() {
            const steps = document.querySelectorAll('.step');
            let completed = 0;
            
            inputs.forEach(input => {
                if (input.value.trim() && !input.classList.contains('is-invalid')) {
                    completed++;
                }
            });
            
            // Check terms
            const terms = document.getElementById('terms');
            if (terms.checked) completed++;
            
            steps.forEach((step, index) => {
                step.classList.remove('active', 'completed');
                if (index < completed) {
                    step.classList.add('completed');
                }
                if (index === completed) {
                    step.classList.add('active');
                }
            });
        }

        // Terms checkbox effect
        const termsCheckbox = document.getElementById('terms');
        termsCheckbox.addEventListener('change', function() {
            if (this.checked) {
                this.parentElement.classList.add('pulse');
                setTimeout(() => {
                    this.parentElement.classList.remove('pulse');
                }, 300);
            }
            updateProgressSteps();
        });

        // Form submission
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            inputs.forEach(input => {
                validateField(input);
                if (input.classList.contains('is-invalid')) {
                    isValid = false;
                    shakeElement(input);
                }
            });
            
            if (!termsCheckbox.checked) {
                isValid = false;
                shakeElement(termsCheckbox.parentElement);
                alert('Please agree to the terms and conditions');
            }
            
            if (!isValid) {
                e.preventDefault();
            } else {
                // Show loading animation
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
                submitBtn.disabled = true;
            }
        });

        // Shake animation
        function shakeElement(element) {
            element.classList.remove('shake');
            void element.offsetWidth;
            element.classList.add('shake');
            
            setTimeout(() => {
                element.classList.remove('shake');
            }, 500);
        }

        // Add shake animation style
        const style = document.createElement('style');
        style.textContent = `
            .shake {
                animation: shake 0.5s ease;
            }
            
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
                20%, 40%, 60%, 80% { transform: translateX(5px); }
            }
            
            .pulse {
                animation: pulse 0.3s ease;
            }
            
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.1); }
            }
            
            .is-valid {
                border-color: #2ecc71 !important;
            }
            
            .is-invalid {
                border-color: #ff6b6b !important;
            }
        `;
        document.head.appendChild(style);

        // Floating particles
        function createParticles() {
            const particlesContainer = document.querySelector('.register-left');
            if (!particlesContainer) return;
            
            for (let i = 0; i < 15; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                const size = Math.random() * 20 + 5;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                particle.style.left = `${Math.random() * 100}%`;
                particle.style.top = `${Math.random() * 100}%`;
                
                const duration = Math.random() * 20 + 10;
                const delay = Math.random() * 5;
                particle.style.animation = `float ${duration}s ease-in-out ${delay}s infinite`;
                
                particlesContainer.appendChild(particle);
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            
            // Tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Auto focus on name field
            const nameInput = document.querySelector('input[name="name"]');
            if (nameInput && !nameInput.value) {
                setTimeout(() => {
                    nameInput.focus();
                }, 500);
            }
            
            // Add hover effects
            document.querySelectorAll('.form-control').forEach(input => {
                input.addEventListener('mouseenter', function() {
                    if (!this.classList.contains('is-invalid')) {
                        this.style.transform = 'translateY(-2px)';
                        this.style.boxShadow = '0 5px 15px rgba(0,0,0,0.1)';
                    }
                });
                
                input.addEventListener('mouseleave', function() {
                    this.style.transform = '';
                    this.style.boxShadow = '';
                });
            });
        });
    </script>
</body>
</html>