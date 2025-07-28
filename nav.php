<?php
// Session is already started in the main files
?>
<div class="main-header">
    <div class="container">
        <div class="logo-section">
            <img src="logo.png" alt="SRKR Engineering College" onerror="this.style.display='none'">
            <div class="college-info">
                <h1>SRKR Engineering College</h1>
                <p>Bhimavaram, Andhra Pradesh | Autonomous Institution</p>
            </div>
        </div>
    </div>
</div>
<nav class="navbar navbar-expand-lg main-nav">
    <div class="container">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="index.php">
                        <i class="fas fa-home"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <?php if (isset($_SESSION['faculty_logged_in']) && $_SESSION['faculty_logged_in']): ?>
                        <a class="nav-link" href="attendance_entry.php">
                            <i class="fas fa-clipboard-check"></i> Attendance Entry
                        </a>
                    <?php else: ?>
                        <a class="nav-link" href="attendance_login.php">
                            <i class="fas fa-sign-in-alt"></i> Faculty Login
                        </a>
                    <?php endif; ?>
                </li>
                <li class="nav-item">
                    <?php if (isset($_SESSION['hod_logged_in']) && $_SESSION['hod_logged_in']): ?>
                        <a class="nav-link" href="hod_dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> HOD Dashboard
                        </a>
                    <?php else: ?>
                        <a class="nav-link" href="hod_login.php">
                            <i class="fas fa-user-shield"></i> HOD Login
                        </a>
                    <?php endif; ?>
                </li>
                <!-- <li class="nav-item">
                    <a class="nav-link" href="student_attendance.php">
                        <i class="fas fa-users"></i> View Attendance
                    </a>
                </li> -->
                <!-- <li class="nav-item">
                    <a class="nav-link" href="attendance_leaderboard.php">
                        <i class="fas fa-trophy"></i> Leaderboard
                    </a>
                </li> -->
                <li class="nav-item">
                    <a class="nav-link" href="https://srkrec.edu.in" target="_blank">
                        <i class="fas fa-external-link-alt"></i> Main Website
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav> 