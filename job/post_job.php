<?php
require_once 'includes/functions.php';

// Check if user is logged in and is a recruiter
if (!isLoggedIn() || !isRecruiter()) {
    setAlert('error', 'You must be logged in as a recruiter to post jobs.');
    redirect('login.php');
}

// Check if company profile exists
$sql = "SELECT * FROM company_profiles WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    setAlert('warning', 'Please complete your company profile before posting a job.');
    redirect('edit_company_profile.php');
}

$company = mysqli_fetch_assoc($result);

// Initialize variables
$job = [
    'title' => '',
    'description' => '',
    'requirements' => '',
    'responsibilities' => '',
    'location' => '',
    'job_type' => 'full-time',
    'experience_level' => 'entry',
    'education_level' => 'bachelor',
    'salary' => '',
    'application_deadline' => date('Y-m-d', strtotime('+30 days')),
    'skills' => ''
];

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
    } elseif (strtotime($job['application_deadline']) < time()) {
        $errors['application_deadline'] = 'Application deadline must be in the future';
    }
    
    // If no errors, insert job into database
    if (empty($errors)) {
        $sql = "INSERT INTO jobs (
                    title, description, requirements, responsibilities, 
                    location, job_type, experience_level, education_level, 
                    salary, application_deadline, skills, 
                    recruiter_id, status, posted_date, views
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'open', NOW(), 0)";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param(
            $stmt, 
            "sssssssssssi", // Changed from "ssssssssssi" to "sssssssssssi" (added one more 's' for skills)
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
            $_SESSION['user_id']
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $job_id = mysqli_insert_id($conn);
            setAlert('success', 'Job posted successfully!');
            redirect('job_details.php?id=' . $job_id);
        } else {
            setAlert('error', 'Failed to post job. Please try again.');
        }
    }
}

// Include header
include 'includes/header.php';
?>

<div class="bg-gray-50 py-8 min-h-screen">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-3xl font-bold text-gray-800">Post a New Job</h1>
                <a href="manage_jobs.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition duration-300 flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Jobs
                </a>
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
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
                <div class="p-6 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-100">
                    <div class="flex items-center">
                        <?php if (!empty($company['logo_path'])): ?>
                            <img src="<?php echo $company['logo_path']; ?>" alt="<?php echo htmlspecialchars($company['company_name']); ?>" class="w-12 h-12 object-contain rounded-lg border border-gray-200 bg-white p-1 mr-4">
                        <?php else: ?>
                            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center border border-blue-200 mr-4">
                                <i class="fas fa-building text-blue-500 text-xl"></i>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800">Posting as <?php echo htmlspecialchars($company['company_name']); ?></h2>
                            <p class="text-gray-600 text-sm">Fill out the form below to create a new job listing</p>
                        </div>
                    </div>
                </div>
                
                <form action="post_job.php" method="post" class="p-6">
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
                            <p class="mt-1 text-sm text-gray-500">Providing salary information can attract more qualified candidates.</p>
                        </div>
                        
                        <div>
                            <label for="application_deadline" class="block text-sm font-medium text-gray-700 mb-1">Application Deadline <span class="text-red-500">*</span></label>
                            <input type="date" id="application_deadline" name="application_deadline" value="<?php echo htmlspecialchars($job['application_deadline']); ?>" class="block w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent <?php echo isset($errors['application_deadline']) ? 'border-red-500' : ''; ?>" required>
                            <?php if (isset($errors['application_deadline'])): ?>
                                <p class="mt-1 text-sm text-red-500"><?php echo $errors['application_deadline']; ?></p>
                            <?php endif; ?>
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
                            <i class="fas fa-plus-circle mr-2"></i> Post Job
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-100">
                    <h2 class="text-xl font-semibold text-gray-800">Tips for Writing an Effective Job Posting</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="text-lg font-medium text-gray-800 mb-2 flex items-center">
                                <i class="fas fa-bullseye text-blue-500 mr-2"></i> Be Specific
                            </h3>
                            <p class="text-gray-600">Clearly define the role, responsibilities, and requirements. Vague job descriptions attract unqualified candidates.</p>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-medium text-gray-800 mb-2 flex items-center">
                                <i class="fas fa-search text-blue-500 mr-2"></i> Use Relevant Keywords
                            </h3>
                            <p class="text-gray-600">Include industry-specific terms and skills that job seekers might search for.</p>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-medium text-gray-800 mb-2 flex items-center">
                                <i class="fas fa-building text-blue-500 mr-2"></i> Highlight Company Culture
                            </h3>
                            <p class="text-gray-600">Share information about your company's values, work environment, and benefits to attract candidates who align with your culture.</p>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-medium text-gray-800 mb-2 flex items-center">
                                <i class="fas fa-chart-line text-blue-500 mr-2"></i> Mention Growth Opportunities
                            </h3>
                            <p class="text-gray-600">Candidates are interested in career development. Highlight potential growth paths within your organization.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>