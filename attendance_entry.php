<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['faculty_logged_in']) || !$_SESSION['faculty_logged_in']) {
    header('Location: attendance_login.php');
    exit();
}

// Test database connection
include './connect.php';
if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}

$classes = [
    '28csit_a_attendance' => '2/4 CSIT-A',
    '28csit_b_attendance' => '2/4 CSIT-B',
    '28csd_attendance'    => '2/4 CSD',
    '27csit_attendance'   => '3/4 CSIT',
    '27csd_attendance'    => '3/4 CSD',
    '26csd_attendance'    => '4/4 CSD',
];
$faculty_mapping = [
    '28csit_a_attendance' => ['A Krishna Veni', 'N Aneela'],
    '28csit_b_attendance' => ['K Sunil Varma', 'K Bhanu Rajesh Naidu'],
    '28csd_attendance'    => ['A Satyam', 'J Tulasi Rajesh'],
    '27csit_attendance'   => ['P S V Surya Kumar'],
    '27csd_attendance'    => ['N Mouna'],
    '26csd_attendance'    => ['A Aswini Priyanka'],
];

$success = false;
$error = '';
$absentees = [];
$existing_attendance = [];
$is_edit_mode = false;
$show_readonly = false;

// Check for success message from redirect
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success = true;
}

// Handle form reset
if (isset($_GET['reset'])) {
    unset($_SESSION['edit_reason']);
    header('Location: attendance_entry.php');
    exit();
}

// Get selected values
$selected_class = $_POST['class'] ?? $_GET['class'] ?? array_key_first($classes);
$selected_session = $_POST['session'] ?? $_GET['session'] ?? '';
$selected_date = $_POST['date'] ?? $_GET['date'] ?? date('Y-m-d');
$selected_faculty = $_POST['faculty'] ?? $_GET['faculty'] ?? '';
$assigned_faculty = '';
$students = [];

// Get all students for the section
if ($selected_class && array_key_exists($selected_class, $classes)) {
    $res = mysqli_query($conn, "SELECT DISTINCT register_no FROM `$selected_class` ORDER BY register_no");
    while ($row = mysqli_fetch_assoc($res)) {
        $students[] = $row['register_no'];
    }
    
    // Set assigned faculty based on selection or default to first faculty
    if (isset($faculty_mapping[$selected_class])) {
        $faculty_list = $faculty_mapping[$selected_class];
        if (empty($selected_faculty) && !empty($faculty_list)) {
            $selected_faculty = $faculty_list[0];
        }
        $assigned_faculty = $selected_faculty;
    }
    
    // Check for existing attendance for the date/session
    if ($selected_date && $selected_session) {
        $existing_query = "SELECT register_no, status FROM `$selected_class` WHERE attendance_date = '" . mysqli_real_escape_string($conn, $selected_date) . "' AND session = '" . mysqli_real_escape_string($conn, $selected_session) . "'";
        $existing_result = mysqli_query($conn, $existing_query);
        while ($row = mysqli_fetch_assoc($existing_result)) {
            $existing_attendance[$row['register_no']] = $row['status'];
        }
    }
}

// Handle Edit button first
if (isset($_POST['edit_attendance'])) {
    if (isset($_POST['edit_reason']) && !empty($_POST['edit_reason'])) {
        $_SESSION['edit_reason'] = $_POST['edit_reason'];
        $is_edit_mode = true;
        $show_readonly = false; // Switch to edit mode
        // Redirect to the same page with edit mode
        $redirect_url = "attendance_entry.php?class=" . urlencode($selected_class) . 
                       "&session=" . urlencode($selected_session) . 
                       "&date=" . urlencode($selected_date) . 
                       "&faculty=" . urlencode($selected_faculty) . 
                       "&edit_mode=1";
        header('Location: ' . $redirect_url);
        exit();
    } else {
        $error = 'Please provide a reason for modifying the attendance.';
        $show_readonly = true; // Stay in read-only mode if error
    }
}

// Check if we're in edit mode from URL
if (isset($_GET['edit_mode']) && $_GET['edit_mode'] == '1') {
    $is_edit_mode = true;
    $show_readonly = false;
}

