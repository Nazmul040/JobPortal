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
    'sort' => isset($_GET['sort']) ? sanitize($_GET['sort']) : 'name_asc'
];

// Build query
$sql_conditions = ["1=1"]; // Always true condition to start with
$sql_params = [];
$sql_types = "";

// Add keyword filter
if (!empty($filters['keyword'])) {
    $sql_conditions[] = "(c.company_name LIKE ? OR c.industry LIKE ? OR c.description LIKE ?)";
    $keyword = "%" . $filters['keyword'] . "%";
    $sql_params[] = $keyword;
    $sql_params[] = $keyword;
    $sql_params[] = $keyword;
    $sql_types .= "sss";
}

// Add location filter
if (!empty($filters['location'])) {
    $sql_conditions[] = "c.location LIKE ?";
    $sql_params[] = "%" . $filters['location'] . "%";
    $sql_types .= "s";
}

// Build the WHERE clause
$where_clause = implode(' AND ', $sql_conditions);

// Determine sort order
$sort_clause = "c.company_name ASC"; // Default: name ascending
if ($filters['sort'] === 'name_desc') {
    $sort_clause = "c.company_name DESC";
} elseif ($filters['sort'] === 'jobs_desc') {
    $sort_clause = "job_count DESC, c.company_name ASC";
} elseif ($filters['sort'] === 'newest') {
    $sort_clause = "c.created_at DESC";
}

// Get total count for pagination
$count_sql = "SELECT COUNT(DISTINCT c.user_id) as total 
              FROM company_profiles c 
              WHERE $where_clause";
