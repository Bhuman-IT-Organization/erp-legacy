<?php

defined('BASEPATH') or exit('No direct script access allowed');

$custom_fields = get_custom_fields('pur_invoice', [
    'show_on_table' => 1,
    ]);


$aColumns = [
    'invoice_number',
    'vendor_invoice_number',
    'grn_id',
    'transactionid',
    db_prefix().'pur_invoices.vendor',
    'contract',
    db_prefix().'pur_invoices.pur_order',
    'invoice_date',
    'is_recurring_from',
    'subtotal',
    'tax', 
    'total',
    'approval_status',
    'payment_request_status',
    'payment_status',
    'vendor_note'
    ];
$sIndexColumn = 'id';
$sTable       = db_prefix().'pur_invoices';
$join         = [ 'LEFT JOIN '.db_prefix().'pur_contracts ON '.db_prefix().'pur_contracts.id = '.db_prefix().'pur_invoices.contract' ];

$i = 0;
foreach ($custom_fields as $field) {
    $select_as = 'cvalue_' . $i;
    if ($field['type'] == 'date_picker' || $field['type'] == 'date_picker_time') {
        $select_as = 'date_picker_cvalue_' . $i;
    }
    array_push($aColumns, 'ctable_' . $i . '.value as ' . $select_as);
    array_push($join, 'LEFT JOIN '.db_prefix().'customfieldsvalues as ctable_' . $i . ' ON '.db_prefix().'pur_invoices.id = ctable_' . $i . '.relid AND ctable_' . $i . '.fieldto="' . $field['fieldto'] . '" AND ctable_' . $i . '.fieldid=' . $field['id']);
    $i++;
}


$where = [];


if ($this->ci->input->post('from_date')
    && $this->ci->input->post('from_date') != '') {
    array_push($where, 'AND invoice_date >= "'.to_sql_date($this->ci->input->post('from_date')).'"');
}

if(isset($vendor)){
    array_push($where, ' AND '.db_prefix().'pur_invoices.vendor = '.$vendor);
}


if ($this->ci->input->post('to_date')
    && $this->ci->input->post('to_date') != '') {
    array_push($where, 'AND invoice_date <= "'.to_sql_date($this->ci->input->post('to_date')).'"');
}

if(!has_permission('purchase_invoices', '', 'view')){
    array_push($where, 'AND (' . db_prefix() . 'pur_invoices.add_from = '.get_staff_user_id().' OR ' . db_prefix() . 'pur_invoices.vendor IN (SELECT vendor_id FROM ' . db_prefix() . 'pur_vendor_admin WHERE staff_id=' . get_staff_user_id() . ') OR '.get_staff_user_id().' IN (SELECT staffid FROM ' . db_prefix() . 'pur_approval_details WHERE ' . db_prefix() . 'pur_approval_details.rel_type = "purchase_invoice" AND ' . db_prefix() . 'pur_approval_details.rel_id = '.db_prefix().'pur_invoices.id))');
}

// Show all invoices including deleted (deleted ones show with "Deleted" badge)
// array_push($where, 'AND ('.db_prefix().'pur_invoices.is_deleted = 0 OR '.db_prefix().'pur_invoices.is_deleted IS NULL)');

$contract = $this->ci->input->post('contract');
if (isset($contract)) {
    $where_contract = '';
    foreach ($contract as $t) {
        if ($t != '') {
            if ($where_contract == '') {
                $where_contract .= ' AND ('.db_prefix().'pur_invoices.contract = "' . $t . '"';
            } else {
                $where_contract .= ' or '.db_prefix().'pur_invoices.contract = "' . $t . '"';
            }
        }
    }
    if ($where_contract != '') {
        $where_contract .= ')';
        array_push($where, $where_contract);
    }
}

$pur_orders = $this->ci->input->post('pur_orders');
if (isset($pur_orders)) {
    $where_pur_orders = '';
    foreach ($pur_orders as $t) {
        if ($t != '') {
            if ($where_pur_orders == '') {
                $where_pur_orders .= ' AND ('.db_prefix().'pur_invoices.pur_order = "' . $t . '"';
            } else {
                $where_pur_orders .= ' or '.db_prefix().'pur_invoices.pur_order = "' . $t . '"';
            }
        }
    }
    if ($where_pur_orders != '') {
        $where_pur_orders .= ')';
        array_push($where, $where_pur_orders);
    }
}

