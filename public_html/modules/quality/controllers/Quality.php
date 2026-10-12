<?php

defined('BASEPATH') or exit('No direct script access allowed');
/**
 * Class Quality
 */
class Quality extends AdminController
{
	/**
	 * __construct
	 */
	public function __construct()
	{
		parent::__construct();
		$this->load->model('quality_model');
		hooks()->do_action('quality_init');

	}

	/**
     * setting
     * @return [type] 
     */
	public function setting()
	{
		if (!has_permission('quality_setting', '', 'edit') && !is_admin() && !has_permission('quality_setting', '', 'create') && !has_permission('quality_setting', '', 'delete')) {
			access_denied('quality_setting');
		}

		$data['group'] = $this->input->get('group');
		$data['title'] = _l('setting');

		$data['tab'][] = 'prefixs';
		$data['tab'][] = 'teams';
		$data['tab'][] = 'permissions';
		if(is_admin()){
			$data['tab'][] = 'reset_data';
		}

		if ($data['group'] == 'teams') {
			$data['tabs']['view'] = 'settings/quality_teams/quality_team';
			$data['staffs'] = $this->staff_model->get();
		}elseif($data['group'] == 'prefixs'){
			$data['tabs']['view'] = 'settings/prefixs/' . $data['group'];
		}elseif($data['group'] == 'permissions'){
			$data['tabs']['view'] = 'settings/permissions/' . $data['group'];
		}elseif($data['group'] == 'reset_data'){
			$data['tabs']['view'] = 'settings/reset_data/' . $data['group'];
		}

		$this->load->view('settings/manage_setting', $data);
	}

    /**
     * prefix number
     * @return [type] 
     */
    public function prefix_number()
    {
    	if (!has_permission('quality_setting', '', 'edit') && !is_admin() && !has_permission('quality_setting', '', 'create')) {
    		access_denied('pl_prefixs');
    	}

    	$data = $this->input->post();

    	if ($data) {

    		$success = $this->quality_model->update_prefix_number($data);
    		if ($success == true) {
    			$message = _l('updated_successfully');
    			set_alert('success', $message);
    		}

    		redirect(admin_url('quality/setting?group=prefixs'));
    	}
    }

