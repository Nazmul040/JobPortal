<?php
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Check if company ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setAlert('error', 'Invalid company ID.');
    redirect('companies.php');
}

$company_id = (int)$_GET['id'];

// Get company profile
$sql = "SELECT c.*, u.email, u.username, u.created_at as user_created_at
        FROM company_profiles c
        JOIN users u ON c.user_id = u.id
        WHERE c.user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $company_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    setAlert('error', 'Company not found.');
    redirect('companies.php');
}

$company = mysqli_fetch_assoc($result);

// Get company's open jobs
$jobs_sql = "SELECT j.*, 
            (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) as applications_count
            FROM jobs j
            WHERE j.recruiter_id = ? AND j.status = 'open'
            ORDER BY j.posted_date DESC";
$stmt = mysqli_prepare($conn, $jobs_sql);
mysqli_stmt_bind_param($stmt, "i", $company_id);
mysqli_stmt_execute($stmt);
$jobs_result = mysqli_stmt_get_result($stmt);
$open_jobs = mysqli_fetch_all($jobs_result, MYSQLI_ASSOC);

// Check if user has saved jobs (for students only)
$saved_jobs = [];
if (isStudent()) {
    $saved_sql = "SELECT job_id FROM saved_jobs WHERE student_id = ?";
    $stmt = mysqli_prepare($conn, $saved_sql);
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $saved_result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($saved_result)) {
        $saved_jobs[] = $row['job_id'];
    }
}

// Include header
include 'includes/header.php';
?>

