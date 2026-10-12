<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    db_prefix() . 'quality_check_details.id as id',
    'quality_check_id',
    'check_work_order_id',
    db_prefix() . 'items.description as item_description',
    'actual_size',
    db_prefix() . 'quality_check_details.description as description',
    'quality_check_results',
    db_prefix() . 'quality_check_details.status as status',
    'completion_date',
    db_prefix() . 'quality_check_details.datecreated as datecreated',
    db_prefix() . 'quality_check_details.staffid as staffid',
    '1',
];
$sIndexColumn = 'id';
$sTable = db_prefix() . 'quality_check_details';

$where = [];
$join= [];
$join         = [
    'LEFT JOIN '.db_prefix().'items ON '.db_prefix().'items.id = '.db_prefix().'quality_check_details.check_item_id',
    'LEFT JOIN '.db_prefix().'quality_checks ON '.db_prefix().'quality_checks.id = '.db_prefix().'quality_check_details.quality_check_id',
];

$quality_check_id = $this->ci->input->post('quality_check_id');

$where[] = 'AND '.db_prefix().'quality_check_details.quality_check_id = '.$quality_check_id ;

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [ db_prefix() . 'quality_check_details.status as status', db_prefix() . 'quality_checks.type as type']);

$output = $result['output'];
$rResult = $result['rResult'];

$change_check_detail = false;
$staff_id = get_staff_user_id();
$quality_check = get_quality_check($quality_check_id);
if($quality_check){
    $quality_team = get_quality_team_name($quality_check->team_id, false);
    if($quality_team){
        if($quality_team->team_member == $staff_id){
            $change_check_detail = true;
        }
    }
}
if(is_admin()){
    $change_check_detail = true;
}


foreach ($rResult as $aRow) {
    $row = [];
    $row[] = $aRow['id'];
    $row[] = format_quality_check_number($aRow['quality_check_id']);
    if(quality_get_status_modules('manufacturing')){
        $row[] = $aRow['check_work_order_id'] != 0 ? get_work_order_name($aRow['check_work_order_id']) : '---';
    }else{
        $row[] = $aRow['check_work_order_id'] != 0 ? '---' : '---';
    }

    $row[] = $aRow['item_description'];
    $row[] = $aRow['actual_size'] != 0.00 ? $aRow['actual_size'] : '---';
    $row[] = $aRow['description'];
    if($aRow['status'] == 'failed'){
        $row[] = '<span class="text-danger">'.$aRow['quality_check_results'].'</span>';
    }elseif($aRow['status'] == 'passed'){
        $row[] = '<span class="text-success">'.$aRow['quality_check_results'].'</span>';
    }else{
        $row[] = $aRow['quality_check_results'];
    }
    $row[] = render_quality_check_detail_status_html($aRow['id'], '', $aRow['status'], false);
    $row[] = $aRow['completion_date'] != null ? _dt($aRow['completion_date']) : '---';
    $row[] = $aRow['datecreated'] != null ? _dt($aRow['datecreated']) : '---';
    $row[] = get_staff_full_name($aRow['staffid']);
    $options = '';

    if($aRow['status'] == 'to_do'){
        if($change_check_detail){
            $options .= '<a href="javascript:void(0)" onclick="quality_check_detail_modal('.$aRow['id'].','.$aRow['quality_check_id'].')" class="btn btn-warning btn-icon" data-original-title="'._l('quality_check').'" data-toggle="tooltip" data-placement="top">
            '._l('quality_check').'
            </a>';
        }
    }else{
        $options .= '<a href="javascript:void(0)" onclick="quality_check_detail_modal('.$aRow['id'].','.$aRow['quality_check_id'].')" class="btn btn-success btn-icon" data-original-title="'._l('view').'" data-toggle="tooltip" data-placement="top">
        '._l('view').'
        </a>';
    }

    $row[] = $options;

    $output['aaData'][] = $row;
}


