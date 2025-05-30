</main>
    <!-- Footer -->
    <footer class="bg-dark text-white pt-16 pb-6">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div>
                    <h5 class="text-xl font-bold mb-4">JobPortal</h5>
                    <p class="text-gray-300 mb-6">Connecting talented individuals with great opportunities. Find your dream job or hire the perfect candidate with our platform.</p>
                    <div class="flex space-x-3">
                        <a href="#" class="social-icon">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                        <a href="#" class="social-icon">
                            <i class="fab fa-instagram"></i>
                        </a>
                    </div>
                </div>
                
                <div>
                    <h5 class="text-xl font-bold mb-4">For Job Seekers</h5>
                    <ul class="space-y-3">
                        <li><a href="jobs.php" class="text-gray-300 hover:text-white transition">Browse Jobs</a></li>
                        <li><a href="companies.php" class="text-gray-300 hover:text-white transition">Browse Companies</a></li>
                        <li><a href="register.php?role=student" class="text-gray-300 hover:text-white transition">Create Account</a></li>
                        <li><a href="login.php" class="text-gray-300 hover:text-white transition">Login</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-white transition">Career Advice</a></li>
                    </ul>
                </div>
                
                <div>
                    <h5 class="text-xl font-bold mb-4">For Employers</h5>
                    <ul class="space-y-3">
                        <li><a href="register.php?role=recruiter" class="text-gray-300 hover:text-white transition">Create Account</a></li>
                        <li><a href="post_job.php" class="text-gray-300 hover:text-white transition">Post a Job</a></li>
                        <li><a href="manage_jobs.php" class="text-gray-300 hover:text-white transition">Manage Jobs</a></li>
                        <li><a href="manage_applications.php" class="text-gray-300 hover:text-white transition">Browse Applications</a></li>
                        <li><a href="#" class="text-gray-300 hover:text-white transition">Pricing Plans</a></li>
                    </ul>
                </div>
                
                <div>
                    <h5 class="text-xl font-bold mb-4">Contact Us</h5>
                    <ul class="space-y-3 text-gray-300">
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt mt-1 mr-3"></i>
                            <span>123 Job Street, Employment City</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-phone mt-1 mr-3"></i>
                            <span>(123) 456-7890</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-envelope mt-1 mr-3"></i>
                            <span>info@jobportal.com</span>
                        </li>
                    </ul>
                    <div class="mt-6">
                        <a href="#" class="px-4 py-2 border border-white text-white rounded-md hover:bg-white hover:text-dark transition duration-300">Contact Support</a>
                    </div>
                </div>
            </div>
            
            <div class="border-t border-gray-700 mt-12 pt-6">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <p class="text-gray-400 mb-4 md:mb-0">&copy; <?php echo date('Y'); ?> JobPortal. All rights reserved.</p>
                    <div class="flex space-x-6">
                        <a href="#" class="text-gray-400 hover:text-white transition">Privacy Policy</a>
                        <a href="#" class="text-gray-400 hover:text-white transition">Terms of Service</a>
                        <a href="#" class="text-gray-400 hover:text-white transition">FAQ</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        });
    </script>
</body>
</html>