<div class="bg-gray-50 py-8 min-h-screen">
    <div class="container mx-auto px-4">
        <!-- Company header -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 p-6 sm:p-8 border-b border-gray-100">
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6">
                    <div class="flex-shrink-0">
                        <?php if (!empty($company['logo_path'])): ?>
                            <img src="<?php echo $company['logo_path']; ?>" alt="<?php echo htmlspecialchars($company['company_name']); ?>" class="w-24 h-24 object-contain rounded-lg border border-gray-200 bg-white p-2">
                        <?php else: ?>
                            <div class="w-24 h-24 bg-blue-100 rounded-lg flex items-center justify-center border border-blue-200">
                                <i class="fas fa-building text-blue-500 text-4xl"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow">
                        <h1 class="text-2xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($company['company_name']); ?></h1>
                        <?php if (!empty($company['industry'])): ?>
                            <p class="text-gray-600 mb-3"><?php echo htmlspecialchars($company['industry']); ?></p>
                        <?php endif; ?>
                        <div class="flex flex-wrap gap-3">
                            <?php if (!empty($company['location'])): ?>
                                <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm flex items-center">
                                    <i class="fas fa-map-marker-alt mr-1 text-blue-500"></i> <?php echo htmlspecialchars($company['location']); ?>
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($company['company_size'])): ?>
                                <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm flex items-center">
                                    <i class="fas fa-users mr-1 text-blue-500"></i> <?php echo htmlspecialchars($company['company_size']); ?> employees
                                </span>
                            <?php endif; ?>
                            <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm flex items-center">
                                <i class="fas fa-briefcase mr-1 text-blue-500"></i> <?php echo count($open_jobs); ?> open positions
                            </span>
                        </div>
                    </div>
                    <div class="flex-shrink-0 flex flex-col sm:items-end gap-2">
                        <?php if (!empty($company['website'])): ?>
                            <a href="<?php echo htmlspecialchars($company['website']); ?>" target="_blank" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition duration-300 flex items-center justify-center">
                                <i class="fas fa-globe mr-2"></i> Visit Website
                            </a>
                        <?php endif; ?>
                        <a href="jobs.php?keyword=<?php echo urlencode($company['company_name']); ?>" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm transition duration-300 flex items-center justify-center">
                            <i class="fas fa-search mr-2"></i> View All Jobs
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Company tabs -->
            <div class="border-b border-gray-100">
                <div class="flex overflow-x-auto">
                    <button class="px-6 py-3 font-medium text-blue-600 border-b-2 border-blue-600 whitespace-nowrap">
                        About
                    </button>
                    <button class="px-6 py-3 font-medium text-gray-500 hover:text-gray-700 whitespace-nowrap">
                        Open Jobs (<?php echo count($open_jobs); ?>)
                    </button>
                </div>
            </div>
            
            <!-- Company details -->
            <div class="p-6 sm:p-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2">
                        <div class="mb-8">
                            <h2 class="text-xl font-semibold text-gray-800 mb-4">About <?php echo htmlspecialchars($company['company_name']); ?></h2>
                            <?php if (!empty($company['description'])): ?>
                                <div class="prose max-w-none text-gray-600">
                                    <?php echo nl2br(htmlspecialchars($company['description'])); ?>
                                </div>
                            <?php else: ?>
                                <p class="text-gray-500 italic">No company description available.</p>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (count($open_jobs) > 0): ?>
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800 mb-4">Open Positions</h2>
                            <div class="space-y-4">
                                <?php foreach ($open_jobs as $index => $job): ?>
                                    <?php if ($index < 3): // Show only first 3 jobs ?>
                                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-100 hover:border-blue-200 transition duration-300">
                                        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                                            <div>
                                                <h3 class="text-lg font-medium text-gray-800 mb-1">
                                                    <a href="job_details.php?id=<?php echo $job['id']; ?>" class="hover:text-blue-600 transition duration-300">
                                                        <?php echo htmlspecialchars($job['title']); ?>
                                                    </a>
                                                </h3>
                                                <div class="flex flex-wrap gap-2 text-sm text-gray-500">
                                                    <span class="flex items-center">
                                                        <i class="fas fa-map-marker-alt mr-1 text-blue-500"></i> <?php echo htmlspecialchars($job['location']); ?>
                                                    </span>
                                                    <span class="flex items-center">
                                                        <i class="fas fa-briefcase mr-1 text-blue-500"></i> <?php echo htmlspecialchars(ucfirst($job['job_type'])); ?>
                                                    </span>
                                                    <?php if (!empty($job['salary'])): ?>
                                                    <span class="flex items-center">
                                                        <i class="fas fa-money-bill-wave mr-1 text-blue-500"></i> <?php echo htmlspecialchars($job['salary']); ?>
                                                    </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="flex gap-2">
                                                <a href="job_details.php?id=<?php echo $job['id']; ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition duration-300 flex items-center justify-center">
                                                    <i class="fas fa-eye mr-2"></i> View
                                                </a>
                                                <?php if (isStudent()): ?>
                                                    <?php if (in_array($job['id'], $saved_jobs)): ?>
                                                        <a href="job_details.php?id=<?php echo $job['id']; ?>" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm transition duration-300 flex items-center justify-center">
                                                            <i class="fas fa-bookmark mr-2"></i> Saved
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="job_details.php?id=<?php echo $job['id']; ?>" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm transition duration-300 flex items-center justify-center">
                                                            <i class="far fa-bookmark mr-2"></i> Save
                                                        </a>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                
                                <?php if (count($open_jobs) > 3): ?>
                                <div class="text-center mt-4">
                                    <a href="jobs.php?keyword=<?php echo urlencode($company['company_name']); ?>" class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium">
                                        View all <?php echo count($open_jobs); ?> open positions <i class="fas fa-arrow-right ml-2"></i>
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="lg:col-span-1">
                        <div class="bg-gray-50 rounded-xl p-6 border border-gray-100 mb-6">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Company Information</h3>
                            <div class="space-y-4">
                                <?php if (!empty($company['founded_year'])): ?>
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-calendar-alt text-blue-500"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700">Founded</h4>
                                        <p class="text-gray-600"><?php echo htmlspecialchars($company['founded_year']); ?></p>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($company['industry'])): ?>
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-industry text-blue-500"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700">Industry</h4>
                                        <p class="text-gray-600"><?php echo htmlspecialchars($company['industry']); ?></p>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($company['company_size'])): ?>
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-users text-blue-500"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700">Company Size</h4>
                                        <p class="text-gray-600"><?php echo htmlspecialchars($company['company_size']); ?> employees</p>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($company['location'])): ?>
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-map-marker-alt text-blue-500"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700">Headquarters</h4>
                                        <p class="text-gray-600"><?php echo htmlspecialchars($company['location']); ?></p>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($company['website'])): ?>
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-globe text-blue-500"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700">Website</h4>
                                        <a href="<?php echo htmlspecialchars($company['website']); ?>" target="_blank" class="text-blue-600 hover:underline break-all">
                                            <?php echo htmlspecialchars(preg_replace('#^https?://#', '', $company['website'])); ?>
                                        </a>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-user-tie text-blue-500"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700">Member Since</h4>
                                        <p class="text-gray-600"><?php echo date('F Y', strtotime($company['user_created_at'])); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($company['social_linkedin']) || !empty($company['social_twitter']) || !empty($company['social_facebook'])): ?>
                        <div class="bg-gray-50 rounded-xl p-6 border border-gray-100">
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Connect With Us</h3>
                            <div class="flex gap-3">
                                <?php if (!empty($company['social_linkedin'])): ?>
                                <a href="<?php echo htmlspecialchars($company['social_linkedin']); ?>" target="_blank" class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center hover:bg-blue-200 transition duration-300">
                                    <i class="fab fa-linkedin-in text-blue-600"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if (!empty($company['social_twitter'])): ?>
                                <a href="<?php echo htmlspecialchars($company['social_twitter']); ?>" target="_blank" class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center hover:bg-blue-200 transition duration-300">
                                    <i class="fab fa-twitter text-blue-600"></i>
                                </a>
                                <?php endif; ?>
                                
                                <?php if (!empty($company['social_facebook'])): ?>
                                <a href="<?php echo htmlspecialchars($company['social_facebook']); ?>" target="_blank" class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center hover:bg-blue-200 transition duration-300">
                                    <i class="fab fa-facebook-f text-blue-600"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Similar companies section -->
        <?php
        // Get similar companies based on industry
        if (!empty($company['industry'])) {
            $similar_sql = "SELECT c.*, 
                          (SELECT COUNT(*) FROM jobs j WHERE j.recruiter_id = c.user_id AND j.status = 'open') as job_count
                          FROM company_profiles c
                          WHERE c.industry = ? AND c.user_id != ?
                          ORDER BY job_count DESC
                          LIMIT 3";
            $stmt = mysqli_prepare($conn, $similar_sql);
            mysqli_stmt_bind_param($stmt, "si", $company['industry'], $company_id);
            mysqli_stmt_execute($stmt);
            $similar_result = mysqli_stmt_get_result($stmt);
            $similar_companies = mysqli_fetch_all($similar_result, MYSQLI_ASSOC);
            
            if (count($similar_companies) > 0):
        ?>
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Similar Companies</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($similar_companies as $similar): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition duration-300">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0 mr-4">
                                <?php if (!empty($similar['logo_path'])): ?>
                                    <img src="<?php echo $similar['logo_path']; ?>" alt="<?php echo htmlspecialchars($similar['company_name']); ?>" class="w-12 h-12 object-contain rounded-lg border border-gray-200">
                                <?php else: ?>
                                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center border border-blue-200">
                                        <i class="fas fa-building text-blue-500 text-xl"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h3 class="text-base font-semibold text-gray-800">
                                    <a href="company_profile.php?id=<?php echo $similar['user_id']; ?>" class="hover:text-blue-600 transition duration-300">
                                        <?php echo htmlspecialchars($similar['company_name']); ?>
                                    </a>
                                </h3>
                                <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($similar['industry']); ?></p>
                            </div>
                        </div>
                        
                        <div class="space-y-2 mb-4">
                            <?php if (!empty($similar['location'])): ?>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-map-marker-alt text-blue-500 w-5"></i>
                                <span><?php echo htmlspecialchars($similar['location']); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-briefcase text-blue-500 w-5"></i>
                                <span><?php echo $similar['job_count']; ?> open job<?php echo $similar['job_count'] != 1 ? 's' : ''; ?></span>
                            </div>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <a href="company_profile.php?id=<?php echo $similar['user_id']; ?>" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                View Profile
                            </a>
                            <?php if ($similar['job_count'] > 0): ?>
                            <a href="jobs.php?keyword=<?php echo urlencode($similar['company_name']); ?>" class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-3 py-1 rounded-lg transition duration-300">
                                View Jobs
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php 
            endif;
        }
        ?>
        
        <!-- Back to companies button -->
        <div class="flex justify-center">
            <a href="companies.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-6 py-2 rounded-lg transition duration-300 flex items-center justify-center">
                <i class="fas fa-arrow-left mr-2"></i> Back to Companies
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>