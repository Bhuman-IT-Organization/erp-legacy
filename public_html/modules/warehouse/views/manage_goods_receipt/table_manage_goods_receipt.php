<?php

defined('BASEPATH') or exit('No direct script access allowed');
$get_base_currency =  get_base_currency();
if($get_base_currency){
    $base_currency = $get_base_currency->id;
}else{
    $base_currency = 0;
}

// Visible column order → SQL column used for ordering / global search.
// The PR, PO and Invoice cells are all derived from pr_order_id (or resolved in
// PHP), but they still occupy their own visible column. DataTables sends the
// *visible* column index when ordering, so this array must have one entry per
// rendered column, otherwise ORDER BY receives an undefined key.
$aColumns = [
    'id',                 // 0
    'goods_receipt_code', // 1
    'supplier_name',      // 2
    'buyer_id',           // 3  Created By
    'pr_order_id',        // 4  Purchase Request  (derived)
    'pr_order_id',        // 5  Purchase Order    (derived)
    'pr_order_id',        // 6  Invoice Number    (derived)
    'date_add',           // 7
    'approval',           // 8
];

// Columns actually iterated when building a row. The pr_order_id entry emits
// three cells (PR, PO, Invoice) so the totals still line up with $aColumns.
$renderColumns = [
    'id',
    'goods_receipt_code',
    'supplier_name',
    'buyer_id',
    'pr_order_id',
    'date_add',
    'approval',
];

$sIndexColumn = 'id';
$sTable       = db_prefix().'goods_receipt';
$join         = [ ];
$where = [];

if($this->ci->input->post('day_vouchers')){
    $day_vouchers = to_sql_date($this->ci->input->post('day_vouchers'));
}

if (isset($day_vouchers)) {

    $where[] = 'AND tblgoods_receipt.date_add <= "' . $day_vouchers . '"';
    
}
if (!has_permission('wh_stock_import', '', 'view')) {
    array_push($where, 'AND (' . db_prefix() . 'goods_receipt.addedfrom=' . get_staff_user_id().' OR ' . db_prefix() . 'goods_receipt.buyer_id=' . get_staff_user_id() .')');
}

if($this->ci->input->post('_project_id')){
    $where[] = 'AND '.db_prefix().'goods_receipt.project = '.$this->ci->input->post('_project_id');
}

// ── Status (approval) filter ──────────────────────────────────────────────
// Accepts one or more of: 1 = approved, 0 = not yet approved, -1 = rejected
$approval_filter = $this->ci->input->post('approval_filter');

if($approval_filter !== null && $approval_filter !== '' && $approval_filter !== 'all'){
    $allowed  = ['1', '0', '-1'];
    $selected = is_array($approval_filter) ? $approval_filter : [$approval_filter];

    // Whitelist the values — they are interpolated into the WHERE clause
    $selected = array_values(array_intersect(array_map('strval', $selected), $allowed));

    if(!empty($selected)){
        $where[] = 'AND ' . db_prefix() . 'goods_receipt.approval IN (' . implode(',', array_map('intval', $selected)) . ')';
    }
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, ['id','date_add','date_c','goods_receipt_code', 'supplier_code', 'currency', 'is_deleted', 'addedfrom']);

$output  = $result['output'];
$rResult = $result['rResult'];

// ── GRN → purchase invoice lookup ─────────────────────────────────────────
// pur_invoices.grn_id holds either a JSON array or a comma separated list of
// GRN ids, so it cannot be joined in SQL. Build the map once here rather than
// querying per row.
$grn_invoice_map = [];

// Only the GRNs on the current page matter
$page_grn_ids = [];
foreach($rResult as $_r){
    $page_grn_ids[(string) $_r['id']] = true;
}

if(!empty($page_grn_ids)){
    $CI_inv = &get_instance();
    $CI_inv->db->select('id, invoice_number, grn_id, is_deleted');
    $CI_inv->db->where('grn_id IS NOT NULL AND grn_id != ""', null, false);
    $inv_rows = $CI_inv->db->get(db_prefix() . 'pur_invoices')->result_array();

    foreach($inv_rows as $inv){
        $gids = json_decode($inv['grn_id'], true);
        if(!is_array($gids)){
            $gids = array_map('trim', explode(',', $inv['grn_id']));
        }

        foreach($gids as $gid){
            $gid = (string) trim($gid);
            if($gid === '' || !is_numeric($gid) || !isset($page_grn_ids[$gid])){
                continue;
            }
            $grn_invoice_map[$gid][] = [
                'id'         => $inv['id'],
                'number'     => $inv['invoice_number'],
                'is_deleted' => (int) $inv['is_deleted'],
            ];
        }
    }
}