$vendors = $this->ci->input->post('vendors');
if (isset($vendors)) {
    $where_vendors = '';
    foreach ($vendors as $t) {
        if ($t != '') {
            if ($where_vendors == '') {
                $where_vendors .= ' AND ('.db_prefix().'pur_invoices.vendor = "' . $t . '"';
            } else {
                $where_vendors .= ' or '.db_prefix().'pur_invoices.vendor = "' . $t . '"';
            }
        }
    }
    if ($where_vendors != '') {
        $where_vendors .= ')';
        array_push($where, $where_vendors);
    }
}

if ($this->ci->input->post('currency')
    && count($this->ci->input->post('currency')) > 0) {
    array_push($where, 'AND '.db_prefix().'pur_invoices.currency IN (' . implode(',', $this->ci->input->post('currency')) . ')');
}

if ($this->ci->input->post('payment_status') && count($this->ci->input->post('payment_status')) > 0) {
    array_push($where, 'AND '.db_prefix() . 'pur_invoices.payment_status IN (' . implode(',', array_map(fn($v) => "\"$v\"", $this->ci->input->post('payment_status')) ) . ')');
}

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, [db_prefix().'pur_invoices.id as id','(SELECT GROUP_CONCAT(name SEPARATOR ",") FROM ' . db_prefix() . 'taggables JOIN ' . db_prefix() . 'tags ON ' . db_prefix() . 'taggables.tag_id = ' . db_prefix() . 'tags.id WHERE rel_id = ' . db_prefix() . 'pur_invoices.id and rel_type="pur_invoice" ORDER by tag_order ASC) as tags', 'contract_number', 'invoice_number', 'currency', 'is_deleted'
]);

$output  = $result['output'];
$rResult = $result['rResult'];

$this->ci->load->model('purchase/purchase_model');