// If attendance exists for a past date and not editing, show read-only
if (!empty($existing_attendance) && !$is_edit_mode && empty($_POST['preview']) && empty($_POST['final_submit'])) {
    $show_readonly = true;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['final_submit'])) {
    $class = $_POST['class'] ?? '';
    $session = $_POST['session'] ?? '';
    $date = $_POST['date'] ?? '';
    $selected_faculty = $_POST['faculty'] ?? '';
    $faculty_name = $selected_faculty;
    $students = $_POST['students'] ?? [];
    $all_students = $_POST['all_students'] ?? [];
    $edit_reason = $_POST['edit_reason'] ?? '';
    $is_modification = $_POST['is_modification'] ?? false;
    if (!$class || !$session || !$date || !$faculty_name) {
        $error = 'Please fill all fields.';
    } elseif (!array_key_exists($class, $classes)) {
        $error = 'Invalid class selection.';
    } elseif (isset($faculty_mapping[$class]) && !in_array($faculty_name, $faculty_mapping[$class])) {
        $error = 'Invalid faculty selection.';
    } elseif ($is_modification && empty($edit_reason)) {
        $error = 'Please provide a reason for modifying the attendance.';
    } else {
        // Check if attendance already exists for this date/session
        $existing_query = "SELECT COUNT(*) as count FROM `$class` WHERE attendance_date = '" . mysqli_real_escape_string($conn, $date) . "' AND session = '" . mysqli_real_escape_string($conn, $session) . "'";
        $existing_result = mysqli_query($conn, $existing_query);
        $existing_count = mysqli_fetch_assoc($existing_result)['count'];
        // If this is a modification, log the change with detailed tracking
        if ($is_modification && $existing_count > 0) {
            // Get existing attendance data for comparison
            $existing_data_query = "SELECT register_no, status FROM `$class` WHERE attendance_date = '" . mysqli_real_escape_string($conn, $date) . "' AND session = '" . mysqli_real_escape_string($conn, $session) . "'";
            $existing_data_result = mysqli_query($conn, $existing_data_query);
            $existing_data = [];
            while ($row = mysqli_fetch_assoc($existing_data_result)) {
                $existing_data[$row['register_no']] = $row['status'];
            }
            
            // Track changes for each student
            $changes = [];
            foreach ($all_students as $reg_no) {
                $old_status = $existing_data[$reg_no] ?? 0;
                $new_status = in_array($reg_no, $students) ? 1 : 0;
                
                if ($old_status != $new_status) {
                    $old_text = $old_status == 1 ? 'Present' : 'Absent';
                    $new_text = $new_status == 1 ? 'Present' : 'Absent';
                    $changes[] = "$reg_no: $old_text → $new_text";
                }
            }
            
            $changes_text = !empty($changes) ? implode(', ', $changes) : 'No status changes';
            
            // Check if attendance_modifications table exists
            $table_check = mysqli_query($conn, "SHOW TABLES LIKE 'attendance_modifications'");
            if (mysqli_num_rows($table_check) > 0) {
                // Check if changes_made column exists
                $column_check = mysqli_query($conn, "SHOW COLUMNS FROM attendance_modifications LIKE 'changes_made'");
                if (mysqli_num_rows($column_check) > 0) {
                    $log_query = "INSERT INTO attendance_modifications (table_name, attendance_date, session, faculty_name, modification_reason, changes_made, modified_at) 
                                 VALUES ('$class', '" . mysqli_real_escape_string($conn, $date) . "', '" . mysqli_real_escape_string($conn, $session) . "', 
                                        '" . mysqli_real_escape_string($conn, $faculty_name) . "', '" . mysqli_real_escape_string($conn, $edit_reason) . "', 
                                        '" . mysqli_real_escape_string($conn, $changes_text) . "', NOW())";
                } else {
                    // Fallback without changes_made column
                    $log_query = "INSERT INTO attendance_modifications (table_name, attendance_date, session, faculty_name, modification_reason, modified_at) 
                                 VALUES ('$class', '" . mysqli_real_escape_string($conn, $date) . "', '" . mysqli_real_escape_string($conn, $session) . "', 
                                        '" . mysqli_real_escape_string($conn, $faculty_name) . "', '" . mysqli_real_escape_string($conn, $edit_reason) . "', NOW())";
                }
                
                $log_result = mysqli_query($conn, $log_query);
                if (!$log_result) {
                    // Log the error but don't stop the process
                    error_log("Failed to log attendance modification: " . mysqli_error($conn));
                }
            } else {
                error_log("attendance_modifications table does not exist");
            }
        }
        // Save attendance
        $save_success = true;
        foreach ($all_students as $reg_no) {
            $is_present = in_array($reg_no, $students) ? 1 : 0;
            $date_esc = mysqli_real_escape_string($conn, $date);
            $session_esc = mysqli_real_escape_string($conn, $session);
            $reg_esc = mysqli_real_escape_string($conn, $reg_no);
            $faculty_esc = mysqli_real_escape_string($conn, $faculty_name);
            $sql = "INSERT INTO `$class` (attendance_date, session, register_no, status, faculty_name) VALUES ('$date_esc', '$session_esc', '$reg_esc', $is_present, '$faculty_esc') ON DUPLICATE KEY UPDATE status=$is_present, faculty_name='$faculty_esc'";
            $result = mysqli_query($conn, $sql);
            if (!$result) {
                $save_success = false;
                $error = 'Database error: ' . mysqli_error($conn);
                break;
            }
        }
        
        if ($save_success) {
            // Clear the edit reason from session after successful submission
            unset($_SESSION['edit_reason']);
            
            // Redirect back to attendance_entry.php with success message
            $redirect_url = "attendance_entry.php?success=1&class=" . urlencode($class) . 
                           "&session=" . urlencode($session) . 
                           "&date=" . urlencode($date) . 
                           "&faculty=" . urlencode($faculty_name);
            header('Location: ' . $redirect_url);
            exit();
        }
    }
}

