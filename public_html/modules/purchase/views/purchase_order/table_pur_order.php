<?php

defined('BASEPATH') or exit('No direct script access allowed');

$custom_fields = get_custom_fields('pur_order', [
    'show_on_table' => 1,
    ]);

$aColumns = [
    'pur_order_number',
    'vendor',
    'order_date',
    'type',
    'project',
    'approve_status',
    'department',
    'pur_order_name',
    db_prefix() . 'pur_orders.addedfrom as po_addedfrom',
    'subtotal',
    'total_tax',
    'total',
    '(SELECT GROUP_CONCAT(name SEPARATOR ",") FROM ' . db_prefix() . 'taggables JOIN ' . db_prefix() . 'tags ON ' . db_prefix() . 'taggables.tag_id = ' . db_prefix() . 'tags.id WHERE rel_id = ' . db_prefix() . 'pur_orders.id and rel_type="pur_order" ORDER by tag_order ASC) as tags', 
    'delivery_date',
    'delivery_status',
    'number',
    'expense_convert',
    ];

if(isset($vendor) || isset($project)){
    $aColumns = [
    'pur_order_number',
    'total',
    'total_tax',
    'vendor', 
    'order_date',
    'approve_status',
    ];
}

$sIndexColumn = 'id';
$sTable       = db_prefix().'pur_orders';
$join         = [
                    'LEFT JOIN '.db_prefix().'pur_vendor ON '.db_prefix().'pur_vendor.userid = '.db_prefix().'pur_orders.vendor',
                    'LEFT JOIN '.db_prefix().'departments ON '.db_prefix().'departments.departmentid = '.db_prefix().'pur_orders.department',
                    'LEFT JOIN '.db_prefix().'projects ON '.db_prefix().'projects.id = '.db_prefix().'pur_orders.project',
                ];
$i = 0;
foreach ($custom_fields as $field) {
    $select_as = 'cvalue_' . $i;
    if ($field['type'] == 'date_picker' || $field['type'] == 'date_picker_time') {
        $select_as = 'date_picker_cvalue_' . $i;
    }
    array_push($aColumns, 'ctable_' . $i . '.value as ' . $select_as);
    array_push($join, 'LEFT JOIN '.db_prefix().'customfieldsvalues as ctable_' . $i . ' ON '.db_prefix().'pur_orders.id = ctable_' . $i . '.relid AND ctable_' . $i . '.fieldto="' . $field['fieldto'] . '" AND ctable_' . $i . '.fieldid=' . $field['id']);
    $i++;
}

$where = [];

if(isset($vendor)){
    array_push($where, ' AND '.db_prefix().'pur_orders.vendor = '.$vendor);
}

if(isset($project)){
    array_push($where, ' AND '.db_prefix().'pur_orders.project = '.$project);
}

if ($this->ci->input->post('from_date')
    && $this->ci->input->post('from_date') != '') {
    array_push($where, 'AND order_date >= "'.$this->ci->input->post('from_date').'"');
}

if ($this->ci->input->post('to_date')
    && $this->ci->input->post('to_date') != '') {
    array_push($where, 'AND order_date <= "'.$this->ci->input->post('to_date').'"');
}


if ($this->ci->input->post('status') && count($this->ci->input->post('status')) > 0) {
    array_push($where, 'AND approve_status IN (' . implode(',', $this->ci->input->post('status')) . ')');
}

if ($this->ci->input->post('vendor')
    && count($this->ci->input->post('vendor')) > 0) {
    array_push($where, 'AND vendor IN (' . implode(',', $this->ci->input->post('vendor')) . ')');
}

if ($this->ci->input->post('project')
    && count($this->ci->input->post('project')) > 0) {
    array_push($where, 'AND project IN (' . implode(',', $this->ci->input->post('project')) . ')');
}

if ($this->ci->input->post('department')
    && count($this->ci->input->post('department')) > 0) {
    array_push($where, 'AND department IN (' . implode(',', $this->ci->input->post('department')) . ')');
}

if ($this->ci->input->post('delivery_status')
    && count($this->ci->input->post('delivery_status')) > 0) {
    array_push($where, 'AND delivery_status IN (' . implode(',', $this->ci->input->post('delivery_status')) . ')');
}

if ($this->ci->input->post('purchase_request')
    && count($this->ci->input->post('purchase_request')) > 0) {
    array_push($where, 'AND pur_request IN (' . implode(',', $this->ci->input->post('purchase_request')) . ')');
}

