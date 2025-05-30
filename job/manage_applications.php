<?php
require_once 'includes/functions.php';

// Check if user is logged in and is a recruiter
if (!isLoggedIn() || !isRecruiter()) {
    setAlert('error', 'You must be logged in as a recruiter to manage applications.');
    redirect('login.php');
}

// Set up filtering and pagination
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';
$job_filter = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
$search_term = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Build the query based on filters
$where_clauses = ["j.recruiter_id = ?"];
$params = [$_SESSION['user_id']];
$types = "i";

if ($status_filter !== 'all') {
    $where_clauses[] = "a.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($job_filter > 0) {
    $where_clauses[] = "j.id = ?";
    $params[] = $job_filter;
    $types .= "i";
}

if (!empty($search_term)) {
    $where_clauses[] = "(sp.full_name LIKE ? OR j.title LIKE ?)";
    $search_param = "%$search_term%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

// Always build $where_clause before using it
$where_clause = count($where_clauses) > 0 ? implode(' AND ', $where_clauses) : '1';

// Get total applications count for pagination
$count_sql = "SELECT COUNT(*) as total
              FROM applications a
              JOIN jobs j ON a.job_id = j.id
              JOIN users u ON a.student_id = u.id
              JOIN student_profiles sp ON u.id = sp.user_id
              WHERE $where_clause";
$count_stmt = mysqli_prepare($conn, $count_sql);
mysqli_stmt_bind_param_array($count_stmt, $types, $params);
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$total_applications = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_applications / $per_page);

// Update the queries to join student_profiles and select the correct fields
$sql = "SELECT a.*, j.title as job_title, j.location, j.job_type,
        sp.full_name, u.email, sp.profile_pic,
        (SELECT COUNT(*) FROM applications WHERE job_id = j.id) as total_applicants
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        JOIN users u ON a.student_id = u.id
        JOIN student_profiles sp ON u.id = sp.user_id
        WHERE $where_clause
        ORDER BY a.application_date DESC
        LIMIT ?, ?";

