-- Full ERP Cheque Management Schema
SET FOREIGN_KEY_CHECKS = 0;

-- Drop and recreate with full lifecycle fields
DROP TABLE IF EXISTS `tblcheque_transactions`;
DROP TABLE IF EXISTS `tblcheque_management`;

CREATE TABLE `tblcheque_management` (
  `id`                INT(11) NOT NULL AUTO_INCREMENT,
  -- Classification
  `cheque_type`       ENUM('incoming','outgoing') NOT NULL DEFAULT 'incoming' COMMENT 'incoming=customer, outgoing=vendor',
  `direction`         ENUM('customer','vendor') NOT NULL DEFAULT 'customer',
  `source_type`       VARCHAR(50) NOT NULL DEFAULT 'manual' COMMENT 'pur_invoice_payment|sales_payment|expense|manual',
  `source_id`         INT(11) NULL DEFAULT NULL,
  -- Cheque Details
  `cheque_number`     VARCHAR(100) NOT NULL DEFAULT '',
  `cheque_bank_name`  VARCHAR(200) NULL DEFAULT NULL,
  `cheque_date`       DATE NULL DEFAULT NULL,
  `deposit_date`      DATE NULL DEFAULT NULL,
  `cleared_date`      DATE NULL DEFAULT NULL,
  `bounce_date`       DATE NULL DEFAULT NULL,
  `amount`            DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `currency`          VARCHAR(10) NULL DEFAULT NULL,
  -- Party
  `rel_id`            INT(11) NULL DEFAULT NULL,
  `rel_name`          VARCHAR(300) NULL DEFAULT NULL,
  `rel_bank`          VARCHAR(200) NULL DEFAULT NULL,
  -- References
  `invoice_ref`       VARCHAR(200) NULL DEFAULT NULL,
  `transaction_id`    VARCHAR(200) NULL DEFAULT NULL,
  `deposit_slip_no`   VARCHAR(100) NULL DEFAULT NULL,
  `batch_ref`         VARCHAR(100) NULL DEFAULT NULL,
  `bank_account_id`   INT(11) NULL DEFAULT NULL COMMENT 'FK to acc_accounts',
  -- Status lifecycle
  -- 1=Received, 2=Verified, 3=Deposited, 4=Cleared, 5=Bounced, 6=Cancelled
  -- 7=Post-Dated(PDC), 8=Issued(outgoing), 9=Printed, 10=Void
  `status`            TINYINT(2) NOT NULL DEFAULT 1,
  `is_pdc`            TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Post-Dated Cheque',
  -- Bounce details
  `bounce_reason`     VARCHAR(300) NULL DEFAULT NULL,
  `bounce_charges`    DECIMAL(15,2) NULL DEFAULT 0.00,
  -- Approval (Maker-Checker)
  `maker_id`          INT(11) NULL DEFAULT NULL,
  `checker_id`        INT(11) NULL DEFAULT NULL,
  `approver_id`       INT(11) NULL DEFAULT NULL,
  `approved_at`       DATETIME NULL DEFAULT NULL,
  `approval_status`   TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0=pending,1=approved,2=rejected',
  -- Attachment
  `cheque_attachment` VARCHAR(500) NULL DEFAULT NULL,
  -- Notes
  `notes`             TEXT NULL DEFAULT NULL,
  -- Audit
  `created_by`        INT(11) NULL DEFAULT NULL,
  `created_at`        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_by`        INT(11) NULL DEFAULT NULL,
  `updated_at`        DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cheque_type`   (`cheque_type`),
  KEY `idx_direction`     (`direction`),
  KEY `idx_status`        (`status`),
  KEY `idx_cheque_number` (`cheque_number`),
  KEY `idx_source`        (`source_type`, `source_id`),
  KEY `idx_cheque_date`   (`cheque_date`),
  KEY `idx_is_pdc`        (`is_pdc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Audit trail for every status change
CREATE TABLE `tblcheque_transactions` (
  `id`          INT(11) NOT NULL AUTO_INCREMENT,
  `cheque_id`   INT(11) NOT NULL,
  `action`      VARCHAR(100) NOT NULL COMMENT 'received|verified|deposited|cleared|bounced|cancelled|issued|printed|void|approved|rejected',
  `from_status` TINYINT(2) NULL DEFAULT NULL,
  `to_status`   TINYINT(2) NULL DEFAULT NULL,
  `action_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id`     INT(11) NULL DEFAULT NULL,
  `notes`       TEXT NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_cheque_id` (`cheque_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