if(!has_permission('purchase_orders', '', 'view')){
   array_push($where, 'AND (' . db_prefix() . 'pur_orders.addedfrom = '.get_staff_user_id().' OR ' . db_prefix() . 'pur_orders.buyer = '.get_staff_user_id().' OR ' . db_prefix() . 'pur_orders.vendor IN (SELECT vendor_id FROM ' . db_prefix() . 'pur_vendor_admin WHERE staff_id=' . get_staff_user_id() . ') OR '.get_staff_user_id().' IN (SELECT staffid FROM ' . db_prefix() . 'pur_approval_details WHERE ' . db_prefix() . 'pur_approval_details.rel_type = "pur_order" AND ' . db_prefix() . 'pur_approval_details.rel_id = '.db_prefix().'pur_orders.id))');
}

$type = $this->ci->input->post('type');
if (isset($type)) {
    $where_type = '';
    foreach ($type as $t) {
        if ($t != '') {
            if ($where_type == '') {
                $where_type .= ' AND (tblpur_orders.type = "' . $t . '"';
            } else {
                $where_type .= ' or tblpur_orders.type = "' . $t . '"';
            }
        }
    }
    if ($where_type != '') {
        $where_type .= ')';
        array_push($where, $where_type);
    }
}

//tags filter
$tags_ft = $this->ci->input->post('item_filter');
if (isset($tags_ft)) {
    $where_tags_ft = '';
    foreach ($tags_ft as $commodity_id) {
        if ($commodity_id != '') {
            if ($where_tags_ft == '') {
                $where_tags_ft .= ' AND (tblpur_orders.id = "' . $commodity_id . '"';
            } else {
                $where_tags_ft .= ' or tblpur_orders.id = "' . $commodity_id . '"';
            }
        }
    }
    if ($where_tags_ft != '') {
        $where_tags_ft .= ')';
        array_push($where, $where_tags_ft);
    }
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [db_prefix().'pur_orders.id as id','company','pur_order_number','expense_convert',db_prefix().'projects.name as project_name',db_prefix().'departments.name as department_name', 'currency', 'is_deleted', 'pur_request']);

$output  = $result['output'];
$rResult = $result['rResult'];

$this->ci->load->model('purchase/purchase_model');

