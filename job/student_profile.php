<?php
require_once 'includes/functions.php';

// Check if user is logged in and is a student
if (!isLoggedIn() || !isStudent()) {
    redirect('login.php');
}

$success = '';
$error = '';

// Get student profile
$profile = getStudentProfile($_SESSION['user_id']);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $education = sanitize($_POST['education']);
    $skills = sanitize($_POST['skills']);
    $experience = sanitize($_POST['experience']);
    
    // Check if profile exists
    if ($profile) {
        // Update existing profile
        $sql = "UPDATE student_profiles SET 
                full_name = ?, 
                phone = ?, 
                address = ?, 
                education = ?, 
                skills = ?, 
                experience = ? 
                WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssssssi", $full_name, $phone, $address, $education, $skills, $experience, $_SESSION['user_id']);
    } else {
        // Create new profile
        $sql = "INSERT INTO student_profiles (user_id, full_name, phone, address, education, skills, experience) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "issssss", $_SESSION['user_id'], $full_name, $phone, $address, $education, $skills, $experience);
    }
      if (mysqli_stmt_execute($stmt)) {
        // Handle resume upload
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] == 0) {
            $resume_path = uploadFile($_FILES['resume'], 'uploads/resumes/', ['pdf', 'doc', 'docx']);
            if ($resume_path) {
                $sql = "UPDATE student_profiles SET resume_path = ? WHERE user_id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "si", $resume_path, $_SESSION['user_id']);
                mysqli_stmt_execute($stmt);
            } else {
                $error = "Failed to upload resume. Please ensure it's a PDF, DOC, or DOCX file.";
            }
        }
        
        // Handle profile picture upload
        if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
            $profile_pic_path = uploadFile($_FILES['profile_pic'], 'uploads/profile_pics/', ['jpg', 'jpeg', 'png']);
            if ($profile_pic_path) {
                $sql = "UPDATE student_profiles SET profile_pic = ? WHERE user_id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "si", $profile_pic_path, $_SESSION['user_id']);
                mysqli_stmt_execute($stmt);
            } else {
                $error = "Failed to upload profile picture. Please ensure it's a JPG, JPEG, or PNG file.";
            }
        }
        
        $success = "Profile updated successfully!";
        // Refresh profile data
        $profile = getStudentProfile($_SESSION['user_id']);
    } else {
        $error = "Error updating profile: " . mysqli_error($conn);
    }
}

// Include header
include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <div class="max-w-4xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">My Profile</h1>
            <a href="student_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
        
        <?php if (!empty($success)): ?>
            <div class="alert alert-success mb-6">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger mb-6">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header">
                <h2 class="text-xl font-semibold">Edit Profile</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <div class="flex flex-col md:flex-row items-center gap-6">
                                <div class="w-32 h-32 relative">
                                    <?php if (!empty($profile['profile_pic'])): ?>
                                        <img src="<?php echo $profile['profile_pic']; ?>" alt="Profile Picture" class="w-full h-full rounded-full object-cover" id="profile-preview">
                                    <?php else: ?>
                                        <div class="w-full h-full rounded-full bg-gray-300 flex items-center justify-center" id="profile-placeholder">
                                            <i class="fas fa-user text-gray-500 text-4xl"></i>
                                        </div>
                                        <img src="" alt="Profile Preview" class="w-full h-full rounded-full object-cover hidden" id="profile-preview">
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow">
                                    <label class="block text-gray-700 font-medium mb-2">Profile Picture</label>
                                    <input type="file" name="profile_pic" class="file-input" data-preview="#profile-preview" accept="image/*">
                                    <p class="text-sm text-gray-500 mt-1">Recommended size: 300x300 pixels (JPG, JPEG, PNG)</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label for="full_name" class="block text-gray-700 font-medium mb-2">Full Name</label>
                            <input type="text" id="full_name" name="full_name" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($profile['full_name'] ?? ''); ?>" required>
                        </div>
                        
                        <div>
                            <label for="phone" class="block text-gray-700 font-medium mb-2">Phone Number</label>
                            <input type="tel" id="phone" name="phone" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($profile['phone'] ?? ''); ?>">
                        </div>
                        
                        <div>
                            <label for="address" class="block text-gray-700 font-medium mb-2">Address</label>
                            <input type="text" id="address" name="address" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" value="<?php echo htmlspecialchars($profile['address'] ?? ''); ?>">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label for="education" class="block text-gray-700 font-medium mb-2">Education</label>
                            <textarea id="education" name="education" rows="4" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($profile['education'] ?? ''); ?></textarea>
                            <p class="text-sm text-gray-500 mt-1">Enter your educational background, including degrees, institutions, and graduation years.</p>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label for="skills" class="block text-gray-700 font-medium mb-2">Skills</label>
                            <textarea id="skills" name="skills" rows="3" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($profile['skills'] ?? ''); ?></textarea>
                            <p class="text-sm text-gray-500 mt-1">Enter your skills separated by commas (e.g., PHP, JavaScript, Project Management).</p>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label for="experience" class="block text-gray-700 font-medium mb-2">Work Experience</label>
                            <textarea id="experience" name="experience" rows="6" class="w-full px-4 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($profile['experience'] ?? ''); ?></textarea>
                            <p class="text-sm text-gray-500 mt-1">Describe your work experience, including job titles, companies, dates, and responsibilities.</p>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-gray-700 font-medium mb-2">Resume/CV</label>
                            <div class="flex items-center space-x-4">
                                <?php if (!empty($profile['resume_path'])): ?>
                                    <a href="<?php echo $profile['resume_path']; ?>" class="text-blue-600 hover:underline flex items-center" target="_blank">
                                        <i class="far fa-file-pdf mr-2"></i> View Current Resume
                                    </a>
                                <?php else: ?>
                                    <span class="text-gray-500">No resume uploaded yet</span>
                                <?php endif; ?>
                                <input type="file" name="resume" accept=".pdf,.doc,.docx">
                            </div>
                            <p class="text-sm text-gray-500 mt-1">Upload your resume in PDF, DOC, or DOCX format.</p>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <button type="submit" class="btn btn-primary">Save Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Preview profile picture before upload
document.addEventListener('DOMContentLoaded', function() {
    const profileInput = document.querySelector('input[name="profile_pic"]');
    const profilePreview = document.getElementById('profile-preview');
    const profilePlaceholder = document.getElementById('profile-placeholder');
    
    if (profileInput && profilePreview) {
        profileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    profilePreview.src = e.target.result;
                    profilePreview.classList.remove('hidden');
                    if (profilePlaceholder) {
                        profilePlaceholder.classList.add('hidden');
                    }
                }
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
});
</script>

<?php include 'includes/footer.php'; ?>