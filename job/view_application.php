<?php
require_once 'includes/functions.php';

// Check if user is logged in and is a recruiter
if (!isLoggedIn() || !isRecruiter()) {
    setAlert('error', 'You must be logged in as a recruiter to view applications.');
    redirect('login.php');
}

// Validate application ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    setAlert('error', 'Invalid application ID.');
    redirect('manage_applications.php');
}
$application_id = (int)$_GET['id'];

// Fetch application details
$sql = "SELECT a.*, 
               j.title AS job_title, j.location, j.job_type, j.description AS job_description,
               sp.full_name, sp.phone, sp.address, sp.education, sp.skills, sp.experience, sp.resume_path, sp.profile_pic,
               u.email
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
    setAlert('error', 'Application not found or you do not have permission to view it.');
    redirect('manage_applications.php');
}

include 'includes/header.php';
?>

<div class="bg-gray-50 py-8 min-h-screen">
    <div class="container mx-auto px-4">
        <!-- Header section styled like my_applications.php -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Application Details</h1>
                <p class="text-gray-600">Review the details of this application</p>
            </div>
            <a href="manage_applications.php" class="mt-4 md:mt-0 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-300 shadow flex items-center justify-center">
                <i class="fas fa-arrow-left mr-2"></i> Back to Applications
            </a>
        </div>

        <!-- Card-style container for application details -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 max-w-3xl mx-auto mb-8">
            <div class="flex items-center mb-6">
                <?php if (!empty($application['profile_pic'])): ?>
                    <img src="<?php echo htmlspecialchars($application['profile_pic']); ?>" alt="Profile Picture" class="h-16 w-16 rounded-full object-cover">
                <?php else: ?>
                    <div class="h-16 w-16 rounded-full bg-blue-100 flex items-center justify-center">
                        <span class="text-blue-500 font-bold text-2xl">
                            <?php echo strtoupper(substr($application['full_name'], 0, 1)); ?>
                        </span>
                    </div>
                <?php endif; ?>
                <div class="ml-4">
                    <h2 class="text-2xl font-bold text-gray-800"><?php echo htmlspecialchars($application['full_name']); ?></h2>
                    <p class="text-gray-600"><?php echo htmlspecialchars($application['email']); ?></p>
                </div>
            </div>
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-700 mb-2">Applied for: <?php echo htmlspecialchars($application['job_title']); ?></h3>
                <p class="text-gray-500 mb-1"><strong>Location:</strong> <?php echo htmlspecialchars($application['location']); ?></p>
                <p class="text-gray-500 mb-1"><strong>Job Type:</strong> <?php echo htmlspecialchars(ucfirst($application['job_type'])); ?></p>
                <p class="text-gray-500"><strong>Applied on:</strong> <?php echo date('M d, Y', strtotime($application['application_date'])); ?></p>
            </div>
            <div class="mb-6">
                <h4 class="text-md font-semibold text-gray-700 mb-1">Applicant Details</h4>
                <ul class="text-gray-600">
                    <li><strong>Phone:</strong> <?php echo htmlspecialchars($application['phone']); ?></li>
                    <li><strong>Address:</strong> <?php echo htmlspecialchars($application['address']); ?></li>
                    <li><strong>Education:</strong> <?php echo nl2br(htmlspecialchars($application['education'])); ?></li>
                    <li><strong>Skills:</strong> <?php echo htmlspecialchars($application['skills']); ?></li>
                    <li><strong>Experience:</strong> <?php echo nl2br(htmlspecialchars($application['experience'])); ?></li>
                </ul>
            </div>
            <div class="mb-6">
                <h4 class="text-md font-semibold text-gray-700 mb-1">Cover Letter</h4>
                <div class="bg-gray-100 rounded p-4 text-gray-700 whitespace-pre-line">
                    <?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?>
                </div>
            </div>
            <div class="mb-6">
                <h4 class="text-md font-semibold text-gray-700 mb-1">Resume</h4>
                <?php if (!empty($application['resume_path'])): ?>
                    <a href="<?php echo htmlspecialchars($application['resume_path']); ?>" target="_blank" class="text-blue-600 hover:underline">Download Resume</a>
                <?php else: ?>
                    <span class="text-gray-500">No resume uploaded.</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>