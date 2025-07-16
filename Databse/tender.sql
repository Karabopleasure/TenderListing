-- USERS
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


-- CATEGORIES
CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE
);

-- SUBSCRIPTIONS
CREATE TABLE subscriptions (
  user_id INT NOT NULL,
  category_id INT NOT NULL,
  PRIMARY KEY (user_id, category_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- TENDERS
CREATE TABLE tenders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  category_id INT NOT NULL,
  location VARCHAR(100),
  deadline DATE,
  description TEXT,
  source_type ENUM('api', 'manual') DEFAULT 'api',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

CREATE TABLE saved_tenders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tender_id VARCHAR(50) NOT NULL,
    tender_No VARCHAR(50),
    description TEXT,
    category VARCHAR(50),
    department VARCHAR(50),
    province VARCHAR(50),
    closing_Date DATETIME,
    date_Published DATETIME,
    contactPerson VARCHAR(100),
    email VARCHAR(100),
    telephone VARCHAR(50),
    fax VARCHAR(50),
    streetname VARCHAR(100),
    surburb VARCHAR(50),
    town VARCHAR(50),
    code VARCHAR(20),
    conditions TEXT,
    briefingSession VARCHAR(255),
    briefingVenue VARCHAR(255),
    briefingCompulsory VARCHAR(10),
    compulsory_briefing_session DATETIME,
    supportDocument TEXT,
    saved_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_tender (user_id, tender_id)
);



-- NOTIFICATIONS (record of who was notified about which tender)
CREATE TABLE notifications (
  user_id INT NOT NULL,
  tender_id INT NOT NULL,
  sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, tender_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (tender_id) REFERENCES tenders(id) ON DELETE CASCADE
);

-- API SOURCES (for managing integrations)
CREATE TABLE api_sources (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  url TEXT NOT NULL,
  last_fetched DATETIME
);
