<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_107 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        if (!$CI->db->table_exists(db_prefix() . 'mrp_approval_setting')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() .'mrp_approval_setting` (
              `id` INT NOT NULL AUTO_INCREMENT,
              `name` VARCHAR(255) NOT NULL,
              `related` VARCHAR(255) NOT NULL,
              `setting` LONGTEXT NOT NULL,
              `approval_type` INT(11) NULL DEFAULT "0" COMMENT "0: All  1: only one",
              PRIMARY KEY (`id`));');
        }

        if (!$CI->db->table_exists(db_prefix() . 'mrp_approval_details')) {
            $CI->db->query('CREATE TABLE `' . db_prefix() .'mrp_approval_details` (
              `id` INT(11) NOT NULL AUTO_INCREMENT,
              `rel_id` INT(11) NOT NULL,
              `rel_type` VARCHAR(45) NOT NULL,
              `staffid` VARCHAR(45) NULL,
              `approve` VARCHAR(45) NULL,
              `note` TEXT NULL,
              `date` DATETIME NULL,
              `approve_action` VARCHAR(255) NULL,
              `reject_action` VARCHAR(255) NULL,
              `approve_value` VARCHAR(255) NULL,
              `reject_value` VARCHAR(255) NULL,
              `staff_approve` INT(11) NULL,
              `action` VARCHAR(45) NULL,
              `sender` INT(11) NULL,
              `date_send` DATETIME NULL,

              PRIMARY KEY (`id`));');
        }

        if (!$CI->db->field_exists('approval' ,db_prefix() . 'mrp_manufacturing_orders')) { 
            $CI->db->query('ALTER TABLE `' . db_prefix() . "mrp_manufacturing_orders`
              ADD COLUMN `approval` INT(11) NULL DEFAULT '1'

              ;");
        }
        if (!$CI->db->field_exists('addedfrom' ,db_prefix() . 'mrp_manufacturing_orders')) { 
            $CI->db->query('ALTER TABLE `' . db_prefix() . "mrp_manufacturing_orders`
              ADD COLUMN `addedfrom` INT(11) NULL DEFAULT '0'
              ;");
        }
    }
}
