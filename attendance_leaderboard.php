<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include "./connect.php";

// Handle table selection via POST and store in session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['table'])) {
    $_SESSION['table'] = $_POST['table'];
    // If this is from HOD dashboard, also store date range
    if (isset($_POST['hod_view']) && $_POST['hod_view'] == '1') {
        $_SESSION['start_date'] = $_POST['start_date'] ?? '';
        $_SESSION['end_date'] = $_POST['end_date'] ?? '';
        $_SESSION['hod_view'] = true;
    }
    header('Location: attendance_leaderboard.php');
    exit();
}
$table = isset($_SESSION['table']) ? $_SESSION['table'] : '28csit_b_attendance';

// Get date range parameters
$start_date = isset($_SESSION['start_date']) ? $_SESSION['start_date'] : (isset($_GET['start_date']) ? $_GET['start_date'] : '');
$end_date = isset($_SESSION['end_date']) ? $_SESSION['end_date'] : (isset($_GET['end_date']) ? $_GET['end_date'] : '');
$is_hod_view = isset($_SESSION['hod_view']) ? $_SESSION['hod_view'] : (isset($_GET['hod_view']) ? $_GET['hod_view'] : false);

// Clear session data after using it
if ($is_hod_view && isset($_SESSION['hod_view'])) {
    unset($_SESSION['start_date']);
    unset($_SESSION['end_date']);
    unset($_SESSION['hod_view']);
}

// Build date range conditions
$date_conditions = "";
if (!empty($start_date) && !empty($end_date)) {
    $date_conditions = " WHERE attendance_date BETWEEN '" . mysqli_real_escape_string($conn, $start_date) . "' AND '" . mysqli_real_escape_string($conn, $end_date) . "'";
} elseif (!empty($start_date)) {
    $date_conditions = " WHERE attendance_date >= '" . mysqli_real_escape_string($conn, $start_date) . "'";
} elseif (!empty($end_date)) {
    $date_conditions = " WHERE attendance_date <= '" . mysqli_real_escape_string($conn, $end_date) . "'";
}

