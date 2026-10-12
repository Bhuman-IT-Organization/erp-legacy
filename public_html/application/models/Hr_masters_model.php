<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Master data behind the HR extensions: employee categories, visa types, document
 * types, loan policy, increment split rules and the per-employee HR profile.
 *
 * Everything here is configuration an administrator edits. The model's job is to
 * store it and to refuse input that would make a downstream rule meaningless - a
 * split that does not add up to 100, a loan policy that is active but has no limits,
 * a category with no approval levels. Refusing early is the point: these rows decide
 * how much somebody is paid and who signs for it.
 */
class Hr_masters_model extends App_Model
{
    private $category_table;
    private $visa_table;
    private $doc_type_table;
    private $loan_policy_table;
    private $split_table;
    private $profile_table;

    public function __construct()
    {
        parent::__construct();

        $this->category_table    = db_prefix() . 'emp_category';
        $this->visa_table        = db_prefix() . 'emp_visa_type';
        $this->doc_type_table    = db_prefix() . 'emp_document_type';
        $this->loan_policy_table = db_prefix() . 'loan_policy';
        $this->split_table       = db_prefix() . 'increment_split_rule';
        $this->profile_table     = db_prefix() . 'emp_profile';
    }

    /* ==================================================== employee categories */

    /**
     * @param bool $active_only
     *
     * @return array
     */
    public function get_categories($active_only = false)
    {
        if (!$this->db->table_exists($this->category_table)) {
            return [];
        }

        if ($active_only) {
            $this->db->where('active', 1);
        }

        $this->db->order_by('sort_order', 'ASC');
        $this->db->order_by('name', 'ASC');

        return $this->db->get($this->category_table)->result_array();
    }

    /**
     * @param int $id
     *
     * @return object|null
     */
    public function get_category($id)
    {
        $this->db->where('id', (int) $id);

        return $this->db->get($this->category_table)->row();
    }

    /**
     * @param array $data
     * @param int   $id
     *
     * @return int|bool
     */
    public function save_category($data, $id = null)
    {
        $name = trim((string) ($data['name'] ?? ''));

        if ($name === '') {
            return false;
        }

        $levels = (int) ($data['approval_levels'] ?? 0);

        // Zero levels would mean nobody approves anything for this category.
        if ($levels < 1 || $levels > 10) {
            return false;
        }

        $row = [
            'name'            => $name,
            'approval_levels' => $levels,
            'is_labour'       => !empty($data['is_labour']) ? 1 : 0,
            'active'          => !empty($data['active']) ? 1 : 0,
            'sort_order'      => (int) ($data['sort_order'] ?? 0),
            // Blank means no alert rather than an alert at zero days.
            'vacation_balance_alert_days' => ($data['vacation_balance_alert_days'] ?? '') !== ''
                ? (float) $data['vacation_balance_alert_days'] : null,
        ];

        if ($id) {
            $row['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', (int) $id);
            $this->db->update($this->category_table, $row);

            return true;
        }

        $row['slug']       = $this->unique_slug($this->category_table, $name);
        $row['created_at'] = date('Y-m-d H:i:s');
        $row['created_by'] = get_staff_user_id() ?: null;

        $this->db->insert($this->category_table, $row);

        return $this->db->insert_id() ?: false;
    }

    /**
     * A category in use must not vanish - loans, increments and approval flows all
     * reference it, and deleting it would leave those rows pointing at nothing.
     *
     * @param int $id
     *
     * @return array|bool ['referenced' => true] when in use
     */
    public function delete_category($id)
    {
        $id = (int) $id;

        $references = [
            db_prefix() . 'emp_profile'           => 'category_id',
            db_prefix() . 'approval_flow'         => 'employee_category_id',
            db_prefix() . 'loan_policy'           => 'category_id',
            db_prefix() . 'increment_split_rule'  => 'category_id',
            db_prefix() . 'loan_application'      => 'category_id',
            db_prefix() . 'increment_request'     => 'category_id',
        ];

        foreach ($references as $table => $column) {
            if (!$this->db->table_exists($table)) {
                continue;
            }

            $this->db->where($column, $id);
            if ((int) $this->db->count_all_results($table) > 0) {
                return ['referenced' => true];
            }
        }

        $this->db->where('id', $id);
        $this->db->delete($this->category_table);

        return $this->db->affected_rows() > 0;
    }

