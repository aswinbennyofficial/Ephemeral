# Ephemeral
A PHP project to share a file over the internet with an expiry of 7 days.

## Running
- `podman-compose up --build`
- Visit `http://localhost:8080`


## Initial SQL
- `psql -U user -d mydatabase` password is `password`

- `CREATE TABLE IF NOT EXISTS users (userID VARCHAR(26) PRIMARY KEY, email VARCHAR(255) NOT NULL UNIQUE, firstName VARCHAR(50) NOT NULL, lastName VARCHAR(50) NOT NULL, passwordHash VARCHAR(255) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);` 
- `CREATE TABLE IF NOT EXISTS files (fileID VARCHAR(26) PRIMARY KEY, userID VARCHAR(26) REFERENCES users(userID) ON DELETE CASCADE, objectKey VARCHAR(255) NOT NULL, url VARCHAR(255) NOT NULL, slug VARCHAR(50) NOT NULL UNIQUE, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, expiry TIMESTAMP NOT NULL, passwordHash VARCHAR(255), metadata JSON, CHECK (expiry > created_at)); `