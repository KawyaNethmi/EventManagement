CREATE DATABASE event_management;
USE event_management;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Events table
CREATE TABLE events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATE NOT NULL,
    event_time TIME NOT NULL,
    location VARCHAR(255) NOT NULL,
    max_participants INT,
    event_type ENUM('conference', 'workshop', 'seminar', 'networking', 'social', 'sports', 'charity', 'concert', 'exhibition', 'other') DEFAULT 'other',
    price DECIMAL(10,2) DEFAULT 0.00,
    category_id INT,
    featured BOOLEAN DEFAULT FALSE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);
-- Event registrations table
CREATE TABLE event_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT,
    user_id INT,
    registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('registered', 'attended', 'cancelled') DEFAULT 'registered',
    FOREIGN KEY (event_id) REFERENCES events(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert sample categories
INSERT INTO categories (name, description, icon) VALUES 
('Business', 'Professional and corporate events', 'fas fa-briefcase'),
('Education', 'Learning and workshop events', 'fas fa-graduation-cap'),
('Technology', 'Tech conferences and hackathons', 'fas fa-laptop-code'),
('Social', 'Networking and social gatherings', 'fas fa-users'),
('Sports', 'Sports competitions and activities', 'fas fa-running'),
('Arts & Culture', 'Cultural events and performances', 'fas fa-palette'),
('Charity', 'Fundraising and volunteer events', 'fas fa-hand-holding-heart'),
('Music', 'Concerts and music festivals', 'fas fa-music');

-- Insert admin user
INSERT INTO users (username, email , password, user_type) 
VALUES ('admin', 'admin12@gmail.com', 1234, 'admin');
-- Password: password

-- Insert sample events
INSERT INTO events (title, description, event_date, event_time, location, max_participants, event_type, price, category_id, featured, created_by) VALUES 
('Tech Innovation Summit 2024', 'Annual technology conference featuring industry leaders and innovative startups. Join us for insightful talks and networking opportunities.', '2024-03-15', '09:00:00', 'Convention Center, Downtown', 500, 'conference', 199.00, 3, TRUE, 1),
('Digital Marketing Workshop', 'Hands-on workshop covering the latest digital marketing strategies and tools. Perfect for marketers and business owners.', '2024-02-20', '14:00:00', 'Business Hub, Tech Park', 50, 'workshop', 99.00, 2, TRUE, 1),
('Community Charity Run', 'Annual 5K charity run to support local shelters. All proceeds go to community development programs.', '2024-04-10', '08:00:00', 'City Central Park', 1000, 'charity', 25.00, 7, FALSE, 1);