    /**
     * real permission table
     * @return [type] 
     */
    public function quality_permission_table() {
    	if ($this->input->is_ajax_request()) {

    		$select = [
    			'staffid',
    			'CONCAT(firstname," ",lastname) as full_name',
                'firstname', //for role name
                'email',
                'phonenumber',
            ];
            $where = [];
            $where[] = 'AND ' . db_prefix() . 'staff.admin != 1';

            $arr_staff_id = quality_get_staff_id_permissions();

            if (count($arr_staff_id) > 0) {
            	$where[] = 'AND ' . db_prefix() . 'staff.staffid IN (' . implode(', ', $arr_staff_id) . ')';
            } else {
            	$where[] = 'AND ' . db_prefix() . 'staff.staffid IN ("")';
            }

            $aColumns = $select;
            $sIndexColumn = 'staffid';
            $sTable = db_prefix() . 'staff';
            $join = ['LEFT JOIN ' . db_prefix() . 'roles ON ' . db_prefix() . 'roles.roleid = ' . db_prefix() . 'staff.role'];

            $result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [db_prefix() . 'roles.name as role_name', db_prefix() . 'staff.role']);

            $output = $result['output'];
            $rResult = $result['rResult'];

            $not_hide = '';

            foreach ($rResult as $aRow) {
            	$row = [];

            	$row[] = '<a href="' . admin_url('staff/member/' . $aRow['staffid']) . '">' . $aRow['full_name'] . '</a>';

            	$row[] = $aRow['role_name'];
            	$row[] = $aRow['email'];
            	$row[] = $aRow['phonenumber'];

            	$options = '';

            	if (has_permission('quality_setting', '', 'edit')) {
            		$options = icon_btn('#', 'fa-regular fa-pen-to-square', 'btn-default', [
            			'title' => _l('edit'),
            			'onclick' => 'quality_permissions_update(' . $aRow['staffid'] . ', ' . $aRow['role'] . ', ' . $not_hide . '); return false;',
            		]);
            	}

            	if (has_permission('quality_setting', '', 'delete')) {
            		$options .= icon_btn('quality/delete_quality_permission/' . $aRow['staffid'], 'fa fa-remove', 'btn-danger _delete', ['title' => _l('delete')]);
            	}

            	$row[] = $options;

            	$output['aaData'][] = $row;
            }

            echo json_encode($output);
            die();
        }
    }

    /**
     * permission modal
     * @return [type] 
     */
    public function permission_modal() {
    	if (!$this->input->is_ajax_request()) {
    		show_404();
    	}
    	$this->load->model('staff_model');

    	if ($this->input->post('slug') === 'update') {
    		$staff_id = $this->input->post('staff_id');
    		$role_id = $this->input->post('role_id');

    		$data = ['funcData' => ['staff_id' => isset($staff_id) ? $staff_id : null]];

    		if (isset($staff_id)) {
    			$data['member'] = $this->staff_model->get($staff_id);
    		}

    		$data['roles_value'] = $this->roles_model->get();
    		$data['staffs'] = quality_get_staff_id_dont_permissions();
    		$add_new = $this->input->post('add_new');

    		if ($add_new == ' hide') {
    			$data['add_new'] = ' hide';
    			$data['display_staff'] = '';
    		} else {
    			$data['add_new'] = '';
    			$data['display_staff'] = ' hide';
    		}

    		$this->load->view('settings/permissions/permission_modal', $data);
    	}
    }

    /**
     * quality update permissions
     * @param  string $id 
     * @return [type]     
     */
    public function quality_update_permissions($id = '') {
    	if (!is_admin() && !has_permission('quality_setting', '', 'create') && !has_permission('quality_setting', '', 'edit')) {
    		access_denied('pl_permission');
    	}
    	$data = $this->input->post();

    	if (!isset($id) || $id == '') {
    		$id = $data['staff_id'];
    	}

    	if (isset($id) && $id != '') {

    		$data = hooks()->apply_filters('before_update_staff_member', $data, $id);

    		if (is_admin()) {
    			if (isset($data['administrator'])) {
    				$data['admin'] = 1;
    				unset($data['administrator']);
    			} else {
    				if ($id != get_staff_user_id()) {
    					if ($id == 1) {
    						return [
    							'cant_remove_main_admin' => true,
    						];
    					}
    				} else {
    					return [
    						'cant_remove_yourself_from_admin' => true,
    					];
    				}
    				$data['admin'] = 0;
    			}
    		}

    		$this->db->where('staffid', $id);
    		$this->db->update(db_prefix() . 'staff', [
    			'role' => $data['role'],
    		]);

    		$response = $this->staff_model->update_permissions((isset($data['admin']) && $data['admin'] == 1 ? [] : $data['permissions']), $id);
    	} else {
    		$this->load->model('roles_model');

    		$role_id = $data['role'];
    		unset($data['role']);
    		unset($data['staff_id']);

    		$data['update_staff_permissions'] = true;

    		$response = $this->roles_model->update($data, $role_id);
    	}

    	if (is_array($response)) {
    		if (isset($response['cant_remove_main_admin'])) {
    			set_alert('warning', _l('staff_cant_remove_main_admin'));
    		} elseif (isset($response['cant_remove_yourself_from_admin'])) {
    			set_alert('warning', _l('staff_cant_remove_yourself_from_admin'));
    		}
    	} elseif ($response == true) {
    		set_alert('success', _l('updated_successfully', _l('staff_member')));
    	}
    	redirect(admin_url('quality/setting?group=permissions'));

    }

    /**
     * delete quality permission
     * @param  [type] $id 
     * @return [type]     
     */
    public function delete_quality_permission($id) {
    	if (!is_admin()) {
    		access_denied('hr_profile');
    	}

    	$response = $this->quality_model->delete_quality_permission($id);

    	if (is_array($response) && isset($response['referenced'])) {
    		set_alert('warning', _l('hr_is_referenced', _l('department_lowercase')));
    	} elseif ($response == true) {
    		set_alert('success', _l('deleted', _l('hr_department')));
    	} else {
    		set_alert('warning', _l('problem_deleting', _l('department_lowercase')));
    	}
    	redirect(admin_url('quality/setting?group=permissions'));

    }

    /**
     * role changed
     * @param  [type] $id 
     * @return [type]     
     */
    public function role_changed($id)
    {
    	echo json_encode($this->roles_model->get($id)->permissions);
    }

    /**
     * team member table
     * @return [type] 
     */
    public function team_member_table()
    {
    	$this->app->get_table_data(module_views_path('quality', 'settings/quality_teams/quality_team_table'));
    }

    /**
     * change team member status
     * @param  [type] $id     
     * @param  [type] $status 
     * @return [type]         
     */
    public function change_quality_team_status($id, $status) {
    	if (has_permission('quality_setting', '', 'edit')) {
    		if ($this->input->is_ajax_request()) {
    			$this->quality_model->change_quality_team_status($id, (int)$status);
    		}
    	}
    }

    /**
     * quality_team
     * @return [type] 
     */
    public function quality_team()
    {
    	$message = '';
    	$success = false;
    	if ($this->input->post()) {
    		if ($this->input->post('id')) {
    			$id = $this->input->post('id');
    			$data = $this->input->post();
    			if(isset($data['id'])){
    				unset($data['id']);
    			}

    			$success = $this->quality_model->update_quality_team($data, $id);
    			if ($success == true) {
    				$message = _l('updated_successfully', _l('quality_team'));
    			}
    		} else {
    			$success = $this->quality_model->add_quality_team($this->input->post());
    			if ($success == true) {
    				$message = _l('added_successfully', _l('quality_team'));
    			}
    		}
    	}
    	echo json_encode([
    		'success' => $success,
    		'message' => $message,
    	]);die;
    }

    /**
     * delete quality_team
     * @param  [type] $id 
     * @return [type]     
     */
    public function delete_quality_team($id)
    {
    	if (!$id) {
    		redirect(admin_url('quality/setting?group=teams'));
    	}

    	if(!has_permission('quality_setting', '', 'delete')  &&  !is_admin()) {
    		access_denied('quality_team');
    	}

    	$response = $this->quality_model->delete_quality_team($id);
    	if ($response) {
    		set_alert('success', _l('deleted'));
    		redirect(admin_url('quality/setting?group=teams'));
    	} else {
    		set_alert('warning', _l('problem_deleting'));
    		redirect(admin_url('quality/setting?group=teams'));
    	}

    }

    /**
     * quality team exists
     * @return [type] 
     */
    public function quality_team_days_off_exists()
    {
    	if ($this->input->is_ajax_request()) {
    		if ($this->input->post()) {
                // First we need to check if Day off is the same
    			$id = $this->input->post('id');
    			if ($id != '') {
    				$this->db->where('id != '.$id);
    				$_current_quality_team = $this->db->get(db_prefix() . 'quality_teams')->row();
    				if ($_current_quality_team->name == ($this->input->post('name'))) {
    					echo json_encode(false);
    					die();
    				}else{
                        echo json_encode(true);
                        die();
                    }
    			}
    			$this->db->where('name', ($this->input->post('name')));
    			$total_rows = $this->db->count_all_results(db_prefix() . 'quality_teams');
    			if ($total_rows > 0) {
    				echo json_encode(false);
    			} else {
    				echo json_encode(true);
    			}
    			die();
    		}
    	}
    }

    /**
     * reset data
     * @return [type] 
     */
	public function reset_data()
	{

		if ( !is_admin()) {
			access_denied('quality_reset_data');
		}

            //delete pl_ecos
        $this->db->truncate(db_prefix().'quality_control_points');
        $this->db->truncate(db_prefix().'quality_activity');
        $this->db->truncate(db_prefix().'quality_checks');
        $this->db->truncate(db_prefix().'quality_check_details');
        $this->db->truncate(db_prefix().'quality_alerts');

        //delete sub folder CONTROL_POINT_UPLOAD_PATH
		foreach(glob(CONTROL_POINT_UPLOAD_PATH . '*') as $file) { 
			$file_arr = new_explode("/",$file);
			$filename = array_pop($file_arr);

			if(is_dir($file)) {
				delete_dir(CONTROL_POINT_UPLOAD_PATH.$filename);
			}
		}

        foreach(glob(QUALITY_CHECK_UPLOAD_PATH . '*') as $file) { 
            $file_arr = new_explode("/",$file);
            $filename = array_pop($file_arr);

            if(is_dir($file)) {
                delete_dir(QUALITY_CHECK_UPLOAD_PATH.$filename);
            }
        }
        foreach(glob(QUALITY_CHECK_DETAIL_UPLOAD_PATH . '*') as $file) { 
            $file_arr = new_explode("/",$file);
            $filename = array_pop($file_arr);

            if(is_dir($file)) {
                delete_dir(QUALITY_CHECK_DETAIL_UPLOAD_PATH.$filename);
            }
        }
        foreach(glob(QUALITY_ALERT_UPLOAD_PATH . '*') as $file) { 
            $file_arr = new_explode("/",$file);
            $filename = array_pop($file_arr);

            if(is_dir($file)) {
                delete_dir(QUALITY_ALERT_UPLOAD_PATH.$filename);
            }
        }
        
        // //delete create task rel_type: "quality_alert"
        $this->db->where('rel_type = "quality_alert"');
        $this->db->delete(db_prefix() . 'tasks');

        // //delete create tag rel_type: "quality_alert"
        $this->db->where('rel_type = "quality_alert"');
        $this->db->delete(db_prefix() . 'taggables');
        

		set_alert('success',_l('quality_reset_data_successful'));

		redirect(admin_url('quality/setting?group=reset_data'));
	}

	/**
     * staff id changed
     * @param  [type] $staff_id
     * @return [type]
     */
    public function staff_id_changed($staff_id) {
        $role_id = '';
        $status = 'false';
        $r_permission = [];

        $staff = $this->staff_model->get($staff_id);

        if ($staff) {
            if (count($staff->permissions) > 0) {
                foreach ($staff->permissions as $permission) {
                    $r_permission[$permission['feature']][] = $permission['capability'];
                }
            }

            $role_id = $staff->role;
            $status = 'true';

        }

        if (count($r_permission) > 0) {
            $data = ['role_id' => $role_id, 'status' => $status, 'permission' => 'true', 'r_permission' => $r_permission];
        } else {
            $data = ['role_id' => $role_id, 'status' => $status, 'permission' => 'false', 'r_permission' => $r_permission];
        }

        echo json_encode($data);
        die;
    }

    /**
     * control_points
     * @control_point [type] 
     */
    public function control_points()
    {
        qm_token();
        if (!has_permission('quality_control_point', '', 'view') && !has_permission('quality_control_point', '', 'view_own')) {
            access_denied('quality_control_point');
        }
        qm_init();
        $this->load->model('invoice_items_model');
        $data['title'] = _l('quality_control_point');
        $data['quality_control_frequency'] = quality_control_frequency();
        $data['quality_control_type'] = quality_control_type();
        $data['items'] = $this->invoice_items_model->get();
        $required_inventory = quality_required_inventory_module();
        //required inventory
        if($required_inventory['inventory'] == false){
            $this->load->view('quality/settings/required_inventory_module');
        }else{
            $this->load->view('control_points/manage', $data);
        }
    }

    /**
     * control_point table
     * @control_point [type] 
     */
    public function control_point_table()
    {
        $this->app->get_table_data(module_views_path('quality', 'control_points/table'));
    }

    /**
     * delete control_point
     * @param  [type] $id 
     * @control_point [type]     
     */
