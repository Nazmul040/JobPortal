<?php
require_once 'includes/functions.php';

// Check if user is logged in and is a recruiter
if (!isLoggedIn() || !isRecruiter()) {
    setAlert('error', 'You must be logged in as a recruiter to edit jobs.');
    redirect('login.php');
}

// Check if job ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setAlert('error', 'Invalid job ID.');
    redirect('manage_jobs.php');
}

$job_id = (int)$_GET['id'];

// Check for update success message
if (isset($_GET['updated']) && $_GET['updated'] == 'true') {
    setAlert('success', 'Job updated successfully!');
}

// Get job details and verify ownership
$sql = "SELECT * FROM jobs WHERE id = ? AND recruiter_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $job_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    setAlert('error', 'Job not found or you do not have permission to edit it.');
    redirect('manage_jobs.php');
}

$job = mysqli_fetch_assoc($result);

// Get company profile
$sql = "SELECT * FROM company_profiles WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$company = mysqli_fetch_assoc($result);

$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $job['title'] = sanitize($_POST['title']);
    $job['description'] = sanitize($_POST['description']);
    $job['requirements'] = sanitize($_POST['requirements']);
    $job['responsibilities'] = sanitize($_POST['responsibilities']);
    $job['location'] = sanitize($_POST['location']);
    $job['job_type'] = sanitize($_POST['job_type']);
    $job['experience_level'] = sanitize($_POST['experience_level']);
    $job['education_level'] = sanitize($_POST['education_level']);
    $job['salary'] = sanitize($_POST['salary']);
    $job['application_deadline'] = sanitize($_POST['application_deadline']);
    $job['skills'] = sanitize($_POST['skills']);
    $job['status'] = sanitize($_POST['status']);
    
    // Validate required fields
    if (empty($job['title'])) {
        $errors['title'] = 'Job title is required';
    }
    
    if (empty($job['description'])) {
        $errors['description'] = 'Job description is required';
    }
    
    if (empty($job['location'])) {
        $errors['location'] = 'Job location is required';
    }
    
    if (empty($job['application_deadline'])) {
        $errors['application_deadline'] = 'Application deadline is required';
    } elseif (strtotime($job['application_deadline']) < time() && $job['status'] === 'open') {
        $errors['application_deadline'] = 'Application deadline must be in the future for open jobs';
    }
    
    // If no errors, update job in database
    if (empty($errors)) {
        $sql = "UPDATE jobs SET 
                title = ?, description = ?, requirements = ?, responsibilities = ?, 
                location = ?, job_type = ?, experience_level = ?, education_level = ?, 
                salary = ?, application_deadline = ?, skills = ?, status = ?
                WHERE id = ? AND recruiter_id = ?";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param(
            $stmt, 
            "ssssssssssssii", 
            $job['title'],
            $job['description'],
            $job['requirements'],
            $job['responsibilities'],
            $job['location'],
            $job['job_type'],
            $job['experience_level'],
            $job['education_level'],
            $job['salary'],
            $job['application_deadline'],
            $job['skills'],
            $job['status'],
            $job_id,
            $_SESSION['user_id']
        );
        
        if (mysqli_stmt_execute($stmt)) {
            setAlert('success', 'Job updated successfully!');
            // Stay on the same page but show the alert
            redirect('edit_job.php?id=' . $job_id . '&updated=true');
        } else {
            setAlert('error', 'Failed to update job. Please try again.');
        }
    }
}

// Include header
include 'includes/header.php';
?>

