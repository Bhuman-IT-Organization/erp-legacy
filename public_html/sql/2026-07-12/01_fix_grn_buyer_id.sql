-- Fix GRN buyer_id: Update NULL buyer_id to use addedfrom (creator) value
-- This fixes the issue where the GRN grid shows the logged-in user's name
-- instead of the actual creator's name when buyer_id is NULL

UPDATE tblgoods_receipt 
SET buyer_id = addedfrom 
WHERE buyer_id IS NULL AND addedfrom IS NOT NULL;
