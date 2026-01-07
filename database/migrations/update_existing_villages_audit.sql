-- Update existing villages to set audit trail to Admin user
-- Run this after ALTER TABLE migration
UPDATE villages 
SET created_by = 9, updated_by = 9 
WHERE created_by IS NULL;

-- Verify the update
SELECT id_village, village_name, created_by, updated_by, is_deleted 
FROM villages
ORDER BY id_village;