foreach ($rResult as $aRow) {
    $row = [];

   for ($i = 0; $i < count($aColumns); $i++) {
        if (strpos($aColumns[$i], 'as') !== false && !isset($aRow[$aColumns[$i]])) {
            $_data = $aRow[strafter($aColumns[$i], 'as ')];
        } else {
            $_data = $aRow[$aColumns[$i]];
        }

        $base_currency = get_base_currency_pur();
        if($aRow['currency'] != 0){
            $base_currency = pur_get_currency_by_id($aRow['currency']);
        }

        if($aColumns[$i] == 'total'){
            $_data = e(app_format_money($aRow['total'], $base_currency->name));
        }elseif($aColumns[$i] == 'pur_order_number'){

            $numberOutput = '';
            $is_deleted = isset($aRow['is_deleted']) ? (int)$aRow['is_deleted'] : 0;
    
            $numberOutput = '<a href="' . admin_url('purchase/purchase_order/' . $aRow['id']) . '"  onclick="init_pur_order(' . $aRow['id'] . '); return false;" >'.$aRow['pur_order_number']. '</a>';

            if($is_deleted == 1){
                $numberOutput .= '<br><span class="label label-danger">'._l('deleted').'</span>';
            }

            $numberOutput .= '<div class="row-options">';

            if (has_permission('purchase_orders', '', 'view') || has_permission('purchase_orders', '', 'view_own')) {
                $numberOutput .= ' <a href="' . admin_url('purchase/purchase_order/' . $aRow['id']) . '" onclick="init_pur_order(' . $aRow['id'] . '); return false;" >' . _l('view') . '</a>';
            }

            // Only show edit/copy/delete for active (non-deleted) POs
            if($is_deleted == 0){
                $list_approve_status = $this->ci->purchase_model->get_list_approval_details($aRow['id'],'pur_order');

                if ((has_permission('purchase_orders', '', 'edit') || is_admin()) && $aRow['approve_status'] != 2) {
                    $numberOutput .= ' | <a href="' . admin_url('purchase/pur_order/' . $aRow['id']) . '">' . _l('edit') . '</a>';
                }

                if (has_permission('purchase_orders', '', 'create') ) {
                    $numberOutput .= ' | <a href="' . admin_url('purchase/pur_order/' . $aRow['id'].'?mode=copy') . '" class="text-success">' . _l('copy') . '</a>';
                }

                if (has_permission('purchase_orders', '', 'delete') || is_admin()) {
                    $numberOutput .= ' | <a href="#" onclick="confirm_delete_po(' . $aRow['id'] . '); return false;" class="text-danger">' . _l('delete') . '</a>';
                }
            }
            $numberOutput .= '</div>';

            $_data = $numberOutput;

        }elseif($aColumns[$i] == 'vendor'){
            $_data = '<a href="' . admin_url('purchase/vendor/' . $aRow['vendor']) . '" >' .  $aRow['company'] . '</a>';
        }elseif ($aColumns[$i] == 'order_date') {
            $_data = _d($aRow['order_date']);
        }elseif($aColumns[$i] == 'approve_status'){
            $_data = get_status_approve($aRow['approve_status']);
        }elseif($aColumns[$i] == 'total_tax'){
          $tax = $this->ci->purchase_model->get_html_tax_pur_order($aRow['id']);
          $total_tax = 0;
          foreach($tax['taxes_val'] as $tax_val){
            $total_tax += $tax_val;
          }

          $_data = e(app_format_money($total_tax, $base_currency->name));
        }elseif($aColumns[$i] == 'expense_convert'){
            if($aRow['expense_convert'] == 0){
             $_data = '<a href="javascript:void(0)" onclick="convert_expense('.$aRow['id'].','.$aRow['total'].'); return false;" class="btn btn-warning btn-icon">'._l('convert').'</a>';
            }else{
                $_data = '<a href="'.admin_url('expenses/list_expenses/'.$aRow['expense_convert']).'" class="btn btn-success btn-icon">'._l('view_expense').'</a>';
            }
        }elseif($aColumns[$i] == '(SELECT GROUP_CONCAT(name SEPARATOR ",") FROM ' . db_prefix() . 'taggables JOIN ' . db_prefix() . 'tags ON ' . db_prefix() . 'taggables.tag_id = ' . db_prefix() . 'tags.id WHERE rel_id = ' . db_prefix() . 'pur_orders.id and rel_type="pur_order" ORDER by tag_order ASC) as tags'){
                
                $_data = render_tags($aRow['tags']);

        }elseif($aColumns[$i] == 'type'){
            $_data = _l($aRow['type']);
        }elseif($aColumns[$i] == 'subtotal'){
            $_data = e(app_format_money($aRow['subtotal'],$base_currency->name));
        }elseif($aColumns[$i] == db_prefix() . 'pur_orders.addedfrom as po_addedfrom'){
            $_data = get_staff_full_name($aRow['po_addedfrom']);
        }elseif($aColumns[$i] == 'project'){
            $_data = $aRow['project_name'];
        }elseif($aColumns[$i] == 'department'){
            $_data = $aRow['department_name'];
        }elseif($aColumns[$i] == 'delivery_status'){
            $delivery_status = '';

            if($aRow['delivery_status'] == 0){
                $delivery_status = '<span class="inline-block label label-danger" id="status_span_'.$aRow['id'].'" task-status-table="undelivered">'._l('undelivered');
            }else if($aRow['delivery_status'] == 1){
                $delivery_status = '<span class="inline-block label label-success" id="status_span_'.$aRow['id'].'" task-status-table="completely_delivered">'._l('completely_delivered');
            }else if($aRow['delivery_status'] == 2){
                $delivery_status = '<span class="inline-block label label-info" id="status_span_'.$aRow['id'].'" task-status-table="pending_delivered">'._l('pending_delivered');
            }else if($aRow['delivery_status'] == 3){
                $delivery_status = '<span class="inline-block label label-warning" id="status_span_'.$aRow['id'].'" task-status-table="partially_delivered">'._l('partially_delivered');
            }
            
            if(has_permission('purchase_orders', '', 'edit') || is_admin()){
                $delivery_status .= '<div class="dropdown inline-block mleft5 table-export-exclude">';
                $delivery_status .= '<a href="#" class="dropdown-toggle text-dark" id="tablePurOderStatus-' . $aRow['id'] . '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">';
                $delivery_status .= '<span data-toggle="tooltip" title="' . _l('ticket_single_change_status') . '"><i class="fa fa-caret-down" aria-hidden="true"></i></span>';
                $delivery_status .= '</a>';

                $delivery_status .= '<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="tablePurOderStatus-' . $aRow['id'] . '">';

                if($aRow['delivery_status'] == 0){
                    $delivery_status .= '<li>
                              <a href="#" onclick="change_delivery_status( 1 ,' . $aRow['id'] . '); return false;">
                                 ' ._l('completely_delivered') . '
                              </a>
                           </li>';
                    $delivery_status .= '<li>
                              <a href="#" onclick="change_delivery_status( 2 ,' . $aRow['id'] . '); return false;">
                                 ' ._l('pending_delivered') . '
                              </a>
                           </li>';
                    $delivery_status .= '<li>
                              <a href="#" onclick="change_delivery_status( 3 ,' . $aRow['id'] . '); return false;">
                                 ' ._l('partially_delivered') . '
                              </a>
                           </li>';
                }else if($aRow['delivery_status'] == 1){
                    $delivery_status .= '<li>
                              <a href="#" onclick="change_delivery_status( 0 ,' . $aRow['id'] . '); return false;">
                                 ' ._l('undelivered') . '
                              </a>
                           </li>';
                    $delivery_status .= '<li>
                              <a href="#" onclick="change_delivery_status( 2 ,' . $aRow['id'] . '); return false;">
                                 ' ._l('pending_delivered') . '
                              </a>
                           </li>';
                    $delivery_status .= '<li>
                              <a href="#" onclick="change_delivery_status( 3 ,' . $aRow['id'] . '); return false;">
                                 ' ._l('partially_delivered') . '
                              </a>
                           </li>';

                }else if($aRow['delivery_status'] == 2) {
                    $delivery_status .= '<li>
                              <a href="#" onclick="change_delivery_status( 0 ,' . $aRow['id'] . '); return false;">
                                 ' ._l('undelivered') . '
                              </a>
                           </li>';
                    $delivery_status .= '<li>
                              <a href="#" onclick="change_delivery_status( 1 ,' . $aRow['id'] . '); return false;">
                                 ' ._l('completely_delivered') . '
                              </a>
                           </li>';
                    $delivery_status .= '<li>
                              <a href="#" onclick="change_delivery_status( 3 ,' . $aRow['id'] . '); return false;">
                                 ' ._l('partially_delivered') . '
                              </a>
                           </li>';
                }else if($aRow['delivery_status'] == 3){
                    $delivery_status .= '<li>
                              <a href="#" onclick="change_delivery_status( 0 ,' . $aRow['id'] . '); return false;">
                                 ' ._l('undelivered') . '
                              </a>
                           </li>';
                    $delivery_status .= '<li>
                              <a href="#" onclick="change_delivery_status( 1 ,' . $aRow['id'] . '); return false;">
                                 ' ._l('completely_delivered') . '
                              </a>
                           </li>';
                    $delivery_status .= '<li>
                              <a href="#" onclick="change_delivery_status( 2 ,' . $aRow['id'] . '); return false;">
                                 ' ._l('pending_delivered') . '
                              </a>
                           </li>';
                }

                $delivery_status .= '</ul>';
                $delivery_status .= '</div>';
                
            }
            $delivery_status .= '</span>';
            $_data = $delivery_status;
        }elseif($aColumns[$i] == 'delivery_date'){
            $_data = _d($aRow['delivery_date']);
        }else if($aColumns[$i] == 'number'){
            $paid = $aRow['total'] - purorder_inv_left_to_pay($aRow['id']);

            $percent = 0;

            if($aRow['total'] > 0){

                $percent = ($paid / $aRow['total'] ) * 100;

            }

            

            $_data = '<div class="progress">

                          <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="' .round($percent).'"

                          aria-valuemin="0" aria-valuemax="100" style="width:'.round($percent).'%" data-percent="' .round($percent).'">

                           ' .round($percent).' % 

                          </div>

                        </div>';

        }else {
            if (strpos($aColumns[$i], 'date_picker_') !== false) {
                $_data = (strpos($_data, ' ') !== false ? _dt($_data) : _d($_data));
            }
        }

        $row[] = $_data;
    }

    // Add PR, GRN, Invoice columns only for main PO list (not vendor/project page)
    if(!isset($vendor) && !isset($project)){
        $insert_pos = count($row) - 5; // Before approval_status, delivery_date, delivery_status, payment_status, convert_expense
        if($insert_pos < 1) $insert_pos = count($row);        $pr_cell = '';
        if(isset($aRow['pur_request']) && is_numeric($aRow['pur_request']) && $aRow['pur_request'] > 0){
            $this->ci->db->select('pur_rq_code');
            $this->ci->db->where('id', $aRow['pur_request']);
            $pr_row = $this->ci->db->get(db_prefix().'pur_request')->row();
            if($pr_row){
                $pr_cell = '<a href="'.admin_url('purchase/view_pur_request/'.$aRow['pur_request']).'">'.$pr_row->pur_rq_code.'</a>';
            }
        }

        $grn_cell = '';
        $this->ci->db->select('id, goods_receipt_code');
        $this->ci->db->where('pr_order_id', $aRow['id']);
        $this->ci->db->where('approval', 1);
        $this->ci->db->where('(is_deleted = 0 OR is_deleted IS NULL)');
        $grn_rows = $this->ci->db->get(db_prefix().'goods_receipt')->result_array();
        $grn_links = [];
        foreach($grn_rows as $gr){
            $grn_links[] = '<a href="'.admin_url('warehouse/manage_purchase/'.$gr['id']).'">'.$gr['goods_receipt_code'].'</a>';
        }
        $grn_cell = implode(', ', $grn_links);

    // Every active invoice for this PO, each with its amount. A PO received over
    // several GRNs can carry several invoices, so they are all listed.
    // Currency resolved the same way as the rest of this table: app_format_money()
    // needs a currency name or object, not the raw id.
    $_po_cur = get_base_currency_pur();
    if(!empty($aRow['currency']) && $aRow['currency'] != 0){
        $_resolved = pur_get_currency_by_id($aRow['currency']);
        if($_resolved){ $_po_cur = $_resolved; }
    }
    $_po_cur_name = isset($_po_cur->name) ? $_po_cur->name : '';

    $inv_cell  = '';
    $inv_rows  = get_active_invoices_by_pur_order($aRow['id']);
    $inv_links = [];
    $inv_total = 0;
    foreach($inv_rows as $inv){
        $inv_total += (float)$inv['total'];
        $inv_links[] = '<a href="'.admin_url('purchase/purchase_invoice/'.$inv['id']).'">'.$inv['invoice_number'].'</a>'
            . ' <small>('.app_format_money($inv['total'], $_po_cur_name).')</small>';
    }
    $inv_cell = implode('<br>', $inv_links);
    if(count($inv_rows) > 1){
        $inv_cell .= '<br><strong>'.app_format_money($inv_total, $_po_cur_name).'</strong>';
    }

    array_splice($row, $insert_pos, 0, [$pr_cell, $grn_cell, $inv_cell]);
    } else {
        // Vendor/project page: add Qty, PR and GRN before status (last column)
        $qty_cell = '';
        $this->ci->db->select_sum('quantity');
        $this->ci->db->where('pur_order', $aRow['id']);
        $qty_row = $this->ci->db->get(db_prefix().'pur_order_detail')->row();
        if($qty_row && $qty_row->quantity > 0){
            $qty_cell = number_format((float)$qty_row->quantity, 2);
        }

        $pr_cell = '';
        if(isset($aRow['pur_request']) && is_numeric($aRow['pur_request']) && $aRow['pur_request'] > 0){
            $this->ci->db->select('pur_rq_code');
            $this->ci->db->where('id', $aRow['pur_request']);
            $pr_row = $this->ci->db->get(db_prefix().'pur_request')->row();
            if($pr_row){
                $pr_cell = '<a href="'.admin_url('purchase/view_pur_request/'.$aRow['pur_request']).'">'.$pr_row->pur_rq_code.'</a>';
            }
        }

        $grn_cell = '';
        $this->ci->db->select('id, goods_receipt_code');
        $this->ci->db->where('pr_order_id', $aRow['id']);
        $this->ci->db->where('(is_deleted = 0 OR is_deleted IS NULL)');
        $grn_rows = $this->ci->db->get(db_prefix().'goods_receipt')->result_array();
        $grn_links = [];
        foreach($grn_rows as $gr){
            $grn_links[] = '<a href="'.admin_url('warehouse/edit_purchase/'.$gr['id']).'">'.$gr['goods_receipt_code'].'</a>';
        }
        $grn_cell = implode(', ', $grn_links);

        // Insert Qty, PR and GRN before last column (status)
        $insert_pos = count($row) - 1;
        array_splice($row, $insert_pos, 0, [$qty_cell, $pr_cell, $grn_cell]);
    }

    $output['aaData'][] = $row;

}
