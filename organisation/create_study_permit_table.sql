CREATE TABLE IF NOT EXISTS study_permit (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    Id VARCHAR(50) NOT NULL,
    date_birth DATE NOT NULL,
    nationality VARCHAR(100) NOT NULL,
    institution VARCHAR(255) NOT NULL,
    course VARCHAR(255) NOT NULL,
    duration VARCHAR(50) NOT NULL,
    accept_letter VARCHAR(255) NOT NULL,
    proof_of_financial VARCHAR(255) NOT NULL,
    address TEXT NOT NULL,
    number VARCHAR(20) NOT NULL,
    email VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
); 