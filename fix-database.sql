-- Fix database schema issues for LocalEvent

-- Fix roles column in app_user table
ALTER TABLE app_user ALTER COLUMN roles TYPE JSON USING roles::json;
ALTER TABLE app_user ALTER COLUMN roles SET NOT NULL;

-- Add missing columns to User entity
ALTER TABLE app_user ADD COLUMN IF NOT EXISTS name VARCHAR(255);
ALTER TABLE app_user ADD COLUMN IF NOT EXISTS location VARCHAR(255) NULL;
ALTER TABLE app_user ADD COLUMN IF NOT EXISTS interests TEXT[] NULL;
ALTER TABLE app_user ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

-- Sync event table with Entity
ALTER TABLE event ADD COLUMN IF NOT EXISTS organizer_id INT NULL;
ALTER TABLE event ADD COLUMN IF NOT EXISTS location VARCHAR(255) NULL;
ALTER TABLE event ADD COLUMN IF NOT EXISTS description TEXT NULL;

-- Create attendance table if it doesn't exist
CREATE TABLE IF NOT EXISTS attendance (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    event_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_attendance_user FOREIGN KEY (user_id) REFERENCES app_user(id) ON DELETE CASCADE,
    CONSTRAINT fk_attendance_event FOREIGN KEY (event_id) REFERENCES event(id) ON DELETE CASCADE
);

-- Create indices on foreign keys
CREATE INDEX IF NOT EXISTS idx_attendance_user ON attendance(user_id);
CREATE INDEX IF NOT EXISTS idx_attendance_event ON attendance(event_id);

-- Fix any constraint issues
ALTER TABLE event ADD CONSTRAINT fk_event_organizer FOREIGN KEY (organizer_id) REFERENCES app_user(id) ON DELETE SET NULL; 