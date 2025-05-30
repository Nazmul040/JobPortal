<?php
require_once 'includes/functions.php';

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    // Validate input
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Check if user exists
        $sql = "SELECT id, username, password, role FROM users WHERE username = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Password is correct, start a new session
                session_start();
                
                // Store data in session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                
                // Redirect user based on role
                if ($user['role'] === 'student') {
                    redirect('student_dashboard.php');
                } else {
                    redirect('recruiter_dashboard.php');
                }
            } else {
                $error = 'Invalid password.';
            }
        } else {
            $error = 'Username does not exist.';
        }
    }
}

// Include header
include 'includes/header.php';
?>

<div class="bg-gray-50 py-12 relative overflow-hidden">
    <!-- Abstract design elements -->
    <div class="absolute top-0 right-0 w-1/3 h-1/3 bg-purple-500 rounded-full opacity-10 transform translate-x-1/4 -translate-y-1/4"></div>
    <div class="absolute bottom-0 left-0 w-1/2 h-1/2 bg-blue-500 rounded-full opacity-10 transform -translate-x-1/4 translate-y-1/4"></div>
    <div class="absolute top-1/2 left-1/4 w-16 h-16 bg-yellow-500 rounded-full opacity-20"></div>
    <div class="absolute bottom-1/3 right-1/4 w-24 h-24 bg-green-500 rounded-full opacity-10"></div>
    
    <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden relative z-10 backdrop-filter backdrop-blur-sm bg-opacity-90">
        <div class="absolute top-0 right-0 w-32 h-32 bg-purple-100 rounded-bl-full opacity-50 z-0"></div>
        <div class="absolute bottom-0 left-0 w-24 h-24 bg-blue-100 rounded-tr-full opacity-50 z-0"></div>
        
        <div class="px-6 py-8 relative z-10">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Login to Your Account</h2>
            
            <?php if (!empty($error)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="mb-4">
                    <label for="username" class="block text-gray-700 font-medium mb-2">Username</label>
                    <input type="text" id="username" name="username" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                </div>
                
                <div class="mb-6">
                    <label for="password" class="block text-gray-700 font-medium mb-2">Password</label>
                    <div class="relative">
                        <input type="password" id="password" name="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                        <span class="absolute right-3 top-1/2 transform -translate-y-1/2 cursor-pointer">
                            <i id="togglePassword" class="fa fa-eye text-gray-500" onclick="togglePasswordVisibility('password', 'togglePassword')"></i>
                        </span>
                    </div>
                </div>
                
                <div class="mb-6">
                    <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-300 shadow">
                        Login
                    </button>
                </div>
                
                <p class="text-center text-gray-600">Don't have an account? <a href="register.php" class="text-blue-600 hover:text-blue-800 font-medium">Register here</a></p>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>