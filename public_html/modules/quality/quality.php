<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Quality Management
Description: This module is a robust and highly configurable system designed to ensure end-to-end product quality throughout manufacturing, warehouse operations, and logistics
Version: 1.0.0
Requires at least: 2.3.*
*/

define('QUALITY_MODULE_NAME', 'quality');
define('QUALITY_MODULE_UPLOAD_FOLDER', module_dir_path(QUALITY_MODULE_NAME, 'uploads'));

/*add folder upload link on here*/
define('QUALITY_PRODUCT_UPLOAD', module_dir_path(QUALITY_MODULE_NAME, 'uploads/products/'));

/*link view on here*/
define('QUALITY_PRINT_ITEM', 'modules/quality/uploads/print_item/');
define('CONTROL_POINT_FOLDER', module_dir_path(QUALITY_MODULE_NAME, 'uploads/control_points/'));
define('CONTROL_POINT_UPLOAD_PATH', 'modules/quality/uploads/control_points/');
define('QUALITY_CHECK_FOLDER', module_dir_path(QUALITY_MODULE_NAME, 'uploads/quality_checks/'));
define('QUALITY_CHECK_UPLOAD_PATH', 'modules/quality/uploads/quality_checks/');
define('QUALITY_CHECK_DETAIL_FOLDER', module_dir_path(QUALITY_MODULE_NAME, 'uploads/quality_check_details/'));
define('QUALITY_CHECK_DETAIL_UPLOAD_PATH', 'modules/quality/uploads/quality_check_details/');
define('QUALITY_ALERT_FOLDER', module_dir_path(QUALITY_MODULE_NAME, 'uploads/quality_alerts/'));
define('QUALITY_ALERT_UPLOAD_PATH', 'modules/quality/uploads/quality_alerts/');

hooks()->add_action('admin_init', 'quality_permissions');
hooks()->add_action('app_admin_head', 'quality_add_head_components');
hooks()->add_action('app_admin_footer', 'quality_load_js');
hooks()->add_action('app_search', 'quality_load_search');
hooks()->add_action('admin_init', 'quality_module_init_menu_items');

hooks()->add_action('after_wh_goods_receipt_approve', 'quality_control_inventory_receipt');
hooks()->add_action('after_wh_goods_delivery_approve', 'quality_control_inventory_delivery');
hooks()->add_action('after_internal_delivery_approve', 'quality_control_internal_delivery');
hooks()->add_action('after_work_order_finished', 'quality_control_work_order');

hooks()->add_action('after_manufacturing_work_order_tab', 'init_tab_work_order_quality_checks');
hooks()->add_action('after_manufacturing_work_order_tab_content', 'init_content_work_order_quality_checks');
hooks()->add_action('mrp_work_order_load_js', 'quality_check_load_js');

hooks()->add_action('after_inventory_receipt_tab', 'init_tab_inventory_receipt_quality_checks');
hooks()->add_action('after_inventory_receipt_tab_content', 'init_content_inventory_receipt_quality_checks');
hooks()->add_action('inventory_receipt_load_js', 'quality_check_load_js');

hooks()->add_action('after_inventory_delivery_tab', 'init_tab_inventory_delivery_quality_checks');
hooks()->add_action('after_inventory_delivery_tab_content', 'init_content_inventory_delivery_quality_checks');
hooks()->add_action('inventory_delivery_load_js', 'quality_check_load_js');

hooks()->add_action('after_internal_delivery_tab', 'init_tab_internal_delivery_quality_checks');
hooks()->add_action('after_internal_delivery_tab_content', 'init_content_internal_delivery_quality_checks');
hooks()->add_action('internal_delivery_load_js', 'quality_check_load_js');

// Task related quality alert
hooks()->add_action('task_modal_rel_type_select', 'quality_alert_task_modal_rel_type_select'); // new
hooks()->add_filter('relation_values', 'quality_alert_get_relation_values', 10, 2); // new
hooks()->add_filter('get_relation_data', 'quality_alert_get_relation_data', 10, 4); // new
hooks()->add_filter('tasks_table_row_data', 'quality_alert_add_table_row', 10, 3);

