<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['hod_logged_in']) || !$_SESSION['hod_logged_in']) {
    header('Location: hod_login.php');
    exit();
}
include './connect.php';

$hod_username = $_SESSION['hod_username'] ?? 'HOD';

// Get attendance statistics
$total_students = 0;
$total_sections = 6;
$total_faculty = 6;

// Get attendance data for today
$today = date('Y-m-d');
$today_attendance = 0;
$today_sessions = 0;

$sections = [
    '28csit_a_attendance' => '2/4 CSIT-A',
    '28csit_b_attendance' => '2/4 CSIT-B',
    '28csd_attendance'    => '2/4 CSD',
    '27csit_attendance'   => '3/4 CSIT',
    '27csd_attendance'    => '3/4 CSD',
    '26csd_attendance'    => '4/4 CSD',
];

foreach ($sections as $table => $section_name) {
    $query = "SELECT COUNT(DISTINCT register_no) as student_count FROM `$table`";
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $total_students += $row['student_count'];
    }
    
    // Get today's attendance
    $today_query = "SELECT COUNT(*) as today_count FROM `$table` WHERE attendance_date = '$today'";
    $today_result = mysqli_query($conn, $today_query);
    if ($today_result) {
        $today_row = mysqli_fetch_assoc($today_result);
        $today_attendance += $today_row['today_count'];
    }
}

// Get recent attendance modifications
$modifications_query = "SELECT * FROM attendance_modifications ORDER BY modified_at DESC LIMIT 10";
$modifications_result = mysqli_query($conn, $modifications_query);
$recent_modifications = [];
if ($modifications_result) {
    while ($row = mysqli_fetch_assoc($modifications_result)) {
        $recent_modifications[] = $row;
    }
}