// If preview, get absentees
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['preview']) && !$error) {
    $absentees = array_diff($students, $_POST['students'] ?? []);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include "./head.php"; ?>
    <title>Attendance Entry - SRKR Engineering College</title>
</head>
<body>
    <?php include "nav_top.php"; ?>
    <?php include "nav.php"; ?>
    <div class="page-title">
        <div class="container">
            <h2><i class="fas fa-clipboard-check"></i> Attendance Entry</h2>
            <p>Mark attendance for your assigned section</p>
        </div>
    </div>
    <div class="main-content">
        <div class="container">
                <?php if ($error): ?>
        <div class="alert alert-danger" style="border-radius: 10px; margin-bottom: 20px;">
            <i class="fas fa-exclamation-triangle"></i> <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
        <div class="alert alert-info" style="border-radius: 10px; margin-bottom: 20px;">
            <i class="fas fa-info-circle"></i> <strong>Debug Info:</strong>
            <ul class="mb-0 mt-2">
                <li>Edit Mode: <?php echo $is_edit_mode ? 'Yes' : 'No'; ?></li>
                <li>Show Readonly: <?php echo $show_readonly ? 'Yes' : 'No'; ?></li>
                <li>Existing Attendance Count: <?php echo count($existing_attendance); ?></li>
                <li>Session Edit Reason: <?php echo isset($_SESSION['edit_reason']) ? 'Set' : 'Not Set'; ?></li>
                <li>Selected Class: <?php echo htmlspecialchars($selected_class); ?></li>
                <li>Selected Date: <?php echo htmlspecialchars($selected_date); ?></li>
                <li>Selected Session: <?php echo htmlspecialchars($selected_session); ?></li>
            </ul>
        </div>
    <?php endif; ?>
            
            <?php if ($is_edit_mode): ?>
                <div class="alert alert-info" style="border-radius: 10px; margin-bottom: 20px;">
                    <i class="fas fa-info-circle"></i> <strong>Edit Mode:</strong> You are now in edit mode. You can modify the attendance below.
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success" style="border-radius: 10px; margin-bottom: 20px;">
                    <i class="fas fa-check-circle"></i> <strong>Success:</strong> Attendance has been successfully saved!
                </div>
            <?php endif; ?>
            
            <div class="text-end mb-4">
                <a href="attendance_entry.php?reset=1" class="btn btn-secondary me-2">
                    <i class="fas fa-refresh"></i> Reset Form
                </a>
                <a href="attendance_entry.php?debug=1&class=<?php echo urlencode($selected_class); ?>&session=<?php echo urlencode($selected_session); ?>&date=<?php echo urlencode($selected_date); ?>&faculty=<?php echo urlencode($selected_faculty); ?>" class="btn btn-info me-2">
                    <i class="fas fa-bug"></i> Debug
                </a>
                <a href="attendance_logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>

            <!-- Read-only view if attendance exists and not editing -->
            <?php if ($show_readonly): ?>
                <div class="card mb-4" style="border: none; box-shadow: 0 4px 16px rgba(7,101,147,0.1); border-radius: 15px;">
                    <div class="card-header" style="background: var(--light-blue); border-bottom: 1px solid #e3e6f0; border-radius: 15px 15px 0 0;">
                        <h5 class="mb-0" style="color: var(--primary-blue); font-weight: 600;">
                            <i class="fas fa-lock"></i> Attendance Already Submitted
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="alert alert-info" style="border-radius: 10px;">
                            <i class="fas fa-info-circle"></i> Attendance for <strong><?php echo htmlspecialchars($selected_date); ?></strong> (<?php echo htmlspecialchars($selected_session); ?>) has already been submitted.<br>
                            <span class="text-muted">If you need to make changes, click the <strong>Edit Attendance</strong> button below. You must provide a reason for any changes.</span>
                        </div>
                        <div class="mb-3">
                            <strong>Section:</strong> <?php echo $classes[$selected_class]; ?><br>
                            <strong>Session:</strong> <?php echo htmlspecialchars($selected_session); ?><br>
                            <strong>Date:</strong> <?php echo htmlspecialchars($selected_date); ?><br>
                            <strong>Faculty:</strong> <?php echo htmlspecialchars($assigned_faculty); ?><br>
                        </div>
                        <div class="mb-3">
                            <label class="form-label" style="color: var(--primary-blue); font-weight: 500;">
                                <i class="fas fa-users"></i> Attendance List
                            </label>
                            <div class="card" style="border: 1px solid #e3e6f0; border-radius: 10px; max-height: 400px; overflow-y: auto;">
                                <div class="card-body p-3" style="background: var(--light-blue);">
                                    <div class="row">
                                        <?php foreach ($students as $reg): ?>
                                            <div class="col-md-6 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" disabled <?php echo (isset($existing_attendance[$reg]) && $existing_attendance[$reg] == 1) ? 'checked' : ''; ?> style="border-radius: 5px;">
                                                    <label class="form-check-label" style="font-size: 0.9rem;">
                                                        <?php echo htmlspecialchars($reg); ?>
                                                        <span class="badge <?php echo $existing_attendance[$reg] == 1 ? 'bg-success' : 'bg-danger'; ?> ms-1" style="font-size: 0.7rem;">
                                                            <?php echo $existing_attendance[$reg] == 1 ? 'Present' : 'Absent'; ?>
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <form method="POST" id="editForm">
                            <input type="hidden" name="class" value="<?php echo htmlspecialchars($selected_class); ?>">
                            <input type="hidden" name="session" value="<?php echo htmlspecialchars($selected_session); ?>">
                            <input type="hidden" name="date" value="<?php echo htmlspecialchars($selected_date); ?>">
                            <input type="hidden" name="faculty" value="<?php echo htmlspecialchars($selected_faculty); ?>">
                            
                            <div class="mb-3">
                                <label for="edit_reason" class="form-label" style="color: var(--primary-blue); font-weight: 500;">
                                    <i class="fas fa-edit"></i> Reason for Modification
                                </label>
                                <textarea name="edit_reason" id="edit_reason" class="form-control" rows="3" placeholder="Please provide a reason for modifying the attendance..." required style="border-radius: 10px; padding: 12px 15px; border: 2px solid #e3e6f0;"><?php echo isset($_SESSION['edit_reason']) ? htmlspecialchars($_SESSION['edit_reason']) : ''; ?></textarea>
                                <small class="form-text text-muted">This reason will be logged and visible to HOD.</small>
                            </div>
                            
                            <button type="submit" name="edit_attendance" class="btn btn-warning" onclick="return validateReason()">
                                <i class="fas fa-edit"></i> Edit Attendance
                            </button>
                        </form>
                        
                        <script>
                        function validateReason() {
                            var reason = document.getElementById('edit_reason').value.trim();
                            if (reason === '') {
                                alert('Please provide a reason for modifying the attendance.');
                                document.getElementById('edit_reason').focus();
                                return false;
                            }
                            console.log('Reason provided:', reason);
                            return true;
                        }
                        
                        // Auto-focus on reason field when page loads
                        document.addEventListener('DOMContentLoaded', function() {
                            var reasonField = document.getElementById('edit_reason');
                            if (reasonField) {
                                reasonField.focus();
                            }
                        });
                        </script>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Editable form (edit mode or new entry) -->
            <?php if (!$show_readonly): ?>
                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['preview']) && !$error): ?>
                    <!-- Preview Section (same as before, with reason if editing) -->
                    <div class="card" style="border: none; box-shadow: 0 4px 16px rgba(7,101,147,0.1); border-radius: 15px;">
                        <div class="card-header" style="background: var(--light-blue); border-bottom: 1px solid #e3e6f0; border-radius: 15px 15px 0 0;">
                            <h5 class="mb-0" style="color: var(--primary-blue); font-weight: 600;">
                                <i class="fas fa-eye"></i> Preview Attendance
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST">
                                <input type="hidden" name="class" value="<?php echo htmlspecialchars($selected_class); ?>">
                                <input type="hidden" name="session" value="<?php echo htmlspecialchars($selected_session); ?>">
                                <input type="hidden" name="date" value="<?php echo htmlspecialchars($selected_date); ?>">
                                <input type="hidden" name="faculty_name" value="<?php echo htmlspecialchars($assigned_faculty); ?>">
                                <input type="hidden" name="faculty" value="<?php echo htmlspecialchars($selected_faculty); ?>">
                                <?php foreach ($students as $reg): ?>
                                    <input type="hidden" name="all_students[]" value="<?php echo htmlspecialchars($reg); ?>">
                                <?php endforeach; ?>
                                <?php foreach ($_POST['students'] ?? [] as $reg): ?>
                                    <input type="hidden" name="students[]" value="<?php echo htmlspecialchars($reg); ?>">
                                <?php endforeach; ?>
                                <?php if (!empty($existing_attendance) || $is_edit_mode): ?>
                                    <input type="hidden" name="is_modification" value="1">
                                    <div class="alert alert-warning" style="border-radius: 10px;">
                                        <i class="fas fa-exclamation-triangle"></i> <strong>Modification Alert:</strong> Attendance for this date and session already exists. You are modifying existing records.
                                    </div>
                                    <div class="mb-4">
                                        <label for="edit_reason" class="form-label" style="color: var(--primary-blue); font-weight: 500;">
                                            <i class="fas fa-edit"></i> Reason for Modification
                                        </label>
                                        <textarea name="edit_reason" id="edit_reason" class="form-control" rows="3" placeholder="Please provide a reason for modifying the attendance..." required style="border-radius: 10px; padding: 12px 15px; border: 2px solid #e3e6f0;"><?php echo isset($_SESSION['edit_reason']) ? htmlspecialchars($_SESSION['edit_reason']) : ''; ?></textarea>
                                        <small class="form-text text-muted">This reason will be logged and visible to HOD.</small>
                                    </div>
                                <?php endif; ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6 style="color: var(--primary-blue); font-weight: 600;">
                                            <i class="fas fa-info-circle"></i> Session Details
                                        </h6>
                                        <p><strong>Section:</strong> <?php echo $classes[$selected_class]; ?></p>
                                        <p><strong>Session:</strong> <?php echo htmlspecialchars($selected_session); ?></p>
                                        <p><strong>Date:</strong> <?php echo htmlspecialchars($selected_date); ?></p>
                                        <p><strong>Faculty:</strong> <?php echo htmlspecialchars($assigned_faculty); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h6 style="color: var(--primary-blue); font-weight: 600;">
                                            <i class="fas fa-users"></i> Absent Students
                                        </h6>
                                        <?php if (count($absentees) > 0): ?>
                                            <div class="alert alert-warning" style="border-radius: 10px;">
                                                <strong>Absent (<?php echo count($absentees); ?>):</strong><br>
                                                <?php echo implode(', ', $absentees); ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-success" style="border-radius: 10px;">
                                                <i class="fas fa-check-circle"></i> No absentees! All present.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="text-center mt-4">
                                    <button type="submit" name="final_submit" class="btn btn-success me-2">
                                        <i class="fas fa-check"></i> Confirm & Submit
                                    </button>
                                    <a href="attendance_entry.php" class="btn btn-secondary">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Attendance Entry Form -->
                    <div class="card" style="border: none; box-shadow: 0 4px 16px rgba(7,101,147,0.1); border-radius: 15px;">
                        <div class="card-header" style="background: var(--light-blue); border-bottom: 1px solid #e3e6f0; border-radius: 15px 15px 0 0;">
                            <h5 class="mb-0" style="color: var(--primary-blue); font-weight: 600;">
                                <i class="fas fa-clipboard-list"></i> Mark Attendance
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="class" class="form-label" style="color: var(--primary-blue); font-weight: 500;">
                                            <i class="fas fa-graduation-cap"></i> Section
                                        </label>
                                        <select name="class" id="class" class="form-control" required onchange="this.form.submit()" style="border-radius: 10px; padding: 10px 15px;">
                                            <?php foreach ($classes as $c_key => $c_label): ?>
                                                <option value="<?php echo $c_key; ?>" <?php if ($selected_class == $c_key) echo 'selected'; ?>><?php echo $c_label; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="session" class="form-label" style="color: var(--primary-blue); font-weight: 500;">
                                            <i class="fas fa-clock"></i> Session
                                        </label>
                                        <select name="session" id="session" class="form-control" required style="border-radius: 10px; padding: 10px 15px;">
                                            <option value="">Select Session</option>
                                            <option value="Forenoon" <?php if ($selected_session == 'Forenoon') echo 'selected'; ?>>Forenoon</option>
                                            <option value="Afternoon" <?php if ($selected_session == 'Afternoon') echo 'selected'; ?>>Afternoon</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="date" class="form-label" style="color: var(--primary-blue); font-weight: 500;">
                                            <i class="fas fa-calendar"></i> Date
                                        </label>
                                        <input type="date" name="date" id="date" class="form-control" value="<?php echo htmlspecialchars($selected_date); ?>" required style="border-radius: 10px; padding: 10px 15px;">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="faculty" class="form-label" style="color: var(--primary-blue); font-weight: 500;">
                                            <i class="fas fa-user-tie"></i> Faculty Name
                                        </label>
                                        <select name="faculty" id="faculty" class="form-control" required onchange="this.form.submit()" style="border-radius: 10px; padding: 10px 15px;">
                                            <option value="">Select Faculty</option>
                                            <?php foreach ($faculty_mapping[$selected_class] as $faculty): ?>
                                                <option value="<?php echo htmlspecialchars($faculty); ?>" <?php if ($selected_faculty == $faculty) echo 'selected'; ?>><?php echo htmlspecialchars($faculty); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="form-text text-muted">
                                            <?php if (count($faculty_mapping[$selected_class]) > 1): ?>
                                                Multiple faculty assigned to this section
                                            <?php else: ?>
                                                Single faculty assigned to this section
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                                <?php if (!empty($existing_attendance) && $is_edit_mode): ?>
                                    <div class="alert alert-warning" style="border-radius: 10px;">
                                        <i class="fas fa-exclamation-triangle"></i> <strong>Modification Alert:</strong> Attendance for this date and session already exists. You are modifying existing records.
                                    </div>
                                    <div class="mb-4">
                                        <label for="edit_reason" class="form-label" style="color: var(--primary-blue); font-weight: 500;">
                                            <i class="fas fa-edit"></i> Reason for Modification
                                        </label>
                                        <textarea name="edit_reason" id="edit_reason" class="form-control" rows="3" placeholder="Please provide a reason for modifying the attendance..." required style="border-radius: 10px; padding: 12px 15px; border: 2px solid #e3e6f0;"><?php echo isset($_SESSION['edit_reason']) ? htmlspecialchars($_SESSION['edit_reason']) : ''; ?></textarea>
                                        <small class="form-text text-muted">This reason will be logged and visible to HOD.</small>
                                    </div>
                                    <input type="hidden" name="is_modification" value="1">
                                <?php endif; ?>
                                <?php if (!empty($students)): ?>
                                    <div class="mb-4">
                                        <label class="form-label" style="color: var(--primary-blue); font-weight: 500;">
                                            <i class="fas fa-users"></i> Student List (<?php echo count($students); ?> students)
                                        </label>
                                        <div class="form-check mb-3 p-3" style="background: #f8f9fa; border-radius: 10px; border: 2px solid #e3e6f0;">
                                            <input class="form-check-input" type="checkbox" id="checkAll" checked style="border-radius: 5px; width: 20px; height: 20px; margin-top: 0;">
                                            <label class="form-check-label" for="checkAll" style="font-weight: 600; color: var(--primary-blue); font-size: 1.1rem;">
                                                <i class="fas fa-check-square"></i> Check/Uncheck All Students
                                            </label>
                                        </div>
                                        <div class="card" style="border: 2px solid #e3e6f0; border-radius: 15px; max-height: 500px; overflow-y: auto; box-shadow: 0 4px 16px rgba(7,101,147,0.1);">
                                            <div class="card-header" style="background: var(--primary-blue); color: white; border-radius: 13px 13px 0 0; padding: 15px;">
                                                <h6 class="mb-0"><i class="fas fa-users"></i> Student Attendance List</h6>
                                            </div>
                                            <div class="card-body p-4" style="background: white;">
                                                <div class="row">
                                                    <?php foreach ($students as $reg): ?>
                                                        <div class="col-lg-6 col-md-6 mb-3">
                                                            <div class="student-checkbox-item" style="background: #f8f9fa; border: 2px solid #e3e6f0; border-radius: 10px; padding: 12px; transition: all 0.3s ease; cursor: pointer;" onclick="toggleCheckbox('student_<?php echo htmlspecialchars($reg); ?>')">
                                                                <div class="d-flex align-items-center">
                                                                    <div class="me-3">
                                                                        <input class="form-check-input student-checkbox" type="checkbox" name="students[]" value="<?php echo htmlspecialchars($reg); ?>" id="student_<?php echo htmlspecialchars($reg); ?>" 
                                                                               <?php echo (isset($existing_attendance[$reg]) && $existing_attendance[$reg] == 1) ? 'checked' : (empty($existing_attendance) ? 'checked' : ''); ?> 
                                                                               style="border-radius: 5px; width: 20px; height: 20px; margin: 0;">
                                                                    </div>
                                                                    <div class="flex-grow-1">
                                                                        <label class="form-check-label mb-0" for="student_<?php echo htmlspecialchars($reg); ?>" style="font-weight: 500; font-size: 1rem; color: var(--primary-blue); cursor: pointer;">
                                                                            <?php echo htmlspecialchars($reg); ?>
                                                                        </label>
                                                                        <?php if (isset($existing_attendance[$reg])): ?>
                                                                            <div class="mt-1">
                                                                                <span class="badge <?php echo $existing_attendance[$reg] == 1 ? 'bg-success' : 'bg-danger'; ?>" style="font-size: 0.8rem; padding: 5px 10px;">
                                                                                    <i class="fas <?php echo $existing_attendance[$reg] == 1 ? 'fa-check-circle' : 'fa-times-circle'; ?>"></i>
                                                                                    <?php echo $existing_attendance[$reg] == 1 ? 'Present' : 'Absent'; ?>
                                                                                </span>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php foreach ($students as $reg): ?>
                                        <input type="hidden" name="all_students[]" value="<?php echo htmlspecialchars($reg); ?>">
                                    <?php endforeach; ?>
                                    <div class="text-center">
                                        <button type="submit" name="preview" class="btn btn-primary" style="border-radius: 10px; padding: 12px 30px; font-weight: 500;">
                                            <i class="fas fa-eye"></i> Preview Attendance
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-info" style="border-radius: 10px;">
                                        <i class="fas fa-info-circle"></i> Please select a section to view the student list.
                                    </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php include "footer.php"; ?>
    <script>
        var checkAll = document.getElementById('checkAll');
        if (checkAll) {
            checkAll.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('.student-checkbox');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateCheckboxStyles();
            });
        }
        
        function toggleCheckbox(checkboxId) {
            const checkbox = document.getElementById(checkboxId);
            checkbox.checked = !checkbox.checked;
            updateCheckboxStyles();
        }
        
        function updateCheckboxStyles() {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(checkbox => {
                const item = checkbox.closest('.student-checkbox-item');
                if (checkbox.checked) {
                    item.style.background = '#d4edda';
                    item.style.borderColor = '#28a745';
                } else {
                    item.style.background = '#f8d7da';
                    item.style.borderColor = '#dc3545';
                }
            });
        }
        
        // Initialize checkbox styles on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateCheckboxStyles();
        });
    </script>
    <style>
        .form-control:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 0.2rem rgba(7,101,147,0.25);
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .student-checkbox:checked {
            background-color: #28a745;
            border-color: #28a745;
        }
        
        .student-checkbox:not(:checked) {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        
        .student-checkbox-item:hover {
            background: #d4edda !important;
            border-color: #28a745 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(40,167,69,0.15);
        }
        
        .student-checkbox-item {
            transition: all 0.3s ease;
        }
        
        .form-check-input:focus {
            box-shadow: 0 0 0 0.2rem rgba(7,101,147,0.25);
            border-color: var(--primary-blue);
        }
        
        /* Mobile Responsive Improvements for Attendance Entry */
        @media (max-width: 768px) {
            .card-body {
                padding: 20px 15px;
            }
            
            .form-control {
                font-size: 16px;
                padding: 12px 15px;
            }
            
            .btn {
                padding: 12px 20px;
                font-size: 14px;
                width: 100%;
                margin-bottom: 10px;
            }
            
            .btn + .btn {
                margin-left: 0;
            }
            
            .text-end {
                text-align: center !important;
            }
            
            .alert {
                padding: 15px;
                margin-bottom: 20px;
            }
            
            .form-check {
                margin-bottom: 8px;
            }
            
            .form-check-input {
                margin-top: 0.2rem;
            }
            
            .form-check-label {
                font-size: 0.9rem;
                line-height: 1.4;
            }
            
            .badge {
                font-size: 0.7rem;
                padding: 4px 8px;
            }
            
            .card {
                margin-bottom: 20px;
            }
            
            .row {
                margin-left: -10px;
                margin-right: -10px;
            }
            
            .col-md-6 {
                padding-left: 10px;
                padding-right: 10px;
            }
        }
        
        @media (max-width: 576px) {
            .card-body {
                padding: 15px 10px;
            }
            
            .form-control {
                padding: 10px 12px;
                font-size: 16px;
            }
            
            .btn {
                padding: 10px 16px;
                font-size: 13px;
            }
            
            .form-check-label {
                font-size: 0.85rem;
            }
            
            .badge {
                font-size: 0.65rem;
                padding: 3px 6px;
            }
            
            .alert {
                padding: 12px;
                font-size: 0.9rem;
            }
            
            .page-title h2 {
                font-size: 20px;
            }
            
            .page-title p {
                font-size: 14px;
            }
        }
        
        /* Landscape orientation fixes */
        @media (max-width: 768px) and (orientation: landscape) {
            .main-content {
                padding: 20px 0;
            }
            
            .card-body {
                padding: 15px 20px;
            }
        }
        
        /* Form validation improvements */
        .form-control.is-invalid {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        
        .form-control.is-valid {
            border-color: #28a745;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }
        
        /* Accessibility improvements */
        .form-check-input:focus {
            box-shadow: 0 0 0 0.2rem rgba(7,101,147,0.25);
        }
        
        /* Touch target improvements */
        .form-check-input,
        .btn {
            min-height: 44px;
        }
        
        @media (max-width: 768px) {
            .form-check-input {
                min-height: 20px;
                min-width: 20px;
            }
        }
    </style>
</body>
</html>