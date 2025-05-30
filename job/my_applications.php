<?php
require_once 'includes/functions.php';

// Check if user is logged in and is a student
if (!isLoggedIn() || !isStudent()) {
    redirect('login.php');
}

// Pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Get total applications count for pagination
$sql = "SELECT COUNT(*) as total FROM applications WHERE student_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$total_applications = mysqli_fetch_assoc($result)['total'];
$total_pages = ceil($total_applications / $limit);

// Get applications with pagination
$sql = "SELECT a.*, j.title, j.location, j.job_type, j.salary, c.company_name, c.logo_path 
        FROM applications a 
        JOIN jobs j ON a.job_id = j.id 
        JOIN company_profiles c ON j.recruiter_id = c.user_id 
        WHERE a.student_id = ? 
        ORDER BY a.application_date DESC 
        LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iii", $_SESSION['user_id'], $limit, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$applications = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Filter applications by status if requested
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
if (!empty($status_filter) && in_array($status_filter, ['pending', 'reviewed', 'accepted', 'rejected'])) {
    $sql = "SELECT a.*, j.title, j.location, j.job_type, j.salary, c.company_name, c.logo_path 
            FROM applications a 
            JOIN jobs j ON a.job_id = j.id 
            JOIN company_profiles c ON j.recruiter_id = c.user_id 
            WHERE a.student_id = ? AND a.status = ? 
            ORDER BY a.application_date DESC 
            LIMIT ? OFFSET ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "isii", $_SESSION['user_id'], $status_filter, $limit, $offset);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $applications = mysqli_fetch_all($result, MYSQLI_ASSOC);
    
    // Update total for pagination
    $sql = "SELECT COUNT(*) as total FROM applications WHERE student_id = ? AND status = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "is", $_SESSION['user_id'], $status_filter);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $total_applications = mysqli_fetch_assoc($result)['total'];
    $total_pages = ceil($total_applications / $limit);
}

// Include header
include 'includes/header.php';
?>

