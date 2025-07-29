<?php
// Simple database test for debugging live environment
include './connect.php';

echo "<h2>Database Connection Test</h2>";

// Test basic connection
if ($conn) {
    echo "<p style='color: green;'>✓ Database connection successful</p>";
} else {
    echo "<p style='color: red;'>✗ Database connection failed</p>";
    exit();
}

// Test if we can read from attendance tables
$test_tables = [
    '28csit_a_attendance',
    '28csit_b_attendance', 
    '28csd_attendance',
    '27csit_attendance',
    '27csd_attendance',
    '26csd_attendance'
];

echo "<h3>Table Access Test:</h3>";
foreach ($test_tables as $table) {
    $query = "SELECT COUNT(*) as count FROM `$table` LIMIT 1";
    $result = mysqli_query($conn, $query);
    
    if ($result) {
        $count = mysqli_fetch_assoc($result)['count'];
        echo "<p style='color: green;'>✓ Table '$table' accessible (Records: $count)</p>";
    } else {
        echo "<p style='color: red;'>✗ Table '$table' not accessible: " . mysqli_error($conn) . "</p>";
    }
}

// Test if we can write to attendance tables
echo "<h3>Write Permission Test:</h3>";
$test_table = '28csit_a_attendance';
$test_query = "INSERT INTO `$test_table` (attendance_date, session, register_no, status, faculty_name) 
               VALUES ('2025-01-29', 'Test', 'TEST001', 1, 'Test Faculty') 
               ON DUPLICATE KEY UPDATE status = 1";

$result = mysqli_query($conn, $test_query);
if ($result) {
    echo "<p style='color: green;'>✓ Write permission to '$test_table' successful</p>";
    
    // Clean up test data
    $cleanup = "DELETE FROM `$test_table` WHERE register_no = 'TEST001' AND attendance_date = '2025-01-29'";
    mysqli_query($conn, $cleanup);
} else {
    echo "<p style='color: red;'>✗ Write permission to '$test_table' failed: " . mysqli_error($conn) . "</p>";
}

// Test attendance_modifications table
echo "<h3>Modifications Table Test:</h3>";
$mod_query = "INSERT INTO attendance_modifications (table_name, attendance_date, session, faculty_name, modification_reason, changes_made, modified_at) 
              VALUES ('test_table', '2025-01-29', 'Test', 'Test Faculty', 'Test reason', 'Test changes', NOW())";

$result = mysqli_query($conn, $mod_query);
if ($result) {
    echo "<p style='color: green;'>✓ Write permission to 'attendance_modifications' successful</p>";
    
    // Clean up test data
    $cleanup = "DELETE FROM attendance_modifications WHERE table_name = 'test_table' AND attendance_date = '2025-01-29'";
    mysqli_query($conn, $cleanup);
} else {
    echo "<p style='color: red;'>✗ Write permission to 'attendance_modifications' failed: " . mysqli_error($conn) . "</p>";
}

// Test session handling
echo "<h3>Session Test:</h3>";
session_start();
$_SESSION['test_session'] = 'test_value';
if (isset($_SESSION['test_session']) && $_SESSION['test_session'] == 'test_value') {
    echo "<p style='color: green;'>✓ Session handling working</p>";
    unset($_SESSION['test_session']);
} else {
    echo "<p style='color: red;'>✗ Session handling not working</p>";
}

echo "<h3>PHP Info:</h3>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Session Save Path: " . session_save_path() . "</p>";
echo "<p>Session Status: " . (session_status() == PHP_SESSION_ACTIVE ? 'Active' : 'Not Active') . "</p>";

mysqli_close($conn);
?> 