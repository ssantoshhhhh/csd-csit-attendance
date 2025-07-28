<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$login_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Simple HOD credentials (in production, use proper authentication)
    if ($username === 'hod' && $password === 'hod123') {
        $_SESSION['hod_logged_in'] = true;
        $_SESSION['hod_username'] = $username;
        header('Location: hod_dashboard.php');
        exit();
    } else {
        $login_error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include "./head.php"; ?>
    <title>HOD Login - SRKR Engineering College</title>
</head>
<body>
    <!-- Top Bar -->
    <?php include "nav_top.php"; ?>
    
    <!-- Main Header -->
    <?php include "nav.php"; ?>
    
    <!-- Page Title -->
    <div class="page-title">
        <div class="container">
            <h2><i class="fas fa-user-shield"></i> HOD Login</h2>
            <p>Head of Department Portal Access</p>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="card" style="border: none; box-shadow: 0 4px 16px rgba(7,101,147,0.1); border-radius: 15px;">
                        <div class="card-body p-5">
                            <div class="text-center mb-4">
                                <i class="fas fa-user-shield" style="font-size: 3rem; color: var(--primary-blue); margin-bottom: 20px;"></i>
                                <h4 style="color: var(--primary-blue); font-weight: 600;">HOD Portal</h4>
                                <p class="text-muted">Enter your credentials to access the HOD dashboard</p>
                            </div>
                            
                            <?php if ($login_error): ?>
                                <div class="alert alert-danger" style="border-radius: 10px;">
                                    <i class="fas fa-exclamation-triangle"></i> <?php echo $login_error; ?>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST">
                                <div class="mb-4">
                                    <label for="username" class="form-label" style="color: var(--primary-blue); font-weight: 500;">
                                        <i class="fas fa-user"></i> Username
                                    </label>
                                    <input type="text" 
                                           class="form-control" 
                                           id="username" 
                                           name="username" 
                                           placeholder="Enter your username" 
                                           required 
                                           autofocus
                                           style="border-radius: 10px; padding: 12px 15px; border: 2px solid #e3e6f0; transition: border-color 0.3s ease;">
                                </div>
                                
                                <div class="mb-4">
                                    <label for="password" class="form-label" style="color: var(--primary-blue); font-weight: 500;">
                                        <i class="fas fa-key"></i> Password
                                    </label>
                                    <input type="password" 
                                           class="form-control" 
                                           id="password" 
                                           name="password" 
                                           placeholder="Enter your password" 
                                           required
                                           style="border-radius: 10px; padding: 12px 15px; border: 2px solid #e3e6f0; transition: border-color 0.3s ease;">
                                </div>
                                
                                <button type="submit" 
                                        class="btn btn-primary w-100" 
                                        style="border-radius: 10px; padding: 12px; font-size: 16px; font-weight: 500; background: var(--primary-blue); border: none;">
                                    <i class="fas fa-sign-in-alt"></i> Login
                                </button>
                            </form>
                            
                            <div class="text-center mt-4">
                                <a href="index.php" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-arrow-left"></i> Back to Home
                                </a>
                            </div>
                            
                            <!-- <div class="text-center mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i> Demo Credentials: hod / hod123
                                </small>
                            </div> -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include "footer.php"; ?>
    
    <style>
        .form-control:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 0.2rem rgba(7,101,147,0.25);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
    </style>
</body>
</html> 