// Get total modifications count
$total_modifications_query = "SELECT COUNT(*) as count FROM attendance_modifications";
$total_modifications_result = mysqli_query($conn, $total_modifications_query);
$total_modifications = 0;
if ($total_modifications_result) {
    $total_modifications = mysqli_fetch_assoc($total_modifications_result)['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include "./head.php"; ?>
    <title>HOD Dashboard - SRKR Engineering College</title>
</head>
<body>
    <!-- Top Bar -->
    <?php include "nav_top.php"; ?>
    
    <!-- Main Header -->
    <?php include "nav.php"; ?>
    
    <!-- Page Title -->
    <div class="page-title">
        <div class="container">
            <h2><i class="fas fa-tachometer-alt"></i> HOD Dashboard</h2>
            <p>Welcome, <?php echo htmlspecialchars($hod_username); ?>! Monitor attendance across all sections</p>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Logout Button -->
            <div class="text-end mb-4">
                <a href="hod_logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="card text-center" style="border: none; box-shadow: 0 4px 16px rgba(7,101,147,0.1); border-radius: 15px;">
                        <div class="card-body p-4">
                            <i class="fas fa-users" style="font-size: 2.5rem; color: var(--primary-blue); margin-bottom: 15px;"></i>
                            <h4 style="color: var(--primary-blue); font-weight: 600;"><?php echo $total_students; ?></h4>
                            <p class="text-muted mb-0">Total Students</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card text-center" style="border: none; box-shadow: 0 4px 16px rgba(7,101,147,0.1); border-radius: 15px;">
                        <div class="card-body p-4">
                            <i class="fas fa-graduation-cap" style="font-size: 2.5rem; color: var(--primary-blue); margin-bottom: 15px;"></i>
                            <h4 style="color: var(--primary-blue); font-weight: 600;"><?php echo $total_sections; ?></h4>
                            <p class="text-muted mb-0">Total Sections</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card text-center" style="border: none; box-shadow: 0 4px 16px rgba(7,101,147,0.1); border-radius: 15px;">
                        <div class="card-body p-4">
                            <i class="fas fa-user-tie" style="font-size: 2.5rem; color: var(--primary-blue); margin-bottom: 15px;"></i>
                            <h4 style="color: var(--primary-blue); font-weight: 600;"><?php echo $total_faculty; ?></h4>
                            <p class="text-muted mb-0">Faculty Members</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-3">
                    <div class="card text-center" style="border: none; box-shadow: 0 4px 16px rgba(7,101,147,0.1); border-radius: 15px;">
                        <div class="card-body p-4">
                            <i class="fas fa-edit" style="font-size: 2.5rem; color: var(--primary-blue); margin-bottom: 15px;"></i>
                            <h4 style="color: var(--primary-blue); font-weight: 600;"><?php echo $total_modifications; ?></h4>
                            <p class="text-muted mb-0">Attendance Modifications</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card" style="border: none; box-shadow: 0 4px 16px rgba(7,101,147,0.1); border-radius: 15px;">
                        <div class="card-header" style="background: var(--light-blue); border-bottom: 1px solid #e3e6f0; border-radius: 15px 15px 0 0;">
                            <h5 class="mb-0" style="color: var(--primary-blue); font-weight: 600;">
                                <i class="fas fa-cogs"></i> Quick Actions
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <a href="student_attendance.php" class="btn btn-primary w-100" style="border-radius: 10px; padding: 15px;">
                                        <i class="fas fa-users"></i><br>
                                        View Attendance
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="attendance_leaderboard.php" class="btn btn-warning w-100" style="border-radius: 10px; padding: 15px;">
                                        <i class="fas fa-trophy"></i><br>
                                        Leaderboard
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="attendance_login.php" class="btn btn-success w-100" style="border-radius: 10px; padding: 15px;">
                                        <i class="fas fa-sign-in-alt"></i><br>
                                        Faculty Login
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="attendance_modifications.php" class="btn btn-warning w-100" style="border-radius: 10px; padding: 15px;">
                                        <i class="fas fa-history"></i><br>
                                        View Modifications
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="index.php" class="btn btn-info w-100" style="border-radius: 10px; padding: 15px;">
                                        <i class="fas fa-home"></i><br>
                                        Home Page
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Modifications -->
            <?php if (!empty($recent_modifications)): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card" style="border: none; box-shadow: 0 4px 16px rgba(7,101,147,0.1); border-radius: 15px;">
                        <div class="card-header" style="background: var(--light-blue); border-bottom: 1px solid #e3e6f0; border-radius: 15px 15px 0 0;">
                            <h5 class="mb-0" style="color: var(--primary-blue); font-weight: 600;">
                                <i class="fas fa-history"></i> Recent Attendance Modifications
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead style="background: var(--light-blue);">
                                        <tr>
                                            <th style="color: var(--primary-blue); font-weight: 600;">Section</th>
                                            <th style="color: var(--primary-blue); font-weight: 600;">Date</th>
                                            <th style="color: var(--primary-blue); font-weight: 600;">Session</th>
                                            <th style="color: var(--primary-blue); font-weight: 600;">Faculty</th>
                                            <th style="color: var(--primary-blue); font-weight: 600;">Reason</th>
                                            <th style="color: var(--primary-blue); font-weight: 600;">Changes Made</th>
                                            <th style="color: var(--primary-blue); font-weight: 600;">Modified At</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_modifications as $mod): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-primary" style="border-radius: 8px;">
                                                        <?php echo $sections[$mod['table_name']] ?? $mod['table_name']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($mod['attendance_date'])); ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo $mod['session'] == 'Forenoon' ? 'bg-warning' : 'bg-info'; ?>" style="border-radius: 8px;">
                                                        <?php echo htmlspecialchars($mod['session']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($mod['faculty_name']); ?>
                                                </td>
                                                <td>
                                                    <span class="text-muted" style="font-size: 0.9rem;">
                                                        <?php echo htmlspecialchars($mod['modification_reason']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($mod['changes_made'])): ?>
                                                        <span class="badge bg-info" style="border-radius: 8px; font-size: 0.7rem;">
                                                            <i class="fas fa-exchange-alt"></i> Changes
                                                        </span>
                                                        <small class="text-muted d-block mt-1" style="font-size: 0.8rem;">
                                                            <?php echo htmlspecialchars($mod['changes_made']); ?>
                                                        </small>
                                                    <?php else: ?>
                                                        <span class="text-muted" style="font-size: 0.8rem;">
                                                            <i class="fas fa-info-circle"></i> No changes tracked
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        <i class="fas fa-clock"></i> <?php echo date('d M Y H:i', strtotime($mod['modified_at'])); ?>
                                                    </small>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Section Overview -->
            <div class="row">
                <div class="col-12">
                    <div class="card" style="border: none; box-shadow: 0 4px 16px rgba(7,101,147,0.1); border-radius: 15px;">
                        <div class="card-header" style="background: var(--light-blue); border-bottom: 1px solid #e3e6f0; border-radius: 15px 15px 0 0;">
                            <h5 class="mb-0" style="color: var(--primary-blue); font-weight: 600;">
                                <i class="fas fa-chart-bar"></i> Section Overview
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="row">
                                <?php foreach ($sections as $table => $section_name): ?>
                                    <?php
                                    // Get section statistics
                                    $section_query = "SELECT 
                                        COUNT(DISTINCT register_no) as total_students,
                                        COUNT(*) as total_records,
                                        SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as present_records,
                                        ROUND((SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as attendance_percentage
                                    FROM `$table`";
                                    $section_result = mysqli_query($conn, $section_query);
                                    $section_data = mysqli_fetch_assoc($section_result);
                                    
                                    // Get modifications count for this section
                                    $mod_count_query = "SELECT COUNT(*) as mod_count FROM attendance_modifications WHERE table_name = '$table'";
                                    $mod_count_result = mysqli_query($conn, $mod_count_query);
                                    $mod_count = 0;
                                    if ($mod_count_result) {
                                        $mod_count = mysqli_fetch_assoc($mod_count_result)['mod_count'];
                                    }
                                    ?>
                                    <div class="col-md-6 col-lg-4 mb-4">
                                        <div class="card h-100" style="border: 1px solid #e3e6f0; border-radius: 10px;">
                                            <div class="card-body p-3">
                                                <h6 style="color: var(--primary-blue); font-weight: 600; margin-bottom: 15px;">
                                                    <i class="fas fa-graduation-cap"></i> <?php echo $section_name; ?>
                                                </h6>
                                                <div class="row text-center">
                                                    <div class="col-6">
                                                        <h5 style="color: var(--primary-blue); font-weight: 600;"><?php echo $section_data['total_students'] ?? 0; ?></h5>
                                                        <small class="text-muted">Students</small>
                                                    </div>
                                                    <div class="col-6">
                                                        <h5 style="color: var(--primary-blue); font-weight: 600;"><?php echo $section_data['attendance_percentage'] ?? 0; ?>%</h5>
                                                        <small class="text-muted">Attendance</small>
                                                    </div>
                                                </div>
                                                <?php if ($mod_count > 0): ?>
                                                    <div class="mt-2 text-center">
                                                        <span class="badge bg-warning" style="border-radius: 8px;">
                                                            <i class="fas fa-edit"></i> <?php echo $mod_count; ?> Modifications
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="mt-3">
                                                    <a href="student_attendance.php" class="btn btn-outline-primary btn-sm w-100" style="border-radius: 8px;">
                                                        <i class="fas fa-eye"></i> View Details
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include "footer.php"; ?>
    
    <style>
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(7,101,147,0.15) !important;
        }
        
        .table th {
            border-top: none;
            font-weight: 600;
        }
        
        .badge {
            font-size: 0.75rem;
        }
        
        /* Mobile responsive improvements for modification table */
        @media (max-width: 768px) {
            .table-responsive {
                font-size: 0.8rem;
            }
            
            .table th,
            .table td {
                padding: 8px 6px;
                font-size: 0.75rem;
            }
            
            .badge {
                font-size: 0.65rem;
                padding: 3px 6px;
            }
            
            .text-muted {
                font-size: 0.7rem;
            }
        }
        
        @media (max-width: 576px) {
            .table th,
            .table td {
                padding: 6px 4px;
                font-size: 0.7rem;
            }
            
            .badge {
                font-size: 0.6rem;
                padding: 2px 4px;
            }
        }
    </style>
</body>
</html> 