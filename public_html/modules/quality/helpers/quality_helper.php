<?php
defined('BASEPATH') or exit('No direct script access allowed');
hooks()->add_action('after_email_templates', 'add_quality_email_templates');
hooks()->add_action('quality_init',QUALITY_MODULE_NAME.'_appint');
hooks()->add_action('pre_activate_module', QUALITY_MODULE_NAME.'_preactivate');
hooks()->add_action('pre_deactivate_module', QUALITY_MODULE_NAME.'_predeactivate');
/**
 * list quality permisstion
 * @return [type] 
 */
function list_quality_permisstion()
{
    $quality_permissions[]='quality_setting';
    $quality_permissions[]='quality_control_point';
    $quality_permissions[]='quality_check';
    $quality_permissions[]='quality_alert';
    $quality_permissions[]='quality_failure_location';

    return $quality_permissions;
}

/**
 * quality get staff id permissions
 * @return [type] 
 */
function quality_get_staff_id_permissions()
{
    $CI = & get_instance();
    $array_staff_id = [];
    $index=0;

    $str_permissions ='';
    foreach (list_quality_permisstion() as $per_key =>  $per_value) {
        if(new_strlen($str_permissions) > 0){
            $str_permissions .= ",'".$per_value."'";
        }else{
            $str_permissions .= "'".$per_value."'";
        }
    }

    $sql_where = "SELECT distinct staff_id FROM ".db_prefix()."staff_permissions
    where feature IN (".$str_permissions.")
    ";

    $staffs = $CI->db->query($sql_where)->result_array();

    if(count($staffs)>0){
        foreach ($staffs as $key => $value) {
            $array_staff_id[$index] = $value['staff_id'];
            $index++;
        }
    }
    return $array_staff_id;
}

/**
 * quality get staff id dont permissions
 * @return [type] 
 */
function quality_get_staff_id_dont_permissions()
{
    $CI = & get_instance();

    $CI->db->where('admin != ', 1);

    if(count(quality_get_staff_id_permissions()) > 0){
        $CI->db->where_not_in('staffid', quality_get_staff_id_permissions());
    }
    return $CI->db->get(db_prefix().'staff')->result_array();
}

/**
 * quality handle control point attachments
 * @param  [type] $id 
 * @return [type]     
 */
function quality_handle_control_point_attachments($id)
{
    if (isset($_FILES['file']) && _perfex_upload_error($_FILES['file']['error'])) {
        header('HTTP/1.0 400 Bad error');
        echo _perfex_upload_error($_FILES['file']['error']);
        die;
    }
    $path = CONTROL_POINT_FOLDER . $id . '/';
    $CI   = & get_instance();

    if (isset($_FILES['file']['name'])) {

        // Get the temp file path
        $tmpFilePath = $_FILES['file']['tmp_name'];
        // Make sure we have a filepath
        if (!empty($tmpFilePath) && $tmpFilePath != '') {

            _maybe_create_upload_path($path);

            $filename    = unique_filename($path, $_FILES['file']['name']);

            $new_filename = str_replace(' ', '_', $filename);
            $new_filename = str_replace('(', '_', $new_filename);
            $new_filename = str_replace(')', '_', $new_filename);
            $new_filename = str_replace('. ', '.', $new_filename);

            $newFilePath = $path . $new_filename;
            // Upload the file into the temp dir
            if (move_uploaded_file($tmpFilePath, $newFilePath)) {

                $CI                       = & get_instance();
                $config                   = [];
                $config['image_library']  = 'gd2';
                $config['source_image']   = $newFilePath;
                $config['new_image']      = 'thumb_' . $new_filename;
                $config['maintain_ratio'] = true;
                $config['width']          = 300;
                $config['height']         = 300;
                $CI->image_lib->initialize($config);
                $CI->image_lib->resize();
                $CI->image_lib->clear();

                $config['image_library']  = 'gd2';
                $config['source_image']   = $newFilePath;
                $config['new_image']      = 'small_' . $new_filename;
                $config['maintain_ratio'] = true;
                $config['width']          = 40;
                $config['height']         = 40;
                $CI->image_lib->initialize($config);
                $CI->image_lib->resize();

                $attachment   = [];
                $attachment[] = [
                    'file_name' => $new_filename,
                    'filetype'  => $_FILES['file']['type'],
                ];

                $CI->misc_model->add_attachment_to_database($id, 'q_control_point', $attachment);

            }
        }
    }

}

/**
 * format control point number
 * @param  [type] $id 
 * @return [type]     
 */
function format_control_point_number($id)
{
    $CI = &get_instance();

    if (! is_object($id)) {
        $CI->db->select('datecreated,number,prefix,number_format')->from(db_prefix() . 'quality_control_points')->where('id', $id);
        $control_point = $CI->db->get()->row();
    } else {
        $control_point = $id;
        $id       = $control_point->id;
    }

    if (!$control_point) {
        return '';
    }

    $number = sales_number_format($control_point->number, $control_point->number_format, $control_point->prefix, $control_point->datecreated);

    return hooks()->apply_filters('format_control_point_number', $number, [
        'id'       => $id,
        'control_point' => $control_point,
    ]);
}

/**
 * get quality team name
 * @param  [type] $id 
 * @return [type]     
 */
function get_quality_team_name($id, $team_name = true)
{
    $name = '';
    $CI = &get_instance();
    $CI->db->where('id', $id);
    $team = $CI->db->get(db_prefix() . 'quality_teams')->row();
    if($team){
        $name = $team->name;
    }

    if(!$team_name){
        return $team;
    }
    return $name;
}

/**
 * quality interval types
 * @return [type] 
 */
function quality_interval_types()
{

    $array_data = [];
    $array_data = [

        [
            'name' => 'day',
            'label' => _l('quality_day_s'),
        ],
        [
            'name' => 'month',
            'label' => _l('quality_month_s'),
        ],
        [
            'name' => 'year',
            'label' => _l('quality_year_s'),
        ],
        
    ];

    return $array_data;
}

/**
 * quality control pers
 * @return [type] 
 */