foreach ($rResult as $aRow) {
    $row = [];

   for ($i = 0; $i < count($aColumns); $i++) {

        $base_currency = get_base_currency_pur();
        if($aRow['currency'] != 0){
            $base_currency = pur_get_currency_by_id($aRow['currency']);
        }

        if (strpos($aColumns[$i], 'as') !== false && !isset($aRow[$aColumns[$i]])) {
            $_data = $aRow[strafter($aColumns[$i], 'as ')];
        } else {
            $_data = $aRow[$aColumns[$i]];
        }
        if($aColumns[$i] == 'invoice_number'){
            $numberOutput = '';
            $is_inv_deleted = isset($aRow['is_deleted']) ? (int)$aRow['is_deleted'] : 0;
    
            $numberOutput = '<a href="' . admin_url('purchase/purchase_invoice/' . $aRow['id']) . '"  >'.$aRow['invoice_number']. '</a>';

            if($is_inv_deleted == 1){
                $numberOutput .= '<br><span class="label label-danger">'._l('deleted').'</span>';
            }

            $numberOutput .= '<div class="row-options">';

            if (has_permission('purchase_invoices', '', 'view') || has_permission('purchase_invoices', '', 'view_own')) {
                $numberOutput .= ' <a href="' . admin_url('purchase/purchase_invoice/' . $aRow['id']) . '" >' . _l('view') . '</a>';
            }

            $list_approve_status = $this->ci->purchase_model->get_list_approval_details($aRow['id'],'purchase_invoice');

            if($is_inv_deleted == 0){
                if ((has_permission('purchase_invoices', '', 'edit') || is_admin()) && $aRow['approval_status'] != 2 && count($list_approve_status) == 0) {
                    $numberOutput .= ' | <a href="' . admin_url('purchase/pur_invoice/' . $aRow['id']) . '">' . _l('edit') . '</a>';
                }
                if (has_permission('purchase_invoices', '', 'delete') || is_admin()) {
                    $numberOutput .= ' | <a href="#" onclick="confirm_delete_pur_invoice(' . $aRow['id'] . '); return false;" class="text-danger">' . _l('delete') . '</a>';
                }
            }
            $numberOutput .= '</div>';

            $_data = $numberOutput;
        }else if($aColumns[$i] == 'vendor_invoice_number'){
            if($aRow['vendor_invoice_number'] != ''){
                $_data = $aRow['vendor_invoice_number'];
            }else{
                $_data = $aRow['invoice_number'];
            }
        }else if($aColumns[$i] == 'grn_id'){
            $_data = '';
            if(isset($aRow['grn_id']) && $aRow['grn_id'] != '' && $aRow['grn_id'] != null){
                $grn_ids = json_decode($aRow['grn_id'], true);
                if(!is_array($grn_ids)) $grn_ids = explode(',', $aRow['grn_id']);
                $grn_links = [];
                foreach($grn_ids as $gid){
                    $gid = trim($gid);
                    if(is_numeric($gid)){
                        $this->ci->db->select('goods_receipt_code');
                        $this->ci->db->where('id', $gid);
                        $grn_row = $this->ci->db->get(db_prefix().'goods_receipt')->row();
                        if($grn_row){
                            $grn_links[] = '<a href="'.admin_url('warehouse/manage_purchase/'.$gid).'">'.$grn_row->goods_receipt_code.'</a>';
                        }
                    }
                }
                $_data = implode(', ', $grn_links);
            }
        }elseif($aColumns[$i] == 'transactionid'){
            // Show transaction IDs from payment records if invoice transactionid is empty
            $_data = $aRow['transactionid'];
            if(empty($_data) || $_data == ''){
                $this->ci->db->select('transactionid');
                $this->ci->db->where('pur_invoice', $aRow['id']);
                $this->ci->db->where('is_deleted', 0);
                $this->ci->db->where('transactionid IS NOT NULL AND transactionid != ""', null, false);
                $payments = $this->ci->db->get(db_prefix().'pur_invoice_payment')->result_array();
                $txn_ids = [];
                foreach($payments as $p){
                    if(!empty($p['transactionid'])){
                        $txn_ids[] = $p['transactionid'];
                    }
                }
                $_data = implode(', ', $txn_ids);
            }
        }elseif($aColumns[$i] == 'vendor_note'){
            $_data = render_tags($aRow['tags']);
        }elseif($aColumns[$i] == 'invoice_date'){
            $_data = _d($aRow['invoice_date']);
        }elseif($aColumns[$i] == 'subtotal'){
            $_data = e(app_format_money($aRow['subtotal'],$base_currency->name));
        }elseif($aColumns[$i] == 'tax'){
            $tax = $this->ci->purchase_model->get_html_tax_pur_invoice($aRow['id']);
            $total_tax = 0;
            foreach($tax['taxes_val'] as $tax_val){
                $total_tax += $tax_val;
            }

            $_data = e(app_format_money($total_tax,$base_currency->name));
        }elseif($aColumns[$i] == 'total'){
            $_data = e(app_format_money($aRow['total'],$base_currency->name));
        }elseif($aColumns[$i] == 'payment_status'){
            $class = '';
            if($aRow['payment_status'] == 'unpaid'){
                $class = 'danger';
            }elseif($aRow['payment_status'] == 'paid'){
                $class = 'success';
            }elseif ($aRow['payment_status'] == 'partially_paid') {
                $class = 'warning';
            }

            $_data = '<span class="label label-'.$class.' s-status invoice-status-3">'._l($aRow['payment_status']).'</span>';
        }elseif($aColumns[$i] == 'contract'){
            $_data = '<a href="'.admin_url('purchase/contract/'.$aRow['contract']).'">'.$aRow['contract_number'].'</a>';
        }elseif($aColumns[$i] == 'approval_status'){
            $_data = get_status_approve($aRow['approval_status']);
        }elseif($aColumns[$i] == 'payment_request_status'){
            $_data = get_payment_request_status_by_inv($aRow['id']);
        }elseif($aColumns[$i] == db_prefix().'pur_invoices.pur_order'){
            // Show ALL POs linked via GRNs (not just the stored single pur_order)
            $_all_po_links_grid = [];
            $_all_po_ids_grid = [];
            $main_po = $aRow[db_prefix().'pur_invoices.pur_order'];
            if(is_numeric($main_po) && $main_po > 0){
                $_all_po_ids_grid[] = (int)$main_po;
            }
            // Get additional POs from GRNs
            if(isset($aRow['grn_id']) && !empty($aRow['grn_id'])){
                $_g_arr = json_decode($aRow['grn_id'], true);
                if(!is_array($_g_arr)) $_g_arr = explode(',', $aRow['grn_id']);
                foreach($_g_arr as $_g){
                    $_g = (int)trim($_g);
                    if($_g > 0){
                        $this->ci->db->select('pr_order_id');
                        $this->ci->db->where('id', $_g);
                        $_gr = $this->ci->db->get(db_prefix().'goods_receipt')->row();
                        if($_gr && $_gr->pr_order_id > 0 && !in_array((int)$_gr->pr_order_id, $_all_po_ids_grid)){
                            $_all_po_ids_grid[] = (int)$_gr->pr_order_id;
                        }
                    }
                }
            }
            foreach($_all_po_ids_grid as $_pid){
                $_all_po_links_grid[] = '<a href="'.admin_url('purchase/purchase_order/'.$_pid).'">'.get_pur_order_subject($_pid).'</a>';
            }
            $_data = implode(', ', $_all_po_links_grid);
        }elseif($aColumns[$i] == 'is_recurring_from'){
            $_data = ' <a href="' . admin_url('purchase/purchase_invoice/' . $aRow['is_recurring_from']) . '" >' . get_pur_invoice_number($aRow['is_recurring_from']) . '</a>'; 
        }elseif($aColumns[$i] == db_prefix().'pur_invoices.vendor'){
            $_data = '<a href="' . admin_url('purchase/vendor/' . $aRow[db_prefix().'pur_invoices.vendor']) . '" >' .  get_vendor_company_name($aRow[db_prefix().'pur_invoices.vendor']) . '</a>'; 
        }else {
            if (strpos($aColumns[$i], 'date_picker_') !== false) {
                $_data = (strpos($_data, ' ') !== false ? _dt($_data) : _d($_data));
            }
        }

        $row[] = $_data;
    }

    // Add PR number after PO column - show ALL PRs from all linked POs
    $pr_cell = '';
    $_all_pr_links_grid = [];
    // Reuse $_all_po_ids_grid if available, otherwise rebuild
    $_pr_po_ids = [];
    $po_id_val = $aRow[db_prefix().'pur_invoices.pur_order'];
    if(is_numeric($po_id_val) && $po_id_val > 0){
        $_pr_po_ids[] = (int)$po_id_val;
    }
    if(isset($aRow['grn_id']) && !empty($aRow['grn_id'])){
        $_g_arr2 = json_decode($aRow['grn_id'], true);
        if(!is_array($_g_arr2)) $_g_arr2 = explode(',', $aRow['grn_id']);
        foreach($_g_arr2 as $_g2){
            $_g2 = (int)trim($_g2);
            if($_g2 > 0){
                $this->ci->db->select('pr_order_id');
                $this->ci->db->where('id', $_g2);
                $_gr2 = $this->ci->db->get(db_prefix().'goods_receipt')->row();
                if($_gr2 && $_gr2->pr_order_id > 0 && !in_array((int)$_gr2->pr_order_id, $_pr_po_ids)){
                    $_pr_po_ids[] = (int)$_gr2->pr_order_id;
                }
            }
        }
    }
    foreach($_pr_po_ids as $_pid2){
        $this->ci->db->select('pur_request');
        $this->ci->db->where('id', $_pid2);
        $_po_r = $this->ci->db->get(db_prefix().'pur_orders')->row();
        if($_po_r && is_numeric($_po_r->pur_request) && $_po_r->pur_request > 0){
            $this->ci->db->select('pur_rq_code');
            $this->ci->db->where('id', $_po_r->pur_request);
            $_pr_r = $this->ci->db->get(db_prefix().'pur_request')->row();
            if($_pr_r){
                $_link = '<a href="'.admin_url('purchase/view_pur_request/'.$_po_r->pur_request).'">'.$_pr_r->pur_rq_code.'</a>';
                if(!in_array($_link, $_all_pr_links_grid)) $_all_pr_links_grid[] = $_link;
            }
        }
    }
    $pr_cell = implode(', ', $_all_pr_links_grid);
    array_splice($row, 7, 0, [$pr_cell]);

    // Add Debit Note info before tag column (last column before vendor_note/tag)
    $debit_cell = '';
    $this->ci->db->select(db_prefix().'pur_debits.amount, '.db_prefix().'pur_debits.debit_id, '.db_prefix().'pur_debit_notes.prefix, '.db_prefix().'pur_debit_notes.number');
    $this->ci->db->from(db_prefix().'pur_debits');
    $this->ci->db->join(db_prefix().'pur_debit_notes', db_prefix().'pur_debit_notes.id = '.db_prefix().'pur_debits.debit_id');
    $this->ci->db->where(db_prefix().'pur_debits.invoice_id', $aRow['id']);
    $applied_dn = $this->ci->db->get()->result();
    if(count($applied_dn) > 0){
        $dn_parts = [];
        foreach($applied_dn as $adn){
            $dn_num = $adn->prefix . str_pad($adn->number, 5, '0', STR_PAD_LEFT);
            $dn_parts[] = '<a href="'.admin_url('purchase/debit_notes/'.$adn->debit_id).'">'.$dn_num.'</a> ('.app_format_money($adn->amount, $base_currency->name).')';
        }
        $debit_cell = implode('<br>', $dn_parts);
    }
    // Insert debit note before the last column (tag)
    array_splice($row, count($row) - 1, 0, [$debit_cell]);

    $output['aaData'][] = $row;

}
