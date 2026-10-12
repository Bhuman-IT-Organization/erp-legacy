-- ---------------------------------------------------------------------------
-- Clear the placeholder email signature.
--
-- tbloptions.email_signature held the literal text "this is test mail", which is
-- what the {email_signature} merge field resolved to. Every outgoing notification
-- was therefore signed:
--
--     Kind Regards,
--     this is test mail
--
-- {email_signature} now resolves, most specific first:
--   1. the acting staff member's own signature (Profile -> Email Signature)
--   2. that staff member's first and last name
--   3. this global option
--   4. the company name
--
-- Setting this option to empty makes step 2 apply for interactive mail and step 4
-- for cron and system mail, so nothing is signed with a placeholder again. An
-- explicit global signature can still be set in Setup -> Settings -> Email at any
-- time and it will take precedence over step 4.
--
-- REVERSIBLE. Step 1 records the old value.
-- RE-RUNNABLE. Once cleared, the UPDATE matches nothing.
--
--   mysql -u root nextgen_live -e "source sql/2026-09-01/clear_placeholder_email_signature.sql"
-- ---------------------------------------------------------------------------

SELECT '--- Before ---' AS step;
SELECT `name`, `value` FROM `tbloptions` WHERE `name` = 'email_signature';

-- ── 1. Record the old value so it can be put back ─────────────────────────

CREATE TABLE IF NOT EXISTS `tbloptions_email_signature_bak_20260901` (
  `name`      VARCHAR(100) NOT NULL,
  `value`     TEXT NULL,
  `backed_up` DATETIME NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `tbloptions_email_signature_bak_20260901` (`name`, `value`, `backed_up`)
SELECT `name`, `value`, NOW() FROM `tbloptions` WHERE `name` = 'email_signature';

-- ── 2. Clear it ───────────────────────────────────────────────────────────
-- Scoped to the known placeholder so a real signature someone has since written
-- is never wiped by a later run of this file.

UPDATE `tbloptions`
SET    `value` = ''
WHERE  `name` = 'email_signature'
  AND  TRIM(`value`) = 'this is test mail';

-- ── 3. Verify ─────────────────────────────────────────────────────────────

SELECT '--- After (value should be empty) ---' AS step;
SELECT `name`, `value`, LENGTH(`value`) AS len
FROM   `tbloptions` WHERE `name` = 'email_signature';

SELECT '--- Company name, used for cron and system mail ---' AS step;
SELECT `value` AS companyname FROM `tbloptions` WHERE `name` = 'companyname';

SELECT '--- Staff with their own signature (these override the name) ---' AS step;
SELECT `staffid`, CONCAT(`firstname`, ' ', `lastname`) AS staff_name,
       LEFT(`email_signature`, 60) AS own_signature
FROM   `tblstaff`
WHERE  `email_signature` IS NOT NULL AND TRIM(`email_signature`) <> ''
ORDER BY `staffid`;

SELECT '--- Active staff and the name their mail will now be signed with ---' AS step;
SELECT `staffid`,
       TRIM(CONCAT(`firstname`, ' ', `lastname`)) AS will_sign_as,
       CASE WHEN `email_signature` IS NOT NULL AND TRIM(`email_signature`) <> ''
            THEN 'own signature overrides'
            ELSE 'first and last name' END AS source
FROM   `tblstaff`
WHERE  `active` = 1
ORDER BY `staffid`;

-- ---------------------------------------------------------------------------
-- ROLLBACK, if ever needed:
--
--   UPDATE tbloptions o
--   JOIN   tbloptions_email_signature_bak_20260901 b ON b.name = o.name
--   SET    o.value = b.value;
--
-- Once satisfied:
--   DROP TABLE tbloptions_email_signature_bak_20260901;
-- ---------------------------------------------------------------------------