function quality_control_pers()
{

    $array_data = [];
    $array_data = [

        [
            'name' => 'operation',
            'label' => _l('quality_operation'),
        ],
        [
            'name' => 'product',
            'label' => _l('quality_product'),
        ],
        [
            'name' => 'quantity',
            'label' => _l('quality_quantity'),
        ],
        
    ];

    return $array_data;
}

/**
 * quality operations
 * @return [type] 
 */
function quality_operations()
{

    $array_data = [];

    if(quality_get_status_modules('warehouse')){
        $array_data = [

            [
                'name' => 'receipts',
                'label' => _l('quality_receipts'),
            ],
            [
                'name' => 'internal_transfers',
                'label' => _l('quality_internal_transfers'),
            ],
            [
                'name' => 'delivery_orders',
                'label' => _l('quality_delivery_orders'),
            ],
        ];
    }

    if(quality_get_status_modules('manufacturing')){
        $array_data[] = [
            'name' => 'manufacturing',
            'label' => _l('quality_manufacturing'),
        ];
    }

    return $array_data;
}

/**
 * quality control frequency
 * @return [type] 
 */
function quality_control_frequency()
{

    $array_data = [];
    $array_data = [

        [
            'name' => 'all',
            'label' => _l('quality_all'),
        ],
        [
            'name' => 'randomly',
            'label' => _l('quality_randomly'),
        ],
        [
            'name' => 'periodically',
            'label' => _l('quality_periodically'),
        ],
    ];

    return $array_data;
}

/**
 * quality control type
 * @return [type] 
 */
function quality_control_type()
{

    $array_data = [];
    $array_data = [

        [
            'name' => 'instructions',
            'label' => _l('quality_instructions'),
        ],
        [
            'name' => 'take_a_picture',
            'label' => _l('quality_take_a_picture'),
        ],
        [
            'name' => 'register_consumed_materials',
            'label' => _l('quality_register_consumed_materials'),
        ],
        [
            'name' => 'register_production',
            'label' => _l('quality_register_production'),
        ],
        
        [
            'name' => 'pass_fail',
            'label' => _l('quality_pass_fail'),
        ],
        [
            'name' => 'measure',
            'label' => _l('quality_measure'),
        ],
        
    ];

    return $array_data;
}

/**
 * quality type of unit of measurement
 * @return [type] 
 */
function quality_type_of_unit_of_measurement()
{

    $array_data = [];
    $array_data = [

        [
            'name' => 'length',
            'label' => _l('quality_Length'),
            'units' => "'mm', 'cm', 'm', 'inch', 'ft'",
            'aray_units' => ['mm', 'cm', 'm', 'inch', 'ft'],
        ],
        [
            'name' => 'weight',
            'label' => _l('quality_Weight'),
            'units' => "'mg', 'g', 'kg', 'lb', 'ton'",
            'aray_units' => ['mg', 'g', 'kg', 'lb', 'ton'],
        ],
        [
            'name' => 'temperature',
            'label' => _l('quality_Temperature'),
            'units' => "'°C', '°F', 'K'",
            'aray_units' => ['°C', '°F', 'K'],
        ],
        [
            'name' => 'time',
            'label' => _l('quality_Time'),
            'units' => "'ms', 's', 'min', 'h', 'day'",
            'aray_units' => ['ms', 's', 'min', 'h', 'day'],
        ],
        [
            'name' => 'volume',
            'label' => _l('quality_Volume'),
            'units' => "'mL', 'L', 'cm³', 'm³'",
            'aray_units' => ['mL', 'L', 'cm³', 'm³'],
        ],
        [
            'name' => 'concentration',
            'label' => _l('quality_Concentration'),
            'units' => "'%', 'ppm', 'mg/L', 'mol/L'",
            'aray_units' => ['%', 'ppm', 'mg/L', 'mol/L'],
        ],
        [
            'name' => 'pressure',
            'label' => _l('quality_Pressure'),
            'units' => "'Pa', 'kPa', 'bar', 'psi', 'atm'",
            'aray_units' => ['Pa', 'kPa', 'bar', 'psi', 'atm'],
        ],
        [
            'name' => 'voltage',
            'label' => _l('quality_Voltage'),
            'units' => "'mV', 'V', 'kV'",
            'aray_units' => ['mV', 'V', 'kV'],
        ],
        [
            'name' => 'current',
            'label' => _l('quality_Current'),
            'units' => "'µA', 'mA', 'A'",
            'aray_units' => ['µA', 'mA', 'A'],
        ],
        [
            'name' => 'power',
            'label' => _l('quality_Power'),
            'units' => "'W', 'kW', 'MW'",
            'aray_units' => ['W', 'kW', 'MW'],
        ],
        [
            'name' => 'frequency',
            'label' => _l('quality_Frequency'),
            'units' => "'Hz', 'kHz', 'MHz'",
            'aray_units' => ['Hz', 'kHz', 'MHz'],
        ],
        [
            'name' => 'resistance',
            'label' => _l('quality_Resistance'),
            'units' => "'Ω', 'kΩ', 'MΩ'",
            'aray_units' => ['Ω', 'kΩ', 'MΩ'],
        ],
        [
            'name' => 'humidity',
            'label' => _l('quality_Humidity'),
            'units' => "'%RH'",
            'aray_units' => ['%RH'],
        ],
        [
            'name' => 'angle',
            'label' => _l('quality_Angle'),
            'units' => "'°', 'rad', 'gon'",
            'aray_units' => ['°', 'rad', 'gon'],
        ],
        [
            'name' => 'torque',
            'label' => _l('quality_Torque'),
            'units' => "'Nm', 'kgf·cm', 'lb·ft'",
            'aray_units' => ['Nm', 'kgf·cm', 'lb·ft'],
        ],
        [
            'name' => 'speed',
            'label' => _l('quality_Speed'),
            'units' => "'m/s', 'km/h', 'rpm'",
            'aray_units' => ['m/s', 'km/h', 'rpm'],
        ],
        [
            'name' => 'density',
            'label' => _l('quality_Density'),
            'units' => "'g/cm³', 'kg/m³'",
            'aray_units' => ['g/cm³', 'kg/m³'],
        ],
        [
            'name' => 'flow_rate',
            'label' => _l('quality_Flow Rate'),
            'units' => "'L/min', 'm³/h', 'gal/min'",
            'aray_units' => ['L/min', 'm³/h', 'gal/min'],
        ],
        [
            'name' => 'percentage',
            'label' => _l('quality_Percentage'),
            'units' => "'%'",
            'aray_units' => ['%'],
        ],
        [
            'name' => 'count',
            'label' => _l('quality_Count'),
            'units' => "'pcs', 'units', 'items'",
            'aray_units' => ['pcs', 'units', 'items'],
        ],
        [
            'name' => 'score',
            'label' => _l('quality_Score'),
            'units' => "'point', 'grade', 'level'",
            'aray_units' => ['point', 'grade', 'level'],
        ],
        [
            'name' => 'other',
            'label' => _l('quality_other'),
            'units' => "",
            'aray_units' => [],
        ],

    ];


    return $array_data;
}

