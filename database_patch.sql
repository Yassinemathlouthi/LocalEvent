-- SQL patch to ensure database structure works with our entities

-- Ensure is_approved column exists in event table
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name='event' AND column_name='is_approved'
    ) THEN
        ALTER TABLE event ADD COLUMN is_approved BOOLEAN DEFAULT false;
    END IF;
END
$$;

-- Add indexes for better performance and relationship integrity
CREATE INDEX IF NOT EXISTS idx_event_category ON event(category);
CREATE INDEX IF NOT EXISTS idx_event_created_by ON event(created_by);

-- Update any empty category values to avoid errors
UPDATE event SET category = '' WHERE category IS NULL;
