<?php
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Check if job ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setAlert('error', 'Invalid job ID.');
    redirect('jobs.php');
}

$job_id = (int)$_GET['id'];

// Get job details
$sql = "SELECT j.*, c.company_name, c.logo_path, c.website, u.email as recruiter_email 
        FROM jobs j 
        JOIN company_profiles c ON j.recruiter_id = c.user_id 
        JOIN users u ON j.recruiter_id = u.id
        WHERE j.id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $job_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    setAlert('error', 'Job not found.');
    redirect('jobs.php');
}

$job = mysqli_fetch_assoc($result);

// Check if user has already applied for this job
$has_applied = false;
if (isStudent()) {
    $sql = "SELECT id FROM applications WHERE job_id = ? AND student_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $job_id, $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $has_applied = mysqli_num_rows($result) > 0;
    
    if ($has_applied) {
        $application = mysqli_fetch_assoc($result);
        $application_id = $application['id'];
    }
}

// Check if user has saved this job
$is_saved = false;
if (isStudent()) {
    // Check if saved_jobs table exists
    $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'saved_jobs'");
    if (mysqli_num_rows($check_table) > 0) {
        $sql = "SELECT id FROM saved_jobs WHERE job_id = ? AND student_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $job_id, $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $is_saved = mysqli_num_rows($result) > 0;
    }
}

// Get similar jobs
$similar_jobs = [];
$keywords = explode(' ', $job['title'] . ' ' . $job['location'] . ' ' . $job['job_type']);
$keywords = array_filter($keywords, function($word) {
    return strlen($word) > 3; // Only use words longer than 3 characters
});

if (!empty($keywords)) {
    $conditions = [];
    $params = [];
    $types = "";
    
    foreach ($keywords as $keyword) {
        $keyword = trim($keyword);
        if (!empty($keyword)) {
            $conditions[] = "j.title LIKE ? OR j.description LIKE ? OR j.location LIKE ?";
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
            $params[] = "%$keyword%";
            $types .= "sss";
        }
    }
    
    if (!empty($conditions)) {
        $sql = "SELECT j.id, j.title, j.location, j.job_type, j.posted_date, c.company_name, c.logo_path 
                FROM jobs j 
                JOIN company_profiles c ON j.recruiter_id = c.user_id 
                WHERE j.id != ? AND j.status = 'open' AND (" . implode(' OR ', $conditions) . ")
                ORDER BY j.posted_date DESC 
                LIMIT 3";
        $stmt = mysqli_prepare($conn, $sql);
        
        // Add job_id as first parameter
        array_unshift($params, $job_id);
        $types = "i" . $types;
        
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $similar_jobs = mysqli_fetch_all($result, MYSQLI_ASSOC);
    }
}

// Handle job application submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply']) && isStudent()) {
    // Check if already applied
    if ($has_applied) {
        setAlert('error', 'You have already applied for this job.');
    } else {
        $cover_letter = isset($_POST['cover_letter']) ? trim($_POST['cover_letter']) : '';
        
        // Get student profile to check if resume exists
        $sql = "SELECT resume_path FROM student_profiles WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $profile = mysqli_fetch_assoc($result);
        
        if (empty($profile['resume_path'])) {
            setAlert('error', 'Please upload your resume in your profile before applying.');
        } else {
            // Insert application
            $sql = "INSERT INTO applications (job_id, student_id, cover_letter, application_date, status) VALUES (?, ?, ?, NOW(), 'pending')";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "iis", $job_id, $_SESSION['user_id'], $cover_letter);
            
            if (mysqli_stmt_execute($stmt)) {
                $application_id = mysqli_insert_id($conn);
                
                // Process the application
                // 1. Get student information for notification
                $sql = "SELECT u.username, u.email, s.* FROM users u 
                        JOIN student_profiles s ON u.id = s.user_id 
                        WHERE u.id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
                mysqli_stmt_execute($stmt);
                $student_info = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                
                // 2. Update application count for this job
                // $sql = "UPDATE jobs SET applications_count = applications_count + 1 WHERE id = ?";
                // $stmt = mysqli_prepare($conn, $sql);
                // mysqli_stmt_bind_param($stmt, "i", $job_id);
                // mysqli_stmt_execute($stmt);
                
                // 3. Log the application activity
                // $activity_log = "INSERT INTO activity_logs (user_id, activity_type, related_id, description, activity_date) 
                //                 VALUES (?, 'application', ?, ?, NOW())";
                // $stmt = mysqli_prepare($conn, $activity_log);
                // $description = "Applied for job: " . $job['title'] . " at " . $job['company_name'];
                // mysqli_stmt_bind_param($stmt, "iis", $_SESSION['user_id'], $job_id, $description);
                // mysqli_stmt_execute($stmt);
                
                // 4. Send email notification to recruiter
                // $email_sent = false;
                // if (!empty($job['recruiter_email'])) {
                //     $to = $job['recruiter_email'];
                //     $subject = "New Application for " . $job['title'];
                //     $message = "Hello,\n\nA new application has been submitted for the position of " . $job['title'] . ".\n\n";
                //     $message .= "Applicant: " . (isset($student_info['username']) ? $student_info['username'] : 'A candidate') . "\n";
                //     $message .= "Application Date: " . date('Y-m-d H:i:s') . "\n\n";
                //     $message .= "Please log in to your account to review this application.\n\n";
                //     $message .= "Regards,\nJob Portal Team";
                //     $headers = "From: noreply@jobportal.com";
                    
                //     // Try to send email
                //     $email_sent = @mail($to, $subject, $message, $headers);
                    
                //     if (!$email_sent) {
                //         // Log email failure but don't stop the application process
                //         setAlert('info', 'Your application was submitted successfully, but there was an issue sending the notification email to the recruiter.');
                //     }
                // }
                
                setAlert('success', 'Your application has been submitted successfully.');
                redirect('my_applications.php');
            } else {
                setAlert('error', 'Failed to submit application. Please try again.');
            }
        }
    }
}

