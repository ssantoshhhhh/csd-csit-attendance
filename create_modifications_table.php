<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

include './connect.php';

if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}

echo "<h2>Attendance Modifications Table Setup</h2>";

// Check if table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'attendance_modifications'");
if (mysqli_num_rows($table_check) > 0) {
    echo "✅ attendance_modifications table already exists<br>";
} else {
    echo "❌ attendance_modifications table does not exist. Creating it...<br>";
    
    // Create the table
    $create_table_sql = "
    CREATE TABLE `attendance_modifications` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `table_name` varchar(50) NOT NULL,
        `attendance_date` date NOT NULL,
        `session` varchar(20) NOT NULL,
        `faculty_name` varchar(100) NOT NULL,
        `modification_reason` text NOT NULL,
        `changes_made` text NOT NULL,
        `modified_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `idx_table_date` (`table_name`, `attendance_date`),
        KEY `idx_faculty` (`faculty_name`),
        KEY `idx_modified_at` (`modified_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    
    $result = mysqli_query($conn, $create_table_sql);
    if ($result) {
        echo "✅ attendance_modifications table created successfully<br>";
    } else {
        echo "❌ Error creating table: " . mysqli_error($conn) . "<br>";
    }
}

// Test insert
echo "<h3>Testing Table Functionality</h3>";
$test_insert = mysqli_query($conn, "INSERT INTO attendance_modifications (table_name, attendance_date, session, faculty_name, modification_reason, changes_made) VALUES ('test_table', '2025-01-29', 'Test', 'Test Faculty', 'Test reason', 'Test changes')");

if ($test_insert) {
    echo "✅ Test insert successful<br>";
    // Clean up test data
    mysqli_query($conn, "DELETE FROM attendance_modifications WHERE table_name = 'test_table'");
    echo "✅ Test data cleaned up<br>";
} else {
    echo "❌ Test insert failed: " . mysqli_error($conn) . "<br>";
}

echo "<h3>Setup Complete</h3>";
echo "<p><a href='attendance_entry.php'>Return to Attendance Entry</a></p>";
?> 