/**
 * quality handle quality check attachments
 * @param  [type] $id 
 * @return [type]     
 */
function quality_handle_quality_check_attachments($id)
{
    if (isset($_FILES['file']) && _perfex_upload_error($_FILES['file']['error'])) {
        header('HTTP/1.0 400 Bad error');
        echo _perfex_upload_error($_FILES['file']['error']);
        die;
    }
    $path =    QUALITY_CHECK_FOLDER . $id . '/';
    $CI   = & get_instance();

    if (isset($_FILES['file']['name'])) {

        // Get the temp file path
        $tmpFilePath = $_FILES['file']['tmp_name'];
        // Make sure we have a filepath
        if (!empty($tmpFilePath) && $tmpFilePath != '') {

            _maybe_create_upload_path($path);

            $filename    = unique_filename($path, $_FILES['file']['name']);

            $new_filename = str_replace(' ', '_', $filename);
            $new_filename = str_replace('(', '_', $new_filename);
            $new_filename = str_replace(')', '_', $new_filename);
            $new_filename = str_replace('. ', '.', $new_filename);

            $newFilePath = $path . $new_filename;
            // Upload the file into the temp dir
            if (move_uploaded_file($tmpFilePath, $newFilePath)) {

                $CI                       = & get_instance();
                $config                   = [];
                $config['image_library']  = 'gd2';
                $config['source_image']   = $newFilePath;
                $config['new_image']      = 'thumb_' . $new_filename;
                $config['maintain_ratio'] = true;
                $config['width']          = 300;
                $config['height']         = 300;
                $CI->image_lib->initialize($config);
                $CI->image_lib->resize();
                $CI->image_lib->clear();

                $config['image_library']  = 'gd2';
                $config['source_image']   = $newFilePath;
                $config['new_image']      = 'small_' . $new_filename;
                $config['maintain_ratio'] = true;
                $config['width']          = 40;
                $config['height']         = 40;
                $CI->image_lib->initialize($config);
                $CI->image_lib->resize();

                $attachment   = [];
                $attachment[] = [
                    'file_name' => $new_filename,
                    'filetype'  => $_FILES['file']['type'],
                ];

                $CI->misc_model->add_attachment_to_database($id, 'q_check', $attachment);

            }
        }
    }

}

/**
 * format quality check number
 * @param  [type] $id 
 * @return [type]     
 */
function format_quality_check_number($id)
{
    $CI = &get_instance();

    if (! is_object($id)) {
        $CI->db->select('datecreated,number,prefix,number_format')->from(db_prefix() . 'quality_checks')->where('id', $id);
        $quality_check = $CI->db->get()->row();
    } else {
        $quality_check = $id;
        $id       = $quality_check->id;
    }

    if (!$quality_check) {
        return '';
    }

    $number = sales_number_format($quality_check->number, $quality_check->number_format, $quality_check->prefix, $quality_check->datecreated);

    return hooks()->apply_filters('format_quality_check_number', $number, [
        'id'       => $id,
        'quality_check' => $quality_check,
    ]);
}

/**
 * quality check
 * @param  string $status 
 * @return [type]         
 */
function quality_check($status='')
{

    $statuses = [
        [
            'id'             => 'draft',
            'color'          => '#9e9e9e',
            'name'           => _l('quality_draft'),
            'order'          => 1,
            'filter_default' => true,
        ],
        [
            'id'             => 'to_do',
            'color'          => '#00BCD4',
            'name'           => _l('quality_to_do'),
            'order'          => 2,
            'filter_default' => true,
        ],
        [
            'id'             => 'completed',
            'color'          => '#4CAF50',
            'name'           => _l('quality_completed'),
            'order'          => 3,
            'filter_default' => true,
        ],
        [
            'id'             => 'cancelled',
            'color'          => '#F44336',
            'name'           => _l('quality_cancelled'),
            'order'          => 4,
            'filter_default' => true,
        ],
        
    ];

    usort($statuses, function ($a, $b) {
        return $a['order'] - $b['order'];
    });

    return $statuses;
}

/**
 * get quality check status by id
 * @param  [type] $id   
 * @param  [type] $type 
 * @return [type]       
 */
function get_quality_check_status_by_id($id, $type)
{
    $CI       = &get_instance();
    $statuses = quality_check();

    $status = [
        'id'             => 'draft',
        'color'          => '#9e9e9e',
        'name'           => _l('quality_draft'),
        'order'          => 1,
        'filter_default' => true,
    ];

    foreach ($statuses as $s) {
        if ($s['id'] == $id) {
            $status = $s;

            break;
        }
    }

    return $status;
}

/**
 * render squality scheck shtml
 * @param  [type]  $id           
 * @param  [type]  $type         
 * @param  string  $status_value 
 * @param  boolean $ChangeStatus 
 * @return [type]                
 */
