-- ---------------------------------------------------------------------------
-- AP opening balances as of 30/04/2026 - step 00: create and load the data
-- ---------------------------------------------------------------------------
--
-- RUN THIS INSTEAD OF FILE 01 ON ANY SERVER WHERE THE SPREADSHEET WAS NEVER
-- IMPORTED. If file 01 gave you
--
--     #1146 - Table 'ngpp_ap_balances_30_04_2026_xlsx___ap_balance_30_04_2026'
--             doesn't exist
--
-- then this is the file you want. Use one or the other, never both.
--
-- It builds the table from scratch, already in its final shape with the columns
-- named, and loads all 180 data rows with the amount and date already parsed.
-- The spreadsheet header row and grand total row are simply not included.
--
-- After this file, carry on with:
--
--     02  ->  03  ->  06  ->  04 to verify
--
--
-- ABOUT THE DROP TABLE
--
-- The table it drops is the imported copy of the spreadsheet and nothing else. It
-- holds no application data, nothing references it, and every row it contains is
-- recreated by the INSERT immediately below. Dropping and rebuilding is what makes
-- this file safely repeatable and what corrects the collation if an earlier version
-- of this file left it on the MySQL 8 server default.
--
-- NO APPLICATION TABLE IS TOUCHED HERE. Not tblpur_vendor, not tblpur_invoices, not
-- tblpur_vendor_opening_balance. Files 02, 03 and 06 do not delete anything at all,
-- and file 07 is the only file in this set that removes application records.
--
-- Run it as many times as you like: you always end up with exactly these 180
-- rows, never duplicates and never a partial load.
--
-- Because the ids are stated explicitly as 1 to 180, the OBnnnnn invoice
-- references that file 06 derives from them come out identical on every server,
-- which makes the two easy to reconcile.
--
--
-- ONE THING TO KNOW IF YOU HAVE ALREADY RUN 03 AND 06
--
-- Re-running this file reloads the source data but does NOT push it through again.
-- Files 03 and 06 both refuse to load a second time, so the vendor opening balances
-- and the invoice records stay exactly as they are. To genuinely rework the figures:
-- run 07, clear tblpur_vendor_opening_balance by hand, then 02, 03 and 06 again.
--
--
-- The collation is stated explicitly and deliberately. Left to the server default
-- this table comes out utf8mb4_0900_ai_ci on MySQL 8, while tblpur_vendor is
-- utf8mb4_general_ci, and file 02 then dies comparing vendor codes across the two:
--     #1267 - Illegal mix of collations (utf8mb4_general_ci,IMPLICIT)
--             and (utf8mb4_0900_ai_ci,IMPLICIT) for operation '='
--
--
-- vendor_id and balance_type are deliberately left empty. File 02 fills them in by
-- matching vendor_code against THIS server's tblpur_vendor, which is what makes the
-- mapping correct even though the vendor ids differ between servers.
--
-- Amount sign, unchanged from the sheet:
--   negative = still owed, we have to pay it
--   positive = already paid, sitting as an advance with the vendor
-- ---------------------------------------------------------------------------

DROP TABLE IF EXISTS `ngpp_ap_balances_30_04_2026_xlsx___ap_balance_30_04_2026`;

