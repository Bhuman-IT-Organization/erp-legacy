<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Salary increment requests (requirement 12).
 *
 * A direct manager or HR raises a request; the form is mostly automatic - designation,
 * joining date, visa, seniority, salary on joining, last increment, current salary and
 * the latest evaluation are all read and snapshot. On final approval the increment is
 * applied to the employee's salary using the configured split (50/30/20 for staff
 * grades, everything to Other Allowances for labour), and every change is written to a
 * salary-history ledger so the full increment history becomes answerable - which it was
 * not before, because editing a contract value overwrote the old one.
 *
 * The split itself is data (tblincrement_split_rule). This model reads it, and refuses
 * to apply an increment whose active split does not add up to 100%, because a split
 * that sums to 90 quietly loses a tenth of every increase.
 */
class Increment_model extends App_Model
{
    private $request_table;
    private $history_table;
    private $contract_table;
    private $detail_table;

    public function __construct()
    {
        parent::__construct();

        $this->request_table  = db_prefix() . 'increment_request';
        $this->history_table  = db_prefix() . 'salary_history';
        $this->contract_table = db_prefix() . 'hr_staff_contract';
        $this->detail_table   = db_prefix() . 'hr_staff_contract_detail';

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
        if (!$this->db->table_exists($this->request_table)) {
            return [];
        }

        $this->db->select($this->request_table . '.*,'
            . ' CONCAT(' . db_prefix() . 'staff.firstname, " ", ' . db_prefix() . 'staff.lastname) as staff_name,'
            . ' ' . db_prefix() . 'emp_category.name as category_name');
        $this->db->join(db_prefix() . 'staff',
            db_prefix() . 'staff.staffid = ' . $this->request_table . '.staff_id', 'left');
        $this->db->join(db_prefix() . 'emp_category',
            db_prefix() . 'emp_category.id = ' . $this->request_table . '.category_id', 'left');

        if (!empty($where)) {
            $this->db->where($where);
        }

        $this->db->order_by($this->request_table . '.id', 'DESC');

        return $this->db->get($this->request_table)->result_array();
    }

    /**
     * @param int $id
     *
     * @return object|null
     */
    public function get($id)
    {
        $this->db->where('id', (int) $id);
        $row = $this->db->get($this->request_table)->row();

        if ($row) {
            $row->staff_name = $this->staff_name($row->staff_id);
        }

        return $row;
    }

    /**
     * @param int $staff_id
     *
     * @return array
     */
    public function get_for_staff($staff_id)
    {
        return $this->get_list([$this->request_table . '.staff_id' => (int) $staff_id]);
    }

    /* ============================================================= automatic data */

    /**
     * Everything the form fills in for an employee without asking.
     *
     * Read live for the form, then snapshot onto the request at creation so the
     * approver sees what was true at submission.
     *
     * @param int $staff_id
     *
     * @return array
     */
    public function gather_employee_data($staff_id)
    {
        $staff_id = (int) $staff_id;

        $this->db->where('staffid', $staff_id);
        $staff = $this->db->get(db_prefix() . 'staff')->row();

        $profile = $this->hr_masters_model->get_profile($staff_id);
        $last    = $this->last_increment($staff_id);

        return [
            'designation'           => $this->designation($staff_id),
            'joining_date'          => $this->hr_masters_model->joining_date($staff_id),
            'service_years'         => $this->hr_masters_model->service_years($staff_id),
            'visa_type'             => $this->visa_type_name($profile),
            'visa_expiry'           => $this->normalise_date($staff->visa_validity ?? null),
            'salary_on_joining'     => $profile && $profile->salary_on_joining !== null
                ? (float) $profile->salary_on_joining : null,
            'current_salary'        => $this->current_salary($staff_id),
            'last_increment_date'   => $last ? $last['effective_from'] : null,
            'last_increment_amount' => $last ? (float) $last['change_amount'] : null,
            'evaluation'            => $this->latest_evaluation($staff_id),
            'category_id'           => $profile ? $profile->category_id : null,
        ];
    }