function render_quality_check_status_html($id, $type, $status_value = '', $ChangeStatus = true)
{
    $status          = get_quality_check_status_by_id($status_value, $type);

    $task_statuses = quality_check();
   
    $outputStatus    = '';

    $outputStatus .= '<span class="inline-block label" style="color:' . $status['color'] . ';border:1px solid ' . $status['color'] . '" task-status-table="' . $status_value . '">';
    $outputStatus .= $status['name'];
    $canChangeStatus = has_permission('quality_check', '', 'edit');

    if ($canChangeStatus && $ChangeStatus) {
        $outputStatus .= '<div class="dropdown inline-block mleft5 table-export-exclude">';
        $outputStatus .= '<a href="#" class="dropdown-toggle text-dark" id="tableTaskStatus-' . $id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
        $outputStatus .= '<span data-toggle="tooltip" title="' . _l('ticket_single_change_status') . '"><i class="fa fa-caret-down" aria-hidden="true"></i></span>';
        $outputStatus .= '</a>';

        $outputStatus .= '<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="tableTaskStatus-' . $id . '">';
        foreach ($task_statuses as $taskChangeStatus) {
            
            if($status_value == 'to_do'){
                if($taskChangeStatus['id'] == 'draft'){
                    continue;
                }
            }
            if($status_value == 'completed'){
                if($taskChangeStatus['id'] == 'draft' || $taskChangeStatus['id'] == 'to_do'){
                    continue;
                }
            }

            if($status_value == 'cancelled'){
                if($taskChangeStatus['id'] == 'draft' || $taskChangeStatus['id'] == 'to_do'){
                    continue;
                }
            }
            
            if ($status_value != $taskChangeStatus['id']) {
                $outputStatus .= '<li>
                <a href="#" onclick="quality_check_status_mark_as(\'' . $taskChangeStatus['id'] . '\',' . $id . ',\'' . $type . '\'); return false;">
                ' . _l('task_mark_as', $taskChangeStatus['name']) . '
                </a>
                </li>';
            }
        }
        $outputStatus .= '</ul>';
        $outputStatus .= '</div>';
    }

    $outputStatus .= '</span>';

    return $outputStatus;
}

/**
 * quality check detail
 * @param  string $status 
 * @return [type]         
 */
function quality_check_detail($status='')
{

    $statuses = [

        [
            'id'             => 'to_do',
            'color'          => '#00BCD4',
            'name'           => _l('quality_to_do'),
            'order'          => 1,
            'filter_default' => true,
        ],
        [
            'id'             => 'validate',
            'color'          => '#00BCD4',
            'name'           => _l('quality_validate'),
            'order'          => 2,
            'filter_default' => true,
        ],
        
        [
            'id'             => 'passed',
            'color'          => '#4CAF50',
            'name'           => _l('quality_passed'),
            'order'          => 3,
            'filter_default' => true,
        ],
        [
            'id'             => 'failed',
            'color'          => '#F44336',
            'name'           => _l('quality_failed'),
            'order'          => 4,
            'filter_default' => true,
        ],
        
    ];

    usort($statuses, function ($a, $b) {
        return $a['order'] - $b['order'];
    });

    return $statuses;
}

/**
 * get quality check detail status by id
 * @param  [type] $id   
 * @param  [type] $type 
 * @return [type]       
 */
function get_quality_check_detail_status_by_id($id, $type)
{
    $CI       = &get_instance();
    $statuses = quality_check_detail();

    $status = [
        'id'             => 'to_do',
        'color'          => '#03A9F4',
        'name'           => _l('quality_to_do'),
        'order'          => 1,
        'filter_default' => true,
    ];

    foreach ($statuses as $s) {
        if ($s['id'] == $id) {
            $status = $s;

            break;
        }
    }

    return $status;
}

/**
 * render quality check detail html
 * @param  [type]  $id           
 * @param  [type]  $type         
 * @param  string  $status_value 
 * @param  boolean $ChangeStatus 
 * @return [type]                
 */
function render_quality_check_detail_status_html($id, $type, $status_value = '', $ChangeStatus = true)
{
    $status          = get_quality_check_detail_status_by_id($status_value, $type);

    $task_statuses = quality_check_detail();
   
    $outputStatus    = '';

    $outputStatus .= '<span class="inline-block label" style="color:' . $status['color'] . ';border:1px solid ' . $status['color'] . '" task-status-table="' . $status_value . '">';
    $outputStatus .= $status['name'];
    $canChangeStatus = has_permission('quality_check', '', 'edit');

    if ($canChangeStatus && $ChangeStatus) {
        $outputStatus .= '<div class="dropdown inline-block mleft5 table-export-exclude">';
        $outputStatus .= '<a href="#" class="dropdown-toggle text-dark" id="tableTaskStatus-' . $id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
        $outputStatus .= '<span data-toggle="tooltip" title="' . _l('ticket_single_change_status') . '"><i class="fa fa-caret-down" aria-hidden="true"></i></span>';
        $outputStatus .= '</a>';

        $outputStatus .= '<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="tableTaskStatus-' . $id . '">';
        foreach ($task_statuses as $taskChangeStatus) {

            if ($status_value != $taskChangeStatus['id']) {
                $outputStatus .= '<li>
                <a href="#" onclick="quality_check_detail_status_mark_as(\'' . $taskChangeStatus['id'] . '\',' . $id . ',\'' . $type . '\'); return false;">
                ' . _l('task_mark_as', $taskChangeStatus['name']) . '
                </a>
                </li>';
            }
        }
        $outputStatus .= '</ul>';
        $outputStatus .= '</div>';
    }

    $outputStatus .= '</span>';

    return $outputStatus;
}

/**
 * quality get status modules
 * @param  [type] $module_name 
 * @return [type]              
 */
function quality_get_status_modules($module_name){
    $CI             = &get_instance();

    $sql = 'select * from '.db_prefix().'modules where module_name = "'.$module_name.'" AND active =1 ';
    $module = $CI->db->query($sql)->row();
    if($module){
        return true;
    }else{
        return false;
    }
}

/**
 * quality handle quality check detail attachments
 * @param  [type] $id 
 * @return [type]     
 */
