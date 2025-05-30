<?php
require_once 'includes/functions.php';

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = sanitize($_POST['role']);
    
    // Validate input
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($role)) {
        $error = 'Please fill in all fields.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if username already exists
        $sql = "SELECT id FROM users WHERE username = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = 'Username already exists. Please choose another one.';
        } else {
            // Check if email already exists
            $sql = "SELECT id FROM users WHERE email = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            
            if (mysqli_stmt_num_rows($stmt) > 0) {
                $error = 'Email already exists. Please use another one.';
            } else {
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user into database
                $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "ssss", $username, $email, $hashed_password, $role);
                
                if (mysqli_stmt_execute($stmt)) {
                    $user_id = mysqli_insert_id($conn);
                    
                    // Create profile based on role
                    if ($role === 'student') {
                        $sql = "INSERT INTO student_profiles (user_id, full_name) VALUES (?, ?)";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "is", $user_id, $username);
                        mysqli_stmt_execute($stmt);
                    } else {
                        $sql = "INSERT INTO company_profiles (user_id, company_name) VALUES (?, ?)";
                        $stmt = mysqli_prepare($conn, $sql);
                        mysqli_stmt_bind_param($stmt, "is", $user_id, $username);
                        mysqli_stmt_execute($stmt);
                    }
                    
                    $success = 'Registration successful! You can now <a href="login.php" class="text-blue-600 hover:underline">login</a>.';
                } else {
                    $error = 'Something went wrong. Please try again later.';
                }
            }
        }
    }
}

// Include header
include 'includes/header.php';
?>

<div class="bg-gray-50 py-12 relative overflow-hidden">
    <!-- Abstract design elements -->
    <div class="absolute top-0 left-0 w-1/3 h-1/3 bg-blue-500 rounded-full opacity-10 transform -translate-x-1/4 -translate-y-1/4"></div>
    <div class="absolute bottom-0 right-0 w-1/2 h-1/2 bg-indigo-500 rounded-full opacity-10 transform translate-x-1/4 translate-y-1/4"></div>
    <div class="absolute top-1/3 right-1/4 w-16 h-16 bg-green-500 rounded-full opacity-20"></div>
    <div class="absolute bottom-1/2 left-1/4 w-24 h-24 bg-yellow-500 rounded-full opacity-10"></div>
    
    <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden relative z-10 backdrop-filter backdrop-blur-sm bg-opacity-90 md:max-w-lg">
        <div class="absolute top-0 left-0 w-32 h-32 bg-blue-100 rounded-br-full opacity-50 z-0"></div>
        <div class="absolute bottom-0 right-0 w-24 h-24 bg-indigo-100 rounded-tl-full opacity-50 z-0"></div>
        
        <div class="px-6 py-8 relative z-10">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Create an Account</h2>
            
            <?php if (!empty($error)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                    <?php echo $success; ?>
                </div>
            <?php else: ?>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <div class="mb-4">
                        <label for="username" class="block text-gray-700 font-medium mb-2">Username</label>
                        <input type="text" id="username" name="username" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="email" class="block text-gray-700 font-medium mb-2">Email</label>
                        <input type="email" id="email" name="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="block text-gray-700 font-medium mb-2">Password</label>
                        <div class="relative">
                            <input type="password" id="password" name="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                            <span class="absolute right-3 top-1/2 transform -translate-y-1/2 cursor-pointer">
                                <i id="togglePassword" class="fa fa-eye text-gray-500" onclick="togglePasswordVisibility('password', 'togglePassword')"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="confirm_password" class="block text-gray-700 font-medium mb-2">Confirm Password</label>
                        <div class="relative">
                            <input type="password" id="confirm_password" name="confirm_password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                            <span class="absolute right-3 top-1/2 transform -translate-y-1/2 cursor-pointer">
                                <i id="toggleConfirmPassword" class="fa fa-eye text-gray-500" onclick="togglePasswordVisibility('confirm_password', 'toggleConfirmPassword')"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-gray-700 font-medium mb-2">Register as</label>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="role" value="student" class="form-radio text-blue-600" checked>
                                <span class="ml-2 text-gray-700">Student/Job Seeker</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="role" value="recruiter" class="form-radio text-blue-600">
                                <span class="ml-2 text-gray-700">Recruiter</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-300 shadow">
                            Create Account
                        </button>
                    </div>
                    
                    <p class="text-center text-gray-600">Already have an account? <a href="login.php" class="text-blue-600 hover:text-blue-800 font-medium">Login here</a></p>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>