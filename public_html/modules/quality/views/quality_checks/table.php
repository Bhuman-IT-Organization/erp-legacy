<?php

defined('BASEPATH') or exit('No direct script access allowed');

$aColumns = [
    db_prefix() . 'quality_checks.id as id',
    'name',
    db_prefix().'items.description as item_name',
    'operation',
    'control_per',
    'percentage',
    'control_point_id',
    'type',
    'team_id',
    db_prefix().'quality_checks.status as status',
    'staffid',
    'datecreated',
];
$sIndexColumn = 'id';
$sTable = db_prefix() . 'quality_checks';

$where = [];
$join= [];
$join         = [
    'LEFT JOIN '.db_prefix().'items ON '.db_prefix().'items.id = '.db_prefix().'quality_checks.item_id',
];

$from_date_filter = $this->ci->input->post('from_date_filter');
$to_date_filter = $this->ci->input->post('to_date_filter');
$item_filter = $this->ci->input->post('item_filter');
$quality_quality_control_point_id_filter = $this->ci->input->post('quality_quality_control_point_id_filter');
$quality_check_type_filter = $this->ci->input->post('quality_check_type_filter');
$team_filter = $this->ci->input->post('team_filter');
$status_filter = $this->ci->input->post('status_filter');
$rel_type_filter = $this->ci->input->post('rel_type_filter');
$rel_id_filter = $this->ci->input->post('rel_id_filter');
$control_point_filter = $this->ci->input->post('control_point_filter');

if($from_date_filter != '' &&  $to_date_filter != ''){
    $from_date_filter = to_sql_date($from_date_filter);
    $to_date_filter = to_sql_date($to_date_filter);

    array_push($where, ' AND ( (date_format('.db_prefix().'quality_checks.datecreated,"%Y-%m-%d")  >= "'.$from_date_filter.'" AND date_format('.db_prefix().'quality_checks.datecreated,"%Y-%m-%d")  <= "'.$to_date_filter.'") )');

}elseif( $from_date_filter != '' && $to_date_filter == ''){
    $from_date_filter = to_sql_date($from_date_filter);

    array_push($where, ' AND ( (date_format('.db_prefix().'quality_checks.datecreated,"%Y-%m-%d") >= "'.$from_date_filter.'" ))');

}elseif($from_date_filter == '' && $to_date_filter != ''){
    $to_date_filter = to_sql_date($to_date_filter);

    array_push($where, ' AND ( (date_format('.db_prefix().'quality_checks.datecreated,"%Y-%m-%d") <= "'.$to_date_filter.'" ))');
}


if($quality_quality_control_point_id_filter){
    $where[] = 'AND '.db_prefix().'quality_checks.control_point_id = "'.$quality_quality_control_point_id_filter.'"';
}
if($item_filter){
    $where[] = 'AND '.db_prefix().'quality_checks.item_id = '.$item_filter;
}
if($quality_check_type_filter){
    $where[] = 'AND '.db_prefix().'quality_checks.type = "'.$quality_check_type_filter.'"';
}
if($status_filter){
    $where[] = 'AND '.db_prefix().'quality_checks.status = "'.$status_filter.'"';
}
if($team_filter){
    $where[] = 'AND '.db_prefix().'quality_checks.team_id = "'.$team_filter.'"';
}
if($control_point_filter){
    $where[] = 'AND '.db_prefix().'quality_checks.control_point_id = "'.$control_point_filter.'"';
}


if(!is_admin() && !has_permission('quality_check','','view') && !$rel_type_filter){
    $staff_user_id = get_staff_user_id();
      //View own
      // get team_id by staff
    $where[] = 'AND ('.db_prefix().'quality_checks.staffid = '.$staff_user_id.' OR '.db_prefix().'quality_checks.team_id IN (SELECT id FROM '.db_prefix().'quality_teams WHERE team_member = '.$staff_user_id.'))' ;
}


