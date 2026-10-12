<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_108 extends App_module_migration
{
	public function up()
	{
		$CI = &get_instance();
		if ($CI->db->table_exists(db_prefix() . 'mrp_manufacturing_orders')) {
			if (!$CI->db->field_exists('acc_mapping' ,db_prefix() . 'mrp_manufacturing_orders')) {
				$CI->db->query("ALTER TABLE `" . db_prefix() . "mrp_manufacturing_orders`
					ADD COLUMN `acc_mapping` tinyint(1) NOT NULL DEFAULT '0'
					");
			}
		}
	}
}
