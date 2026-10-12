<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Server side datatable for the Petty Cash Advance list.
 *
 * There must be exactly one $aColumns entry per header in manage.php. A header with
 * no backing column makes DataTables throw "Requested unknown parameter", so the
 * Options column is backed by a real column whose cell content is replaced below.
 */
$aColumns = [
    db_prefix() . 'pur_petty_cash_advances.request_no',
    db_prefix() . 'pur_petty_cash_advances.employee',
    db_prefix() . 'departments.name',
    // The FK rather than expenses_categories.name: that column would come back under
    // the same "name" key as departments.name and silently overwrite it. The readable
    // name is pulled in via the aliased additional select below.
    db_prefix() . 'pur_petty_cash_advances.expense_category',
    db_prefix() . 'pur_petty_cash_advances.amount_requested',
    db_prefix() . 'pur_petty_cash_advances.payment_priority',
    db_prefix() . 'pur_petty_cash_advances.required_date',
    db_prefix() . 'pur_petty_cash_advances.created_at',
    db_prefix() . 'pur_petty_cash_advances.approve_status',
    // Backs the Options column
    db_prefix() . 'pur_petty_cash_advances.created_by',
];

$join = [
    'LEFT JOIN ' . db_prefix() . 'departments ON ' . db_prefix() . 'departments.departmentid = ' . db_prefix() . 'pur_petty_cash_advances.department',
    'LEFT JOIN ' . db_prefix() . 'expenses_categories ON ' . db_prefix() . 'expenses_categories.id = ' . db_prefix() . 'pur_petty_cash_advances.expense_category',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'pur_petty_cash_advances';

$where = [];

// Without the global view capability a user only sees requests they raised or are
// named as the employee on
if (!has_permission('purchase_petty_cash', '', 'view')) {
    $staff_id = get_staff_user_id();
    array_push(
        $where,
        'AND (' . db_prefix() . 'pur_petty_cash_advances.created_by = ' . $staff_id
        . ' OR ' . db_prefix() . 'pur_petty_cash_advances.employee = ' . $staff_id . ')'
    );
}

if ($this->ci->input->post('from_date') && $this->ci->input->post('from_date') != '') {
    array_push($where, 'AND date(' . db_prefix() . 'pur_petty_cash_advances.created_at) >= "' . to_sql_date($this->ci->input->post('from_date')) . '"');
}

if ($this->ci->input->post('to_date') && $this->ci->input->post('to_date') != '') {
    array_push($where, 'AND date(' . db_prefix() . 'pur_petty_cash_advances.created_at) <= "' . to_sql_date($this->ci->input->post('to_date')) . '"');
}

// Cast to int before building the IN list - these come straight from the browser
if ($this->ci->input->post('department') && count($this->ci->input->post('department')) > 0) {
    $departments = array_map('intval', $this->ci->input->post('department'));
    array_push($where, 'AND ' . db_prefix() . 'pur_petty_cash_advances.department IN (' . implode(',', $departments) . ')');
}

if ($this->ci->input->post('status') && count($this->ci->input->post('status')) > 0) {
    $statuses = array_map('intval', $this->ci->input->post('status'));
    array_push($where, 'AND ' . db_prefix() . 'pur_petty_cash_advances.approve_status IN (' . implode(',', $statuses) . ')');
}

// Priority is a string, so it is filtered against the known values rather than being
// interpolated - anything unrecognised is simply dropped
if ($this->ci->input->post('priority') && count($this->ci->input->post('priority')) > 0) {
    $allowed  = array_keys(pur_pca_priorities());
    $selected = array_intersect($this->ci->input->post('priority'), $allowed);

    if (count($selected) > 0) {
        array_push($where, 'AND ' . db_prefix() . 'pur_petty_cash_advances.payment_priority IN ("' . implode('","', $selected) . '")');
    }
}

$result = data_tables_init(
    $aColumns,
    $sIndexColumn,
    $sTable,
    $join,
    $where,
    [
        db_prefix() . 'pur_petty_cash_advances.id as id',
        db_prefix() . 'pur_petty_cash_advances.currency',
        db_prefix() . 'expenses_categories.name as category_name',
    ]
);

$output  = $result['output'];
$rResult = $result['rResult'];

$base_currency = get_base_currency_pur();

foreach ($rResult as $aRow) {
    // data_tables_init() does not select a qualified column under its bare name. Any
    // entry in $aColumns of the form table.column is passed through
    // prefixed_table_fields_wildcard() (application/helpers/database_helper.php), which
    // emits
    //
    //     `tblpur_petty_cash_advances`.`request_no` AS `tblpur_petty_cash_advances.request_no`
    //
    // so the row arrives keyed 'tblpur_petty_cash_advances.request_no'. Reading
    // $aRow['request_no'] returned null for every column, which rendered this list as a
    // row per advance with every detail blank - only id, currency and category_name
    // worked, because those come through $additionalSelect, which is imploded raw and
    // never aliased.
    //
    // The qualified names stay in $aColumns: they are what keeps ordering and searching
    // unambiguous across the joined tables. A bare key already present is never
    // overwritten, so 'name' from departments cannot clobber the aliased category_name.
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

    $row[] = '<a href="' . admin_url('purchase/petty_cash_advance_detail/' . $r['id']) . '">'
             . e($r['request_no']) . '</a>';

    $row[] = $r['employee']
             ? '<a href="' . admin_url('staff/profile/' . $r['employee']) . '">' . e(get_staff_full_name($r['employee'])) . '</a>'
             : '';

    // departments.name is the flattened one; the expense category is selected under its
    // own alias precisely so the two cannot collide.
    $row[] = isset($r['name']) ? e($r['name']) : '';
    $row[] = isset($r['category_name']) ? e($r['category_name']) : '';

    // app_format_money needs a currency object or name, never a bare id
    $row_currency = $base_currency;
    if (!empty($r['currency'])) {
        $found = pur_get_currency_by_id($r['currency']);
        if ($found) {
            $row_currency = $found;
        }
    }
    $row[] = app_format_money($r['amount_requested'], $row_currency);

    $row[] = pur_pca_priority_label($r['payment_priority']);

    $row[] = $r['required_date'] ? _d($r['required_date']) : '';

    $row[] = _dt($r['created_at']);

    $row[] = pur_pca_status_label($r['approve_status']);

    $options = '<a href="' . admin_url('purchase/petty_cash_advance_detail/' . $r['id']) . '" class="btn btn-icon btn-default"><i class="fa fa-eye"></i></a>';

    // Editing closes once an approval has been recorded, mirroring the controller guard
    if (has_permission('purchase_petty_cash', '', 'edit')
        && !$this->ci->purchase_model->petty_cash_advance_has_approval_action($r['id'])) {
        $options .= '<a href="' . admin_url('purchase/petty_cash_advance/' . $r['id']) . '" class="btn btn-icon btn-warning"><i class="fa fa-pencil"></i></a>';
    }

    if (has_permission('purchase_petty_cash', '', 'delete')) {
        $options .= '<a href="' . admin_url('purchase/delete_petty_cash_advance/' . $r['id']) . '" class="btn btn-icon btn-danger _delete"><i class="fa fa-remove"></i></a>';
    }

    $row[] = $options;

    $output['aaData'][] = $row;
}

echo json_encode($output);
die();
