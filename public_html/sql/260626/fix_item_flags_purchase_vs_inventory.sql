-- Fix item flags: Items with can_be_inventory should NOT have can_be_purchased
-- They are managed via inventory/warehouse module
-- Purchase items page only shows items with can_be_purchased AND without can_be_inventory

-- Remove can_be_purchased from items that are inventory items
UPDATE tblitems 
SET can_be_purchased = NULL 
WHERE can_be_inventory = 'can_be_inventory' 
AND can_be_purchased = 'can_be_purchased';

-- Verify the separation
-- Purchase items: SELECT * FROM tblitems WHERE can_be_purchased = 'can_be_purchased' AND (can_be_inventory IS NULL OR can_be_inventory = '');
-- Inventory items: SELECT * FROM tblitems WHERE can_be_inventory = 'can_be_inventory';

UPDATE tblitems
SET
    can_be_purchased = 'can_be_purchased',
    can_be_inventory = NULL
WHERE id = 15;

UPDATE tblitems
SET
    can_be_purchased = 'can_be_purchased',
    can_be_inventory = NULL
WHERE id = 17;

UPDATE tblitems
SET
    can_be_purchased = 'can_be_purchased',
    can_be_inventory = NULL
WHERE id = 18;


UPDATE tblitems
SET
    can_be_purchased = NULL,
    can_be_inventory = 'can_be_inventory',
    can_be_sold = 'can_be_sold',
    can_be_manufacturing = 'can_be_manufacturing'
WHERE id = 85;