function quality_handle_quality_check_detail_attachments($id)
{
    if (isset($_FILES['file']) && _perfex_upload_error($_FILES['file']['error'])) {
        header('HTTP/1.0 400 Bad error');
        echo _perfex_upload_error($_FILES['file']['error']);
        die;
    }
    $path =    QUALITY_CHECK_DETAIL_FOLDER . $id . '/';
    $CI   = & get_instance();

    if (isset($_FILES['file']['name'])) {

        // Get the temp file path
        $tmpFilePath = $_FILES['file']['tmp_name'];
        // Make sure we have a filepath
        if (!empty($tmpFilePath) && $tmpFilePath != '') {

            _maybe_create_upload_path($path);

            $filename    = unique_filename($path, $_FILES['file']['name']);

            $new_filename = str_replace(' ', '_', $filename);
            $new_filename = str_replace('(', '_', $new_filename);
            $new_filename = str_replace(')', '_', $new_filename);
            $new_filename = str_replace('. ', '.', $new_filename);

            $newFilePath = $path . $new_filename;
            // Upload the file into the temp dir
            if (move_uploaded_file($tmpFilePath, $newFilePath)) {

                $CI                       = & get_instance();
                $config                   = [];
                $config['image_library']  = 'gd2';
                $config['source_image']   = $newFilePath;
                $config['new_image']      = 'thumb_' . $new_filename;
                $config['maintain_ratio'] = true;
                $config['width']          = 300;
                $config['height']         = 300;
                $CI->image_lib->initialize($config);
                $CI->image_lib->resize();
                $CI->image_lib->clear();

                $config['image_library']  = 'gd2';
                $config['source_image']   = $newFilePath;
                $config['new_image']      = 'small_' . $new_filename;
                $config['maintain_ratio'] = true;
                $config['width']          = 40;
                $config['height']         = 40;
                $CI->image_lib->initialize($config);
                $CI->image_lib->resize();

                $attachment   = [];
                $attachment[] = [
                    'file_name' => $new_filename,
                    'filetype'  => $_FILES['file']['type'],
                ];

                $CI->misc_model->add_attachment_to_database($id, 'q_check_detail', $attachment);

            }
        }
    }

}

/**
 * get quality check
 * @param  [type] $id 
 * @return [type]     
 */
function get_quality_check($id)
{
    $name = '';
    $CI = &get_instance();
    $CI->db->where('id', $id);
    $quality_check = $CI->db->get(db_prefix() . 'quality_checks')->row();

    return $quality_check;
}

/**
 * quality get item description
 * @param  boolean $id 
 * @return [type]      
 */
function quality_get_item_description($id = false)
{
    $CI           = & get_instance();
    $item_name = '';
    if (is_numeric($id)) {
        $CI->db->where('id', $id);

        $item =  $CI->db->get(db_prefix() . 'items')->row();
        if($item){
            $item_name = $item->description;
        }
    }
     return $item_name;
}

/**
 * quality alert priority
 * @return [type] 
 */
function quality_alert_priority()
{

    $statuses = [

        [
            'id'             => 'low',
            'color'          => '#00BCD4',
            'name'           => _l('quality_low'),
            'order'          => 1,
            'filter_default' => true,
        ],
        [
            'id'             => 'hight',
            'color'          => '#4CAF50',
            'name'           => _l('quality_hight'),
            'order'          => 2,
            'filter_default' => true,
        ],
        [
            'id'             => 'very_hight',
            'color'          => '#F44336',
            'name'           => _l('quality_very_hight'),
            'order'          => 3,
            'filter_default' => true,
        ],

    ];

    usort($statuses, function ($a, $b) {
        return $a['order'] - $b['order'];
    });

    return $statuses;

}

/**
 * quality alert root cause
 * @return [type] 
 */
function quality_alert_root_cause()
{

    $statuses = [

        [
            'id'             => 'workcenter_failure',
            'color'          => '#00BCD4',
            'name'           => _l('quality_workcenter_failure'),
            'order'          => 1,
            'filter_default' => true,
        ],
        [
            'id'             => 'parts_quality',
            'color'          => '#4CAF50',
            'name'           => _l('quality_parts_quality'),
            'order'          => 2,
            'filter_default' => true,
        ],
        [
            'id'             => 'work_operation',
            'color'          => '#F44336',
            'name'           => _l('quality_work_operation'),
            'order'          => 3,
            'filter_default' => true,
        ],
        [
            'id'             => 'others',
            'color'          => '#F44336',
            'name'           => _l('quality_others'),
            'order'          => 3,
            'filter_default' => true,
        ],
        
        
    ];

    usort($statuses, function ($a, $b) {
        return $a['order'] - $b['order'];
    });

    return $statuses;

}

/**
 * quality handle quality check attachments
 * @param  [type] $id 
 * @return [type]     
 */
function quality_handle_quality_alert_attachments($id)
{
    if (isset($_FILES['file']) && _perfex_upload_error($_FILES['file']['error'])) {
        header('HTTP/1.0 400 Bad error');
        echo _perfex_upload_error($_FILES['file']['error']);
        die;
    }
    $path =    QUALITY_ALERT_FOLDER . $id . '/';
    $CI   = & get_instance();

    if (isset($_FILES['file']['name'])) {

        // Get the temp file path
        $tmpFilePath = $_FILES['file']['tmp_name'];
        // Make sure we have a filepath
        if (!empty($tmpFilePath) && $tmpFilePath != '') {

            _maybe_create_upload_path($path);

            $filename    = unique_filename($path, $_FILES['file']['name']);

            $new_filename = str_replace(' ', '_', $filename);
            $new_filename = str_replace('(', '_', $new_filename);
            $new_filename = str_replace(')', '_', $new_filename);
            $new_filename = str_replace('. ', '.', $new_filename);

            $newFilePath = $path . $new_filename;
            // Upload the file into the temp dir
            if (move_uploaded_file($tmpFilePath, $newFilePath)) {

                $CI                       = & get_instance();
                $config                   = [];
                $config['image_library']  = 'gd2';
                $config['source_image']   = $newFilePath;
                $config['new_image']      = 'thumb_' . $new_filename;
                $config['maintain_ratio'] = true;
                $config['width']          = 300;
                $config['height']         = 300;
                $CI->image_lib->initialize($config);
                $CI->image_lib->resize();
                $CI->image_lib->clear();

                $config['image_library']  = 'gd2';
                $config['source_image']   = $newFilePath;
                $config['new_image']      = 'small_' . $new_filename;
                $config['maintain_ratio'] = true;
                $config['width']          = 40;
                $config['height']         = 40;
                $CI->image_lib->initialize($config);
                $CI->image_lib->resize();

                $attachment   = [];
                $attachment[] = [
                    'file_name' => $new_filename,
                    'filetype'  => $_FILES['file']['type'],
                ];

                $CI->misc_model->add_attachment_to_database($id, 'q_alert', $attachment);

            }
        }
    }

}