    /**
     * The employee's designation.
     *
     * From the hr_profile job position when set; that is where hr_payroll also reads
     * it. Returns null rather than an id when nothing is set.
     *
     * @param int $staff_id
     *
     * @return string|null
     */
    public function designation($staff_id)
    {
        $this->db->select('job_position');
        $this->db->where('staffid', (int) $staff_id);
        $staff = $this->db->get(db_prefix() . 'staff')->row();

        if (!$staff || empty($staff->job_position)) {
            return null;
        }

        $position_table = db_prefix() . 'hr_job_position';

        if (!$this->db->table_exists($position_table)) {
            return null;
        }

        $this->db->select('position_name');
        $this->db->where('position_id', (int) $staff->job_position);
        $row = $this->db->get($position_table)->row();

        return $row ? $row->position_name : null;
    }

    /**
     * Current monthly salary: the sum of the salary and allowance rows on the latest
     * valid contract. Null when there is no contract, so "no salary on file" is
     * distinguishable from zero.
     *
     * @param int $staff_id
     *
     * @return float|null
     */
    public function current_salary($staff_id)
    {
        $contract = $this->latest_contract($staff_id);

        if (!$contract) {
            return null;
        }

        $this->db->select_sum('rel_value', 'gross');
        $this->db->where('staff_contract_id', $contract->id_contract);
        $this->db->where_in('rel_type', ['salary', 'allowance']);
        $row = $this->db->get($this->detail_table)->row();

        return $row && $row->gross !== null ? round((float) $row->gross, 2) : null;
    }

    /**
     * The most recent salary-history entry that represents an increment.
     *
     * @param int $staff_id
     *
     * @return array|null
     */
    public function last_increment($staff_id)
    {
        if (!$this->db->table_exists($this->history_table)) {
            return null;
        }

        // 'total' rows are the summary written per increment, so a multi-slice split
        // reports one increment, not three.
        $this->db->where('staff_id', (int) $staff_id);
        $this->db->where('change_type', 'increment');
        $this->db->where('component_type', 'total');
        $this->db->order_by('effective_from', 'DESC');
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);