<div class="bg-gray-50 py-8 min-h-screen">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <!-- Abstract design elements -->
            <div class="absolute top-0 right-0 -mt-10 -mr-10 hidden lg:block">
                <div class="w-64 h-64 bg-gradient-to-br from-blue-400/20 to-indigo-500/20 rounded-full blur-3xl"></div>
            </div>
            <div class="absolute bottom-0 left-0 -mb-10 -ml-10 hidden lg:block">
                <div class="w-72 h-72 bg-gradient-to-tr from-purple-400/20 to-pink-500/20 rounded-full blur-3xl"></div>
            </div>
            
            <div class="flex items-center justify-between mb-6 relative">
                <h1 class="text-3xl font-bold text-gray-800">Edit Job</h1>
                <div class="flex gap-3">
                    <a href="job_details.php?id=<?php echo $job_id; ?>" class="bg-blue-100 hover:bg-blue-200 text-blue-700 px-4 py-2 rounded-lg transition duration-300 flex items-center">
                        <i class="fas fa-eye mr-2"></i> View Job
                    </a>
                    <a href="manage_jobs.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition duration-300 flex items-center">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Jobs
                    </a>
                </div>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg">
                    <div class="font-medium">Please fix the following errors:</div>
                    <ul class="mt-1 ml-5 list-disc">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8 relative">
                <!-- Abstract corner accent -->
                <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-bl from-blue-100 to-transparent rounded-bl-full"></div>
                
                <div class="p-6 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-100 relative z-10">
                    <div class="flex items-center">
                        <?php if (!empty($company['logo_path'])): ?>
                            <img src="<?php echo $company['logo_path']; ?>" alt="<?php echo htmlspecialchars($company['company_name']); ?>" class="w-12 h-12 object-contain rounded-lg border border-gray-200 bg-white p-1 mr-4">
                        <?php else: ?>
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center border border-blue-200 mr-4">
                                <i class="fas fa-building text-blue-500 text-xl"></i>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800">Editing Job for <?php echo htmlspecialchars($company['company_name']); ?></h2>
                            <p class="text-gray-600 text-sm">Job ID: #<?php echo $job_id; ?> • Posted: <?php echo date('M d, Y', strtotime($job['posted_date'])); ?></p>
                        </div>
                    </div>
                </div>
                
                <form action="edit_job.php?id=<?php echo $job_id; ?>" method="post" class="p-6 relative z-10">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="col-span-2">
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Job Title <span class="text-red-500">*</span></label>
                            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($job['title']); ?>" class="block w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent <?php echo isset($errors['title']) ? 'border-red-500' : ''; ?>" required>
                            <?php if (isset($errors['title'])): ?>
                                <p class="mt-1 text-sm text-red-500"><?php echo $errors['title']; ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-span-2">
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Job Description <span class="text-red-500">*</span></label>
                            <textarea id="description" name="description" rows="6" class="block w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent <?php echo isset($errors['description']) ? 'border-red-500' : ''; ?>" required><?php echo htmlspecialchars($job['description']); ?></textarea>
                            <?php if (isset($errors['description'])): ?>
                                <p class="mt-1 text-sm text-red-500"><?php echo $errors['description']; ?></p>
                            <?php endif; ?>
                            <p class="mt-1 text-sm text-gray-500">Provide a detailed description of the job, including its purpose and objectives.</p>
                        </div>
                        
                        <div class="col-span-2">
                            <label for="responsibilities" class="block text-sm font-medium text-gray-700 mb-1">Responsibilities</label>
                            <textarea id="responsibilities" name="responsibilities" rows="4" class="block w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?php echo htmlspecialchars($job['responsibilities']); ?></textarea>
                            <p class="mt-1 text-sm text-gray-500">List the key responsibilities and duties of the role. Use bullet points for better readability.</p>
                        </div>
                        
                        <div class="col-span-2">
                            <label for="requirements" class="block text-sm font-medium text-gray-700 mb-1">Requirements</label>
                            <textarea id="requirements" name="requirements" rows="4" class="block w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"><?php echo htmlspecialchars($job['requirements']); ?></textarea>
                            <p class="mt-1 text-sm text-gray-500">Specify the qualifications, skills, and experience required for this position.</p>
                        </div>
                        
                        <div>
                            <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Location <span class="text-red-500">*</span></label>
                            <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($job['location']); ?>" class="block w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent <?php echo isset($errors['location']) ? 'border-red-500' : ''; ?>" required>
                            <?php if (isset($errors['location'])): ?>
                                <p class="mt-1 text-sm text-red-500"><?php echo $errors['location']; ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div>
                            <label for="job_type" class="block text-sm font-medium text-gray-700 mb-1">Job Type</label>
                            <select id="job_type" name="job_type" class="block w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="full-time" <?php echo $job['job_type'] === 'full-time' ? 'selected' : ''; ?>>Full-time</option>
                                <option value="part-time" <?php echo $job['job_type'] === 'part-time' ? 'selected' : ''; ?>>Part-time</option>
                                <option value="contract" <?php echo $job['job_type'] === 'contract' ? 'selected' : ''; ?>>Contract</option>
                                <option value="internship" <?php echo $job['job_type'] === 'internship' ? 'selected' : ''; ?>>Internship</option>
                                <option value="remote" <?php echo $job['job_type'] === 'remote' ? 'selected' : ''; ?>>Remote</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="experience_level" class="block text-sm font-medium text-gray-700 mb-1">Experience Level</label>
                            <select id="experience_level" name="experience_level" class="block w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="entry" <?php echo $job['experience_level'] === 'entry' ? 'selected' : ''; ?>>Entry Level</option>
                                <option value="mid" <?php echo $job['experience_level'] === 'mid' ? 'selected' : ''; ?>>Mid Level</option>
                                <option value="senior" <?php echo $job['experience_level'] === 'senior' ? 'selected' : ''; ?>>Senior Level</option>
                                <option value="executive" <?php echo $job['experience_level'] === 'executive' ? 'selected' : ''; ?>>Executive Level</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="education_level" class="block text-sm font-medium text-gray-700 mb-1">Education Level</label>
                            <select id="education_level" name="education_level" class="block w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="high_school" <?php echo $job['education_level'] === 'high_school' ? 'selected' : ''; ?>>High School</option>
                                <option value="associate" <?php echo $job['education_level'] === 'associate' ? 'selected' : ''; ?>>Associate Degree</option>
                                <option value="bachelor" <?php echo $job['education_level'] === 'bachelor' ? 'selected' : ''; ?>>Bachelor's Degree</option>
                                <option value="master" <?php echo $job['education_level'] === 'master' ? 'selected' : ''; ?>>Master's Degree</option>
                                <option value="doctorate" <?php echo $job['education_level'] === 'doctorate' ? 'selected' : ''; ?>>Doctorate</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="salary" class="block text-sm font-medium text-gray-700 mb-1">Salary (Optional)</label>
                            <input type="text" id="salary" name="salary" value="<?php echo htmlspecialchars($job['salary']); ?>" placeholder="e.g. $50,000 - $70,000 per year" class="block w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        
                        <div>
                            <label for="application_deadline" class="block text-sm font-medium text-gray-700 mb-1">Application Deadline <span class="text-red-500">*</span></label>
                            <input type="date" id="application_deadline" name="application_deadline" value="<?php echo htmlspecialchars($job['application_deadline']); ?>" class="block w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent <?php echo isset($errors['application_deadline']) ? 'border-red-500' : ''; ?>" required>
                            <?php if (isset($errors['application_deadline'])): ?>
                                <p class="mt-1 text-sm text-red-500"><?php echo $errors['application_deadline']; ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Job Status</label>
                            <select id="status" name="status" class="block w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="open" <?php echo $job['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                                <option value="closed" <?php echo $job['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                            </select>
                            <p class="mt-1 text-sm text-gray-500">Closed jobs will not appear in search results.</p>
                        </div>
                        
                        <div class="col-span-2">
                            <label for="skills" class="block text-sm font-medium text-gray-700 mb-1">Required Skills</label>
                            <input type="text" id="skills" name="skills" value="<?php echo htmlspecialchars($job['skills']); ?>" placeholder="e.g. JavaScript, React, Node.js" class="block w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <p class="mt-1 text-sm text-gray-500">Separate skills with commas. These will be used for job search and matching.</p>
                        </div>
                    </div>
                    
                    <div class="border-t border-gray-100 pt-6 flex justify-end gap-3">
                        <a href="manage_jobs.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-lg transition duration-300">
                            Cancel
                        </a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition duration-300 flex items-center">
                            <i class="fas fa-save mr-2"></i> Update Job
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Job statistics card with abstract design -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8 relative">
                <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-br from-blue-50 via-transparent to-indigo-50 opacity-50"></div>
                <div class="absolute top-0 right-0 w-24 h-24 bg-blue-100 rounded-full -mt-12 -mr-12 opacity-50"></div>
                <div class="absolute bottom-0 left-0 w-32 h-32 bg-indigo-100 rounded-full -mb-16 -ml-16 opacity-50"></div>
                
                <div class="p-6 relative z-10">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-chart-line text-blue-500 mr-2"></i> Job Performance
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border border-blue-200">
                            <div class="text-blue-600 text-sm font-medium mb-1">Views</div>
                            <div class="text-2xl font-bold text-gray-800"><?php echo number_format($job['views']); ?></div>
                        </div>
                        
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4 border border-purple-200">
                            <div class="text-purple-600 text-sm font-medium mb-1">Applications</div>
                            <div class="text-2xl font-bold text-gray-800">
                                <?php
                                // Get application count
                                $app_sql = "SELECT COUNT(*) as count FROM applications WHERE job_id = ?";
                                $app_stmt = mysqli_prepare($conn, $app_sql);
                                mysqli_stmt_bind_param($app_stmt, "i", $job_id);
                                mysqli_stmt_execute($app_stmt);
                                $app_result = mysqli_stmt_get_result($app_stmt);
                                $app_count = mysqli_fetch_assoc($app_result)['count'];
                                echo number_format($app_count);
                                ?>
                            </div>
                        </div>
                        
                        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 border border-green-200">
                            <div class="text-green-600 text-sm font-medium mb-1">Days Remaining</div>
                            <div class="text-2xl font-bold text-gray-800">
                                <?php
                                $deadline = new DateTime($job['application_deadline']);
                                $today = new DateTime();
                                $days_remaining = $today > $deadline ? 0 : $today->diff($deadline)->days;
                                echo $days_remaining;
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Danger zone with abstract design -->
            <div class="bg-white rounded-xl shadow-sm border border-red-200 overflow-hidden">
                <div class="p-6 bg-gradient-to-r from-red-50 to-orange-50 border-b border-red-100 relative">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-red-100 rounded-full -mt-10 -mr-10 opacity-50"></div>
                    <h2 class="text-xl font-semibold text-gray-800 flex items-center relative z-10">
                        <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i> Danger Zone
                    </h2>
                </div>
                
                <div class="p-6">
                    <p class="text-gray-600 mb-4">These actions cannot be undone. Please be certain.</p>
                    
                    <div class="flex flex-col sm:flex-row gap-3">
                        <a href="manage_jobs.php?action=close&id=<?php echo $job_id; ?>" class="bg-orange-100 hover:bg-orange-200 text-orange-700 px-4 py-2 rounded-lg transition duration-300 flex items-center justify-center">
                            <i class="fas fa-times-circle mr-2"></i> Close Job
                        </a>
                        
                        <button type="button" onclick="confirmDelete(<?php echo $job_id; ?>)" class="bg-red-100 hover:bg-red-200 text-red-700 px-4 py-2 rounded-lg transition duration-300 flex items-center justify-center">
                            <i class="fas fa-trash-alt mr-2"></i> Delete Job
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(jobId) {
    if (confirm('Are you sure you want to delete this job? This action cannot be undone.')) {
        window.location.href = 'manage_jobs.php?action=delete&id=' + jobId;
    }
}
</script>

<?php include 'includes/footer.php'; ?>