    /* =========================================================== visa types */

    /**
     * @param bool $active_only
     *
     * @return array
     */
    public function get_visa_types($active_only = false)
    {
        if (!$this->db->table_exists($this->visa_table)) {
            return [];
        }

        if ($active_only) {
            $this->db->where('active', 1);
        }

        $this->db->order_by('sort_order', 'ASC');

        return $this->db->get($this->visa_table)->result_array();
    }

    /**
     * @param array $data
     * @param int   $id
     *
     * @return int|bool
     */
    public function save_visa_type($data, $id = null)
    {
        $name = trim((string) ($data['name'] ?? ''));

        if ($name === '') {
            return false;
        }

        $row = [
            'name'       => $name,
            'active'     => !empty($data['active']) ? 1 : 0,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ];

        if ($id) {
            $this->db->where('id', (int) $id);
            $this->db->update($this->visa_table, $row);

            return true;
        }

        $row['slug'] = $this->unique_slug($this->visa_table, $name);
        $this->db->insert($this->visa_table, $row);

        return $this->db->insert_id() ?: false;
    }

    /**
     * @param int $id
     *
     * @return array|bool
     */
    public function delete_visa_type($id)
    {
        $this->db->where('visa_type_id', (int) $id);
        if ($this->db->table_exists($this->profile_table)
            && (int) $this->db->count_all_results($this->profile_table) > 0) {
            return ['referenced' => true];
        }

        $this->db->where('id', (int) $id);
        $this->db->delete($this->visa_table);

        return $this->db->affected_rows() > 0;
    }

    /* ======================================================= document types */

    /**
     * @param bool $active_only
     *
     * @return array
     */
    public function get_document_types($active_only = false)
    {
        if (!$this->db->table_exists($this->doc_type_table)) {
            return [];
        }

        if ($active_only) {
            $this->db->where('active', 1);
        }

        $this->db->order_by('sort_order', 'ASC');

        return $this->db->get($this->doc_type_table)->result_array();
    }

    /**
     * @param int $id
     *
     * @return object|null
     */
    public function get_document_type($id)
    {
        $this->db->where('id', (int) $id);

        return $this->db->get($this->doc_type_table)->row();
    }

    /**
     * @param string $slug
     *
     * @return object|null
     */
    public function get_document_type_by_slug($slug)
    {
        $this->db->where('slug', $slug);

        return $this->db->get($this->doc_type_table)->row();
    }

    /**
     * The tblstaff columns a document type is allowed to write back to.
     *
     * Whitelisted rather than free text: this value ends up in an UPDATE against the
     * core staff table, so it must never come straight from a form field.
     *
     * @return array column => label
     */
    public function staff_document_fields()
    {
        return [
            'passport_no'     => 'passport_no',
            'passport_expiry' => 'passport_expiry',
            'emirates_ID_No'  => 'emirates_ID_No',
            'EID_expiry'      => 'EID_expiry',
            'labour_card_No'  => 'labour_card_No',
            'visa_validity'   => 'visa_validity',
        ];
    }

