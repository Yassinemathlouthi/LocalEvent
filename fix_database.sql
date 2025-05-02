-- SQL Script to fix potential database issues
-- Run this if you still have problems after the entity fixes

-- Add category_id column to event table if it doesn't exist
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='event' AND column_name='category_id') THEN
        ALTER TABLE event ADD COLUMN category_id INTEGER;
    END IF;
END $$;

-- Make sure event table has is_approved column
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name='event' AND column_name='is_approved') THEN
        ALTER TABLE event ADD COLUMN is_approved BOOLEAN DEFAULT false;
    END IF;
END $$;

-- Fix any NULL category values to prevent errors
UPDATE event SET category = '' WHERE category IS NULL;

-- Make sure event_category table has correct constraints
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.table_constraints WHERE table_name='event_category' AND constraint_type='PRIMARY KEY') THEN
        -- Drop and recreate the table with proper constraints if needed
        DROP TABLE IF EXISTS event_category;
        CREATE TABLE event_category (
            event_id INT REFERENCES event(id) ON DELETE CASCADE,
            category_id INT REFERENCES category(id) ON DELETE CASCADE,
            PRIMARY KEY (event_id, category_id)
        );
    END IF;
END $$;
