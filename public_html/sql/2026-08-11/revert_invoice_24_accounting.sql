-- Revert accounting entries for deleted invoice INV00044 (id=24)
-- Invoice was soft-deleted (is_deleted=1) but accounting entries remained

DELETE FROM tblacc_account_history 
WHERE rel_id = 24 AND rel_type = 'purchase_invoice';
