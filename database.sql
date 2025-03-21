-- Create the virtual_library database
CREATE DATABASE IF NOT EXISTS virtual_library;
USE virtual_library;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(30) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    bio TEXT,
    profile_image VARCHAR(255),
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (username),
    INDEX (email)
);

-- Books table
CREATE TABLE IF NOT EXISTS books (
    book_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    description TEXT,
    cover_image VARCHAR(255),
    isbn VARCHAR(20),
    year_published INT,
    genre VARCHAR(100),
    rating DECIMAL(3,2),
    status ENUM('available', 'borrowed', 'reserved') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (title),
    INDEX (author),
    INDEX (genre),
    INDEX (status)
);

-- User_Books relation (for borrowed/reserved books)
CREATE TABLE IF NOT EXISTS user_books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    status ENUM('borrowed', 'reserved', 'history') NOT NULL,
    borrow_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    return_date TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books(book_id) ON DELETE CASCADE,
    INDEX (user_id),
    INDEX (book_id)
);

-- Discussion categories
CREATE TABLE IF NOT EXISTS discussion_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Discussion threads
CREATE TABLE IF NOT EXISTS discussion_threads (
    thread_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_locked BOOLEAN DEFAULT FALSE,
    is_sticky BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES discussion_categories(category_id) ON DELETE CASCADE,
    INDEX (category_id),
    INDEX (user_id)
);

-- Discussion posts
CREATE TABLE IF NOT EXISTS discussion_posts (
    post_id INT AUTO_INCREMENT PRIMARY KEY,
    thread_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    is_edited BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (thread_id) REFERENCES discussion_threads(thread_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX (thread_id),
    INDEX (user_id)
);

-- Insert some initial categories
INSERT INTO discussion_categories (name, description) VALUES 
('General Discussion', 'General discussions about books and reading'),
('Book Recommendations', 'Recommend and discover new books'),
('Author Spotlight', 'Discussions about specific authors and their works'),
('Genre Discussions', 'Discuss books by genre categories');

-- Insert sample admin user (password: Admin@123)
INSERT INTO users (username, email, password, role) VALUES 
('admin', 'admin@virtuallibrary.com', '$2y$10$qnTF.HGVKo0yR3AOtRXRFOD.BIreuJ21SWls/CO52Vpx.9s0XD/4C', 'admin'); 