<div class="bg-gray-50 py-8 min-h-screen">
    <div class="container mx-auto px-4">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">My Applications</h1>
                <p class="text-gray-600">Track and manage all your job applications</p>
            </div>
            <a href="jobs.php" class="mt-4 md:mt-0 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300 shadow flex items-center justify-center">
                <i class="fas fa-search mr-2"></i> Find More Jobs
            </a>
        </div>
        
        <!-- Filter options -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
            <div class="flex flex-wrap items-center gap-3">
                <span class="text-gray-700 font-medium">Filter by status:</span>
                <a href="my_applications.php" class="<?php echo empty($status_filter) ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> px-4 py-2 rounded-lg transition duration-300">
                    All
                </a>
                <a href="my_applications.php?status=pending" class="<?php echo $status_filter === 'pending' ? 'bg-yellow-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> px-4 py-2 rounded-lg transition duration-300">
                    <i class="fas fa-clock mr-1"></i> Pending
                </a>
                <a href="my_applications.php?status=reviewed" class="<?php echo $status_filter === 'reviewed' ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> px-4 py-2 rounded-lg transition duration-300">
                    <i class="fas fa-eye mr-1"></i> Reviewed
                </a>
                <a href="my_applications.php?status=accepted" class="<?php echo $status_filter === 'accepted' ? 'bg-green-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> px-4 py-2 rounded-lg transition duration-300">
                    <i class="fas fa-check-circle mr-1"></i> Accepted
                </a>
                <a href="my_applications.php?status=rejected" class="<?php echo $status_filter === 'rejected' ? 'bg-red-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?> px-4 py-2 rounded-lg transition duration-300">
                    <i class="fas fa-times-circle mr-1"></i> Rejected
                </a>
            </div>
        </div>
        
        <!-- Applications list -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
            <?php if (count($applications) > 0): ?>
                <div class="divide-y divide-gray-100">
                    <?php foreach ($applications as $application): ?>
                        <div class="p-6 hover:bg-gray-50 transition duration-300">
                            <div class="flex flex-col md:flex-row gap-6">
                                <!-- Company logo -->
                                <div class="flex-shrink-0">
                                    <?php if (!empty($application['logo_path'])): ?>
                                        <img src="<?php echo $application['logo_path']; ?>" alt="<?php echo htmlspecialchars($application['company_name']); ?>" class="w-16 h-16 object-contain rounded-lg border border-gray-200">
                                    <?php else: ?>
                                        <div class="w-16 h-16 bg-blue-100 rounded-lg flex items-center justify-center border border-blue-200">
                                            <i class="fas fa-building text-blue-500 text-2xl"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Application details -->
                                <div class="flex-grow">
                                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                                        <div>
                                            <h2 class="text-xl font-semibold text-gray-800 mb-1">
                                                <a href="job_details.php?id=<?php echo $application['job_id']; ?>" class="hover:text-blue-600 transition duration-300"><?php echo htmlspecialchars($application['title']); ?></a>
                                            </h2>
                                            <p class="text-gray-600 mb-3"><?php echo htmlspecialchars($application['company_name']); ?></p>
                                            
                                            <div class="flex flex-wrap gap-2 mb-3">
                                                <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm flex items-center">
                                                    <i class="fas fa-map-marker-alt mr-1 text-blue-500"></i> <?php echo htmlspecialchars($application['location']); ?>
                                                </span>
                                                <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm flex items-center">
                                                    <i class="fas fa-briefcase mr-1 text-blue-500"></i> <?php echo htmlspecialchars(ucfirst($application['job_type'])); ?>
                                                </span>
                                                <?php if (!empty($application['salary'])): ?>
                                                <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm flex items-center">
                                                    <i class="fas fa-money-bill-wave mr-1 text-blue-500"></i> <?php echo htmlspecialchars($application['salary']); ?>
                                                </span>
                                                <?php endif; ?>
                                                <span class="bg-gray-100 text-gray-700 px-3 py-1 rounded-full text-sm flex items-center">
                                                    <i class="fas fa-calendar-alt mr-1 text-blue-500"></i> Applied: <?php echo date('M d, Y', strtotime($application['application_date'])); ?>
                                                </span>
                                            </div>
                                            
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
                                            <div class="flex items-center">
                                                <span class="<?php echo $status_bg . ' ' . $status_class; ?> px-3 py-1 rounded-full text-sm flex items-center">
                                                    <i class="<?php echo $status_icon; ?> mr-1"></i> <?php echo ucfirst($application['status']); ?>
                                                </span>
                                                
                                                <?php if (!empty($application['feedback'])): ?>
                                                <span class="ml-3 text-gray-600 text-sm flex items-center cursor-pointer group relative">
                                                    <i class="fas fa-comment-alt mr-1"></i> Feedback
                                                    <div class="absolute bottom-full left-0 mb-2 w-64 bg-white p-3 rounded-lg shadow-lg border border-gray-200 hidden group-hover:block z-10">
                                                        <p class="text-gray-700 text-sm"><?php echo htmlspecialchars($application['feedback']); ?></p>
                                                    </div>
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="flex flex-col gap-2">
                                            <a href="application_details.php?id=<?php echo $application['id']; ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition duration-300 flex items-center justify-center">
                                                <i class="fas fa-eye mr-1"></i> View Details
                                            </a>
                                            <?php if ($application['status'] === 'pending'): ?>
                                            <a href="withdraw_application.php?id=<?php echo $application['id']; ?>" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm transition duration-300 flex items-center justify-center" onclick="return confirm('Are you sure you want to withdraw this application?');">
                                                <i class="fas fa-undo mr-1"></i> Withdraw
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-100">
                    <div class="flex justify-center">
                        <nav class="inline-flex rounded-md shadow-sm">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?>" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50">
                                    <i class="fas fa-chevron-left mr-1"></i> Previous
                                </a>
                            <?php else: ?>
                                <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-300 bg-white border border-gray-300 rounded-l-md cursor-not-allowed">
                                    <i class="fas fa-chevron-left mr-1"></i> Previous
                                </span>
                            <?php endif; ?>
                            
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $start_page + 4);
                            if ($end_page - $start_page < 4 && $start_page > 1) {
                                $start_page = max(1, $end_page - 4);
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-600">
                                        <?php echo $i; ?>
                                    </span>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?>" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?><?php echo !empty($status_filter) ? '&status=' . $status_filter : ''; ?>" class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-r-md hover:bg-gray-50">
                                    Next <i class="fas fa-chevron-right ml-1"></i>
                                </a>
                            <?php else: ?>
                                <span class="relative inline-flex items-center px-4 py-2 text-sm font-medium text-gray-300 bg-white border border-gray-300 rounded-r-md cursor-not-allowed">
                                    Next <i class="fas fa-chevron-right ml-1"></i>
                                </span>
                            <?php endif; ?>
                        </nav>
                    </div>
                </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="p-12 text-center">
                    <div class="w-20 h-20 bg-blue-100 rounded-full mx-auto flex items-center justify-center mb-6">
                        <i class="fas fa-clipboard-list text-blue-500 text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">No applications found</h3>
                    <?php if (!empty($status_filter)): ?>
                        <p class="text-gray-600 mb-6">You don't have any <?php echo $status_filter; ?> applications.</p>
                        <a href="my_applications.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg transition duration-300 inline-flex items-center">
                            <i class="fas fa-list mr-2"></i> View all applications
                        </a>
                    <?php else: ?>
                        <p class="text-gray-600 mb-6">You haven't applied to any jobs yet.</p>
                        <a href="jobs.php" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition duration-300 inline-flex items-center">
                            <i class="fas fa-search mr-2"></i> Browse Jobs
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Application tips -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b">
                <h2 class="text-xl font-semibold text-gray-800">Application Tips</h2>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white rounded-lg border border-gray-200 p-5 hover:shadow-md transition duration-300">
                        <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center mb-4">
                            <i class="fas fa-file-alt text-blue-600 text-xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Keep Your Resume Updated</h3>
                        <p class="text-gray-600">Regularly update your resume to showcase your latest skills and experiences.</p>
                    </div>
                    
                    <div class="bg-white rounded-lg border border-gray-200 p-5 hover:shadow-md transition duration-300">
                        <div class="w-12 h-12 rounded-full bg-purple-100 flex items-center justify-center mb-4">
                            <i class="fas fa-comments text-purple-600 text-xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Personalize Your Applications</h3>
                        <p class="text-gray-600">Tailor your cover letter and application for each job to stand out from other candidates.</p>
                    </div>
                    
                    <div class="bg-white rounded-lg border border-gray-200 p-5 hover:shadow-md transition duration-300">
                        <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center mb-4">
                            <i class="fas fa-check-double text-green-600 text-xl"></i>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Follow Up</h3>
                        <p class="text-gray-600">Don't be afraid to follow up on your applications if you haven't heard back after a week.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>