    /**
     * @param array $data
     * @param int   $id
     *
     * @return int|bool
     */
    public function save_document_type($data, $id = null)
    {
        $name = trim((string) ($data['name'] ?? ''));

        if ($name === '') {
            return false;
        }

        $allowed = $this->staff_document_fields();

        $number_field = $data['staff_number_field'] ?? '';
        $expiry_field = $data['staff_expiry_field'] ?? '';

        // Anything not on the whitelist is dropped rather than trusted.
        $number_field = isset($allowed[$number_field]) ? $number_field : null;
        $expiry_field = isset($allowed[$expiry_field]) ? $expiry_field : null;

        $reminder = (int) ($data['reminder_days'] ?? 30);

        if ($reminder < 0 || $reminder > 3650) {
            return false;
        }

        $row = [
            'name'                 => $name,
            'has_number'           => !empty($data['has_number']) ? 1 : 0,
            'has_expiry'           => !empty($data['has_expiry']) ? 1 : 0,
            'reminder_days'        => $reminder,
            'employee_can_upload'  => !empty($data['employee_can_upload']) ? 1 : 0,
            'requires_hr_approval' => !empty($data['requires_hr_approval']) ? 1 : 0,
            'staff_number_field'   => $number_field,
            'staff_expiry_field'   => $expiry_field,
            'active'               => !empty($data['active']) ? 1 : 0,
            'sort_order'           => (int) ($data['sort_order'] ?? 0),
        ];

        if ($id) {
            $this->db->where('id', (int) $id);
            $this->db->update($this->doc_type_table, $row);

            return true;
        }

        $row['slug'] = $this->unique_slug($this->doc_type_table, $name);
        $this->db->insert($this->doc_type_table, $row);

        return $this->db->insert_id() ?: false;
    }

    /**
     * @param int $id
     *
     * @return array|bool
     */
    public function delete_document_type($id)
    {
        $documents = db_prefix() . 'emp_document';

        if ($this->db->table_exists($documents)) {
            $this->db->where('document_type_id', (int) $id);
            if ((int) $this->db->count_all_results($documents) > 0) {
                return ['referenced' => true];
            }
        }

        $this->db->where('id', (int) $id);
        $this->db->delete($this->doc_type_table);

        return $this->db->affected_rows() > 0;
    }

    /* ========================================================== loan policy */

    /**
     * @return array
     */
    public function get_loan_policies()
    {
        if (!$this->db->table_exists($this->loan_policy_table)) {
            return [];
        }

        $this->db->select($this->loan_policy_table . '.*, ' . $this->category_table . '.name as category_name');
        $this->db->join(
            $this->category_table,
            $this->category_table . '.id = ' . $this->loan_policy_table . '.category_id',
            'left'
        );
        $this->db->order_by($this->loan_policy_table . '.category_id', 'ASC');

        return $this->db->get($this->loan_policy_table)->result_array();
    }

    /**
     * The policy governing an employee: their category's row, else the catch-all.
     *
     * @param int|null $category_id
     *
     * @return object|null
     */
    public function get_loan_policy_for($category_id)
    {
        if (!$this->db->table_exists($this->loan_policy_table)) {
            return null;
        }

        if ($category_id) {
            $this->db->where('category_id', (int) $category_id);
            $this->db->where('active', 1);
            $row = $this->db->get($this->loan_policy_table)->row();

            if ($row) {
                return $row;
            }
        }

        $this->db->where('category_id IS NULL', null, false);
        $this->db->where('active', 1);

        return $this->db->get($this->loan_policy_table)->row();
    }

    /**
     * @param int $id
     *
     * @return object|null
     */
    public function get_loan_policy($id)
    {
        $this->db->where('id', (int) $id);

        return $this->db->get($this->loan_policy_table)->row();
    }

