<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
$conn = require_once 'config.php';

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is a student
function isStudent() {
    return isLoggedIn() && $_SESSION['role'] === 'student';
}

// Function to check if user is a recruiter
function isRecruiter() {
    return isLoggedIn() && $_SESSION['role'] === 'recruiter';
}

// Function to redirect to a specific page
function redirect($page) {
    header("Location: $page");
    exit;
}

// Function to sanitize input data
function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($data)));
}

// Function to upload file
function uploadFile($file, $targetDir, $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']) {
    // Check if file was uploaded without errors
    if ($file['error'] == 0) {
        $fileName = basename($file['name']);
        $targetFilePath = $targetDir . $fileName;
        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
        
        // Check if file type is allowed
        if (in_array(strtolower($fileType), $allowedTypes)) {
            // Generate unique filename to prevent overwriting
            $uniqueName = uniqid() . '.' . $fileType;
            $targetFilePath = $targetDir . $uniqueName;
            
            // Upload file to server
            if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
                return $targetFilePath;
            }
        }
    }
    return false;
}

// Function to get user data
function getUserData($userId) {
    global $conn;
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

// Function to get student profile
function getStudentProfile($userId) {
    global $conn;
    $sql = "SELECT * FROM student_profiles WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

// Function to get company profile
function getCompanyProfile($userId) {
    global $conn;
    $sql = "SELECT * FROM company_profiles WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    return mysqli_fetch_assoc($result);
}

/**
 * Set alert message in session
 * 
 * @param string $type Alert type (success, error, info, warning)
 * @param string $message Alert message
 * @return void
 */
function setAlert($type, $message) {
    if (!isset($_SESSION['alerts'])) {
        $_SESSION['alerts'] = [];
    }
    
    $_SESSION['alerts'][] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear all alert messages
 * 
 * @return array Alert messages
 */
function getAlerts() {
    $alerts = isset($_SESSION['alerts']) ? $_SESSION['alerts'] : [];
    $_SESSION['alerts'] = [];
    return $alerts;
}