<?php
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('login.php');
}

// Initialize filters
$filters = [
    'keyword' => isset($_GET['keyword']) ? sanitize($_GET['keyword']) : '',
    'location' => isset($_GET['location']) ? sanitize($_GET['location']) : '',
    'job_type' => isset($_GET['job_type']) ? sanitize($_GET['job_type']) : '',
    'sort' => isset($_GET['sort']) ? sanitize($_GET['sort']) : 'newest'
];

// Build query
$sql_conditions = ["j.status = 'open'"];
$sql_params = [];
$sql_types = "";

// Add keyword filter
if (!empty($filters['keyword'])) {
    $sql_conditions[] = "(j.title LIKE ? OR j.description LIKE ? OR c.company_name LIKE ?)";
    $keyword = "%" . $filters['keyword'] . "%";
    $sql_params[] = $keyword;
    $sql_params[] = $keyword;
    $sql_params[] = $keyword;
    $sql_types .= "sss";
}

// Add location filter
if (!empty($filters['location'])) {
    $sql_conditions[] = "j.location LIKE ?";
    $sql_params[] = "%" . $filters['location'] . "%";
    $sql_types .= "s";
}

// Add job type filter
if (!empty($filters['job_type'])) {
    $sql_conditions[] = "j.job_type = ?";
    $sql_params[] = $filters['job_type'];
    $sql_types .= "s";
}

// Build the WHERE clause
$where_clause = implode(' AND ', $sql_conditions);

// Determine sort order
$sort_clause = "j.posted_date DESC"; // Default: newest first
if ($filters['sort'] === 'oldest') {
    $sort_clause = "j.posted_date ASC";
} elseif ($filters['sort'] === 'title_asc') {
    $sort_clause = "j.title ASC";
} elseif ($filters['sort'] === 'title_desc') {
    $sort_clause = "j.title DESC";
}

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM jobs j 
              JOIN company_profiles c ON j.recruiter_id = c.user_id 
              WHERE $where_clause";