//mail theme
register_merge_fields('quality/merge_fields/quality_check_merge_fields');
register_merge_fields('quality/merge_fields/quality_alert_merge_fields');

define('VERSION_QUALITY', 100);

/**
* Register activation module hook
*/
register_activation_hook(QUALITY_MODULE_NAME, 'quality_module_activation_hook');

function quality_module_activation_hook()
{
	$CI = &get_instance();
	require_once(__DIR__ . '/install.php');
}


/**
* Register language files, must be registered if the module is using languages
*/
register_language_files(QUALITY_MODULE_NAME, [QUALITY_MODULE_NAME]);


$CI = & get_instance();
$CI->load->helper(QUALITY_MODULE_NAME . '/quality');

/**
 * Init goals module menu items in setup in admin_init hook
 * @return null
 */
function quality_module_init_menu_items()
{   
	 $CI = &get_instance();

	 /*add menu on here*/

	 if(has_permission('quality_setting','','view') || has_permission('quality_control_point','','view') || has_permission('quality_check','','view') || has_permission('quality_check','','view_own') || has_permission('quality_alert','','view')|| has_permission('quality_alert','','view_own') || has_permission('quality_failure_location','','view')){
	 	
	 	$CI->app_menu->add_sidebar_menu_item('quality', [
	 		'name'     => _l('quality_quality_label'),
	 		'icon'     => 'fa-solid fa-medal', 
	 		'position' => 5,
	 	]);
	 }

	 if(has_permission('quality_dashboard','','view')){
		 $CI->app_menu->add_sidebar_children_item('quality', [
			'slug'     => 'quality_dashboard',
			'name'     => _l('quality_dashboard'),
			'icon'     => 'fa fa-dashboard',
			'href'     => admin_url('quality/dashboard'),
			'position' => 1,
		]);
	 }
	 if(has_permission('quality_control_point','','view')){
		 $CI->app_menu->add_sidebar_children_item('quality', [
			'slug'     => 'quality_control_point',
			'name'     => _l('quality_control_point_menu'),
			'icon'     => 'fa-solid fa-arrows-to-dot',
			'href'     => admin_url('quality/control_points'),
			'position' => 2,
		]);
	 }
	 if(has_permission('quality_check','','view') || has_permission('quality_check','','view_own')){
		 $CI->app_menu->add_sidebar_children_item('quality', [
			'slug'     => 'quality_quality_check',
			'name'     => _l('quality_quality_check_menu'),
			'icon'     => 'fa-solid fa-list-check',
			'href'     => admin_url('quality/quality_checks'),
			'position' => 3,
		]);
	 }
	 if(has_permission('quality_alert','','view') || has_permission('quality_alert','','view_own')){
		 $CI->app_menu->add_sidebar_children_item('quality', [
			'slug'     => 'quality_quality_alert',
			'name'     => _l('quality_quality_alert_menu'),
			'icon'     => 'fa-solid fa-circle-exclamation',
			'href'     => admin_url('quality/quality_alerts'),
			'position' => 4,
		]);
	 }
	 if(has_permission('quality_failure_location','','view')){
		 $CI->app_menu->add_sidebar_children_item('quality', [
			'slug'     => 'quality_failure_location',
			'name'     => _l('quality_failure_location_menu'),
			'icon'     => 'fa-solid fa-circle-xmark',
			'href'     => admin_url('quality/failure_locations'),
			'position' => 5,
		]);
	 }



	if(has_permission('quality_setting','','view')){
		 $CI->app_menu->add_sidebar_children_item('quality', [
			'slug'     => 'quality_setting',
			'name'     => _l('quality_settings'),
			'icon'     => 'fa fa-cog menu-icon',
			'href'     => admin_url('quality/setting?group=teams'),
			'position' => 10,
		]);
	 }


}

	/**
	 * quality load js
	 */
	function quality_load_js(){    
		$CI = &get_instance();    
		$viewuri = $_SERVER['REQUEST_URI'];
		
		if(!(strpos($viewuri,'admin/quality') === false) || !(strpos($viewuri,'admin/warehouse/manage_purchase') === false)){
			echo '<script src="' . module_dir_url(QUALITY_MODULE_NAME, 'assets/js/tinymce_init.js') .'?v=' . VERSION_QUALITY.'"></script>';
		}

		if(!(strpos($viewuri,'admin/quality/dashboard') === false)){

			echo '<script src="'.module_dir_url(QUALITY_MODULE_NAME, 'assets/plugins/highcharts/highcharts.js').'?v=' . VERSION_QUALITY.'"></script>';
			echo '<script src="'.module_dir_url(QUALITY_MODULE_NAME, 'assets/plugins/highcharts/variable-pie.js').'?v=' . VERSION_QUALITY.'"></script>';
			echo '<script src="'.module_dir_url(QUALITY_MODULE_NAME, 'assets/plugins/highcharts/export-data.js').'?v=' . VERSION_QUALITY.'"></script>';
			echo '<script src="'.module_dir_url(QUALITY_MODULE_NAME, 'assets/plugins/highcharts/accessibility.js').'?v=' . VERSION_QUALITY.'"></script>';
			echo '<script src="'.module_dir_url(QUALITY_MODULE_NAME, 'assets/plugins/highcharts/exporting.js').'?v=' . VERSION_QUALITY.'"></script>';
			echo '<script src="'.module_dir_url(QUALITY_MODULE_NAME, 'assets/plugins/highcharts/highcharts-3d.js').'?v=' . VERSION_QUALITY.'"></script>';
		}


	}


	/**
	 * quality add head components
	 */
	function quality_add_head_components(){    
		$CI = &get_instance();
		$viewuri = $_SERVER['REQUEST_URI'];

		if(!(strpos($viewuri,'admin/quality') === false)){
			echo '<link href="' . module_dir_url(QUALITY_MODULE_NAME, 'assets/css/styles.css') . '?v=' . VERSION_QUALITY. '"  rel="stylesheet" type="text/css" />';
		}

		if(!(strpos($viewuri,'admin/quality/dashboard') === false)){
			echo '<link href="' . module_dir_url(QUALITY_MODULE_NAME, 'assets/css/dashboards/dashboard.css') . '?v=' . VERSION_QUALITY. '"  rel="stylesheet" type="text/css" />';
		}

	}



	/**
	 * quality permissions
	 */
	function quality_permissions()
	{

		$capabilities = [];

		$capabilities['capabilities'] = [
				'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
				'create' => _l('permission_create'),
				'edit'   => _l('permission_edit'),
				'delete' => _l('permission_delete'),
		];

		$capabilities_with_view_own['capabilities'] = [
				'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
				'view_own'   => _l('permission_view_own'),
				'create' => _l('permission_create'),
				'edit'   => _l('permission_edit'),
				'delete' => _l('permission_delete'),
		];

		$view_capabilities['capabilities'] = [
			'view'   => _l('permission_view') . '(' . _l('permission_global') . ')',
		];

		
		register_staff_capabilities('quality_dashboard', $view_capabilities, _l('quality_permission_dashboard'));
		register_staff_capabilities('quality_setting', $capabilities, _l('quality_permission_settings'));
		register_staff_capabilities('quality_control_point', $capabilities, _l('quality_permission_control_points'));
		register_staff_capabilities('quality_check', $capabilities_with_view_own, _l('quality_permission_checks'));
		register_staff_capabilities('quality_alert', $capabilities_with_view_own, _l('quality_permission_alerts'));
		register_staff_capabilities('quality_failure_location', $capabilities, _l('quality_permission_failure_locations'));

	}

	function quality_appint(){
		// Bypass activation check
		return;
	}

	function quality_preactivate($module_name){
		// Bypass activation check
		return;
	}

	function quality_predeactivate($module_name){
		// Bypass deactivation
		return;
	}

	/**
	 * quality control inventory receipt
	 * @param  [type] $id 
	 * @return [type]     
	 */
	function quality_control_inventory_receipt($id)
	{
		$CI = &get_instance();
		$CI->load->model('quality/quality_model');
		$CI->quality_model->handle_create_quality_check($id, 'receipts');
		return $id;
	}

	/**
	 * quality control inventory receipt
	 * @param  [type] $id 
	 * @return [type]     
	 */
	function quality_control_inventory_delivery($id)
	{
		$CI = &get_instance();
		$CI->load->model('quality/quality_model');
		$CI->quality_model->handle_create_quality_check($id, 'delivery_orders');
		return $id;
	}

	/**
	 * quality control inventory receipt
	 * @param  [type] $id 
	 * @return [type]     
	 */
	function quality_control_internal_delivery($id)
	{
		$CI = &get_instance();
		$CI->load->model('quality/quality_model');
		$CI->quality_model->handle_create_quality_check($id, 'internal_transfers');
		return $id;
	}

	/**
	 * quality control inventory receipt
	 * @param  [type] $id 
	 * @return [type]     
	 */
	function quality_control_work_order($id)
	{
		$CI = &get_instance();
		$CI->load->model('quality/quality_model');
		$CI->quality_model->handle_create_quality_check($id, 'manufacturing');
		return $id;
	}

	/**
	 * init tab work order quality checks
	 * @return [type] 
	 */
	function init_tab_work_order_quality_checks() {
		echo '<li role="presentation">
		<a href="#quality_check" aria-controls="quality_check" role="tab" data-toggle="tab">
		' . _l('quality_checks') . '
		</a>
		</li>';
	}

	/**
	 * init content work order quality checks
	 * @param  [type] $work_order 
	 * @return [type]             
	 */
	function init_content_work_order_quality_checks($work_order) {
		$CI = &get_instance();

		if ($work_order) {
			$data['work_order'] = $work_order;
			$data['rel_type'] = 'manufacturing';
			$data['rel_id'] = $work_order->id;

			$CI->load->view('quality/module_hooks/quality_checks/quality_check_tab.php', $data);
		}
	}

	/**
	 * quality check load js
	 * @return [type] 
	 */
	function quality_check_load_js() {
		$CI = &get_instance();
		$viewuri = $_SERVER['REQUEST_URI'];
		$CI->load->view('quality/module_hooks/quality_checks/quality_check_js.php');
	}

	/**
	 * init tab inventory receipt quality checks
	 * @return [type] 
	 */
	function init_tab_inventory_receipt_quality_checks() {
		echo '<li role="presentation">
		<a href="#quality_check" aria-controls="quality_check" role="tab" data-toggle="tab">
		' . _l('quality_checks') . '
		</a>
		</li>';
	}

	/**
	 * init content inventory receipt quality checks
	 * @param  [type] $inventory_receipt 
	 * @return [type]                    
	 */
	function init_content_inventory_receipt_quality_checks($inventory_receipt) {
		$CI = &get_instance();

		if ($inventory_receipt) {
			$data['inventory_receipt'] = $inventory_receipt;
			$data['rel_type'] = 'receipts';
			$data['rel_id'] = $inventory_receipt->id;
			$CI->load->view('quality/module_hooks/quality_checks/quality_check_tab', $data);
		}
	}

	/**
	 * init tab inventory delivery quality checks
	 * @return [type] 
	 */
	function init_tab_inventory_delivery_quality_checks() {
		echo '<li role="presentation">
		<a href="#quality_check" aria-controls="quality_check" role="tab" data-toggle="tab">
		' . _l('quality_checks') . '
		</a>
		</li>';
	}

	/**
	 * init content inventory delivery quality checks
	 * @param  [type] $inventory_delivery 
	 * @return [type]                     
	 */
	function init_content_inventory_delivery_quality_checks($inventory_delivery) {
		$CI = &get_instance();

		if ($inventory_delivery) {
			$data['inventory_delivery'] = $inventory_delivery;
			$data['rel_type'] = 'delivery_orders';
			$data['rel_id'] = $inventory_delivery->id;
			$CI->load->view('quality/module_hooks/quality_checks/quality_check_tab', $data);
		}
	}

	/**
	 * init tab internal delivery quality checks
	 * @return [type] 
	 */
	function init_tab_internal_delivery_quality_checks() {
		echo '<li role="presentation">
		<a href="#quality_check" aria-controls="quality_check" role="tab" data-toggle="tab">
		' . _l('quality_checks') . '
		</a>
		</li>';
	}

	/**
	 * init tab internal delivery quality checks
	 * @return [type] 
	 */
	function init_content_internal_delivery_quality_checks($internal_delivery) {
		$CI = &get_instance();

		if ($internal_delivery) {
			$data['internal_delivery'] = $internal_delivery;
			$data['rel_type'] = 'internal_transfers';
			$data['rel_id'] = $internal_delivery->id;
			$CI->load->view('quality/module_hooks/quality_checks/quality_check_tab', $data);
		}
	}
	
	/**
	 * quality alert task modal rel type select
	 * @param  [type] $value 
	 * @return [type]        
	 */
	function quality_alert_task_modal_rel_type_select($value) {
		$selected = '';
		if (isset($value) && isset($value['rel_type']) && $value['rel_type'] == 'quality_alert') {
			$selected = 'selected';
		}
		echo "<option value='quality_alert' " . $selected . ">" .
		_l('quality_alert') . "
		</option>";

	}

	/**
	 * quality alert get relation values
	 * @param  [type] $values   
	 * @param  [type] $relation 
	 * @return [type]           
	 */
	function quality_alert_get_relation_values($values, $relation = null) {
		if ($values['type'] == 'quality_alert') {
			if (is_array($relation)) {
				$values['id'] = $relation['id'];
				$values['name'] = format_quality_alert_number($relation['id']);
			} else {
				$values['id'] = $relation->id;
				$values['name'] = format_quality_alert_number($relation->id);
			}

			$CI = &get_instance();
			$CI->load->model('quality/quality_model');
			$quality_alert = $CI->quality_model->get_quality_alert($values['id']);
			if($quality_alert){
				$values['link'] = admin_url('quality/quality_alert_detail/' . $values['id'].'/?tab=detail');
			}else{
				$values['link'] = '';
			}

		}

		return $values;
	}

	/**
	 * quality alert get relation_data
	 * @param  [type] $data 
	 * @param  [type] $obj  
	 * @return [type]       
	 */
	function quality_alert_get_relation_data($data, $obj) {
		$type = $obj['type'];
		$rel_id = $obj['rel_id'];
		$CI = &get_instance();
		$CI->load->model('quality/quality_model');

		if ($type == 'quality_alert') {
			if ($rel_id != '') {
				$data = $CI->quality_model->get_quality_alert($rel_id);
			} else {
				$data = [];
			}
		}

		return $data;
	}

	/**
	 * quality alert add table row
	 * @param  [type] $row  
	 * @param  [type] $aRow 
	 * @return [type]       
	 */
	function quality_alert_add_table_row($row ,$aRow)
	{

		$CI = &get_instance();
		$CI->load->model('quality/quality_model');

		if($aRow['rel_type'] == 'quality_alert'){
			$quality_alert = $CI->quality_model->get_quality_alert($aRow['rel_id']);

			if ($quality_alert) {
				$str = '<span class="hide"> - </span><a class="text-muted task-table-related" data-toggle="tooltip" title="' . _l('task_related_to') . '" href="' .admin_url('quality/quality_alert_detail/' . $quality_alert->id.'/?tab=detail').'">' . format_quality_alert_number($quality_alert->id) . '</a><br /><div class="row-options">';

				$row[2] =  new_str_replace('<div class="row-options">', $str, $row[2]);
			}
		}
		return $row;
	}