if($rel_type_filter){
    if($rel_type_filter == 'receipts'){

        $where[] = 'AND '.db_prefix().'quality_checks.operation = "receipts" AND '.db_prefix().'quality_checks.goods_receipt_id = '.$rel_id_filter ;
    }elseif($rel_type_filter == 'internal_transfers'){
        $where[] = 'AND '.db_prefix().'quality_checks.operation = "internal_transfers" AND '.db_prefix().'quality_checks.internal_delivery_id = '.$rel_id_filter ;

    }elseif($rel_type_filter == 'delivery_orders'){
        $where[] = 'AND '.db_prefix().'quality_checks.operation = "delivery_orders" AND '.db_prefix().'quality_checks.goods_delivery_id = '.$rel_id_filter ;

    }elseif($rel_type_filter == 'manufacturing'){
        $where[] = 'AND '.db_prefix().'quality_checks.operation = "manufacturing" AND '.db_prefix().'quality_checks.work_order_id = '.$rel_id_filter ;
    }
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, ['partial_transfer_test', db_prefix() . 'quality_checks.status as status', 'goods_receipt_id', 'internal_delivery_id', 'goods_delivery_id', 'work_order_id']);

$output = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];
    $row[] = $aRow['id'];

    if((has_permission('quality_check', '', 'view') || has_permission('quality_check', '', 'view_own') || is_admin())){
        $options = '<a href="'.admin_url('quality/quality_check_detail/'.$aRow['id'].'?tab=detail').'" >'.format_quality_check_number($aRow['id']). '</a>';
    }else{
        $options = format_quality_check_number($aRow['id']);
    }

    $options .= '<div class="row-options">';

    if((has_permission('quality_check', '', 'view') || has_permission('quality_check', '', 'view_own') || is_admin())){
        $options .= '<a href="'.admin_url('quality/quality_check_detail/'.$aRow['id']).'?tab=detail" >' . _l('view') . '</a>';
    }
    if((has_permission('quality_check', '', 'edit') || has_permission('quality_check', '', 'create') || is_admin()) && $aRow['status'] == 'draft'){
        $options .= ' | <a href="#" onclick="quality_check_modal('.$aRow['id'].'); return false;" >' . _l('edit') . '</a>';
    }
    if (has_permission('quality_check', '', 'delete')) {
        $options .= ' | <a href="#" onclick="delete_quality_check('.$aRow['id'].'); return false;" class="text-danger">' . _l('delete') . '</a>';
    }
    $options .= '</div>';
    $row[] = $options;

    $row[] = $aRow['name'];
    $row[] = $aRow['item_name'];
    $operation_id = 0;
    if($aRow['operation'] == 'receipts'){
        $operation_id = $aRow['goods_receipt_id'];
    } elseif($aRow['operation'] == 'internal_transfers'){
        $operation_id = $aRow['internal_delivery_id'];
    } elseif($aRow['operation'] == 'delivery_orders'){
        $operation_id = $aRow['goods_delivery_id'];
    } elseif($aRow['operation'] == 'manufacturing'){
        $operation_id = $aRow['work_order_id'];
    }
    $operation_detail = quality_get_operation_detail($aRow['operation'], $operation_id);

    $row[] = _l('quality_'.$aRow['operation']).': '. $operation_detail;
    $row[] = _l('quality_'.$aRow['control_per']);
    $row[] = ($aRow['partial_transfer_test'] == 1 && $aRow['control_per'] == 'quantity') ? $aRow['percentage'] : '---';
    $row[] = '<a href="'.admin_url('quality/control_point_detail/'.$aRow['control_point_id'].'?tab=detail').'" target="_blank">'.format_control_point_number($aRow['control_point_id']).'</a>';
    $row[] = _l('quality_'.$aRow['type']);
    $row[] = get_quality_team_name($aRow['team_id']);
    $row[] = render_quality_check_status_html($aRow['id'], '', $aRow['status']);
    $row[] = get_staff_full_name($aRow['staffid']);
    $row[] = $aRow['datecreated'] != null ? _dt($aRow['datecreated']) : '---';

    $output['aaData'][] = $row;
}


