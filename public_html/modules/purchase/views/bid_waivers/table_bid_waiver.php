<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Server side datatable for the Bid Waiver list.
 *
 * Column order here must match the header order in manage.php, and every entry must
 * be a real column - a header without a matching data column makes DataTables throw
 * "Requested unknown parameter".
 */
$aColumns = [
    db_prefix() . 'pur_bid_waivers.request_no',
    db_prefix() . 'departments.name',
    db_prefix() . 'pur_bid_waivers.requestor',
    db_prefix() . 'pur_bid_waivers.purchase_type',
    db_prefix() . 'pur_vendor.company',
    db_prefix() . 'pur_bid_waivers.total_amount',
    db_prefix() . 'pur_bid_waivers.two_or_more_quotations',
    db_prefix() . 'pur_bid_waivers.created_at',
    db_prefix() . 'pur_bid_waivers.approve_status',
    // Backs the Options column. DataTables needs one entry per header, so this has to
    // be a real column even though the cell content is replaced below - a header with
    // no matching column throws "Requested unknown parameter".
    db_prefix() . 'pur_bid_waivers.created_by',
];

$join = [
    'LEFT JOIN ' . db_prefix() . 'departments ON ' . db_prefix() . 'departments.departmentid = ' . db_prefix() . 'pur_bid_waivers.department',
    'LEFT JOIN ' . db_prefix() . 'pur_vendor ON ' . db_prefix() . 'pur_vendor.userid = ' . db_prefix() . 'pur_bid_waivers.recommended_vendor',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'pur_bid_waivers';

$where = [];

// Without the global view capability a user only sees forms they raised or are named
// as requestor on
if (!has_permission('purchase_bid_waiver', '', 'view')) {
    $staff_id = get_staff_user_id();
    array_push(
        $where,
        'AND (' . db_prefix() . 'pur_bid_waivers.created_by = ' . $staff_id
        . ' OR ' . db_prefix() . 'pur_bid_waivers.requestor = ' . $staff_id . ')'
    );
}

if ($this->ci->input->post('from_date') && $this->ci->input->post('from_date') != '') {
    array_push($where, 'AND date(' . db_prefix() . 'pur_bid_waivers.created_at) >= "' . to_sql_date($this->ci->input->post('from_date')) . '"');
}

if ($this->ci->input->post('to_date') && $this->ci->input->post('to_date') != '') {
    array_push($where, 'AND date(' . db_prefix() . 'pur_bid_waivers.created_at) <= "' . to_sql_date($this->ci->input->post('to_date')) . '"');
}

// Cast to int before building the IN list: these arrive straight from the browser
if ($this->ci->input->post('department') && count($this->ci->input->post('department')) > 0) {
    $departments = array_map('intval', $this->ci->input->post('department'));
    array_push($where, 'AND ' . db_prefix() . 'pur_bid_waivers.department IN (' . implode(',', $departments) . ')');
}

if ($this->ci->input->post('status') && count($this->ci->input->post('status')) > 0) {
    $statuses = array_map('intval', $this->ci->input->post('status'));
    array_push($where, 'AND ' . db_prefix() . 'pur_bid_waivers.approve_status IN (' . implode(',', $statuses) . ')');
}

$result = data_tables_init(
    $aColumns,
    $sIndexColumn,
    $sTable,
    $join,
    $where,
    [db_prefix() . 'pur_bid_waivers.id as id', db_prefix() . 'pur_bid_waivers.currency']
);

$output  = $result['output'];
$rResult = $result['rResult'];

$purchase_types = pur_bid_waiver_purchase_types();
$base_currency  = get_base_currency_pur();

foreach ($rResult as $aRow) {
    // data_tables_init() does not select a qualified column under its bare name. Any
    // entry in $aColumns of the form table.column is passed through
    // prefixed_table_fields_wildcard() (application/helpers/database_helper.php), which
    // emits
    //
    //     `tblpur_bid_waivers`.`request_no` AS `tblpur_bid_waivers.request_no`
    //
    // so the row arrives keyed 'tblpur_bid_waivers.request_no'. Reading $aRow['request_no']
    // returned null for every column, which is why this list rendered a row per form with
    // every detail blank - only the id and currency worked, because those come through
    // $additionalSelect, which is imploded raw and never aliased.
    //
    // The qualified names are kept in $aColumns on purpose: they are what make ordering
    // and searching unambiguous across the two joined tables. So the keys are flattened
    // here instead. A bare key already present is never overwritten, so 'id' from
    // $additionalSelect keeps its value.
    $r = $aRow;

    foreach ($aRow as $key => $value) {
        if (strpos($key, '.') === false) {
            continue;
        }

        $bare = substr($key, strrpos($key, '.') + 1);

        if (!array_key_exists($bare, $r)) {
            $r[$bare] = $value;
        }
    }

    $row = [];

    $row[] = '<a href="' . admin_url('purchase/bid_waiver_detail/' . $r['id']) . '">'
             . e($r['request_no']) . '</a>';

    $row[] = e($r['name']);

    $row[] = $r['requestor'] ? '<a href="' . admin_url('staff/profile/' . $r['requestor']) . '">'
             . e(get_staff_full_name($r['requestor'])) . '</a>' : '';

    $row[] = isset($purchase_types[$r['purchase_type']]) ? $purchase_types[$r['purchase_type']] : '';

    $row[] = e($r['company']);

    // app_format_money needs a currency object or name, never a bare id
    $row_currency = $base_currency;
    if (!empty($r['currency']) && $r['currency'] != 0) {
        $found = pur_get_currency_by_id($r['currency']);
        if ($found) {
            $row_currency = $found;
        }
    }
    $row[] = app_format_money($r['total_amount'], $row_currency);

    // NULL means the Yes/No question has not been answered yet
    if ($r['two_or_more_quotations'] === null || $r['two_or_more_quotations'] === '') {
        $row[] = '';
    } elseif ((int) $r['two_or_more_quotations'] === 1) {
        $row[] = '<span class="label label-info">' . _l('pur_bw_waiver_yes') . '</span>';
    } else {
        $row[] = '<span class="label label-default">' . _l('pur_bw_waiver_no') . '</span>';
    }

    $row[] = _dt($r['created_at']);

    $row[] = pur_bid_waiver_status_label($r['approve_status']);

    $options = '<a href="' . admin_url('purchase/bid_waiver_detail/' . $r['id']) . '" class="btn btn-icon btn-default"><i class="fa fa-eye"></i></a>';

    // Editing is closed once an approval has been recorded, mirroring the controller guard
    if (has_permission('purchase_bid_waiver', '', 'edit')
        && !$this->ci->purchase_model->bid_waiver_has_approval_action($r['id'])) {
        $options .= '<a href="' . admin_url('purchase/bid_waiver/' . $r['id']) . '" class="btn btn-icon btn-warning"><i class="fa fa-pencil"></i></a>';
    }

    if (has_permission('purchase_bid_waiver', '', 'delete')) {
        $options .= '<a href="' . admin_url('purchase/delete_bid_waiver/' . $r['id']) . '" class="btn btn-icon btn-danger _delete"><i class="fa fa-remove"></i></a>';
    }

    $row[] = $options;

    $output['aaData'][] = $row;
}

echo json_encode($output);
die();
