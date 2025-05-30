<?php
require_once 'includes/functions.php';

// Check if user is logged in and is a recruiter
if (!isLoggedIn() || !isRecruiter()) {
    setAlert('error', 'You must be logged in as a recruiter to manage jobs.');
    redirect('login.php');
}

// Handle job actions (close, delete)
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $job_id = (int)$_GET['id'];
    
    // Verify job ownership
    $check_sql = "SELECT id FROM jobs WHERE id = ? AND recruiter_id = ?";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "ii", $job_id, $_SESSION['user_id']);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) > 0) {
        if ($action === 'close') {
            // Close the job
            $update_sql = "UPDATE jobs SET status = 'closed' WHERE id = ?";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "i", $job_id);
            
            if (mysqli_stmt_execute($update_stmt)) {
                setAlert('success', 'Job has been closed successfully.');
            } else {
                setAlert('error', 'Failed to close job. Please try again.');
            }
        } elseif ($action === 'open') {
            // Reopen the job
            $update_sql = "UPDATE jobs SET status = 'open' WHERE id = ? AND application_deadline >= CURDATE()";
            $update_stmt = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($update_stmt, "i", $job_id);
            
            if (mysqli_stmt_execute($update_stmt)) {
                if (mysqli_stmt_affected_rows($update_stmt) > 0) {
                    setAlert('success', 'Job has been reopened successfully.');
                } else {
                    setAlert('warning', 'Cannot reopen job with expired deadline. Please update the deadline first.');
                }
            } else {
                setAlert('error', 'Failed to reopen job. Please try again.');
            }
        } elseif ($action === 'delete') {
            // Delete the job
            $delete_sql = "DELETE FROM jobs WHERE id = ?";
            $delete_stmt = mysqli_prepare($conn, $delete_sql);
            mysqli_stmt_bind_param($delete_stmt, "i", $job_id);
            
            if (mysqli_stmt_execute($delete_stmt)) {
                setAlert('success', 'Job has been deleted successfully.');
            } else {
                setAlert('error', 'Failed to delete job. Please try again.');
            }
        }
    } else {
        setAlert('error', 'Job not found or you do not have permission to perform this action.');
    }
    
    // Redirect to remove action parameters from URL
    redirect('manage_jobs.php');
}

// Get company profile
$company_sql = "SELECT * FROM company_profiles WHERE user_id = ?";
$company_stmt = mysqli_prepare($conn, $company_sql);
mysqli_stmt_bind_param($company_stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($company_stmt);
$company_result = mysqli_stmt_get_result($company_stmt);
$company = mysqli_fetch_assoc($company_result);

// Set up filtering and pagination
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';
$search_term = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build the query based on filters
$where_clauses = ["recruiter_id = ?"];
$params = [$_SESSION['user_id']];
$types = "i";

if ($status_filter !== 'all') {
    $where_clauses[] = "status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if (!empty($search_term)) {
    $where_clauses[] = "(title LIKE ? OR location LIKE ? OR skills LIKE ?)";
    $search_param = "%$search_term%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "sss";
}

$where_clause = implode(' AND ', $where_clauses);

// Count total jobs for pagination
$count_sql = "SELECT COUNT(*) as total FROM jobs WHERE $where_clause";
$count_stmt = mysqli_prepare($conn, $count_sql);
mysqli_stmt_bind_param_array($count_stmt, $types, $params);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$total_jobs = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_jobs / $per_page);

// Get jobs with pagination
$jobs_sql = "SELECT * FROM jobs WHERE $where_clause ORDER BY posted_date DESC LIMIT ?, ?";
$jobs_stmt = mysqli_prepare($conn, $jobs_sql);
$params[] = $offset;
$params[] = $per_page;
$types .= "ii";
mysqli_stmt_bind_param_array($jobs_stmt, $types, $params);
mysqli_stmt_execute($jobs_stmt);
$jobs_result = mysqli_stmt_get_result($jobs_stmt);

// Helper function for binding parameters dynamically
function mysqli_stmt_bind_param_array($stmt, $types, $params) {
    $refs = [];
    $args = [];
    
    $args[] = $stmt;
    $args[] = $types;
    
    foreach ($params as $key => $value) {
        $refs[$key] = &$params[$key];
        $args[] = &$refs[$key];
    }
    
    return call_user_func_array('mysqli_stmt_bind_param', $args);
}

