<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_106 extends App_module_migration
{
    public function up()
    {
        $CI = &get_instance();
        if (!$CI->db->field_exists('purchase_prices' ,db_prefix() . 'mrp_manufacturing_order_details')) { 
            $CI->db->query('ALTER TABLE `' . db_prefix() . "mrp_manufacturing_order_details`
              ADD COLUMN `purchase_prices` TEXT NULL,
              ADD COLUMN `temp_quantity_with_purchase_price` TEXT NULL,
              ADD COLUMN `serial_numbers` TEXT NULL,
              ADD COLUMN `old_quantities` TEXT NULL,
              ADD COLUMN `inventory_manage_id` INT(11) NULL

              ;");
        }

        if (!$CI->db->field_exists('mo_detail_id' ,db_prefix() . 'goods_delivery_detail')) { 
            $CI->db->query('ALTER TABLE `' . db_prefix() . "goods_delivery_detail`
              ADD COLUMN `mo_detail_id` INT(11) NULL DEFAULT '0'
              ;");
        }
    }
}