// Handle save/unsave job
if (isset($_POST['toggle_save']) && isStudent()) {
    // Check if saved_jobs table exists
    $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'saved_jobs'");
    if (mysqli_num_rows($check_table) == 0) {
        // Create saved_jobs table if it doesn't exist
        $create_table = "CREATE TABLE saved_jobs (
            id INT(11) NOT NULL AUTO_INCREMENT,
            job_id INT(11) NOT NULL,
            student_id INT(11) NOT NULL,
            saved_date DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY job_student (job_id, student_id)
        )";
        mysqli_query($conn, $create_table);
    }
    
    if ($is_saved) {
        // Unsave job
        $sql = "DELETE FROM saved_jobs WHERE job_id = ? AND student_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $job_id, $_SESSION['user_id']);
        
        if (mysqli_stmt_execute($stmt)) {
            $is_saved = false;
            setAlert('success', 'Job removed from saved jobs.');
        } else {
            setAlert('error', 'Failed to remove job from saved jobs.');
        }
    } else {
        // Save job
        $sql = "INSERT INTO saved_jobs (job_id, student_id, saved_date) VALUES (?, ?, NOW())";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ii", $job_id, $_SESSION['user_id']);
        
        if (mysqli_stmt_execute($stmt)) {
            $is_saved = true;
            setAlert('success', 'Job saved successfully.');
        } else {
            setAlert('error', 'Failed to save job.');
        }
    }
}

// Include header
include 'includes/header.php';
?>

