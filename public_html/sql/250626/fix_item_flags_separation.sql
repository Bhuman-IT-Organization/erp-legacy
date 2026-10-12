-- Fix item flags: Separate purchase items from inventory items
-- Items with can_be_inventory should NOT have can_be_purchased (they are managed via inventory)
-- Items with can_be_purchased only are for services/consumables that don't track stock

UPDATE tblitems 
SET can_be_purchased = NULL 
WHERE can_be_inventory = 'can_be_inventory' 
AND can_be_purchased = 'can_be_purchased';
