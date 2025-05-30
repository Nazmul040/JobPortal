<?php
require_once 'includes/functions.php';

// Check if user is logged in and is a student
if (!isLoggedIn()) {
    redirect('login.php');
}

if (!isStudent()) {
    setAlert('error', 'Access denied. Only students can view saved jobs.');
    redirect('index.php');
}

// Handle unsave job action
if (isset($_POST['unsave_job']) && !empty($_POST['job_id'])) {
    $job_id = (int)$_POST['job_id'];
    
    $sql = "DELETE FROM saved_jobs WHERE job_id = ? AND student_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $job_id, $_SESSION['user_id']);
    
    if (mysqli_stmt_execute($stmt)) {
        setAlert('success', 'Job removed from saved jobs.');
    } else {
        setAlert('error', 'Failed to remove job from saved jobs.');
    }
}

// Get all saved jobs for the current student
$sql = "SELECT j.id, j.title, j.location, j.job_type, j.salary, j.posted_date, j.deadline, 
               c.company_name, c.logo_path, MAX(s.saved_date) as saved_date
        FROM saved_jobs s
        JOIN jobs j ON s.job_id = j.id
        JOIN company_profiles c ON j.recruiter_id = c.user_id
        WHERE s.student_id = ?
        GROUP BY j.id
        ORDER BY MAX(s.saved_date) DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$saved_jobs = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Include header
include 'includes/header.php';
?>

<div class="bg-gray-50 py-8 min-h-screen">
    <div class="container mx-auto px-4">
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800 mb-2">Saved Jobs</h1>
                <p class="text-gray-600">Manage your saved job listings</p>
            </div>
            <div class="mt-4 md:mt-0">
                <a href="jobs.php" class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-300">
                    <i class="fas fa-search mr-2"></i> Browse More Jobs
                </a>
            </div>
        </div>
        
        <?php if (empty($saved_jobs)): ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-bookmark text-blue-500 text-2xl"></i>
                </div>
                <h2 class="text-xl font-semibold text-gray-800 mb-2">No Saved Jobs</h2>
                <p class="text-gray-600 mb-6">You haven't saved any jobs yet. Browse available jobs and save the ones you're interested in.</p>
                <a href="jobs.php" class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition duration-300">
                    <i class="fas fa-search mr-2"></i> Browse Jobs
                </a>
            </div>
        <?php else: ?>
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100 bg-gradient-to-r from-blue-50 to-indigo-50">
                    <div class="flex justify-between items-center">
                        <h2 class="text-xl font-semibold text-gray-800">Your Saved Jobs (<?php echo count($saved_jobs); ?>)</h2>
                    </div>
                </div>
                
                <div class="divide-y divide-gray-100">
                    <?php foreach ($saved_jobs as $job): ?>
                        <div class="p-6">
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
                                        <span class="flex items-center">
                                            <i class="fas fa-bookmark mr-1"></i> Saved: <?php echo date('M d, Y', strtotime($job['saved_date'])); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <!-- Action buttons -->
                                <div class="flex-shrink-0 flex flex-row md:flex-col gap-2">
                                    <a href="job_details.php?id=<?php echo $job['id']; ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition duration-300 flex items-center justify-center">
                                        <i class="fas fa-eye mr-2"></i> View
                                    </a>
                                    <form method="post" onsubmit="return confirm('Are you sure you want to remove this job from your saved list?');">
                                        <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                        <button type="submit" name="unsave_job" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm transition duration-300 flex items-center justify-center w-full">
                                            <i class="fas fa-trash-alt mr-2"></i> Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>