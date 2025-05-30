<?php
require_once 'includes/functions.php';

// Check if user is logged in and is a recruiter
if (!isLoggedIn() || !isRecruiter()) {
    setAlert('error', 'You must be logged in as a recruiter to download resumes.');
    redirect('login.php');
}

// Validate application ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setAlert('error', 'Invalid application ID.');
    redirect('manage_applications.php');
}
$application_id = (int)$_GET['id'];

// Fetch resume path and check permission
$sql = "SELECT a.resume_path, sp.full_name, j.title AS job_title
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        JOIN users u ON a.student_id = u.id
        JOIN student_profiles sp ON u.id = sp.user_id
        WHERE a.id = ? AND j.recruiter_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $application_id, $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$application = mysqli_fetch_assoc($result);

if (!$application) {
    setAlert('error', 'Resume not found or you do not have permission to download it.');
    redirect('manage_applications.php');
}

$resume_path = $application['resume_path'];
$full_name = $application['full_name'];
$job_title = $application['job_title'];

// Check if file exists
if (empty($resume_path) || !file_exists($resume_path)) {
    include 'includes/header.php';
    ?>
    <div class="bg-gray-50 py-8 min-h-screen">
        <div class="container mx-auto px-4">
            <div class="max-w-xl mx-auto bg-white rounded-xl shadow-sm border border-gray-100 p-8 mt-12 text-center">
                <div class="mb-4">
                    <i class="fas fa-exclamation-triangle text-red-400 text-4xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Resume Not Found</h2>
                <p class="text-gray-600 mb-6">The resume file for this application could not be found.</p>
                <a href="manage_applications.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300 shadow inline-flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Applications
                </a>
            </div>
        </div>
    </div>
    <?php
    include 'includes/footer.php';
    exit;
}

// If ?download=1 is set, force download
if (isset($_GET['download']) && $_GET['download'] == '1') {
    $filename = basename($resume_path);
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $download_name = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $full_name . '_' . $job_title) . '.' . $ext;

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $download_name . '"');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($resume_path));
    readfile($resume_path);
    exit;
}

include 'includes/header.php';
?>

<div class="bg-gray-50 py-8 min-h-screen">
    <div class="container mx-auto px-4">
        <div class="max-w-xl mx-auto bg-white rounded-xl shadow-sm border border-gray-100 p-8 mt-12 text-center">
            <div class="mb-4">
                <i class="fas fa-file-download text-blue-500 text-4xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Download Resume</h2>
            <p class="text-gray-600 mb-6">
                Download the resume for <span class="font-semibold"><?php echo htmlspecialchars($full_name); ?></span>
                (applied for <span class="font-semibold"><?php echo htmlspecialchars($job_title); ?></span>).
            </p>
            <a href="download_resume.php?id=<?php echo $application_id; ?>&download=1"
               class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-6 rounded-lg transition duration-300 shadow inline-flex items-center mb-4">
                <i class="fas fa-download mr-2"></i> Download Resume
            </a>
            <br>
            <a href="manage_applications.php" class="text-blue-600 hover:underline inline-flex items-center mt-2">
                <i class="fas fa-arrow-left mr-2"></i> Back to Applications
            </a>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>