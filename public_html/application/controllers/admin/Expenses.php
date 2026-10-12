<?php

use app\services\utilities\Arr;

defined('BASEPATH') or exit('No direct script access allowed');

class Expenses extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('expenses_model');
    }

    public function index($id = '')
    {
        $this->list_expenses($id);
    }

    public function list_expenses($id = '')
    {
        close_setup_menu();

        if (staff_cant('view', 'expenses') && staff_cant('view_own', 'expenses')) {
            access_denied('expenses');
        }

        $this->load->model('payment_modes_model');
        $data['payment_modes'] = $this->payment_modes_model->get('', [], true);
        $data['expenseid']     = $id;
        $data['categories']    = $this->expenses_model->get_category();
        $data['years']         = $this->expenses_model->get_expenses_years();
        $data['table']         = App_table::find('expenses');
        $data['title']         = _l('expenses');

        $this->load->view('admin/expenses/manage', $data);
    }

    public function table($clientid = '')
    {
        if (staff_cant('view', 'expenses') && staff_cant('view_own', 'expenses')) {
            ajax_access_denied();
        }

        $this->load->model('payment_modes_model');
        $data['payment_modes'] = $this->payment_modes_model->get('', [], true);

        App_table::find('expenses')->output([
            'clientid' => $clientid,
            'data'     => $data,
        ]);
    }

    public function expense($id = '')
    {
        if ($this->input->post()) {
            if ($id == '') {
                if (staff_cant('create', 'expenses')) {
                    set_alert('danger', _l('access_denied'));
                    echo json_encode([
                        'url' => admin_url('expenses/expense'),
                    ]);
                    die;
                }
                $id = $this->expenses_model->add($this->input->post());
                if ($id) {
                    set_alert('success', _l('added_successfully', _l('expense')));
                    echo json_encode([
                        'url'       => admin_url('expenses/list_expenses/' . $id),
                        'expenseid' => $id,
                    ]);
                    die;
                }
                echo json_encode([
                    'url' => admin_url('expenses/expense'),
                ]);
                die;
            }
            if (staff_cant('edit', 'expenses')) {
                set_alert('danger', _l('access_denied'));
                echo json_encode([
                        'url' => admin_url('expenses/expense/' . $id),
                    ]);
                die;
            }
            // Moving an expense off the Credit mode while payments are recorded
            // against it would orphan those payments - their ledger entries would
            // stay on the books while the expense re-posts as if it were paid at
            // source, crediting bank twice. Reverse the payments first.
            $post_data      = $this->input->post();
            $credit_mode_id = (int) get_option('expense_credit_payment_mode');
            $existing       = $this->expenses_model->get($id);

            $was_credit = $existing && $credit_mode_id > 0 && (int) $existing->paymentmode === $credit_mode_id;
            $now_credit = $credit_mode_id > 0 && isset($post_data['paymentmode'])
                && (int) $post_data['paymentmode'] === $credit_mode_id;

            if ($was_credit && !$now_credit && $this->expenses_model->expense_total_paid($id) > 0) {
                set_alert('warning', _l('expense_payment_mode_locked_by_payments'));
                echo json_encode([
                    'url'       => admin_url('expenses/list_expenses/' . $id),
                    'expenseid' => $id,
                ]);
                die;
            }

            $success = $this->expenses_model->update($post_data, $id);
            if ($success) {
                set_alert('success', _l('updated_successfully', _l('expense')));
            }
            echo json_encode([
                    'url'       => admin_url('expenses/list_expenses/' . $id),
                    'expenseid' => $id,
                ]);
            die;
        }
        if ($id == '') {
            $title = _l('add_new', _l('expense'));
        } else {
            $data['expense'] = $this->expenses_model->get($id);

            if (!$data['expense'] || (staff_cant('view', 'expenses') && $data['expense']->addedfrom != get_staff_user_id())) {
                blank_page(_l('expense_not_found'));
            }

            $title = _l('edit', _l('expense'));
        }

        if ($this->input->get('customer_id')) {
            $data['customer_id'] = $this->input->get('customer_id');
        }

        $this->load->model('taxes_model');
        $this->load->model('payment_modes_model');
        $this->load->model('currencies_model');

        $data['taxes']         = $this->taxes_model->get();
        $data['categories']    = $this->expenses_model->get_category();
        // Load expense ledger accounts - all active accounts.
        // The account type is carried through as a subtext so it is obvious which
        // accounts land in the Profit & Loss and which stay on the Balance Sheet.
        // Picking a liability here (for example an A/P account) is legal double
        // entry but the expense will then never appear in the P&L, which has
        // caught users out before.
        $this->db->select('id, account_type_id, CONCAT(number, " - ", name) as name');
        $this->db->where('active', 1);
        $this->db->order_by('number', 'ASC');
        $expense_ledgers = $this->db->get(db_prefix().'acc_accounts')->result_array();

        // Account types 11-15 (income, other income, cost of sales, expenses, other
        // expense) are the Profit & Loss types; everything else is Balance Sheet.
        // Mirrors the ranges the P&L and Balance Sheet reports use in
        // Accounting_model - keep them in step if the type list ever changes.
        $profit_and_loss_types = [11, 12, 13, 14, 15];

        foreach ($expense_ledgers as $key => $ledger) {
            $expense_ledgers[$key]['type_name'] = in_array((int) $ledger['account_type_id'], $profit_and_loss_types)
                ? _l('expense_ledger_in_profit_loss')
                : _l('expense_ledger_in_balance_sheet');
        }

        $data['expense_ledgers'] = $expense_ledgers;
        // Cost centers - managed at admin/cost_centers
        $this->db->select('id, name');
        $this->db->order_by('name', 'ASC');
        $data['cost_centers'] = $this->db->get(db_prefix().'cost_centers')->result_array();
        $data['payment_modes'] = $this->payment_modes_model->get('', [
            'invoices_only !=' => 1,
        ]);
        $data['bodyclass']  = 'expense';
        $data['currencies'] = $this->currencies_model->get();
        $data['title']      = $title;

        // Line items behind the header amount. Empty on a new expense, and empty on an
        // older one that was recorded before line items existed, in which case the
        // Amount box carries on working exactly as it did.
        $data['expense_items'] = isset($data['expense'])
            ? $this->expenses_model->get_expense_items($data['expense']->id)
            : [];

        $this->load->view('admin/expenses/expense', $data);
    }

    public function import()
    {
        if (staff_cant('create', 'expenses')) {
            access_denied('Items Import');
        }

        $this->load->library('import/import_expenses', [], 'import');

        $this->import->setDatabaseFields($this->db->list_fields(db_prefix() . 'expenses'))
            ->setCustomFields(get_custom_fields('expenses'));

        if ($this->input->post('download_sample') === 'true') {
            $this->import->downloadSample();
        }

        if (
            $this->input->post()
            && isset($_FILES['file_csv']['name']) && $_FILES['file_csv']['name'] != ''
        ) {
            $this->import->setSimulation($this->input->post('simulate'))
                ->setTemporaryFileLocation($_FILES['file_csv']['tmp_name'])
                ->setFilename($_FILES['file_csv']['name'])
                ->perform();

            $data['total_rows_post'] = $this->import->totalRows();

            if (!$this->import->isSimulation()) {
                set_alert('success', _l('import_total_imported', $this->import->totalImported()));
            }
        }

        $data['title'] = _l('import');
        $this->load->view('admin/expenses/import', $data);
    }

    public function bulk_action()
    {
        hooks()->do_action('before_do_bulk_action_for_expenses');
        $total_deleted = 0;
        $total_updated = 0;

        if ($this->input->post()) {
            $ids         = $this->input->post('ids');
            $amount      = $this->input->post('amount');
            $date        = $this->input->post('date');
            $category    = $this->input->post('category');
            $paymentmode = $this->input->post('paymentmode');

            if (is_array($ids)) {
                foreach ($ids as $id) {
                    if ($this->input->post('mass_delete')) {
                        if (staff_can('delete', 'expenses')) {
                            if ($this->expenses_model->delete($id)) {
                                $total_deleted++;
                            }
                        }
                    } else {
                        if (staff_can('edit', 'expenses')) {
                            $this->db->where('id', $id);
                            $this->db->update('expenses', array_filter([
                                'paymentmode' => $paymentmode ?: null,
                                'category'    => $category ?: null,
                                'date'        => $date ? to_sql_date($date) : null,
                                'amount'      => $amount ?: null,
                            ]));

                            if ($this->db->affected_rows() > 0) {
                                $total_updated++;
                            }
                        }
                    }
                }
            }

            if ($total_updated > 0) {
                set_alert('success', _l('updated_successfully', _l('expenses')));
            } elseif ($this->input->post('mass_delete')) {
                set_alert('success', _l('total_expenses_deleted', $total_deleted));
            }
        }
    }

    public function get_expenses_total()
    {
        if ($this->input->post()) {
            $data['totals'] = $this->expenses_model->get_expenses_total($this->input->post());

            if ($data['totals']['currency_switcher'] == true) {
                $this->load->model('currencies_model');
                $data['currencies'] = $this->currencies_model->get();
            }

            $data['expenses_years'] = $this->expenses_model->get_expenses_years();

            if (count($data['expenses_years']) >= 1 && $data['expenses_years'][0]['year'] != date('Y')) {
                array_unshift($data['expenses_years'], ['year' => date('Y')]);
            }
            
            $data['expenses_years'] = Arr::uniqueByKey($data['expenses_years'], 'year');

            $data['_currency'] = $data['totals']['currencyid'];
            $this->load->view('admin/expenses/expenses_total_template', $data);
        }
    }

    // Not used at this time
    public function pdf($id)
    {
        $expense = $this->expenses_model->get($id);

        if (staff_cant('view', 'expenses') && $expense->addedfrom != get_staff_user_id()) {
            access_denied();
        }

        $pdf = app_pdf('expense', LIBSPATH . 'pdf/Expense_pdf', $expense);
        // Output PDF to user
        $pdf->output('#' . slug_it($expense->category_name) . '_' . _d($expense->date) . '.pdf', 'I');
    }

    public function delete($id)
    {
        if (staff_cant('delete', 'expenses')) {
            access_denied('expenses');
        }
        if (!$id) {
            redirect(admin_url('expenses/list_expenses'));
        }
        $response = $this->expenses_model->delete($id);
        if ($response === true) {
            set_alert('success', _l('deleted', _l('expense')));
        } else {
            if (is_array($response) && $response['invoiced'] == true) {
                set_alert('warning', _l('expense_invoice_delete_not_allowed'));
            } else {
                set_alert('warning', _l('problem_deleting', _l('expense_lowercase')));
            }
        }

        redirect(previous_url() ?: $_SERVER['HTTP_REFERER']);
    }

    public function copy($id)
    {
        if (staff_cant('create', 'expenses')) {
            access_denied('expenses');
        }
        $new_expense_id = $this->expenses_model->copy($id);
        if ($new_expense_id) {
            set_alert('success', _l('expense_copy_success'));
            redirect(admin_url('expenses/expense/' . $new_expense_id));
        } else {
            set_alert('warning', _l('expense_copy_fail'));
        }
        redirect(admin_url('expenses/list_expenses/' . $id));
    }

    public function convert_to_invoice($id)
    {
        if (staff_cant('create', 'invoices')) {
            access_denied('Convert Expense to Invoice');
        }
        if (!$id) {
            redirect(admin_url('expenses/list_expenses'));
        }
        $draft_invoice = false;
        if ($this->input->get('save_as_draft')) {
            $draft_invoice = true;
        }

        $params = [];
        if ($this->input->get('include_note') == 'true') {
            $params['include_note'] = true;
        }

        if ($this->input->get('include_name') == 'true') {
            $params['include_name'] = true;
        }

        $invoiceid = $this->expenses_model->convert_to_invoice($id, $draft_invoice, $params);
        if ($invoiceid) {
            set_alert('success', _l('expense_converted_to_invoice'));
            redirect(admin_url('invoices/invoice/' . $invoiceid));
        } else {
            set_alert('warning', _l('expense_converted_to_invoice_fail'));
        }
        redirect(admin_url('expenses/list_expenses/' . $id));
    }

    public function get_expense_data_ajax($id)
    {
        if (staff_cant('view', 'expenses') && staff_cant('view_own', 'expenses')) {
            echo _l('access_denied');
            die;
        }
        $expense = $this->expenses_model->get($id);

        if (!$expense || (staff_cant('view', 'expenses') && $expense->addedfrom != get_staff_user_id())) {
            echo _l('expense_not_found');
            die;
        }

        $data['expense'] = $expense;
        if ($expense->billable == 1) {
            if ($expense->invoiceid !== null) {
                $this->load->model('invoices_model');
                $data['invoice'] = $this->invoices_model->get($expense->invoiceid);
            }
        }

        $data['child_expenses'] = $this->expenses_model->get_child_expenses($id);
        $data['members']        = $this->staff_model->get('', ['active' => 1]);

        // Resolve display labels for the id-based fields captured on the form,
        // so the preview shows everything that was entered.
        $data['expense_vendor_name']      = '';
        $data['expense_cost_center_name'] = '';
        $data['expense_ledger_name']      = $this->acc_account_label($expense->ledger_account ?? null);
        $data['expense_credit_gl_name']   = $this->acc_account_label($expense->credit_gl_account ?? null);

        if (!empty($expense->vendor) && is_numeric($expense->vendor)) {
            $this->db->select('company');
            $this->db->where('userid', $expense->vendor);
            $vendor_row = $this->db->get(db_prefix() . 'pur_vendor')->row();
            if ($vendor_row) {
                $data['expense_vendor_name'] = $vendor_row->company;
            }
        }

        if (!empty($expense->cost_center) && is_numeric($expense->cost_center)) {
            $this->db->select('name');
            $this->db->where('id', $expense->cost_center);
            $cc_row = $this->db->get(db_prefix() . 'cost_centers')->row();
            if ($cc_row) {
                $data['expense_cost_center_name'] = $cc_row->name;
            }
        }

        // Payment data. Only an expense bought on credit carries an outstanding
        // payable - the rest were settled the moment they were recorded, so they
        // must not offer a payment form.
        $data['expense_is_credit']    = $this->expenses_model->is_credit_expense($expense);
        $data['expense_total']        = $this->expenses_model->expense_total_with_tax($expense);
        $data['expense_total_paid']   = $this->expenses_model->expense_total_paid($expense->id);
        $data['expense_amount_left']  = $this->expenses_model->expense_amount_left($expense);
        $data['expense_payments']     = $this->expenses_model->get_expense_payments($expense->id, true);
        $data['expense_pay_accounts'] = $this->expenses_model->get_expense_payment_accounts();

        // Ways of actually settling the expense. "Credit" is excluded because it is
        // what created the payable in the first place. Read straight from
        // tblpayment_modes rather than through payment_modes_model::get(), which also
        // merges in the online gateways - those carry string ids that would be
        // truncated to 0 by the integer paymentmode column.
        $credit_mode_id = (int) get_option('expense_credit_payment_mode');
        $this->db->select('id, name');
        $this->db->where('active', 1);
        $this->db->where('invoices_only !=', 1);
        if ($credit_mode_id > 0) {
            $this->db->where('id !=', $credit_mode_id);
        }
        $this->db->order_by('name', 'ASC');
        $data['expense_payment_modes'] = $this->db->get(db_prefix() . 'payment_modes')->result_array();

        // The breakdown behind the header amount. Empty for expenses recorded before
        // line items existed, and the view simply omits the table in that case.
        $data['expense_items'] = $this->expenses_model->get_expense_items($expense->id);

        $this->load->view('admin/expenses/expense_preview_template', $data);
    }

    /**
     * Batch payment screen for credit expenses.
     *
     * Returns the modal HTML, loaded into #modal-wrapper on the expenses list. The
     * purchase module's batch payment works the same way, so the two screens behave
     * identically for the user.
     */
    public function batch_payment_modal()
    {
        if (staff_cant('edit', 'expenses')) {
            ajax_access_denied();
        }

        $data['expenses'] = $this->expenses_model->get_unpaid_credit_expenses();

        // Vendors that actually appear in the list, so the filter never offers a
        // vendor with nothing to pay.
        $vendor_ids = [];
        foreach ($data['expenses'] as $expense) {
            if (!empty($expense->vendor) && is_numeric($expense->vendor)) {
                $vendor_ids[(int) $expense->vendor] = $expense->vendor_company;
            }
        }
        asort($vendor_ids);
        $data['vendors'] = $vendor_ids;

        // Ways of settling. "Credit" is excluded - it is what created the payable.
        // Read straight from tblpayment_modes rather than through
        // payment_modes_model::get(), which also merges in the online gateways whose
        // string ids would be truncated to 0 by the integer paymentmode column.
        $credit_mode_id = (int) get_option('expense_credit_payment_mode');
        $this->db->select('id, name');
        $this->db->where('active', 1);
        $this->db->where('invoices_only !=', 1);
        if ($credit_mode_id > 0) {
            $this->db->where('id !=', $credit_mode_id);
        }
        $this->db->order_by('name', 'ASC');
        $data['payment_modes'] = $this->db->get(db_prefix() . 'payment_modes')->result_array();

        $data['pay_accounts'] = $this->expenses_model->get_expense_payment_accounts();

        $this->load->view('admin/expenses/batch_payment_modal', $data);
    }

    /**
     * Save a batch of expense payments.
     *
     * Answers JSON - the modal submits by AJAX so partial failures can be reported
     * per expense rather than losing the whole submission.
     */
    public function add_batch_payment()
    {
        if (staff_cant('edit', 'expenses')) {
            echo json_encode(['success' => false, 'message' => _l('access_denied')]);
            die;
        }

        if ($this->input->method() !== 'post') {
            show_404();
        }

        $result = $this->expenses_model->add_batch_expense_payment($this->input->post());

        $message = $result['count'] > 0
            ? _l('expense_batch_payment_added', $result['count'])
            : _l('expense_batch_payment_none');

        // Rows that could not be paid are named rather than silently dropped
        if (!empty($result['errors'])) {
            $message .= ' ' . implode(' ', $result['errors']);
        }

        echo json_encode([
            'success' => $result['count'] > 0,
            'message' => $message,
            'count'   => $result['count'],
        ]);
        die;
    }

    /**
     * Batch print - find the expense payments recorded under one transaction id.
     *
     * Answers JSON with a preview table, mirroring purchase/batch_print_search so
     * the two screens behave the same way.
     */
    public function expense_batch_print_search()
    {
        if (staff_cant('view', 'expenses') && staff_cant('view_own', 'expenses')) {
            ajax_access_denied();
        }

        $txn_id = $this->input->post('transaction_id');

        if (empty($txn_id)) {
            echo json_encode(['html' => '', 'count' => 0]);
            die;
        }

        $rows = $this->expenses_model->get_expense_payments_by_transaction($txn_id);

        if (count($rows) === 0) {
            echo json_encode([
                'html'  => '<p class="text-muted text-center">' . _l('no_results_found') . '</p>',
                'count' => 0,
            ]);
            die;
        }

        $html = '<table class="table table-bordered">';
        $html .= '<thead><tr>'
            . '<th>' . _l('expense_invoice_number') . '</th>'
            . '<th>' . _l('expense_batch_vendor') . '</th>'
            . '<th>' . _l('expense_batch_date') . '</th>'
            . '<th class="text-right">' . _l('expense_total') . '</th>'
            . '<th class="text-right">' . _l('expense_payment_amount') . '</th>'
            . '<th>' . _l('payment_mode') . '</th>'
            . '<th>' . _l('payment_status') . '</th>'
            . '</tr></thead><tbody>';

        $total_paid = 0;

        foreach ($rows as $row) {
            $total_paid += (float) $row->amount;

            $html .= '<tr>'
                . '<td>' . e($row->expinv_number ?: '#' . $row->expense_id) . '</td>'
                . '<td>' . e($row->vendor_company) . '</td>'
                . '<td>' . _d($row->expense_date) . '</td>'
                . '<td class="text-right">' . app_format_money($row->expense_total, $row->currency_name) . '</td>'
                . '<td class="text-right">' . app_format_money($row->amount, $row->currency_name) . '</td>'
                . '<td>' . e($row->payment_mode_name) . '</td>'
                . '<td>' . _l($row->payment_status ?: 'unpaid') . '</td>'
                . '</tr>';
        }

        $html .= '</tbody><tfoot><tr>'
            . '<td colspan="4" class="text-right"><strong>' . _l('total') . '</strong></td>'
            . '<td class="text-right"><strong>' . app_format_money($total_paid, '') . '</strong></td>'
            . '<td colspan="2"></td>'
            . '</tr></tfoot></table>';

        echo json_encode(['html' => $html, 'count' => count($rows)]);
        die;
    }

    /**
     * Batch print PDF for one transaction id.
     *
     * Same layout as the purchase invoice batch print: company heading, the payment
     * details that are common to the batch, cheque details when relevant, then a line
     * per expense paid.
     */
    public function expense_batch_print_pdf()
    {
        if (staff_cant('view', 'expenses') && staff_cant('view_own', 'expenses')) {
            access_denied('expenses');
        }

        // TCPDF writes straight to the output buffer and errors if anything was sent
        // before it.
        if (ob_get_level()) {
            ob_end_clean();
        }

        $txn_id = $this->input->get('transaction_id');

        if (empty($txn_id)) {
            redirect(admin_url('expenses'));
        }

        $rows = $this->expenses_model->get_expense_payments_by_transaction($txn_id);

        if (count($rows) === 0) {
            set_alert('warning', _l('no_results_found'));
            redirect(admin_url('expenses'));
        }

        $company_name = get_option('invoice_company_name');
        $first        = $rows[0];

        // A batch can span vendors, so the vendor is shown per line rather than once
        // at the top. Everything genuinely common to the batch is shown here.
        $is_cheque = (stripos($first->payment_mode_name, 'cheque') !== false
            || stripos($first->payment_mode_name, 'check') !== false);

        $html = '<div style="text-align:center;margin-bottom:10px;">';
        $html .= '<h2>' . e($company_name) . '</h2>';
        $html .= '<h3>' . _l('expense_batch_payments') . '</h3>';
        $html .= '<p><strong>' . _l('transaction_id') . ':</strong> ' . e($txn_id) . '</p>';
        $html .= '</div>';

        $html .= '<table width="100%" cellpadding="4" style="margin-bottom:6px;border-bottom:1px solid #ccc;">';
        $html .= '<tr>';
        $html .= '<td width="50%"><strong>' . _l('payment_mode') . ':</strong> ' . e($first->payment_mode_name) . '</td>';
        $html .= '<td width="50%" align="right"><strong>' . _l('expense_payment_date') . ':</strong> ' . _d($first->date) . '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td width="50%"><strong>' . _l('expense_payment_paid_from') . ':</strong> ' . e($first->paid_from_name ?: '-') . '</td>';
        $html .= '<td width="50%" align="right"><strong>' . _l('expense_batch_print_printed_on') . ':</strong> ' . _d(date('Y-m-d')) . '</td>';
        $html .= '</tr>';
        $html .= '</table>';

        if ($is_cheque) {
            $html .= '<table width="100%" cellpadding="4" cellspacing="0" style="border:1px solid #aaa;margin-bottom:12px;">';
            $html .= '<tr style="background:#ddeeff;"><td colspan="3" style="padding:5px 7px;"><strong><i>'
                . _l('cheque_details') . '</i></strong></td></tr>';
            $html .= '<tr>';
            $html .= '<td width="33%" style="padding:4px 7px;border-top:1px solid #ccc;"><strong>'
                . _l('cheque_bank_name') . ':</strong><br>' . e($first->cheque_bank_name ?: '-') . '</td>';
            $html .= '<td width="33%" style="padding:4px 7px;border-top:1px solid #ccc;"><strong>'
                . _l('cheque_number') . ':</strong><br>' . e($first->cheque_number ?: '-') . '</td>';
            $html .= '<td width="34%" style="padding:4px 7px;border-top:1px solid #ccc;"><strong>'
                . _l('cheque_date') . ':</strong><br>' . (!empty($first->cheque_date) ? _d($first->cheque_date) : '-') . '</td>';
            $html .= '</tr></table>';
        }

        $html .= '<table border="1" cellpadding="4" cellspacing="0" width="100%">';
        $html .= '<tr style="background-color:#f0f0f0;">';
        $html .= '<td width="5%" align="center"><strong>#</strong></td>';
        $html .= '<td width="17%"><strong>' . _l('expense_invoice_number') . '</strong></td>';
        $html .= '<td width="20%"><strong>' . _l('expense_batch_vendor') . '</strong></td>';
        $html .= '<td width="11%" align="center"><strong>' . _l('expense_batch_date') . '</strong></td>';
        $html .= '<td width="15%" align="right"><strong>' . _l('expense_total') . '</strong></td>';
        $html .= '<td width="15%" align="right"><strong>' . _l('paid') . '</strong></td>';
        $html .= '<td width="17%" align="center"><strong>' . _l('payment_status') . '</strong></td>';
        $html .= '</tr>';

        $total_expense = 0;
        $total_paid    = 0;
        $i             = 1;

        foreach ($rows as $row) {
            $html .= '<tr>';
            $html .= '<td width="5%" align="center">' . $i . '</td>';
            $html .= '<td width="17%">' . e($row->expinv_number ?: '#' . $row->expense_id) . '</td>';
            $html .= '<td width="20%">' . e($row->vendor_company ?: '-') . '</td>';
            $html .= '<td width="11%" align="center">' . _d($row->expense_date) . '</td>';
            $html .= '<td width="15%" align="right">' . app_format_money($row->expense_total, $row->currency_name) . '</td>';
            $html .= '<td width="15%" align="right">' . app_format_money($row->amount, $row->currency_name) . '</td>';
            $html .= '<td width="17%" align="center">' . _l($row->payment_status ?: 'unpaid') . '</td>';
            $html .= '</tr>';

            $total_expense += (float) $row->expense_total;
            $total_paid += (float) $row->amount;
            $i++;
        }

        $html .= '<tr style="background-color:#f0f0f0;">';
        $html .= '<td width="5%"></td><td width="17%"></td><td width="20%"></td>';
        $html .= '<td width="11%" align="right"><strong>' . _l('total') . '</strong></td>';
        $html .= '<td width="15%" align="right"><strong>' . app_format_money($total_expense, '') . '</strong></td>';
        $html .= '<td width="15%" align="right"><strong>' . app_format_money($total_paid, '') . '</strong></td>';
        $html .= '<td width="17%"></td>';
        $html .= '</tr></table>';

        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('NextGen');
        $pdf->SetAuthor($company_name);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->SetFont('dejavusans', '', 9);
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('expense_batch_payment_' . $txn_id . '.pdf', 'I');
    }

    /**
     * Record a payment against an expense bought on credit.
     *
     * Answers JSON because the form lives in a modal on the AJAX loaded preview.
     */
    public function expense_payment($expense_id = '')
    {
        if (staff_cant('edit', 'expenses')) {
            echo json_encode([
                'success' => false,
                'message' => _l('access_denied'),
            ]);
            die;
        }

        if (!$this->input->post() || !is_numeric($expense_id)) {
            echo json_encode([
                'success' => false,
                'message' => _l('expense_payment_failed'),
            ]);
            die;
        }

        $expense = $this->expenses_model->get($expense_id);

        if (!$expense) {
            echo json_encode([
                'success' => false,
                'message' => _l('expense_not_found'),
            ]);
            die;
        }

        // Only a credit expense has an outstanding payable. Everything else was
        // settled when it was recorded, so paying it again would double count.
        if (!$this->expenses_model->is_credit_expense($expense)) {
            echo json_encode([
                'success' => false,
                'message' => _l('expense_payment_not_on_credit'),
            ]);
            die;
        }

        $data               = $this->input->post();
        $data['expense_id'] = $expense_id;

        $left   = $this->expenses_model->expense_amount_left($expense);
        $amount = isset($data['amount']) ? round((float) $data['amount'], 2) : 0;

        if ($amount <= 0) {
            echo json_encode([
                'success' => false,
                'message' => _l('expense_payment_amount_required'),
            ]);
            die;
        }

        if ($amount > $left) {
            echo json_encode([
                'success' => false,
                'message' => _l('expense_payment_exceeds_balance', app_format_money($left, $expense->currency_data)),
            ]);
            die;
        }

        if (empty($data['gl_account'])) {
            echo json_encode([
                'success' => false,
                'message' => _l('expense_payment_account_required'),
            ]);
            die;
        }

        $payment_id = $this->expenses_model->add_expense_payment($data);

        echo json_encode([
            'success' => $payment_id ? true : false,
            'message' => $payment_id
                ? _l('expense_payment_recorded')
                : _l('expense_payment_failed'),
        ]);
    }

    /**
     * Reverse an expense payment. Soft deleted, ledger entry removed.
     */
    public function delete_expense_payment($id)
    {
        if (staff_cant('delete', 'expenses')) {
            access_denied('expenses');
        }

        $payment = $this->expenses_model->get_expense_payment($id);

        if (!$payment) {
            set_alert('warning', _l('expense_payment_not_found'));
            redirect(admin_url('expenses'));
        }

        if ($this->expenses_model->delete_expense_payment($id)) {
            set_alert('success', _l('expense_payment_reversed'));
        } else {
            set_alert('warning', _l('expense_payment_reverse_failed'));
        }

        redirect(admin_url('expenses/list_expenses/' . $payment->expense_id));
    }

    /**
     * Chart of accounts label ("number - name") for a given account id.
     */
    private function acc_account_label($id)
    {
        if (empty($id) || !is_numeric($id)) {
            return '';
        }

        $this->db->select('number, name');
        $this->db->where('id', $id);
        $row = $this->db->get(db_prefix() . 'acc_accounts')->row();

        return $row ? trim($row->number . ' - ' . $row->name) : '';
    }

    public function get_customer_change_data($customer_id = '')
    {
        echo json_encode([
            'customer_has_projects' => customer_has_projects($customer_id),
            'client_currency'       => $this->clients_model->get_customer_default_currency($customer_id),
        ]);
    }

    public function categories()
    {
        if (!is_admin()) {
            access_denied('expenses');
        }
        if ($this->input->is_ajax_request()) {
            $this->app->get_table_data('expenses_categories');
        }
        $data['title'] = _l('expense_categories');
        $this->load->view('admin/expenses/manage_categories', $data);
    }

    public function category()
    {
        if (!is_admin() && get_option('staff_members_create_inline_expense_categories') == '0') {
            access_denied('expenses');
        }
        if ($this->input->post()) {
            if (!$this->input->post('id')) {
                $id = $this->expenses_model->add_category($this->input->post());
                echo json_encode([
                    'success' => $id ? true : false,
                    'message' => $id ? _l('added_successfully', _l('expense_category')) : '',
                    'id'      => $id,
                    'name'    => $this->input->post('name'),
                ]);
            } else {
                $data = $this->input->post();
                $id   = $data['id'];
                unset($data['id']);
                $success = $this->expenses_model->update_category($data, $id);
                $message = _l('updated_successfully', _l('expense_category'));
                echo json_encode(['success' => $success, 'message' => $message]);
            }
        }
    }

    public function delete_category($id)
    {
        if (!is_admin()) {
            access_denied('expenses');
        }
        if (!$id) {
            redirect(admin_url('expenses/categories'));
        }
        $response = $this->expenses_model->delete_category($id);
        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('is_referenced', _l('expense_category_lowercase')));
        } elseif ($response == true) {
            set_alert('success', _l('deleted', _l('expense_category')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('expense_category_lowercase')));
        }
        redirect(admin_url('expenses/categories'));
    }

    public function add_expense_attachment($id)
    {
        handle_expense_attachments($id);
        echo json_encode([
            'url' => admin_url('expenses/list_expenses/' . $id),
        ]);
    }

    public function delete_expense_attachment($id, $preview = '')
    {
        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'expense');
        $file = $this->db->get(db_prefix() . 'files')->row();

        if ($file->staffid == get_staff_user_id() || is_admin()) {
            $success = $this->expenses_model->delete_expense_attachment($id);
            if ($success) {
                set_alert('success', _l('deleted', _l('expense_receipt')));
            } else {
                set_alert('warning', _l('problem_deleting', _l('expense_receipt_lowercase')));
            }
            if ($preview == '') {
                redirect(admin_url('expenses/expense/' . $id));
            } else {
                redirect(admin_url('expenses/list_expenses/' . $id));
            }
        } else {
            access_denied('expenses');
        }
    }
}