foreach ($rResult as $aRow) {
    $currency = $base_currency;
    $row = [];

    if(is_numeric($aRow['currency']) && $aRow['currency'] != 0){
        $currency = $aRow['currency'];
    }

    for ($i = 0; $i < count($renderColumns); $i++) {

        $_data = $aRow[$renderColumns[$i]];
        if($renderColumns[$i] == 'supplier_name'){

            if (get_status_modules_wh('purchase') && ($aRow['supplier_code'] != '') && ($aRow['supplier_code'] != 0) ){
                $_data = wh_get_vendor_company_name($aRow['supplier_code']);
            }else{
                $_data = $aRow['supplier_name'];
            }

        }elseif($renderColumns[$i] == 'buyer_id'){
            // Show the creator (addedfrom) name
            $staff_id = (isset($aRow['addedfrom']) && $aRow['addedfrom'] > 0) ? $aRow['addedfrom'] : 0;
            if($staff_id > 0){
                $CI = &get_instance();
                $CI->db->select('firstname, lastname');
                $CI->db->where('staffid', $staff_id);
                $staff_row = $CI->db->get(db_prefix() . 'staff')->row();
                $staff_name = $staff_row ? trim($staff_row->firstname . ' ' . $staff_row->lastname) : '';
                $_data = '<a href="' . admin_url('staff/profile/' . $staff_id) . '">' . staff_profile_image($staff_id, [
                    'staff-profile-image-small',
                ]) . '</a>';
                $_data .= ' <a href="' . admin_url('staff/profile/' . $staff_id) . '">' . $staff_name . '</a>';
            } else {
                $_data = '';
            }
        }elseif($renderColumns[$i] == 'date_add'){
            $_data = _d($aRow['date_add']);
        }elseif ($renderColumns[$i] == 'total_tax_money') {
            $_data = app_format_money((float)$aRow['total_tax_money'], $currency);
        }elseif($renderColumns[$i] == 'goods_receipt_code'){
            $is_deleted = isset($aRow['is_deleted']) ? (int)$aRow['is_deleted'] : 0;

            if($this->ci->input->post('_project_id')){
                $name = '<a href="' . admin_url('warehouse/manage_purchase/' . $aRow['id'] ).'" target="_blank">' . $aRow['goods_receipt_code'] . '</a>';
            }else{
                $name = '<a href="' . admin_url('warehouse/view_purchase/' . $aRow['id'] ).'" onclick="init_goods_receipt('.$aRow['id'].'); return false;">' . $aRow['goods_receipt_code'] . '</a>';
            }

            if($is_deleted == 1){
                $name .= '<br><span class="label label-danger">'._l('returned').'</span>';
            }

            $name .= '<div class="row-options">';
            if($this->ci->input->post('_project_id')){
                $name .= '<a href="' . admin_url('warehouse/manage_purchase/' . $aRow['id'] ).'" target="_blank">' . _l('view') . '</a>';
            }else{
                $name .= '<a href="' . admin_url('warehouse/edit_purchase/' . $aRow['id'] ).'" >' . _l('view') . '</a>';
            }

            // Only show edit/delete for active (non-deleted) GRNs
            if($is_deleted == 0){
                if((has_permission('wh_stock_import', '', 'edit') || is_admin()) && ($aRow['approval'] == 0)){
                    $name .= ' | <a href="' . admin_url('warehouse/manage_goods_receipt/' . $aRow['id'] ).'" >' . _l('edit') . '</a>';
                }

                if (has_permission('wh_stock_import', '', 'delete') || is_admin()) {
                    $name .= ' | <a href="#" onclick="confirm_delete_grn(' . $aRow['id'] . '); return false;" class="text-danger">' . _l('delete') . '</a>';
                }
            }
            
            $name .= '</div>';

            $_data = $name;
        }elseif($renderColumns[$i] == 'approval') {
           
           if($aRow['approval'] == 1){
            $_data = '<span class="label label-tag tag-id-1 label-tab1"><span class="tag">'._l('approved').'</span><span class="hide">, </span></span>&nbsp';
        }elseif($aRow['approval'] == 0){
            $_data = '<span class="label label-tag tag-id-1 label-tab2"><span class="tag">'._l('not_yet_approve').'</span><span class="hide">, </span></span>&nbsp';
        }elseif($aRow['approval'] == -1){
            $_data = '<span class="label label-tag tag-id-1 label-tab3"><span class="tag">'._l('reject').'</span><span class="hide">, </span></span>&nbsp';
        }
    }elseif($renderColumns[$i] == 'pr_order_id'){
        // First output PR Number column
        $pr_cell = '';
        if(($aRow['pr_order_id'] != '') && ($aRow['pr_order_id'] != 0)){
            $CI = &get_instance();
            $CI->db->select('pur_request');
            $CI->db->where('id', $aRow['pr_order_id']);
            $po_row = $CI->db->get(db_prefix().'pur_orders')->row();
            if($po_row && is_numeric($po_row->pur_request) && $po_row->pur_request > 0){
                $CI->db->select('pur_rq_code');
                $CI->db->where('id', $po_row->pur_request);
                $pr_row = $CI->db->get(db_prefix().'pur_request')->row();
                if($pr_row){
                    $pr_cell = '<a href="'.admin_url('purchase/view_pur_request/'.$po_row->pur_request).'">'.$pr_row->pur_rq_code.'</a>';
                }
            }
        }
        $row[] = $pr_cell;

        // Then output PO Number column
        $get_pur_order_name ='';
        if (get_status_modules_wh('purchase')) {
            if( ($aRow['pr_order_id'] != '') && ($aRow['pr_order_id'] != 0) ){
                $get_pur_order_name .='<a href="'. admin_url('purchase/purchase_order/'.$aRow['pr_order_id']) .'" >'. get_pur_order_name($aRow['pr_order_id']) .'</a>';
            }
        }
        $row[] = $get_pur_order_name;

        // Finally the Invoice Number column — blank when no invoice was generated
        $invoice_cell = '';
        $grn_key      = (string) $aRow['id'];

        if(isset($grn_invoice_map[$grn_key])){
            $invoice_links = [];

            foreach($grn_invoice_map[$grn_key] as $inv){
                $label = $inv['number'] != '' ? $inv['number'] : ('#' . $inv['id']);
                $link  = '<a href="' . admin_url('purchase/purchase_invoice/' . $inv['id']) . '">' . $label . '</a>';

                if($inv['is_deleted'] == 1){
                    $link .= ' <span class="label label-danger">' . _l('reversed') . '</span>';
                }

                $invoice_links[] = $link;
            }

            $invoice_cell = implode('<br>', $invoice_links);
        }

        $_data = $invoice_cell;

    }
    


    $row[] = $_data;
}
$output['aaData'][] = $row;

}