/**
 * format quality alert number
 * @param  [type] $id 
 * @return [type]     
 */
function format_quality_alert_number($id)
{
    $CI = &get_instance();

    if (! is_object($id)) {
        $CI->db->select('datecreated,number,prefix,number_format')->from(db_prefix() . 'quality_alerts')->where('id', $id);
        $quality_alert = $CI->db->get()->row();
    } else {
        $quality_alert = $id;
        $id       = $quality_alert->id;
    }

    if (!$quality_alert) {
        return '';
    }

    $number = sales_number_format($quality_alert->number, $quality_alert->number_format, $quality_alert->prefix, $quality_alert->datecreated);

    return hooks()->apply_filters('format_quality_alert_number', $number, [
        'id'       => $id,
        'quality_alert' => $quality_alert,
    ]);
}

/**
 * quality alert status
 * @param  string $status 
 * @return [type]         
 */
function quality_alert_status($status='')
{

    $statuses = [
        [
            'id'             => 'new',
            'color'          => '#9e9e9e',
            'name'           => _l('quality_new'),
            'order'          => 1,
            'filter_default' => true,
        ],
        [
            'id'             => 'verified',
            'color'          => '#00BCD4',
            'name'           => _l('quality_verified'),
            'order'          => 2,
            'filter_default' => true,
        ],
        [
            'id'             => 'action_proposed',
            'color'          => '#4CAF50',
            'name'           => _l('quality_action_proposed'),
            'order'          => 3,
            'filter_default' => true,
        ],
        [
            'id'             => 'resolved',
            'color'          => '#F44336',
            'name'           => _l('quality_resolved'),
            'order'          => 4,
            'filter_default' => true,
        ],
        
    ];

    usort($statuses, function ($a, $b) {
        return $a['order'] - $b['order'];
    });

    return $statuses;
}

/**
 * get quality alert status by id
 * @param  [type] $id   
 * @param  [type] $type 
 * @return [type]       
 */
function get_quality_alert_status_by_id($id, $type)
{
    $CI       = &get_instance();
    $statuses = quality_alert_status();

    $status = [
        'id'             => 'new',
        'color'          => '#9e9e9e',
        'name'           => _l('quality_new'),
        'order'          => 1,
        'filter_default' => true,
    ];

    foreach ($statuses as $s) {
        if ($s['id'] == $id) {
            $status = $s;

            break;
        }
    }

    return $status;
}

/**
 * render squality alert shtml
 * @param  [type]  $id           
 * @param  [type]  $type         
 * @param  string  $status_value 
 * @param  boolean $ChangeStatus 
 * @return [type]                
 */
function render_quality_alert_status_html($id, $type, $status_value = '', $ChangeStatus = true)
{
    $status          = get_quality_alert_status_by_id($status_value, $type);

    $task_statuses = quality_alert_status();
   
    $outputStatus    = '';

    $outputStatus .= '<span class="inline-block label" style="color:' . $status['color'] . ';border:1px solid ' . $status['color'] . '" task-status-table="' . $status_value . '">';
    $outputStatus .= $status['name'];
    $canChangeStatus = has_permission('quality_alert', '', 'edit');

    if ($canChangeStatus && $ChangeStatus) {
        $outputStatus .= '<div class="dropdown inline-block mleft5 table-export-exclude">';
        $outputStatus .= '<a href="#" class="dropdown-toggle text-dark" id="tableTaskStatus-' . $id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
        $outputStatus .= '<span data-toggle="tooltip" title="' . _l('ticket_single_change_status') . '"><i class="fa fa-caret-down" aria-hidden="true"></i></span>';
        $outputStatus .= '</a>';

        $outputStatus .= '<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="tableTaskStatus-' . $id . '">';
        foreach ($task_statuses as $taskChangeStatus) {
            
            if ($status_value != $taskChangeStatus['id']) {
                $outputStatus .= '<li>
                <a href="#" onclick="quality_alert_status_mark_as(\'' . $taskChangeStatus['id'] . '\',' . $id . ',\'' . $type . '\'); return false;">
                ' . _l('task_mark_as', $taskChangeStatus['name']) . '
                </a>
                </li>';
            }
        }
        $outputStatus .= '</ul>';
        $outputStatus .= '</div>';
    }

    $outputStatus .= '</span>';

    return $outputStatus;
}

/**
 * get quality alert priotity by id
 * @param  [type] $id   
 * @param  [type] $type 
 * @return [type]       
 */
function get_quality_alert_priority_by_id($id, $type)
{
    $CI       = &get_instance();
    $statuses = quality_alert_priority();

    $status = [
        'id'             => 'low',
        'color'          => '#9e9e9e',
        'name'           => _l('quality_low'),
        'order'          => 1,
        'filter_default' => true,
    ];

    foreach ($statuses as $s) {
        if ($s['id'] == $id) {
            $status = $s;

            break;
        }
    }

    return $status;
}

/**
 * render squality alert shtml
 * @param  [type]  $id           
 * @param  [type]  $type         
 * @param  string  $status_value 
 * @param  boolean $ChangeStatus 
 * @return [type]                
 */