// Include header
include 'includes/header.php';
?>

<div class="bg-gray-50 py-8 min-h-screen">
    <div class="container mx-auto px-4">
        <div class="max-w-6xl mx-auto">
            <!-- Abstract design elements -->
            <div class="absolute top-0 right-0 -mt-10 -mr-10 hidden lg:block">
                <div class="w-64 h-64 bg-gradient-to-br from-blue-400/20 to-indigo-500/20 rounded-full blur-3xl"></div>
            </div>
            <div class="absolute bottom-0 left-0 -mb-10 -ml-10 hidden lg:block">
                <div class="w-72 h-72 bg-gradient-to-tr from-purple-400/20 to-pink-500/20 rounded-full blur-3xl"></div>
            </div>
            
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 relative">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Manage Jobs</h1>
                    <p class="text-gray-600 mt-1">View, edit and manage all your job listings</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <a href="post_job.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition duration-300 flex items-center justify-center md:justify-start">
                        <i class="fas fa-plus-circle mr-2"></i> Post New Job
                    </a>
                </div>
            </div>
            
            <!-- Company profile card with abstract design -->
            <?php if ($company): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8 relative">
                <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-br from-blue-50 via-transparent to-indigo-50 opacity-50"></div>
                <div class="absolute top-0 right-0 w-24 h-24 bg-blue-100 rounded-full -mt-12 -mr-12 opacity-50"></div>
                
                <div class="p-6 relative z-10 flex flex-col md:flex-row md:items-center">
                    <div class="flex-shrink-0 mb-4 md:mb-0 md:mr-6">
                        <?php if (!empty($company['logo_path'])): ?>
                            <img src="<?php echo $company['logo_path']; ?>" alt="<?php echo htmlspecialchars($company['company_name']); ?>" class="w-16 h-16 object-contain rounded-lg border border-gray-200 bg-white p-1">
                        <?php else: ?>
                            <div class="w-16 h-16 bg-blue-100 rounded-lg flex items-center justify-center border border-blue-200">
                                <i class="fas fa-building text-blue-500 text-2xl"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="flex-grow">
                        <h2 class="text-xl font-semibold text-gray-800"><?php echo htmlspecialchars($company['company_name']); ?></h2>
                        <p class="text-gray-600"><?php echo htmlspecialchars($company['industry']); ?> • <?php echo htmlspecialchars($company['location']); ?></p>
                    </div>
                    <div class="mt-4 md:mt-0">
                        <a href="edit_company_profile.php" class="text-blue-600 hover:text-blue-800 flex items-center">
                            <i class="fas fa-edit mr-1"></i> Edit Profile
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Filters and search -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                <div class="p-4 sm:p-6">
                    <form action="manage_jobs.php" method="get" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Filter by Status</label>
                            <select id="status" name="status" class="block w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" onchange="this.form.submit()">
                                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Jobs</option>
                                <option value="open" <?php echo $status_filter === 'open' ? 'selected' : ''; ?>>Open Jobs</option>
                                <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>Closed Jobs</option>
                            </select>
                        </div>
                        
                        <div class="lg:col-span-2">
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search Jobs</label>
                            <div class="relative">
                                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Search by title, location, or skills..." class="block w-full rounded-lg border border-gray-300 pl-10 pr-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                    <button type="submit" class="text-blue-600 hover:text-blue-800 focus:outline-none">
                                        <i class="fas fa-arrow-right"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Jobs list -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                <?php if (mysqli_num_rows($jobs_result) > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applications</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Posted</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deadline</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while ($job = mysqli_fetch_assoc($jobs_result)): ?>
                                    <?php
                                    // Get application count
                                    $app_sql = "SELECT COUNT(*) as count FROM applications WHERE job_id = ?";
                                    $app_stmt = mysqli_prepare($conn, $app_sql);
                                    mysqli_stmt_bind_param($app_stmt, "i", $job['id']);
                                    mysqli_stmt_execute($app_stmt);
                                    $app_result = mysqli_stmt_get_result($app_stmt);
                                    $app_count = mysqli_fetch_assoc($app_result)['count'];
                                    
                                    // Calculate days remaining
                                    $deadline = new DateTime($job['application_deadline']);
                                    $today = new DateTime();
                                    $days_remaining = $today > $deadline ? 0 : $today->diff($deadline)->days;
                                    
                                    // Determine status class
                                    $status_class = '';
                                    $status_text = '';
                                    
                                    if ($job['status'] === 'open') {
                                        if ($days_remaining > 0) {
                                            $status_class = 'bg-green-100 text-green-800';
                                            $status_text = 'Open';
                                        } else {
                                            $status_class = 'bg-orange-100 text-orange-800';
                                            $status_text = 'Expired';
                                        }
                                    } else {
                                        $status_class = 'bg-gray-100 text-gray-800';
                                        $status_text = 'Closed';
                                    }
                                    ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                                    <i class="fas fa-briefcase text-blue-500"></i>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <a href="job_details.php?id=<?php echo $job['id']; ?>" class="hover:text-blue-600">
                                                            <?php echo htmlspecialchars($job['title']); ?>
                                                        </a>
                                                    </div>
                                                    <div class="text-sm text-gray-500 flex items-center">
                                                        <i class="fas fa-map-marker-alt mr-1 text-gray-400"></i>
                                                        <?php echo htmlspecialchars($job['location']); ?>
                                                        <span class="mx-2">•</span>
                                                        <?php echo ucfirst(str_replace('-', ' ', $job['job_type'])); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="view_applications.php?job_id=<?php echo $job['id']; ?>" class="text-blue-600 hover:text-blue-900">
                                                <?php echo $app_count; ?> application<?php echo $app_count !== 1 ? 's' : ''; ?>
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M d, Y', strtotime($job['posted_date'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">
                                                <?php echo date('M d, Y', strtotime($job['application_deadline'])); ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <?php if ($days_remaining > 0): ?>
                                                    <?php echo $days_remaining; ?> day<?php echo $days_remaining !== 1 ? 's' : ''; ?> remaining
                                                <?php else: ?>
                                                    <span class="text-red-500">Expired</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex items-center justify-end space-x-2">
                                                <a href="job_details.php?id=<?php echo $job['id']; ?>" class="text-blue-600 hover:text-blue-900" title="View Job">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit_job.php?id=<?php echo $job['id']; ?>" class="text-indigo-600 hover:text-indigo-900" title="Edit Job">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($job['status'] === 'open'): ?>
                                                    <a href="manage_jobs.php?action=close&id=<?php echo $job['id']; ?>" class="text-orange-600 hover:text-orange-900" title="Close Job" onclick="return confirm('Are you sure you want to close this job?')">
                                                        <i class="fas fa-times-circle"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="manage_jobs.php?action=open&id=<?php echo $job['id']; ?>" class="text-green-600 hover:text-green-900" title="Reopen Job" onclick="return confirm('Are you sure you want to reopen this job?')">
                                                        <i class="fas fa-check-circle"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="manage_jobs.php?action=delete&id=<?php echo $job['id']; ?>" class="text-red-600 hover:text-red-900" title="Delete Job" onclick="return confirm('Are you sure you want to delete this job? This action cannot be undone.')">
                                                    <i class="fas fa-trash-alt"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-700">
                                    Showing <span class="font-medium"><?php echo min(($page - 1) * $per_page + 1, $total_jobs); ?></span> to <span class="font-medium"><?php echo min($page * $per_page, $total_jobs); ?></span> of <span class="font-medium"><?php echo $total_jobs; ?></span> jobs
                                </div>
                                <div class="flex space-x-1">
                                    <?php if ($page > 1): ?>
                                        <a href="manage_jobs.php?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_term); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                            Previous
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <a href="manage_jobs.php?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search_term); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                            Next
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="p-6 text-center">
                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-100 text-blue-500 mb-4">
                            <i class="fas fa-search text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-1">No jobs found</h3>
                        <p class="text-gray-500 mb-4">
                            <?php if (!empty($search_term)): ?>
                                No jobs match your search criteria. Try different keywords or clear your search.
                            <?php elseif ($status_filter !== 'all'): ?>
                                No <?php echo $status_filter; ?> jobs found. Try a different filter.
                            <?php else: ?>
                                You haven't posted any jobs yet. Click the button below to post your first job.
                            <?php endif; ?>
                        </p>
                        <div class="mt-4">
                            <?php if (!empty($search_term) || $status_filter !== 'all'): ?>
                                <a href="manage_jobs.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 mr-3">
                                    <i class="fas fa-times mr-2"></i> Clear Filters
                                </a>
                            <?php endif; ?>
                            <a href="post_job.php" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                                <i class="fas fa-plus-circle mr-2"></i> Post New Job
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Job statistics card with abstract design -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8 relative">
                <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-br from-blue-50 via-transparent to-indigo-50 opacity-50"></div>
                <div class="absolute top-0 right-0 w-24 h-24 bg-blue-100 rounded-full -mt-12 -mr-12 opacity-50"></div>
                <div class="absolute bottom-0 left-0 w-32 h-32 bg-indigo-100 rounded-full -mb-16 -ml-16 opacity-50"></div>
                
                <div class="p-6 relative z-10">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-chart-pie text-blue-500 mr-2"></i> Job Statistics
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <?php
                        // Get job statistics
                        $stats_sql = "SELECT 
                            COUNT(*) as total_jobs,
                            SUM(CASE WHEN status = 'open' AND application_deadline >= CURDATE() THEN 1 ELSE 0 END) as active_jobs,
                            SUM(CASE WHEN status = 'closed' OR application_deadline < CURDATE() THEN 1 ELSE 0 END) as inactive_jobs,
                            SUM(views) as total_views
                            FROM jobs WHERE recruiter_id = ?";
                        $stats_stmt = mysqli_prepare($conn, $stats_sql);
                        mysqli_stmt_bind_param($stats_stmt, "i", $_SESSION['user_id']);
                        mysqli_stmt_execute($stats_stmt);
                        $stats_result = mysqli_stmt_get_result($stats_stmt);
                        $stats = mysqli_fetch_assoc($stats_result);
                        
                        // Get total applications
                        $apps_sql = "SELECT COUNT(*) as total_applications FROM applications a 
                                    JOIN jobs j ON a.job_id = j.id 
                                    WHERE j.recruiter_id = ?";
                        $apps_stmt = mysqli_prepare($conn, $apps_sql);
                        mysqli_stmt_bind_param($apps_stmt, "i", $_SESSION['user_id']);
                        mysqli_stmt_execute($apps_stmt);
                        $apps_result = mysqli_stmt_get_result($apps_stmt);
                        $apps = mysqli_fetch_assoc($apps_result);
                        ?>
                        
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border border-blue-200">
                            <div class="text-blue-600 text-sm font-medium mb-1">Total Jobs</div>
                            <div class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['total_jobs']); ?></div>
                        </div>
                        
                        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 border border-green-200">
                            <div class="text-green-600 text-sm font-medium mb-1">Active Jobs</div>
                            <div class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['active_jobs']); ?></div>
                        </div>
                        
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4 border border-purple-200">
                            <div class="text-purple-600 text-sm font-medium mb-1">Total Applications</div>
                            <div class="text-2xl font-bold text-gray-800"><?php echo number_format($apps['total_applications']); ?></div>
                        </div>
                        
                        <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-lg p-4 border border-indigo-200">
                            <div class="text-indigo-600 text-sm font-medium mb-1">Total Views</div>
                            <div class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['total_views']); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick tips -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-100">
                    <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-lightbulb text-yellow-500 mr-2"></i> Quick Tips
                    </h2>
                </div>
                
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center h-10 w-10 rounded-md bg-blue-100 text-blue-600">
                                    <i class="fas fa-pencil-alt"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Write Clear Job Descriptions</h3>
                                <p class="text-gray-500">
                                    Make sure your job descriptions are clear, concise, and easy to understand.
                                </p>
                            </div>
                        </div>
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center h-10 w-10 rounded-md bg-green-100 text-green-600">
                                    <i class="fas fa-chart-line"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Monitor Your Applications</h3>
                                <p class="text-gray-500">
                                    Keep track of your applications and respond to them promptly.
                                </p>
                            </div>
                        </div>
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center h-10 w-10 rounded-md bg-purple-100 text-purple-600">
                                    <i class="fas fa-users"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Build a Strong Network</h3>
                                <p class="text-gray-500">
                                    Reach out to potential candidates and build a strong network.
                                </p>
                            </div>
                        </div>
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <div class="flex items-center justify-center h-10 w-10 rounded-md bg-indigo-100 text-indigo-600">
                                    <i class="fas fa-globe"></i>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Expand Your Reach</h3>
                                <p class="text-gray-500">
                                    Expand your reach by posting jobs on multiple platforms.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php';?>
                            