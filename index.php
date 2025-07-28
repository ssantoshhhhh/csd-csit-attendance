<?php 
if (session_status() === PHP_SESSION_NONE) session_start();
include "./head.php"; 
?>
<body>
    <!-- Top Bar -->
    <?php include "nav_top.php"; ?>
    
    <!-- Main Header -->
    <?php include "nav.php"; ?>
    
    <!-- Page Title -->
    <div class="page-title">
        <div class="container">
            <h2><i class="fas fa-clipboard-check"></i> SRKR Attendance Portal</h2>
            <p>Select a section to view attendance records</p>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center align-items-stretch">
                <?php
                $sections = [
                                                ['table' => '28csit_a_attendance', 'title' => '2/4 CSIT-A', 'desc' => 'View attendance for 2nd Year, CSIT-A Section', 'icon' => 'fas fa-users'],
                    ['table' => '28csit_b_attendance', 'title' => '2/4 CSIT-B', 'desc' => 'View attendance for 2nd Year, CSIT-B Section', 'icon' => 'fas fa-users'],
                    ['table' => '28csd_attendance', 'title' => '2/4 CSD', 'desc' => 'View attendance for 2nd Year, CSD Section', 'icon' => 'fas fa-users'],
                    ['table' => '27csit_attendance', 'title' => '3/4 CSIT', 'desc' => 'View attendance for 3rd Year, CSIT Section', 'icon' => 'fas fa-user-graduate'],
                    ['table' => '27csd_attendance', 'title' => '3/4 CSD', 'desc' => 'View attendance for 3rd Year, CSD Section', 'icon' => 'fas fa-user-graduate'],
                    ['table' => '26csd_attendance', 'title' => '4/4 CSD', 'desc' => 'View attendance for 4th Year, CSD Section', 'icon' => 'fas fa-user-tie'],
                ];
                foreach ($sections as $sec): ?>
                    <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12 mb-4 d-flex">
                        <form method="post" action="student_attendance.php" style="width: 100%; height: 100%;">
                            <input type="hidden" name="table" value="<?php echo htmlspecialchars($sec['table']); ?>">
                            <button type="submit" class="card h-100 section-card w-100" style="border: none; background: var(--white); box-shadow: 0 4px 16px rgba(7,101,147,0.1); border-radius: 15px; transition: all 0.3s ease; cursor: pointer;">
                                <div class="card-body text-center p-4 d-flex flex-column">
                                    <div class="section-icon mb-3">
                                        <i class="<?php echo $sec['icon']; ?>" style="font-size: 3rem; color: var(--primary-blue);"></i>
                                    </div>
                                    <h5 class="card-title" style="color: var(--primary-blue); font-weight: 600; margin-bottom: 10px;">
                                        <?php echo $sec['title']; ?>
                                    </h5>
                                    <p class="card-text flex-grow-1" style="color: var(--gray-medium); font-size: 0.9rem;">
                                        <?php echo $sec['desc']; ?>
                                    </p>
                                    <div class="mt-3">
                                        <span class="badge bg-primary">View Attendance</span>
                                    </div>
                                </div>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Quick Actions -->
            <!-- <div class="row mt-5">
                <div class="col-12">
                    <div class="card" style="background: var(--light-blue); border: none; border-radius: 15px;">
                        <div class="card-body text-center p-4">
                            <h4 style="color: var(--primary-blue); margin-bottom: 20px;">
                                <i class="fas fa-cogs"></i> Quick Actions
                            </h4>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <a href="attendance_login.php" class="btn btn-primary btn-lg w-100" style="border-radius: 10px;">
                                        <i class="fas fa-sign-in-alt"></i><br>
                                        Faculty Login
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="student_attendance.php" class="btn btn-success btn-lg w-100" style="border-radius: 10px;">
                                        <i class="fas fa-users"></i><br>
                                        View Attendance
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="attendance_leaderboard.php" class="btn btn-warning btn-lg w-100" style="border-radius: 10px;">
                                        <i class="fas fa-trophy"></i><br>
                                        Leaderboard
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="https://srkrec.edu.in" target="_blank" class="btn btn-info btn-lg w-100" style="border-radius: 10px;">
                                        <i class="fas fa-external-link-alt"></i><br>
                                        Main Website
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> -->
        </div>
    </div>
    
    <!-- Footer -->
    <?php include "footer.php"; ?>
    
    <style>
        .section-card {
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            width: 100%;
        }
        
        .section-card .card-body {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
            width: 100%;
        }
        
        /* Ensure proper grid alignment */
        .row {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: stretch;
        }
        
        .col-xl-4,
        .col-lg-4,
        .col-md-6,
        .col-sm-12 {
            display: flex;
            margin-bottom: 1.5rem;
        }
        
        .section-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 32px rgba(7,101,147,0.15) !important;
        }
        
        .section-card:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(7,101,147,0.3);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        /* Ensure equal height cards */
        .col-xl-4,
        .col-lg-4,
        .col-md-6,
        .col-sm-12 {
            display: flex;
        }
        
        /* Card content alignment */
        .section-icon {
            flex-shrink: 0;
        }
        
        .card-title {
            flex-shrink: 0;
        }
        
        .card-text {
            flex-grow: 1;
            margin-bottom: 1rem;
        }
        
        .mt-3 {
            flex-shrink: 0;
        }
        
        /* Mobile Responsive Improvements for Index Page */
        @media (max-width: 768px) {
            .section-card {
                margin-bottom: 15px;
            }
            
            .card-body {
                padding: 20px 15px;
            }
            
            .section-icon i {
                font-size: 2.5rem !important;
            }
            
            .card-title {
                font-size: 1.1rem !important;
                margin-bottom: 8px !important;
            }
            
            .card-text {
                font-size: 0.85rem !important;
                line-height: 1.4;
            }
            
            .badge {
                font-size: 0.8rem;
                padding: 6px 12px;
            }
            
            .row {
                margin-left: -10px;
                margin-right: -10px;
            }
            
            .col-xl-4,
            .col-lg-4,
            .col-md-6,
            .col-sm-12 {
                padding-left: 10px;
                padding-right: 10px;
            }
            
            /* Ensure proper alignment on mobile */
            .col-md-6 {
                width: 50%;
            }
            
            .col-sm-12 {
                width: 100%;
            }
        }
        
        @media (max-width: 576px) {
            .card-body {
                padding: 15px 10px;
            }
            
            .section-icon i {
                font-size: 2rem !important;
            }
            
            .card-title {
                font-size: 1rem !important;
            }
            
            .card-text {
                font-size: 0.8rem !important;
            }
            
            .badge {
                font-size: 0.75rem;
                padding: 5px 10px;
            }
            
            .page-title h2 {
                font-size: 20px;
            }
            
            .page-title p {
                font-size: 14px;
            }
            
            /* Single column layout on very small screens */
            .col-md-6,
            .col-sm-12 {
                width: 100%;
            }
        }
        
        /* Touch target improvements */
        .section-card {
            min-height: 200px;
        }
        
        @media (max-width: 768px) {
            .section-card {
                min-height: 180px;
            }
        }
        
        @media (max-width: 576px) {
            .section-card {
                min-height: 160px;
            }
        }
        
        /* Accessibility improvements */
        .section-card:focus-visible {
            outline: 2px solid var(--primary-blue);
            outline-offset: 2px;
        }
        
        /* High contrast mode support */
        @media (prefers-contrast: high) {
            .section-card {
                border: 2px solid var(--primary-blue);
            }
            
            .card-title {
                color: var(--primary-blue) !important;
            }
        }
    </style>
</body>
</html> 