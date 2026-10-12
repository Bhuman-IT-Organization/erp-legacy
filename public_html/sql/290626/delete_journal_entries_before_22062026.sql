-- Delete all accounting journal entries before 22-06-2026
DELETE FROM tblacc_account_history WHERE date < '2026-06-22';
