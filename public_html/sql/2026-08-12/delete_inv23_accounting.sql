-- Remove accounting entries for deleted invoice INV00043 (id=23)
-- Invoice is_deleted=1, accounting entries should not remain in trial balance

DELETE FROM tblacc_account_history 
WHERE rel_id = 23 AND rel_type = 'purchase_invoice';

-- Also remove any shipping charge entries linked to this invoice
DELETE FROM tblacc_account_history 
WHERE rel_type = 'inv_shipping_charge' 
AND rel_id IN (SELECT id FROM tblinv_shipping_charges WHERE invoice_id = 23);

DELETE FROM tblinv_shipping_charges WHERE invoice_id = 23;
