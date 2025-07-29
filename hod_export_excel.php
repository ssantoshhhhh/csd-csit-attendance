<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['hod_logged_in']) || !$_SESSION['hod_logged_in']) {
    header('Location: hod_login.php');
    exit();
}
include './connect.php';

// Get parameters
$table = $_POST['table'] ?? $_GET['table'] ?? '';
$start_date = $_POST['start_date'] ?? $_GET['start_date'] ?? '';
$end_date = $_POST['end_date'] ?? $_GET['end_date'] ?? '';

// Validate table
$valid_tables = [
    '28csit_a_attendance' => '2/4 CSIT-A',
    '28csit_b_attendance' => '2/4 CSIT-B',
    '28csd_attendance'    => '2/4 CSD',
    '27csit_attendance'   => '3/4 CSIT',
    '27csd_attendance'    => '3/4 CSD',
    '26csd_attendance'    => '4/4 CSD',
];

if (!array_key_exists($table, $valid_tables)) {
    die('Invalid table selected');
}

// Build date conditions
$date_conditions = "";
if (!empty($start_date) && !empty($end_date)) {
    $date_conditions = " AND attendance_date BETWEEN '" . mysqli_real_escape_string($conn, $start_date) . "' AND '" . mysqli_real_escape_string($conn, $end_date) . "'";
} elseif (!empty($start_date)) {
    $date_conditions = " AND attendance_date >= '" . mysqli_real_escape_string($conn, $start_date) . "'";
} elseif (!empty($end_date)) {
    $date_conditions = " AND attendance_date <= '" . mysqli_real_escape_string($conn, $end_date) . "'";
}

// Get all unique dates and sessions for the header
$header_query = "
    SELECT DISTINCT attendance_date, session
    FROM `$table` 
    WHERE 1=1$date_conditions
    ORDER BY attendance_date, session
";
$header_result = mysqli_query($conn, $header_query);

if (!$header_result) {
    die('Error fetching header data: ' . mysqli_error($conn));
}

// Build header structure
$dates = [];
$sessions = [];
while ($row = mysqli_fetch_assoc($header_result)) {
    $date = $row['attendance_date'];
    $session = $row['session'];
    
    if (!isset($dates[$date])) {
        $dates[$date] = [];
    }
    $dates[$date][] = $session;
    if (!in_array($session, $sessions)) {
        $sessions[] = $session;
    }
}

// Get all students with faculty information
$students_query = "
    SELECT DISTINCT register_no, faculty_name
    FROM `$table` 
    WHERE 1=1$date_conditions
    ORDER BY register_no
";
$students_result = mysqli_query($conn, $students_query);

if (!$students_result) {
    die('Error fetching students: ' . mysqli_error($conn));
}

$students = [];
$student_faculty = [];
while ($row = mysqli_fetch_assoc($students_result)) {
    $students[] = $row['register_no'];
    $student_faculty[$row['register_no']] = $row['faculty_name'];
}

// Get attendance data for all students
$attendance_query = "
    SELECT register_no, attendance_date, session, status
    FROM `$table` 
    WHERE 1=1$date_conditions
    ORDER BY register_no, attendance_date, session
";
$attendance_result = mysqli_query($conn, $attendance_query);

if (!$attendance_result) {
    die('Error fetching attendance data: ' . mysqli_error($conn));
}

// Build attendance matrix
$attendance_matrix = [];
while ($row = mysqli_fetch_assoc($attendance_result)) {
    $key = $row['register_no'] . '_' . $row['attendance_date'] . '_' . $row['session'];
    $attendance_matrix[$key] = $row['status'];
}

// Create CSV file
$filename = $valid_tables[$table] . '_Attendance_Pivot_' . date('Y-m-d_H-i-s') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$output = fopen('php://output', 'w');

// Add BOM for Excel UTF-8 compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Create header rows
$header_row1 = ['S.No', 'Regn No', 'Faculty Name'];
$header_row2 = ['', '', ''];

foreach ($dates as $date => $date_sessions) {
    $header_row1[] = date('d/m/Y', strtotime($date));
    $header_row1[] = '';
    
    // Add session sub-headers
    $has_forenoon = in_array('Forenoon', $date_sessions);
    $has_afternoon = in_array('Afternoon', $date_sessions);
    
    if ($has_forenoon && $has_afternoon) {
        $header_row2[] = 'FN';
        $header_row2[] = 'AN';
    } elseif ($has_forenoon) {
        $header_row2[] = 'FN';
        $header_row2[] = '';
    } elseif ($has_afternoon) {
        $header_row2[] = '';
        $header_row2[] = 'AN';
    }
}

fputcsv($output, $header_row1);
fputcsv($output, $header_row2);

// Create data rows
$sno = 1;
foreach ($students as $student) {
    $faculty_name = isset($student_faculty[$student]) ? $student_faculty[$student] : 'N/A';
    $data_row = [$sno, $student, $faculty_name];
    
    foreach ($dates as $date => $date_sessions) {
        $has_forenoon = in_array('Forenoon', $date_sessions);
        $has_afternoon = in_array('Afternoon', $date_sessions);
        
        if ($has_forenoon) {
            $key = $student . '_' . $date . '_Forenoon';
            $status = isset($attendance_matrix[$key]) ? $attendance_matrix[$key] : '';
            
            if ($status === '') {
                $data_row[] = 'N/A';
            } elseif ($status == 1) {
                $data_row[] = '1';
            } else {
                $data_row[] = '0';
            }
        }
        
        if ($has_afternoon) {
            $key = $student . '_' . $date . '_Afternoon';
            $status = isset($attendance_matrix[$key]) ? $attendance_matrix[$key] : '';
            
            if ($status === '') {
                $data_row[] = 'N/A';
            } elseif ($status == 1) {
                $data_row[] = '1';
            } else {
                $data_row[] = '0';
            }
        }
    }
    
    fputcsv($output, $data_row);
    $sno++;
}

fclose($output);

exit();
?> 