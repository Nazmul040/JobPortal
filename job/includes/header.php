<?php
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JobPortal - Find Your Dream Job</title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="assets/img/favicon.ico" type="image/x-icon">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0061a7',
                        secondary: '#00a4e4',
                        accent: '#6cc24a',
                        dark: '#1a2b3c',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                }
            }
        }
    </script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/custom.css">
    <!-- Alpine.js for dropdowns and interactivity -->
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="font-sans bg-gray-50 text-gray-800">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <a href="index.php" class="flex items-center space-x-2">
                    <span class="text-primary text-2xl font-bold">JobPortal</span>
                </a>
                
                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="index.php" class="text-gray-700 hover:text-primary font-medium <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'text-primary' : ''; ?>">Home</a>
                    <a href="jobs.php" class="text-gray-700 hover:text-primary font-medium <?php echo (basename($_SERVER['PHP_SELF']) == 'jobs.php') ? 'text-primary' : ''; ?>">Browse Jobs</a>
                    <a href="companies.php" class="text-gray-700 hover:text-primary font-medium <?php echo (basename($_SERVER['PHP_SELF']) == 'companies.php') ? 'text-primary' : ''; ?>">Companies</a>
                    <?php if (isLoggedIn() && isRecruiter()): ?>
                    <a href="post_job.php" class="text-gray-700 hover:text-primary font-medium <?php echo (basename($_SERVER['PHP_SELF']) == 'post_job.php') ? 'text-primary' : ''; ?>">Post a Job</a>
                    <?php endif; ?>
                </div>
                
                <!-- Auth Buttons -->
                <div class="hidden md:flex items-center space-x-4">
                    <?php if (isLoggedIn()): ?>
                        <div class="relative group">
                            <button class="flex items-center space-x-2 text-gray-700 hover:text-primary font-medium">
                                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50">
                                <?php if (isStudent()): ?>
                                    <a href="student_dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Dashboard</a>
                                    <a href="student_profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Profile</a>
                                    <a href="my_applications.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">My Applications</a>
                                <?php elseif (isRecruiter()): ?>
                                    <a href="recruiter_dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Dashboard</a>
                                    <a href="company_profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Company Profile</a>
                                    <a href="manage_jobs.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Manage Jobs</a>
                                    <a href="manage_applications.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Applications</a>
                                <?php endif; ?>
                                <div class="border-t border-gray-100 my-1"></div>
                                <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Logout</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="px-4 py-2 text-primary border border-primary rounded-md hover:bg-primary hover:text-white transition duration-300">Login</a>
                        <a href="register.php" class="px-4 py-2 bg-primary text-white rounded-md hover:bg-secondary transition duration-300">Register</a>
                    <?php endif; ?>
                </div>
                
                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button id="mobile-menu-button" class="text-gray-500 hover:text-primary focus:outline-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Mobile Menu -->
            <div id="mobile-menu" class="md:hidden hidden pb-4">
                <a href="index.php" class="block py-2 text-gray-700 hover:text-primary">Home</a>
                <a href="jobs.php" class="block py-2 text-gray-700 hover:text-primary">Browse Jobs</a>
                <a href="companies.php" class="block py-2 text-gray-700 hover:text-primary">Companies</a>
                <?php if (isLoggedIn() && isRecruiter()): ?>
                <a href="post_job.php" class="block py-2 text-gray-700 hover:text-primary">Post a Job</a>
                <?php endif; ?>
                
                <div class="border-t border-gray-200 my-2"></div>
                
                <?php if (isLoggedIn()): ?>
                    <div class="py-2">
                        <p class="font-medium text-gray-700"><?php echo htmlspecialchars($_SESSION['username']); ?></p>
                        <?php if (isStudent()): ?>
                            <a href="student_dashboard.php" class="block py-2 text-gray-700 hover:text-primary">Dashboard</a>
                            <a href="student_profile.php" class="block py-2 text-gray-700 hover:text-primary">My Profile</a>
                            <a href="my_applications.php" class="block py-2 text-gray-700 hover:text-primary">My Applications</a>
                        <?php elseif (isRecruiter()): ?>
                            <a href="recruiter_dashboard.php" class="block py-2 text-gray-700 hover:text-primary">Dashboard</a>
                            <a href="company_profile.php" class="block py-2 text-gray-700 hover:text-primary">Company Profile</a>
                            <a href="manage_jobs.php" class="block py-2 text-gray-700 hover:text-primary">Manage Jobs</a>
                            <a href="manage_applications.php" class="block py-2 text-gray-700 hover:text-primary">Applications</a>
                        <?php endif; ?>
                        <a href="logout.php" class="block py-2 text-gray-700 hover:text-primary">Logout</a>
                    </div>
                <?php else: ?>
                    <div class="flex space-x-4 py-2">
                        <a href="login.php" class="px-4 py-2 text-primary border border-primary rounded-md hover:bg-primary hover:text-white transition duration-300">Login</a>
                        <a href="register.php" class="px-4 py-2 bg-primary text-white rounded-md hover:bg-secondary transition duration-300">Register</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main>