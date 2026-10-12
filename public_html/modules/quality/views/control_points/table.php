<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    db_prefix() . 'quality_control_points.id as id',
    'name',
    db_prefix().'items.description as item_name',
    'operation',
    'control_per',
    'percentage',
    'control_frequency',
    'type',
    'team_id',
    'responsible_id',
    db_prefix() . 'quality_control_points.status as status',
    'datecreated',
];
$sIndexColumn = 'id';
$sTable = db_prefix() . 'quality_control_points';

$where = [];
$join= [];
$join         = [
    'LEFT JOIN '.db_prefix().'items ON '.db_prefix().'items.id = '.db_prefix().'quality_control_points.item_id',
];

$from_date_filter = $this->ci->input->post('from_date_filter');
$to_date_filter = $this->ci->input->post('to_date_filter');
$item_filter = $this->ci->input->post('item_filter');
$quality_control_frequency_filter = $this->ci->input->post('quality_control_frequency_filter');
$control_point_type_filter = $this->ci->input->post('control_point_type_filter');


if($from_date_filter != '' &&  $to_date_filter != ''){
    $from_date_filter = to_sql_date($from_date_filter);
    $to_date_filter = to_sql_date($to_date_filter);

    array_push($where, ' AND ( (date_format('.db_prefix().'quality_control_points.datecreated,"%Y-%m-%d")  >= "'.$from_date_filter.'" AND date_format('.db_prefix().'quality_control_points.datecreated,"%Y-%m-%d")  <= "'.$to_date_filter.'") )');

}elseif( $from_date_filter != '' && $to_date_filter == ''){
    $from_date_filter = to_sql_date($from_date_filter);

    array_push($where, ' AND ( (date_format('.db_prefix().'quality_control_points.datecreated,"%Y-%m-%d") >= "'.$from_date_filter.'" ))');

}elseif($from_date_filter == '' && $to_date_filter != ''){
    $to_date_filter = to_sql_date($to_date_filter);

    array_push($where, ' AND ( (date_format('.db_prefix().'quality_control_points.datecreated,"%Y-%m-%d") <= "'.$to_date_filter.'" ))');
}


if($quality_control_frequency_filter){
    $where[] = 'AND '.db_prefix().'quality_control_points.control_frequency = "'.$quality_control_frequency_filter.'"';
}
if($item_filter){
    $where[] = 'AND '.db_prefix().'quality_control_points.item_id = '.$item_filter;
}
if($control_point_type_filter){
    $where[] = 'AND '.db_prefix().'quality_control_points.type = "'.$control_point_type_filter.'"';
}


if(!is_admin() && !has_permission('quality_control_point','','view')){
      //View own
    $where[] = 'AND '.db_prefix().'quality_control_points.staffid = '.get_staff_user_id() ;
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, ['partial_transfer_test', db_prefix() . 'quality_control_points.status as status']);

$output = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];
    $row[] = $aRow['id'];

    if((has_permission('quality_control_point', '', 'view') || has_permission('quality_control_point', '', 'view_own') || is_admin())){
        $options = '<a href="'.admin_url('quality/control_point_detail/'.$aRow['id'].'?tab=detail').'" >'.format_control_point_number($aRow['id']). '</a>';
    }else{
        $options = format_control_point_number($aRow['id']);
    }

    $options .= '<div class="row-options">';

    if((has_permission('quality_control_point', '', 'view') || has_permission('quality_control_point', '', 'view_own') || is_admin())){
        $options .= '<a href="'.admin_url('quality/control_point_detail/'.$aRow['id']).'?tab=detail" >' . _l('view') . '</a>';
    }
    if((has_permission('quality_control_point', '', 'edit') || has_permission('quality_control_point', '', 'create') || is_admin())){
        $options .= ' | <a href="#" onclick="control_point_modal('.$aRow['id'].'); return false;" >' . _l('edit') . '</a>';
    }
    if (has_permission('quality_control_point', '', 'delete')) {
        $options .= ' | <a href="#" onclick="delete_control_point('.$aRow['id'].'); return false;" class="text-danger">' . _l('delete') . '</a>';
    }
    $options .= '</div>';
    $row[] = $options;

    $row[] = $aRow['name'];
    $row[] = $aRow['item_name'];
    $row[] = _l('quality_'.$aRow['operation']);
    $row[] = _l('quality_'.$aRow['control_per']);
    $row[] = ($aRow['partial_transfer_test'] == 1 && $aRow['control_per'] == 'quantity') ? $aRow['percentage'] : '---';
    $row[] = _l('quality_'.$aRow['control_frequency']);
    $row[] = _l('quality_'.$aRow['type']);
    $row[] = get_quality_team_name($aRow['team_id']);
    $row[] = get_staff_full_name($aRow['responsible_id']);
    $status = '';
    $checked = '';
    if ($aRow['status'] == 1) {
        $checked = 'checked';
    }

    $status .= '<div class="onoffswitch">
    <input type="checkbox" ' . (((is_admin() || !has_permission('quality_control_point', '', 'edit')) && !is_admin()) ? 'disabled' : '') . ' data-switch-url="' . admin_url() . 'quality/change_quality_control_status" name="onoffswitch" class="onoffswitch-checkbox" id="c_' . $aRow['id'] . '" data-id="' . $aRow['id'] . '" data-status="' . $aRow['status'] . '" ' . $checked . '>
    <label class="onoffswitch-label" for="c_' . $aRow['id'] . '"></label>
    </div>';

    $row[] = $status;

    $row[] = $aRow['datecreated'] != null ? _dt($aRow['datecreated']) : '---';

    $output['aaData'][] = $row;
}


