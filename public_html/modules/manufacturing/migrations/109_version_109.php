<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Migration_Version_109 extends App_module_migration
{
	public function up()
	{
		$CI = &get_instance();
		if (mrp_row_options_exists('"lot_number_from_mo_number"') == 0){
			$CI->db->query('INSERT INTO `'.db_prefix().'mrp_option` (`option_name`,`option_val`, `auto`) VALUES ("lot_number_from_mo_number", "0", "1");
				');
		}
	}
}
