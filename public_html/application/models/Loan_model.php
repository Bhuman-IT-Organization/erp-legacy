<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Employee loan applications with an instalment schedule and a derived outstanding
 * balance (requirement 11).
 *
 * Nothing loan-shaped existed before: the business-trip advance in the timesheets
 * module has no principal, schedule or balance, and the purchase petty-cash advance
 * is a buying document. This model owns the application, generates the repayment
 * schedule, and answers "how much is still owed" by summing the schedule rather than
 * storing a figure that would drift.
 *
 * Approvals run through the shared Approval_flow_model - the number of levels comes
 * from the employee's category (2 for staff, 3 for labour), and the approved amount
 * and instalment are set at the level flagged can_edit_amounts. Repayment recovers
 * through the existing payroll deduction path.
 */
class Loan_model extends App_Model
{
    private $loan_table;
    private $instalment_table;

    public function __construct()
    {
        parent::__construct();

        $this->loan_table       = db_prefix() . 'loan_application';
        $this->instalment_table = db_prefix() . 'loan_instalment';

        $this->load->model('hr_masters_model');
        $this->load->model('approval_flow_model');
    }

    /* ==================================================================== read */

    /**
     * @param array $where
     *
     * @return array
     */
    public function get_list($where = [])
    {
        if (!$this->db->table_exists($this->loan_table)) {
            return [];
        }

        $this->db->select($this->loan_table . '.*,'
            . ' CONCAT(' . db_prefix() . 'staff.firstname, " ", ' . db_prefix() . 'staff.lastname) as staff_name,'
            . ' ' . db_prefix() . 'emp_category.name as category_name');
        $this->db->join(db_prefix() . 'staff',
            db_prefix() . 'staff.staffid = ' . $this->loan_table . '.staff_id', 'left');
        $this->db->join(db_prefix() . 'emp_category',
            db_prefix() . 'emp_category.id = ' . $this->loan_table . '.category_id', 'left');

        if (!empty($where)) {
            $this->db->where($where);
        }

        $this->db->order_by($this->loan_table . '.id', 'DESC');

        return $this->db->get($this->loan_table)->result_array();
    }

    /**
     * @param int $id
     *
     * @return object|null
     */
    public function get($id)
    {
        $this->db->where('id', (int) $id);
        $loan = $this->db->get($this->loan_table)->row();

        if ($loan) {
            $loan->instalments = $this->get_instalments($id);
            $loan->staff_name  = $this->staff_name($loan->staff_id);
        }

        return $loan;
    }

    /**
     * @param int $loan_id
     *
     * @return array
     */
    public function get_instalments($loan_id)
    {
        $this->db->where('loan_id', (int) $loan_id);
        $this->db->order_by('instalment_no', 'ASC');

        return $this->db->get($this->instalment_table)->result_array();
    }

    /**
     * Loans for one employee, for the outstanding-balance figure and the self-service
     * list.
     *
     * @param int $staff_id
     *
     * @return array
     */
    public function get_for_staff($staff_id)
    {
        return $this->get_list([$this->loan_table . '.staff_id' => (int) $staff_id]);
    }

    /* ============================================================== balances */

    /**
     * What an employee still owes across every live loan.
     *
     * The sum of scheduled instalments, not a stored column, so it can never
     * disagree with the schedule that justifies it. Waived and skipped rows are
     * excluded because they will never be recovered.
     *
     * @param int      $staff_id
     * @param int|null $exclude_loan_id ignore one loan, e.g. the one being applied for
     *
     * @return float
     */
    public function outstanding_balance($staff_id, $exclude_loan_id = null)
    {
        if (!$this->db->table_exists($this->instalment_table)) {
            return 0.0;
        }

        $this->db->select_sum($this->instalment_table . '.amount', 'due');
        $this->db->join($this->loan_table,
            $this->loan_table . '.id = ' . $this->instalment_table . '.loan_id');
        $this->db->where($this->loan_table . '.staff_id', (int) $staff_id);
        $this->db->where_in($this->loan_table . '.status', ['approved', 'disbursed']);
        $this->db->where($this->instalment_table . '.status', 'scheduled');

        if ($exclude_loan_id) {
            $this->db->where($this->loan_table . '.id !=', (int) $exclude_loan_id);
        }

        $row = $this->db->get($this->instalment_table)->row();

        return $row && $row->due ? round((float) $row->due, 2) : 0.0;
    }