CREATE TABLE `ngpp_ap_balances_30_04_2026_xlsx___ap_balance_30_04_2026` (
    `id`                INT UNSIGNED  NOT NULL,
    `sap_supplier_code` VARCHAR(50)   NULL DEFAULT NULL,
    `vendor_code`       VARCHAR(50)   NULL DEFAULT NULL,
    `supplier_name`     VARCHAR(255)  NULL DEFAULT NULL,
    `doc_date_raw`      VARCHAR(30)   NULL DEFAULT NULL,
    `invoice_no`        VARCHAR(100)  NULL DEFAULT NULL,
    `amount_aed_raw`    VARCHAR(30)   NULL DEFAULT NULL,
    `sap_description`   VARCHAR(255)  NULL DEFAULT NULL,
    `vendor_id`         INT           NULL DEFAULT NULL
        COMMENT 'tblpur_vendor.userid resolved from vendor_code by file 02',
    `doc_date`          DATE          NULL DEFAULT NULL,
    `amount`            DECIMAL(15,2) NULL DEFAULT NULL
        COMMENT 'Minus = we still owe the vendor, plus = already paid / advance',
    `balance_type`      VARCHAR(20)   NULL DEFAULT NULL
        COMMENT 'payable when amount < 0, advance when amount > 0. Set by file 02',
    PRIMARY KEY (`id`),
    KEY `vendor_id` (`vendor_id`),
    KEY `vendor_code` (`vendor_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `ngpp_ap_balances_30_04_2026_xlsx___ap_balance_30_04_2026`
    (`id`, `sap_supplier_code`, `vendor_code`, `supplier_name`, `doc_date_raw`,
     `invoice_no`, `amount_aed_raw`, `sap_description`, `doc_date`, `amount`)
VALUES
(1, '1000654', 'VEN0015', 'GUANGZHOU YIDE PRINT', '12/31/2025', 'C.I # YD29-1-001', '53,924.05', '100% ADV AGST C.I & CONTRACT # C.I # YD29-1-001', '2025-12-31', 53924.05),
(2, '1000654', 'VEN0015', 'GUANGZHOU YIDE PRINT', '12/31/2025', 'YD29-1-001 D25/9/3', '80,077.65', '100% ADV AGNST CI * CONTRACT YD29-1-001-DT 25/9/3', '2025-12-31', 80077.65),
(3, '1000654', 'VEN0015', 'GUANGZHOU YIDE PRINT', '4/9/2026', 'PO-37871', '11,542.67', '100% ADV PYMT FOR YD29-1-001', '2026-04-09', 11542.67),
(4, '1000659', 'VEN0039', 'WUXI KAICHUANG MOULD', '12/31/2025', '100% ADV DT 270825', '33,253.75', '100% ADV AGNST PUR&SAL CONTRC DT 27/08/25', '2025-12-31', 33253.75),
(5, '1000659', 'VEN0039', 'WUXI KAICHUANG MOULD', '12/31/2025', 'TT Payment USD', '120,500.49', '100% ADV AGNST PUR&SAL CONTRC DT Sep-16-2025', '2025-12-31', 120500.49),
(6, '1000659', 'VEN0039', 'WUXI KAICHUANG MOULD', '12/31/2025', 'TT Payment USD', '63,358.67', '100% ADV AGNST Quotation - Dt Sep-20-2025', '2025-12-31', 63358.67),
(7, '1000659', 'VEN0039', 'WUXI KAICHUANG MOULD', '12/31/2025', 'TT-09.04.2025', '64,132.76', '100% ADV AGST DIE CUTTING PLATES CONTRACT DT6.3.25', '2025-12-31', 64132.76),
(8, '1000659', 'VEN0039', 'WUXI KAICHUANG MOULD', '3/31/2026', '2 INVOICES', '24,870.98', 'PYMT FOR PO#38455/38442', '2026-03-31', 24870.98),
(9, '1000659', 'VEN0039', 'WUXI KAICHUANG MOULD', '12/31/2025', '00336213', '-30,689.03', 'HP&MD Red Philip & Ceramic Anilox Roll 450/434', '2025-12-31', -30689.03),
(10, '1000659', 'VEN0039', 'WUXI KAICHUANG MOULD', '12/31/2025', 'KC202509-WM-005', '-92,659.01', 'HP CARTON DIE CUTTING & HP SMALL BOX 450/435', '2025-12-31', -92659.01),
(11, '1000659', 'VEN0039', 'WUXI KAICHUANG MOULD', '12/31/2025', 'OCT10 2025', '-65,719.75', 'Bon Nise Red Steel Mold(with accesso) 450/438', '2025-12-31', -65719.75),
(12, '1000659', 'VEN0039', 'WUXI KAICHUANG MOULD', '1/1/2026', 'GZ20251101', '-3,382.89', 'PO-38442 Cig Pack Male Die (HP&MD) KS Red Hard Pa', '2026-01-01', -3382.89),
(13, '1000659', 'VEN0039', 'WUXI KAICHUANG MOULD', '1/15/2026', '003362148', '-21,488.19', 'PO-38455 Cig. Pack Punch(Cig Pack -2)/Embossed/Deb', '2026-01-15', -21488.19),
(14, '1000661', 'VEN0052', 'HANGZHOU KUANGHU IMP', '12/31/2025', 'XM20250326', '-267,985.26', 'Silver Foil Board - 450/400 - Rev', '2025-12-31', -267985.26),
(15, '1000662', 'VEN0053', 'SHENZHEN GRAND IMPOR', '12/31/2025', 'SK20250906', '-568,785.05', 'White Cardboard GSM-230 450/423 Rev', '2025-12-31', -568785.05),
(16, '1000663', 'VEN0054', 'DONGGUAN MINGHUI NEW', '12/31/2025', 'MHXC-20250416001', '-409,428.66', 'UV inks 450/407', '2025-12-31', -409428.66),
(17, '1000664', 'VEN0037', 'DONGGUAN YUNCHEN PLA', '12/11/2025', '003362211', '-8,130.92', 'PO-4500000522 SHIPPING CHARGES', '2025-12-11', -8130.92),
(18, '1000664', 'VEN0037', 'DONGGUAN YUNCHEN PLA', '12/31/2025', '100%ADV PI25/09/27', '41,422.13', '100% Adv PI Dt 2025/09/27 - from NBF AED', '2025-12-31', 41422.13),
(19, '1000664', 'VEN0037', 'DONGGUAN YUNCHEN PLA', '12/31/2025', 'Adv Cont 2025/10/5', '82,844.26', '100% Adv Cont. 2025/10/5 - from NBF AED', '2025-12-31', 82844.26),
(20, '1000664', 'VEN0037', 'DONGGUAN YUNCHEN PLA', '12/31/2025', 'ADV CONT2025/10/22', '69,968.47', '100% Adv Cont. 2025/10/22 - from NBF AED', '2025-12-31', 69968.47),
(21, '1000664', 'VEN0037', 'DONGGUAN YUNCHEN PLA', '12/31/2025', 'TT Payment USD', '12,064.16', '100% Adv Contract Dt 2025/09/15 - from NBF AED', '2025-12-31', 12064.16),
(22, '1000664', 'VEN0037', 'DONGGUAN YUNCHEN PLA', '12/31/2025', 'TT-10.07.2025', '4,407.00', '100% ADV. PAID AGST CONTACT DATED 07.07.2025', '2025-12-31', 4407.00),
(23, '1000664', 'VEN0037', 'DONGGUAN YUNCHEN PLA', '3/31/2026', '3 INVOICES', '73,545.49', 'PYMT FOR PO#440/519/522', '2026-03-31', 73545.49),
(24, '1000664', 'VEN0037', 'DONGGUAN YUNCHEN PLA', '4/15/2026', 'PO-38682/38748', '32,876.22', '100% ADV PYMT FOR PO-38682/38748', '2026-04-15', 32876.22),
(25, '1000664', 'VEN0037', 'DONGGUAN YUNCHEN PLA', '4/15/2026', 'PO-38682/38748', '45,259.89', '100% ADV PYMT FOR PO-38682/38748', '2026-04-15', 45259.89),
(26, '1000664', 'VEN0037', 'DONGGUAN YUNCHEN PLA', '1/9/2026', '00336223', '-2,203.50', 'Bon blank cylinder PO#37904', '2026-01-09', -2203.50),
(27, '1000664', 'VEN0037', 'DONGGUAN YUNCHEN PLA', '12/31/2025', '00336217', '-41,866.50', 'Printing Roller-HPRed& BonniseBlue 450/437', '2025-12-31', -41866.50),
(28, '1000664', 'VEN0037', 'DONGGUAN YUNCHEN PLA', '12/31/2025', '00336218', '-22,035.00', 'Printing Roller-HPRed& BonniseBlue 450/439', '2025-12-31', -22035.00),
(29, '1000664', 'VEN0037', 'DONGGUAN YUNCHEN PLA', '12/31/2025', '00336219', '-19,831.50', 'Printing Roller-HPRedOuterCarton 900*606 450/436', '2025-12-31', -19831.50),
(30, '1000664', 'VEN0037', 'DONGGUAN YUNCHEN PLA', '12/31/2025', '0036219', '-30,849.00', 'Printing Roller ModGold/Blue Box 450/440', '2025-12-31', -30849.00),
(31, '1000664', 'VEN0037', 'DONGGUAN YUNCHEN PLA', '12/31/2025', '20251201', '-12,655.44', 'Air freight charges for Po#440', '2025-12-31', -12655.44),
(32, '1000664', 'VEN0037', 'DONGGUAN YUNCHEN PLA', '12/31/2025', '20251231', '-18,803.20', 'Printing roller Po#4500000522', '2025-12-31', -18803.20),
(33, '1000664', 'VEN0037', 'DONGGUAN YUNCHEN PLA', '12/4/2025', '00336232', '-3,106.94', 'PO-38635 Modern Gold Inner- 900*620mm', '2025-12-04', -3106.94),
(34, '2000784', 'VEN0055', 'AL WAHDANIA GENERAL', '12/31/2025', 'ARAMEX_CRG_JUN-25', '-39.13', 'ARAMEX_COURIER_JUN-25_DOCS_NIKITHA_LOGISTICS_NGPP', '2025-12-31', -39.13),
(35, '2000784', 'VEN0055', 'AL WAHDANIA GENERAL', '12/31/2025', '2020000303', '-39.13', 'Courier_Aug25_Docs_nikitha_procurement_NGPP', '2025-12-31', -39.13),
(36, '2000784', 'VEN0055', 'AL WAHDANIA GENERAL', '12/31/2025', '2020000331', '-206.52', 'DHL Courier_Sep25_Docs_Procurement_NGPP', '2025-12-31', -206.52),
(37, '2000784', 'VEN0055', 'AL WAHDANIA GENERAL', '1/30/2026', '90027470', '-641,321.20', 'To record inv#90027470 PO#37930', '2026-01-30', -641321.20),
(38, '2000784', 'VEN0055', 'AL WAHDANIA GENERAL', '2/23/2026', '90027602', '-66,690.66', 'INK,ADT PO#38198', '2026-02-23', -66690.66),
(39, '2000784', 'VEN0055', 'AL WAHDANIA GENERAL', '12/31/2025', '20251130', '-154,975.54', 'White base paper Po#4500000494', '2025-12-31', -154975.54),
(40, '2000784', 'VEN0055', 'AL WAHDANIA GENERAL', '12/31/2025', '20251224', '-1,152,310.28', 'Printing Ink Po#4500000479', '2025-12-31', -1152310.28),
(41, '2000784', 'VEN0055', 'AL WAHDANIA GENERAL', '12/31/2025', '20251224', '-73,435.00', 'Varnish&Adhesive Po#4500000485', '2025-12-31', -73435.00),
(42, '2000784', 'VEN0055', 'AL WAHDANIA GENERAL', '12/31/2025', '20251224', '-545,522.14', 'Ink&varnish Po#4500000480', '2025-12-31', -545522.14),
(43, '2000784', 'VEN0055', 'AL WAHDANIA GENERAL', '12/31/2025', '20251224', '-393,500.37', 'GSM board Po#4500000497', '2025-12-31', -393500.37),
(44, '2000784', 'VEN0055', 'AL WAHDANIA GENERAL', '12/31/2025', '20251225', '-630,708.00', 'GSM board Po#4500000495', '2025-12-31', -630708.00),
(45, '2000784', 'VEN0055', 'AL WAHDANIA GENERAL', '12/31/2025', '20251225', '-590,644.08', 'GSM board Po#4500000474', '2025-12-31', -590644.08),
(46, '2000784', 'VEN0055', 'AL WAHDANIA GENERAL', '12/31/2025', '20251225', '-611,928.72', 'GSM board Po#4500000477', '2025-12-31', -611928.72),
(47, '1000665', 'VEN0056', 'WUHAN JIUYUTONG TECH', '12/31/2025', 'TT-07-11-2025 REVI', '19,600.13', '100% Adv PI INV 20251009 DT 09-OCT-2025 REVISE', '2025-12-31', 19600.13),
(48, '1000666', 'VEN0057', 'ZHIXING(HK) DEVELOPM', '12/31/2025', 'INV#MH2025-03-001', '158,565.70', '30% ADV AGST IMPORT INV#MH2025-03-001', '2025-12-31', 158565.70),
(49, '1000666', 'VEN0057', 'ZHIXING(HK) DEVELOPM', '12/31/2025', 'TT-22.04.2025', '369,692.21', '70% BALANCE ADV AGST INV#MH2025-03-001', '2025-12-31', 369692.21),
(50, '1000679', 'VEN0058', 'Zhejiang Xinggan Tec', '3/31/2026', 'PO-38599', '21,861.80', '100% ADV PYMT FOR PO-38599', '2026-03-31', 21861.80),
(51, '2000785', 'VEN0010', 'AIR CARE FZC', '4/23/2026', '000373', '-3,570.00', 'PYMT FOR INV#7500', '2026-04-23', -3570.00),
(52, '2000790', 'VEN0045', 'Modern Stationery', '1/13/2026', '18209', '-685.13', 'Office Stationery PO#37913', '2026-01-13', -685.13),
(53, '2000790', 'VEN0045', 'Modern Stationery', '12/31/2025', '18041', '-124.95', 'Office stationery Po#4500000450', '2025-12-31', -124.95),
(54, '2000790', 'VEN0045', 'Modern Stationery', '12/31/2025', '18106', '-643.65', 'Office stationery Po#4500000449', '2025-12-31', -643.65),
(55, '2000806', 'VEN0005', 'SINO PRINTING SUPPLI', '', '20260302', '-15,183.00', 'PO-38956 VAR-GRAVURE VARNISH-COATING', NULL, -15183.00),
(56, '3000096', 'VEN0059', 'Wenzhou Real Star Ma', '', 'PO-37826', '367.25', 'PO-37826', NULL, 367.25),
(57, '3000097', 'VEN0060', 'CTC TRADE & LOGISTIC', '12/31/2025', 'TT-18.04.2025', '60,752.74', '100% ADV AGST INV#DBCR250402007', '2025-12-31', 60752.74),
(58, '3000098', 'VEN0061', 'HONGKONG SCARBORE TR', '12/31/2025', 'PI#DBCR03052025', '24,737.96', '100% ADV AGST INV#DBCR03052025', '2025-12-31', 24737.96),
(59, '3000098', 'VEN0061', 'HONGKONG SCARBORE TR', '12/31/2025', 'ADV AGST PI#YTE2505044', '19,890.04', '100% ADV PAID AGST PI # YTE2505044', '2025-12-31', 19890.04),
(60, '3000098', 'VEN0061', 'HONGKONG SCARBORE TR', '12/31/2025', '100% PI DY00245/6 7480223', '56,343.50', '100% Adv PI DY00245/6 & 7480223 DT 07.25 & 08.25', '2025-12-31', 56343.50),
(61, '3000099', 'VEN0062', 'BIZGROUP SUPPLIES PT', '7/16/2025', '250103, 25030201', '511,043.07', 'PAYMENT AGST 2 INVOICES FOR BIZGROUP', '2025-07-16', 511043.07),
(62, '3000099', 'VEN0062', 'BIZGROUP SUPPLIES PT', '12/16/2025', 'NGPP-25121601', '-174,337.72', 'Printing machine parts Po#37828', '2025-12-16', -174337.72),
(63, '3000099', 'VEN0062', 'BIZGROUP SUPPLIES PT', '11/1/2024', 'NPINV-24110101', '-1,068,697.50', 'Stamping,Printing,Cutting,Finishing Mach-PO45/77', '2024-11-01', -1068697.50),
(64, '3000099', 'VEN0062', 'BIZGROUP SUPPLIES PT', '12/15/2024', 'BIZ-NPINV-241215', '-162,221.67', 'Wet Horizontal Gold & Silver Card Lamina', '2024-12-15', -162221.67),
(65, '3000099', 'VEN0062', 'BIZGROUP SUPPLIES PT', '3/2/2025', 'NPINV-25030201', '-601,746.47', 'Printer Parts & Accessories & Fork Lifts', '2025-03-02', -601746.47),
(66, '3000099', 'VEN0062', 'BIZGROUP SUPPLIES PT', '4/1/2025', 'BIZ-NPINV-250401', '-9,210.63', 'ALLOY EVAPORATOR FOR REFRIGERATION CYCLE', '2025-04-01', -9210.63),
(67, '3000099', 'VEN0062', 'BIZGROUP SUPPLIES PT', '3/2/2025', 'BIZ-NPINV-250302', '-113,641.84', 'AUTOMATIC CROSS CUTTING MACHINE', '2025-03-02', -113641.84),
(68, '3000099', 'VEN0062', 'BIZGROUP SUPPLIES PT', '4/20/2025', 'BIZ-NPINV-250420', '-1,252,287.06', 'VENTILATION DUCT AND BEARING PARTS & ACCESS.', '2025-04-20', -1252287.06),
(69, '3000099', 'VEN0062', 'BIZGROUP SUPPLIES PT', '5/28/2025', 'BIZ-PINV-2502528', '-655,093.32', 'PRINTING QUALITY INSPECTION MACHINE', '2025-05-28', -655093.32),
(70, '3000099', 'VEN0062', 'BIZGROUP SUPPLIES PT', '5/20/2025', 'BIZ-NPINV-250520', '-2,595,370.44', 'Printing Machine 450/317', '2025-05-20', -2595370.44),
(71, '3000099', 'VEN0062', 'BIZGROUP SUPPLIES PT', '6/26/2025', 'BIZ-NPINV-250626', '-161,615.19', 'Slitting Machine', '2025-06-26', -161615.19),
(72, '3000099', 'VEN0062', 'BIZGROUP SUPPLIES PT', '7/3/2025', 'BIZ-NPINV-250703', '-484,770.00', 'Single Concave Printing Machine 450/217-R', '2025-07-03', -484770.00),
(73, '3000099', 'VEN0062', 'BIZGROUP SUPPLIES PT', '3/26/2025', '2025032601', '-267,985.27', 'PO-4500038596 MB-METTALIZED SILVER', '2025-03-26', -267985.27),
(74, '3000099', 'VEN0062', 'BIZGROUP SUPPLIES PT', '9/18/2025', '2025091801', '-734,500.00', 'PO-38612/38613/38614/38611 BRONZING MACHINE', '2025-09-18', -734500.00),
(75, '3000099', 'VEN0062', 'BIZGROUP SUPPLIES PT', '10/16/2025', '2025101601', '-4,014,336.30', 'PO-38732 SILKSCREEN PRINTING MACHINE', '2025-10-16', -4014336.30),
(76, '3000099', 'VEN0062', 'BIZGROUP SUPPLIES PT', '9/6/2025', '2025090601', '-568,785.05', 'PO-38601 WHITE CARDBOARD', '2025-09-06', -568785.05),
(77, '3000099', 'VEN0062', 'BIZGROUP SUPPLIES PT', '10/26/2025', '2025102601', '-810,751.63', 'PO-38730 PRINTING MACHINE', '2025-10-26', -810751.63),
(78, '3000099', 'VEN0062', 'BIZGROUP SUPPLIES PT', '9/30/2025', '2025093001', '-4,003,028.35', 'PO-38780 PRINTER PARTS', '2025-09-30', -4003028.35),
(79, '3000099', 'VEN0062', 'BIZGROUP SUPPLIES PT', '10/29/2025', '2025102901', '-429,318.92', 'PO-38815 QUALITY INSPECTION MACHINE', '2025-10-29', -429318.92),
(80, '3000099', 'VEN0062', 'BIZGROUP SUPPLIES PT', '8/23/2025', '2025082301', '-226,472.13', 'PO-38811 SPARE PARTS', '2025-08-23', -226472.13),
(81, '3000099', 'VEN0062', 'BIZGROUP SUPPLIES PT', '10/12/2025', '2025101201', '-206,901.31', 'PO-38820 MATCHINE PARTS', '2025-10-12', -206901.31),
(82, '3000099', 'VEN0062', 'BIZGROUP SUPPLIES PT', '10/27/2025', '2025102701', '-336,401.00', 'PO-38819 Paper Meterials', '2025-10-27', -336401.00),
(83, '3000100', 'VEN0063', 'TRINITY PRINT & MEDI', '8/15/2024', '0815-24XL', '-4,590,625.00', '1 SET  USED HEIDELBERG PRINTING MACHINE', '2024-08-15', -4590625.00),
(84, '4000696', 'VEN0049', 'ARIF TURNING WORKS L', '2/28/2026', 'SL-05348', '-3,780.00', 'PO-38262 Hole close & water proof & foam', '2026-02-28', -3780.00),
(85, '4000696', 'VEN0049', 'ARIF TURNING WORKS L', '2/28/2026', 'SL-05350', '-2,457.00', 'PO-38263 New Square bar 133X2cn hole making & thr', '2026-02-28', -2457.00),
(86, '4000696', 'VEN0049', 'ARIF TURNING WORKS L', '2/28/2026', 'SL-05349', '-420.00', 'PO-38264 Pin bush new making', '2026-02-28', -420.00),
(87, '4000696', 'VEN0049', 'ARIF TURNING WORKS L', '4/20/2026', 'SL-05490', '-1,428.00', 'PO-38807 Aluminium ring drilling & thread new mak', '2026-04-20', -1428.00),
(88, '4000697', 'VEN0016', 'BYRNE EQUIPMENT RENT', '4/30/2026', 'RAKU/SI-01173', '-65,415.00', 'PO-38954 Rent DGs 1250 kva x2', '2026-04-30', -65415.00),
(89, '4000698', 'VEN0064', 'GOLDEN DIESEL TRADIN', '4/21/2026', '2104202602', '-69,300.00', 'PO-38751 Diesel 10 PPM', '2026-04-21', -69300.00),
(90, '4000698', 'VEN0064', 'GOLDEN DIESEL TRADIN', '4/14/2026', '1404202601', '-23,100.00', 'PO-38751 Diesel 10 PPM', '2026-04-14', -23100.00),
(91, '4000701', 'VEN0003', 'AMNA NETWORKING SOLU', '3/23/2026', 'AMIT/INV/26/2052', '-630.00', 'PO-38501 Logitech MK270 Wireless Keyboard/UGREEN W', '2026-03-23', -630.00),
(92, '4000701', 'VEN0003', 'AMNA NETWORKING SOLU', '3/23/2026', 'AMIT/INV/26/2051', '-2,310.00', 'PO-38500 DELL 27 MONITOR /DELL PRO 24 MONITOR', '2026-03-23', -2310.00),
(93, '4000702', 'VEN0077', 'EXPRESS PRINTING LLC', '4/15/2026', '14674', '-84.00', 'PO-38797 Stamp - Inova Filter (No.1)', '2026-04-15', -84.00),
(94, '4000702', 'VEN0077', 'EXPRESS PRINTING LLC', '4/13/2026', '14665', '-84.00', 'PO-38775 Stamp - Inova Filter', '2026-04-13', -84.00),
(95, '4000706', 'VEN0024', 'Soud Trading LLC', '2/5/2026', '12762', '-138.60', 'PO - 38287 PLastic Elbow 6XM6/FCV 8mm', '2026-02-05', -138.60),
(96, '4000706', 'VEN0024', 'Soud Trading LLC', '2/8/2026', '12791', '-189.00', 'PO-38288 Compact Cylinder/Extension Socket', '2026-02-08', -189.00),
(97, '4000706', 'VEN0024', 'Soud Trading LLC', '2/1/2026', '12754', '-52.50', 'PO-38286 MS CSK Bolt 8X20mm', '2026-02-01', -52.50),
(98, '4000706', 'VEN0024', 'Soud Trading LLC', '4/15/2026', '13136', '-866.25', 'PO-38800 Nylon Cable/Hose Body/PU Pipe/Hose Clip', '2026-04-15', -866.25),
(99, '4000706', 'VEN0024', 'Soud Trading LLC', '4/30/2026', '13176', '-367.50', 'PO-38951 Compact Cylinder SDA20X15SB', '2026-04-30', -367.50),
(100, '4000708', 'VEN0065', 'ROUNAK COMPUTERS LLC', '4/30/2026', '000378', '-7,972.95', 'PYMT FOR INV#S20251839', '2026-04-30', -7972.95),
(101, '4000712', 'VEN0025', 'SUHA ELECTRICAL EQUI', '5/31/2025', 'TX41975', '-7,875.00', 'Cables for A/C Office PO#4500000104', '2025-05-31', -7875.00),
(102, '4000712', 'VEN0025', 'SUHA ELECTRICAL EQUI', '5/28/2025', 'TX41854', '-315.00', 'Supply 1 HP WATER PUMP ALDEN', '2025-05-28', -315.00),
(103, '4000712', 'VEN0025', 'SUHA ELECTRICAL EQUI', '9/4/2025', 'TX44153', '-4,158.00', 'Pipe connection for machines items Po#4500000312', '2025-09-04', -4158.00),
(104, '4000712', 'VEN0025', 'SUHA ELECTRICAL EQUI', '9/4/2025', 'TX44154', '-522.90', 'Pipe connection for machines items Po#4500000316', '2025-09-04', -522.90),
(105, '4000712', 'VEN0025', 'SUHA ELECTRICAL EQUI', '7/11/2025', 'TX42908', '-1,480.50', 'DIESEL TANK', '2025-07-11', -1480.50),
(106, '4000712', 'VEN0025', 'SUHA ELECTRICAL EQUI', '6/25/2025', 'TX42430', '-7,160.48', 'Consumble items for works shop hummdifie', '2025-06-25', -7160.48),
(107, '4000712', 'VEN0025', 'SUHA ELECTRICAL EQUI', '7/21/2025', 'TX43138', '-672.00', 'Humidifier works 450/258', '2025-07-21', -672.00),
(108, '4000712', 'VEN0025', 'SUHA ELECTRICAL EQUI', '7/25/2025', 'TX43242', '-198.45', 'Plumbing works - 450/257', '2025-07-25', -198.45),
(109, '4000712', 'VEN0025', 'SUHA ELECTRICAL EQUI', '7/29/2025', 'TX43314', '-3,508.05', 'Plumbing works 450/266', '2025-07-29', -3508.05),
(110, '4000712', 'VEN0025', 'SUHA ELECTRICAL EQUI', '8/5/2025', 'TX43470', '-785.40', 'Installation material for Humidifier 450/249', '2025-08-05', -785.40),
(111, '4000712', 'VEN0025', 'SUHA ELECTRICAL EQUI', '8/5/2025', 'TX43471', '-451.50', 'Heidelberg Machine Chiller 450/264', '2025-08-05', -451.50),
(112, '4000712', 'VEN0025', 'SUHA ELECTRICAL EQUI', '2/24/2026', 'TX48295', '-283.50', 'PO-38223 10W Killer Set-1Ft SPEX', '2026-02-24', -283.50),
(113, '4000712', 'VEN0025', 'SUHA ELECTRICAL EQUI', '2/24/2026', 'TX48294', '-1,086.75', 'PO-37909 Polythene Sheet/Steel Wire Brided/1 HP Su', '2026-02-24', -1086.75),
(114, '4000712', 'VEN0025', 'SUHA ELECTRICAL EQUI', '3/9/2026', 'TX48546', '-1,685.25', 'PO-38274  Timerr Hager/Ceramic Fuse/Cable Lug', '2026-03-09', -1685.25),
(115, '4000712', 'VEN0025', 'SUHA ELECTRICAL EQUI', '3/2/2026', 'TX45981', '-6,496.88', 'PO-38277 Electrical Equipment', '2026-03-02', -6496.88),
(116, '4000712', 'VEN0025', 'SUHA ELECTRICAL EQUI', '3/5/2026', 'TX45985', '-2,410.28', 'PO-38281 Electrical Items', '2026-03-05', -2410.28),
(117, '4000712', 'VEN0025', 'SUHA ELECTRICAL EQUI', '3/9/2026', 'TX48547', '-388.50', 'PO-38261 MCCB 3P 160A', '2026-03-09', -388.50),
(118, '4000781', 'VEN0012', 'ALKHALEEJ BOOK SHOP', '4/21/2026', '176308', '-56.70', 'PO-38868 Photocopier Paper Copy', '2026-04-21', -56.70),
(119, '4000781', 'VEN0012', 'ALKHALEEJ BOOK SHOP', '4/15/2026', '176276', '-1,167.60', 'PO-38787 OFFICE SUPPLIES', '2026-04-15', -1167.60),
(120, '4000785', 'VEN0022', 'HEITECH PRINTING EQU', '4/29/2026', 'HT/INV/035A-4-26', '-8,715.00', 'PO-38910 Troubleshooting and repairing electrical', '2026-04-29', -8715.00),
(121, '4000790', 'VEN0066', 'ORCHID INT\'L CLEANIN', '10/3/2025', 'RAK-25-2561', '-399.00', 'PO-38938 Maxi Roll', '2025-10-03', -399.00),
(122, '4000791', 'VEN0013', 'MASTER PRINTING PLAT', '4/28/2026', '2026-0779', '-24,321.50', 'PO-38979 Cylinder Modern Red Outer', '2026-04-28', -24321.50),
(123, '5000846', 'VEN0011', 'AL HIKAM BUILDING CO', '4/30/2026', '000380', '-6,940.50', 'PYMT FOR INV#I-0559 / I-0558', '2026-04-30', -6940.50),
(124, '5000847', 'VEN0014', 'BASIC CONTRACTING TE', '4/30/2026', '000379', '-125,417.25', 'PYMT FOR DUE BALANCE AS OF 26.02.2026', '2026-04-30', -125417.25),
(125, '5000848', 'VEN0018', 'D DESIRE BUILDING CO', '2/18/2026', 'RN/2026/02/0014', '-8,137.50', 'Const Found for Heidelberg MC Chiller Po#38166', '2026-02-18', -8137.50),
(126, '5000851', 'VEN0021', 'GUARDIAN INTEGRATED', '1/12/2026', '314/2025 (P1)', '-94,250.00', 'Pmt forinv#NPPINV/1314/2025 (P1)', '2026-01-12', -94250.00),
(127, '5000851', 'VEN0021', 'GUARDIAN INTEGRATED', '1/12/2026', '314/2025 (P1)', '94,250.00', 'Pmt forinv#NPPINV/1314/2025 (P1)', '2026-01-12', 94250.00),
(128, '5000851', 'VEN0021', 'GUARDIAN INTEGRATED', '2/19/2026', 'GIS-NPP/INV/1482', '-76,650.00', 'PO-37897 Power Supply for New Machine', '2026-02-19', -76650.00),
(129, '5000851', 'VEN0021', 'GUARDIAN INTEGRATED', '9/13/2025', 'NPPINV/1314/2025', '-147,000.00', 'Supply,Installation of Elec.Power Supply 450/014', '2025-09-13', -147000.00),
(130, '5000855', 'VEN0038', 'AL MUBARAK GENERAL T', '4/30/2026', '51272', '-5,000.00', 'PO-38965 RENTAL BUS APRIL 2026', '2026-04-30', -5000.00),
(131, '5000857', 'VEN0040', 'AL RAED BUILIDING CL', '4/30/2026', '114', '-34,303.50', 'PO-38966 Al Raed\'s outsourced manpower', '2026-04-30', -34303.50),
(132, '5000862', 'VEN0067', 'AL AMWAJ SERVICES FO', '1/26/2026', 'PO#45-518', '3,500.00', 'paid agst inv#SIV4-AMWS/25/1841', '2026-01-26', 3500.00),
(133, '5000865', 'VEN0073', 'OASIS PURE WATER FAC', '1/27/2026', 'INV#20251231', '106.00', 'Paid agst INV#20251231', '2026-01-27', 106.00),
(134, '5000865', 'VEN0073', 'OASIS PURE WATER FAC', '2/28/2026', '20489631', '-185.07', 'PO-38357 Oasis Water Plain BT 5G1*1', '2026-02-28', -185.07),
(135, '5000865', 'VEN0073', 'OASIS PURE WATER FAC', '3/31/2026', '20853348', '-277.20', 'PO-38684 Oasis Water 5G1*1/500ML24*1', '2026-03-31', -277.20),
(136, '5000865', 'VEN0073', 'OASIS PURE WATER FAC', '4/30/2026', '21193316', '-731.06', 'PO-38984 Oasis water 5GL/500ML', '2026-04-30', -731.06),
(137, '5000865', 'VEN0073', 'OASIS PURE WATER FAC', '10/31/2025', '19067559', '-106.31', 'Drinking water Po#4500000515', '2025-10-31', -106.31),
(138, '5000867', 'VEN0068', 'IRON MOUNTAIN CONSUL', '3/31/2026', 'TI000165161', '-11.15', 'RECURING ERM - DOCUMENTS SCANNING 31.03.2026', '2026-03-31', -11.15),
(139, '5000868', 'VEN0069', 'CUSTOMS DEPARTMENT F', '4/30/2026', 'CD/RH/N08/CA/88', '-108.00', 'FUJ_CUSTOMS_DUTY_TR_FEES_DOC_CRGS(IMP&EXP)APRIL 26', '2026-04-30', -108.00),
(140, '5000869', 'VEN0070', 'FUJAIRAH FREE ZONE A', '12/31/2024', 'INTITIAL UPLOAD', '-221,732.50', 'initial upload - S4Hana to ECC', '2024-12-31', -221732.50),
(141, '5000869', 'VEN0070', 'FUJAIRAH FREE ZONE A', '9/1/2025', 'CR009968', '-2,150.00', 'CHNG STATUS 3 EMP - AJMAL - QIUGU - XIAODAN', '2025-09-01', -2150.00),
(142, '5000869', 'VEN0070', 'FUJAIRAH FREE ZONE A', '7/4/2025', 'CS007962', '-4,444.00', 'VISA EXP & SECRITY DEPOSIT FANGYUAN GUO', '2025-07-04', -4444.00),
(143, '5000869', 'VEN0070', 'FUJAIRAH FREE ZONE A', '10/6/2025', 'CS007962', '4,478.00', 'Fangyuan Residence Visa Charges', '2025-10-06', 4478.00),
(144, '5000869', 'VEN0070', 'FUJAIRAH FREE ZONE A', '1/1/2025', 'INTITIAL UPLOAD', '221,732.50', 'initial upload - S4Hana to ECC', '2025-01-01', 221732.50),
(145, '5000869', 'VEN0070', 'FUJAIRAH FREE ZONE A', '12/31/2025', 'CR000819', '-499,013.00', 'License & W/H Rent 01.01.2026-31.12.2026', '2025-12-31', -499013.00),
(146, '5000869', 'VEN0070', 'FUJAIRAH FREE ZONE A', '12/16/2025', '000224', '20,000.00', 'Paid to freezone for License Fee/Year', '2025-12-16', 20000.00),
(147, '5000869', 'VEN0070', 'FUJAIRAH FREE ZONE A', '12/31/2025', 'INITIAL UPLOAD -', '120,322.00', 'initial upload - S4Hana to ECC', '2025-12-31', 120322.00),
(148, '5000869', 'VEN0070', 'FUJAIRAH FREE ZONE A', '9/1/2025', '20250901', '2,150.00', 'CHNG STATUS 3 EMP - AJMAL - QIUGU - XIAODAN', '2025-09-01', 2150.00),
(149, '5000869', 'VEN0070', 'FUJAIRAH FREE ZONE A', '4/10/2026', '000229', '120,322.00', 'Paid for Fujairah Freezone Rent Agreement for 2026', '2026-04-10', 120322.00),
(150, '5000869', 'VEN0070', 'FUJAIRAH FREE ZONE A', '7/10/2026', '000230', '120,322.00', 'Paid for Fujairah Freezone Rent Agreement for 2026', '2026-07-10', 120322.00),
(151, '5000869', 'VEN0070', 'FUJAIRAH FREE ZONE A', '7/10/2026', '000230', '-120,322.00', 'Paid for Fujairah Freezone Rent Agreement for 2026', '2026-07-10', -120322.00),
(152, '5000869', 'VEN0070', 'FUJAIRAH FREE ZONE A', '10/10/2026', '000231', '120,322.00', 'Paid for Fujairah Freezone Rent Agreement for 2026', '2026-10-10', 120322.00),
(153, '5000869', 'VEN0070', 'FUJAIRAH FREE ZONE A', '10/10/2026', '000231', '-120,322.00', 'Paid for Fujairah Freezone Rent Agreement for 2026', '2026-10-10', -120322.00),
(154, '5000875', 'VEN0071', 'The National Insuran', '3/9/2026', '12361499', '-1,681.62', 'Med Insurance Lilly 42035 12.09.2025-11.09.2026 QA', '2026-03-09', -1681.62),
(155, '5000875', 'VEN0071', 'The National Insuran', '3/10/2026', '12361912', '-1,672.77', 'Med Insurance Amrutha 42036 12.9.2025-11.9.2026 QA', '2026-03-10', -1672.77),
(156, '5000875', 'VEN0071', 'The National Insuran', '4/9/2026', '12373144', '-1,407.02', 'Med Ins Mr.Amjad 42039 12.09.2025-11.09.2026', '2026-04-09', -1407.02),
(157, '5000875', 'VEN0071', 'The National Insuran', '4/15/2026', '12374961', '-1,353.87', 'Med Ins Mr.Amjad Spouse 12.09.2025-11.09.2026', '2026-04-15', -1353.87),
(158, '5000875', 'VEN0071', 'The National Insuran', '4/15/2026', '12374953', '-1,353.87', 'Med Ins Mr.Amjad Child 12.09.2025-11.09.2026', '2026-04-15', -1353.87),
(159, '5000875', 'VEN0071', 'The National Insuran', '4/16/2026', '12375249', '-1,345.02', 'Med Ins Zhannat 42040 12.09.2025-11.09.2026 HR', '2026-04-16', -1345.02),
(160, '5000875', 'VEN0071', 'The National Insuran', '4/21/2026', '12376693', '-1,300.74', 'Med Ins Firdavs 42041 12.09.2025-11.09.2026 Admin', '2026-04-21', -1300.74),
(161, '5000875', 'VEN0071', 'The National Insuran', '2/25/2026', '12355776', '1,753.87', 'Med Ins Cancel Sol 42037 12.9.2025-11.9.2026 SC', '2026-02-25', 1753.87),
(162, '5000875', 'VEN0071', 'The National Insuran', '3/31/2026', '12369640', '2,905.37', 'Med Ins Cancel 42014/42017 12.09.2025-11.09.2026', '2026-03-31', 2905.37),
(163, '5000875', 'VEN0071', 'The National Insuran', '4/9/2026', '12373205', '1,372.97', 'Med Ins Cancel Wei Chen 42028 12.9.2025-11.9.2026', '2026-04-09', 1372.97),
(164, '5000875', 'VEN0071', 'The National Insuran', '4/10/2026', '12373623', '1,364.11', 'Med Ins Cancel Shanthi 42032 12.9.2025-11.9.2026', '2026-04-10', 1364.11),
(165, '5000875', 'VEN0071', 'The National Insuran', '4/16/2026', '12375324', '3,932.93', 'Med Ins Cancel Li Quigu/Jun Wen/Yang Li - Produ', '2026-04-16', 3932.93),
(166, '5000875', 'VEN0071', 'The National Insuran', '4/21/2026', '12376687', '1,266.67', 'Med Ins Cancel Hongwang 42016 12.9.2025-11.9.2026', '2026-04-21', 1266.67),
(167, '5000875', 'VEN0071', 'The National Insuran', '6/12/2026', 'DAMAN INS-Q4', '-20,206.98', 'Pmt for Medical Heath Ins -Q4', '2026-06-12', -20206.98),
(168, '5000876', 'VEN0050', 'SAFARI TRANSPORT', '3/31/2026', 'SAF26/SE/03/520', '-210.00', 'PO-38688 Sewage Removal', '2026-03-31', -210.00),
(169, '5000876', 'VEN0050', 'SAFARI TRANSPORT', '4/30/2026', 'SAF26/GR/04/758', '-210.00', 'PO-38993 Garbage 10 CBM', '2026-04-30', -210.00),
(170, '5000876', 'VEN0050', 'SAFARI TRANSPORT', '4/30/2026', 'SAF26/SE/04/778', '-210.00', 'PO-38992 SEWAGE REMOVAL', '2026-04-30', -210.00),
(171, '5000884', 'VEN0046', 'Etisalat', '12/1/2025', 'E&_NOV-25', '-2,315.33', 'ETISALAT_BILLS_FOR_NOV-25', '2025-12-01', -2315.33),
(172, '5000884', 'VEN0046', 'Etisalat', '1/9/2026', 'SIM & NET', '2,200.00', 'padi agst etisalt sim & internet', '2026-01-09', 2200.00),
(173, '5000886', 'VEN0072', 'HASSAN IBRAHIM AL FA', '6/13/2025', '000109', '36,000.00', 'CHEQUE ISSUED TO HASSAN IBRAHIM AL FARDAN REAL EST', '2025-06-13', 36000.00),
(174, '5000886', 'VEN0072', 'HASSAN IBRAHIM AL FA', '4/10/2025', '000010', '38,000.00', 'CHEQUE ISSUED TO HASSAN IBRAHIM AL FARDAN REAL EST', '2025-04-10', 38000.00),
(175, '5000886', 'VEN0072', 'HASSAN IBRAHIM AL FA', '6/13/2025', '000007', '36,000.00', 'CHEQUE ISSUED TO HASSAN IBRAHIM AL FARDAN REAL EST', '2025-06-13', 36000.00),
(176, '5000886', 'VEN0072', 'HASSAN IBRAHIM AL FA', '10/7/2025', '000161', '37,000.00', 'Paid for Flat 106 - Fan Slong accomod. allowances', '2025-10-07', 37000.00),
(177, '5000871', 'VEN0028', 'METRO FIRE SYSTEM LL', '4/8/2026', '15716', '-1,260.00', 'PO-38953 non comprehensive annual maintenance', '2026-04-08', -1260.00),
(178, '5000954', 'VEN0002', 'MERIDIANSOLUTIONS IT', '4/22/2026', '000371', '-4,483.50', 'PYMT FOR INV#2026/313', '2026-04-22', -4483.50),
(179, '5000955', 'VEN0023', 'NET DESIRE TECHNOLOG', '3/31/2026', 'PO-38514', '357,525.00', '100%ADV PYMT FOR PI No. 2600- 03- 25- ARC', '2026-03-31', 357525.00),
(180, '5000970', 'VEN0004', 'ARIZONE TRADING - L.', '4/29/2026', '000376', '-6,090.00', 'PYMT FOR INV#ARZFJ-581', '2026-04-29', -6090.00);


-- ---------------------------------------------------------------------------
-- Check before moving on. Expect 180 rows, a total of -28330450.48 - which
-- is exactly the grand total printed on the spreadsheet - and the collation
-- reading utf8mb4_general_ci.
-- ---------------------------------------------------------------------------
SELECT COUNT(*) AS rows_loaded,
       ROUND(SUM(`amount`), 2) AS total_aed,
       MIN(`doc_date`) AS earliest_date,
       MAX(`doc_date`) AS latest_date
FROM `ngpp_ap_balances_30_04_2026_xlsx___ap_balance_30_04_2026`;

SELECT `TABLE_COLLATION`
FROM `information_schema`.`TABLES`
WHERE `TABLE_SCHEMA` = DATABASE()
  AND `TABLE_NAME` = 'ngpp_ap_balances_30_04_2026_xlsx___ap_balance_30_04_2026';