    /**
     * @param array $data
     * @param int   $id
     *
     * @return int|bool
     */
    public function save_loan_policy($data, $id = null)
    {
        $category_id = !empty($data['category_id']) ? (int) $data['category_id'] : null;

        $row = [
            'category_id'         => $category_id,
            'max_amount'          => ($data['max_amount'] ?? '') !== '' ? (float) $data['max_amount'] : null,
            'max_instalments'     => ($data['max_instalments'] ?? '') !== '' ? (int) $data['max_instalments'] : null,
            'min_service_months'  => (int) ($data['min_service_months'] ?? 0),
            'max_concurrent_loans' => max(1, (int) ($data['max_concurrent_loans'] ?? 1)),
            'max_instalment_percent_of_salary' => ($data['max_instalment_percent_of_salary'] ?? '') !== ''
                ? (float) $data['max_instalment_percent_of_salary'] : null,
            'active'              => !empty($data['active']) ? 1 : 0,
            'note'                => $data['note'] ?? null,
        ];

        // An active policy with no limits at all enforces nothing, which is worse
        // than being switched off because it looks like a control.
        if ($row['active']
            && $row['max_amount'] === null
            && $row['max_instalments'] === null
            && $row['max_instalment_percent_of_salary'] === null
            && $row['min_service_months'] === 0) {
            return false;
        }

        if ($this->loan_policy_exists($category_id, $id)) {
            return false;
        }

        if ($id) {
            $row['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', (int) $id);
            $this->db->update($this->loan_policy_table, $row);

            return true;
        }

        $row['created_at'] = date('Y-m-d H:i:s');
        $row['created_by'] = get_staff_user_id() ?: null;

        $this->db->insert($this->loan_policy_table, $row);

        return $this->db->insert_id() ?: false;
    }