$stmt = mysqli_prepare($conn, $sql);
$params[] = $offset;
$params[] = $per_page;
$types .= "ii";
mysqli_stmt_bind_param_array($stmt, $types, $params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get jobs for filter dropdown
$jobs_sql = "SELECT id, title FROM jobs WHERE recruiter_id = ? ORDER BY posted_date DESC";
$jobs_stmt = mysqli_prepare($conn, $jobs_sql);
mysqli_stmt_bind_param($jobs_stmt, "i", $_SESSION['user_id']);
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

include 'includes/header.php';
?>

<div class="bg-gray-50 py-8 min-h-screen">
    <div class="container mx-auto px-4">
        <div class="max-w-6xl mx-auto">
            <!-- Abstract design elements (copied from manage_jobs.php) -->
            <div class="absolute top-0 right-0 -mt-10 -mr-10 hidden lg:block">
                <div class="w-64 h-64 bg-gradient-to-br from-blue-400/20 to-indigo-500/20 rounded-full blur-3xl"></div>
            </div>
            <div class="absolute bottom-0 left-0 -mb-10 -ml-10 hidden lg:block">
                <div class="w-72 h-72 bg-gradient-to-tr from-purple-400/20 to-pink-500/20 rounded-full blur-3xl"></div>
            </div>

            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 relative">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Manage Applications</h1>
                    <p class="text-gray-600 mt-1">Review and manage applications for all your job postings</p>
                </div>
            </div>

            <!-- Filters and search (styled like manage_jobs.php) -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
                <div class="p-4 sm:p-6">
                    <form action="manage_applications.php" method="get" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label for="job_id" class="block text-sm font-medium text-gray-700 mb-1">Filter by Job</label>
                            <select id="job_id" name="job_id" class="block w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="0">All Jobs</option>
                                <?php while ($job = mysqli_fetch_assoc($jobs_result)): ?>
                                    <option value="<?php echo $job['id']; ?>" <?php echo $job_filter == $job['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($job['title']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Filter by Status</label>
                            <select id="status" name="status" class="block w-full rounded-lg border border-gray-300 px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="reviewed" <?php echo $status_filter === 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                <option value="accepted" <?php echo $status_filter === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                                <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                            </select>
                        </div>
                        <div class="lg:col-span-2">
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search Applications</label>
                            <div class="relative">
                                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search_term); ?>"
                                       placeholder="Search by applicant name or job title..."
                                       class="block w-full rounded-lg border border-gray-300 pl-10 pr-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6" >
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <div class="overflow-x-auto"  style="min-height: 600px;">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applicant</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applied</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" >
                                <?php while ($application = mysqli_fetch_assoc($result)): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-10 w-10">
                                                    <?php if (!empty($application['profile_pic'])): ?>
                                                        <img class="h-10 w-10 rounded-full object-cover" 
                                                             src="<?php echo $application['profile_pic']; ?>" 
                                                             alt="<?php echo htmlspecialchars($application['full_name']); ?>">
                                                    <?php else: ?>
                                                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                            <span class="text-blue-500 font-medium text-lg">
                                                                <?php echo strtoupper(substr($application['full_name'], 0, 1)); ?>
                                                            </span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        <?php echo htmlspecialchars($application['full_name']); ?>
                                                    </div>
                                                    <div class="text-sm text-gray-500">
                                                        <?php echo htmlspecialchars($application['email']); ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm text-gray-900">
                                                <?php echo htmlspecialchars($application['job_title']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                <?php echo htmlspecialchars($application['location']); ?> • 
                                                <?php echo ucfirst(str_replace('-', ' ', $application['job_type'])); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $status_classes = [
                                                'pending' => 'bg-yellow-100 text-yellow-800',
                                                'reviewed' => 'bg-blue-100 text-blue-800',
                                                'accepted' => 'bg-green-100 text-green-800',
                                                'rejected' => 'bg-red-100 text-red-800'
                                            ];
                                            $status_class = $status_classes[$application['status']] ?? 'bg-gray-100 text-gray-800';
                                            ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                                <?php echo ucfirst($application['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M d, Y', strtotime($application['application_date'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex items-center justify-end space-x-2">
                                                <a href="view_application.php?id=<?php echo $application['id']; ?>" 
                                                   class="text-blue-600 hover:text-blue-900" title="View Application">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="download_resume.php?id=<?php echo $application['id']; ?>" 
                                                   class="text-green-600 hover:text-green-900" title="Download Resume">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <!-- Dropdown menu for status actions -->
                                                <div x-data="{ open: false }" class="relative">
                                                    <button @click="open = !open" type="button"
                                                            class="text-indigo-600 hover:text-indigo-900 focus:outline-none"
                                                            title="Update Status">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <div x-show="open"
                                                         @click.away="open = false"
                                                         class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-gray-100 focus:outline-none z-10">
                                                        <div class="py-1">
                                                            <a href="update_application_status.php?id=<?php echo $application['id']; ?>&status=reviewed" 
                                                               class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                                Mark as Reviewed
                                                            </a>
                                                            <a href="update_application_status.php?id=<?php echo $application['id']; ?>&status=accepted" 
                                                               class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                                Accept
                                                            </a>
                                                            <a href="update_application_status.php?id=<?php echo $application['id']; ?>&status=rejected" 
                                                               class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                                Reject
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if ($total_pages > 1): ?>
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-gray-700">
                                    Showing <span class="font-medium"><?php echo min(($page - 1) * $per_page + 1, $total_applications); ?></span> 
                                    to <span class="font-medium"><?php echo min($page * $per_page, $total_applications); ?></span> 
                                    of <span class="font-medium"><?php echo $total_applications; ?></span> applications
                                </div>
                                <div class="flex space-x-1">
                                    <?php if ($page > 1): ?>
                                        <a href="manage_applications.php?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&job_id=<?php echo $job_filter; ?>&search=<?php echo urlencode($search_term); ?>" 
                                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                            Previous
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <a href="manage_applications.php?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&job_id=<?php echo $job_filter; ?>&search=<?php echo urlencode($search_term); ?>" 
                                           class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
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
                            <i class="fas fa-inbox text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-1">No applications found</h3>
                        <p class="text-gray-500">
                            <?php if (!empty($search_term)): ?>
                                No applications match your search criteria. Try different keywords or clear your search.
                            <?php elseif ($status_filter !== 'all'): ?>
                                No applications with status "<?php echo $status_filter; ?>" found. Try a different filter.
                            <?php elseif ($job_filter > 0): ?>
                                No applications for this job yet.
                            <?php else: ?>
                                You haven't received any applications yet.
                            <?php endif; ?>
                        </p>
                        <?php if (!empty($search_term) || $status_filter !== 'all' || $job_filter > 0): ?>
                            <div class="mt-4">
                                <a href="manage_applications.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                    <i class="fas fa-times mr-2"></i> Clear Filters
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>