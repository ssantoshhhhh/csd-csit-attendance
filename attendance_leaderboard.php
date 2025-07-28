<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include "./connect.php";

// Handle table selection via POST and store in session
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['table'])) {
    $_SESSION['table'] = $_POST['table'];
    header('Location: attendance_leaderboard.php');
    exit();
}
$table = isset($_SESSION['table']) ? $_SESSION['table'] : '28csit_b_attendance';

// Get all students sorted by points desc
$leaderboard_query = "
    SELECT 
        register_no,
        COUNT(*) as total_sessions,
        SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as present_sessions,
        ROUND((SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100, 2) as attendance_percentage
    FROM $table 
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
                <a href="student_attendance.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left"></i> Back to Attendance
                </a>
            </div>
            
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