$stmt = mysqli_prepare($conn, $count_sql);
if (!empty($sql_params)) {
    mysqli_stmt_bind_param($stmt, $sql_types, ...$sql_params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$total_companies = mysqli_fetch_assoc($result)['total'];

// Pagination
$companies_per_page = 10;
$total_pages = ceil($total_companies / $companies_per_page);
$current_page = isset($_GET['page']) ? max(1, min($total_pages, (int)$_GET['page'])) : 1;
$offset = ($current_page - 1) * $companies_per_page;

// Get companies with job count
$sql = "SELECT c.*, u.email, u.username, 
        (SELECT COUNT(*) FROM jobs j WHERE j.recruiter_id = c.user_id AND j.status = 'open') as job_count,
        (SELECT COUNT(*) FROM jobs j WHERE j.recruiter_id = c.user_id) as total_jobs
        FROM company_profiles c
        JOIN users u ON c.user_id = u.id
        WHERE $where_clause
        GROUP BY c.user_id
        ORDER BY $sort_clause
        LIMIT $offset, $companies_per_page";

$stmt = mysqli_prepare($conn, $sql);
if (!empty($sql_params)) {
    mysqli_stmt_bind_param($stmt, $sql_types, ...$sql_params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$companies = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get locations for filter dropdown
$locations_sql = "SELECT DISTINCT location FROM company_profiles ORDER BY location";
$locations_result = mysqli_query($conn, $locations_sql);
$locations = [];
while ($row = mysqli_fetch_assoc($locations_result)) {
    if (!empty($row['location'])) {
        $locations[] = $row['location'];
    }
}

// Include header
include 'includes/header.php';
?>

<div class="bg-gray-50 py-8 min-h-screen relative">
    <!-- Abstract design elements -->
    <div class="absolute top-0 left-0 w-64 h-64 bg-gradient-to-br from-purple-100 to-blue-200 rounded-full opacity-50 -ml-32 -mt-32 transform rotate-45"></div>
    <div class="absolute bottom-0 right-0 w-96 h-96 bg-gradient-to-tr from-indigo-100 to-blue-200 rounded-full opacity-40 -mr-48 -mb-48"></div>
    
    <div class="container mx-auto px-4 relative z-10">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Explore Companies</h1>
            <p class="text-gray-600">Discover great companies that are hiring</p>
        </div>
        
        <!-- Search and filters -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
            <form action="companies.php" method="get" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="col-span-1">
                        <label for="keyword" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input type="text" id="keyword" name="keyword" value="<?php echo htmlspecialchars($filters['keyword']); ?>" placeholder="Company name, industry, or keywords" class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
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
                        <label for="sort" class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                        <select id="sort" name="sort" class="block w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            <option value="name_asc" <?php echo ($filters['sort'] === 'name_asc') ? 'selected' : ''; ?>>Company Name (A-Z)</option>
                            <option value="name_desc" <?php echo ($filters['sort'] === 'name_desc') ? 'selected' : ''; ?>>Company Name (Z-A)</option>
                            <option value="jobs_desc" <?php echo ($filters['sort'] === 'jobs_desc') ? 'selected' : ''; ?>>Most Open Jobs</option>
                            <option value="newest" <?php echo ($filters['sort'] === 'newest') ? 'selected' : ''; ?>>Newest First</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-6 rounded-lg transition duration-300 flex items-center justify-center">
                        <i class="fas fa-search mr-2"></i> Search
                    </button>
                    <a href="companies.php" class="bg-gray-200 hover:bg-gray-300 text-gray-700 py-2 px-6 rounded-lg transition duration-300 flex items-center justify-center">
                        <i class="fas fa-redo mr-2"></i> Reset
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Companies list -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
            <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-indigo-50 to-blue-50">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-800">Companies (<?php echo $total_companies; ?>)</h2>
                </div>
            </div>
            
            <?php if (empty($companies)): ?>
            <div class="p-8 text-center">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-building text-blue-500 text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-gray-800 mb-2">No Companies Found</h3>
                <p class="text-gray-600 mb-4">We couldn't find any companies matching your search criteria.</p>
                <a href="companies.php" class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-300">
                    <i class="fas fa-redo mr-2"></i> Clear Filters
                </a>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
                <?php foreach ($companies as $company): ?>
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition duration-300">
                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="flex-shrink-0 mr-4">
                                <?php if (!empty($company['logo_path'])): ?>
                                    <img src="<?php echo $company['logo_path']; ?>" alt="<?php echo htmlspecialchars($company['company_name']); ?>" class="w-16 h-16 object-contain rounded-lg border border-gray-200">
                                <?php else: ?>
                                    <div class="w-16 h-16 bg-blue-100 rounded-lg flex items-center justify-center border border-blue-200">
                                        <i class="fas fa-building text-blue-500 text-2xl"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800">
                                    <a href="company_profile.php?id=<?php echo $company['user_id']; ?>" class="hover:text-blue-600 transition duration-300">
                                        <?php echo htmlspecialchars($company['company_name']); ?>
                                    </a>
                                </h3>
                                <?php if (!empty($company['industry'])): ?>
                                <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($company['industry']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="space-y-2 mb-4">
                            <?php if (!empty($company['location'])): ?>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-map-marker-alt text-blue-500 w-5"></i>
                                <span><?php echo htmlspecialchars($company['location']); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($company['website'])): ?>
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-globe text-blue-500 w-5"></i>
                                <a href="<?php echo htmlspecialchars($company['website']); ?>" target="_blank" class="text-blue-600 hover:underline truncate">
                                    <?php echo htmlspecialchars(preg_replace('#^https?://#', '', $company['website'])); ?>
                                </a>
                            </div>
                            <?php endif; ?>
                            
                            <div class="flex items-center text-sm text-gray-600">
                                <i class="fas fa-briefcase text-blue-500 w-5"></i>
                                <span><?php echo $company['job_count']; ?> open job<?php echo $company['job_count'] != 1 ? 's' : ''; ?></span>
                            </div>
                        </div>
                        
                        <?php if (!empty($company['description'])): ?>
                        <div class="mb-4">
                            <p class="text-gray-600 text-sm line-clamp-2"><?php echo htmlspecialchars(substr($company['description'], 0, 120)) . (strlen($company['description']) > 120 ? '...' : ''); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex justify-between items-center">
                            <a href="company_profile.php?id=<?php echo $company['user_id']; ?>" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                View Profile
                            </a>
                            <?php if ($company['job_count'] > 0): ?>
                            <a href="jobs.php?keyword=<?php echo urlencode($company['company_name']); ?>" class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-3 py-1 rounded-lg transition duration-300">
                                View Jobs
                            </a>
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
        
        <!-- Why join section -->
        <div class="bg-gradient-to-r from-indigo-600 to-blue-700 rounded-xl shadow-lg overflow-hidden text-white">
            <div class="p-6 md:p-8 relative">
                <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full -mr-16 -mt-16 transform rotate-45"></div>
                <div class="absolute bottom-0 left-0 w-24 h-24 bg-white opacity-10 rounded-full -ml-12 -mb-12"></div>
                
                <h2 class="text-2xl font-bold mb-4 relative z-10">Why Join Our Platform?</h2>
                <div class="grid md:grid-cols-3 gap-6 relative z-10">
                    <div>
                        <h3 class="font-semibold text-lg mb-2 flex items-center">
                            <i class="fas fa-building mr-2"></i> For Companies
                        </h3>
                        <p class="text-blue-100">Reach qualified candidates, build your employer brand, and streamline your hiring process.</p>
                    </div>
                    <div>
                        <h3 class="font-semibold text-lg mb-2 flex items-center">
                            <i class="fas fa-user-graduate mr-2"></i> For Students
                        </h3>
                        <p class="text-blue-100">Discover opportunities with top employers, apply with ease, and launch your career.</p>
                    </div>
                    <div>
                        <h3 class="font-semibold text-lg mb-2 flex items-center">
                            <i class="fas fa-handshake mr-2"></i> For Everyone
                        </h3>
                        <p class="text-blue-100">Connect with a community of professionals, access resources, and grow your network.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>