<?php

use app\services\utilities\Arr;

defined('BASEPATH') or exit('No direct script access allowed');

class Expenses_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get expense(s)
     * @param  mixed $id Optional expense id
     * @return mixed     object or array
     */
    public function get($id = '', $where = [])
    {
        $this->db->select('*,' . db_prefix() . 'expenses.id as id,' . db_prefix() . 'expenses_categories.name as category_name,' . db_prefix() . 'payment_modes.name as payment_mode_name,' . db_prefix() . 'taxes.name as tax_name, ' . db_prefix() . 'taxes.taxrate as taxrate,' . db_prefix() . 'taxes_2.name as tax_name2, ' . db_prefix() . 'taxes_2.taxrate as taxrate2, ' . db_prefix() . 'expenses.id as expenseid,' . db_prefix() . 'expenses.addedfrom as addedfrom, recurring_from');
        $this->db->from(db_prefix() . 'expenses');
        $this->db->join(db_prefix() . 'clients', '' . db_prefix() . 'clients.userid = ' . db_prefix() . 'expenses.clientid', 'left');
        $this->db->join(db_prefix() . 'payment_modes', '' . db_prefix() . 'payment_modes.id = ' . db_prefix() . 'expenses.paymentmode', 'left');
        $this->db->join(db_prefix() . 'taxes', '' . db_prefix() . 'taxes.id = ' . db_prefix() . 'expenses.tax', 'left');
        $this->db->join('' . db_prefix() . 'taxes as ' . db_prefix() . 'taxes_2', '' . db_prefix() . 'taxes_2.id = ' . db_prefix() . 'expenses.tax2', 'left');
        $this->db->join(db_prefix() . 'expenses_categories', '' . db_prefix() . 'expenses_categories.id = ' . db_prefix() . 'expenses.category');
        $this->db->where($where);

        if (is_numeric($id)) {
            $this->db->where(db_prefix() . 'expenses.id', $id);
            $expense = $this->db->get()->row();
            if ($expense) {
                $expense->attachment            = '';
                $expense->filetype              = '';
                $expense->attachment_added_from = 0;

                $this->db->where('rel_id', $id);
                $this->db->where('rel_type', 'expense');
                $file = $this->db->get(db_prefix() . 'files')->row();

                if ($file) {
                    $expense->attachment            = $file->file_name;
                    $expense->filetype              = $file->filetype;
                    $expense->attachment_added_from = $file->staffid;
                }

                $this->load->model('projects_model');
                $expense->currency_data = get_currency($expense->currency);
                if ($expense->project_id) {
                    $expense->project_data = $this->projects_model->get($expense->project_id);
                }

                if (is_null($expense->payment_mode_name)) {
                    // is online payment mode
                    $this->load->model('payment_modes_model');
                    $payment_gateways = $this->payment_modes_model->get_payment_gateways(true);
                    foreach ($payment_gateways as $gateway) {
                        if ($expense->paymentmode == $gateway['id']) {
                            $expense->payment_mode_name = $gateway['name'];
                        }
                    }
                }
            }

            return $expense;
        }
        $this->db->order_by('date', 'desc');

        return $this->db->get()->result_array();
    }

    /**
     * Add new expense
     * @param mixed $data All $_POST data
     * @return  mixed
     */
    public function add($data)
    {
        $data['date'] = to_sql_date($data['date']);
        $data['note'] = nl2br($data['note']);

        // Line items are their own table, so they must come out of the payload before
        // the insert or CI would try to write an items column that does not exist.
        // Held here and written once the expense has an id.
        $items = $this->extract_items_from_post($data);

        // Handle cheque date conversion
        if(isset($data['cheque_date']) && !empty($data['cheque_date'])){
            $data['cheque_date'] = to_sql_date($data['cheque_date']);
        } else {
            $data['cheque_date'] = null;
        }
        // Clear cheque fields if payment mode is not cheque
        if(!empty($data['paymentmode'])){
            $this->db->where('id', $data['paymentmode']);
            $_pm = $this->db->get(db_prefix().'payment_modes')->row();
            if(!$_pm || (stripos($_pm->name, 'cheque') === false && stripos($_pm->name, 'check') === false)){
                $data['cheque_number'] = null;
                $data['cheque_bank_name'] = null;
                $data['cheque_date'] = null;
            }
        }

        // An expense bought on credit is not paid when it is recorded - the vendor is
        // owed the amount - so it must not touch cash or bank. Its credit side goes to
        // the configured account (180090 Prepaid Expenses by default). Matched on the
        // configured mode id rather than the name so renaming the mode cannot break it.
        $credit_mode_id    = (int) get_option('expense_credit_payment_mode');
        $is_credit_expense = $credit_mode_id > 0
            && isset($data['paymentmode'])
            && (int) $data['paymentmode'] === $credit_mode_id;

        if ($is_credit_expense && empty($data['credit_gl_account'])) {
            $default_credit_gl = $this->default_credit_expense_account($data['vendor'] ?? null);
            if ($default_credit_gl > 0) {
                $data['credit_gl_account'] = $default_credit_gl;
            }
        }

        if (isset($data['billable'])) {
            $data['billable'] = 1;
        } else {
            $data['billable'] = 0;
        }
        if (isset($data['create_invoice_billable'])) {
            $data['create_invoice_billable'] = 1;
        } else {
            $data['create_invoice_billable'] = 0;
        }
        if (isset($data['custom_fields'])) {
            $custom_fields = $data['custom_fields'];
            unset($data['custom_fields']);
        }
        if (isset($data['send_invoice_to_customer'])) {
            $data['send_invoice_to_customer'] = 1;
        } else {
            $data['send_invoice_to_customer'] = 0;
        }

        if (isset($data['repeat_every']) && $data['repeat_every'] != '') {
            $data['recurring'] = 1;
            if ($data['repeat_every'] == 'custom') {
                $data['repeat_every']     = $data['repeat_every_custom'];
                $data['recurring_type']   = $data['repeat_type_custom'];
                $data['custom_recurring'] = 1;
            } else {
                $_temp                    = explode('-', $data['repeat_every']);
                $data['recurring_type']   = $_temp[1];
                $data['repeat_every']     = $_temp[0];
                $data['custom_recurring'] = 0;
            }
        } else {
            $data['recurring'] = 0;
        }
        unset($data['repeat_type_custom']);
        unset($data['repeat_every_custom']);

        if ((isset($data['project_id']) && $data['project_id'] == '') || !isset($data['project_id'])) {
            $data['project_id'] = 0;
        }

        // Store "not selected" as NULL rather than 0 so reports can distinguish it
        if (array_key_exists('cost_center', $data) && !is_numeric($data['cost_center'])) {
            $data['cost_center'] = null;
        }

        $data['addedfrom'] = get_staff_user_id();
        $data['dateadded'] = date('Y-m-d H:i:s');

        $data = hooks()->apply_filters('before_expense_added', $data);

        $this->db->insert(db_prefix() . 'expenses', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            if (isset($custom_fields)) {
                handle_custom_fields_post($insert_id, $custom_fields);
            }
            if (isset($data['project_id']) && !empty($data['project_id'])) {
                $this->load->model('projects_model');
                $project_settings = $this->projects_model->get_project_settings($data['project_id']);
                $visible_activity = 0;
                foreach ($project_settings as $s) {
                    if ($s['name'] == 'view_finance_overview') {
                        if ($s['value'] == 1) {
                            $visible_activity = 1;

                            break;
                        }
                    }
                }
                $expense                  = $this->get($insert_id);
                $activity_additional_data = $expense->name;
                $this->projects_model->log_activity($data['project_id'], 'project_activity_recorded_expense', $activity_additional_data, $visible_activity);
            }

            hooks()->do_action('after_expense_added', $insert_id);

            // Auto-create cheque_management record if payment mode is cheque
            if(!empty($data['paymentmode']) && !empty($data['cheque_number'])){
                $pm_check = $this->db->where('id', $data['paymentmode'])->get(db_prefix().'payment_modes')->row();
                if($pm_check && (stripos($pm_check->name, 'cheque') !== false || stripos($pm_check->name, 'check') !== false)){
                    // Get party name
                    $party_name = '';
                    $party_id = null;
                    $direction = 'vendor';
                    if(!empty($data['vendor'])){
                        $vend = $this->db->where('userid', $data['vendor'])->get(db_prefix().'pur_vendor')->row();
                        if($vend) $party_name = $vend->company;
                        $party_id = $data['vendor'];
                        $direction = 'vendor';
                    } elseif(!empty($data['clientid'])){
                        $cl = $this->db->where('userid', $data['clientid'])->get(db_prefix().'clients')->row();
                        if($cl) $party_name = $cl->company;
                        $party_id = $data['clientid'];
                        $direction = 'customer';
                    }

                    $cheque_date = !empty($data['cheque_date']) ? $data['cheque_date'] : null;
                    $is_pdc = (!empty($cheque_date) && $cheque_date > date('Y-m-d')) ? 1 : 0;

                    // One row per physical cheque, and the entry status comes from the
                    // lifecycle - see acc_cheque_register_upsert()
                    acc_cheque_register_upsert([
                        'cheque_type'      => 'outgoing',
                        'direction'        => $direction,
                        'source_type'      => 'expense',
                        'source_id'        => $insert_id,
                        'cheque_number'    => $data['cheque_number'],
                        'cheque_bank_name' => $data['cheque_bank_name'] ?? '',
                        'cheque_date'      => $cheque_date,
                        'amount'           => $data['amount'],
                        'rel_id'           => $party_id,
                        'rel_name'         => $party_name,
                        'invoice_ref'      => 'EXP-' . $insert_id,
                        'is_pdc'           => $is_pdc,
                    ]);
                }
            }

            // Before the ledger entries, so nothing downstream can read a half saved
            // expense. The header amount is not touched by this - it is whatever the
            // form posted, and the form posts the sum of these lines.
            $this->save_expense_items($insert_id, $items);

            $this->stamp_expense_invoice_number($insert_id, $is_credit_expense);
            $this->post_expense_ledger_entries($insert_id, $data, $is_credit_expense);
            $this->update_expense_payment_status($insert_id);

            log_activity('New Expense Added [' . $insert_id . ']');

            return $insert_id;
        }

        return false;
    }

    // =========================================================================
    // Expense payments
    //
    // A "Credit" expense is not settled when it is recorded - the vendor is owed
    // the amount and it sits on their accounts payable account. These methods let
    // it be paid off afterwards, following the same shape as purchase invoice
    // payments: soft deletes, a payment_status on the parent, and one balanced
    // ledger entry per payment.
    // =========================================================================

    /**
     * Whether an expense was bought on credit and therefore can be paid later.
     *
     * Keyed on the payment mode rather than the presence of an EXPINV number so it
     * still answers correctly for an expense saved before numbering existed.
     *
     * @param object|int $expense Expense row or id
     *
     * @return bool
     */
    public function is_credit_expense($expense)
    {
        $payment_mode   = $this->expense_field($expense, 'paymentmode');
        $credit_mode_id = (int) get_option('expense_credit_payment_mode');

        return $credit_mode_id > 0 && $payment_mode !== null && (int) $payment_mode === $credit_mode_id;
    }

    /**
     * Expense amount including both tax lines.
     *
     * The ledger entry credits amount + VAT, so the payable - and therefore what
     * needs paying - is the tax inclusive figure, not tblexpenses.amount.
     *
     * @param object|int $expense
     *
     * @return float
     */
    public function expense_total_with_tax($expense)
    {
        $amount = (float) $this->expense_field($expense, 'amount');

        // Round the VAT and then add, exactly as post_expense_ledger_entries() does
        // when it writes the credit leg. Rounding the sum instead could land a cent
        // away from the posted payable, leaving an expense that can never reach
        // fully paid.
        return round($amount + $this->expense_tax_total($expense), 2);
    }

    /**
     * Combined VAT on an expense across both tax lines.
     *
     * The single place this is calculated, so the ledger, the vendor statement and
     * the vendor expenses list can never disagree.
     *
     * @param object|array|int $expense Expense row or id
     *
     * @return float
     */
    public function expense_tax_total($expense)
    {
        $amount = (float) $this->expense_field($expense, 'amount');
        $rate1  = (float) $this->expense_field($expense, 'taxrate');
        $rate2  = (float) $this->expense_field($expense, 'taxrate2');

        return round(($amount / 100 * $rate1) + ($amount / 100 * $rate2), 2);
    }

    /**
     * Read a field off an expense however the caller happens to hold it.
     *
     * get() returns an object for a single id but an array per row for a list, and
     * both shapes reach these helpers - the vendor expenses tab passes array rows.
     *
     * @param object|array|int $expense
     * @param string           $field
     *
     * @return mixed|null
     */
    protected function expense_field($expense, $field)
    {
        if (is_numeric($expense)) {
            $expense = $this->get($expense);
        }

        if (is_array($expense)) {
            return $expense[$field] ?? null;
        }

        if (is_object($expense)) {
            return $expense->{$field} ?? null;
        }

        return null;
    }

    /**
     * Sum of live payments against an expense. Reversed payments are excluded.
     *
     * @param int $expense_id
     *
     * @return float
     */
    public function expense_total_paid($expense_id)
    {
        $this->db->select('COALESCE(SUM(amount),0) as total_paid', false);
        $this->db->where('expense_id', $expense_id);
        $this->db->where('is_deleted', 0);
        $row = $this->db->get(db_prefix() . 'expense_payments')->row();

        return $row ? round((float) $row->total_paid, 2) : 0.00;
    }

    /**
     * Outstanding balance on an expense. Never negative.
     *
     * @param object|int $expense
     *
     * @return float
     */
    public function expense_amount_left($expense)
    {
        $expense_id = $this->expense_field($expense, 'id');

        if (!$expense_id) {
            return 0.00;
        }

        $left = $this->expense_total_with_tax($expense) - $this->expense_total_paid($expense_id);

        return $left > 0 ? round($left, 2) : 0.00;
    }

    /**
     * Payments recorded against an expense, newest first.
     *
     * @param int  $expense_id
     * @param bool $include_deleted Include reversed payments
     *
     * @return array
     */
    public function get_expense_payments($expense_id, $include_deleted = false)
    {
        $this->db->select(
            db_prefix() . 'expense_payments.*, '
            . db_prefix() . 'payment_modes.name as payment_mode_name, '
            . db_prefix() . 'acc_accounts.name as gl_account_name, '
            . db_prefix() . 'acc_accounts.number as gl_account_number'
        );
        $this->db->from(db_prefix() . 'expense_payments');
        $this->db->join(db_prefix() . 'payment_modes', db_prefix() . 'payment_modes.id = ' . db_prefix() . 'expense_payments.paymentmode', 'left');
        $this->db->join(db_prefix() . 'acc_accounts', db_prefix() . 'acc_accounts.id = ' . db_prefix() . 'expense_payments.gl_account', 'left');
        $this->db->where(db_prefix() . 'expense_payments.expense_id', $expense_id);

        if (!$include_deleted) {
            $this->db->where(db_prefix() . 'expense_payments.is_deleted', 0);
        }

        $this->db->order_by(db_prefix() . 'expense_payments.date', 'desc');
        $this->db->order_by(db_prefix() . 'expense_payments.id', 'desc');

        return $this->db->get()->result_array();
    }

    /**
     * Single payment row, including reversed ones so callers can inspect history.
     *
     * @param int $id
     *
     * @return object|null
     */
    public function get_expense_payment($id)
    {
        $this->db->where('id', $id);

        return $this->db->get(db_prefix() . 'expense_payments')->row();
    }

    /**
     * Credit expenses that still owe something, newest first.
     *
     * The batch payment screen's row source. Mirrors
     * Purchase_model::get_unpaid_approve_invoices(): only documents with a balance
     * left, with the vendor joined on so the list can be filtered by vendor.
     *
     * A credit expense is identified by its payment mode, not by expinv_number -
     * see is_credit_expense(). The mode is compared in SQL here so the whole list
     * comes back in one query rather than one per row.
     *
     * @return array Expense rows with vendor_company and amount_left attached
     */
    public function get_unpaid_credit_expenses()
    {
        $credit_mode_id = (int) get_option('expense_credit_payment_mode');

        if ($credit_mode_id <= 0) {
            // Nothing is bought on credit until that mode is configured, so there is
            // nothing to batch pay.
            return [];
        }

        $this->db->select(
            db_prefix() . 'expenses.*, '
            . db_prefix() . 'expenses_categories.name as category_name, '
            . db_prefix() . 'currencies.name as currency_name, '
            . 'tax_1.taxrate as taxrate, tax_2.taxrate as taxrate2'
        );
        $this->db->join(db_prefix() . 'expenses_categories', db_prefix() . 'expenses_categories.id = ' . db_prefix() . 'expenses.category', 'left');
        $this->db->join(db_prefix() . 'currencies', db_prefix() . 'currencies.id = ' . db_prefix() . 'expenses.currency', 'left');
        $this->db->join(db_prefix() . 'taxes tax_1', 'tax_1.id = ' . db_prefix() . 'expenses.tax', 'left');
        $this->db->join(db_prefix() . 'taxes tax_2', 'tax_2.id = ' . db_prefix() . 'expenses.tax2', 'left');
        $this->db->where(db_prefix() . 'expenses.paymentmode', $credit_mode_id);
        $this->db->where('(' . db_prefix() . 'expenses.is_deleted = 0 OR ' . db_prefix() . 'expenses.is_deleted IS NULL)');

        // Staff without the global view capability only see their own, matching the
        // expenses list itself.
        if (staff_cant('view', 'expenses')) {
            $this->db->where(db_prefix() . 'expenses.addedfrom', get_staff_user_id());
        }

        $this->db->order_by(db_prefix() . 'expenses.date', 'DESC');

        $expenses = $this->db->get(db_prefix() . 'expenses')->result();

        // Vendor company names in one query rather than one per row
        $vendor_names = [];
        if ($this->db->table_exists(db_prefix() . 'pur_vendor')) {
            foreach ($this->db->select('userid, company')->get(db_prefix() . 'pur_vendor')->result() as $v) {
                $vendor_names[(int) $v->userid] = $v->company;
            }
        }

        $result = [];

        foreach ($expenses as $expense) {
            // expense_amount_left() works off the tax inclusive total, which is what
            // the ledger credited. Using tblexpenses.amount here would understate the
            // balance by the VAT and leave expenses that can never reach fully paid.
            $expense->amount_left = $this->expense_amount_left($expense);

            if ($expense->amount_left <= 0) {
                continue;
            }

            $expense->total_with_tax = $this->expense_total_with_tax($expense);
            $expense->vendor_company = isset($vendor_names[(int) $expense->vendor])
                ? $vendor_names[(int) $expense->vendor]
                : '';

            $result[] = $expense;
        }

        return $result;
    }

    /**
     * Record several expense payments in one submission.
     *
     * Each row goes through add_expense_payment(), so the overpayment guard, the
     * double entry, the cheque register and the payment status all behave exactly as
     * they do for a single payment. Nothing is duplicated here.
     *
     * Rows with no amount are skipped rather than rejected - the screen lists every
     * open expense and the user fills in only the ones being paid.
     *
     * @param array $data Posted form data with an 'expense' array of rows
     *
     * @return array ['count' => int, 'errors' => string[]]
     */
    public function add_batch_expense_payment($data)
    {
        $added  = 0;
        $errors = [];

        if (empty($data['expense']) || !is_array($data['expense'])) {
            return ['count' => 0, 'errors' => []];
        }

        foreach ($data['expense'] as $row) {
            if (empty($row['expense_id'])) {
                continue;
            }

            $amount = isset($row['amount']) ? round((float) $row['amount'], 2) : 0;

            if ($amount <= 0) {
                continue;
            }

            $expense = $this->get((int) $row['expense_id']);

            if (!$expense) {
                continue;
            }

            // Only a credit expense carries a payable. Anything else was settled when
            // it was recorded, so paying it again would double count.
            if (!$this->is_credit_expense($expense)) {
                $errors[] = $this->expense_label($expense) . ': ' . _l('expense_payment_not_on_credit');

                continue;
            }

            $left = $this->expense_amount_left($expense);

            if ($amount > $left) {
                $errors[] = $this->expense_label($expense) . ': '
                    . _l('expense_payment_exceeds_balance', app_format_money($left, $expense->currency_data));

                continue;
            }

            $payment = [
                'expense_id'    => (int) $row['expense_id'],
                'amount'        => $amount,
                'date'          => !empty($row['date']) ? $row['date'] : _d(date('Y-m-d')),
                'paymentmode'   => isset($row['paymentmode']) ? $row['paymentmode'] : '',
                'gl_account'    => isset($row['gl_account']) ? $row['gl_account'] : '',
                'transactionid' => isset($row['transactionid']) ? $row['transactionid'] : '',
                'note'          => isset($row['note']) ? $row['note'] : '',
            ];

            // Cheque details only for rows the form marked as a cheque mode
            if (!empty($row['is_cheque_row']) && $row['is_cheque_row'] == '1') {
                $payment['cheque_number']    = isset($row['cheque_number']) ? $row['cheque_number'] : '';
                $payment['cheque_bank_name'] = isset($row['cheque_bank_name']) ? $row['cheque_bank_name'] : '';
                $payment['cheque_date']      = isset($row['cheque_date']) ? $row['cheque_date'] : '';
            }

            if ($this->add_expense_payment($payment)) {
                $added++;
            } else {
                $errors[] = $this->expense_label($expense) . ': ' . _l('expense_payment_failed');
            }
        }

        return ['count' => $added, 'errors' => $errors];
    }

    /**
     * Every expense payment recorded under one transaction id.
     *
     * The single source for both the batch print preview and its PDF, so the two can
     * never disagree. transactionid is what groups a batch, exactly as it does on
     * tblpur_invoice_payment.
     *
     * @param string $transaction_id
     *
     * @return array Payment rows with the expense, vendor, mode and account resolved
     */
    public function get_expense_payments_by_transaction($transaction_id)
    {
        if ($transaction_id === null || $transaction_id === '') {
            return [];
        }

        if (!$this->db->table_exists(db_prefix() . 'expense_payments')) {
            return [];
        }

        $this->db->select(
            db_prefix() . 'expense_payments.*, '
            . db_prefix() . 'expenses.id as expense_id, '
            . db_prefix() . 'expenses.expinv_number, '
            . db_prefix() . 'expenses.expense_name, '
            . db_prefix() . 'expenses.date as expense_date, '
            . db_prefix() . 'expenses.amount as expense_amount, '
            . db_prefix() . 'expenses.payment_status, '
            . db_prefix() . 'expenses.vendor, '
            . db_prefix() . 'currencies.name as currency_name, '
            . db_prefix() . 'payment_modes.name as payment_mode_name, '
            . 'tax_1.taxrate as taxrate, tax_2.taxrate as taxrate2, '
            . 'CONCAT(acc.number, " - ", acc.name) as paid_from_name'
        );
        $this->db->join(db_prefix() . 'expenses', db_prefix() . 'expenses.id = ' . db_prefix() . 'expense_payments.expense_id');
        $this->db->join(db_prefix() . 'currencies', db_prefix() . 'currencies.id = ' . db_prefix() . 'expenses.currency', 'left');
        $this->db->join(db_prefix() . 'payment_modes', db_prefix() . 'payment_modes.id = ' . db_prefix() . 'expense_payments.paymentmode', 'left');
        $this->db->join(db_prefix() . 'taxes tax_1', 'tax_1.id = ' . db_prefix() . 'expenses.tax', 'left');
        $this->db->join(db_prefix() . 'taxes tax_2', 'tax_2.id = ' . db_prefix() . 'expenses.tax2', 'left');
        $this->db->join(db_prefix() . 'acc_accounts acc', 'acc.id = ' . db_prefix() . 'expense_payments.gl_account', 'left');
        $this->db->where(db_prefix() . 'expense_payments.transactionid', $transaction_id);
        $this->db->where(db_prefix() . 'expense_payments.is_deleted', 0);
        $this->db->where('(' . db_prefix() . 'expenses.is_deleted = 0 OR ' . db_prefix() . 'expenses.is_deleted IS NULL)');

        if (staff_cant('view', 'expenses')) {
            $this->db->where(db_prefix() . 'expenses.addedfrom', get_staff_user_id());
        }

        $this->db->order_by(db_prefix() . 'expense_payments.id', 'ASC');

        $rows = $this->db->get(db_prefix() . 'expense_payments')->result();

        // Vendor names in one query rather than one per row
        $vendor_names = [];
        if ($this->db->table_exists(db_prefix() . 'pur_vendor')) {
            foreach ($this->db->select('userid, company')->get(db_prefix() . 'pur_vendor')->result() as $v) {
                $vendor_names[(int) $v->userid] = $v->company;
            }
        }

        foreach ($rows as $row) {
            $row->vendor_company = isset($vendor_names[(int) $row->vendor])
                ? $vendor_names[(int) $row->vendor]
                : '';

            // Tax inclusive, because that is what was owed and what the payment is
            // being measured against.
            $row->expense_total = $this->expense_total_with_tax($row);
        }

        return $rows;
    }

    /**
     * Short human label for an expense, used in batch error messages.
     *
     * @param object $expense
     *
     * @return string
     */
    protected function expense_label($expense)
    {
        if (!empty($expense->expinv_number)) {
            return $expense->expinv_number;
        }

        return !empty($expense->expense_name) ? $expense->expense_name : '#' . $expense->id;
    }

    /**
     * Record a payment against an expense.
     *
     * Rejects anything that would overpay, so the payable can never go negative
     * through this path.
     *
     * @param array $data Posted payment fields, must include expense_id and amount
     *
     * @return int|false Payment id
     */
    public function add_expense_payment($data)
    {
        $expense_id = isset($data['expense_id']) ? (int) $data['expense_id'] : 0;
        $expense    = $expense_id ? $this->get($expense_id) : null;

        if (!$expense) {
            return false;
        }

        $amount = isset($data['amount']) ? round((float) $data['amount'], 2) : 0;
        $left   = $this->expense_amount_left($expense);

        if ($amount <= 0 || $left <= 0 || $amount > $left) {
            return false;
        }

        $insert = [
            'expense_id'       => $expense_id,
            'amount'           => $amount,
            'paymentmode'      => !empty($data['paymentmode']) ? (int) $data['paymentmode'] : null,
            'gl_account'       => !empty($data['gl_account']) ? (int) $data['gl_account'] : null,
            'date'             => !empty($data['date']) ? to_sql_date($data['date']) : date('Y-m-d'),
            'transactionid'    => isset($data['transactionid']) ? $data['transactionid'] : null,
            'note'             => isset($data['note']) ? nl2br($data['note']) : null,
            'cheque_number'    => !empty($data['cheque_number']) ? $data['cheque_number'] : null,
            'cheque_bank_name' => !empty($data['cheque_bank_name']) ? $data['cheque_bank_name'] : null,
            'cheque_date'      => !empty($data['cheque_date']) ? to_sql_date($data['cheque_date']) : null,
            'daterecorded'     => date('Y-m-d H:i:s'),
            'addedfrom'        => get_staff_user_id(),
            'is_deleted'       => 0,
        ];

        // Cheque details only belong on a cheque payment
        if (!empty($insert['paymentmode'])) {
            $this->db->where('id', $insert['paymentmode']);
            $mode = $this->db->get(db_prefix() . 'payment_modes')->row();
            if (!$mode || (stripos($mode->name, 'cheque') === false && stripos($mode->name, 'check') === false)) {
                $insert['cheque_number']    = null;
                $insert['cheque_bank_name'] = null;
                $insert['cheque_date']      = null;
            }
        }

        $this->db->insert(db_prefix() . 'expense_payments', $insert);
        $payment_id = $this->db->insert_id();

        if (!$payment_id) {
            return false;
        }

        $this->post_expense_payment_ledger_entries($payment_id);
        $this->register_expense_payment_cheque($payment_id, $expense);
        $this->update_expense_payment_status($expense_id);

        log_activity('Expense Payment Added [Expense: ' . $expense_id . ', Payment: ' . $payment_id . ']');

        hooks()->do_action('after_expense_payment_added', $payment_id);

        return $payment_id;
    }

    /**
     * Track a cheque paid against an expense in the central cheque register.
     *
     * Without this the cheque would exist only on the payment row and would be
     * invisible to the cheque management screens, and delete_expense_payment()
     * would have nothing to cancel. Mirrors what add() does for a cheque expense.
     *
     * @param int    $payment_id
     * @param object $expense
     *
     * @return bool True when a cheque row was created
     */
    protected function register_expense_payment_cheque($payment_id, $expense)
    {
        if (!$this->db->table_exists(db_prefix() . 'cheque_management')) {
            return false;
        }

        $payment = $this->get_expense_payment($payment_id);

        if (!$payment || empty($payment->cheque_number)) {
            return false;
        }

        $party_name = '';
        $party_id   = null;
        $direction  = 'vendor';

        if (!empty($expense->vendor)) {
            $this->db->where('userid', $expense->vendor);
            $vendor = $this->db->get(db_prefix() . 'pur_vendor')->row();
            if ($vendor) {
                $party_name = $vendor->company;
            }
            $party_id = $expense->vendor;
        } elseif (!empty($expense->clientid)) {
            $this->db->where('userid', $expense->clientid);
            $client = $this->db->get(db_prefix() . 'clients')->row();
            if ($client) {
                $party_name = $client->company;
            }
            $party_id  = $expense->clientid;
            $direction = 'customer';
        }

        // Post dated when the cheque date is in the future
        $is_pdc = (!empty($payment->cheque_date) && $payment->cheque_date > date('Y-m-d')) ? 1 : 0;

        // One row per physical cheque. A batch payment settles many expenses with a single
        // cheque, so paying 14 expenses used to put the same cheque on the register 14
        // times, each row holding one expense's slice. The helper adds this expense's
        // amount to the cheque already there instead, and picks the entry status from the
        // lifecycle rather than hardcoding 1, which is the incoming entry point and left
        // outgoing cheques unable to move at all.
        return (bool) acc_cheque_register_upsert([
            'cheque_type'      => 'outgoing',
            'direction'        => $direction,
            'source_type'      => 'expense_payment',
            'source_id'        => $payment_id,
            'cheque_number'    => $payment->cheque_number,
            'cheque_bank_name' => $payment->cheque_bank_name ?? '',
            'cheque_date'      => $payment->cheque_date,
            'amount'           => $payment->amount,
            'rel_id'           => $party_id,
            'rel_name'         => $party_name,
            'invoice_ref'      => !empty($expense->expinv_number) ? $expense->expinv_number : 'EXP-' . $expense->id,
            'bank_account_id'  => $payment->gl_account,
            'is_pdc'           => $is_pdc,
        ]);
    }

    /**
     * Reverse a payment.
     *
     * Soft deleted rather than removed so the reversal is auditable, matching
     * purchase invoice payments. The ledger entry is removed outright because a
     * payment that never happened should not sit in the account register.
     *
     * @param int $id Payment id
     *
     * @return bool
     */
    public function delete_expense_payment($id)
    {
        $payment = $this->get_expense_payment($id);

        if (!$payment || $payment->is_deleted == 1) {
            return false;
        }

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'expense_payments', ['is_deleted' => 1]);

        if ($this->db->affected_rows() == 0) {
            return false;
        }

        $this->db->where('rel_id', $id);
        $this->db->where('rel_type', 'expense_payment');
        $this->db->delete(db_prefix() . 'acc_account_history');

        // Cancel the cheque this payment raised, if the purchase module owns one
        if ($this->db->table_exists(db_prefix() . 'cheque_management')) {
            $this->db->where('source_type', 'expense_payment');
            $this->db->where('source_id', $id);
            // 4 Cleared, 5 Bounced, 6 Cancelled - leave those alone, which also
            // makes this safe to run twice
            $this->db->where_not_in('status', [4, 5, 6]);
            $this->db->update(db_prefix() . 'cheque_management', ['status' => 6]);
        }

        $this->update_expense_payment_status($payment->expense_id);

        log_activity('Expense Payment Reversed [Expense: ' . $payment->expense_id . ', Payment: ' . $id . ']');

        hooks()->do_action('after_expense_payment_deleted', $id);

        return true;
    }

    /**
     * Recalculate and store unpaid / partially_paid / paid on the expense.
     *
     * @param int $expense_id
     *
     * @return string The stored status
     */
    public function update_expense_payment_status($expense_id)
    {
        $expense = $this->get($expense_id);

        if (!$expense) {
            return '';
        }

        // Anything not bought on credit was settled the moment it was recorded -
        // its ledger entry already credited bank or cash.
        if (!$this->is_credit_expense($expense)) {
            $status = 'paid';
        } else {
            $total = $this->expense_total_with_tax($expense);
            $paid  = $this->expense_total_paid($expense_id);

            if ($paid <= 0) {
                $status = 'unpaid';
            } elseif (round($paid, 2) >= round($total, 2)) {
                $status = 'paid';
            } else {
                $status = 'partially_paid';
            }
        }

        $this->db->where('id', $expense_id);
        $this->db->update(db_prefix() . 'expenses', ['payment_status' => $status]);

        return $status;
    }

    /**
     * Double entry for an expense payment.
     *
     * Dr the payable the expense was booked against (clearing what is owed),
     * Cr the bank or cash account the money left. Mirrors the purchase payment
     * entry so the account register reads consistently.
     *
     * @param int $payment_id
     *
     * @return bool
     */
    protected function post_expense_payment_ledger_entries($payment_id)
    {
        $payment = $this->get_expense_payment($payment_id);

        if (!$payment) {
            return false;
        }

        $expense = $this->get($payment->expense_id);

        if (!$expense) {
            return false;
        }

        // The payable to clear is whatever the expense credited when it was
        // recorded. Falling back to the configured default keeps older expenses
        // payable even if their credit account was never stored.
        $payable_account = !empty($expense->credit_gl_account) && $expense->credit_gl_account > 0
            ? (int) $expense->credit_gl_account
            : $this->default_credit_expense_account($expense->vendor ?? null);

        $bank_account = $this->resolve_expense_payment_bank_account($payment);

        if ($payable_account <= 0 || $bank_account <= 0) {
            return false;
        }

        // Clear any previous entry so re-posting cannot double up
        $this->db->where('rel_id', $payment_id);
        $this->db->where('rel_type', 'expense_payment');
        $this->db->delete(db_prefix() . 'acc_account_history');

        $reference = 'EXPPAY' . str_pad($payment_id, 5, '0', STR_PAD_LEFT);

        $description = !empty($expense->expinv_number)
            ? _l('expense_payment_for', $expense->expinv_number)
            : _l('expense_payment_for', '#' . $expense->id);

        $vendor_id   = (!empty($expense->vendor) && is_numeric($expense->vendor)) ? $expense->vendor : null;
        $customer_id = (!empty($expense->clientid) && is_numeric($expense->clientid)) ? $expense->clientid : null;

        $shared = [
            'rel_id'      => $payment_id,
            'rel_type'    => 'expense_payment',
            'date'        => $payment->date,
            'datecreated' => date('Y-m-d H:i:s'),
            'addedfrom'   => get_staff_user_id(),
            'vendor'      => $vendor_id,
            'customer'    => $customer_id,
            'description' => $description,
            'number'      => $reference,
        ];

        $this->db->insert(db_prefix() . 'acc_account_history', array_merge($shared, [
            'account' => $payable_account,
            'debit'   => $payment->amount,
            'credit'  => 0,
            'split'   => $bank_account,
        ]));

        $this->db->insert(db_prefix() . 'acc_account_history', array_merge($shared, [
            'account' => $bank_account,
            'debit'   => 0,
            'credit'  => $payment->amount,
            'split'   => $payable_account,
        ]));

        return true;
    }

    /**
     * Which money account a payment left, in order of authority.
     *
     * 1. The account chosen on the form. An explicit choice always wins.
     * 2. The payment mode's mapping - tblacc_payment_mode_mappings.expense_deposit_to.
     *    This is what makes "pay by Bank Transfer" or "pay by Cash" post to the right
     *    account without the user picking one, and it is the same mapping
     *    post_expense_ledger_entries() uses for a non credit expense.
     * 3. The acc_expense_deposit_to option, as a last resort.
     *
     * Returning 0 means nothing is configured, and the caller declines to post
     * rather than guessing at an account.
     *
     * @param object $payment tblexpense_payments row
     *
     * @return int Account id, 0 when it cannot be established
     */
    protected function resolve_expense_payment_bank_account($payment)
    {
        if (!empty($payment->gl_account) && is_numeric($payment->gl_account) && $payment->gl_account > 0) {
            return (int) $payment->gl_account;
        }

        if (!empty($payment->paymentmode)
            && $this->db->table_exists(db_prefix() . 'acc_payment_mode_mappings')) {
            $this->db->where('payment_mode_id', $payment->paymentmode);
            $mapping = $this->db->get(db_prefix() . 'acc_payment_mode_mappings')->row();

            if ($mapping && !empty($mapping->expense_deposit_to) && $mapping->expense_deposit_to > 0) {
                return (int) $mapping->expense_deposit_to;
            }
        }

        $fallback = (int) get_option('acc_expense_deposit_to');

        return $fallback > 0 ? $fallback : 0;
    }

    /**
     * Bank and cash accounts a payment can be made from.
     *
     * Account types 3 (cash and cash equivalents) and 16 (bank) - the same types
     * the banking screens treat as real money accounts.
     *
     * @return array
     */
    public function get_expense_payment_accounts()
    {
        $this->db->select('id, CONCAT(number, " - ", name) as name');
        $this->db->where('active', 1);
        $this->db->where_in('account_type_id', [3, 16]);
        $this->db->order_by('number', 'ASC');

        return $this->db->get(db_prefix() . 'acc_accounts')->result_array();
    }

    /**
     * Account to credit for an expense bought on credit.
     *
     * The amount is owed to the vendor, so it belongs on that vendor's own accounts
     * payable account - the same account the purchase invoice and batch payment paths
     * post to, which keeps the vendor's A/P balance in one place. Falls back to the
     * expense_credit_gl_account option when the vendor has no account set or the
     * expense has no vendor at all.
     *
     * @param int|string|null $vendor_id
     *
     * @return int Account id, 0 when nothing is configured
     */
    protected function default_credit_expense_account($vendor_id = null)
    {
        if (!empty($vendor_id) && is_numeric($vendor_id)
            && $this->db->table_exists(db_prefix() . 'pur_vendor')
            && $this->db->field_exists('ledger_account', db_prefix() . 'pur_vendor')) {
            $this->db->select('ledger_account');
            $this->db->where('userid', $vendor_id);
            $vendor = $this->db->get(db_prefix() . 'pur_vendor')->row();

            if ($vendor && !empty($vendor->ledger_account) && $vendor->ledger_account > 0) {
                return (int) $vendor->ledger_account;
            }
        }

        return (int) get_option('expense_credit_gl_account');
    }

    /**
     * Issue (or clear) the vendor facing document number for a credit expense.
     *
     * Derived from the expense id so it can never collide, the same way shipping
     * charges number themselves ISC00001. The vendor statement keys on this number,
     * so it is cleared again if the expense stops being a credit expense - otherwise
     * a paid expense would keep showing as owed.
     *
     * @param int  $expense_id
     * @param bool $is_credit_expense
     *
     * @return string The number in force after the call, empty string if none
     */
    protected function stamp_expense_invoice_number($expense_id, $is_credit_expense)
    {
        $expinv_number = $is_credit_expense
            ? 'EXPINV' . str_pad($expense_id, 5, '0', STR_PAD_LEFT)
            : null;

        $this->db->where('id', $expense_id);
        $this->db->update(db_prefix() . 'expenses', ['expinv_number' => $expinv_number]);

        return (string) $expinv_number;
    }

    /**
     * Write the double entry for an expense.
     *
     * Shared by add() and update(). Existing rows for the expense are removed first
     * so an edit re-states the entry rather than leaving the original amounts behind.
     * The accounting module's own expense conversion is deliberately disabled (see
     * modules/accounting/accounting.php) - this is the only writer.
     *
     * @param int   $expense_id
     * @param array $data              Expense fields, as posted
     * @param bool  $is_credit_expense
     *
     * @return bool True when an entry was written
     */
    protected function post_expense_ledger_entries($expense_id, $data, $is_credit_expense)
    {
        $this->db->where('id', $expense_id);
        $expense_row = $this->db->get(db_prefix() . 'expenses')->row();

        if (!$expense_row) {
            return false;
        }

        // Fall back to the stored values for anything the caller did not supply.
        // Without this a partial update - one that does not post ledger_account or
        // amount - would delete the existing entry below and then have nothing to
        // rebuild it from, silently losing the double entry.
        foreach (['ledger_account', 'credit_gl_account', 'amount', 'vendor', 'clientid',
            'date', 'note', 'tax', 'tax2', 'paymentmode', 'expense_name', ] as $field) {
            if (!array_key_exists($field, $data) && property_exists($expense_row, $field)) {
                $data[$field] = $expense_row->{$field};
            }
        }

        // Nothing to post without a debit account. Leave whatever is already on the
        // ledger alone rather than deleting it - an entry that exists is better than
        // no entry at all, and this path means the caller had nothing to replace it
        // with.
        if (!isset($data['ledger_account']) || !is_numeric($data['ledger_account']) || $data['ledger_account'] <= 0) {
            return false;
        }

        // Reference shown in the account register / ledger reports. Every row must
        // carry one, otherwise the register shows a blank Number column.
        $gl_number = !empty($expense_row->expinv_number)
            ? $expense_row->expinv_number
            : 'EXP-' . $expense_id;

        // Clear any previous entry so an edit cannot leave stale amounts behind
        $this->db->where('rel_id', $expense_id);
        $this->db->where('rel_type', 'expense');
        $this->db->delete(db_prefix() . 'acc_account_history');

        $this->db->where('id', $expense_id);
        $this->db->update(db_prefix() . 'expenses', ['acc_mapping' => 0]);

        // Kept as $insert_id below so the entry building code reads identically to
        // the version this was extracted from
        $insert_id = $expense_id;

            // Auto-create accounting entry if ledger_account is selected
            if(isset($data['ledger_account']) && is_numeric($data['ledger_account']) && $data['ledger_account'] > 0){
                $expense_gl = $data['ledger_account']; // GL account to DEBIT (selected by user)
                
                // Determine CREDIT account:
                // 1. If credit_gl_account is explicitly selected (no customer/vendor), use it directly
                // 2. Otherwise, use payment mode mapping or default
                $credit_account = null;
                $use_credit_gl = false;
                
                // A credit expense always posts to its credit GL account, even when a
                // vendor is set - nothing has left cash or bank yet. Otherwise the
                // account is only used when there is no customer and no vendor.
                if(!empty($data['credit_gl_account']) && is_numeric($data['credit_gl_account']) && $data['credit_gl_account'] > 0
                   && ($is_credit_expense || (empty($data['clientid']) && empty($data['vendor'])))){
                    $credit_account = $data['credit_gl_account'];
                    $use_credit_gl = true;
                } else {
                    $paymentmode = isset($data['paymentmode']) ? $data['paymentmode'] : '';
                    if(!empty($paymentmode)){
                        $this->db->where('payment_mode_id', $paymentmode);
                        $mode_mapping = $this->db->get(db_prefix().'acc_payment_mode_mappings')->row();
                        if($mode_mapping && !empty($mode_mapping->expense_deposit_to)){
                            $credit_account = $mode_mapping->expense_deposit_to;
                        }
                    }
                    // Fallback to default
                    if(!$credit_account){
                        $credit_account = get_option('acc_expense_deposit_to');
                    }
                }
                
                if($credit_account && is_numeric($credit_account)){
                    $amount = (float)$data['amount'];
                    $vendor_id = (!empty($data['vendor']) && is_numeric($data['vendor'])) ? $data['vendor'] : null;
                    $customer_id = (!empty($data['clientid']) && is_numeric($data['clientid'])) ? $data['clientid'] : null;
                    $exp_date = isset($data['date']) ? $data['date'] : date('Y-m-d');
                    // Fall back to the expense name so the register never shows a blank
                    // description - the note is optional and usually empty.
                    $exp_desc = isset($data['note']) ? trim(strip_tags($data['note'])) : '';
                    if ($exp_desc === '') {
                        $exp_desc = !empty($data['expense_name']) ? $data['expense_name'] : _l('expense');
                    }

                    // Calculate VAT amount
                    $vat_amount = 0;
                    $vat_account = 124; // Default: 179020 - VAT Paid (purchase/expense VAT)
                    $tax_id_1 = isset($data['tax']) ? (int)$data['tax'] : 0;
                    $tax_id_2 = isset($data['tax2']) ? (int)$data['tax2'] : 0;

                    // One posting per VAT rate. Built up first so the header tax path and
                    // the line item path can share the insert loop further down.
                    $vat_postings = [];

                    // Line items carry their own tax, and when they do the header amount
                    // is the tax inclusive total while Tax 1 and Tax 2 are cleared - see
                    // save_expense_items(). Without reading the lines here the VAT never
                    // reaches the VAT account and never appears on the VAT report, and
                    // the whole gross amount lands in the expense account.
                    //
                    // Grouped by rate so an expense mixing rates - a vatable line and a
                    // zero rated one, which is the common case - posts each rate against
                    // its own tax record rather than lumping them together.
                    if($tax_id_1 == 0 && $tax_id_2 == 0 && $this->db->table_exists(db_prefix().'expense_items')){
                        $line_vat_query = $this->db->query(
                            'SELECT tax_percent, ROUND(SUM(tax_amount), 2) AS vat_amount
                             FROM '.db_prefix().'expense_items
                             WHERE expense_id = '.$this->db->escape($insert_id).'
                               AND tax_amount > 0
                             GROUP BY tax_percent'
                        );

                        if($line_vat_query !== false){
                            foreach($line_vat_query->result_array() as $line_vat_row){
                                $rate_vat = (float)$line_vat_row['vat_amount'];

                                if($rate_vat <= 0){
                                    continue;
                                }

                                // Match the line's percentage back to a tax record so the
                                // ledger row carries a tax id and honours any account
                                // mapping configured for that tax.
                                $rate_tax_id  = 0;
                                $rate_account = $vat_account;

                                $rate_tax_query = $this->db->query(
                                    'SELECT id FROM '.db_prefix().'taxes
                                     WHERE taxrate = '.$this->db->escape($line_vat_row['tax_percent']).'
                                     ORDER BY id ASC LIMIT 1'
                                );

                                if($rate_tax_query !== false){
                                    $rate_tax_row = $rate_tax_query->row();
                                    if($rate_tax_row){
                                        $rate_tax_id = (int)$rate_tax_row->id;

                                        $this->db->where('tax_id', $rate_tax_id);
                                        $rate_map = $this->db->get(db_prefix().'acc_tax_mappings')->row();
                                        if($rate_map && !empty($rate_map->expense_payment_account)){
                                            $rate_account = $rate_map->expense_payment_account;
                                        }
                                    }
                                }

                                $vat_postings[] = [
                                    'account' => $rate_account,
                                    'amount'  => $rate_vat,
                                    'tax_id'  => $rate_tax_id,
                                ];

                                $vat_amount += $rate_vat;
                            }
                        }
                    }

                    $vat_from_line_items = count($vat_postings) > 0;

                    if(!$vat_from_line_items){
                        if($tax_id_1 > 0){
                            $this->db->where('id', $tax_id_1);
                            $tax_row = $this->db->get(db_prefix().'taxes')->row();
                            if($tax_row){
                                $vat_amount += ($amount * $tax_row->taxrate / 100);
                            }
                            // Check tax mapping for VAT account
                            $this->db->where('tax_id', $tax_id_1);
                            $tax_map = $this->db->get(db_prefix().'acc_tax_mappings')->row();
                            if($tax_map && !empty($tax_map->expense_payment_account)){
                                $vat_account = $tax_map->expense_payment_account;
                            }
                        }
                        if($tax_id_2 > 0){
                            $this->db->where('id', $tax_id_2);
                            $tax_row2 = $this->db->get(db_prefix().'taxes')->row();
                            if($tax_row2){
                                $vat_amount += ($amount * $tax_row2->taxrate / 100);
                            }
                        }
                    }

                    $vat_amount = round($vat_amount, 2);

                    if($vat_from_line_items){
                        // The stored amount is already tax inclusive, so the expense
                        // account takes the net and the credit stays at the gross the
                        // vendor billed - which is what the vendor statement and the AP
                        // balance already show for this expense.
                        $expense_debit  = round($amount - $vat_amount, 2);
                        $total_with_vat = $amount;
                    }else{
                        $expense_debit  = $amount;
                        $total_with_vat = $amount + $vat_amount;

                        if($vat_amount > 0){
                            $vat_postings[] = [
                                'account' => $vat_account,
                                'amount'  => $vat_amount,
                                'tax_id'  => $tax_id_1,
                            ];
                        }
                    }

                    if($customer_id){
                        // Customer expense: Debit selected GL + VAT, Credit Cash/Bank (total)
                        $this->db->insert(db_prefix().'acc_account_history', [
                            'account' => $expense_gl,
                            'debit' => $expense_debit,
                            'credit' => 0,
                            'rel_id' => $insert_id,
                            'rel_type' => 'expense',
                            'date' => $exp_date,
                            'datecreated' => date('Y-m-d H:i:s'),
                            'addedfrom' => get_staff_user_id(),
                            'customer' => $customer_id,
                            'description' => $exp_desc,
                            'number' => $gl_number,
                            'split' => $credit_account,
                        ]);
                        $this->db->insert(db_prefix().'acc_account_history', [
                            'account' => $credit_account,
                            'debit' => 0,
                            'credit' => $total_with_vat,
                            'rel_id' => $insert_id,
                            'rel_type' => 'expense',
                            'date' => $exp_date,
                            'datecreated' => date('Y-m-d H:i:s'),
                            'addedfrom' => get_staff_user_id(),
                            'customer' => $customer_id,
                            'description' => $exp_desc,
                            'number' => $gl_number,
                            'split' => $expense_gl,
                        ]);
                    } else {
                        // Vendor or general expense: Debit selected GL + VAT, Credit Cash/Bank (total)
                        $this->db->insert(db_prefix().'acc_account_history', [
                            'account' => $expense_gl,
                            'debit' => $expense_debit,
                            'credit' => 0,
                            'rel_id' => $insert_id,
                            'rel_type' => 'expense',
                            'date' => $exp_date,
                            'datecreated' => date('Y-m-d H:i:s'),
                            'addedfrom' => get_staff_user_id(),
                            'vendor' => $vendor_id,
                            'description' => $exp_desc,
                            'number' => $gl_number,
                            'split' => $credit_account,
                        ]);
                        $this->db->insert(db_prefix().'acc_account_history', [
                            'account' => $credit_account,
                            'debit' => 0,
                            'credit' => $total_with_vat,
                            'rel_id' => $insert_id,
                            'rel_type' => 'expense',
                            'date' => $exp_date,
                            'datecreated' => date('Y-m-d H:i:s'),
                            'addedfrom' => get_staff_user_id(),
                            'vendor' => $vendor_id,
                            'description' => $exp_desc,
                            'number' => $gl_number,
                            'split' => $expense_gl,
                        ]);
                    }

                    // VAT entry: Dr VAT Input, Cr Cash/Bank (already included in total credit above)
                    // One row per rate, so a mixed rate expense is auditable rate by rate.
                    foreach($vat_postings as $vat_posting){
                        if($vat_posting['amount'] <= 0){
                            continue;
                        }

                        $this->db->insert(db_prefix().'acc_account_history', [
                            'account' => $vat_posting['account'],
                            'debit' => $vat_posting['amount'],
                            'credit' => 0,
                            'rel_id' => $insert_id,
                            'rel_type' => 'expense',
                            'tax' => $vat_posting['tax_id'],
                            'date' => $exp_date,
                            'datecreated' => date('Y-m-d H:i:s'),
                            'addedfrom' => get_staff_user_id(),
                            'vendor' => $vendor_id,
                            'customer' => $customer_id,
                            'description' => 'VAT on expense',
                            'number' => $gl_number,
                            'split' => $credit_account,
                        ]);
                    }

                    // Mark expense as converted
                    $this->db->where('id', $insert_id);
                    $this->db->update(db_prefix().'expenses', ['acc_mapping' => 1]);

                    return true;
                }
            }

        return false;
    }

    public function get_child_expenses($id)
    {
        $this->db->select('id');
        $this->db->where('recurring_from', $id);
        $expenses = $this->db->get(db_prefix() . 'expenses')->result_array();

        $_expenses = [];
        foreach ($expenses as $expense) {
            $_expenses[] = $this->get($expense['id']);
        }

        return $_expenses;
    }

    public function get_expenses_total($data)
    {
        $this->load->model('currencies_model');
        $base_currency     = $this->currencies_model->get_base_currency()->id;
        $base              = true;
        $currency_switcher = false;
        if (isset($data['currency'])) {
            $currencyid        = $data['currency'];
            $currency_switcher = true;
        } elseif (isset($data['customer_id']) && $data['customer_id'] != '') {
            $currencyid = $this->clients_model->get_customer_default_currency($data['customer_id']);
            if ($currencyid == 0) {
                $currencyid = $base_currency;
            } else {
                if (total_rows(db_prefix() . 'expenses', [
                    'currency' => $base_currency,
                    'clientid' => $data['customer_id'],
                ])) {
                    $currency_switcher = true;
                }
            }
        } elseif (isset($data['project_id']) && $data['project_id'] != '') {
            $this->load->model('projects_model');
            $currencyid = $this->projects_model->get_currency($data['project_id'])->id;
        } else {
            $currencyid = $base_currency;
            if (total_rows(db_prefix() . 'expenses', [
                'currency !=' => $base_currency,
            ])) {
                $currency_switcher = true;
            }
        }

        $currency = get_currency($currencyid);

        $has_permission_view = staff_can('view',  'expenses');
        $_result             = [];

        for ($i = 1; $i <= 5; $i++) {
            $this->db->select('amount,tax,tax2,invoiceid');
            $this->db->where('currency', $currencyid);

            if (isset($data['years']) && count($data['years']) > 0) {
                $this->db->where('YEAR(date) IN (' . implode(', ', array_map(function ($year) {
                    return get_instance()->db->escape_str($year);
                }, $data['years'])) . ')');
            } else {
                $this->db->where('YEAR(date) = ' . date('Y'));
            }
            if (isset($data['customer_id']) && $data['customer_id'] != '') {
                $this->db->where('clientid', $data['customer_id']);
            }
            if (isset($data['project_id']) && $data['project_id'] != '') {
                $this->db->where('project_id', $data['project_id']);
            }

            if (!$has_permission_view) {
                $this->db->where('addedfrom', get_staff_user_id());
            }
            switch ($i) {
                case 1:
                    $key = 'all';

                    break;
                case 2:
                    $key = 'billable';
                    $this->db->where('billable', 1);

                    break;
                case 3:
                    $key = 'non_billable';
                    $this->db->where('billable', 0);

                    break;
                case 4:
                    $key = 'billed';
                    $this->db->where('billable', 1);
                    $this->db->where('invoiceid IS NOT NULL');
                    $this->db->where('invoiceid IN (SELECT invoiceid FROM ' . db_prefix() . 'invoices WHERE status=2 AND id=' . db_prefix() . 'expenses.invoiceid)');

                    break;
                case 5:
                    $key = 'unbilled';
                    $this->db->where('billable', 1);
                    $this->db->where('invoiceid IS NULL');

                    break;
            }
            $all_expenses = $this->db->get(db_prefix() . 'expenses')->result_array();
            $_total_all   = [];
            $cached_taxes = [];
            foreach ($all_expenses as $expense) {
                $_total = $expense['amount'];
                if ($expense['tax'] != 0) {
                    if (!isset($cached_taxes[$expense['tax']])) {
                        $tax                           = get_tax_by_id($expense['tax']);
                        $cached_taxes[$expense['tax']] = $tax;
                    } else {
                        $tax = $cached_taxes[$expense['tax']];
                    }
                    $_total += ($_total / 100 * $tax->taxrate);
                }
                if ($expense['tax2'] != 0) {
                    if (!isset($cached_taxes[$expense['tax2']])) {
                        $tax                            = get_tax_by_id($expense['tax2']);
                        $cached_taxes[$expense['tax2']] = $tax;
                    } else {
                        $tax = $cached_taxes[$expense['tax2']];
                    }
                    $_total += ($expense['amount'] / 100 * $tax->taxrate);
                }
                array_push($_total_all, $_total);
            }
            $_result[$key]['total'] = app_format_money(array_sum($_total_all), $currency);
        }
        $_result['currency_switcher'] = $currency_switcher;
        $_result['currencyid']        = $currencyid;

        // All expenses
        /*   $this->db->select('amount,tax');
        $this->db->where('currency',$currencyid);


        $this->db->select('amount,tax,invoiceid');
        $this->db->where('currency',$currencyid);
        $this->db->where('billable',1);
        $this->db->where('invoiceid IS NOT NULL');

        $all_expenses = $this->db->get(db_prefix().'expenses')->result_array();
        $_total_all = array();
        foreach($all_expenses as $expense){
        $_total = 0;
        if(total_rows(db_prefix().'invoices',array('status'=>2,'id'=>$expense['invoiceid'])) > 0){
        $_total = $expense['amount'];
        if($expense['tax'] != 0){
        $tax = get_tax_by_id($expense['tax']);
        $_total += ($_total / 100 * $tax->taxrate);
        }
        }
        array_push($_total_all,$_total);
        }
        $_result['billed']['total'] = app_format_money(array_sum($_total_all), $currency);

        $this->db->select('amount,tax,invoiceid');
        $this->db->where('currency',$currencyid);
        $this->db->where('billable',1);
        $this->db->where('invoiceid IS NOT NULL');

        $all_expenses = $this->db->get(db_prefix().'expenses')->result_array();
        $_total_all = array();
        foreach($all_expenses as $expense){
        $_total = 0;
        if(total_rows(db_prefix().'invoices','status NOT IN(2,5) AND id ='.$expense['invoiceid']) > 0){
        echo $this->db->last_query();
        $_total = $expense['amount'];
        if($expense['tax'] != 0){
        $tax = get_tax_by_id($expense['tax']);
        $_total += ($_total / 100 * $tax->taxrate);
        }
        }
        array_push($_total_all,$_total);
        }
        $_result['unbilled']['total'] = app_format_money(array_sum($_total_all), $currency);*/

        return $_result;
    }

    /**
     * Update expense
     * @param  mixed $data All $_POST data
     * @param  mixed $id   expense id to update
     * @return boolean
     */
    public function update($data, $id)
    {
        $original_expense = $this->get($id);

        $data['date'] = to_sql_date($data['date']);
        $data['note'] = nl2br($data['note']);

        // Line items live in their own table, so they come out of the payload before
        // the update. See add() for the same handling.
        $items = $this->extract_items_from_post($data);
        $this->save_expense_items($id, $items);

        // Handle cheque date conversion
        if(isset($data['cheque_date']) && !empty($data['cheque_date'])){
            $data['cheque_date'] = to_sql_date($data['cheque_date']);
        } else {
            $data['cheque_date'] = null;
        }
        // Clear cheque fields if payment mode is not cheque
        if(!empty($data['paymentmode'])){
            $this->db->where('id', $data['paymentmode']);
            $_pm = $this->db->get(db_prefix().'payment_modes')->row();
            if(!$_pm || (stripos($_pm->name, 'cheque') === false && stripos($_pm->name, 'check') === false)){
                $data['cheque_number'] = null;
                $data['cheque_bank_name'] = null;
                $data['cheque_date'] = null;
            }
        }

        // Same credit expense handling as add(). Switching an existing expense onto
        // the Credit mode has to issue the EXPINV number and move the credit side off
        // cash/bank, otherwise the edit would leave the original bank entry in place.
        // Falls back to the stored mode so an update that does not post paymentmode
        // cannot silently strip the EXPINV number off a credit expense.
        $credit_mode_id     = (int) get_option('expense_credit_payment_mode');
        $effective_pay_mode = array_key_exists('paymentmode', $data)
            ? $data['paymentmode']
            : ($original_expense->paymentmode ?? null);
        $is_credit_expense = $credit_mode_id > 0
            && $effective_pay_mode !== null
            && (int) $effective_pay_mode === $credit_mode_id;

        if ($is_credit_expense && empty($data['credit_gl_account'])) {
            $default_credit_gl = $this->default_credit_expense_account(
                $data['vendor'] ?? ($original_expense->vendor ?? null)
            );
            if ($default_credit_gl > 0) {
                $data['credit_gl_account'] = $default_credit_gl;
            }
        }

        // Recurring expense set to NO, Cancelled
        if ($original_expense->repeat_every != '' && ($data['repeat_every'] ?? '') == '') {
            $data['cycles']              = 0;
            $data['total_cycles']        = 0;
            $data['last_recurring_date'] = null;
        }

        if (isset($data['repeat_every']) && $data['repeat_every'] != '') {
            $data['recurring'] = 1;
            if ($data['repeat_every'] == 'custom') {
                $data['repeat_every']     = $data['repeat_every_custom'];
                $data['recurring_type']   = $data['repeat_type_custom'];
                $data['custom_recurring'] = 1;
            } else {
                $_temp                    = explode('-', $data['repeat_every']);
                $data['recurring_type']   = $_temp[1];
                $data['repeat_every']     = $_temp[0];
                $data['custom_recurring'] = 0;
            }
        } else {
            $data['recurring'] = 0;
        }

        $data['cycles'] = !isset($data['cycles']) || $data['recurring'] == 0 ? 0 : $data['cycles'];

        unset($data['repeat_type_custom']);
        unset($data['repeat_every_custom']);

        $data['create_invoice_billable']  = array_key_exists('create_invoice_billable', $data) ? 1 : 0;
        $data['billable']                 = array_key_exists('billable', $data) ? 1 : 0;
        $data['send_invoice_to_customer'] = array_key_exists('send_invoice_to_customer', $data) ? 1 : 0;

        if (isset($data['project_id']) && $data['project_id'] == '' || !isset($data['project_id'])) {
            $data['project_id'] = 0;
        }

        // Store "not selected" as NULL rather than 0 so reports can distinguish it
        if (array_key_exists('cost_center', $data) && !is_numeric($data['cost_center'])) {
            $data['cost_center'] = null;
        }

        $updated       = false;
        $data          = hooks()->apply_filters('before_expense_updated', $data, $id);
        $custom_fields = Arr::pull($data, 'custom_fields') ?? [];

        if (handle_custom_fields_post($id, $custom_fields)) {
            $updated = true;
        }

        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'expenses', $data);

        if ($this->db->affected_rows() > 0) {
            $updated = true;
        }

        // Re-state the double entry from the saved values. Run unconditionally rather
        // than only when affected_rows changed, because an edit that leaves the expense
        // fields untouched can still need re-posting - for example when the default
        // credit account has been repointed since the expense was first recorded.
        $this->stamp_expense_invoice_number($id, $is_credit_expense);
        $this->post_expense_ledger_entries($id, $data, $is_credit_expense);
        $this->update_expense_payment_status($id);

        do_action_deprecated('after_expense_updated', [$id], '2.9.4', 'expense_updated');

        hooks()->do_action('expense_updated', [
            'id'            => $id,
            'data'          => $data,
            'custom_fields' => $custom_fields,
            'updated'       => &$updated,
        ]);

        if ($updated) {
            log_activity('Expense Updated [' . $id . ']');
        }

        return $updated;
    }

    /**
     * @param  integer ID
     * @return mixed
     * Delete expense from database, if used return
     */
    public function delete($id, $simpleDelete = false)
    {
        $_expense = $this->get($id);

        if ($_expense->invoiceid !== null && $simpleDelete == false) {
            return [
                'invoiced' => true,
            ];
        }

        // Soft delete — mark as deleted, keep record in DB
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'expenses', [
            'is_deleted' => 1,
            'date_deleted' => date('Y-m-d H:i:s'),
        ]);

        if ($this->db->affected_rows() > 0) {
            log_activity('Expense Soft-Deleted [' . $id . ']');

            // Reverse every payment made against it first. The accounting hook below
            // only clears rel_type 'expense' rows, so without this the payment side
            // of the entry would be left behind in the account register - the same
            // gap that left reversed purchase invoices sitting in the ledger.
            foreach ($this->get_expense_payments($id) as $expense_payment) {
                $this->delete_expense_payment($expense_payment['id']);
            }

            // Reverse accounting entries
            hooks()->do_action('after_expense_deleted', $id);

            return true;
        }

        return false;
    }

    /**
     * Convert expense to invoice
     * @param  mixed  $id   expense id
     * @return mixed
     */
    public function convert_to_invoice($id, $draft_invoice = false, $params = [])
    {
        $expense          = $this->get($id);
        $new_invoice_data = [];
        $client           = $this->clients_model->get($expense->clientid);

        if ($draft_invoice == true) {
            $new_invoice_data['save_as_draft'] = true;
        }
        $new_invoice_data['clientid'] = $expense->clientid;
        $new_invoice_data['number']   = get_option('next_invoice_number');
        $invoice_date                 = (isset($params['invoice_date']) ? $params['invoice_date'] : date('Y-m-d'));
        $new_invoice_data['date']     = _d($invoice_date);

        if (get_option('invoice_due_after') != 0) {
            $new_invoice_data['duedate'] = _d(date('Y-m-d', strtotime('+' . get_option('invoice_due_after') . ' DAY', strtotime($invoice_date))));
        }

        $new_invoice_data['show_quantity_as'] = 1;
        $new_invoice_data['terms']            = get_option('predefined_terms_invoice');
        $new_invoice_data['clientnote']       = get_option('predefined_clientnote_invoice');
        $new_invoice_data['discount_total']   = 0;
        $new_invoice_data['sale_agent']       = 0;
        $new_invoice_data['adjustment']       = 0;
        $new_invoice_data['project_id']       = $expense->project_id;

        $new_invoice_data['subtotal'] = $expense->amount;
        $total                        = $expense->amount;

        if ($expense->tax != 0) {
            $total += ($expense->amount / 100 * $expense->taxrate);
        }
        if ($expense->tax2 != 0) {
            $total += ($expense->amount / 100 * $expense->taxrate2);
        }

        $new_invoice_data['total']     = $total;
        $new_invoice_data['currency']  = $expense->currency;
        $new_invoice_data['status']    = 1;
        $new_invoice_data['adminnote'] = '';
        // Since version 1.0.6
        $new_invoice_data['billing_street']  = clear_textarea_breaks($client->billing_street);
        $new_invoice_data['billing_city']    = $client->billing_city;
        $new_invoice_data['billing_state']   = $client->billing_state;
        $new_invoice_data['billing_zip']     = $client->billing_zip;
        $new_invoice_data['billing_country'] = $client->billing_country;
        if (!empty($client->shipping_street)) {
            $new_invoice_data['shipping_street']          = clear_textarea_breaks($client->shipping_street);
            $new_invoice_data['shipping_city']            = $client->shipping_city;
            $new_invoice_data['shipping_state']           = $client->shipping_state;
            $new_invoice_data['shipping_zip']             = $client->shipping_zip;
            $new_invoice_data['shipping_country']         = $client->shipping_country;
            $new_invoice_data['include_shipping']         = 1;
            $new_invoice_data['show_shipping_on_invoice'] = 1;
        } else {
            $new_invoice_data['include_shipping']         = 0;
            $new_invoice_data['show_shipping_on_invoice'] = 1;
        }

        $this->load->model('payment_modes_model');
        $modes = $this->payment_modes_model->get('', [
            'expenses_only !=' => 1,
        ]);
        $temp_modes = [];
        foreach ($modes as $mode) {
            if ($mode['selected_by_default'] == 0) {
                continue;
            }
            $temp_modes[] = $mode['id'];
        }

        $new_invoice_data['billed_expenses'][1] = [
            $expense->expenseid,
        ];
        $new_invoice_data['allowed_payment_modes']           = $temp_modes;
        $new_invoice_data['newitems'][1]['description']      = _l('item_as_expense') . ' ' . $expense->name;
        $new_invoice_data['newitems'][1]['long_description'] = $expense->description;

        if (isset($params['include_note']) && $params['include_note'] == true && !empty($expense->note)) {
            $new_invoice_data['newitems'][1]['long_description'] .= PHP_EOL . $expense->note;
        }
        if (isset($params['include_name']) && $params['include_name'] == true && !empty($expense->expense_name)) {
            $new_invoice_data['newitems'][1]['long_description'] .= PHP_EOL . $expense->expense_name;
        }

        $new_invoice_data['newitems'][1]['unit']    = '';
        $new_invoice_data['newitems'][1]['qty']     = 1;
        $new_invoice_data['newitems'][1]['taxname'] = [];
        if ($expense->tax != 0) {
            $tax_data = get_tax_by_id($expense->tax);
            array_push($new_invoice_data['newitems'][1]['taxname'], $tax_data->name . '|' . $tax_data->taxrate);
        }
        if ($expense->tax2 != 0) {
            $tax_data = get_tax_by_id($expense->tax2);
            array_push($new_invoice_data['newitems'][1]['taxname'], $tax_data->name . '|' . $tax_data->taxrate);
        }

        $new_invoice_data['newitems'][1]['rate']  = $expense->amount;
        $new_invoice_data['newitems'][1]['order'] = 1;
        $this->load->model('invoices_model');

        $invoiceid = $this->invoices_model->add($new_invoice_data, true);
        if ($invoiceid) {
            $this->db->where('id', $expense->expenseid);
            $this->db->update(db_prefix() . 'expenses', [
                'invoiceid' => $invoiceid,
            ]);

            if (is_custom_fields_smart_transfer_enabled()) {
                $this->db->where('fieldto', 'expenses');
                $this->db->where('active', 1);
                $cfExpenses = $this->db->get(db_prefix() . 'customfields')->result_array();
                foreach ($cfExpenses as $field) {
                    $tmpSlug = explode('_', $field['slug'], 2);
                    if (isset($tmpSlug[1])) {
                        $this->db->where('fieldto', 'invoice');
                        $this->db->group_start();
                        $this->db->like('slug', 'invoice_' . $tmpSlug[1], 'after');
                        $this->db->where('type', $field['type']);
                        $this->db->where('options', $field['options']);
                        $this->db->where('active', 1);
                        $this->db->group_end();

                        $cfTransfer = $this->db->get(db_prefix() . 'customfields')->result_array();

                        // Don't make mistakes
                        // Only valid if 1 result returned
                        // + if field names similarity is equal or more then CUSTOM_FIELD_TRANSFER_SIMILARITY%
                        if (count($cfTransfer) == 1 && ((similarity($field['name'], $cfTransfer[0]['name']) * 100) >= CUSTOM_FIELD_TRANSFER_SIMILARITY)) {
                            $value = get_custom_field_value($id, $field['id'], 'expenses', false);
                            if ($value == '') {
                                continue;
                            }
                            $this->db->insert(db_prefix() . 'customfieldsvalues', [
                                'relid'   => $invoiceid,
                                'fieldid' => $cfTransfer[0]['id'],
                                'fieldto' => 'invoice',
                                'value'   => $value,
                            ]);
                        }
                    }
                }
            }

            log_activity('Expense Converted To Invoice [ExpenseID: ' . $expense->expenseid . ', InvoiceID: ' . $invoiceid . ']');

            hooks()->do_action('expense_converted_to_invoice', ['expense_id' => $expense->expenseid, 'invoice_id' => $invoiceid]);

            return $invoiceid;
        }

        return false;
    }

    /**
     * Copy expense
     * @param  mixed $id expense id to copy from
     * @return mixed
     */
    public function copy($id)
    {
        $expense_fields   = $this->db->list_fields(db_prefix() . 'expenses');
        $expense          = $this->get($id);
        $new_expense_data = [];
        foreach ($expense_fields as $field) {
            if (isset($expense->$field)) {
                // We dont need these fields.
                if ($field != 'invoiceid' && $field != 'id' && $field != 'recurring_from') {
                    $new_expense_data[$field] = $expense->$field;
                }
            }
        }
        $new_expense_data['addedfrom']           = get_staff_user_id();
        $new_expense_data['dateadded']           = date('Y-m-d H:i:s');
        $new_expense_data['last_recurring_date'] = null;
        $new_expense_data['total_cycles']        = 0;

        $this->db->insert(db_prefix() . 'expenses', $new_expense_data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            // Get the old expense custom field and add to the new
            $custom_fields = get_custom_fields('expenses');
            foreach ($custom_fields as $field) {
                $value = get_custom_field_value($id, $field['id'], 'expenses', false);
                if ($value == '') {
                    continue;
                }
                $this->db->insert(db_prefix() . 'customfieldsvalues', [
                    'relid'   => $insert_id,
                    'fieldid' => $field['id'],
                    'fieldto' => 'expenses',
                    'value'   => $value,
                ]);
            }
            log_activity('Expense Copied [ExpenseID' . $id . ', NewExpenseID: ' . $insert_id . ']');

            return $insert_id;
        }

        return false;
    }

    /**
     * Delete Expense attachment
     * @param  mixed $id expense id
     * @return boolean
     */
    public function delete_expense_attachment($id)
    {
        if (is_dir(get_upload_path_by_type('expense') . $id)) {
            if (delete_dir(get_upload_path_by_type('expense') . $id)) {
                $this->db->where('rel_id', $id);
                $this->db->where('rel_type', 'expense');
                $this->db->delete(db_prefix() . 'files');
                log_activity('Expense Receipt Deleted [ExpenseID: ' . $id . ']');

                return true;
            }
        }

        return false;
    }

    /* Categories start */

    /**
     * Get expense category
     * @param  mixed $id category id (Optional)
     * @return mixed     object or array
     */
    public function get_category($id = '')
    {
        if (is_numeric($id)) {
            $this->db->where('id', $id);

            return $this->db->get(db_prefix() . 'expenses_categories')->row();
        }
        $this->db->order_by('name', 'asc');

        return $this->db->get(db_prefix() . 'expenses_categories')->result_array();
    }

    /**
     * Add new expense category
     * @param mixed $data All $_POST data
     * @return boolean
     */
    /**
     * Pull the posted line items out of an expense payload.
     *
     * They belong in tblexpense_items, not in tblexpenses, so they have to be removed
     * before the insert or update - CI would otherwise try to write an `items` column.
     *
     * @param  array $data  the $_POST payload, modified in place
     * @return array        the raw posted rows, empty when none were sent
     */
    private function extract_items_from_post(&$data)
    {
        if (!isset($data['items'])) {
            return [];
        }

        $items = is_array($data['items']) ? $data['items'] : [];
        unset($data['items']);

        return $items;
    }

    /**
     * Replace an expense's line items with the posted set.
     *
     * Every figure is recomputed here rather than trusted from the form, so a hand
     * crafted post cannot store an amount that disagrees with its own qty and rate.
     * Blank rows are dropped - the form always keeps one empty row on screen.
     *
     * @param  int   $expense_id
     * @param  array $items
     * @return void
     */
    public function save_expense_items($expense_id, $items)
    {
        // db_debug is off in production, so a missing table returns false rather than
        // throwing. Guarded so an expense can still be saved before the migration runs.
        if (!$this->db->table_exists(db_prefix() . 'expense_items')) {
            return;
        }

        $rows = [];
        $sort = 0;

        foreach ((array) $items as $item) {
            $description = isset($item['description']) ? trim((string) $item['description']) : '';
            $qty         = isset($item['qty']) ? (float) $item['qty'] : 0;
            $rate        = isset($item['rate']) ? (float) $item['rate'] : 0;
            $tax_percent = isset($item['tax_percent']) ? (float) $item['tax_percent'] : 0;

            // A row with nothing typed into it is the form's spare row, not a line
            if ($description === '' && $rate == 0) {
                continue;
            }

            $amount = round($qty * $rate, 2);

            $rows[] = [
                'expense_id'  => $expense_id,
                'description' => $description,
                'qty'         => $qty,
                'rate'        => $rate,
                'amount'      => $amount,
                'tax_percent' => $tax_percent,
                'tax_amount'  => round($amount * $tax_percent / 100, 2),
                'sort'        => $sort++,
            ];
        }

        // Replace rather than merge: the form always posts the complete set, so a line
        // the user deleted has to disappear. Only ever touches this one expense.
        $this->db->where('expense_id', $expense_id);
        $this->db->delete(db_prefix() . 'expense_items');

        foreach ($rows as $row) {
            $this->db->insert(db_prefix() . 'expense_items', $row);
        }
    }

    /**
     * The line items behind one expense, in the order they were entered.
     *
     * @param  int   $expense_id
     * @return array
     */
    public function get_expense_items($expense_id)
    {
        if (!$this->db->table_exists(db_prefix() . 'expense_items')) {
            return [];
        }

        $this->db->where('expense_id', $expense_id);
        $this->db->order_by('sort', 'asc');
        $this->db->order_by('id', 'asc');

        $query = $this->db->get(db_prefix() . 'expense_items');

        // A failed query returns false with db_debug off, and ->result_array() on false
        // is fatal. Guarded rather than trusted.
        return $query === false ? [] : $query->result_array();
    }

    public function add_category($data)
    {
        $data['description'] = nl2br($data['description']);
        $this->db->insert(db_prefix() . 'expenses_categories', $data);
        $insert_id = $this->db->insert_id();
        if ($insert_id) {
            log_activity('New Expense Category Added [ID: ' . $insert_id . ']');

            return $insert_id;
        }

        return false;
    }

    /**
     * Update expense category
     * @param  mixed $data All $_POST data
     * @param  mixed $id   expense id to update
     * @return boolean
     */
    public function update_category($data, $id)
    {
        $data['description'] = nl2br($data['description']);
        $this->db->where('id', $id);
        $this->db->update(db_prefix() . 'expenses_categories', $data);
        if ($this->db->affected_rows() > 0) {
            log_activity('Expense Category Updated [ID: ' . $id . ']');

            return true;
        }

        return false;
    }

    /**
     * @param  integer ID
     * @return mixed
     * Delete expense category from database, if used return array with key referenced
     */
    public function delete_category($id)
    {
        if (is_reference_in_table('category', db_prefix() . 'expenses', $id)) {
            return [
                'referenced' => true,
            ];
        }
        $this->db->where('id', $id);
        $this->db->delete(db_prefix() . 'expenses_categories');
        if ($this->db->affected_rows() > 0) {
            log_activity('Expense Category Deleted [' . $id . ']');

            return true;
        }

        return false;
    }

    public function get_expenses_years()
    {
        return $this->db->query('SELECT DISTINCT(YEAR(date)) as year FROM ' . db_prefix() . 'expenses ORDER by year DESC')->result_array();
    }
}
