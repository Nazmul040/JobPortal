-- Create database
CREATE DATABASE IF NOT EXISTS job_portal;
USE job_portal;

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    role ENUM('student', 'recruiter') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create student_profiles table
CREATE TABLE IF NOT EXISTS student_profiles (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    education TEXT,
    skills TEXT,
    experience TEXT,
    resume_path VARCHAR(255),
    profile_pic VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create company_profiles table
CREATE TABLE IF NOT EXISTS company_profiles (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    company_name VARCHAR(100) NOT NULL,
    company_description TEXT,
    industry VARCHAR(100),
    website VARCHAR(255),
    location VARCHAR(100),
    logo_path VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create jobs table
CREATE TABLE IF NOT EXISTS jobs (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    recruiter_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    requirements TEXT,
    location VARCHAR(100),
    job_type ENUM('full-time', 'part-time', 'contract', 'internship') NOT NULL,
    salary VARCHAR(50),
    posted_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deadline DATE,
    status ENUM('open', 'closed') DEFAULT 'open',
    FOREIGN KEY (recruiter_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create applications table
CREATE TABLE IF NOT EXISTS applications (
    id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
    job_id INT NOT NULL,
    student_id INT NOT NULL,
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'reviewed', 'accepted', 'rejected') DEFAULT 'pending',
    cover_letter TEXT,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default users (password: password123)
-- Option 1: Delete existing users first (use with caution as it will delete all related data)
-- DELETE FROM users WHERE username IN ('student1', 'student2', 'recruiter1', 'recruiter2');

-- Option 2: Insert if not exists
INSERT IGNORE INTO users (username, email, password, role) VALUES 
('student1', 'student1@example.com', '$2y$10$8SOlFS4.RN1Tg7zHd/4TZeJ.zKBquLU4e4.0qiRmUKmri8LnzaEfK', 'student'),
('student2', 'student2@example.com', '$2y$10$8SOlFS4.RN1Tg7zHd/4TZeJ.zKBquLU4e4.0qiRmUKmri8LnzaEfK', 'student'),
('recruiter1', 'recruiter1@example.com', '$2y$10$8SOlFS4.RN1Tg7zHd/4TZeJ.zKBquLU4e4.0qiRmUKmri8LnzaEfK', 'recruiter'),
('recruiter2', 'recruiter2@example.com', '$2y$10$8SOlFS4.RN1Tg7zHd/4TZeJ.zKBquLU4e4.0qiRmUKmri8LnzaEfK', 'recruiter');

-- Option 3: Replace existing records
-- REPLACE INTO users (username, email, password, role) VALUES 
-- ('student1', 'student1@example.com', '$2y$10$8SOlFS4.RN1Tg7zHd/4TZeJ.zKBquLU4e4.0qiRmUKmri8LnzaEfK', 'student'),
-- ('student2', 'student2@example.com', '$2y$10$8SOlFS4.RN1Tg7zHd/4TZeJ.zKBquLU4e4.0qiRmUKmri8LnzaEfK', 'student'),
-- ('recruiter1', 'recruiter1@example.com', '$2y$10$8SOlFS4.RN1Tg7zHd/4TZeJ.zKBquLU4e4.0qiRmUKmri8LnzaEfK', 'recruiter'),
-- ('recruiter2', 'recruiter2@example.com', '$2y$10$8SOlFS4.RN1Tg7zHd/4TZeJ.zKBquLU4e4.0qiRmUKmri8LnzaEfK', 'recruiter');

-- Insert default student profiles
INSERT INTO student_profiles (user_id, full_name, phone, address, education, skills, experience) VALUES
(1, 'John Doe', '123-456-7890', '123 Student St, College Town', 'Bachelor of Computer Science, University of Technology (2018-2022)', 'PHP, JavaScript, HTML, CSS, MySQL, React, Node.js', 'Web Developer Intern at TechCorp (2021-2022)\n- Developed responsive web applications\n- Collaborated with senior developers on large projects'),
(2, 'Jane Smith', '987-654-3210', '456 Campus Ave, University City', 'Master of Business Administration, Business School (2019-2021)\nBachelor of Marketing, College of Business (2015-2019)', 'Marketing, Social Media Management, Content Creation, Data Analysis, Project Management', 'Marketing Assistant at BrandCo (2019-2021)\n- Managed social media campaigns\n- Analyzed marketing data and prepared reports');

-- Insert default company profiles
INSERT INTO company_profiles (user_id, company_name, company_description, industry, website, location) VALUES
(3, 'TechSolutions Inc.', 'TechSolutions is a leading software development company specializing in web and mobile applications. We create innovative solutions for businesses of all sizes.', 'Information Technology', 'https://techsolutions.example.com', 'New York, NY'),
(4, 'Global Marketing Group', 'Global Marketing Group is a full-service marketing agency helping brands connect with their audiences through strategic campaigns and creative content.', 'Marketing and Advertising', 'https://globalmarketing.example.com', 'Los Angeles, CA');

-- Insert sample jobs
INSERT INTO jobs (recruiter_id, title, description, requirements, location, job_type, salary, deadline) VALUES
(3, 'Full Stack Developer', 'We are looking for a skilled Full Stack Developer to join our team. You will be responsible for developing and maintaining web applications, working with both front-end and back-end technologies.', 'Bachelor\'s degree in Computer Science or related field\n3+ years of experience with PHP, JavaScript, and MySQL\nFamiliarity with modern frameworks (React, Laravel, etc.)\nStrong problem-solving skills', 'New York, NY', 'full-time', '$80,000 - $100,000', '2023-12-31'),
(3, 'Mobile App Developer', 'Join our mobile development team to create innovative iOS and Android applications. You will be involved in the entire app development lifecycle.', 'Experience with React Native or Flutter\nKnowledge of iOS and Android platforms\nStrong understanding of UI/UX principles\nAbility to write clean, maintainable code', 'Remote', 'full-time', '$75,000 - $95,000', '2023-12-15'),
(4, 'Digital Marketing Specialist', 'We are seeking a Digital Marketing Specialist to plan and execute marketing campaigns across various digital channels.', 'Bachelor\'s degree in Marketing or related field\nExperience with social media marketing and SEO\Knowledge of analytics tools (Google Analytics, etc.)\nExcellent communication skills', 'Los Angeles, CA', 'full-time', '$60,000 - $75,000', '2023-12-20'),
(4, 'Content Writer', 'Looking for a talented Content Writer to create engaging content for our clients across various industries.', 'Excellent writing and editing skills\nAbility to research and write about diverse topics\nSEO knowledge\nPortfolio of published work', 'Remote', 'part-time', '$25 - $35 per hour', '2023-12-25');

-- Insert sample applications
INSERT INTO applications (job_id, student_id, status, cover_letter) VALUES
(1, 1, 'pending', 'Dear Hiring Manager,\n\nI am excited to apply for the Full Stack Developer position at TechSolutions Inc. With my experience in web development and strong technical skills, I believe I would be a valuable addition to your team.\n\nThank you for considering my application.\n\nSincerely,\nJohn Doe'),
(3, 1, 'reviewed', 'Dear Hiring Manager,\n\nI am writing to express my interest in the Digital Marketing Specialist position at Global Marketing Group. My background in web development combined with my interest in marketing makes me a unique candidate for this role.\n\nThank you for your consideration.\n\nBest regards,\nJohn Doe'),
(2, 2, 'pending', 'Dear Hiring Manager,\n\nI am applying for the Mobile App Developer position. Although my background is in marketing, I have been learning mobile development and am eager to transition into this field.\n\nThank you for considering my application.\n\nSincerely,\nJane Smith'),
(3, 2, 'accepted', 'Dear Hiring Manager,\n\nI am excited to apply for the Digital Marketing Specialist position. With my education in marketing and relevant experience, I am confident that I can contribute effectively to your team.\n\nThank you for your consideration.\n\nBest regards,\nJane Smith');