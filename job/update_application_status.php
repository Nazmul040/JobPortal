<?php
require_once 'includes/functions.php';

// Check if user is logged in and is a recruiter
if (!isLoggedIn() || !isRecruiter()) {
    setAlert('error', 'You must be logged in as a recruiter to update application status.');
    redirect('login.php');
}

// Validate application ID and status
if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['status'])) {
    setAlert('error', 'Invalid request.');
    redirect('manage_applications.php');
}

$application_id = (int)$_GET['id'];
$status = $_GET['status'];
$allowed_statuses = ['pending', 'reviewed', 'accepted', 'rejected'];

if (!in_array($status, $allowed_statuses)) {
    setAlert('error', 'Invalid status value.');
    redirect('manage_applications.php');
}

// Check if the recruiter owns the job for this application
$sql = "SELECT a.id
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        WHERE a.id = ? AND j.recruiter_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $application_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    setAlert('error', 'You do not have permission to update this application.');
    redirect('manage_applications.php');
}

// Update the application status
$update_sql = "UPDATE applications SET status = ? WHERE id = ?";
$update_stmt = mysqli_prepare($conn, $update_sql);
mysqli_stmt_bind_param($update_stmt, "si", $status, $application_id);

if (mysqli_stmt_execute($update_stmt)) {
    setAlert('success', 'Application status updated successfully.');
} else {
    setAlert('error', 'Failed to update application status.');
}

redirect('manage_applications.php');
?>