$stmt = mysqli_prepare($conn, $count_sql);
if (!empty($sql_params)) {
    mysqli_stmt_bind_param($stmt, $sql_types, ...$sql_params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$total_jobs = mysqli_fetch_assoc($result)['total'];

// Pagination
$jobs_per_page = 10;
$total_pages = ceil($total_jobs / $jobs_per_page);
$current_page = isset($_GET['page']) ? max(1, min($total_pages, (int)$_GET['page'])) : 1;
$offset = ($current_page - 1) * $jobs_per_page;

// Get jobs
$sql = "SELECT DISTINCT j.*, c.company_name, c.logo_path 
        FROM jobs j 
        JOIN company_profiles c ON j.recruiter_id = c.user_id 
        WHERE $where_clause 
        ORDER BY $sort_clause 
        LIMIT $offset, $jobs_per_page";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($sql_params)) {
    mysqli_stmt_bind_param($stmt, $sql_types, ...$sql_params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$jobs = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get job types for filter dropdown
$job_types_sql = "SELECT DISTINCT job_type FROM jobs WHERE status = 'open' ORDER BY job_type";
$job_types_result = mysqli_query($conn, $job_types_sql);
$job_types = [];
while ($row = mysqli_fetch_assoc($job_types_result)) {
    $job_types[] = $row['job_type'];
}

// Get locations for filter dropdown
$locations_sql = "SELECT DISTINCT location FROM jobs WHERE status = 'open' ORDER BY location";
$locations_result = mysqli_query($conn, $locations_sql);
$locations = [];
while ($row = mysqli_fetch_assoc($locations_result)) {
    $locations[] = $row['location'];
}

// Check if user has saved jobs (for students only)
$saved_jobs = [];
if (isStudent()) {
    $check_table = mysqli_query($conn, "SHOW TABLES LIKE 'saved_jobs'");
    if (mysqli_num_rows($check_table) > 0) {
        $saved_sql = "SELECT job_id FROM saved_jobs WHERE student_id = ?";
        $stmt = mysqli_prepare($conn, $saved_sql);
        mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
        mysqli_stmt_execute($stmt);
        $saved_result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($saved_result)) {
            $saved_jobs[] = $row['job_id'];
        }
    }
}

// Include header
include 'includes/header.php';
?>

<div class="bg-gray-50 py-8 min-h-screen relative">
    <!-- Abstract design elements -->
    <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-blue-100 to-indigo-200 rounded-full opacity-50 -mr-32 -mt-32 transform rotate-45"></div>
    <div class="absolute bottom-0 left-0 w-96 h-96 bg-gradient-to-tr from-blue-100 to-purple-200 rounded-full opacity-40 -ml-48 -mb-48"></div>
    
    <div class="container mx-auto px-4 relative z-10">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Find Your Dream Job</h1>
            <p class="text-gray-600">Browse through our curated list of job opportunities</p>
        </div>
        
        <!-- Search and filters -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
            <form action="jobs.php" method="get" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="col-span-1 md:col-span-2">
                        <label for="keyword" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input type="text" id="keyword" name="keyword" value="<?php echo htmlspecialchars($filters['keyword']); ?>" placeholder="Job title, company, or keywords" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    
                    <div>
                        <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                        <select id="location" name="location" class="block w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Locations</option>
                            <?php foreach ($locations as $location): ?>
                                <option value="<?php echo htmlspecialchars($location); ?>" <?php echo ($filters['location'] === $location) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($location); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label for="job_type" class="block text-sm font-medium text-gray-700 mb-1">Job Type</label>
                        <select id="job_type" name="job_type" class="block w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Types</option>
                            <?php foreach ($job_types as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>" <?php echo ($filters['job_type'] === $type) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($type)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="w-full sm:w-auto">
                        <label for="sort" class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                        <select id="sort" name="sort" class="block w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <option value="newest" <?php echo ($filters['sort'] === 'newest') ? 'selected' : ''; ?>>Newest First</option>
                            <option value="oldest" <?php echo ($filters['sort'] === 'oldest') ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="title_asc" <?php echo ($filters['sort'] === 'title_asc') ? 'selected' : ''; ?>>Title (A-Z)</option>
                            <option value="title_desc" <?php echo ($filters['sort'] === 'title_desc') ? 'selected' : ''; ?>>Title (Z-A)</option>
                        </select>
                    </div>
                    
                    <div class="flex gap-3 w-full sm:w-auto">
                        <button type="submit" class="flex-grow sm:flex-grow-0 bg-blue-600 hover:bg-blue-700 text-white py-2 px-6 rounded-lg transition duration-300 flex items-center justify-center">
                            <i class="fas fa-search mr-2"></i> Search
                        </button>
                        <a href="jobs.php" class="flex-grow sm:flex-grow-0 bg-gray-200 hover:bg-gray-300 text-gray-700 py-2 px-6 rounded-lg transition duration-300 flex items-center justify-center">
                            <i class="fas fa-redo mr-2"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Job listings -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-indigo-50">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-800">Available Jobs (<?php echo $total_jobs; ?>)</h2>
                    <?php if (isStudent()): ?>
                    <a href="saved_jobs.php" class="text-blue-600 hover:text-blue-800 transition duration-300 flex items-center">
                        <i class="fas fa-bookmark mr-2"></i> View Saved Jobs
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (empty($jobs)): ?>
            <div class="p-8 text-center">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-search text-blue-500 text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-800 mb-2">No Jobs Found</h3>
                <p class="text-gray-600 mb-4">We couldn't find any jobs matching your search criteria.</p>
                <a href="jobs.php" class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-300">
                    <i class="fas fa-redo mr-2"></i> Clear Filters
                </a>
            </div>
            <?php else: ?>
            <div class="divide-y divide-gray-100">
                <?php foreach ($jobs as $job): ?>
                <div class="p-6 hover:bg-gray-50 transition duration-300">
                    <div class="flex flex-col md:flex-row gap-6">
                        <!-- Company logo -->
                        <div class="flex-shrink-0">
                            <?php if (!empty($job['logo_path'])): ?>
                                <img src="<?php echo $job['logo_path']; ?>" alt="<?php echo htmlspecialchars($job['company_name']); ?>" class="w-16 h-16 object-contain rounded-lg border border-gray-200">
                            <?php else: ?>
                                <div class="w-16 h-16 bg-blue-100 rounded-lg flex items-center justify-center border border-blue-200">
                                    <i class="fas fa-building text-blue-500 text-2xl"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Job details -->
                        <div class="flex-grow">
                            <h3 class="text-lg font-semibold text-gray-800 mb-1">
                                <a href="job_details.php?id=<?php echo $job['id']; ?>" class="hover:text-blue-600 transition duration-300"><?php echo htmlspecialchars($job['title']); ?></a>
                            </h3>
                            <p class="text-gray-600 mb-3"><?php echo htmlspecialchars($job['company_name']); ?></p>
                            
                            <div class="flex flex-wrap gap-2 mb-3">
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
                            </div>
                            
                            <div class="flex flex-wrap gap-2 text-sm text-gray-500">
                                <span class="flex items-center">
                                    <i class="fas fa-calendar-alt mr-1"></i> Posted: <?php echo date('M d, Y', strtotime($job['posted_date'])); ?>
                                </span>
                                <?php if (!empty($job['deadline'])): ?>
                                <span class="flex items-center">
                                    <i class="fas fa-hourglass-end mr-1"></i> Deadline: <?php echo date('M d, Y', strtotime($job['deadline'])); ?>
                                </span>
                                <?php endif; ?>
                                <?php if (isset($job['applications_count'])): ?>
                                <span class="flex items-center">
                                    <i class="fas fa-users mr-1"></i> Applications: <?php echo $job['applications_count']; ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Action buttons -->
                        <div class="flex-shrink-0 flex flex-row md:flex-col gap-2">
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
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="p-6 border-t border-gray-100 flex justify-center">
                <div class="flex flex-wrap gap-2">
                    <?php if ($current_page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($filters, ['page' => $current_page - 1])); ?>" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition duration-300">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $start_page + 4);
                    if ($end_page - $start_page < 4) {
                        $start_page = max(1, $end_page - 4);
                    }
                    ?>
                    
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <a href="?<?php echo http_build_query(array_merge($filters, ['page' => $i])); ?>" class="<?php echo ($i === $current_page) ? 'bg-blue-600 text-white' : 'bg-gray-200 hover:bg-gray-300 text-gray-700'; ?> px-4 py-2 rounded-lg transition duration-300">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>
                    
                    <?php if ($current_page < $total_pages): ?>
                    <a href="?<?php echo http_build_query(array_merge($filters, ['page' => $current_page + 1])); ?>" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg transition duration-300">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- Job search tips -->
        <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-xl shadow-lg overflow-hidden text-white">
            <div class="p-6 md:p-8 relative">
                <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-16 -mt-16"></div>
                <div class="absolute bottom-0 left-0 w-24 h-24 bg-white opacity-10 rounded-full -ml-12 -mb-12"></div>
                
                <h2 class="text-2xl font-bold mb-4 relative z-10">Job Search Tips</h2>
                <div class="grid md:grid-cols-2 gap-6 relative z-10">
                    <div>
                        <h3 class="font-semibold text-lg mb-2">Perfect Your Resume</h3>
                        <p class="text-blue-100">Tailor your resume to each job application, highlighting relevant skills and experience that match the job description.</p>
                    </div>
                    <div>
                        <h3 class="font-semibold text-lg mb-2">Prepare for Interviews</h3>
                        <p class="text-blue-100">Research the company, practice common interview questions, and prepare thoughtful questions to ask the interviewer.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>