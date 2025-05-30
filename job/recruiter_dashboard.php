<?php
require_once 'includes/functions.php';

// Check if user is logged in and is a recruiter
if (!isLoggedIn() || !isRecruiter()) {
    redirect('login.php');
}

// Get company profile
$company = getCompanyProfile($_SESSION['user_id']);

// Get posted jobs
$sql = "SELECT j.*, 
        (SELECT COUNT(*) FROM applications WHERE job_id = j.id) as application_count 
        FROM jobs j 
        WHERE j.recruiter_id = ? 
        ORDER BY j.posted_date DESC 
        LIMIT 5";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$recent_jobs = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get recent applications
$sql = "SELECT a.*, j.title, s.full_name, s.profile_pic 
        FROM applications a 
        JOIN jobs j ON a.job_id = j.id 
        JOIN student_profiles s ON a.student_id = s.user_id 
        WHERE j.recruiter_id = ? 
        ORDER BY a.application_date DESC 
        LIMIT 5";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$recent_applications = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Include header
include 'includes/header.php';
?>

<div class="bg-gray-50 py-8 min-h-screen relative overflow-hidden">
    <!-- Abstract design elements -->
    <div class="absolute top-0 right-0 w-1/3 h-1/3 bg-blue-500 rounded-full opacity-5 transform translate-x-1/4 -translate-y-1/4"></div>
    <div class="absolute bottom-0 left-0 w-1/2 h-1/2 bg-indigo-500 rounded-full opacity-5 transform -translate-x-1/4 translate-y-1/4"></div>
    <div class="absolute top-1/3 right-1/4 w-16 h-16 bg-green-500 rounded-full opacity-10"></div>
    <div class="absolute bottom-1/2 left-1/4 w-24 h-24 bg-yellow-500 rounded-full opacity-5"></div>
    
    <div class="container mx-auto px-4 relative z-10">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-3xl font-bold text-gray-800">
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-blue-600 to-indigo-600">
                    Welcome, <?php echo htmlspecialchars($company['company_name'] ?? $_SESSION['username']); ?>!
                </span>
            </h1>
            <a href="post_job.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300 shadow flex items-center">
                <i class="fas fa-plus-circle mr-2"></i> Post New Job
            </a>
        </div>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Company Profile Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6 transition duration-300 hover:shadow-md">
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 flex justify-between items-center border-b">
                        <h2 class="text-xl font-semibold text-gray-800">Company Profile</h2>
                        <a href="company_profile.php" class="text-blue-600 hover:text-blue-800 transition duration-300 flex items-center">
                            <i class="fas fa-edit mr-1"></i> Edit
                        </a>
                    </div>
                    <div class="p-6">
                        <div class="flex flex-col items-center mb-6">
                            <?php if (!empty($company['logo_path'])): ?>
                                <img src="<?php echo $company['logo_path']; ?>" alt="Company Logo" class="w-24 h-24 object-contain mb-3 rounded-lg shadow-sm">
                            <?php else: ?>
                                <div class="w-24 h-24 bg-blue-50 flex items-center justify-center mb-3 rounded-lg">
                                    <i class="fas fa-building text-blue-400 text-4xl"></i>
                                </div>
                            <?php endif; ?>
                            <h3 class="text-lg font-semibold text-center text-gray-800"><?php echo htmlspecialchars($company['company_name'] ?? $_SESSION['username']); ?></h3>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <h4 class="font-semibold text-gray-700 mb-2 flex items-center">
                                    <i class="fas fa-industry text-blue-500 mr-2"></i> Industry
                                </h4>
                                <?php if (!empty($company['industry'])): ?>
                                    <p class="text-gray-600"><?php echo htmlspecialchars($company['industry']); ?></p>
                                <?php else: ?>
                                    <p class="text-gray-400 italic">Not specified</p>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <h4 class="font-semibold text-gray-700 mb-2 flex items-center">
                                    <i class="fas fa-map-marker-alt text-blue-500 mr-2"></i> Location
                                </h4>
                                <?php if (!empty($company['location'])): ?>
                                    <p class="text-gray-600"><?php echo htmlspecialchars($company['location']); ?></p>
                                <?php else: ?>
                                    <p class="text-gray-400 italic">Not specified</p>
                                <?php endif; ?>
                            </div>
                            
                            <div>
                                <h4 class="font-semibold text-gray-700 mb-2 flex items-center">
                                    <i class="fas fa-globe text-blue-500 mr-2"></i> Website
                                </h4>
                                <?php if (!empty($company['website'])): ?>
                                    <a href="<?php echo $company['website']; ?>" class="text-blue-600 hover:text-blue-800 transition duration-300" target="_blank"><?php echo htmlspecialchars($company['website']); ?></a>
                                <?php else: ?>
                                    <p class="text-gray-400 italic">Not specified</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden transition duration-300 hover:shadow-md">
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b">
                        <h2 class="text-xl font-semibold text-gray-800">Quick Links</h2>
                    </div>
                    <div class="p-6">
                        <ul class="space-y-3">
                            <li>
                                <a href="post_job.php" class="flex items-center text-gray-700 hover:text-blue-600 transition duration-300 p-2 rounded-lg hover:bg-blue-50">
                                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                        <i class="fas fa-plus-circle text-blue-600"></i>
                                    </div>
                                    <span>Post New Job</span>
                                </a>
                            </li>
                            <li>
                                <a href="manage_jobs.php" class="flex items-center text-gray-700 hover:text-blue-600 transition duration-300 p-2 rounded-lg hover:bg-blue-50">
                                    <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center mr-3">
                                        <i class="fas fa-briefcase text-indigo-600"></i>
                                    </div>
                                    <span>Manage Jobs</span>
                                </a>
                            </li>
                            <li>
                                <a href="manage_applications.php" class="flex items-center text-gray-700 hover:text-blue-600 transition duration-300 p-2 rounded-lg hover:bg-blue-50">
                                    <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center mr-3">
                                        <i class="fas fa-clipboard-list text-purple-600"></i>
                                    </div>
                                    <span>Manage Applications</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <!-- Job Statistics -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6 transition duration-300 hover:shadow-md">
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b">
                        <h2 class="text-xl font-semibold text-gray-800">Job Statistics</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <?php
                            // Get total jobs count
                            $sql = "SELECT COUNT(*) as total FROM jobs WHERE recruiter_id = ?";
                            $stmt = mysqli_prepare($conn, $sql);
                            mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);
                            $total_jobs = mysqli_fetch_assoc($result)['total'];
                            
                            // Get active jobs count
                            $sql = "SELECT COUNT(*) as active FROM jobs WHERE recruiter_id = ? AND status = 'open'";
                            $stmt = mysqli_prepare($conn, $sql);
                            mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);
                            $active_jobs = mysqli_fetch_assoc($result)['active'];
                            
                            // Get total applications count
                            $sql = "SELECT COUNT(*) as total FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.recruiter_id = ?";
                            $stmt = mysqli_prepare($conn, $sql);
                            mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);
                            $total_applications = mysqli_fetch_assoc($result)['total'];
                            ?>
                            
                            <div class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-xl border border-blue-200">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-blue-600 text-3xl font-bold"><?php echo $total_jobs; ?></div>
                                        <div class="text-gray-600 mt-1">Total Jobs</div>
                                    </div>
                                    <div class="w-12 h-12 rounded-full bg-blue-200 flex items-center justify-center">
                                        <i class="fas fa-briefcase text-blue-600 text-xl"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-gradient-to-br from-green-50 to-green-100 p-6 rounded-xl border border-green-200">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-green-600 text-3xl font-bold"><?php echo $active_jobs; ?></div>
                                        <div class="text-gray-600 mt-1">Active Jobs</div>
                                    </div>
                                    <div class="w-12 h-12 rounded-full bg-green-200 flex items-center justify-center">
                                        <i class="fas fa-check-circle text-green-600 text-xl"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="bg-gradient-to-br from-purple-50 to-purple-100 p-6 rounded-xl border border-purple-200">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-purple-600 text-3xl font-bold"><?php echo $total_applications; ?></div>
                                        <div class="text-gray-600 mt-1">Applications</div>
                                    </div>
                                    <div class="w-12 h-12 rounded-full bg-purple-200 flex items-center justify-center">
                                        <i class="fas fa-users text-purple-600 text-xl"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Jobs -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6 transition duration-300 hover:shadow-md">
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 flex justify-between items-center border-b">
                        <h2 class="text-xl font-semibold text-gray-800">Recent Jobs</h2>
                        <a href="manage_jobs.php" class="text-blue-600 hover:text-blue-800 transition duration-300 flex items-center">
                            <span>View All</span> <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                    <div class="p-6">
                        <?php if (count($recent_jobs) > 0): ?>
                            <div class="space-y-5">
                                <?php foreach ($recent_jobs as $job): ?>
                                    <div class="border-b border-gray-100 pb-5 last:border-b-0 last:pb-0">
                                        <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-4">
                                            <div>
                                                <h3 class="font-semibold text-lg text-gray-800">
                                                    <a href="job_details.php?id=<?php echo $job['id']; ?>" class="hover:text-blue-600 transition duration-300"><?php echo htmlspecialchars($job['title']); ?></a>
                                                </h3>
                                                <div class="flex flex-wrap gap-2 mt-3">
                                                    <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm flex items-center">
                                                        <i class="fas fa-map-marker-alt mr-1 text-blue-500"></i> <?php echo htmlspecialchars($job['location']); ?>
                                                    </span>
                                                    <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm flex items-center">
                                                        <i class="fas fa-briefcase mr-1 text-blue-500"></i> <?php echo htmlspecialchars(ucfirst($job['job_type'])); ?>
                                                    </span>
                                                    <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm flex items-center">
                                                        <i class="fas fa-calendar-alt mr-1 text-blue-500"></i> <?php echo date('M d, Y', strtotime($job['posted_date'])); ?>
                                                    </span>
                                                </div>
                                                <div class="mt-3">
                                                    <span class="text-blue-600 flex items-center">
                                                        <i class="fas fa-users mr-1"></i> <?php echo $job['application_count']; ?> Applications
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="flex flex-row md:flex-col space-x-3 md:space-x-0 md:space-y-2">
                                                <a href="edit_job.php?id=<?php echo $job['id']; ?>" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm transition duration-300 flex items-center justify-center">
                                                    <i class="fas fa-edit mr-1"></i> Edit
                                                </a>
                                                <a href="view_applications.php?job_id=<?php echo $job['id']; ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition duration-300 flex items-center justify-center">
                                                    <i class="fas fa-eye mr-1"></i> Applications
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <div class="w-16 h-16 bg-blue-100 rounded-full mx-auto flex items-center justify-center mb-4">
                                    <i class="fas fa-briefcase text-blue-500 text-xl"></i>
                                </div>
                                <p class="text-gray-500 mb-4">You haven't posted any jobs yet.</p>
                                <a href="post_job.php" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-300 inline-flex items-center">
                                    <i class="fas fa-plus-circle mr-2"></i> Post a job
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Recent Applications -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden transition duration-300 hover:shadow-md">
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 flex justify-between items-center border-b">
                        <h2 class="text-xl font-semibold text-gray-800">Recent Applications</h2>
                        <a href="manage_applications.php" class="text-blue-600 hover:text-blue-800 transition duration-300 flex items-center">
                            <span>View All</span> <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                    <div class="p-6">
                        <?php if (count($recent_applications) > 0): ?>
                            <div class="space-y-5">
                                <?php foreach ($recent_applications as $application): ?>
                                    <div class="border-b border-gray-100 pb-5 last:border-b-0 last:pb-0">
                                        <div class="flex flex-col md:flex-row md:justify-between md:items-start gap-4">
                                            <div class="flex items-start space-x-4">
                                                <?php if (!empty($application['profile_pic'])): ?>
                                                    <img src="<?php echo $application['profile_pic']; ?>" alt="Applicant" class="w-12 h-12 rounded-full object-cover border border-gray-200">
                                                <?php else: ?>
                                                    <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center border border-blue-200">
                                                        <i class="fas fa-user text-blue-500"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <h3 class="font-semibold text-gray-800"><?php echo htmlspecialchars($application['full_name']); ?></h3>
                                                    <p class="text-gray-600 mt-1">Applied for: <a href="job_details.php?id=<?php echo $application['job_id']; ?>" class="text-blue-600 hover:text-blue-800 transition duration-300"><?php echo htmlspecialchars($application['title']); ?></a></p>
                                                    <div class="flex flex-wrap gap-2 mt-3">
                                                        <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm flex items-center">
                                                            <i class="fas fa-calendar-alt mr-1 text-blue-500"></i> <?php echo date('M d, Y', strtotime($application['application_date'])); ?>
                                                        </span>
                                                        <?php
                                                        $status_class = '';
                                                        $status_bg = '';
                                                        $status_icon = '';
                                                        switch ($application['status']) {
                                                            case 'pending':
                                                                $status_class = 'text-yellow-700';
                                                                $status_bg = 'bg-yellow-100';
                                                                $status_icon = 'fas fa-clock text-yellow-500';
                                                                break;
                                                            case 'reviewed':
                                                                $status_class = 'text-blue-700';
                                                                $status_bg = 'bg-blue-100';
                                                                $status_icon = 'fas fa-eye text-blue-500';
                                                                break;
                                                            case 'accepted':
                                                                $status_class = 'text-green-700';
                                                                $status_bg = 'bg-green-100';
                                                                $status_icon = 'fas fa-check-circle text-green-500';
                                                                break;
                                                            case 'rejected':
                                                                $status_class = 'text-red-700';
                                                                $status_bg = 'bg-red-100';
                                                                $status_icon = 'fas fa-times-circle text-red-500';
                                                                break;
                                                        }
                                                        ?>
                                                        <span class="<?php echo $status_bg . ' ' . $status_class; ?> px-3 py-1 rounded-full text-sm flex items-center">
                                                            <i class="<?php echo $status_icon; ?> mr-1"></i> <?php echo ucfirst($application['status']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <a href="review_application.php?id=<?php echo $application['id']; ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition duration-300 flex items-center justify-center whitespace-nowrap">
                                                <i class="fas fa-eye mr-1"></i> Review
                                            </a>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8">
                                <div class="w-16 h-16 bg-blue-100 rounded-full mx-auto flex items-center justify-center mb-4">
                                    <i class="fas fa-clipboard-list text-blue-500 text-xl"></i>
                                </div>
                                <p class="text-gray-500">No applications received yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>