    /**
     * @param int $staff_id
     *
     * @return int loans not yet closed or rejected
     */
    public function active_loan_count($staff_id)
    {
        $this->db->where('staff_id', (int) $staff_id);
        $this->db->where_in('status', ['pending', 'approved', 'disbursed']);

        return (int) $this->db->count_all_results($this->loan_table);
    }

    /* ============================================================ current pay */

    /**
     * The employee's current monthly salary, read from the hr_profile contract.
     *
     * Salary lives as contract-detail rows (rel_type 'salary' or 'allowance'); the
     * gross is their sum for the currently valid contract. Returns null when no
     * contract exists rather than zero, so the caller can tell "nothing owed" apart
     * from "no salary on file".
     *
     * @param int $staff_id
     *
     * @return float|null
     */
    public function current_salary($staff_id)
    {
        $contract_table = db_prefix() . 'hr_staff_contract';
        $detail_table   = db_prefix() . 'hr_staff_contract_detail';

        if (!$this->db->table_exists($contract_table) || !$this->db->table_exists($detail_table)) {
            return null;
        }

        $this->db->select('id_contract');
        $this->db->where('staff', (int) $staff_id);
        $this->db->where('contract_status', 'valid');
        $this->db->order_by('start_valid', 'DESC');
        $this->db->limit(1);
        $contract = $this->db->get($contract_table)->row();

        if (!$contract) {
            return null;
        }

        $this->db->select_sum('rel_value', 'gross');
        $this->db->where('staff_contract_id', $contract->id_contract);
        $this->db->where_in('rel_type', ['salary', 'allowance']);
        $row = $this->db->get($detail_table)->row();

        return $row && $row->gross !== null ? round((float) $row->gross, 2) : null;
    }

    /* =============================================================== eligibility */

    /**
     * Would this application be allowed under the configured policy?
     *
     * Returns the reasons rather than a bare bool so the form can explain a refusal.
     * With no active policy the only rule is the schema minimum, because inventing a
     * ceiling here is exactly what the master-data screen exists to avoid.
     *
     * @param int   $staff_id
     * @param array $data requested_amount, proposed_instalment
     *
     * @return array ['ok' => bool, 'errors' => string[], 'policy' => object|null]
     */
    public function check_eligibility($staff_id, $data)
    {
        $errors = [];

        $amount     = (float) ($data['requested_amount'] ?? 0);
        $instalment = (float) ($data['proposed_instalment'] ?? 0);

        if ($amount <= 0) {
            $errors[] = _l('loan_error_amount_required');
        }

        if ($instalment <= 0) {
            $errors[] = _l('loan_error_instalment_required');
        }

        // A single instalment larger than the whole loan is always a mistake.
        if ($amount > 0 && $instalment > $amount) {
            $errors[] = _l('loan_error_instalment_exceeds_amount');
        }

        $category_id = $this->approval_flow_model->staff_category_id($staff_id);
        $policy      = $this->hr_masters_model->get_loan_policy_for($category_id);

        if ($policy) {
            if ($policy->max_amount !== null && $amount > (float) $policy->max_amount) {
                $errors[] = _l('loan_error_over_max_amount') . ' ' . app_format_money($policy->max_amount, '');
            }

            $service_months = $this->hr_masters_model->service_months($staff_id);

            if ((int) $policy->min_service_months > 0 && $service_months < (int) $policy->min_service_months) {
                $errors[] = _l('loan_error_min_service') . ' ' . (int) $policy->min_service_months;
            }

            if ((int) $policy->max_concurrent_loans > 0
                && $this->active_loan_count($staff_id) >= (int) $policy->max_concurrent_loans) {
                $errors[] = _l('loan_error_max_concurrent');
            }

            if ($policy->max_instalment_percent_of_salary !== null && $instalment > 0) {
                $salary = $this->current_salary($staff_id);

                if ($salary && $salary > 0) {
                    $ceiling = $salary * ((float) $policy->max_instalment_percent_of_salary / 100);

                    if ($instalment > $ceiling + 0.01) {
                        $errors[] = _l('loan_error_instalment_over_salary_percent') . ' '
                            . (float) $policy->max_instalment_percent_of_salary . '% ('
                            . app_format_money($ceiling, '') . ')';
                    }
                }
            }

            // The number of instalments the proposed figure implies.
            if ($policy->max_instalments !== null && $amount > 0 && $instalment > 0) {
                $implied = (int) ceil($amount / $instalment);

                if ($implied > (int) $policy->max_instalments) {
                    $errors[] = _l('loan_error_too_many_instalments') . ' ' . (int) $policy->max_instalments;
                }
            }
        }

        return ['ok' => count($errors) === 0, 'errors' => $errors, 'policy' => $policy];
    }