function render_quality_alert_priority_html($id, $type, $status_value = '', $ChangeStatus = true)
{
    $status          = get_quality_alert_priority_by_id($status_value, $type);

    $task_statuses = quality_alert_priority();
   
    $outputStatus    = '';

    $outputStatus .= '<span class="inline-block label" style="color:' . $status['color'] . ';border:1px solid ' . $status['color'] . '" task-status-table="' . $status_value . '">';
    $outputStatus .= $status['name'];
    $canChangeStatus = has_permission('quality_alert', '', 'edit');

    if ($canChangeStatus && $ChangeStatus) {
        $outputStatus .= '<div class="dropdown inline-block mleft5 table-export-exclude">';
        $outputStatus .= '<a href="#" class="dropdown-toggle text-dark" id="tableTaskStatus-' . $id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
        $outputStatus .= '<span data-toggle="tooltip" title="' . _l('ticket_single_change_status') . '"><i class="fa fa-caret-down" aria-hidden="true"></i></span>';
        $outputStatus .= '</a>';

        $outputStatus .= '<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="tableTaskStatus-' . $id . '">';
        foreach ($task_statuses as $taskChangeStatus) {
            
            if ($status_value != $taskChangeStatus['id']) {
                $outputStatus .= '<li>
                <a href="#" onclick="quality_alert_priority_mark_as(\'' . $taskChangeStatus['id'] . '\',' . $id . ',\'' . $type . '\'); return false;">
                ' . _l('task_mark_as', $taskChangeStatus['name']) . '
                </a>
                </li>';
            }
        }
        $outputStatus .= '</ul>';
        $outputStatus .= '</div>';
    }

    $outputStatus .= '</span>';

    return $outputStatus;
}

/**
 * get quality alert status by id
 * @param  [type] $id   
 * @param  [type] $type 
 * @return [type]       
 */
function get_quality_alert_root_cause_by_id($id, $type)
{
    $CI       = &get_instance();
    $statuses = quality_alert_root_cause();

    $status = [
        'id'             => 'workcenter_failure',
        'color'          => '#9e9e9e',
        'name'           => _l('quality_workcenter_failure'),
        'order'          => 1,
        'filter_default' => true,
    ];

    foreach ($statuses as $s) {
        if ($s['id'] == $id) {
            $status = $s;

            break;
        }
    }

    return $status;
}

/**
 * render squality alert shtml
 * @param  [type]  $id           
 * @param  [type]  $type         
 * @param  string  $status_value 
 * @param  boolean $ChangeStatus 
 * @return [type]                
 */
function render_quality_alert_root_cause_html($id, $type, $status_value = '', $ChangeStatus = true)
{
    $status          = get_quality_alert_root_cause_by_id($status_value, $type);

    $task_statuses = quality_alert_root_cause();
   
    $outputStatus    = '';

    $outputStatus .= '<span class="inline-block label" style="color:' . $status['color'] . ';border:1px solid ' . $status['color'] . '" task-status-table="' . $status_value . '">';
    $outputStatus .= $status['name'];
    $canChangeStatus = has_permission('quality_alert', '', 'edit');

    if ($canChangeStatus && $ChangeStatus) {
        $outputStatus .= '<div class="dropdown inline-block mleft5 table-export-exclude">';
        $outputStatus .= '<a href="#" class="dropdown-toggle text-dark" id="tableTaskStatus-' . $id . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
        $outputStatus .= '<span data-toggle="tooltip" title="' . _l('ticket_single_change_status') . '"><i class="fa fa-caret-down" aria-hidden="true"></i></span>';
        $outputStatus .= '</a>';

        $outputStatus .= '<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="tableTaskStatus-' . $id . '">';
        foreach ($task_statuses as $taskChangeStatus) {
            
            if ($status_value != $taskChangeStatus['id']) {
                $outputStatus .= '<li>
                <a href="#" onclick="quality_alert_root_cause_mark_as(\'' . $taskChangeStatus['id'] . '\',' . $id . ',\'' . $type . '\'); return false;">
                ' . _l('task_mark_as', $taskChangeStatus['name']) . '
                </a>
                </li>';
            }
        }
        $outputStatus .= '</ul>';
        $outputStatus .= '</div>';
    }

    $outputStatus .= '</span>';

    return $outputStatus;
}

/**
 * quality required inventory module
 * @param  boolean $inventory_verison     
 * @param  boolean $manufacturing_verison 
 * @return [type]                         
 */
function quality_required_inventory_module($inventory_verison = false, $manufacturing_verison = false)
{   
    $CI   = & get_instance();
    if(!$inventory_verison){
        $inventory_verison = '1.5.1';
    }

    if(!$manufacturing_verison){
        $manufacturing_verison = '1.1.0';
    }

        //required inventory module version 1.5.1
    $sql = 'select * from '.db_prefix().'modules where module_name = "warehouse" AND active =1 ';
    $module = $CI->db->query($sql)->row();
    if($module){
        if(version_compare($inventory_verison, $module->installed_version, '<=')){
            $inventory = true;
        }else{
            $inventory = false;
        }
    }else{
        $inventory = false;
    }

        //required manufacturing module
    $sql = 'select * from '.db_prefix().'modules where module_name = "manufacturing" AND active =1 ';
    $module = $CI->db->query($sql)->row();
    if($module){
        if(version_compare($manufacturing_verison, $module->installed_version, '<=')){
            $manufacturing = true;
        }else{
            $manufacturing = false;
        }
    }else{
        $manufacturing = false;
    }


    $data=[];
    $data['inventory'] = $inventory;
    $data['manufacturing'] = $manufacturing;

    return $data;
}

if (!function_exists('add_quality_email_templates')) {
    /**
     * Init email templates and assign languages
     * @return void
     */
    function add_quality_email_templates($email_type) {
        $CI = &get_instance();

        $data['quality_templates'] = $CI->emails_model->get(['type' => 'quality', 'language' => 'english']);
        $data['email_type'] = $email_type;
        $CI->load->view('quality/email_templates', $data);
    }
}

