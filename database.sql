-- Church Workers Performance Review System Database Setup

-- Create reviews table
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reviewer_name VARCHAR(255) NOT NULL,
    reviewer_department VARCHAR(255) NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT NOT NULL,
    visible_to_workers ENUM('Yes', 'No') DEFAULT 'Yes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_department (reviewer_department),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample data for testing (assuming workers table already exists)
-- Insert some sample reviews
INSERT INTO reviews (reviewer_name, reviewer_department, rating, comment, visible_to_workers) VALUES
('John Doe', 'Spirit & Life (THE WORD)', 5, 'Excellent ministry work this month. The team showed great dedication.', 'Yes'),
('Jane Smith', 'Spirit & Power Ministry (MUSIC)', 4, 'Good performance overall. Need to improve coordination during rehearsals.', 'Yes'),
('Mike Johnson', 'The Fire Place (PRAYER)', 5, 'Outstanding prayer sessions. Very impactful ministry.', 'Yes');

-- Create activity logs table for admin tracking
CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_name VARCHAR(255) NOT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;