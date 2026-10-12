<?php defined('BASEPATH') or exit('No direct script access allowed');

$this->ci->load->model('currencies_model');
$currency = $this->ci->currencies_model->get_base_currency();

$aColumns = [
    'cheque_number',
    'cheque_type',
    'direction',
    'rel_name',
    'cheque_bank_name',
    'cheque_date',
    'amount',
    'invoice_ref',
    'status',
    'is_pdc',
    'approval_status',
];

$sIndexColumn = 'id';
$sTable       = db_prefix() . 'cheque_management';
$join         = [];
$where        = [];

// Deleting a cheque only flags it, so the register has to filter it out here. The
// "Show deleted" filter is what makes those rows reachable again for restoring.
if ($this->ci->input->post('show_deleted_ft') == '1') {
    $where[] = 'AND is_deleted = 1';
} else {
    $where[] = 'AND is_deleted = 0';
}

if ($this->ci->input->post('cheque_type_ft') != '') {
    $where[] = 'AND cheque_type = "' . $this->ci->db->escape_str($this->ci->input->post('cheque_type_ft')) . '"';
}
if ($this->ci->input->post('direction_ft') != '') {
    $where[] = 'AND direction = "' . $this->ci->db->escape_str($this->ci->input->post('direction_ft')) . '"';
}
if ($this->ci->input->post('status_ft') != '') {
    $where[] = 'AND status = ' . (int)$this->ci->input->post('status_ft');
}
if ($this->ci->input->post('is_pdc_ft') != '') {
    $where[] = 'AND is_pdc = ' . (int)$this->ci->input->post('is_pdc_ft');
}
if ($this->ci->input->post('from_date_ft') != '') {
    $where[] = 'AND cheque_date >= "' . to_sql_date($this->ci->input->post('from_date_ft')) . '"';
}
if ($this->ci->input->post('to_date_ft') != '') {
    $where[] = 'AND cheque_date <= "' . to_sql_date($this->ci->input->post('to_date_ft')) . '"';
}

$result  = data_tables_init($aColumns, $sIndexColumn, $sTable, $join, $where, ['id', 'cheque_attachment', 'source_type', 'is_deleted']);
$output  = $result['output'];
$rResult = $result['rResult'];

// Taken from the model rather than duplicated here. The local copy stopped at 10, so the
// four lifecycle statuses that were added later - 11 In Clearing, 12 Prepared,
// 13 Presented, 14 Returned - rendered as "Unknown", which is every outgoing cheque.
$this->ci->load->model('accounting/accounting_model');
$status_map = $this->ci->accounting_model->acc_cheque_statuses();

// 1 = awaiting approval, 2 = approved, 3 = rejected, matching what
// acc_cheque_send_for_approval() and acc_cheque_record_approval() write. 0 is a cheque
// that has never been submitted.
$approval_map = [
    0 => ['Not submitted', 'default'],
    1 => ['Awaiting',      'warning'],
    2 => ['Approved',      'success'],
    3 => ['Rejected',      'danger'],
];

foreach ($rResult as $aRow) {
    $row = [];

    // Cheque # + options
    $out  = '<a href="' . admin_url('accounting/cheque_management_view/' . $aRow['id']) . '"><strong>#' . htmlspecialchars($aRow['cheque_number']) . '</strong></a>';
    if ($aRow['is_pdc']) $out .= ' <span class="label label-warning">PDC</span>';
    // No Edit. A cheque on the register is a record of a cheque that was written, and
    // its amount, party and documents are derived from the payments behind it - editing
    // the register row by hand would put it out of step with them, and would also let the
    // amount or status be changed behind an approval that had already been given. The
    // lifecycle is changed through the status action, which is guarded, logged, and the
    // only route that keeps the payments and the ledger in step.
    $out .= '<div class="row-options">';
    $out .= '<a href="' . admin_url('accounting/cheque_management_view/' . $aRow['id']) . '">' . _l('view') . '</a>';

    if (!empty($aRow['is_deleted'])) {
        $out .= ' | <a href="' . admin_url('accounting/cheque_management_restore/' . $aRow['id']) . '" class="text-success">' . _l('acc_cheque_restore') . '</a>';
    } else {
        // Soft delete - the row and its audit trail are kept, see cheque_management_delete()
        $out .= ' | <a href="' . admin_url('accounting/cheque_management_delete/' . $aRow['id']) . '" class="text-danger _delete">' . _l('delete') . '</a>';
    }

    $out .= '</div>';
    $row[] = $out;

    // Type
    $tc = $aRow['cheque_type'] == 'outgoing' ? 'warning' : 'primary';
    $row[] = '<span class="label label-' . $tc . '">' . ucfirst($aRow['cheque_type']) . '</span>';

    // Direction
    $dc = $aRow['direction'] == 'vendor' ? 'warning' : 'info';
    $row[] = '<span class="label label-' . $dc . '">' . ucfirst($aRow['direction']) . '</span>';

    $row[] = htmlspecialchars($aRow['rel_name'] ?? '-');
    $row[] = htmlspecialchars($aRow['cheque_bank_name'] ?? '-');
    $row[] = !empty($aRow['cheque_date']) ? _d($aRow['cheque_date']) : '-';
    $row[] = app_format_money($aRow['amount'], $currency->name);
    $row[] = htmlspecialchars($aRow['invoice_ref'] ?? '-');

    // Status
    $st = $status_map[$aRow['status']] ?? ['Unknown','default'];
    $row[] = '<span class="label label-' . $st[1] . '">' . $st[0] . '</span>';

    // PDC
    $row[] = $aRow['is_pdc'] ? '<span class="label label-warning">Yes</span>' : '-';

    // Approval
    $ap = $approval_map[$aRow['approval_status']] ?? ['Not submitted','default'];
    $row[] = '<span class="label label-' . $ap[1] . '">' . $ap[0] . '</span>';

    $row['DT_RowClass'] = 'has-row-options';
    $output['aaData'][] = $row;
}
