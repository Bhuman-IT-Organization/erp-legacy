<?php
defined('BASEPATH') or exit('No direct script access allowed');
add_option("quality_control_point_prefix", 'QCP-', 1);
add_option("quality_control_point_number", 1, 1);
add_option('quality_control_point_number_format', 1);
add_option("quality_check_prefix", 'QC-', 1);
add_option("quality_check_number", 1, 1);
add_option('quality_check_number_format', 1);
add_option("quality_alert_prefix", 'QA-', 1);
add_option("quality_alert_number", 1, 1);
add_option('quality_alert_number_format', 1);
add_option("failure_location_prefix", 'FL-', 1);
add_option("failure_location_number", 1, 1);
add_option('failure_location_number_format', 1);

if (!$CI->db->table_exists(db_prefix() . 'quality_teams')) {
  $CI->db->query('CREATE TABLE `' . db_prefix() . "quality_teams` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` TEXT NULL,
    `team_member` INT(11) NULL,
    `status` INT(11) NOT NULL DEFAULT '1',
    `datecreated` datetime NULL,
    `staffid` INT(11) NULL,

    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}

if (!$CI->db->table_exists(db_prefix() . 'quality_control_points')) {
  $CI->db->query('CREATE TABLE `' . db_prefix() . "quality_control_points` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` TEXT NULL,
    `number` int(11) NOT NULL,
    `prefix` varchar(50) DEFAULT NULL,
    `number_format` int(11) NOT NULL DEFAULT '0',
    `hash` varchar(32) DEFAULT NULL,
    `item_id` INT(11) NULL,
    `item_group` INT(11) NULL,
    `operation` TEXT NULL COMMENT 'receipts-internal_transfers-delivery_orders-pick_components-manufacturing-resupply_subcontractor',
    `mrp_routing_detail_id` INT(11) NULL,
    `control_per` TEXT NULL COMMENT 'product-operation-quantity',
    `partial_transfer_test` INT(1) NULL DEFAULT '1',
    `percentage` DECIMAL(15,2) NULL DEFAULT '0.00',
    `control_frequency` TEXT NULL COMMENT 'all-randomly-periodically',
    `every_percent_of_transfer` DECIMAL(15,2) NULL DEFAULT '0.00',
    `periodically_every_custom` INT(11) NULL DEFAULT '0',
    `periodically_type_custom` TEXT NULL, 
    `type` TEXT NULL,
    `team_id` INT(11) NULL,
    `responsible_id` INT(11) NULL,
    `step_document` INT(11) NULL,
    `worksheet_page` INT(11) NULL,
    `instructions` LONGTEXT NULL,
    `message_if_failure` LONGTEXT NULL,
    `note` LONGTEXT NULL,
    `status` INT(11) NOT NULL DEFAULT '1',
    `type_of_unit_of_measurement` TEXT NULL,
    `norm` DECIMAL(15,2) NULL DEFAULT '0.00',
    `unit_of_measurement` TEXT NULL,
    `tolerance_from` DECIMAL(15,2) NULL DEFAULT '0.00',
    `tolerance_to` DECIMAL(15,2) NULL DEFAULT '0.00',
    `last_recurring_date` date DEFAULT NULL,
    `failure_location_ids` TEXT NULL,
    `datecreated` datetime NULL,
    `staffid` INT(11) NULL,

    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}


if (!$CI->db->field_exists('last_recurring_date' ,db_prefix() . 'quality_control_points')) { 
  $CI->db->query('ALTER TABLE `' . db_prefix() . "quality_control_points`
    ADD COLUMN `last_recurring_date` date DEFAULT NULL
    ;");
}


if (!$CI->db->table_exists(db_prefix() . 'quality_activity')) {
  $CI->db->query('CREATE TABLE `' . db_prefix() . "quality_activity` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `rel_type` varchar(20) DEFAULT NULL,
    `rel_id` int(11) NOT NULL,
    `description` text NOT NULL,
    `additional_data` text,
    `staffid` varchar(11) DEFAULT NULL,
    `full_name` varchar(100) DEFAULT NULL,
    `date` datetime NOT NULL,

    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}

if (!$CI->db->table_exists(db_prefix() . 'quality_checks')) {
  $CI->db->query('CREATE TABLE `' . db_prefix() . "quality_checks` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` TEXT NULL,
    `number` int(11) NOT NULL,
    `prefix` varchar(50) DEFAULT NULL,
    `number_format` int(11) NOT NULL DEFAULT '0',
    `hash` varchar(32) DEFAULT NULL,
    `item_id` INT(11) NULL,
    `control_per` TEXT NULL COMMENT 'product-operation-quantity',
    `partial_transfer_test` INT(1) NULL DEFAULT '1',
    `percentage` DECIMAL(15,2) NULL DEFAULT '0.00',
    `operation` TEXT NULL COMMENT 'receipts-internal_transfers-delivery_orders-pick_components-manufacturing-resupply_subcontractor',
    `manufacturing_order_id` INT(11) NULL DEFAULT '0',
    `work_order_id` INT(11) NULL DEFAULT '0',
    `goods_receipt_id` INT(11) NULL DEFAULT '0',
    `goods_delivery_id` INT(11) NULL DEFAULT '0',
    `internal_delivery_id` INT(11) NULL DEFAULT '0',
    `control_point_id` INT(11) NULL,
    `company` TEXT NULL,
    `type` TEXT NULL,
    `team_id` INT(11) NULL,
    `instructions` LONGTEXT NULL,
    `message_if_failure` LONGTEXT NULL,
    `note` LONGTEXT NULL,
    `status` TEXT NULL,
    `type_of_unit_of_measurement` TEXT NULL,
    `norm` DECIMAL(15,2) NULL DEFAULT '0.00',
    `unit_of_measurement` TEXT NULL,
    `tolerance_from` DECIMAL(15,2) NULL DEFAULT '0.00',
    `tolerance_to` DECIMAL(15,2) NULL DEFAULT '0.00',
    `failure_location_ids` TEXT NULL,
    `datecreated` datetime NULL,
    `staffid` INT(11) NULL,

    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}

if (!$CI->db->table_exists(db_prefix() . 'quality_check_details')) {
  $CI->db->query('CREATE TABLE `' . db_prefix() . "quality_check_details` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `quality_check_id` int(11) NULL,
    `check_work_order_id` INT(11) NULL DEFAULT '0',
    `check_item_id` INT(11) NULL DEFAULT '0',
    `actual_size` DECIMAL(15,2) NULL DEFAULT '0.00',
    `description` LONGTEXT NULL,
    `quality_check_results` LONGTEXT NULL,
    `status` TEXT NULL,
    `completion_date` datetime NULL,
    `datecreated` datetime NULL,
    `staffid` INT(11) NULL,

    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}


if (!$CI->db->table_exists(db_prefix() . 'quality_alerts')) {
  $CI->db->query('CREATE TABLE `' . db_prefix() . "quality_alerts` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` TEXT NULL,
    `number` int(11) NOT NULL,
    `prefix` varchar(50) DEFAULT NULL,
    `number_format` int(11) NOT NULL DEFAULT '0',
    `hash` varchar(32) DEFAULT NULL,
    `item_id` INT(11) NULL,
    `work_center_id` INT(11) NULL,
    `operation` TEXT NULL COMMENT 'receipts-internal_transfers-delivery_orders-pick_components-manufacturing-resupply_subcontractor',
    `manufacturing_order_id` INT(11) NULL DEFAULT '0',
    `work_order_id` INT(11) NULL DEFAULT '0',
    `goods_receipt_id` INT(11) NULL DEFAULT '0',
    `goods_delivery_id` INT(11) NULL DEFAULT '0',
    `internal_delivery_id` INT(11) NULL DEFAULT '0',
    `quality_check_id` INT(11) NULL,
    `team_id` INT(11) NULL,
    `responsible_id` INT(11) NULL,
    `root_cause` TEXT NULL,
    `priority` TEXT NULL,
    `description` LONGTEXT NULL,
    `corrective_actions` LONGTEXT NULL,
    `preventive_actions` LONGTEXT NULL,
    `vendor_id` INT(11) NULL,
    `date_assigned` DATETIME NULL,
    `status` TEXT NULL,
    `datecreated` datetime NULL,
    `staffid` INT(11) NULL,

    PRIMARY KEY (`id`)
  ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ';');
}

if (!$CI->db->field_exists('failure_location_ids' ,db_prefix() . 'quality_control_points')) { 
  $CI->db->query('ALTER TABLE `' . db_prefix() . "quality_control_points`
    ADD COLUMN `failure_location_ids` TEXT NULL
    ;");
}
if (!$CI->db->field_exists('mrp_routing_detail_id' ,db_prefix() . 'quality_control_points')) { 
  $CI->db->query('ALTER TABLE `' . db_prefix() . "quality_control_points`
    ADD COLUMN `mrp_routing_detail_id` INT(11) NULL
    ;");
}

if (!$CI->db->field_exists('failure_location_ids' ,db_prefix() . 'quality_checks')) { 
  $CI->db->query('ALTER TABLE `' . db_prefix() . "quality_checks`
    ADD COLUMN `failure_location_ids` TEXT NULL
    ;");
}

if (!$CI->db->field_exists('send_product_failure_location' ,db_prefix() . 'quality_checks')) { 
  $CI->db->query('ALTER TABLE `' . db_prefix() . "quality_checks`
    ADD COLUMN `send_product_failure_location` INT(11) NULL DEFAULT '0'
    ;");
}

create_email_template('Quality Check {quality_check_number}', '<span>Dear {check_by_staff}</span><br /><br /><span>This email is to request your review of {operation_type}. Your feedback is crucial to ensuring we maintain high quality standards. <br /> Please find the attached quality check {quality_check_link}<br /><br /><br /><span>Kind Regards,</span><br /><span>{email_signature}<br /></span>', 'quality', 'Send Quality Check to Staff', 'quality-check-send-to-staff');

create_email_template('Quality Alert {quality_alert_number}', '<span>Dear {alert_by_staff}</span><br /><br /><span>This email is to alert {operation_type}. Please review to ensure we maintain high quality standards. <br /> Please find the attached quality alert {quality_alert_link}<br /><br /><br /><span>Kind Regards,</span><br /><span>{email_signature}<br /></span>', 'quality', 'Send Quality Alert to Staff', 'quality-alert-send-to-staff');