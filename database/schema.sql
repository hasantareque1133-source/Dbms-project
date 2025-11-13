-- University Networking Portal Database Schema
-- Created for DBMS Project

DROP DATABASE IF EXISTS university_portal;
CREATE DATABASE university_portal;
USE university_portal;

-- Users table (students, admins, moderators)
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    institutional_id VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'student', 'moderator') DEFAULT 'student',
    department VARCHAR(100),
    year INT,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_department (department)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Clubs table
CREATE TABLE clubs (
    club_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    moderator_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (moderator_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_moderator (moderator_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Opportunities table (jobs, internships, research)
CREATE TABLE opportunities (
    opportunity_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    type ENUM('job', 'internship', 'research') NOT NULL,
    description TEXT NOT NULL,
    company_organization VARCHAR(200),
    location VARCHAR(200),
    posted_by INT NOT NULL,
    deadline DATE,
    requirements TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (posted_by) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_type (type),
    INDEX idx_posted_by (posted_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Events table (workshops, club events)
CREATE TABLE events (
    event_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    club_id INT,
    event_date DATETIME NOT NULL,
    venue VARCHAR(200),
    capacity INT DEFAULT 0,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id) REFERENCES clubs(club_id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_club (club_id),
    INDEX idx_created_by (created_by),
    INDEX idx_event_date (event_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Event registrations (many-to-many relationship)
CREATE TABLE event_registrations (
    registration_id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    student_id INT NOT NULL,
    registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(event_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_registration (event_id, student_id),
    INDEX idx_event (event_id),
    INDEX idx_student (student_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Alumni table
CREATE TABLE alumni (
    alumni_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    department VARCHAR(100) NOT NULL,
    graduation_year INT NOT NULL,
    profession VARCHAR(200),
    company VARCHAR(200),
    contact_info TEXT,
    bio TEXT,
    linkedin_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_department (department),
    INDEX idx_graduation_year (graduation_year),
    INDEX idx_profession (profession)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Activity logs (optional but useful for tracking)
CREATE TABLE activity_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(200) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user (password: admin123)
INSERT INTO users (institutional_id, name, email, password, role, department) VALUES
('ADMIN001', 'System Administrator', 'admin@university.edu', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Administration');

-- Insert sample clubs
INSERT INTO clubs (name, description) VALUES
('Computer Science Club', 'A club for CS enthusiasts to share knowledge and organize tech events'),
('Entrepreneurship Society', 'Fostering innovation and startup culture among students'),
('Research Forum', 'Connecting students with research opportunities and mentors');

-- Insert sample opportunities
INSERT INTO opportunities (title, type, description, company_organization, location, posted_by, deadline, requirements) VALUES
('Software Engineering Intern', 'internship', 'Join our team as a software engineering intern. Work on cutting-edge projects and gain real-world experience.', 'Tech Corp', 'Remote', 1, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'Strong programming skills, knowledge of web technologies'),
('Machine Learning Research Position', 'research', 'Research position in ML and AI. Work with professors on innovative projects.', 'University Research Lab', 'Campus', 1, DATE_ADD(CURDATE(), INTERVAL 45 DAY), 'Background in ML, Python programming'),
('Full Stack Developer', 'job', 'Full-time position for a full stack developer. Great benefits and growth opportunities.', 'StartupXYZ', 'San Francisco, CA', 1, DATE_ADD(CURDATE(), INTERVAL 60 DAY), '3+ years experience, React, Node.js');

-- Insert sample alumni
INSERT INTO alumni (name, email, department, graduation_year, profession, company, contact_info, bio, linkedin_url) VALUES
('John Smith', 'john.smith@email.com', 'Computer Science', 2018, 'Software Engineer', 'Google', 'john.smith@email.com', 'Passionate about building scalable systems and mentoring students.', 'https://linkedin.com/in/johnsmith'),
('Sarah Johnson', 'sarah.j@email.com', 'Business Administration', 2017, 'Product Manager', 'Microsoft', 'sarah.j@email.com', 'Experienced product manager with expertise in SaaS products.', 'https://linkedin.com/in/sarahjohnson'),
('Michael Chen', 'm.chen@email.com', 'Computer Science', 2019, 'Data Scientist', 'Amazon', 'm.chen@email.com', 'Data science enthusiast working on ML models for e-commerce.', 'https://linkedin.com/in/michaelchen'),
('Emily Davis', 'emily.davis@email.com', 'Electrical Engineering', 2016, 'Hardware Engineer', 'Intel', 'emily.davis@email.com', 'Hardware engineer specializing in processor design.', 'https://linkedin.com/in/emilydavis');

-- Insert sample events
INSERT INTO events (title, description, club_id, event_date, venue, capacity, created_by) VALUES
('Web Development Workshop', 'Learn modern web development with React and Node.js', 1, DATE_ADD(NOW(), INTERVAL 7 DAY), 'Tech Building Room 101', 50, 1),
('Startup Pitch Competition', 'Showcase your startup ideas and win prizes', 2, DATE_ADD(NOW(), INTERVAL 14 DAY), 'Business Hall Auditorium', 100, 1),
('Research Methodology Seminar', 'Learn how to conduct effective research', 3, DATE_ADD(NOW(), INTERVAL 10 DAY), 'Library Conference Room', 30, 1);
