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
                                    <button type="button" class="btn btn-primary w-100" style="border-radius: 10px; padding: 15px;" data-bs-toggle="modal" data-bs-target="#attendanceModal">
                                        <i class="fas fa-users"></i><br>
                                        View Attendance
                                    </button>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <button type="button" class="btn btn-warning w-100" style="border-radius: 10px; padding: 15px;" data-bs-toggle="modal" data-bs-target="#leaderboardModal">
                                        <i class="fas fa-trophy"></i><br>
                                        Leaderboard
                                    </button>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <button type="button" class="btn btn-success w-100" style="border-radius: 10px; padding: 15px;" data-bs-toggle="modal" data-bs-target="#exportModal">
                                        <i class="fas fa-file-excel"></i><br>
                                        Export Excel
                                    </button>
                                </div>
                                <!-- <div class="col-md-3 mb-3">
                                    <a href="attendance_login.php" class="btn btn-success w-100" style="border-radius: 10px; padding: 15px;">
                                        <i class="fas fa-sign-in-alt"></i><br>
                                        Faculty Login
                                    </a>
                                </div> -->
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
                            <!-- Desktop Table View -->
                            <div class="table-responsive d-none d-md-block">
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
                            
                            <!-- Mobile Card View -->
                            <div class="d-md-none">
                                <?php foreach ($recent_modifications as $mod): ?>
                                    <div class="card mb-3" style="border: 1px solid #e3e6f0; border-radius: 10px;">
                                        <div class="card-body p-3">
                                            <div class="row">
                                                <div class="col-6">
                                                    <span class="badge bg-primary mb-2" style="border-radius: 8px; font-size: 0.7rem;">
                                                        <?php echo $sections[$mod['table_name']] ?? $mod['table_name']; ?>
                                                    </span>
                                                    <div class="mb-2">
                                                        <small class="text-muted">
                                                            <i class="fas fa-calendar"></i> <?php echo date('d M Y', strtotime($mod['attendance_date'])); ?>
                                                        </small>
                                                    </div>
                                                    <div class="mb-2">
                                                        <span class="badge <?php echo $mod['session'] == 'Forenoon' ? 'bg-warning' : 'bg-info'; ?>" style="border-radius: 8px; font-size: 0.7rem;">
                                                            <?php echo htmlspecialchars($mod['session']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="col-6 text-end">
                                                    <small class="text-muted">
                                                        <i class="fas fa-clock"></i> <?php echo date('d M Y H:i', strtotime($mod['modified_at'])); ?>
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="mb-2">
                                                <strong style="font-size: 0.8rem; color: var(--primary-blue);">
                                                    <i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($mod['faculty_name']); ?>
                                                </strong>
                                            </div>
                                            <div class="mb-2">
                                                <small class="text-muted" style="font-size: 0.75rem;">
                                                    <strong>Reason:</strong> <?php echo htmlspecialchars($mod['modification_reason']); ?>
                                                </small>
                                            </div>
                                            <?php if (!empty($mod['changes_made'])): ?>
                                                <div>
                                                    <span class="badge bg-info" style="border-radius: 8px; font-size: 0.65rem;">
                                                        <i class="fas fa-exchange-alt"></i> Changes
                                                    </span>
                                                    <small class="text-muted d-block mt-1" style="font-size: 0.7rem;">
                                                        <?php echo htmlspecialchars($mod['changes_made']); ?>
                                                    </small>
                                                </div>
                                            <?php else: ?>
                                                <div>
                                                    <small class="text-muted" style="font-size: 0.7rem;">
                                                        <i class="fas fa-info-circle"></i> No changes tracked
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
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
    
    <!-- Attendance Selection Modal -->
    <div class="modal fade" id="attendanceModal" tabindex="-1" aria-labelledby="attendanceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: 15px; border: none; box-shadow: 0 8px 32px rgba(7,101,147,0.15);">
                <div class="modal-header" style="background: var(--light-blue); border-bottom: 1px solid #e3e6f0; border-radius: 15px 15px 0 0;">
                    <h5 class="modal-title" id="attendanceModalLabel" style="color: var(--primary-blue); font-weight: 600;">
                        <i class="fas fa-users"></i> Select Section & Date Range
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form method="POST" action="student_attendance.php" id="attendanceForm">
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 style="color: var(--primary-blue); font-weight: 600; margin-bottom: 15px;">
                                    <i class="fas fa-graduation-cap"></i> Select Section
                                </h6>
                                <div class="row">
                                    <?php
                                    $sections = [
                                        ['table' => '28csit_a_attendance', 'title' => '2/4 CSIT-A', 'desc' => '2nd Year, CSIT-A Section', 'icon' => 'fas fa-users'],
                                        ['table' => '28csit_b_attendance', 'title' => '2/4 CSIT-B', 'desc' => '2nd Year, CSIT-B Section', 'icon' => 'fas fa-users'],
                                        ['table' => '28csd_attendance', 'title' => '2/4 CSD', 'desc' => '2nd Year, CSD Section', 'icon' => 'fas fa-users'],
                                        ['table' => '27csit_attendance', 'title' => '3/4 CSIT', 'desc' => '3rd Year, CSIT Section', 'icon' => 'fas fa-user-graduate'],
                                        ['table' => '27csd_attendance', 'title' => '3/4 CSD', 'desc' => '3rd Year, CSD Section', 'icon' => 'fas fa-user-graduate'],
                                        ['table' => '26csd_attendance', 'title' => '4/4 CSD', 'desc' => '4th Year, CSD Section', 'icon' => 'fas fa-user-tie'],
                                    ];
                                    foreach ($sections as $sec): ?>
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="section-option" style="border: 2px solid #e3e6f0; border-radius: 10px; padding: 15px; cursor: pointer; transition: all 0.3s ease; background: #f8f9fa;" onclick="selectSection('<?php echo $sec['table']; ?>', this)">
                                                <div class="text-center">
                                                    <i class="<?php echo $sec['icon']; ?>" style="font-size: 2rem; color: var(--primary-blue); margin-bottom: 10px;"></i>
                                                    <h6 style="color: var(--primary-blue); font-weight: 600; margin-bottom: 5px;">
                                                        <?php echo $sec['title']; ?>
                                                    </h6>
                                                    <small class="text-muted"><?php echo $sec['desc']; ?></small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 style="color: var(--primary-blue); font-weight: 600; margin-bottom: 15px;">
                                    <i class="fas fa-calendar"></i> Select Date Range (Optional)
                                </h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="start_date" class="form-label" style="color: var(--primary-blue); font-weight: 500;">
                                            <i class="fas fa-calendar-day"></i> Start Date
                                        </label>
                                        <input type="date" name="start_date" id="start_date" class="form-control" style="border-radius: 10px; padding: 10px 15px;">
                                        <small class="form-text text-muted">Leave empty to view all records</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="end_date" class="form-label" style="color: var(--primary-blue); font-weight: 500;">
                                            <i class="fas fa-calendar-day"></i> End Date
                                        </label>
                                        <input type="date" name="end_date" id="end_date" class="form-control" style="border-radius: 10px; padding: 10px 15px;">
                                        <small class="form-text text-muted">Leave empty to view all records</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <input type="hidden" name="table" id="selected_table" value="">
                        <input type="hidden" name="hod_view" value="1">
                        
                        <div class="text-center">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-primary" id="viewAttendanceBtn" disabled>
                                <i class="fas fa-eye"></i> View Attendance
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Leaderboard Selection Modal -->
    <div class="modal fade" id="leaderboardModal" tabindex="-1" aria-labelledby="leaderboardModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: 15px; border: none; box-shadow: 0 8px 32px rgba(7,101,147,0.15);">
                <div class="modal-header" style="background: var(--light-blue); border-bottom: 1px solid #e3e6f0; border-radius: 15px 15px 0 0;">
                    <h5 class="modal-title" id="leaderboardModalLabel" style="color: var(--primary-blue); font-weight: 600;">
                        <i class="fas fa-trophy"></i> Select Section & Date Range for Leaderboard
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form method="POST" action="attendance_leaderboard.php" id="leaderboardForm">
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 style="color: var(--primary-blue); font-weight: 600; margin-bottom: 15px;">
                                    <i class="fas fa-graduation-cap"></i> Select Section
                                </h6>
                                <div class="row">
                                    <?php
                                    $sections = [
                                        ['table' => '28csit_a_attendance', 'title' => '2/4 CSIT-A', 'desc' => '2nd Year, CSIT-A Section', 'icon' => 'fas fa-users'],
                                        ['table' => '28csit_b_attendance', 'title' => '2/4 CSIT-B', 'desc' => '2nd Year, CSIT-B Section', 'icon' => 'fas fa-users'],
                                        ['table' => '28csd_attendance', 'title' => '2/4 CSD', 'desc' => '2nd Year, CSD Section', 'icon' => 'fas fa-users'],
                                        ['table' => '27csit_attendance', 'title' => '3/4 CSIT', 'desc' => '3rd Year, CSIT Section', 'icon' => 'fas fa-user-graduate'],
                                        ['table' => '27csd_attendance', 'title' => '3/4 CSD', 'desc' => '3rd Year, CSD Section', 'icon' => 'fas fa-user-graduate'],
                                        ['table' => '26csd_attendance', 'title' => '4/4 CSD', 'desc' => '4th Year, CSD Section', 'icon' => 'fas fa-user-tie'],
                                    ];
                                    foreach ($sections as $sec): ?>
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="section-option-leaderboard" style="border: 2px solid #e3e6f0; border-radius: 10px; padding: 15px; cursor: pointer; transition: all 0.3s ease; background: #f8f9fa;" onclick="selectSectionLeaderboard('<?php echo $sec['table']; ?>', this)">
                                                <div class="text-center">
                                                    <i class="<?php echo $sec['icon']; ?>" style="font-size: 2rem; color: var(--primary-blue); margin-bottom: 10px;"></i>
                                                    <h6 style="color: var(--primary-blue); font-weight: 600; margin-bottom: 5px;">
                                                        <?php echo $sec['title']; ?>
                                                    </h6>
                                                    <small class="text-muted"><?php echo $sec['desc']; ?></small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 style="color: var(--primary-blue); font-weight: 600; margin-bottom: 15px;">
                                    <i class="fas fa-calendar"></i> Select Date Range (Optional)
                                </h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="leaderboard_start_date" class="form-label" style="color: var(--primary-blue); font-weight: 500;">
                                            <i class="fas fa-calendar-day"></i> Start Date
                                        </label>
                                        <input type="date" name="start_date" id="leaderboard_start_date" class="form-control" style="border-radius: 10px; padding: 10px 15px;">
                                        <small class="form-text text-muted">Leave empty to view all records</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="leaderboard_end_date" class="form-label" style="color: var(--primary-blue); font-weight: 500;">
                                            <i class="fas fa-calendar-day"></i> End Date
                                        </label>
                                        <input type="date" name="end_date" id="leaderboard_end_date" class="form-control" style="border-radius: 10px; padding: 10px 15px;">
                                        <small class="form-text text-muted">Leave empty to view all records</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <input type="hidden" name="table" id="selected_table_leaderboard" value="">
                        <input type="hidden" name="hod_view" value="1">
                        
                        <div class="text-center">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-warning" id="viewLeaderboardBtn" disabled>
                                <i class="fas fa-trophy"></i> View Leaderboard
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Excel Modal -->
    <div class="modal fade" id="exportModal" tabindex="-1" aria-labelledby="exportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="border-radius: 15px; border: none; box-shadow: 0 8px 32px rgba(7,101,147,0.15);">
                <div class="modal-header" style="background: var(--light-blue); border-bottom: 1px solid #e3e6f0; border-radius: 15px 15px 0 0;">
                    <h5 class="modal-title" id="exportModalLabel" style="color: var(--primary-blue); font-weight: 600;">
                        <i class="fas fa-file-excel"></i> Export Attendance Data to Excel
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form method="POST" action="hod_export_excel.php" id="exportForm">
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 style="color: var(--primary-blue); font-weight: 600; margin-bottom: 15px;">
                                    <i class="fas fa-graduation-cap"></i> Select Section
                                </h6>
                                <div class="row">
                                    <?php
                                    $sections = [
                                        ['table' => '28csit_a_attendance', 'title' => '2/4 CSIT-A', 'desc' => '2nd Year, CSIT-A Section', 'icon' => 'fas fa-users'],
                                        ['table' => '28csit_b_attendance', 'title' => '2/4 CSIT-B', 'desc' => '2nd Year, CSIT-B Section', 'icon' => 'fas fa-users'],
                                        ['table' => '28csd_attendance', 'title' => '2/4 CSD', 'desc' => '2nd Year, CSD Section', 'icon' => 'fas fa-users'],
                                        ['table' => '27csit_attendance', 'title' => '3/4 CSIT', 'desc' => '3rd Year, CSIT Section', 'icon' => 'fas fa-user-graduate'],
                                        ['table' => '27csd_attendance', 'title' => '3/4 CSD', 'desc' => '3rd Year, CSD Section', 'icon' => 'fas fa-user-graduate'],
                                        ['table' => '26csd_attendance', 'title' => '4/4 CSD', 'desc' => '4th Year, CSD Section', 'icon' => 'fas fa-user-tie'],
                                    ];
                                    foreach ($sections as $sec): ?>
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="section-option-export" style="border: 2px solid #e3e6f0; border-radius: 10px; padding: 15px; cursor: pointer; transition: all 0.3s ease; background: #f8f9fa;" onclick="selectSectionExport('<?php echo $sec['table']; ?>', this)">
                                                <div class="text-center">
                                                    <i class="<?php echo $sec['icon']; ?>" style="font-size: 2rem; color: var(--primary-blue); margin-bottom: 10px;"></i>
                                                    <h6 style="color: var(--primary-blue); font-weight: 600; margin-bottom: 5px;">
                                                        <?php echo $sec['title']; ?>
                                                    </h6>
                                                    <small class="text-muted"><?php echo $sec['desc']; ?></small>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-12">
                                <h6 style="color: var(--primary-blue); font-weight: 600; margin-bottom: 15px;">
                                    <i class="fas fa-calendar"></i> Select Date Range (Optional)
                                </h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="export_start_date" class="form-label" style="color: var(--primary-blue); font-weight: 500;">
                                            <i class="fas fa-calendar-day"></i> Start Date
                                        </label>
                                        <input type="date" name="start_date" id="export_start_date" class="form-control" style="border-radius: 10px; padding: 10px 15px;">
                                        <small class="form-text text-muted">Leave empty to export all records</small>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="export_end_date" class="form-label" style="color: var(--primary-blue); font-weight: 500;">
                                            <i class="fas fa-calendar-day"></i> End Date
                                        </label>
                                        <input type="date" name="end_date" id="export_end_date" class="form-control" style="border-radius: 10px; padding: 10px 15px;">
                                        <small class="form-text text-muted">Leave empty to export all records</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info" style="border-radius: 10px;">
                            <i class="fas fa-info-circle"></i> <strong>Export Information:</strong>
                            <ul class="mb-0 mt-2">
                                <li><strong>Format:</strong> S.No | Regn No | Faculty Name | Date (with FN/AN sub-headers)</li>
                                <li><strong>Header Structure:</strong> Two rows - dates in row 1, FN/AN in row 2</li>
                                <li><strong>Status Codes:</strong> 1 = Present, 0 = Absent, N/A = No data</li>
                                <li><strong>Session Codes:</strong> FN = Forenoon, AN = Afternoon</li>
                                <li><strong>File Format:</strong> CSV that opens perfectly in Excel</li>
                            </ul>
                        </div>
                        
                        <input type="hidden" name="table" id="selected_table_export" value="">
                        
                        <div class="text-center">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-success" id="exportExcelBtn" disabled>
                                <i class="fas fa-file-excel"></i> Export to CSV
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include "footer.php"; ?>
    
    <script>
        function selectSection(tableName, element) {
            // Remove active class from all sections
            document.querySelectorAll('.section-option').forEach(option => {
                option.style.borderColor = '#e3e6f0';
                option.style.backgroundColor = '#f8f9fa';
            });
            
            // Add active class to selected section
            element.style.borderColor = 'var(--primary-blue)';
            element.style.backgroundColor = '#e8f4fd';
            
            // Set the selected table
            document.getElementById('selected_table').value = tableName;
            
            // Enable the view button
            document.getElementById('viewAttendanceBtn').disabled = false;
        }
        
        function selectSectionLeaderboard(tableName, element) {
            // Remove active class from all sections
            document.querySelectorAll('.section-option-leaderboard').forEach(option => {
                option.style.borderColor = '#e3e6f0';
                option.style.backgroundColor = '#f8f9fa';
            });
            
            // Add active class to selected section
            element.style.borderColor = 'var(--primary-blue)';
            element.style.backgroundColor = '#e8f4fd';
            
            // Set the selected table
            document.getElementById('selected_table_leaderboard').value = tableName;
            
            // Enable the view button
            document.getElementById('viewLeaderboardBtn').disabled = false;
        }
        
        function selectSectionExport(tableName, element) {
            // Remove active class from all sections
            document.querySelectorAll('.section-option-export').forEach(option => {
                option.style.borderColor = '#e3e6f0';
                option.style.backgroundColor = '#f8f9fa';
            });
            
            // Add active class to selected section
            element.style.borderColor = 'var(--primary-blue)';
            element.style.backgroundColor = '#e8f4fd';
            
            // Set the selected table
            document.getElementById('selected_table_export').value = tableName;
            
            // Enable the export button
            document.getElementById('exportExcelBtn').disabled = false;
        }
        
        // Set default dates (last 30 days)
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date();
            const thirtyDaysAgo = new Date(today.getTime() - (30 * 24 * 60 * 60 * 1000));
            
            // Set dates for attendance modal
            document.getElementById('end_date').value = today.toISOString().split('T')[0];
            document.getElementById('start_date').value = thirtyDaysAgo.toISOString().split('T')[0];
            
            // Set dates for leaderboard modal
            document.getElementById('leaderboard_end_date').value = today.toISOString().split('T')[0];
            document.getElementById('leaderboard_start_date').value = thirtyDaysAgo.toISOString().split('T')[0];
            
            // Set dates for export modal
            document.getElementById('export_end_date').value = today.toISOString().split('T')[0];
            document.getElementById('export_start_date').value = thirtyDaysAgo.toISOString().split('T')[0];
        });
    </script>
    
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
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .table th,
            .table td {
                padding: 8px 6px;
                font-size: 0.75rem;
                white-space: nowrap;
                min-width: 80px;
            }
            
            .table th:nth-child(4),
            .table td:nth-child(4) {
                min-width: 120px;
            }
            
            .table th:nth-child(5),
            .table td:nth-child(5) {
                min-width: 150px;
                white-space: normal;
                word-wrap: break-word;
            }
            
            .table th:nth-child(6),
            .table td:nth-child(6) {
                min-width: 140px;
                white-space: normal;
                word-wrap: break-word;
            }
            
            .table th:nth-child(7),
            .table td:nth-child(7) {
                min-width: 100px;
            }
            
            .badge {
                font-size: 0.65rem;
                padding: 3px 6px;
            }
            
            .text-muted {
                font-size: 0.7rem;
            }
            
            /* Improve modal responsiveness */
            .modal-dialog {
                margin: 10px;
                max-width: calc(100% - 20px);
            }
            
            .modal-body {
                padding: 15px;
            }
            
            .section-option,
            .section-option-leaderboard,
            .section-option-export {
                padding: 10px !important;
            }
            
            .section-option i,
            .section-option-leaderboard i,
            .section-option-export i {
                font-size: 1.5rem !important;
            }
            
            .section-option h6,
            .section-option-leaderboard h6,
            .section-option-export h6 {
                font-size: 0.9rem !important;
            }
        }
        
        @media (max-width: 576px) {
            .table-responsive {
                font-size: 0.7rem;
            }
            
            .table th,
            .table td {
                padding: 6px 4px;
                font-size: 0.65rem;
                min-width: 70px;
            }
            
            .table th:nth-child(4),
            .table td:nth-child(4) {
                min-width: 100px;
            }
            
            .table th:nth-child(5),
            .table td:nth-child(5) {
                min-width: 120px;
                max-width: 120px;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
            .table th:nth-child(6),
            .table td:nth-child(6) {
                min-width: 110px;
                max-width: 110px;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
            .table th:nth-child(7),
            .table td:nth-child(7) {
                min-width: 80px;
            }
            
            .badge {
                font-size: 0.6rem;
                padding: 2px 4px;
            }
            
            /* Stack cards in single column on very small screens */
            .col-md-6,
            .col-lg-4 {
                width: 100%;
            }
            
            /* Improve modal on very small screens */
            .modal-dialog {
                margin: 5px;
                max-width: calc(100% - 10px);
            }
            
            .modal-body {
                padding: 10px;
            }
            
            .section-option,
            .section-option-leaderboard,
            .section-option-export {
                padding: 8px !important;
            }
            
            .section-option i,
            .section-option-leaderboard i,
            .section-option-export i {
                font-size: 1.2rem !important;
            }
            
            .section-option h6,
            .section-option-leaderboard h6,
            .section-option-export h6 {
                font-size: 0.8rem !important;
            }
        }
        
        /* Landscape orientation fixes */
        @media (max-width: 768px) and (orientation: landscape) {
            .table-responsive {
                max-height: 60vh;
                overflow-y: auto;
            }
            
            .modal-dialog {
                max-height: 90vh;
                overflow-y: auto;
            }
        }
        
        /* Touch target improvements */
        .table th,
        .table td {
            min-height: 44px;
        }
        
        @media (max-width: 768px) {
            .table th,
            .table td {
                min-height: 40px;
            }
        }
        
        @media (max-width: 576px) {
            .table th,
            .table td {
                min-height: 36px;
            }
        }
    </style>
</body>
</html> 