// Get all students sorted by points desc
$leaderboard_query = "
    SELECT 
        register_no,
        COUNT(*) as total_sessions,
        SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as present_sessions,
        ROUND((SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as attendance_percentage
    FROM $table$date_conditions
    GROUP BY register_no 
    ORDER BY present_sessions DESC, register_no
";
$leaderboard_result = mysqli_query($conn, $leaderboard_query);
$leaderboard = [];
while ($row = mysqli_fetch_assoc($leaderboard_result)) {
    $leaderboard[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en-US">
<head>
    <?php include "head.php"; ?>
    <title>Attendance Leaderboard - SRKR Engineering College</title>
</head>
<body>
    <!-- Top Bar -->
    <?php include "nav_top.php"; ?>
    
    <!-- Main Header -->
    <?php include "nav.php"; ?>
    
    <!-- Page Title -->
    <div class="page-title">
        <div class="container">
            <h2><i class="fas fa-trophy"></i> Attendance Leaderboard</h2>
            <p>Top performers and achievement badges</p>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Back Button -->
            <div class="mb-4">
                <?php if ($is_hod_view): ?>
                    <a href="hod_dashboard.php" class="btn btn-outline-primary me-2">
                        <i class="fas fa-arrow-left"></i> Back to HOD Dashboard
                    </a>
                <?php endif; ?>
                <a href="student_attendance.php<?php echo $is_hod_view && (!empty($start_date) || !empty($end_date)) ? '?start_date=' . urlencode($start_date) . '&end_date=' . urlencode($end_date) . '&hod_view=1' : ''; ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Attendance
                </a>
            </div>
            
            <?php if ($is_hod_view && (!empty($start_date) || !empty($end_date))): ?>
                <!-- HOD Date Range Info -->
                <div class="card mb-4" style="border: none; box-shadow: 0 4px 16px rgba(7,101,147,0.1); border-radius: 15px;">
                    <div class="card-header" style="background: var(--light-blue); border-bottom: 1px solid #e3e6f0; border-radius: 15px 15px 0 0;">
                        <h5 class="mb-0" style="color: var(--primary-blue); font-weight: 600;">
                            <i class="fas fa-calendar"></i> Date Range Filter
                        </h5>
                    </div>
                    <div class="card-body p-3">
                        <p class="mb-0">
                            <?php if (!empty($start_date) && !empty($end_date)): ?>
                                <span class="badge bg-info" style="border-radius: 8px;">
                                    <i class="fas fa-calendar-day"></i> 
                                    <?php echo date('d M Y', strtotime($start_date)); ?> - <?php echo date('d M Y', strtotime($end_date)); ?>
                                </span>
                            <?php elseif (!empty($start_date)): ?>
                                <span class="badge bg-info" style="border-radius: 8px;">
                                    <i class="fas fa-calendar-day"></i> 
                                    From: <?php echo date('d M Y', strtotime($start_date)); ?>
                                </span>
                            <?php elseif (!empty($end_date)): ?>
                                <span class="badge bg-info" style="border-radius: 8px;">
                                    <i class="fas fa-calendar-day"></i> 
                                    Until: <?php echo date('d M Y', strtotime($end_date)); ?>
                                </span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Leaderboard Table -->
            <div class="card" style="border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border-radius: 15px;">
                <div class="card-header" style="background: var(--light-blue); border-bottom: 1px solid #e3e6f0; border-radius: 15px 15px 0 0;">
                    <h5 class="mb-0" style="color: var(--primary-blue); font-weight: 600;">
                        <i class="fas fa-medal"></i> Top Performers
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead style="background: var(--light-blue);">
                                <tr>
                                    <th style="border: none; padding: 15px; color: var(--primary-blue); font-weight: 600; text-align: center;">Rank</th>
                                    <th style="border: none; padding: 15px; color: var(--primary-blue); font-weight: 600;">Registration No</th>
                                    <th style="border: none; padding: 15px; color: var(--primary-blue); font-weight: 600; text-align: center;">Badge</th>
                                    <th style="border: none; padding: 15px; color: var(--primary-blue); font-weight: 600; text-align: center;">Points</th>
                                    <th style="border: none; padding: 15px; color: var(--primary-blue); font-weight: 600; text-align: center;">Attendance %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $rank = 1;
                                $prev_points = null;
                                $display_rank = 1;
                                foreach ($leaderboard as $i => $row):
                                    $points = $row['present_sessions'];
                                    // Gamified badges based on points
                                    if ($rank == 1) {
                                        $badge = '<span class="badge bg-warning text-dark">🥇 Gold</span>';
                                        $row_class = 'table-warning';
                                    } elseif ($rank == 2) {
                                        $badge = '<span class="badge bg-secondary">🥈 Silver</span>';
                                        $row_class = 'table-light';
                                    } elseif ($rank == 3) {
                                        $badge = '<span class="badge bg-danger">🥉 Bronze</span>';
                                        $row_class = 'table-danger';
                                    } elseif ($points >= 90) {
                                        $badge = '<span class="badge bg-info">💎 Platinum</span>';
                                        $row_class = '';
                                    } elseif ($points >= 80) {
                                        $badge = '<span class="badge bg-primary">🔷 Diamond</span>';
                                        $row_class = '';
                                    } elseif ($points >= 70) {
                                        $badge = '<span class="badge bg-warning text-dark">⭐ Star</span>';
                                        $row_class = '';
                                    } elseif ($points >= 60) {
                                        $badge = '<span class="badge bg-success">🏅 Consistent</span>';
                                        $row_class = '';
                                    } else {
                                        $badge = '<span class="badge bg-secondary">🎯 Challenger</span>';
                                        $row_class = '';
                                    }
                                    // Handle ties
                                    if ($prev_points !== null && $row['present_sessions'] != $prev_points) {
                                        $display_rank = $rank;
                                    }
                                ?>
                                <tr class="<?php echo $row_class; ?>" style="border-bottom: 1px solid #f0f0f0;">
                                    <td style="padding: 15px; text-align: center; font-weight: 600;"><?php echo $display_rank; ?></td>
                                    <td style="padding: 15px; font-weight: 500;"><?php echo htmlspecialchars($row['register_no']); ?></td>
                                    <td style="padding: 15px; text-align: center;"><?php echo $badge; ?></td>
                                    <td style="padding: 15px; text-align: center; font-weight: 600;"><?php echo $row['present_sessions']; ?></td>
                                    <td style="padding: 15px; text-align: center;">
                                        <span class="badge <?php echo $row['attendance_percentage'] >= 75 ? 'bg-success' : ($row['attendance_percentage'] >= 60 ? 'bg-warning' : 'bg-danger'); ?>">
                                            <?php echo $row['attendance_percentage']; ?>%
                                        </span>
                                    </td>
                                </tr>
                                <?php
                                    $prev_points = $row['present_sessions'];
                                    $rank++;
                                endforeach;
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Badge Legend -->
            <div class="card mt-4" style="border: none; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-radius: 15px;">
                <div class="card-header" style="background: var(--light-blue); border-bottom: 1px solid #e3e6f0; border-radius: 15px 15px 0 0;">
                    <h5 class="mb-0" style="color: var(--primary-blue); font-weight: 600;">
                        <i class="fas fa-info-circle"></i> Badge Legend
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li class="mb-2"><span class="badge bg-warning text-dark">🥇 Gold</span> Top 1</li>
                                <li class="mb-2"><span class="badge bg-secondary">🥈 Silver</span> Top 2</li>
                                <li class="mb-2"><span class="badge bg-danger">🥉 Bronze</span> Top 3</li>
                                <li class="mb-2"><span class="badge bg-info">💎 Platinum</span> 90+ Points</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul class="list-unstyled">
                                <li class="mb-2"><span class="badge bg-primary">🔷 Diamond</span> 80-89 Points</li>
                                <li class="mb-2"><span class="badge bg-warning text-dark">⭐ Star</span> 70-79 Points</li>
                                <li class="mb-2"><span class="badge bg-success">🏅 Consistent</span> 60-69 Points</li>
                                <li class="mb-2"><span class="badge bg-secondary">🎯 Challenger</span> Below 60 Points</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include "footer.php"; ?>
</body>
</html> 