    /**
     * @param int|null $category_id
     * @param int|null $exclude_id
     *
     * @return bool
     */
    public function loan_policy_exists($category_id, $exclude_id = null)
    {
        if ($category_id === null) {
            $this->db->where('category_id IS NULL', null, false);
        } else {
            $this->db->where('category_id', (int) $category_id);
        }

        if ($exclude_id) {
            $this->db->where('id !=', (int) $exclude_id);
        }

        return (int) $this->db->count_all_results($this->loan_policy_table) > 0;
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    public function delete_loan_policy($id)
    {
        $this->db->where('id', (int) $id);
        $this->db->delete($this->loan_policy_table);

        return $this->db->affected_rows() > 0;
    }

    /* =================================================== increment split rules */

    /**
     * @param int|null $category_id
     * @param bool     $active_only
     *
     * @return array
     */
    public function get_split_rules($category_id = null, $active_only = false)
    {
        if (!$this->db->table_exists($this->split_table)) {
            return [];
        }

        $this->db->select($this->split_table . '.*, ' . $this->category_table . '.name as category_name');
        $this->db->join(
            $this->category_table,
            $this->category_table . '.id = ' . $this->split_table . '.category_id',
            'left'
        );

        if ($category_id) {
            $this->db->where($this->split_table . '.category_id', (int) $category_id);
        }

        if ($active_only) {
            $this->db->where($this->split_table . '.active', 1);
        }

        $this->db->order_by($this->split_table . '.category_id', 'ASC');
        $this->db->order_by($this->split_table . '.sort_order', 'ASC');

        return $this->db->get($this->split_table)->result_array();
    }

    /**
     * @param int $id
     *
     * @return object|null
     */
    public function get_split_rule($id)
    {
        $this->db->where('id', (int) $id);

        return $this->db->get($this->split_table)->row();
    }

    /**
     * @param array $data
     * @param int   $id
     *
     * @return int|bool
     */
    public function save_split_rule($data, $id = null)
    {
        $category_id = !empty($data['category_id']) ? (int) $data['category_id'] : null;
        $component   = ($data['component_type'] ?? '') === 'allowance' ? 'allowance' : 'basic';
        $percent     = (float) ($data['percent'] ?? 0);

        if ($category_id === null || $percent <= 0 || $percent > 100) {
            return false;
        }

        // An allowance slice has to say which allowance it lands on, otherwise the
        // money has nowhere to go when the increment is applied.
        $allowance_type_id = !empty($data['allowance_type_id']) ? (int) $data['allowance_type_id'] : null;

        if ($component === 'allowance' && !empty($data['active']) && !$allowance_type_id) {
            return false;
        }

        $row = [
            'category_id'       => $category_id,
            'component_type'    => $component,
            'allowance_type_id' => $component === 'allowance' ? $allowance_type_id : null,
            'percent'           => $percent,
            'label'             => trim((string) ($data['label'] ?? '')) ?: null,
            'active'            => !empty($data['active']) ? 1 : 0,
            'sort_order'        => (int) ($data['sort_order'] ?? 0),
        ];

        if ($id) {
            $row['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', (int) $id);
            $this->db->update($this->split_table, $row);

            return true;
        }

        $row['created_at'] = date('Y-m-d H:i:s');
        $row['created_by'] = get_staff_user_id() ?: null;

        $this->db->insert($this->split_table, $row);

        return $this->db->insert_id() ?: false;
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    public function delete_split_rule($id)
    {
        $this->db->where('id', (int) $id);
        $this->db->delete($this->split_table);

        return $this->db->affected_rows() > 0;
    }

    /**
     * Do a category's ACTIVE split rules add up to 100 percent?
     *
     * The increment applier refuses to run when they do not, because a split that
     * sums to 90 quietly loses a tenth of every increment.
     *
     * @param int $category_id
     *
     * @return array ['ok' => bool, 'total' => float, 'rules' => int]
     */
    public function split_totals($category_id)
    {
        $rules = $this->get_split_rules($category_id, true);
        $total = 0.0;

        foreach ($rules as $rule) {
            $total += (float) $rule['percent'];
        }

        return [
            'ok'    => count($rules) > 0 && abs($total - 100.0) < 0.01,
            'total' => round($total, 2),
            'rules' => count($rules),
        ];
    }

    /**
     * Allowance types from the hr_profile master, for the split rule form.
     *
     * @return array
     */
    public function get_allowance_types()
    {
        $table = db_prefix() . 'hr_allowance_type';

        if (!$this->db->table_exists($table)) {
            return [];
        }

        $this->db->order_by('type_name', 'ASC');

        return $this->db->get($table)->result_array();
    }

    /* ======================================================== employee profile */

    /**
     * @param int $staff_id
     *
     * @return object|null
     */
    public function get_profile($staff_id)
    {
        if (!$this->db->table_exists($this->profile_table)) {
            return null;
        }

        $this->db->where('staff_id', (int) $staff_id);

        return $this->db->get($this->profile_table)->row();
    }

    /**
     * Staff list with their category and profile, for the assignment screen.
     *
     * @return array
     */
    public function get_staff_profiles()
    {
        $this->db->select(db_prefix() . 'staff.staffid, ' . db_prefix() . 'staff.firstname, '
            . db_prefix() . 'staff.lastname, ' . db_prefix() . 'staff.staff_identifi, '
            . db_prefix() . 'staff.datecreated, ' . db_prefix() . 'staff.visa_validity,'
            . ' p.id as profile_id, p.category_id, p.visa_type_id, p.joining_date,'
            . ' p.probation_end_date, p.salary_on_joining,'
            . ' c.name as category_name, v.name as visa_type_name');
        $this->db->join($this->profile_table . ' p', 'p.staff_id = ' . db_prefix() . 'staff.staffid', 'left');
        $this->db->join($this->category_table . ' c', 'c.id = p.category_id', 'left');
        $this->db->join($this->visa_table . ' v', 'v.id = p.visa_type_id', 'left');
        $this->db->where(db_prefix() . 'staff.active', 1);
        $this->db->order_by(db_prefix() . 'staff.firstname', 'ASC');

        return $this->db->get(db_prefix() . 'staff')->result_array();
    }

    /**
     * Create or update the HR profile side record for a staff member.
     *
     * @param int   $staff_id
     * @param array $data
     *
     * @return bool
     */
    public function save_profile($staff_id, $data)
    {
        $staff_id = (int) $staff_id;

        if ($staff_id <= 0) {
            return false;
        }

        $row = [
            'category_id'        => !empty($data['category_id']) ? (int) $data['category_id'] : null,
            'visa_type_id'       => !empty($data['visa_type_id']) ? (int) $data['visa_type_id'] : null,
            'joining_date'       => !empty($data['joining_date']) ? to_sql_date($data['joining_date']) : null,
            'probation_end_date' => !empty($data['probation_end_date'])
                ? to_sql_date($data['probation_end_date']) : null,
            'salary_on_joining'  => ($data['salary_on_joining'] ?? '') !== ''
                ? (float) $data['salary_on_joining'] : null,
            'note'               => $data['note'] ?? null,
        ];

        $existing = $this->get_profile($staff_id);

        if ($existing) {
            $row['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('staff_id', $staff_id);
            $this->db->update($this->profile_table, $row);

            return true;
        }

        $row['staff_id']   = $staff_id;
        $row['created_at'] = date('Y-m-d H:i:s');
        $row['created_by'] = get_staff_user_id() ?: null;

        $this->db->insert($this->profile_table, $row);

        return (bool) $this->db->insert_id();
    }

    /* ================================================================ helpers */

    /**
     * The date seniority is measured from.
     *
     * tblemp_profile.joining_date wins when set. Otherwise tblstaff.datecreated,
     * which hr_payroll already labels "Date of Joining" - accurate for staff created
     * in the system, wrong for anyone migrated in, which is exactly why the override
     * column exists.
     *
     * @param int $staff_id
     *
     * @return string|null Y-m-d
     */
    public function joining_date($staff_id)
    {
        $profile = $this->get_profile($staff_id);

        if ($profile && !empty($profile->joining_date) && $profile->joining_date !== '0000-00-00') {
            return $profile->joining_date;
        }

        $this->db->select('datecreated');
        $this->db->where('staffid', (int) $staff_id);
        $staff = $this->db->get(db_prefix() . 'staff')->row();

        if (!$staff || empty($staff->datecreated)) {
            return null;
        }

        return date('Y-m-d', strtotime($staff->datecreated));
    }

    /**
     * Completed years of service, to two decimals.
     *
     * @param int         $staff_id
     * @param string|null $as_at Y-m-d, defaults to today
     *
     * @return float|null null when no joining date is known
     */
    public function service_years($staff_id, $as_at = null)
    {
        $joined = $this->joining_date($staff_id);

        if (!$joined) {
            return null;
        }

        $as_at = $as_at ?: date('Y-m-d');
        $start = strtotime($joined);
        $end   = strtotime($as_at);

        if (!$start || !$end || $end < $start) {
            return 0.0;
        }

        // Whole days over the average year length. Precise enough for a seniority
        // figure and immune to the month-length problems of naive date maths.
        $days = ($end - $start) / 86400;

        return round($days / 365.25, 2);
    }

    /**
     * @param int $staff_id
     *
     * @return int completed months of service
     */
    public function service_months($staff_id)
    {
        $joined = $this->joining_date($staff_id);

        if (!$joined) {
            return 0;
        }

        $start = new DateTime($joined);
        $now   = new DateTime(date('Y-m-d'));

        if ($now < $start) {
            return 0;
        }

        $diff = $start->diff($now);

        return ($diff->y * 12) + $diff->m;
    }

    /**
     * @param string $table
     * @param string $name
     *
     * @return string
     */
    private function unique_slug($table, $name)
    {
        $base = preg_replace('/[^a-z0-9]+/', '_', strtolower($name));
        $base = trim($base, '_');

        if ($base === '') {
            $base = 'item';
        }

        $slug    = $base;
        $attempt = 1;

        while (true) {
            $this->db->where('slug', $slug);
            if ((int) $this->db->count_all_results($table) === 0) {
                return $slug;
            }

            $attempt++;
            $slug = $base . '_' . $attempt;
        }
    }
}
