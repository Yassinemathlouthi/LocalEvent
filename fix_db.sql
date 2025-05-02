
-- Make sure category is nullable
ALTER TABLE event ALTER COLUMN category DROP NOT NULL;

-- Make sure is_approved exists and has correct type
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema='public' AND table_name='event' AND column_name='is_approved'
    ) THEN
        ALTER TABLE event ADD COLUMN is_approved BOOLEAN DEFAULT false;
    END IF;
END
$$;

-- Make sure category exists with correct type
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema='public' AND table_name='event' AND column_name='category'
    ) THEN
        ALTER TABLE event ADD COLUMN category VARCHAR(100);
    END IF;
END
$$;

-- Update null categories to empty string to avoid errors
UPDATE event SET category = '' WHERE category IS NULL;