public function delete_control_point($id)
    {
        if (!$id) {
            redirect(admin_url('quality/control_points'));
        }

        if(!has_permission('quality_control_point', '', 'delete')  &&  !is_admin()) {
            access_denied('quality_control_point');
        }
        $success = false;
        $message = '';

        $response = $this->quality_model->delete_control_point($id);
        if($response){
            $success = true;
            $message = _l('deleted', _l('quality_control_point'));
        }

        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]);
    }

    /**
     * load control_point modal
     * @return [type] 
     */
    public function load_control_point_modal()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $data = [];
        $control_point_id = $this->input->post('control_point_id');
        $data['title'] = _l('quality_add_control_point');
        $data['devices'] = [];


        if(is_numeric($control_point_id) && $control_point_id != 0){
            $data['control_point'] = $this->quality_model->get_control_point($control_point_id);
            $data['title'] = _l('quality_edit_control_point');
            $data['control_point_attachments'] = $this->quality_model->get_attachment_file($control_point_id, 'q_control_point');
             $data['list_work_orders'] = $this->quality_model->get_operation_by_product($data['control_point']->item_id);

        }
        $this->load->model('invoice_items_model');
        $data['staffs']             = $this->staff_model->get('', 'active = 1');
        $data['teams'] = $this->quality_model->get_quality_team(false, true);
        $data['quality_interval_types'] = quality_interval_types();
        $data['quality_control_pers'] = quality_control_pers();
        $data['quality_operations'] = quality_operations();
        $data['quality_control_frequency'] = quality_control_frequency();
        $data['quality_control_type'] = quality_control_type();
        $data['quality_type_of_unit_of_measurements'] = quality_type_of_unit_of_measurement();
        $data['items'] = $this->invoice_items_model->get();
        $data['failure_locations'] = [];
        if(quality_get_status_modules('warehouse')){
            $this->load->model('warehouse/warehouse_model');
            $data['failure_locations'] = $this->warehouse_model->get_warehouse();
        }

        $this->load->view('control_points/add_modal', $data);
    }

    /**
     * control_point
     * @return [type] 
     */
    public function add_edit_control_point($id ='')
    {        
        $message = '';
        $success = false;
        if ($this->input->post()) {
            $data = $this->input->post();
            $data['instructions'] = $this->input->post('instructions', false);
            $data['note'] = $this->input->post('note', false);

            if (is_numeric($id) && $id != 0) {
                if(isset($data['id'])){
                    unset($data['id']);
                }

                $response = $this->quality_model->update_control_point($data, $id);
                if ($response == true) {
                    $success = true;
                    $message = _l('updated_successfully', _l('quality_control_point'));
                }
                echo json_encode([
                    'success' => $success,
                    'message' => $message,
                    'control_point_id' => $id,
                    'url' => admin_url('quality/control_points'),
                ]);die;
            } else {
                $response = $this->quality_model->add_control_point($data);
                if ($response == true) {
                    $success = true;
                    $message = _l('added_successfully', _l('quality_control_point'));
                }
                echo json_encode([
                    'success' => $success,
                    'message' => $message,
                    'control_point_id' => $response,
                    'url' => admin_url('quality/control_points'),

                ]);die;
            }
        }
        
    }

    /**
     * add control_point attachment
     * @param [type] $id 
     */
    public function add_control_point_attachment($id)
    {
        quality_handle_control_point_attachments($id);
        $url = admin_url('quality/control_points');
        echo json_encode([
            'url' => $url,
            'id' => $id,
        ]);
    }

    /**
     * delete quality attachment
     * @param  [type]  $attachment_id 
     * @param  boolean $folder_name   
     * @return [type]                 
     */
    public function delete_quality_attachment($attachment_id, $folder_name = false)
    {
        if (!has_permission('quality_control_point', '', 'delete') && !has_permission('quality_check', '', 'delete') && !has_permission('quality_alert', '', 'delete') && !has_permission('quality_failure_location', '', 'delete') && !is_admin()) {
            access_denied('quality_quality_label');
        }
        $_folder_name = CONTROL_POINT_FOLDER;

        if($folder_name == 'CONTROL_POINT_FOLDER'){
            $_folder_name = CONTROL_POINT_FOLDER;
        }elseif($folder_name == 'QUALITY_CHECK_FOLDER'){
            $_folder_name = QUALITY_CHECK_FOLDER;
        }elseif($folder_name == 'QUALITY_ALERT_FOLDER'){
            $_folder_name = QUALITY_ALERT_FOLDER;
        }

        echo json_encode([
            'success' => $this->quality_model->delete_quality_file($attachment_id, $_folder_name),
        ]);
    }

    /**
     * get operation
     * @param  integer $item_id 
     * @return [type]           
     */
    public function get_operation($item_id = 0)
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $component_arr=[];
        $product_option_html = '<option value="">---</option>';

        $operations = $this->quality_model->get_operation_by_product($item_id);
        if($operations){
            foreach ($operations as $key => $related_value) {
                $selected='';
                
                $product_option_html .= '<option value="'.$related_value['id'].'" ' .$selected.'>'.$related_value['operation'].'</option>';
            }
        }

        echo json_encode([
            'product_option_html' => $product_option_html,
        ]);
    }

    /**
     * get work order
     * @param  integer $item_id 
     * @return [type]           
     */
    public function get_work_order($item_id = 0)
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $component_arr=[];
        $product_option_html = '<option value="">---</option>';

        $operations = $this->quality_model->get_work_order_by_product($item_id);
        if($operations){
            foreach ($operations as $key => $related_value) {
                $selected='';
                
                $product_option_html .= '<option value="'.$related_value['id'].'" ' .$selected.'>'.$related_value['operation'].'</option>';
            }
        }

        echo json_encode([
            'product_option_html' => $product_option_html,
        ]);
    }

    /**
     * control point detail
     * @param  string $id 
     * @return [type]     
     */
    public function control_point_detail($id = '')
    {
        qm_token();
        if (!has_permission('quality_control_point', '', 'view') && !has_permission('quality_control_point', '', 'view_own') && !has_permission('quality_control_point', '', 'edit') && !is_admin() && !has_permission('quality_control_point', '', 'create')) {
            access_denied('quality_control_point');
        }
        if(!is_numeric($id) || $id == ''){
            blank_page('Control point Not Found', 'danger');
        }

        $data = [];
        $data['id'] = $id;
        $data['control_point'] = $this->quality_model->get_control_point($id);
        $data['control_point_attachments'] = $this->quality_model->get_attachment_file($id, 'q_control_point');
        $data['total_quality_check'] = count($this->quality_model->get_quality_check(false, ['control_point_id' => $id]));

        $this->load->view('control_points/control_point_detail', $data);
    }

    /**
     * note pdf file
     * @param  [type] $id     
     * @param  [type] $rel_id 
     * @return [type]         
     */
    public function preview_file($id, $rel_id)
    {
        $data['discussion_user_profile_image_url'] = staff_profile_image_url(get_staff_user_id());
        $data['current_user_is_admin'] = is_admin();
        $data['file'] = $this->misc_model->get_file($id, $rel_id);
        if (!$data['file']) {
            header('HTTP/1.0 404 Not Found');
            die;
        }

        $upload_path = CONTROL_POINT_FOLDER;
        $upload_folder = 'control_points';

        if($data['file']->rel_type == 'q_control_point'){
            $upload_path = CONTROL_POINT_FOLDER;
            $upload_folder = 'control_points';
        }elseif($data['file']->rel_type == 'q_check'){
            $upload_path = QUALITY_CHECK_FOLDER;
            $upload_folder = 'quality_checks';
        }elseif($data['file']->rel_type == 'q_check_detail'){
            $upload_path = QUALITY_CHECK_DETAIL_FOLDER;
            $upload_folder = 'quality_check_details';
        }elseif($data['file']->rel_type == 'q_alert'){
            $upload_path = QUALITY_ALERT_FOLDER;
            $upload_folder = 'quality_alerts';
        }
        

        $data['upload_path'] = $upload_path;
        $data['upload_folder'] = $upload_folder;

        $this->load->view('utilities/preview_pdf_file', $data);
    }


    /**
     * quality_checks
     * @quality_check [type] 
     */
    public function quality_checks()
    {
        qm_token();
        if (!has_permission('quality_check', '', 'view') && !has_permission('quality_check', '', 'view_own')) {
            access_denied('quality_check');
        }
        qm_init();
        $this->load->model('invoice_items_model');
        if($this->input->get('team_id')){
            $data['team_id'] = $this->input->get('team_id');
        }else{
            $data['team_id'] = '';
        }
        if($this->input->get('control_point_id')){
            $data['control_point_id'] = $this->input->get('control_point_id');
        }else{
            $data['control_point_id'] = '';
        }
        
        $data['title'] = _l('quality_check');
        $data['quality_control_frequency'] = quality_control_frequency();
        $data['quality_control_type'] = quality_control_type();
        $data['items'] = $this->invoice_items_model->get();
        $data['quality_check_status'] = quality_check();
        $data['teams'] = $this->quality_model->get_quality_team(false, true);
        $data['control_points'] = $this->quality_model->get_control_point(false);
        $required_inventory = quality_required_inventory_module();
        //required inventory
        if($required_inventory['inventory'] == false){
            $this->load->view('quality/settings/required_inventory_module');
        }else{
            $this->load->view('quality_checks/manage', $data);
        }
    }

    /**
     * quality_check table
     * @quality_check [type] 
     */
    public function quality_check_table()
    {
        $this->app->get_table_data(module_views_path('quality', 'quality_checks/table'));
    }

    /**
     * delete quality_check
     * @param  [type] $id 
     * @quality_check [type]     
     */