    /* ================================================================== create */

    /**
     * Create a draft application, snapshotting the automatic context.
     *
     * Service years, outstanding balance and current salary are stored on the row
     * because they are "as at the request": read live they would drift as the
     * employee's situation changes, and the approver needs to see what was true when
     * they decide.
     *
     * @param array $data
     *
     * @return int|bool
     */
    public function create($data)
    {
        $staff_id = (int) ($data['staff_id'] ?? 0);

        if ($staff_id <= 0) {
            return false;
        }

        $eligibility = $this->check_eligibility($staff_id, $data);

        if (!$eligibility['ok']) {
            return false;
        }

        $category_id = $this->approval_flow_model->staff_category_id($staff_id);

        $row = [
            'reference'             => $this->next_reference(),
            'staff_id'              => $staff_id,
            'submitted_by'          => get_staff_user_id() ?: null,
            'reason'                => $data['reason'] ?? null,
            'requested_amount'      => (float) $data['requested_amount'],
            'proposed_instalment'   => (float) $data['proposed_instalment'],
            'first_instalment_date' => !empty($data['first_instalment_date'])
                ? to_sql_date($data['first_instalment_date']) : null,
            'service_years'         => $this->hr_masters_model->service_years($staff_id),
            'outstanding_at_request' => $this->outstanding_balance($staff_id),
            'current_salary'        => $this->current_salary($staff_id),
            'category_id'           => $category_id,
            'status'                => 'draft',
            'created_at'            => date('Y-m-d H:i:s'),
        ];

        $this->db->insert($this->loan_table, $row);

        return $this->db->insert_id() ?: false;
    }

    /**
     * @param int   $id
     * @param array $data
     *
     * @return bool
     */
    public function update_draft($id, $data)
    {
        $loan = $this->get($id);

        // Only a draft may be edited; once submitted the figures are what the
        // approvers are acting on.
        if (!$loan || $loan->status !== 'draft') {
            return false;
        }

        $eligibility = $this->check_eligibility($loan->staff_id, $data);

        if (!$eligibility['ok']) {
            return false;
        }

        $this->db->where('id', (int) $id);
        $this->db->update($this->loan_table, [
            'reason'                => $data['reason'] ?? null,
            'requested_amount'      => (float) $data['requested_amount'],
            'proposed_instalment'   => (float) $data['proposed_instalment'],
            'first_instalment_date' => !empty($data['first_instalment_date'])
                ? to_sql_date($data['first_instalment_date']) : null,
            'updated_at'            => date('Y-m-d H:i:s'),
        ]);

        return true;
    }

    /* ================================================================== submit */

    /**
     * Submit for approval. Delegates the chain to the shared engine.
     *
     * @param int $id
     *
     * @return array ['success' => bool, 'error' => string]
     */
    public function submit($id)
    {
        $loan = $this->get($id);

        if (!$loan) {
            return ['success' => false, 'error' => 'not_found'];
        }

        if ($loan->status !== 'draft') {
            return ['success' => false, 'error' => 'not_a_draft'];
        }

        $result = $this->approval_flow_model->submit(
            Approval_flow_model::TYPE_LOAN,
            (int) $id,
            (int) $loan->staff_id
        );

        if (!$result['success']) {
            return $result;
        }

        $this->db->where('id', (int) $id);
        $this->db->update($this->loan_table, ['status' => 'pending', 'updated_at' => date('Y-m-d H:i:s')]);

        return ['success' => true];
    }

    /**
     * Set the approved amount and instalment.
     *
     * Only callable from the approval level flagged can_edit_amounts, which the
     * controller enforces. When left unset the requested figures stand, so an
     * approver who simply approves does not blank the loan.
     *
     * @param int   $id
     * @param array $data approved_amount, approved_instalment, approved_first_instalment_date
     *
     * @return bool
     */
    public function set_approved_amounts($id, $data)
    {
        $loan = $this->get($id);

        if (!$loan || $loan->status !== 'pending') {
            return false;
        }

        $amount     = ($data['approved_amount'] ?? '') !== '' ? (float) $data['approved_amount'] : null;
        $instalment = ($data['approved_instalment'] ?? '') !== '' ? (float) $data['approved_instalment'] : null;

        if ($amount !== null && $amount <= 0) {
            return false;
        }

        if ($instalment !== null && $instalment <= 0) {
            return false;
        }

        if ($amount !== null && $instalment !== null && $instalment > $amount) {
            return false;
        }

        $this->db->where('id', (int) $id);
        $this->db->update($this->loan_table, [
            'approved_amount'     => $amount,
            'approved_instalment' => $instalment,
            'approved_first_instalment_date' => !empty($data['approved_first_instalment_date'])
                ? to_sql_date($data['approved_first_instalment_date']) : null,
            'updated_at'          => date('Y-m-d H:i:s'),
        ]);

        return true;
    }

