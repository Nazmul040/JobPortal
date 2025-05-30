<?php
// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'job_portal');

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD);

// Check connection
if (!$conn) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

// Create database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS " . DB_NAME;
if (mysqli_query($conn, $sql)) {
    // Select the database
    mysqli_select_db($conn, DB_NAME);
} else {
    echo "Error creating database: " . mysqli_error($conn);
}

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('student', 'recruiter') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
mysqli_query($conn, $sql);

// Create student_profiles table
$sql = "CREATE TABLE IF NOT EXISTS student_profiles (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    education TEXT,
    skills TEXT,
    experience TEXT,
    resume_path VARCHAR(255),
    profile_pic VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
mysqli_query($conn, $sql);

// Create company_profiles table
$sql = "CREATE TABLE IF NOT EXISTS company_profiles (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    company_name VARCHAR(100) NOT NULL,
    company_description TEXT,
    industry VARCHAR(100),
    website VARCHAR(255),
    location VARCHAR(100),
    logo_path VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)";
mysqli_query($conn, $sql);

// Create jobs table
$sql = "CREATE TABLE IF NOT EXISTS jobs (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    recruiter_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    requirements TEXT,
    location VARCHAR(100),
    job_type ENUM('full-time', 'part-time', 'contract', 'internship') NOT NULL,
    salary VARCHAR(50),
    posted_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deadline DATE,
    status ENUM('open', 'closed') DEFAULT 'open',
    FOREIGN KEY (recruiter_id) REFERENCES users(id) ON DELETE CASCADE
)";
mysqli_query($conn, $sql);

// Create applications table
$sql = "CREATE TABLE IF NOT EXISTS applications (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    job_id INT NOT NULL,
    student_id INT NOT NULL,
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'reviewed', 'accepted', 'rejected') DEFAULT 'pending',
    cover_letter TEXT,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
)";
mysqli_query($conn, $sql);

// Return the connection
return $conn;
?>