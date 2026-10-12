<?php

defined('BASEPATH') or exit('No direct script access allowed');

// 10 sortable/searchable columns. The Options column has no entry here, so it is
// index 10 and must be excluded from sorting and searching in shipping_charges.php.
// Header count (11) must equal this count + 1, and must equal the number of
// $row[] pushes below, or DataTables throws "Requested unknown parameter".
$aColumns = [
    'shipping_number',
    'shipping_invoice_number',
    'shipping_invoice_date',
    'invoice_id',
    'cost_center',
    'vendor',
    'amount',
    'tax_percent',
    'total',
    'date_created',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'inv_shipping_charges';
$join         = [];
$where        = [];

$result = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, ['id', 'shipping_number', 'shipping_invoice_number', 'shipping_invoice_date', 'invoice_id', 'cost_center', 'vendor', 'amount', 'tax_percent', 'total', 'date_created']);

$output  = $result['output'];
$rResult = $result['rResult'];

foreach ($rResult as $aRow) {
    $row = [];

    // Shipping Number (clickable)
    $sc_number = !empty($aRow['shipping_number']) ? $aRow['shipping_number'] : 'ISC' . str_pad($aRow['id'], 5, '0', STR_PAD_LEFT);
    $row[] = '<a href="' . admin_url('purchase/view_shipping_charge/' . $aRow['id']) . '">' . $sc_number . '</a>';

    // The freight vendor's own invoice number, blank on charges raised before the
    // column existed
    $row[] = !empty($aRow['shipping_invoice_number']) ? e($aRow['shipping_invoice_number']) : '';

    // The date on the freight bill, which is also the date the ledger entries carry
    $row[] = (!empty($aRow['shipping_invoice_date']) && $aRow['shipping_invoice_date'] != '0000-00-00')
        ? _d($aRow['shipping_invoice_date'])
        : '';

    // Invoice Number
    $inv_number = '';
    if($aRow['invoice_id'] > 0){
        $CI =& get_instance();
        $CI->db->select('invoice_number');
        $CI->db->where('id', $aRow['invoice_id']);
        $inv_row = $CI->db->get(db_prefix() . 'pur_invoices')->row();
        if($inv_row) $inv_number = $inv_row->invoice_number;
    }
    $row[] = '<a href="' . admin_url('purchase/purchase_invoice/' . $aRow['invoice_id']) . '">' . $inv_number . '</a>';

    // Cost Centre
    $cost_center_name = '';
    if(!empty($aRow['cost_center'])){
        $CI =& get_instance();
        $CI->db->select('name');
        $CI->db->where('id', $aRow['cost_center']);
        $cc_row = $CI->db->get(db_prefix() . 'cost_centers')->row();
        if($cc_row) $cost_center_name = $cc_row->name;
    }
    $row[] = e($cost_center_name);

    // Vendor
    $vendor_name = '';
    if($aRow['vendor'] > 0){
        $CI =& get_instance();
        $CI->db->select('company');
        $CI->db->where('userid', $aRow['vendor']);
        $v = $CI->db->get(db_prefix() . 'pur_vendor')->row();
        if($v) $vendor_name = $v->company;
    }
    $row[] = $vendor_name;

    // Amount
    $row[] = app_format_money((float)$aRow['amount'], '');

    // Tax %
    $row[] = $aRow['tax_percent'] . '%';

    // Total
    $row[] = app_format_money((float)$aRow['total'], '');

    // Date
    $row[] = _d($aRow['date_created']);

    // Options
    $options = '<a href="' . admin_url('purchase/view_shipping_charge/' . $aRow['id']) . '" class="btn btn-default btn-icon" data-toggle="tooltip" title="' . _l('view') . '"><i class="fa fa-eye"></i></a> ';
    if(has_permission('purchase_invoices', '', 'edit') || is_admin()){
        $options .= '<a href="' . admin_url('purchase/shipping_charge_form/' . $aRow['id']) . '" class="btn btn-default btn-icon" data-toggle="tooltip" title="' . _l('edit') . '"><i class="fa fa-pencil-square-o"></i></a> ';
        $options .= '<a href="' . admin_url('purchase/reverse_shipping_charge/' . $aRow['id']) . '" class="btn btn-danger btn-icon _delete" data-toggle="tooltip" title="Reverse"><i class="fa fa-undo"></i></a>';
    }
    $row[] = $options;

    $output['aaData'][] = $row;
}
