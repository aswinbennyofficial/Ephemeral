-- Create Users Table
CREATE TABLE IF NOT EXISTS users (
    userID VARCHAR(26) PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    firstName VARCHAR(50) NOT NULL,
    lastName VARCHAR(50) NOT NULL,
    passwordHash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create Files Table
CREATE TABLE IF NOT EXISTS files (
    fileID VARCHAR(26) PRIMARY KEY,
    userID VARCHAR(26) REFERENCES users(userID) ON DELETE CASCADE,
    url VARCHAR(255) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expiry TIMESTAMP NOT NULL,
    passwordHash VARCHAR(255),
    metadata JSON,
    CHECK (expiry > created_at)
);

-- Create function to generate ULID
CREATE EXTENSION IF NOT EXISTS pgcrypto;

CREATE OR REPLACE FUNCTION generate_ulid() RETURNS text AS $$
DECLARE
  -- 6 bytes of time
  timestamp bytea = E'\\000\\000\\000\\000\\000\\000';
  -- 10 bytes of entropy
  entropy bytea = E'\\000\\000\\000\\000\\000\\000\\000\\000\\000\\000';
BEGIN
  -- Generate timestamp (big-endian)
  timestamp = set_byte(timestamp, 0, (extract(epoch from clock_timestamp()) * 1000)::bigint >> 40);
  timestamp = set_byte(timestamp, 1, (extract(epoch from clock_timestamp()) * 1000)::bigint >> 32);
  timestamp = set_byte(timestamp, 2, (extract(epoch from clock_timestamp()) * 1000)::bigint >> 24);
  timestamp = set_byte(timestamp, 3, (extract(epoch from clock_timestamp()) * 1000)::bigint >> 16);
  timestamp = set_byte(timestamp, 4, (extract(epoch from clock_timestamp()) * 1000)::bigint >> 8);
  timestamp = set_byte(timestamp, 5, (extract(epoch from clock_timestamp()) * 1000)::bigint);

  -- Generate entropy
  entropy = gen_random_bytes(10);

  -- Concatenate timestamp and entropy
  RETURN encode(timestamp || entropy, 'base64');
END;
$$ LANGUAGE plpgsql VOLATILE;

-- Alter tables to use ULID function as default for IDs
ALTER TABLE users ALTER COLUMN userID SET DEFAULT generate_ulid();
ALTER TABLE files ALTER COLUMN fileID SET DEFAULT generate_ulid();