<div class="bg-gray-50 py-8 min-h-screen">
    <div class="container mx-auto px-4">
        <!-- Back button -->
        <div class="mb-6">
            <a href="javascript:history.back()" class="inline-flex items-center text-blue-600 hover:text-blue-800 transition duration-300">
                <i class="fas fa-arrow-left mr-2"></i> Back to Jobs
            </a>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main job details -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                    <!-- Job header -->
                    <div class="p-6 border-b border-gray-100">
                        <div class="flex flex-col md:flex-row md:items-start gap-6">
                            <!-- Company logo -->
                            <div class="flex-shrink-0">
                                <?php if (!empty($job['logo_path'])): ?>
                                    <img src="<?php echo $job['logo_path']; ?>" alt="<?php echo htmlspecialchars($job['company_name']); ?>" class="w-20 h-20 object-contain rounded-lg border border-gray-200">
                                <?php else: ?>
                                    <div class="w-20 h-20 bg-blue-100 rounded-lg flex items-center justify-center border border-blue-200">
                                        <i class="fas fa-building text-blue-500 text-3xl"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Job title and company -->
                            <div class="flex-grow">
                                <h1 class="text-2xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($job['title']); ?></h1>
                                <p class="text-lg text-gray-600 mb-4"><?php echo htmlspecialchars($job['company_name']); ?></p>
                                
                                <div class="flex flex-wrap gap-2 mb-4">
                                    <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm flex items-center">
                                        <i class="fas fa-map-marker-alt mr-1 text-blue-500"></i> <?php echo htmlspecialchars($job['location']); ?>
                                    </span>
                                    <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm flex items-center">
                                        <i class="fas fa-briefcase mr-1 text-blue-500"></i> <?php echo htmlspecialchars(ucfirst($job['job_type'])); ?>
                                    </span>
                                    <?php if (!empty($job['salary'])): ?>
                                    <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm flex items-center">
                                        <i class="fas fa-money-bill-wave mr-1 text-blue-500"></i> <?php echo htmlspecialchars($job['salary']); ?>
                                    </span>
                                    <?php endif; ?>
                                    <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm flex items-center">
                                        <i class="fas fa-calendar-alt mr-1 text-blue-500"></i> Posted: <?php echo date('M d, Y', strtotime($job['posted_date'])); ?>
                                    </span>
                                </div>
                                
                                <?php if (isStudent()): ?>
                                <div class="flex flex-wrap gap-3">
                                    <?php if ($has_applied): ?>
                                        <a href="application_details.php?id=<?php echo $application_id; ?>" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm transition duration-300 flex items-center">
                                            <i class="fas fa-check-circle mr-2"></i> Applied
                                        </a>
                                    <?php else: ?>
                                        <a href="#apply-section" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition duration-300 flex items-center">
                                            <i class="fas fa-paper-plane mr-2"></i> Apply Now
                                        </a>
                                    <?php endif; ?>
                                    
                                    <form method="post" class="inline">
                                        <button type="submit" name="toggle_save" class="<?php echo $is_saved ? 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> px-4 py-2 rounded-lg text-sm transition duration-300 flex items-center">
                                            <i class="<?php echo $is_saved ? 'fas' : 'far'; ?> fa-bookmark mr-2"></i> <?php echo $is_saved ? 'Saved' : 'Save Job'; ?>
                                        </button>
                                    </form>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Job description -->
                    <div class="p-6">
                        <div class="mb-8">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4">Job Description</h2>
                            <div class="prose max-w-none text-gray-700">
                                <?php echo nl2br(htmlspecialchars($job['description'])); ?>
                            </div>
                        </div>
                        
                        <div class="mb-8">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4">Requirements</h2>
                            <div class="prose max-w-none text-gray-700">
                                <?php echo nl2br(htmlspecialchars($job['requirements'])); ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($job['benefits'])): ?>
                        <div class="mb-8">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4">Benefits</h2>
                            <div class="prose max-w-none text-gray-700">
                                <?php echo nl2br(htmlspecialchars($job['benefits'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isStudent() && !$has_applied): ?>
                        <div id="apply-section" class="mt-8 pt-8 border-t border-gray-100">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4">Apply for this Position</h2>
                            <form method="post" action="">
                                <div class="mb-4">
                                    <label for="cover_letter" class="block text-gray-700 font-medium mb-2">Cover Letter</label>
                                    <textarea id="cover_letter" name="cover_letter" rows="6" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500" placeholder="Introduce yourself and explain why you're a good fit for this position..."><?php echo isset($_POST['cover_letter']) ? htmlspecialchars($_POST['cover_letter']) : ''; ?></textarea>
                                    <p class="text-gray-500 text-sm mt-1">Your resume will be automatically attached from your profile.</p>
                                </div>
                                <button type="submit" name="apply" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition duration-300 flex items-center">
                                    <i class="fas fa-paper-plane mr-2"></i> Submit Application
                                </button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Similar jobs -->
                <?php if (!empty($similar_jobs)): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b">
                        <h2 class="text-xl font-semibold text-gray-800">Similar Jobs</h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-6">
                            <?php foreach ($similar_jobs as $similar_job): ?>
                                <div class="flex flex-col md:flex-row gap-4 pb-6 border-b border-gray-100 last:border-b-0 last:pb-0">
                                    <div class="flex-shrink-0">
                                        <?php if (!empty($similar_job['logo_path'])): ?>
                                            <img src="<?php echo $similar_job['logo_path']; ?>" alt="Company Logo" class="w-12 h-12 object-contain rounded-lg border border-gray-200">
                                        <?php else: ?>
                                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center border border-blue-200">
                                                <i class="fas fa-building text-blue-500"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-grow">
                                        <h3 class="font-semibold text-gray-800">
                                            <a href="job_details.php?id=<?php echo $similar_job['id']; ?>" class="hover:text-blue-600 transition duration-300"><?php echo htmlspecialchars($similar_job['title']); ?></a>
                                        </h3>
                                        <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($similar_job['company_name']); ?></p>
                                        <div class="flex flex-wrap gap-2 mt-2">
                                            <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded-full text-xs flex items-center">
                                                <i class="fas fa-map-marker-alt mr-1 text-blue-500"></i> <?php echo htmlspecialchars($similar_job['location']); ?>
                                            </span>
                                            <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded-full text-xs flex items-center">
                                                <i class="fas fa-briefcase mr-1 text-blue-500"></i> <?php echo htmlspecialchars(ucfirst($similar_job['job_type'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <a href="job_details.php?id=<?php echo $similar_job['id']; ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-lg text-sm transition duration-300 flex items-center">
                                            View
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Company information -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b">
                        <h2 class="text-xl font-semibold text-gray-800">Company Information</h2>
                    </div>
                    <div class="p-6">
                        <div class="flex flex-col items-center mb-6">
                            <?php if (!empty($job['logo_path'])): ?>
                                <img src="<?php echo $job['logo_path']; ?>" alt="<?php echo htmlspecialchars($job['company_name']); ?>" class="w-24 h-24 object-contain rounded-lg border border-gray-200 mb-4">
                            <?php else: ?>
                                <div class="w-24 h-24 bg-blue-100 rounded-lg flex items-center justify-center border border-blue-200 mb-4">
                                    <i class="fas fa-building text-blue-500 text-3xl"></i>
                                </div>
                            <?php endif; ?>
                            <h3 class="text-lg font-semibold text-gray-800 text-center"><?php echo htmlspecialchars($job['company_name']); ?></h3>
                        </div>
                        
                        <!-- Removed the about section since the column doesn't exist in the database -->
                        
                        <div class="space-y-3">
                            <?php if (!empty($job['website'])): ?>
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-globe text-blue-600"></i>
                                </div>
                                <a href="<?php echo htmlspecialchars($job['website']); ?>" target="_blank" class="text-blue-600 hover:text-blue-800 transition duration-300"><?php echo htmlspecialchars($job['website']); ?></a>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($job['recruiter_email'])): ?>
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-envelope text-blue-600"></i>
                                </div>
                                <a href="mailto:<?php echo htmlspecialchars($job['recruiter_email']); ?>" class="text-blue-600 hover:text-blue-800 transition duration-300"><?php echo htmlspecialchars($job['recruiter_email']); ?></a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Job details summary -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b">
                        <h2 class="text-xl font-semibold text-gray-800">Job Details</h2>
                    </div>
                    <div class="p-6">
                        <ul class="space-y-4">
                            <li class="flex items-start">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mr-3 mt-0.5">
                                    <i class="fas fa-map-marker-alt text-blue-600"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-700">Location</h4>
                                    <p class="text-gray-600"><?php echo htmlspecialchars($job['location']); ?></p>
                                </div>
                            </li>
                            
                            <li class="flex items-start">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mr-3 mt-0.5">
                                    <i class="fas fa-briefcase text-blue-600"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-700">Job Type</h4>
                                    <p class="text-gray-600"><?php echo htmlspecialchars(ucfirst($job['job_type'])); ?></p>
                                </div>
                            </li>
                            
                            <?php if (!empty($job['salary'])): ?>
                            <li class="flex items-start">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mr-3 mt-0.5">
                                    <i class="fas fa-money-bill-wave text-blue-600"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-700">Salary</h4>
                                    <p class="text-gray-600"><?php echo htmlspecialchars($job['salary']); ?></p>
                                </div>
                            </li>
                            <?php endif; ?>
                            
                            <li class="flex items-start">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mr-3 mt-0.5">
                                    <i class="fas fa-calendar-alt text-blue-600"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-700">Posted Date</h4>
                                    <p class="text-gray-600"><?php echo date('F d, Y', strtotime($job['posted_date'])); ?></p>
                                </div>
                            </li>
                            
                            <?php if (!empty($job['deadline'])): ?>
                            <li class="flex items-start">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mr-3 mt-0.5">
                                    <i class="fas fa-hourglass-end text-blue-600"></i>
                                </div>
                                <div>
                                    <h4 class="font-semibold text-gray-700">Application Deadline</h4>
                                    <p class="text-gray-600"><?php echo date('F d, Y', strtotime($job['deadline'])); ?></p>
                                </div>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
                
                <!-- Share job -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b">
                        <h2 class="text-xl font-semibold text-gray-800">Share This Job</h2>
                    </div>
                    <div class="p-6">
                        <div class="flex justify-center space-x-4">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center hover:bg-blue-700 transition duration-300">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>&text=<?php echo urlencode('Check out this job: ' . $job['title'] . ' at ' . $job['company_name']); ?>" target="_blank" class="w-10 h-10 rounded-full bg-blue-400 text-white flex items-center justify-center hover:bg-blue-500 transition duration-300">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" target="_blank" class="w-10 h-10 rounded-full bg-blue-800 text-white flex items-center justify-center hover:bg-blue-900 transition duration-300">
                                <i class="fab fa-linkedin-in"></i>
                            </a>
                            <a href="mailto:?subject=<?php echo urlencode('Job Opportunity: ' . $job['title'] . ' at ' . $job['company_name']); ?>&body=<?php echo urlencode('Check out this job posting: http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']); ?>" class="w-10 h-10 rounded-full bg-red-500 text-white flex items-center justify-center hover:bg-red-600 transition duration-300">
                                <i class="fas fa-envelope"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>