    /* ============================================== approval outcome handlers */

    /**
     * Called when the approval chain completes. Wired to the engine's
     * approval_request_completed hook by my_functions_helper, so the model does not
     * have to know how approvals are driven.
     *
     * @param int    $loan_id
     * @param string $status approved|rejected
     */
    public function on_approval_complete($loan_id, $status)
    {
        $loan = $this->get($loan_id);

        if (!$loan) {
            return;
        }

        if ($status === 'rejected') {
            $this->db->where('id', (int) $loan_id);
            $this->db->update($this->loan_table, ['status' => 'rejected', 'updated_at' => date('Y-m-d H:i:s')]);

            return;
        }

        // Approved: settle the final figures and build the schedule.
        $amount     = $loan->approved_amount !== null ? (float) $loan->approved_amount : (float) $loan->requested_amount;
        $instalment = $loan->approved_instalment !== null
            ? (float) $loan->approved_instalment : (float) $loan->proposed_instalment;
        $first_date = $loan->approved_first_instalment_date ?: $loan->first_instalment_date;

        $this->db->where('id', (int) $loan_id);
        $this->db->update($this->loan_table, [
            'status'                          => 'approved',
            'approved_amount'                 => $amount,
            'approved_instalment'             => $instalment,
            'approved_first_instalment_date'  => $first_date,
            'updated_at'                      => date('Y-m-d H:i:s'),
        ]);

        $this->generate_schedule($loan_id, $amount, $instalment, $first_date);
    }

    /* =============================================================== schedule */

    /**
     * Build the instalment rows from the approved amount and instalment.
     *
     * Level instalments monthly from the first date. The final instalment carries
     * whatever the equal instalments did not cover, so the rows always sum to the
     * principal exactly rather than leaving a rounding crumb. This is what the form
     * shows, including the last instalment date.
     *
     * @param int    $loan_id
     * @param float  $amount
     * @param float  $instalment
     * @param string $first_date Y-m-d
     *
     * @return array the rows created
     */
    public function generate_schedule($loan_id, $amount, $instalment, $first_date)
    {
        // Rebuild from scratch so a re-approval never stacks two schedules.
        $this->db->where('loan_id', (int) $loan_id);
        $this->db->delete($this->instalment_table);

        $amount     = round((float) $amount, 2);
        $instalment = round((float) $instalment, 2);

        if ($amount <= 0 || $instalment <= 0) {
            return [];
        }

        if (!$first_date) {
            $first_date = date('Y-m-d');
        }

        $count = (int) ceil($amount / $instalment);
        $rows  = [];
        $running = 0.0;

        for ($i = 1; $i <= $count; $i++) {
            // strtotime "+n months" from a fixed base avoids the drift you get from
            // repeatedly adding a month to the previous date (31 Jan -> 3 Mar).
            $due = date('Y-m-d', strtotime($first_date . ' +' . ($i - 1) . ' months'));

            if ($i < $count) {
                $this_amount = $instalment;
                $running += $instalment;
            } else {
                // Last one clears the remainder exactly.
                $this_amount = round($amount - $running, 2);
            }

            $rows[] = [
                'loan_id'       => (int) $loan_id,
                'instalment_no' => $i,
                'due_date'      => $due,
                'amount'        => $this_amount,
                'status'        => 'scheduled',
            ];
        }

        if (count($rows)) {
            $this->db->insert_batch($this->instalment_table, $rows);
        }

        return $rows;
    }

    /**
     * A preview schedule for the form, without touching the database.
     *
     * @param float  $amount
     * @param float  $instalment
     * @param string $first_date
     *
     * @return array ['rows' => array, 'count' => int, 'last_date' => string|null]
     */
    public function preview_schedule($amount, $instalment, $first_date)
    {
        $amount     = round((float) $amount, 2);
        $instalment = round((float) $instalment, 2);

        if ($amount <= 0 || $instalment <= 0) {
            return ['rows' => [], 'count' => 0, 'last_date' => null];
        }

        if (!$first_date) {
            $first_date = date('Y-m-d');
        }

        $count   = (int) ceil($amount / $instalment);
        $rows    = [];
        $running = 0.0;

        for ($i = 1; $i <= $count; $i++) {
            $due         = date('Y-m-d', strtotime($first_date . ' +' . ($i - 1) . ' months'));
            $this_amount = $i < $count ? $instalment : round($amount - $running, 2);
            $running += $i < $count ? $instalment : 0;

            $rows[] = ['instalment_no' => $i, 'due_date' => $due, 'amount' => $this_amount];
        }

        return [
            'rows'      => $rows,
            'count'     => $count,
            'last_date' => $count > 0 ? $rows[$count - 1]['due_date'] : null,
        ];
    }

