-- Update attendance_modifications table to include changes_made column
ALTER TABLE attendance_modifications ADD COLUMN changes_made TEXT AFTER modification_reason;

-- Update existing records to have a default value
UPDATE attendance_modifications SET changes_made = 'Changes tracked from this update forward' WHERE changes_made IS NULL; 