public function delete_quality_check($id)
    {
        if (!$id) {
            redirect(admin_url('quality/quality_checks'));
        }

        if(!has_permission('quality_check', '', 'delete')  &&  !is_admin()) {
            access_denied('quality_check');
        }
        $success = false;
        $message = '';

        $response = $this->quality_model->delete_quality_check($id);
        if($response){
            $success = true;
            $message = _l('deleted', _l('quality_check'));
        }

        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]);
    }

    /**
     * load quality_check modal
     * @return [type] 
     */
    public function load_quality_check_modal()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $data = [];
        $quality_check_id = $this->input->post('quality_check_id');
        $data['title'] = _l('quality_add_quality_check');
        $data['devices'] = [];
        $data['work_orders'] = [];
        $data['goods_receipts'] = [];
        $data['goods_deliverys'] = [];
        $data['internal_deliverys'] = [];

        if(is_numeric($quality_check_id) && $quality_check_id != 0){
            $data['quality_check'] = $this->quality_model->get_quality_check($quality_check_id);
            $data['title'] = _l('quality_edit_quality_check');
            $data['quality_check_attachments'] = $this->quality_model->get_attachment_file($quality_check_id, 'q_check');

            if($data['quality_check']->operation == 'manufacturing'){
                $data['work_orders'] = $this->quality_model->get_work_order_by_product($data['quality_check']->item_id);
            }else if($data['quality_check']->operation == 'receipts'){
                $data['goods_receipts'] = $this->quality_model->quality_get_inventory_receipt($data['quality_check']->item_id);
            }else if($data['quality_check']->operation == 'delivery_orders'){
                $data['goods_deliverys'] = $this->quality_model->quality_get_inventory_delivery($data['quality_check']->item_id);
            }else if($data['quality_check']->operation == 'internal_transfers'){
                $data['internal_deliverys'] = $this->quality_model->quality_get_inventory_internal_delivery($data['quality_check']->item_id);
            }

        }
        $this->load->model('invoice_items_model');
        $data['staffs']             = $this->staff_model->get('', 'active = 1');
        $data['teams'] = $this->quality_model->get_quality_team(false, true);
        $data['quality_interval_types'] = quality_interval_types();
        $data['quality_control_pers'] = quality_control_pers();
        $data['quality_operations'] = quality_operations();
        $data['quality_control_type'] = quality_control_type();
        $data['quality_type_of_unit_of_measurements'] = quality_type_of_unit_of_measurement();
        $data['items'] = $this->invoice_items_model->get();
        $data['control_points'] = $this->quality_model->get_control_point(false);
        $data['failure_locations'] = [];
        if(quality_get_status_modules('warehouse')){
            $this->load->model('warehouse/warehouse_model');
            $data['failure_locations'] = $this->warehouse_model->get_warehouse();
        }

        $this->load->view('quality_checks/add_modal', $data);
    }

    /**
     * quality_check
     * @return [type] 
     */
    public function add_edit_quality_check($id ='')
    {
        $message = '';
        $success = false;
        if ($this->input->post()) {
            $data = $this->input->post();
            $data['instructions'] = $this->input->post('instructions', false);
            $data['note'] = $this->input->post('note', false);

            if (is_numeric($id) && $id != 0) {
                if(isset($data['id'])){
                    unset($data['id']);
                }

                $response = $this->quality_model->update_quality_check($data, $id);
                if ($response == true) {
                    $success = true;
                    $message = _l('updated_successfully', _l('quality_check'));
                }
                echo json_encode([
                    'success' => $success,
                    'message' => $message,
                    'quality_check_id' => $id,
                    'url' => admin_url('quality/quality_checks'),
                ]);die;
            } else {
                $response = $this->quality_model->add_quality_check($data);
                if ($response == true) {
                    $success = true;
                    $message = _l('added_successfully', _l('quality_check'));
                }
                echo json_encode([
                    'success' => $success,
                    'message' => $message,
                    'quality_check_id' => $response,
                    'url' => admin_url('quality/quality_checks'),

                ]);die;
            }
        }
        
    }

    /**
     * add quality_check attachment
     * @param [type] $id 
     */
    public function add_quality_check_attachment($id)
    {
        quality_handle_quality_check_attachments($id);
        $url = admin_url('quality/quality_checks');
        echo json_encode([
            'url' => $url,
            'id' => $id,
        ]);
    }

    /**
     * control point detail
     * @param  string $id 
     * @return [type]     
     */
    public function quality_check_detail($id = '', $hash = '')
    {
        qm_token();
        if(!is_numeric($id) || $id == ''){
            blank_page('Quality check Not Found', 'danger');
        }

        $is_staff_approval = false;
        $quality_check = $this->quality_model->get_quality_check(false, ['hash' => $hash]);
        if(count($quality_check) > 0){
            $is_staff_approval = true;
        }
        $data = [];
        $data['quality_check'] = $this->quality_model->get_quality_check($id);
        if($data['quality_check']){
            $quality_team = get_quality_team_name($data['quality_check']->team_id, false);
            if($quality_team->team_member == get_staff_user_id()){
                $is_staff_approval = true;
            }
        }

        if (!has_permission('quality_check', '', 'view') && !has_permission('quality_check', '', 'view_own') && !has_permission('quality_check', '', 'edit') && !is_admin() && !has_permission('quality_check', '', 'create') && $is_staff_approval) {
            access_denied('quality_check');
        }

        $data['id'] = $id;
        $data['quality_check_attachments'] = $this->quality_model->get_attachment_file($id, 'q_check');
        $data['total_quality_check'] = 0;

        $this->load->view('quality_checks/quality_check_detail', $data);
    }

    /**
     * quality check status mark as
     * @param  [type] $status 
     * @param  [type] $id     
     * @param  string $type   
     * @return [type]         
     */
    public function quality_check_status_mark_as($status, $id, $type = '')
    {
        $success = $this->quality_model->quality_check_status_mark_as($status, $id, $type);
        $message = '';

        if ($success) {
            $message = _l('quality_change_status_successfully');
        }
        echo json_encode([
            'success'  => $success,
            'message'  => $message
        ]);die;
    }
    
    /**
     * get inventory receipt
     * @param  integer $item_id 
     * @return [type]           
     */
    public function get_inventory_receipt($item_id = 0)
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $component_arr=[];
        $inventory_receipt_option_html = '<option value="">---</option>';

        if(quality_get_status_modules('warehouse')){
            $goods_receipts = $this->quality_model->quality_get_inventory_receipt($item_id);

            if(count($goods_receipts) > 0){
                foreach ($goods_receipts as $key => $related_value) {
                    $selected='';

                    $inventory_receipt_option_html .= '<option value="'.$related_value['id'].'" ' .$selected.' data-subtext="'.$related_value['description'].'">'.$related_value['goods_receipt_code'].'</option>';
                }
            }
        }

        echo json_encode([
            'inventory_receipt_option_html' => $inventory_receipt_option_html,
        ]);
    }

    /**
     * get inventory delivery
     * @param  integer $item_id 
     * @return [type]           
     */
    public function get_inventory_delivery($item_id = 0)
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $component_arr=[];
        $inventory_delivery_option_html = '<option value="">---</option>';

        if(quality_get_status_modules('warehouse')){
            $goods_deliverys = $this->quality_model->quality_get_inventory_delivery($item_id);

            if(count($goods_deliverys) > 0){
                foreach ($goods_deliverys as $key => $related_value) {
                    $selected='';

                    $inventory_delivery_option_html .= '<option value="'.$related_value['id'].'" ' .$selected.' data-subtext="'.$related_value['description'].'">'.$related_value['goods_delivery_code'].'</option>';
                }
            }
        }

        echo json_encode([
            'inventory_delivery_option_html' => $inventory_delivery_option_html,
        ]);
    }

    /**
     * get inventory internal delivery
     * @param  integer $item_id 
     * @return [type]           
     */
    public function get_inventory_internal_delivery($item_id = 0)
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $component_arr=[];
        $inventory_internal_delivery_option_html = '<option value="">---</option>';
        if(quality_get_status_modules('warehouse')){
            $internal_delivery_notes = $this->quality_model->quality_get_inventory_internal_delivery($item_id);

            if(count($internal_delivery_notes) > 0){
                foreach ($internal_delivery_notes as $key => $related_value) {
                    $selected='';

                    $inventory_internal_delivery_option_html .= '<option value="'.$related_value['id'].'" ' .$selected.' data-subtext="'.$related_value['description'].'">'.$related_value['internal_delivery_code'].'</option>';
                }
            }
        }

        echo json_encode([
            'inventory_internal_delivery_option_html' => $inventory_internal_delivery_option_html,
        ]);
    }

    /**
     * get quality control point
     * @param  string $control_point_id 
     * @return [type]                   
     */
    public function get_quality_control_point($control_point_id='')
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $status = false;
        $control_point = '';
        if( is_numeric($control_point_id) && $control_point_id > 0 ){
            $control_point = $this->quality_model->get_control_point($control_point_id);
            if($control_point){
                $status = true;
            }
        }

        echo json_encode([
            'status' => $status,
            'control_point' => $control_point,
        ]);

    }

    /**
     * detailed checklist table
     * @return [type] 
     */
    public function detailed_checklist_table()
    {
        $this->app->get_table_data(module_views_path('quality', 'quality_checks/detailed_checklist_table'));
    }

    /**
     * quality check detail status mark as
     * @param  [type] $status 
     * @param  [type] $id     
     * @param  string $type   
     * @return [type]         
     */
    public function quality_check_detail_status_mark_as($status, $id, $type = '')
    {
        $success = $this->quality_model->quality_check_detail_status_mark_as($status, $id, $type);
        $message = '';

        if ($success) {
            $message = _l('quality_change_status_successfully');
        }
        echo json_encode([
            'success'  => $success,
            'message'  => $message
        ]);die;
    }

    /**
     * load quality check detail modal
     * @return [type] 
     */
    public function load_quality_check_detail_modal()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $data = [];
        $quality_check_detail_id = $this->input->post('quality_check_detail_id');
        $quality_check_id = $this->input->post('quality_check_id');
        $data['title'] = _l('quality_check');
        $data['devices'] = [];
        $data['work_orders'] = [];
        $data['goods_receipts'] = [];
        $data['goods_deliverys'] = [];
        $data['internal_deliverys'] = [];

        $data['quality_check_detail_attachments'] = $this->quality_model->get_attachment_file($quality_check_detail_id, 'q_check_detail');

        $data['quality_control_type'] = quality_control_type();
        $data['quality_type_of_unit_of_measurements'] = quality_type_of_unit_of_measurement();
        $data['quality_check_detail'] = $this->quality_model->get_quality_check_detail($quality_check_detail_id);
        $data['quality_check'] = $this->quality_model->get_quality_check($quality_check_id);

        $this->load->view('quality_checks/quality_check_item_modal', $data);
    }

    /**
     * add edit quality check detail
     */
    public function add_edit_quality_check_detail()
    {
        $message = '';
        $success = false;
        if ($this->input->post()) {
            $data = $this->input->post();
            $data['instructions'] = $this->input->post('instructions', false);
            $data['note'] = $this->input->post('note', false);
            $id = $this->input->post('id');

            if (is_numeric($id) && $id != 0) {
                if(isset($data['id'])){
                    unset($data['id']);
                }

                $response = $this->quality_model->update_quality_check_detail($data, $id);
                if ($response == true) {
                    $success = true;
                    $message = _l('updated_successfully', _l('quality_check'));
                }
                // check all check detail done
                $checklists_processed = true;
                $quality_check_detail = $this->quality_model->get_quality_check_detail($id);
                if($quality_check_detail){
                    $get_quality_check_detail = $this->quality_model->get_quality_check_detail(false, $quality_check_detail->quality_check_id, 'status != "failed" AND status != "passed"');
                    if(count($get_quality_check_detail) > 0){
                        $checklists_processed = false;
                    }
                }

                echo json_encode([
                    'success' => $success,
                    'message' => $message,
                    'quality_check_detail_id' => $id,
                    'url' => admin_url('quality/quality_checks'),
                    'checklists_processed' => $checklists_processed,
                ]);die;
            }
        }
        
    }

    /**
     * add quality check detail attachment
     * @param [type] $id 
     */
    public function add_quality_check_detail_attachment($id)
    {
        quality_handle_quality_check_detail_attachments($id);
        $url = admin_url('quality/quality_checks');
        echo json_encode([
            'url' => $url,
            'id' => $id,
        ]);
    }

    /**
     * change quality control status
     * @param  [type] $id     
     * @param  [type] $status 
     * @return [type]         
     */
    public function change_quality_control_status($id, $status) {
        if (has_permission('quality_control_point', '', 'edit')) {
            if ($this->input->is_ajax_request()) {
                $this->quality_model->change_quality_control_status($id, (int)$status);
            }
        }
    }

    /**
     * quality_alerts
     * @quality_alert [type] 
     */
    public function quality_alerts()
    {
        qm_token();
        if (!has_permission('quality_alert', '', 'view') && !has_permission('quality_alert', '', 'view_own')) {
            access_denied('quality_alert');
        }
        qm_init();
        $this->load->model('invoice_items_model');
        $data['title'] = _l('quality_alert');
        $data['quality_control_frequency'] = quality_control_frequency();
        $data['quality_control_type'] = quality_control_type();
        $data['items'] = $this->invoice_items_model->get();
        $data['quality_alert_status'] = quality_alert_status();
        if($this->input->get('team_id')){
            $data['team_id'] = $this->input->get('team_id');
        }else{
            $data['team_id'] = '';
        }
        $data['teams'] = $this->quality_model->get_quality_team(false, true);
        $required_inventory = quality_required_inventory_module();
        //required inventory
        if($required_inventory['inventory'] == false){
            $this->load->view('quality/settings/required_inventory_module');
        }else{
            $this->load->view('quality_alerts/manage', $data);
        }
    }

    /**
     * quality_alert table
     * @quality_alert [type] 
     */
    public function quality_alert_table()
    {
        $this->app->get_table_data(module_views_path('quality', 'quality_alerts/table'));
    }

    /**
     * delete quality_alert
     * @param  [type] $id 
     * @quality_alert [type]     
     */
    
    public function delete_quality_alert($id)
    {
        if (!$id) {
            redirect(admin_url('quality/quality_alerts'));
        }

        if(!has_permission('quality_alert', '', 'delete')  &&  !is_admin()) {
            access_denied('quality_alert');
        }
        $success = false;
        $message = '';

        $response = $this->quality_model->delete_quality_alert($id);
        if($response){
            $success = true;
            $message = _l('deleted', _l('quality_alert'));
        }

        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]);
    }

    /**
     * load quality_alert modal
     * @return [type] 
     */
    public function load_quality_alert_modal()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $data = [];
        $quality_alert_id = $this->input->post('quality_alert_id');
        $quality_check_id = $this->input->post('quality_check_id');
        $data['title'] = _l('quality_add_quality_alert');
        $data['devices'] = [];
        $data['work_orders'] = [];
        $data['goods_receipts'] = [];
        $data['goods_deliverys'] = [];
        $data['internal_deliverys'] = [];

        if(is_numeric($quality_alert_id) && $quality_alert_id != 0){
            $data['quality_alert'] = $this->quality_model->get_quality_alert($quality_alert_id);
            $data['title'] = _l('quality_edit_quality_alert');
            $data['quality_alert_attachments'] = $this->quality_model->get_attachment_file($quality_alert_id, 'q_alert');

            if($data['quality_alert']->operation == 'manufacturing'){
                $data['work_orders'] = $this->quality_model->get_work_order_by_product($data['quality_alert']->item_id);
            }else if($data['quality_alert']->operation == 'receipts'){
                $data['goods_receipts'] = $this->quality_model->quality_get_inventory_receipt($data['quality_alert']->item_id);
            }else if($data['quality_alert']->operation == 'delivery_orders'){
                $data['goods_deliverys'] = $this->quality_model->quality_get_inventory_delivery($data['quality_alert']->item_id);
            }else if($data['quality_alert']->operation == 'internal_transfers'){
                $data['internal_deliverys'] = $this->quality_model->quality_get_inventory_internal_delivery($data['quality_alert']->item_id);
            }

        }else{
            if(!is_null($quality_check_id)){
            // create alert from quality check
                $data['quality_alert'] = $this->quality_model->get_quality_check($quality_check_id);
                $data['quality_alert']->quality_check_id = $quality_check_id;
                $data['quality_alert']->prefix = get_option('quality_alert_prefix');
                $data['quality_alert']->number = get_option('quality_alert_number');
                $data['quality_alert']->id = 0;
                $data['quality_alert']->work_center_id = null;
                $data['quality_alert']->root_cause = null;
                $data['quality_alert']->priority = null;
                $data['quality_alert']->description = null;
                $data['quality_alert']->corrective_actions = null;
                $data['quality_alert']->preventive_actions = null;
                $data['quality_alert']->status = null;
                $data['quality_alert']->responsible_id = null;

                if($data['quality_alert']->operation == 'manufacturing'){
                    $data['work_orders'] = $this->quality_model->get_work_order_by_product($data['quality_alert']->item_id);
                }else if($data['quality_alert']->operation == 'receipts'){
                    $data['goods_receipts'] = $this->quality_model->quality_get_inventory_receipt($data['quality_alert']->item_id);
                }else if($data['quality_alert']->operation == 'delivery_orders'){
                    $data['goods_deliverys'] = $this->quality_model->quality_get_inventory_delivery($data['quality_alert']->item_id);
                }else if($data['quality_alert']->operation == 'internal_transfers'){
                    $data['internal_deliverys'] = $this->quality_model->quality_get_inventory_internal_delivery($data['quality_alert']->item_id);
                }
            }
        }

        $this->load->model('invoice_items_model');
        $data['work_centers'] = [];
        $data['staffs']             = $this->staff_model->get('', 'active = 1');
        $data['teams'] = $this->quality_model->get_quality_team(false, true);
        $data['quality_operations'] = quality_operations();
        $data['root_causes'] = quality_alert_root_cause();
        $data['prioritys'] = quality_alert_priority();
        $data['items'] = $this->invoice_items_model->get();
        $data['quality_checks'] = $this->quality_model->get_quality_check_with_code();

        if(quality_get_status_modules('manufacturing')){
            $this->load->model('manufacturing/manufacturing_model');
            $data['work_centers'] = $this->manufacturing_model->get_work_centers();
        }

        $this->load->view('quality_alerts/add_modal', $data);
    }

    /**
     * quality_alert
     * @return [type] 
     */
    public function add_edit_quality_alert($id ='')
    {
        $message = '';
        $success = false;
        if ($this->input->post()) {
            $data = $this->input->post();
            $data['description'] = $this->input->post('description', false);
            $data['corrective_actions'] = $this->input->post('corrective_actions', false);
            $data['preventive_actions'] = $this->input->post('preventive_actions', false);

            if (is_numeric($id) && $id != 0) {
                if(isset($data['id'])){
                    unset($data['id']);
                }

                $response = $this->quality_model->update_quality_alert($data, $id);
                if ($response == true) {
                    $success = true;
                    $message = _l('updated_successfully', _l('quality_alert'));
                }
                echo json_encode([
                    'success' => $success,
                    'message' => $message,
                    'quality_alert_id' => $id,
                    'url' => admin_url('quality/quality_alerts'),
                ]);die;
            } else {
                $response = $this->quality_model->add_quality_alert($data);
                if ($response == true) {
                    $success = true;
                    $message = _l('added_successfully', _l('quality_alert'));
                }
                echo json_encode([
                    'success' => $success,
                    'message' => $message,
                    'quality_alert_id' => $response,
                    'url' => admin_url('quality/quality_alerts'),

                ]);die;
            }
        }
        
    }

    /**
     * add quality_alert attachment
     * @param [type] $id 
     */
    public function add_quality_alert_attachment($id)
    {
        quality_handle_quality_alert_attachments($id);
        $url = admin_url('quality/quality_alerts');
        echo json_encode([
            'url' => $url,
            'id' => $id,
        ]);
    }

    /**
     * control point detail
     * @param  string $id 
     * @return [type]     
     */
    public function quality_alert_detail($id = '', $hash = '')
    {
        qm_token();
        if(!is_numeric($id) || $id == ''){
            blank_page('Control point Not Found', 'danger');
        }

        $is_staff_approval = false;
        $get_staff_user_id = get_staff_user_id();
        $quality_check = $this->quality_model->get_quality_check(false, ['hash' => $hash]);
        if(count($quality_check) > 0){
            $is_staff_approval = true;
        }

        $data = [];
        $data['quality_alert'] = $this->quality_model->get_quality_alert($id);
        if($data['quality_alert']){
            $quality_team = get_quality_team_name($data['quality_alert']->team_id, false);
            if($quality_team->team_member == $get_staff_user_id){
                $is_staff_approval = true;
            }
            if($data['quality_alert']->responsible_id == $get_staff_user_id){
                $is_staff_approval = true;
            }
        }

        if (!has_permission('quality_alert', '', 'view') && !has_permission('quality_alert', '', 'view_own') && !has_permission('quality_alert', '', 'edit') && !is_admin() && !has_permission('quality_alert', '', 'create') && !$is_staff_approval) {
            access_denied('quality_alert');
        }
        $data['id'] = $id;
        $data['quality_alert_attachments'] = $this->quality_model->get_attachment_file($id, 'q_alert');
        $data['total_quality_alert'] = 0;

        $this->load->view('quality_alerts/quality_alert_detail', $data);
    }

    /**
     * quality check status mark as
     * @param  [type] $status 
     * @param  [type] $id     
     * @param  string $type   
     * @return [type]         
     */
    public function quality_alert_status_mark_as($status, $id, $type = '')
    {
        $success = $this->quality_model->quality_alert_status_mark_as($status, $id, $type);
        $message = '';

        if ($success) {
            $message = _l('quality_change_status_successfully');
        }
        echo json_encode([
            'success'  => $success,
            'message'  => $message
        ]);die;
    }

    /**
     * quality alert priority mark as
     * @param  [type] $status 
     * @param  [type] $id     
     * @param  string $type   
     * @return [type]         
     */
    public function quality_alert_priority_mark_as($status, $id, $type = '')
    {
        $success = $this->quality_model->quality_alert_priority_mark_as($status, $id, $type);
        $message = '';

        if ($success) {
            $message = _l('quality_change_priority_successfully');
        }
        echo json_encode([
            'success'  => $success,
            'message'  => $message
        ]);die;
    }

    /**
     * quality alert root cause mark as
     * @param  [type] $status 
     * @param  [type] $id     
     * @param  string $type   
     * @return [type]         
     */
    public function quality_alert_root_cause_mark_as($status, $id, $type = '')
    {
        $success = $this->quality_model->quality_alert_root_cause_mark_as($status, $id, $type);
        $message = '';

        if ($success) {
            $message = _l('quality_change_status_successfully');
        }
        echo json_encode([
            'success'  => $success,
            'message'  => $message
        ]);die;
    }
    
    /**
     * get quality check
     * @param  string $check_id 
     * @return [type]           
     */
    public function get_quality_check($check_id='')
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $status = false;
        $quality_check = '';
        if( is_numeric($check_id) && $check_id > 0 ){
            $quality_check = $this->quality_model->get_quality_check($check_id);
            if($quality_check){
                $status = true;
            }
        }

        echo json_encode([
            'status' => $status,
            'quality_check' => $quality_check,
        ]);

    }

    /**
     * load products to location modal
     * @return [type] 
     */
    public function load_products_to_location_modal()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }
        $data = [];
        $item_failed = [];
        $quality_check_id = $this->input->post('quality_check_id');
        $data['title'] = _l('quality_check');
        $data['quality_check'] = $this->quality_model->get_quality_check($quality_check_id);
        if($data['quality_check']->details){
            foreach ($data['quality_check']->details as $key => $value) {
                if($value['status'] == 'failed'){
                    $item_failed[] = $value['check_item_id'];
                }
            }
        }

        $products = [];
        if($data['quality_check']->operation == 'receipts'){
            if(quality_get_status_modules('warehouse')){
                $this->load->model('warehouse/warehouse_model');
                $goods_receipt_details = $this->warehouse_model->get_goods_receipt_detail($data['quality_check']->goods_receipt_id);
                foreach ($goods_receipt_details as $key => $value) {
                    if(!in_array($value['commodity_code'], $item_failed)){
                        continue;
                    }
                    $products[] = [
                        'commodity_code' => $value['commodity_code'],
                        'warehouse_id' => $value['warehouse_id'],
                        'unit_id' => $value['unit_id'],
                        'quantities' => $value['quantities'],
                        'unit_price' => $value['unit_price'],
                        'commodity_name' => $value['commodity_name'],
                        'serial_number' => $value['serial_number'],
                        'expiry_date' => $value['expiry_date'],
                        'lot_number' => $value['lot_number'],
                    ];
                }
            }
        }elseif($data['quality_check']->operation == 'internal_transfers'){
            if(quality_get_status_modules('warehouse')){
                $this->load->model('warehouse/warehouse_model');
                $goods_internal_details = $this->warehouse_model->get_internal_delivery_detail($data['quality_check']->internal_delivery_id);
                foreach ($goods_internal_details as $key => $value) {
                    if(!in_array($value['commodity_code'], $item_failed)){
                        continue;
                    }
                    $products[] = [
                        'commodity_code' => $value['commodity_code'],
                        'warehouse_id' => $value['to_stock_name'],
                        'unit_id' => $value['unit_id'],
                        'quantities' => $value['quantities'],
                        'unit_price' => $value['unit_price'],
                        'commodity_name' => $value['commodity_name'],
                        'serial_number' => $value['serial_number'],
                        'expiry_date' => null,
                        'lot_number' => null,
                    ];
                }
            }
        }elseif($data['quality_check']->operation == 'delivery_orders'){
            if(quality_get_status_modules('warehouse')){
                $this->load->model('warehouse/warehouse_model');
                $goods_delivery_details = $this->warehouse_model->get_goods_delivery_detail($data['quality_check']->goods_delivery_id);
                foreach ($goods_delivery_details as $key => $value) {
                    if(!in_array($value['commodity_code'], $item_failed)){
                        continue;
                    }
                    $products[] = [
                        'commodity_code' => $value['commodity_code'],
                        'warehouse_id' => $value['warehouse_id'],
                        'unit_id' => $value['unit_id'],
                        'quantities' => $value['quantities'],
                        'unit_price' => $value['unit_price'],
                        'commodity_name' => $value['commodity_name'],
                        'serial_number' => $value['serial_number'],
                        'expiry_date' => $value['expiry_date'],
                        'lot_number' => $value['lot_number'],
                    ];
                }
            } 
        }
        $data['products'] = $products;
        $data['quality_check_id'] = $quality_check_id;

        $faulty_goods_warehouse = [];
        $failure_location_ids = [];
        if($data['quality_check']->failure_location_ids){
            $failure_location_ids = explode(',', $data['quality_check']->failure_location_ids ?? '');
        }
        foreach ($failure_location_ids as $key => $value) {
            if(quality_get_status_modules('warehouse')){
                $faulty_goods_warehouse[] = [
                    'id' => $value,
                    'name' => get_warehouse_name($value, true),
                ];
            }else{

                $faulty_goods_warehouse[] = [
                    'id' => $value,
                    'name' => $value,
                ];
            }
        }

        $data['faulty_goods_warehouse'] = $faulty_goods_warehouse;

        $this->load->view('failures/move_damaged_products_to_warehouse_modal', $data);
    }

    /**
     * send product to warehouse
     * @return [type] 
     */
    public function send_product_to_warehouse()
    {
        $data = $this->input->post();
        $quality_check_id = $data['quality_check_id'];

        $success = false;
        $message = '';

        $quality_check = $this->quality_model->get_quality_check($quality_check_id);
        if($quality_check->operation == 'receipts' || $quality_check->operation == 'internal_transfers'){
            $success = $this->quality_model->create_internal_delivery_from_detail_checklist($data);
            if($success){
                $message = _l('quality_send_products_to_failure_location');
            }
        }elseif($quality_check->operation == 'delivery_orders'){
            $success =  $this->quality_model->create_inventory_receipt_from_detail_checklist($data);
            if($success){
                $message = _l('quality_send_products_to_failure_location');
            }
        }

        echo json_encode([
            'success' => $success,
            'message' => $message,
        ]);
    }
    
    /**
     * failure locations
     * @return [type] 
     */
    public function failure_locations()
    {
        qm_token();
        if (!has_permission('quality_failure_location', '', 'view') && !has_permission('quality_failure_location', '', 'view_own')) {
            access_denied('quality_failure_location');
        }
        qm_init();
        $data['title'] = _l('quality_failure_location');
        $data['failure_location_ids'] = $this->quality_model->get_failure_location();

        if(quality_get_status_modules('warehouse')){
            $this->load->model('warehouse/warehouse_model');

            $data['warehouse_types'] = $this->warehouse_model->get_warehouse();
            $this->db->where('fieldto', 'warehouse_name');
            $data['wh_custom_fields_display'] = $this->db->get(db_prefix().'customfields')->result_array();
            $data['staffs'] = $this->warehouse_model->get_staff();
        }
        $required_inventory = quality_required_inventory_module();
        //required inventory
        if($required_inventory['inventory'] == false){
            $this->load->view('quality/settings/required_inventory_module');
        }else{
            $this->load->view('failures/manage', $data);
        }
    }

    /**
     * dashboard
     * @return [type] 
     */
    public function dashboard()
    {
        qm_token();
        if (!has_permission('quality_dashboard', '', 'view')  && !is_admin()) {
            access_denied('dashboard');
        }
        qm_init();
        $data['title'] = _l('quality_dashboard');
        $data['quality_check_statistics_by_teams'] = $this->quality_model->get_quality_check_statistics_by_teams();

        $this->load->view('quality/dashboards/dashboard', $data);
    }

    /**
     * report by quality check
     * @return [type] 
     */
    public function report_by_quality_check()
    {
        if ($this->input->is_ajax_request()) { 
            $data = $this->input->get();

            $months_report = $data['months_report'];
            $report_from = $data['report_from'];
            $report_to = $data['report_to'];

            if($months_report == ''){

                $from_date = date('Y-m-d', strtotime('1997-01-01'));
                $to_date = date('Y-m-d', strtotime(date('Y-12-31')));
            }

            if($months_report == 'this_month'){
                $from_date = date('Y-m-01');
                $to_date   = date('Y-m-t');
            }

            if($months_report == '1'){ 
                $from_date = date('Y-m-01', strtotime('first day of last month'));
                $to_date   = date('Y-m-t', strtotime('last day of last month'));
            }

            if($months_report == 'this_year'){
                $from_date = date('Y-m-d', strtotime(date('Y-01-01')));
                $to_date = date('Y-m-d', strtotime(date('Y-12-31')));
            }

            if($months_report == 'last_year'){

                $from_date = date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-01-01')));
                $to_date = date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-12-31')));  


            }

            if($months_report == '3'){
                $months_report = 3;
                $months_report--;
                $from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
                $to_date   = date('Y-m-t');

            }

            if($months_report == '6'){
                $months_report = 6;
                $months_report--;
                $from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
                $to_date   = date('Y-m-t');
            }

            if($months_report == '12'){
                $months_report = 12;
                $months_report--;
                $from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
                $to_date   = date('Y-m-t');
            }

            if($months_report == 'custom'){
                $from_date = to_sql_date($report_from);
                $to_date   = to_sql_date($report_to);
            }
    
            $mo_data = $this->quality_model->get_report_quality_check($from_date, $to_date);


            echo json_encode([
                'categories' => $mo_data['categories'],
                'estimated_hours' => $mo_data['total_check'],
            ]); 
        }
    }

    /**
     * report by quality alert
     * @return [type] 
     */
    public function report_by_quality_alert()
    {
        if ($this->input->is_ajax_request()) { 
            $data = $this->input->get();

            $months_report = $data['months_report'];
            $report_from = $data['report_from'];
            $report_to = $data['report_to'];

            if($months_report == ''){

                $from_date = date('Y-m-d', strtotime('1997-01-01'));
                $to_date = date('Y-m-d', strtotime(date('Y-12-31')));
            }

            if($months_report == 'this_month'){
                $from_date = date('Y-m-01');
                $to_date   = date('Y-m-t');
            }

            if($months_report == '1'){ 
                $from_date = date('Y-m-01', strtotime('first day of last month'));
                $to_date   = date('Y-m-t', strtotime('last day of last month'));
            }

            if($months_report == 'this_year'){
                $from_date = date('Y-m-d', strtotime(date('Y-01-01')));
                $to_date = date('Y-m-d', strtotime(date('Y-12-31')));
            }

            if($months_report == 'last_year'){

                $from_date = date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-01-01')));
                $to_date = date('Y-m-d', strtotime(date(date('Y', strtotime('last year')) . '-12-31')));  


            }

            if($months_report == '3'){
                $months_report = 3;
                $months_report--;
                $from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
                $to_date   = date('Y-m-t');

            }

            if($months_report == '6'){
                $months_report = 6;
                $months_report--;
                $from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
                $to_date   = date('Y-m-t');
            }

            if($months_report == '12'){
                $months_report = 12;
                $months_report--;
                $from_date = date('Y-m-01', strtotime("-$months_report MONTH"));
                $to_date   = date('Y-m-t');
            }

            if($months_report == 'custom'){
                $from_date = to_sql_date($report_from);
                $to_date   = to_sql_date($report_to);
            }
    
            $mo_data = $this->quality_model->get_report_quality_alert($from_date, $to_date);


            echo json_encode([
                'categories' => $mo_data['categories'],
                'estimated_hours' => $mo_data['total_alert'],
            ]); 
        }
    }

    /*end file*/
}