    /* ============================================================== deduction */

    /**
     * Instalments due up to and including a payroll month, for the recovery step.
     *
     * The payroll bridge calls this to know what to deduct; marking them deducted is
     * mark_instalment_deducted() so the balance falls as pay is run.
     *
     * @param string $payroll_month Y-m or Y-m-d
     *
     * @return array
     */
    public function get_due_instalments($payroll_month)
    {
        $month_end = date('Y-m-t', strtotime(substr($payroll_month, 0, 7) . '-01'));

        $this->db->select($this->instalment_table . '.*, ' . $this->loan_table . '.staff_id');
        $this->db->join($this->loan_table,
            $this->loan_table . '.id = ' . $this->instalment_table . '.loan_id');
        $this->db->where_in($this->loan_table . '.status', ['approved', 'disbursed']);
        $this->db->where($this->instalment_table . '.status', 'scheduled');
        $this->db->where($this->instalment_table . '.due_date <=', $month_end);
        $this->db->order_by($this->instalment_table . '.due_date', 'ASC');

        return $this->db->get($this->instalment_table)->result_array();
    }

    /**
     * @param int    $instalment_id
     * @param string $payroll_month
     * @param float  $deducted
     *
     * @return bool
     */
    public function mark_instalment_deducted($instalment_id, $payroll_month, $deducted)
    {
        $this->db->where('id', (int) $instalment_id);
        $this->db->where('status', 'scheduled');
        $this->db->update($this->instalment_table, [
            'status'          => 'deducted',
            'payroll_month'   => date('Y-m-01', strtotime(substr($payroll_month, 0, 7) . '-01')),
            'deducted_amount' => round((float) $deducted, 2),
            'deducted_on'     => date('Y-m-d'),
        ]);

        if ($this->db->affected_rows() > 0) {
            $this->close_if_settled($this->loan_of_instalment($instalment_id));

            return true;
        }

        return false;
    }

    /**
     * Close a loan once no scheduled instalments remain.
     *
     * @param int $loan_id
     */
    private function close_if_settled($loan_id)
    {
        if (!$loan_id) {
            return;
        }

        $this->db->where('loan_id', (int) $loan_id);
        $this->db->where('status', 'scheduled');

        if ((int) $this->db->count_all_results($this->instalment_table) === 0) {
            $this->db->where('id', (int) $loan_id);
            $this->db->where_in('status', ['approved', 'disbursed']);
            $this->db->update($this->loan_table, [
                'status'     => 'closed',
                'closed_on'  => date('Y-m-d'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * @param int $instalment_id
     *
     * @return int|null
     */
    private function loan_of_instalment($instalment_id)
    {
        $this->db->select('loan_id');
        $this->db->where('id', (int) $instalment_id);
        $row = $this->db->get($this->instalment_table)->row();

        return $row ? (int) $row->loan_id : null;
    }

    /* ================================================================ helpers */

    /**
     * @param int $id
     *
     * @return bool
     */
    public function delete_draft($id)
    {
        $loan = $this->get($id);

        // Only drafts can be deleted; a submitted or decided loan is a record.
        if (!$loan || $loan->status !== 'draft') {
            return false;
        }

        $this->db->where('loan_id', (int) $id)->delete($this->instalment_table);
        $this->db->where('id', (int) $id)->delete($this->loan_table);

        return true;
    }

    /**
     * @return string
     */
    private function next_reference()
    {
        $year = date('Y');
        $this->db->like('reference', 'LN-' . $year . '-', 'after');
        $count = (int) $this->db->count_all_results($this->loan_table);

        return 'LN-' . $year . '-' . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param int $staff_id
     *
     * @return string
     */
    private function staff_name($staff_id)
    {
        $this->db->select('firstname, lastname');
        $this->db->where('staffid', (int) $staff_id);
        $staff = $this->db->get(db_prefix() . 'staff')->row();

        return $staff ? trim($staff->firstname . ' ' . $staff->lastname) : '';
    }
}
