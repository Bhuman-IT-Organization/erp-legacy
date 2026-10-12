-- ---------------------------------------------------------------------------
-- Optional: strip the stray spaces out of tblpur_vendor.vendor_code
-- ---------------------------------------------------------------------------
--
-- Vendors 57 to 77 were saved with a leading space in vendor_code - ' VEN0053'
-- rather than 'VEN0053'. That is why a plain join between the AP balance sheet
-- and the vendor table found nothing for those 21 vendors, and it will keep
-- biting anything else that matches on the code.
--
-- Steps 02 and 03 work around it with TRIM() on both sides, so this file is not
-- required for the balances to load. It is here because the underlying data is
-- wrong and worth correcting.
--
-- Read only check first, then the fix, then the same check again.
-- Safe to re-run: after the first run the UPDATE matches nothing.
--
-- One thing to know before running it: VEN0052 is currently held by two
-- vendors, 56 ABM INKS INDUSTRY L.L.C ('VEN0052') and 57 HANGZHOU KUANGHU IMP
-- (' VEN0052'). Trimming makes that collision exact rather than hidden. There is
-- no unique index on vendor_code so the UPDATE will not fail, but you should
-- decide which of those two keeps VEN0052 and give the other its own code.
-- ---------------------------------------------------------------------------

-- Before
SELECT `userid`, CONCAT('[', `vendor_code`, ']') AS code_with_boundaries, `company`
FROM `tblpur_vendor`
WHERE `vendor_code` <> TRIM(`vendor_code`)
ORDER BY `userid`;


UPDATE `tblpur_vendor`
SET `vendor_code` = TRIM(`vendor_code`)
WHERE `vendor_code` <> TRIM(`vendor_code`);


-- After. Expect an empty result set.
SELECT `userid`, CONCAT('[', `vendor_code`, ']') AS code_with_boundaries, `company`
FROM `tblpur_vendor`
WHERE `vendor_code` <> TRIM(`vendor_code`)
ORDER BY `userid`;
