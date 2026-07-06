<?php
// register.php - Complete Professional Registration with Student/Regular Options
session_start();

if (isset($_SESSION['member_logged_in']) && $_SESSION['member_logged_in'] === true) {
    header('Location: dashboard.php');
    exit;
}

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/functions.php';

$error = '';
$success = '';
$db = Database::getInstance()->getConnection();

// Get membership types from database
$memberships = $db->query("SELECT * FROM Membership_Type WHERE status = 'active'")->fetchAll();

// If no memberships, insert defaults
if (empty($memberships)) {
    $db->exec("INSERT INTO Membership_Type (membership_type_id, name, description, fee, duration_months, benefits, status) VALUES
        (1, 'Regular', 'Regular membership - Full gym access', 300.00, 1, 'Full gym access\nGroup classes\nLocker room\nWater station', 'active'),
        (2, 'Student', 'Student membership - Student ID required', 150.00, 1, 'Full gym access\nGroup classes\nLocker room\nWater station\nStudent ID required', 'active')");
    $memberships = $db->query("SELECT * FROM Membership_Type WHERE status = 'active'")->fetchAll();
}

// Get the last member ID for auto-generation
$lastIdResult = $db->query("SELECT MAX(member_id) as last_id FROM Members");
$lastId = $lastIdResult->fetch()['last_id'] ?? 0;
$nextId = $lastId + 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $membershipType = intval($_POST['membership_type'] ?? 0);
    $indexNumber = trim($_POST['index_number'] ?? '');
    $healthStatus = trim($_POST['health_status'] ?? '');
    
    // Validation
    if (empty($firstName) || empty($lastName) || empty($email) || empty($username) || empty($password)) {
        $error = 'Please fill in all required fields.';
    } elseif (!Security::validateEmail($email)) {
        $error = 'Please enter a valid email address.';
    } elseif (!Security::validateUsername($username)) {
        $error = 'Username must be 3-30 characters and contain only letters, numbers, and underscores.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif ($membershipType <= 0) {
        $error = 'Please select a membership type.';
    } elseif ($membershipType == 2 && empty($indexNumber)) {
        $error = 'Please enter your Student Index Number.';
    } elseif ($membershipType == 2 && !preg_match('/^[0-9]{10}$/', $indexNumber)) {
        $error = 'Student Index Number must be exactly 10 digits.';
    } else {
        // Check if username or email exists
        $check = $db->prepare("SELECT COUNT(*) FROM Members WHERE username = ? OR email = ?");
        $check->execute([$username, $email]);
        if ($check->fetchColumn() > 0) {
            $error = 'Username or email already exists.';
        } else {
            // Verify membership type exists
            $verifyMembership = $db->prepare("SELECT membership_type_id FROM Membership_Type WHERE membership_type_id = ?");
            $verifyMembership->execute([$membershipType]);
            if ($verifyMembership->rowCount() == 0) {
                $error = 'Selected membership type does not exist.';
            } else {
                $hash = Security::hashPassword($password);
                
                // Prepare SQL based on membership type
                if ($membershipType == 2) {
                    // Student - store index number
                    $sql = "INSERT INTO Members (
                                first_name, last_name, email, telephone, username, 
                                password_hash, health_status, membership_type_id, 
                                index_number, registration_date, status
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 'pending')";
                    $stmt = $db->prepare($sql);
                    $params = [
                        $firstName, $lastName, $email, $telephone, $username, 
                        $hash, $healthStatus, $membershipType, $indexNumber
                    ];
                } else {
                    // Regular - auto-generate ID (use member_id auto-increment)
                    $sql = "INSERT INTO Members (
                                first_name, last_name, email, telephone, username, 
                                password_hash, health_status, membership_type_id, 
                                index_number, registration_date, status
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, CURDATE(), 'pending')";
                    $stmt = $db->prepare($sql);
                    $params = [
                        $firstName, $lastName, $email, $telephone, $username, 
                        $hash, $healthStatus, $membershipType
                    ];
                }
                
                if ($stmt->execute($params)) {
                    $newId = $db->lastInsertId();
                    $success = 'Registration successful! Your Member ID is: <strong>#' . str_pad($newId, 6, '0', STR_PAD_LEFT) . '</strong><br>Please wait for admin approval.';
                } else {
                    $error = 'Registration failed. Please try again.';
                }
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
    <title>Join USTED-K Gym - Register</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        /* ============================================
           PROFESSIONAL REGISTRATION STYLES
           ============================================ */
        
        /* ===== RESET & BASE ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #0D0D0D;
            color: #FFFFFF;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background-image: 
                radial-gradient(ellipse at 10% 20%, rgba(255,107,0,0.05) 0%, transparent 50%),
                radial-gradient(ellipse at 90% 80%, rgba(255,107,0,0.03) 0%, transparent 50%);
        }

        .register-wrapper {
            width: 100%;
            max-width: 580px;
            margin: 0 auto;
        }

        .register-card {
            background: linear-gradient(145deg, #1A1A1A, #141414);
            border-radius: 28px;
            padding: 48px 40px;
            border: 1px solid rgba(255,107,0,0.08);
            box-shadow: 0 30px 80px rgba(0,0,0,0.6), 0 0 0 1px rgba(255,107,0,0.05);
            position: relative;
            overflow: hidden;
            transition: all 0.4s ease;
        }

        .register-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -30%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255,107,0,0.03) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .register-card::after {
            content: '';
            position: absolute;
            bottom: -40%;
            left: -20%;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(255,107,0,0.02) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        /* ===== HEADER ===== */
        .register-header {
            text-align: center;
            margin-bottom: 36px;
            position: relative;
            z-index: 1;
        }

        .register-header .logo-icon {
            width: 72px;
            height: 72px;
            background: linear-gradient(135deg, #FF6B00, #FF8C00);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            font-size: 2rem;
            color: #FFFFFF;
            box-shadow: 0 8px 32px rgba(255,107,0,0.25);
            transition: transform 0.3s ease;
        }

        .register-header .logo-icon:hover {
            transform: rotate(-8deg) scale(1.05);
        }

        .register-header h1 {
            font-size: 1.75rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 4px;
            background: linear-gradient(135deg, #FFFFFF, #FFD9B3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .register-header p {
            color: #A68A7A;
            font-size: 0.95rem;
            font-weight: 400;
        }

        .register-header .membership-badge {
            display: inline-block;
            margin-top: 12px;
            padding: 6px 20px;
            background: rgba(255,107,0,0.08);
            border: 1px solid rgba(255,107,0,0.12);
            border-radius: 100px;
            font-size: 0.7rem;
            font-weight: 600;
            color: #FF8C00;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        /* ===== FORM ===== */
        .register-form {
            position: relative;
            z-index: 1;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .form-group {
            margin-bottom: 16px;
            transition: all 0.3s ease;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 0.7rem;
            margin-bottom: 6px;
            color: #C4B5A5;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .form-group label .required {
            color: #FF6B6B;
            margin-left: 2px;
        }

        .form-group .input-wrapper {
            position: relative;
        }

        .form-group .input-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #5A4A3A;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            pointer-events: none;
        }

        .form-group .input-wrapper:focus-within i {
            color: #FF8C00;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px 12px 44px;
            background: #1E1E1E;
            border: 2px solid rgba(255,107,0,0.06);
            border-radius: 14px;
            color: #FFFFFF;
            font-size: 0.9rem;
            font-family: 'Inter', sans-serif;
            transition: all 0.3s ease;
            outline: none;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #FF6B00;
            background: #242424;
            box-shadow: 0 0 0 4px rgba(255,107,0,0.06);
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #4A3A2A;
        }

        .form-group textarea {
            padding: 12px 16px 12px 44px;
            min-height: 70px;
            resize: vertical;
        }

        /* ===== INDEX NUMBER FIELD (Hidden by default) ===== */
        .index-number-field {
            display: none;
            animation: slideDown 0.4s ease;
        }

        .index-number-field.visible {
            display: block;
        }

        .index-number-field .input-wrapper i {
            color: #5A4A3A;
        }

        .index-number-field .input-wrapper:focus-within i {
            color: #FF8C00;
        }

        .index-number-field .hint {
            font-size: 0.7rem;
            color: #4A3A2A;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .index-number-field .hint i {
            color: #5A4A3A;
        }

        /* ===== MEMBERSHIP TYPE CARDS ===== */
        .membership-selector {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 4px;
        }

        .membership-option {
            position: relative;
        }

        .membership-option input[type="radio"] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .membership-option label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 16px 12px;
            background: #1E1E1E;
            border: 2px solid rgba(255,107,0,0.06);
            border-radius: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            min-height: 80px;
        }

        .membership-option label i {
            font-size: 1.5rem;
            margin-bottom: 6px;
            color: #5A4A3A;
            transition: all 0.3s ease;
        }

        .membership-option label .plan-name {
            font-weight: 700;
            font-size: 0.95rem;
            color: #FFFFFF;
        }

        .membership-option label .plan-price {
            font-size: 0.85rem;
            font-weight: 600;
            color: #FF8C00;
            margin-top: 2px;
        }

        .membership-option label .plan-duration {
            font-size: 0.65rem;
            color: #5A4A3A;
        }

        .membership-option input[type="radio"]:checked + label {
            border-color: #FF6B00;
            background: rgba(255,107,0,0.06);
            box-shadow: 0 0 0 4px rgba(255,107,0,0.06);
        }

        .membership-option input[type="radio"]:checked + label i {
            color: #FF8C00;
        }

        .membership-option label:hover {
            border-color: rgba(255,107,0,0.2);
            background: rgba(255,107,0,0.03);
            transform: translateY(-2px);
        }

        .membership-option input[type="radio"]:checked + label .plan-name {
            color: #FF8C00;
        }

        /* ===== SUBMIT BUTTON ===== */
        .btn-register {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #FF6B00, #FF8C00);
            color: #FFFFFF;
            border: none;
            border-radius: 14px;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-family: 'Inter', sans-serif;
            margin-top: 8px;
            position: relative;
            overflow: hidden;
        }

        .btn-register::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            transition: left 0.5s ease;
        }

        .btn-register:hover::before {
            left: 100%;
        }

        .btn-register:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 32px rgba(255,107,0,0.3);
        }

        .btn-register:active {
            transform: translateY(0);
        }

        /* ===== ALERTS ===== */
        .alert {
            padding: 14px 18px;
            border-radius: 14px;
            margin-bottom: 20px;
            font-size: 0.875rem;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            animation: slideDown 0.4s ease;
        }

        .alert i {
            font-size: 1.1rem;
            margin-top: 2px;
            flex-shrink: 0;
        }

        .alert-danger {
            background: rgba(255,68,68,0.08);
            color: #FF6B6B;
            border: 1px solid rgba(255,68,68,0.08);
        }

        .alert-success {
            background: rgba(0,184,148,0.08);
            color: #00B894;
            border: 1px solid rgba(0,184,148,0.08);
        }

        .alert-success a {
            color: #00B894;
            font-weight: 600;
            text-decoration: underline;
        }

        .alert-success strong {
            color: #FF8C00;
            font-size: 1.1rem;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ===== FOOTER ===== */
        .register-footer {
            text-align: center;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,107,0,0.06);
            position: relative;
            z-index: 1;
        }

        .register-footer p {
            color: #5A4A3A;
            font-size: 0.875rem;
        }

        .register-footer a {
            color: #FF8C00;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .register-footer a:hover {
            color: #FF6B00;
        }

        .register-footer .footer-links {
            display: flex;
            justify-content: center;
            gap: 16px;
            margin-top: 8px;
        }

        .register-footer .footer-links a {
            font-size: 0.75rem;
            color: #4A3A2A;
            font-weight: 400;
        }

        .register-footer .footer-links a:hover {
            color: #FF8C00;
        }

        /* ===== ANIMATIONS ===== */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-fade-in {
            animation: fadeIn 0.5s ease forwards;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 600px) {
            .register-card {
                padding: 32px 20px;
                border-radius: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }

            .membership-selector {
                grid-template-columns: 1fr 1fr;
                gap: 8px;
            }

            .membership-option label {
                min-height: 60px;
                padding: 12px 8px;
            }

            .register-header h1 {
                font-size: 1.5rem;
            }

            .register-header .logo-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }

            .register-footer .footer-links {
                flex-wrap: wrap;
                gap: 8px;
            }
        }

        @media (max-width: 400px) {
            .membership-selector {
                grid-template-columns: 1fr;
            }
        }

        /* ===== SCROLLBAR ===== */
        ::-webkit-scrollbar {
            width: 6px;
        }
        ::-webkit-scrollbar-track {
            background: #0D0D0D;
        }
        ::-webkit-scrollbar-thumb {
            background: #FF6B00;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #FF8C00;
        }

        /* ===== TOOLTIP STYLES ===== */
        .tooltip-text {
            font-size: 0.75rem;
            color: #5A4A3A;
            margin-top: 4px;
            display: block;
        }
        .tooltip-text i {
            margin-right: 4px;
        }
    </style>
</head>
<body>

<div class="register-wrapper">
    <div class="register-card">

        <!-- ===== HEADER ===== -->
        <div class="register-header">
            <div class="logo-icon">
                <i class="fas fa-dumbbell"></i>
            </div>
            <h1>Start Your Journey</h1>
            <p>Join USTED-K Gym Center today</p>
            <span class="membership-badge">
                <i class="fas fa-shield-alt"></i> Secure Registration
            </span>
        </div>

        <!-- ===== ALERTS ===== -->
        <?php if ($error): ?>
            <div class="alert alert-danger animate-fade-in">
                <i class="fas fa-exclamation-circle"></i>
                <div><?= htmlspecialchars($error) ?></div>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success animate-fade-in">
                <i class="fas fa-check-circle"></i>
                <div>
                    <?= $success ?>
                    <br><a href="login.php">Click here to login →</a>
                </div>
            </div>
        <?php endif; ?>

        <!-- ===== FORM ===== -->
        <?php if (!$success): ?>
        <form method="POST" class="register-form" id="registerForm" novalidate>

            <!-- ===== NAME ROW ===== -->
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" name="first_name" id="first_name" placeholder="Enter first name" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="last_name">Last Name <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" name="last_name" id="last_name" placeholder="Enter last name" required>
                    </div>
                </div>
            </div>

            <!-- ===== EMAIL ===== -->
            <div class="form-group">
                <label for="email">Email Address <span class="required">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" id="email" placeholder="you@example.com" required>
                </div>
            </div>

            <!-- ===== TELEPHONE ===== -->
            <div class="form-group">
                <label for="telephone">Phone Number</label>
                <div class="input-wrapper">
                    <i class="fas fa-phone"></i>
                    <input type="tel" name="telephone" id="telephone" placeholder="+233 XX XXX XXXX">
                </div>
            </div>

            <!-- ===== USERNAME ===== -->
            <div class="form-group">
                <label for="username">Username <span class="required">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-user-tag"></i>
                    <input type="text" name="username" id="username" placeholder="Choose a username" required>
                </div>
            </div>

            <!-- ===== PASSWORD ROW ===== -->
            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" placeholder="Min 6 characters" required minlength="6">
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                    <div class="input-wrapper">
                        <i class="fas fa-check-circle"></i>
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="Re-enter password" required>
                    </div>
                </div>
            </div>

            <!-- ===== MEMBERSHIP TYPE - STUDENT / REGULAR ===== -->
            <div class="form-group">
                <label>Select Membership Type <span class="required">*</span></label>
                <div class="membership-selector">
                    <!-- Regular Member -->
                    <div class="membership-option">
                        <input type="radio" name="membership_type" id="membership_regular" value="1" checked>
                        <label for="membership_regular">
                            <i class="fas fa-user"></i>
                            <span class="plan-name">Regular</span>
                            <span class="plan-price">GH₵ 300 / month</span>
                            <span class="plan-duration">Full gym access</span>
                        </label>
                    </div>
                    <!-- Student Member -->
                    <div class="membership-option">
                        <input type="radio" name="membership_type" id="membership_student" value="2">
                        <label for="membership_student">
                            <i class="fas fa-graduation-cap"></i>
                            <span class="plan-name">Student</span>
                            <span class="plan-price">GH₵ 150 / month</span>
                            <span class="plan-duration">Student discount</span>
                        </label>
                    </div>
                </div>
                <span class="tooltip-text"><i class="fas fa-info-circle"></i> Student ID required at check-in</span>
            </div>

            <!-- ===== INDEX NUMBER FIELD (Shows only for Student) ===== -->
            <div class="form-group index-number-field" id="indexField">
                <label for="index_number">Student Index Number <span class="required">*</span></label>
                <div class="input-wrapper">
                    <i class="fas fa-id-card"></i>
                    <input type="text" name="index_number" id="index_number" 
                           placeholder="Enter 10-digit index number" 
                           maxlength="10" pattern="[0-9]{10}">
                </div>
                <div class="hint">
                    <i class="fas fa-info-circle"></i> Must be exactly 10 digits (e.g., 2023123456)
                </div>
            </div>

            <!-- ===== HEALTH STATUS ===== -->
            <div class="form-group">
                <label for="health_status">Health Status</label>
                <div class="input-wrapper">
                    <i class="fas fa-heartbeat"></i>
                    <textarea name="health_status" id="health_status" placeholder="Any medical conditions or injuries? (Optional)"></textarea>
                </div>
            </div>

            <!-- ===== AUTO-GENERATED ID DISPLAY ===== -->
            <div style="background: rgba(255,107,0,0.04); border-radius: 10px; padding: 10px 16px; margin-bottom: 16px; border: 1px solid rgba(255,107,0,0.06);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="color: #A68A7A; font-size: 0.75rem;">
                        <i class="fas fa-id-badge"></i> Your Member ID
                    </span>
                    <span style="font-weight: 700; color: #FF8C00; font-size: 1.1rem;">
                        #<?= str_pad($nextId, 6, '0', STR_PAD_LEFT) ?>
                    </span>
                </div>
                <div style="font-size: 0.65rem; color: #4A3A2A; margin-top: 2px;">
                    <i class="fas fa-info-circle"></i> This will be your unique member ID
                </div>
            </div>

            <!-- ===== SUBMIT ===== -->
            <button type="submit" class="btn-register">
                <i class="fas fa-user-plus"></i>
                Create Account
                <i class="fas fa-arrow-right"></i>
            </button>

        </form>
        <?php endif; ?>

        <!-- ===== FOOTER ===== -->
        <div class="register-footer">
            <p>Already have an account? <a href="login.php">Sign In</a></p>
            <div class="footer-links">
                <a href="index.php"><i class="fas fa-arrow-left"></i> Back to Home</a>
                <span style="color: #2A2A2A;">|</span>
                <a href="admin_login.php"><i class="fas fa-crown"></i> Admin Login</a>
            </div>
        </div>

    </div>
</div>

<!-- ===== JAVASCRIPT ===== -->
<script>
    (function() {
        'use strict';

        const form = document.getElementById('registerForm');
        const password = document.getElementById('password');
        const confirmPassword = document.getElementById('confirm_password');
        const membershipRegular = document.getElementById('membership_regular');
        const membershipStudent = document.getElementById('membership_student');
        const indexField = document.getElementById('indexField');
        const indexInput = document.getElementById('index_number');

        // ===== TOGGLE INDEX NUMBER FIELD =====
        function toggleIndexField() {
            if (membershipStudent.checked) {
                indexField.classList.add('visible');
                indexInput.setAttribute('required', 'required');
            } else {
                indexField.classList.remove('visible');
                indexInput.removeAttribute('required');
                indexInput.value = '';
            }
        }

        // ===== EVENT LISTENERS =====
        membershipRegular.addEventListener('change', toggleIndexField);
        membershipStudent.addEventListener('change', toggleIndexField);

        // ===== INITIAL STATE =====
        toggleIndexField();

        // ===== INDEX NUMBER VALIDATION =====
        indexInput.addEventListener('input', function() {
            // Only allow digits
            this.value = this.value.replace(/\D/g, '');
            
            // Auto-format to show groups (optional)
            if (this.value.length > 10) {
                this.value = this.value.slice(0, 10);
            }
            
            // Visual feedback
            if (this.value.length === 10) {
                this.style.borderColor = 'rgba(0,184,148,0.3)';
            } else if (this.value.length > 0) {
                this.style.borderColor = 'rgba(255,107,0,0.2)';
            } else {
                this.style.borderColor = '';
            }
        });

        // ===== FORM VALIDATION =====
        if (form) {
            form.addEventListener('submit', function(e) {
                let isValid = true;
                let errorMessage = '';

                // Check password match
                if (password.value !== confirmPassword.value) {
                    isValid = false;
                    errorMessage = 'Passwords do not match.';
                    confirmPassword.style.borderColor = '#FF6B6B';
                } else {
                    confirmPassword.style.borderColor = '';
                }

                // Check index number for student
                if (membershipStudent.checked) {
                    const indexVal = indexInput.value.trim();
                    if (indexVal.length !== 10) {
                        isValid = false;
                        errorMessage = 'Student Index Number must be exactly 10 digits.';
                        indexInput.style.borderColor = '#FF6B6B';
                    } else {
                        indexInput.style.borderColor = '';
                    }
                }

                if (!isValid) {
                    e.preventDefault();
                    alert(errorMessage);
                }
            });
        }

        // ===== REMOVE ERROR STYLING ON INPUT =====
        confirmPassword.addEventListener('input', function() {
            if (this.value === password.value) {
                this.style.borderColor = 'rgba(0,184,148,0.3)';
            } else {
                this.style.borderColor = '';
            }
        });

        // ===== MEMBERSHIP SELECTOR HOVER EFFECT =====
        document.querySelectorAll('.membership-option label').forEach(function(label) {
            label.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
            });
            label.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        console.log('🏋️ USTED-K Gym Registration Loaded');
        console.log('📝 Regular: GH₵ 300/month | Student: GH₵ 150/month');
    })();
</script>

</body>
</html>