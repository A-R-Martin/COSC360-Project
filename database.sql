-- Create the virtual_library database
CREATE DATABASE IF NOT EXISTS virtual_library;
USE virtual_library;

-- Temporarily disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(30) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    bio TEXT,
    profile_image VARCHAR(255),
    role ENUM('user', 'admin') DEFAULT 'user',
    status ENUM('active', 'banned') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (username),
    INDEX (email),
    INDEX (status)
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
    owner_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (title),
    INDEX (author),
    INDEX (genre),
    INDEX (status),
    INDEX (owner_id),
    FOREIGN KEY (owner_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- User_Books relation (for borrowed/reserved books)
CREATE TABLE IF NOT EXISTS user_books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    status ENUM('borrowed', 'reserved', 'returned', 'cancelled') NOT NULL,
    borrow_date TIMESTAMP NULL,
    return_date TIMESTAMP NULL,
    reserve_date TIMESTAMP NULL,
    cancel_date TIMESTAMP NULL,
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

-- Insert sample admin user first (ID 1)
INSERT INTO users (username, email, password, role) VALUES 
('admin', 'admin@virtuallibrary.com', '$2y$10$qnTF.HGVKo0yR3AOtRXRFOD.BIreuJ21SWls/CO52Vpx.9s0XD/4C', 'admin');

-- Insert test user with ID 2 
INSERT INTO users (username, email, password, role) VALUES 
('testuser', 'test@virtuallibrary.com', '$2y$10$qnTF.HGVKo0yR3AOtRXRFOD.BIreuJ21SWls/CO52Vpx.9s0XD/4C', 'user');

-- Insert sample books from featured books
INSERT INTO books (title, author, description, cover_image, isbn, year_published, genre, rating, status, owner_id) VALUES
('The Great Gatsby', 'F. Scott Fitzgerald', 'A story of the fabulously wealthy Jay Gatsby and his powerful love for the beautiful Daisy Buchanan.', 'uploads/covers/gatsby.jpg', '978-0743273565', 1925, 'Classic Fiction', 4.5, 'available', 2),
('To Kill a Mockingbird', 'Harper Lee', 'The unforgettable novel of a childhood in a sleepy Southern town and the crisis of conscience that rocked it.', 'uploads/covers/mockingbird.jpg', '978-0446310789', 1960, 'Classic Fiction', 5.0, 'available', 2),
('1984', 'George Orwell', 'A dystopian social science fiction novel and cautionary tale about the dangers of totalitarianism.', 'uploads/covers/1984.jpg', '978-0451524935', 1949, 'Dystopian Fiction', 4.7, 'available', 1);

-- Insert more sample books to populate the library
INSERT INTO books (title, author, description, cover_image, isbn, year_published, genre, rating, status, owner_id) VALUES
('Pride and Prejudice', 'Jane Austen', 'A romantic novel of manners that follows the character development of Elizabeth Bennet.', 'uploads/covers/pride.jpg', '978-0141439518', 1813, 'Classic Romance', 4.8, 'available', 1),
('The Hobbit', 'J.R.R. Tolkien', 'A fantasy novel about the adventures of Bilbo Baggins, a hobbit who embarks on a quest.', 'uploads/covers/hobbit.jpg', '978-0345534835', 1937, 'Fantasy', 4.9, 'available', 2),
('Harry Potter and the Philosopher\'s Stone', 'J.K. Rowling', 'The first novel in the Harry Potter series following a young wizard\'s journey.', 'uploads/covers/harry-potter.jpg', '978-0747532699', 1997, 'Fantasy', 4.8, 'available', 1),
('The Catcher in the Rye', 'J.D. Salinger', 'A novel about teenage alienation and loss of innocence.', 'uploads/covers/catcher.jpg', '978-0316769488', 1951, 'Coming-of-age Fiction', 4.3, 'available', 2),
('The Lord of the Rings', 'J.R.R. Tolkien', 'An epic high-fantasy novel about the quest to destroy the One Ring.', 'uploads/covers/lotr.jpg', '978-0618640157', 1954, 'Fantasy', 4.9, 'available', 1),
('Brave New World', 'Aldous Huxley', 'A dystopian novel set in a futuristic World State of genetically modified citizens.', 'uploads/covers/brave-new-world.jpg', '978-0060850524', 1932, 'Dystopian Fiction', 4.6, 'available', 2),
('Moby-Dick', 'Herman Melville', 'The saga of Captain Ahab and his monomaniacal pursuit of the white whale.', 'uploads/covers/moby-dick.jpg', '978-0142437247', 1851, 'Adventure Fiction', 4.2, 'available', 1);

-- Then update book statuses and create relationships
UPDATE books SET status = 'borrowed' WHERE title = 'The Catcher in the Rye';
UPDATE books SET status = 'reserved' WHERE title = 'Brave New World';

-- Now add the book relationships AFTER both users exist
INSERT INTO user_books (user_id, book_id, status, borrow_date, return_date) 
SELECT 2, book_id, 'borrowed', NOW(), DATE_ADD(NOW(), INTERVAL 14 DAY) 
FROM books WHERE title = 'The Catcher in the Rye';

-- Add some history entries to demonstrate the borrowing history
INSERT INTO user_books (user_id, book_id, status, borrow_date, return_date) 
SELECT 2, book_id, 'history', DATE_SUB(NOW(), INTERVAL 30 DAY), DATE_SUB(NOW(), INTERVAL 16 DAY) 
FROM books WHERE title = 'The Hobbit';

INSERT INTO user_books (user_id, book_id, status, borrow_date, return_date) 
SELECT 2, book_id, 'history', DATE_SUB(NOW(), INTERVAL 60 DAY), DATE_SUB(NOW(), INTERVAL 46 DAY) 
FROM books WHERE title = 'Moby-Dick';

SET FOREIGN_KEY_CHECKS = 1; 