        return $this->db->get($this->history_table)->row_array() ?: null;
    }

    /**
     * Full increment history for an employee, newest first. This is the
     * "extend the last increment date to see the full history" view.
     *
     * @param int $staff_id
     *
     * @return array
     */
    public function increment_history($staff_id)
    {
        if (!$this->db->table_exists($this->history_table)) {
            return [];
        }

        $this->db->where('staff_id', (int) $staff_id);
        $this->db->order_by('effective_from', 'DESC');
        $this->db->order_by('id', 'DESC');

        return $this->db->get($this->history_table)->result_array();
    }

    /* ================================================================== create */

    /**
     * @param array $data
     *
     * @return int|bool
     */
    public function create($data)
    {
        $staff_id = (int) ($data['staff_id'] ?? 0);
        $amount   = (float) ($data['increment_amount'] ?? 0);

        if ($staff_id <= 0 || $amount <= 0) {
            return false;
        }

        $auto = $this->gather_employee_data($staff_id);

        $row = [
            'reference'             => $this->next_reference(),
            'staff_id'              => $staff_id,
            'submitted_by'          => get_staff_user_id() ?: null,
            'reason'                => $data['reason'] ?? null,
            'increment_amount'      => $amount,
            'effective_from'        => !empty($data['effective_from'])
                ? to_sql_date($data['effective_from']) : null,
            'designation'           => $auto['designation'],
            'joining_date'          => $auto['joining_date'],
            'service_years'         => $auto['service_years'],
            'visa_type'             => $auto['visa_type'],
            'visa_expiry'           => $auto['visa_expiry'],
            'salary_on_joining'     => $auto['salary_on_joining'],
            'current_salary'        => $auto['current_salary'],
            'last_increment_date'   => $auto['last_increment_date'],
            'last_increment_amount' => $auto['last_increment_amount'],
            'evaluation_result'     => $auto['evaluation'] ? $auto['evaluation']['result'] : null,
            'evaluation_year'       => $auto['evaluation'] ? $auto['evaluation']['year'] : null,
            'category_id'           => $auto['category_id'],
            'status'                => 'draft',
            'created_at'            => date('Y-m-d H:i:s'),
        ];

        $this->db->insert($this->request_table, $row);

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
        $request = $this->get($id);

        if (!$request || $request->status !== 'draft') {
            return false;
        }

        $amount = (float) ($data['increment_amount'] ?? 0);

        if ($amount <= 0) {
            return false;
        }

        $this->db->where('id', (int) $id);
        $this->db->update($this->request_table, [
            'reason'           => $data['reason'] ?? null,
            'increment_amount' => $amount,
            'effective_from'   => !empty($data['effective_from']) ? to_sql_date($data['effective_from']) : null,
            'updated_at'       => date('Y-m-d H:i:s'),
        ]);

        return true;
    }

    /* ================================================================== submit */

    /**
     * @param int $id
     *
     * @return array
     */
    public function submit($id)
    {
        $request = $this->get($id);

        if (!$request) {
            return ['success' => false, 'error' => 'not_found'];
        }

        if ($request->status !== 'draft') {
            return ['success' => false, 'error' => 'not_a_draft'];
        }

        $result = $this->approval_flow_model->submit(
            Approval_flow_model::TYPE_INCREMENT,
            (int) $id,
            (int) $request->staff_id
        );

        if (!$result['success']) {
            return $result;
        }

        $this->db->where('id', (int) $id);
        $this->db->update($this->request_table, ['status' => 'pending', 'updated_at' => date('Y-m-d H:i:s')]);

        return ['success' => true];
    }

    /**
     * @param int   $id
     * @param float $amount
     *
     * @return bool
     */
    public function set_approved_amount($id, $amount)
    {
        $amount = (float) $amount;

        if ($amount <= 0) {
            return false;
        }

        $this->db->where('id', (int) $id);
        $this->db->where('status', 'pending');
        $this->db->update($this->request_table, ['approved_amount' => $amount, 'updated_at' => date('Y-m-d H:i:s')]);

        return $this->db->affected_rows() >= 0;
    }

    /* =========================================================== approval hook */

    /**
     * Called by the shared engine when the chain finishes.
     *
     * @param int    $request_id
     * @param string $status
     */
    public function on_approval_complete($request_id, $status)
    {
        $request = $this->get($request_id);

        if (!$request) {
            return;
        }

        if ($status === 'rejected') {
            $this->db->where('id', (int) $request_id);
            $this->db->update($this->request_table, ['status' => 'rejected', 'updated_at' => date('Y-m-d H:i:s')]);

            return;
        }

        $this->db->where('id', (int) $request_id);
        $this->db->update($this->request_table, ['status' => 'approved', 'updated_at' => date('Y-m-d H:i:s')]);

        // Apply the split. If it cannot be applied cleanly the request is left
        // 'approved' but not 'applied', and HR is told why, rather than a partial or
        // wrong split being written to pay.
        $applied = $this->apply_increment($request_id);

        if ($applied['success']) {
            $this->db->where('id', (int) $request_id);
            $this->db->update($this->request_table, [
                'status'     => 'applied',
                'applied_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            $this->notify_apply_failure($request, $applied['error']);
        }
    }

    /* =============================================================== apply split */

    /**
     * Write the approved increment onto the employee's salary, split by category.
     *
     * @param int $request_id
     *
     * @return array ['success' => bool, 'error' => string]
     */
    public function apply_increment($request_id)
    {
        $request = $this->get($request_id);

        if (!$request) {
            return ['success' => false, 'error' => 'not_found'];
        }

        $amount = $request->approved_amount !== null
            ? (float) $request->approved_amount : (float) $request->increment_amount;

        if ($amount <= 0) {
            return ['success' => false, 'error' => 'zero_amount'];
        }

        $category_id = $request->category_id;

        if (!$category_id) {
            return ['success' => false, 'error' => 'no_category'];
        }

        // The active split must total 100, else the increment would be lost or
        // inflated. This is enforced here as well as shown on the config screen.
        $totals = $this->hr_masters_model->split_totals($category_id);

        if (!$totals['ok']) {
            return ['success' => false, 'error' => 'split_not_100'];
        }

        $rules = $this->hr_masters_model->get_split_rules($category_id, true);

        $contract = $this->latest_contract($request->staff_id);

        if (!$contract) {
            return ['success' => false, 'error' => 'no_contract'];
        }

        $effective = $request->effective_from ?: date('Y-m-d');

        // Each slice adds to the matching contract-detail row, creating it if absent,
        // and records a history line. The whole thing runs in a transaction so a
        // failure part way through does not leave a half-applied increment.
        $this->db->trans_begin();

        try {
            foreach ($rules as $rule) {
                $slice = round($amount * ((float) $rule['percent'] / 100), 2);

                if ($slice == 0) {
                    continue;
                }

                if ($rule['component_type'] === 'basic') {
                    $this->apply_to_component($contract->id_contract, 'salary', null, $slice,
                        $effective, $request_id, $request->staff_id, 'Basic salary');
                } else {
                    $this->apply_to_component($contract->id_contract, 'allowance',
                        (int) $rule['allowance_type_id'], $slice,
                        $effective, $request_id, $request->staff_id, $rule['label'] ?: 'Allowance');
                }
            }

            // One summary 'total' row so last_increment() reports a single increment.
            $this->record_history([
                'staff_id'       => (int) $request->staff_id,
                'effective_from' => $effective,
                'change_type'    => 'increment',
                'component_type' => 'total',
                'component_label' => 'Increment total',
                'old_amount'     => (float) ($request->current_salary ?? 0),
                'new_amount'     => (float) ($request->current_salary ?? 0) + $amount,
                'change_amount'  => $amount,
                'ref_type'       => 'increment_request',
                'ref_id'         => (int) $request_id,
                'contract_id'    => (int) $contract->id_contract,
            ]);

            if ($this->db->trans_status() === false) {
                $this->db->trans_rollback();

                return ['success' => false, 'error' => 'db_error'];
            }

            $this->db->trans_commit();

            return ['success' => true, 'error' => ''];
        } catch (\Throwable $e) {
            $this->db->trans_rollback();
            log_message('error', 'Increment apply failed: ' . $e->getMessage());

            return ['success' => false, 'error' => 'exception'];
        }
    }

    /**
     * Add a slice to a contract-detail component, creating the row if it does not
     * exist yet, and log the change.
     *
     * @param int      $contract_id
     * @param string   $rel_type salary|allowance
     * @param int|null $type_id  the master row id for an allowance
     * @param float    $slice
     * @param string   $effective
     * @param int      $request_id
     * @param int      $staff_id
     * @param string   $label
     */
    private function apply_to_component($contract_id, $rel_type, $type_id, $slice, $effective, $request_id, $staff_id, $label)
    {
        $this->db->where('staff_contract_id', $contract_id);
        $this->db->where('rel_type', $rel_type);

        // An allowance is identified by its master id in the `type` column, so several
        // allowance rows on one contract stay distinct.
        if ($rel_type === 'allowance' && $type_id) {
            $this->db->where('type', $type_id);
        }

        $this->db->order_by('contract_detail_id', 'ASC');
        $this->db->limit(1);
        $existing = $this->db->get($this->detail_table)->row();

        $old = $existing ? (float) $existing->rel_value : 0.0;
        $new = round($old + $slice, 2);

        if ($existing) {
            $this->db->where('contract_detail_id', $existing->contract_detail_id);
            $this->db->update($this->detail_table, [
                'rel_value'  => $new,
                'since_date' => $effective,
            ]);
            $detail_id = (int) $existing->contract_detail_id;
        } else {
            $this->db->insert($this->detail_table, [
                'staff_contract_id' => $contract_id,
                'type'              => $type_id,
                'rel_type'          => $rel_type,
                'rel_value'         => $new,
                'since_date'        => $effective,
                'contract_note'     => 'Created by increment #' . $request_id,
            ]);
            $detail_id = (int) $this->db->insert_id();
        }

        $this->record_history([
            'staff_id'           => (int) $staff_id,
            'effective_from'     => $effective,
            'change_type'        => 'increment',
            'component_type'     => $rel_type === 'salary' ? 'basic' : 'allowance',
            'allowance_type_id'  => $rel_type === 'allowance' ? $type_id : null,
            'component_label'    => $label,
            'old_amount'         => $old,
            'new_amount'         => $new,
            'change_amount'      => $slice,
            'ref_type'           => 'increment_request',
            'ref_id'             => (int) $request_id,
            'contract_id'        => (int) $contract_id,
            'contract_detail_id' => $detail_id,
        ]);
    }

    /**
     * @param array $data
     */
    private function record_history($data)
    {
        $this->db->insert($this->history_table, array_merge([
            'created_by' => get_staff_user_id() ?: null,
            'created_at' => date('Y-m-d H:i:s'),
        ], $data));
    }

    /* ================================================================ helpers */

    /**
     * @param int $id
     *
     * @return bool
     */
    public function delete_draft($id)
    {
        $request = $this->get($id);

        if (!$request || $request->status !== 'draft') {
            return false;
        }

        $this->db->where('id', (int) $id)->delete($this->request_table);

        return true;
    }

    /**
     * @param int $staff_id
     *
     * @return object|null
     */
    private function latest_contract($staff_id)
    {
        if (!$this->db->table_exists($this->contract_table)) {
            return null;
        }

        $this->db->select('id_contract');
        $this->db->where('staff', (int) $staff_id);
        $this->db->where('contract_status', 'valid');
        $this->db->order_by('start_valid', 'DESC');
        $this->db->limit(1);

        return $this->db->get($this->contract_table)->row();
    }

    /**
     * @param object|null $profile
     *
     * @return string|null
     */
    private function visa_type_name($profile)
    {
        if (!$profile || empty($profile->visa_type_id)) {
            return null;
        }

        $this->db->select('name');
        $this->db->where('id', (int) $profile->visa_type_id);
        $row = $this->db->get(db_prefix() . 'emp_visa_type')->row();

        return $row ? $row->name : null;
    }

    /**
     * The latest evaluation result, when a recruitment/evaluation module provides one.
     *
     * That module is not installed here, so this returns null cleanly rather than
     * assuming a table. It is written to read tblrec_evaluation if it ever appears.
     *
     * @param int $staff_id
     *
     * @return array|null ['result' => string, 'year' => int]
     */
    private function latest_evaluation($staff_id)
    {
        $table = db_prefix() . 'rec_evaluation';

        if (!$this->db->table_exists($table)) {
            return null;
        }

        if (!$this->db->field_exists('staff_id', $table)) {
            return null;
        }

        $this->db->where('staff_id', (int) $staff_id);
        $this->db->order_by('id', 'DESC');
        $this->db->limit(1);
        $row = $this->db->get($table)->row();

        if (!$row) {
            return null;
        }

        return [
            'result' => $row->result ?? ($row->total_point ?? ''),
            'year'   => isset($row->date) ? (int) date('Y', strtotime($row->date)) : (int) date('Y'),
        ];
    }

    /**
     * @param string|null $raw
     *
     * @return string|null Y-m-d
     */
    private function normalise_date($raw)
    {
        if (empty($raw)) {
            return null;
        }

        $ts = strtotime($raw);

        return $ts ? date('Y-m-d', $ts) : null;
    }

    /**
     * @param object $request
     * @param string $error
     */
    private function notify_apply_failure($request, $error)
    {
        $reasons = [
            'split_not_100' => _l('increment_apply_split_not_100'),
            'no_contract'   => _l('increment_apply_no_contract'),
            'no_category'   => _l('increment_apply_no_category'),
        ];

        $reason = $reasons[$error] ?? _l('increment_apply_failed_generic');

        // Tell whoever submitted it, so an approved-but-not-applied increment does not
        // sit silently.
        add_notification([
            'description' => _l('increment_approved_not_applied') . ': ' . $reason,
            'touserid'    => (int) $request->submitted_by ?: (int) $request->staff_id,
            'link'        => 'increments/view/' . (int) $request->id,
            'fromcompany' => 1,
        ]);
    }

    /**
     * @return string
     */
    private function next_reference()
    {
        $year = date('Y');
        $this->db->like('reference', 'INC-' . $year . '-', 'after');
        $count = (int) $this->db->count_all_results($this->request_table);

        return 'INC-' . $year . '-' . str_pad((string) ($count + 1), 4, '0', STR_PAD_LEFT);
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
