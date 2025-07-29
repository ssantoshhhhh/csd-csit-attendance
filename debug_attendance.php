<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Attendance System Debug Information</h2>";

// Test 1: Database Connection
echo "<h3>1. Database Connection Test</h3>";
try {
    $conn = mysqli_connect('127.0.0.1:4306', 'root', 'password', 'attendance');
    if ($conn) {
        echo "✅ Database connection successful<br>";
        echo "Server info: " . mysqli_get_server_info($conn) . "<br>";
        echo "Host info: " . mysqli_get_host_info($conn) . "<br>";
    } else {
        echo "❌ Database connection failed: " . mysqli_connect_error() . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Database connection exception: " . $e->getMessage() . "<br>";
}

// Test 2: Check if tables exist
echo "<h3>2. Table Existence Test</h3>";
if ($conn) {
    $tables_to_check = [
        '27csd_attendance',
        'attendance_modifications'
    ];
    
    foreach ($tables_to_check as $table) {
        $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
        if (mysqli_num_rows($result) > 0) {
            echo "✅ Table '$table' exists<br>";
        } else {
            echo "❌ Table '$table' does not exist<br>";
        }
    }
}

// Test 3: Check table structure
echo "<h3>3. Table Structure Test</h3>";
if ($conn) {
    $result = mysqli_query($conn, "DESCRIBE 27csd_attendance");
    if ($result) {
        echo "✅ 27csd_attendance table structure:<br>";
        echo "<ul>";
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<li>{$row['Field']} - {$row['Type']}</li>";
        }
        echo "</ul>";
    } else {
        echo "❌ Error describing table: " . mysqli_error($conn) . "<br>";
    }
}

// Test 4: Check permissions
echo "<h3>4. Database Permissions Test</h3>";
if ($conn) {
    // Test INSERT permission
    $test_insert = mysqli_query($conn, "INSERT INTO 27csd_attendance (attendance_date, session, register_no, status, faculty_name) VALUES ('2025-01-29', 'Test', 'TEST001', 1, 'Test Faculty')");
    if ($test_insert) {
        echo "✅ INSERT permission granted<br>";
        // Clean up test data
        mysqli_query($conn, "DELETE FROM 27csd_attendance WHERE register_no = 'TEST001'");
    } else {
        echo "❌ INSERT permission denied: " . mysqli_error($conn) . "<br>";
    }
    
    // Test SELECT permission
    $test_select = mysqli_query($conn, "SELECT COUNT(*) as count FROM 27csd_attendance");
    if ($test_select) {
        $row = mysqli_fetch_assoc($test_select);
        echo "✅ SELECT permission granted (count: {$row['count']})<br>";
    } else {
        echo "❌ SELECT permission denied: " . mysqli_error($conn) . "<br>";
    }
}

// Test 5: Session functionality
echo "<h3>5. Session Test</h3>";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['test_var'] = 'test_value';
if (isset($_SESSION['test_var']) && $_SESSION['test_var'] === 'test_value') {
    echo "✅ Session functionality working<br>";
} else {
    echo "❌ Session functionality not working<br>";
}

// Test 6: PHP Configuration
echo "<h3>6. PHP Configuration</h3>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Memory Limit: " . ini_get('memory_limit') . "<br>";
echo "Max Execution Time: " . ini_get('max_execution_time') . "<br>";
echo "Post Max Size: " . ini_get('post_max_size') . "<br>";
echo "Upload Max Filesize: " . ini_get('upload_max_filesize') . "<br>";

// Test 7: File permissions
echo "<h3>7. File Permissions Test</h3>";
$files_to_check = [
    'attendance_entry.php',
    'connect.php',
    'head.php',
    'nav.php',
    'nav_top.php'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "✅ File '$file' exists and is readable<br>";
    } else {
        echo "❌ File '$file' does not exist<br>";
    }
}

echo "<h3>Debug Complete</h3>";
echo "<p>If you see any ❌ errors above, those are likely the cause of the HTTP 500 error.</p>";
?> 