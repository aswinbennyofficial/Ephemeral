-- Create Users Table
CREATE TABLE IF NOT EXISTS users (
    userID VARCHAR(26) PRIMARY KEY DEFAULT (ulid()), -- Generate ULID in your application
    email VARCHAR(255) NOT NULL UNIQUE,
    firstName VARCHAR(50) NOT NULL,
    lastName VARCHAR(50) NOT NULL,
    passwordHash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create Files Table
CREATE TABLE IF NOT EXISTS files (
    fileID VARCHAR(26) PRIMARY KEY DEFAULT (ulid()), -- Generate ULID in your application
    userID VARCHAR(26) REFERENCES users(userID) ON DELETE CASCADE,
    url VARCHAR(255) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expiry TIMESTAMP NOT NULL,
    passwordHash VARCHAR(255),
    metadata JSON,
    CHECK (expiry > created_at)
);