/**
 * qm_init
 */
function qm_init(){
    $token1 = "aG9va3MoKS0+YWRkX2FjdGlvbigncXVhbGl0eV9pbml0JyxRVUFMSVRZX01PRFVMRV9OQU1FLidfYXBwaW50Jyk=";
    $token2 = "aG9va3MoKS0+YWRkX2FjdGlvbigncHJlX2FjdGl2YXRlX21vZHVsZScsIFFVQUxJVFlfTU9EVUxFX05BTUUuJ19wcmVhY3RpdmF0ZScp";
    $token3 = "ZnVuY3Rpb24gcXVhbGl0eV9hcHBpbnQoKQ==";
    $token4 = "ZnVuY3Rpb24gcXVhbGl0eV9wcmVhY3RpdmF0ZQ==";
    $token5 = "aG9va3MoKS0+ZG9fYWN0aW9uKCdxdWFsaXR5X2luaXQnKQ==";
    $path_h = realpath(realpath(__DIR__).'/..'). base64_decode('L2hlbHBlcnMvcXVhbGl0eV9oZWxwZXIucGhw');
    $path_i = realpath(realpath(__DIR__).'/..'). base64_decode('L3F1YWxpdHkucGhw');
    $path_c = realpath(realpath(__DIR__).'/..'). base64_decode('L2NvbnRyb2xsZXJzL1F1YWxpdHkucGhw');
    if(is_file($path_h)){
        $content_h = file_get_contents($path_h);        
        if (!(strpos($content_h, base64_decode($token1)) !== false && strpos($content_h, base64_decode($token2)) !== false)) {
            redirect(admin_url());
        }
    }
    if(is_file($path_i)){
        $content_i = file_get_contents($path_i);
        if (!(strpos($content_i, base64_decode($token3)) !== false && strpos($content_i, base64_decode($token4)) !== false)) {
            redirect(admin_url());
        }
    }
    if(is_file($path_c)){
        $content_c = file_get_contents($path_c);
        if (!strpos($content_c, base64_decode($token5)) !== false) {
            redirect(admin_url());
        }
    }
}

/**
 * quality get operation detail
 * @param  [type] $type 
 * @param  [type] $id   
 * @return [type]       
 */
function quality_get_operation_detail($type, $id)
{
    $operation_detail = '';
    if($type == 'receipts'){
        if(quality_get_status_modules('warehouse')){
            $goods_receipt = get_goods_receipt_code($id);
            if($goods_receipt){
                $operation_detail = '<a href="'.admin_url('warehouse/manage_purchase/'.$id).'" target="_blank">'.$goods_receipt->goods_receipt_code.'</a>';
            }
        }
    } elseif($type == 'delivery_orders'){
        if(quality_get_status_modules('warehouse')){
            $goods_delivery = get_goods_delivery_code($id);
            if($goods_delivery){
                $operation_detail = '<a href="'.admin_url('warehouse/manage_delivery/'.$id).'" target="_blank">'.$goods_delivery->goods_delivery_code.'</a>';
            }
        }

    } elseif($type == 'internal_transfers'){
        if(quality_get_status_modules('warehouse')){
            $goods_delivery = get_internal_delivery_code($id);
            if($goods_delivery){
                $operation_detail = '<a href="'.admin_url('warehouse/manage_internal_delivery/'.$id).'" target="_blank">'.$goods_delivery->internal_delivery_code.'</a>';
            }
        }
    } elseif($type == 'manufacturing'){
        if(quality_get_status_modules('manufacturing')){
            $mo_code = '';
            $mo_id = 0;
            $CI   = & get_instance();
            $CI->load->model('manufacturing/manufacturing_model');
            $get_work_order = $CI->manufacturing_model->get_work_order($id);
            if($get_work_order){
                $mo_id = $get_work_order->manufacturing_order_id;

                $mo = $CI->manufacturing_model->get_manufacturing_order($mo_id);
                if(isset($mo['manufacturing_order'])){
                    $mo_code .= ' - '.$mo['manufacturing_order']->manufacturing_order_code;
                }
            }

            $operation_detail = '<a href="'.admin_url('manufacturing/view_work_order/'.$id.'/'.$mo_id).'" target="_blank">'.get_work_order_name($id).'</a>'.$mo_code;
        }
    }
    return $operation_detail;
}

/**
 * quality get failure location name
 * @param  [type] $failure_location_ids 
 * @return [type]                       
 */
function quality_get_failure_location_name($failure_location_ids)
{
    $list_failure_location = '';
    if(!is_null($failure_location_ids) && new_strlen($failure_location_ids) > 0){
        $arr_location_ids = explode(',', $failure_location_ids);
        foreach ($arr_location_ids as $wh_key => $warehouseid) {
            $str = '';
            if ($warehouseid != '' && $warehouseid != '0') {
                $team = get_warehouse_name($warehouseid);
                if($team){
                    $value = $team != null ? get_object_vars($team)['warehouse_name'] : '';
                    $str .= '<span class="label label-tag tag-id-1"><span class="tag">' . $value . '</span><span class="hide">, </span></span>&nbsp';

                    $list_failure_location .= $str;
                }

            }
        }
    }
    return $list_failure_location;
}

if (!function_exists('new_html_entity_decode')) {
    
    function new_html_entity_decode($str){
        return html_entity_decode($str ?? '');
    }
}

if (!function_exists('new_strlen')) {
    
    function new_strlen($str){
        return strlen($str ?? '');
    }
}

if (!function_exists('new_str_replace')) {
    
    function new_str_replace($search, $replace, $subject){
        return str_replace($search, $replace, $subject ?? '');
    }
}

if (!function_exists('new_explode')) {

    function new_explode($delimiter, $string){
        return explode($delimiter, $string ?? '');
    }
}

/**
 * Check token
 */
function qm_token(){    
	$token_path = realpath(realpath(__DIR__).'/..'). base64_decode('L2xpYnJhcmllcy9saWN0b2tlbi8ubGlj');
	if(!is_file($token_path)){
		redirect(admin_url());
	}	
}

