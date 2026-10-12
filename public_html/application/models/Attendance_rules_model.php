
<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Master data for the policy-driven attendance rules: overtime, compensatory off and
 * leave entitlement.
 *
 * These are rules held as DATA. Nothing here decides a rate, a ratio or a balance -
 * it stores and reads what an administrator configured, and resolves which rule
 * applies. Overtime stays inert until at least one rule is entered, and comp-off until
 * its setting row is activated, so an unconfigured system does not quietly apply a
 * guessed policy to somebody's pay.
 *
 * Balances are always derived with SUM() over the ledgers rather than kept in a column,
 * so a balance can never disagree with the entries that justify it.
 */
class Attendance_rules_model extends App_Model
{
    private $ot_table;
    private $comp_setting_table;
    private $comp_ledger_table;
    private $entitlement_table;
    private $leave_ledger_table;

    public function __construct()
    {
        parent::__construct();

        // Named as the timesheets module expects, so its own CRUD keeps working and
        // the existing FK columns on tbltimesheets_timesheet /
        // tbltimesheets_additional_timesheet point at something real.
        $this->ot_table           = db_prefix() . 'timesheets_overtime_setting';
        $this->comp_setting_table = db_prefix() . 'attendance_comp_off_setting';
        $this->comp_ledger_table  = db_prefix() . 'attendance_comp_off_ledger';
        $this->entitlement_table  = db_prefix() . 'attendance_leave_entitlement';
        $this->leave_ledger_table = db_prefix() . 'attendance_leave_ledger';
    }

    /* ================================================================ overtime */

    /**
     * @param bool $active_only
     *
     * @return array
     */
    public function get_overtime_rules($active_only = false)
    {
        if (!$this->db->table_exists($this->ot_table)) {
            return [];
        }

        if ($active_only) {
            $this->db->where('active', 1);
        }

        $this->db->order_by('priority', 'ASC');
        $this->db->order_by('id', 'ASC');

        return $this->db->get($this->ot_table)->result_array();
    }

    /**
     * @param int $id
     *
     * @return object|null
     */
    public function get_overtime_rule($id)
    {
        $this->db->where('id', (int) $id);

        return $this->db->get($this->ot_table)->row();
    }

    /**
     * Create or update an overtime rule.
     *
     * @param array $data
     * @param int   $id
     *
     * @return int|bool Insert id, or true on update, false on failure
     */
    public function save_overtime_rule($data, $id = null)
    {
        $row = [
            'name'              => trim((string) ($data['name'] ?? '')),
            'coefficient'       => (float) ($data['coefficient'] ?? 1),
            'day_type'          => in_array($data['day_type'] ?? '', ['any', 'working_day', 'weekly_off', 'public_holiday'], true)
                ? $data['day_type'] : 'any',
            'min_minutes'       => (int) ($data['min_minutes'] ?? 0),
            'max_hours_per_day' => (float) ($data['max_hours_per_day'] ?? 0),
            'priority'          => (int) ($data['priority'] ?? 10),
            'active'            => !empty($data['active']) ? 1 : 0,
            'note'              => $data['note'] ?? null,
        ];

        if ($row['name'] === '') {
            return false;
        }

        // A multiplier of zero would silently zero out overtime pay. Treat it as
        // invalid rather than accepting it.
        if ($row['coefficient'] <= 0) {
            return false;
        }

        // Empty time fields mean "any time", which is not the same as 00:00.
        $row['from_time'] = !empty($data['from_time']) ? $data['from_time'] : null;
        $row['to_time']   = !empty($data['to_time']) ? $data['to_time'] : null;

        $row['departments']   = !empty($data['departments']) && is_array($data['departments'])
            ? implode(',', array_map('intval', $data['departments'])) : null;
        $row['job_positions'] = !empty($data['job_positions']) && is_array($data['job_positions'])
            ? implode(',', array_map('intval', $data['job_positions'])) : null;

        $row['effective_from'] = !empty($data['effective_from']) ? to_sql_date($data['effective_from']) : null;
        $row['effective_to']   = !empty($data['effective_to']) ? to_sql_date($data['effective_to']) : null;

        if ($id) {
            $row['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', (int) $id);
            $this->db->update($this->ot_table, $row);

            return true;
        }

        $row['created_at'] = date('Y-m-d H:i:s');
        $row['created_by'] = get_staff_user_id() ?: null;
        $this->db->insert($this->ot_table, $row);

        return $this->db->insert_id();
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    public function delete_overtime_rule($id)
    {
        $this->db->where('id', (int) $id);
        $this->db->delete($this->ot_table);

        return $this->db->affected_rows() > 0;
    }

    /**
     * Which overtime rule applies to a given employee, moment and day type.
     *
     * Returns null when nothing matches, which the caller must treat as "no overtime
     * premium configured" rather than assuming a default. Guessing a multiplier here
     * would mean quietly mispaying somebody.
     *
     * @param int    $staff_id
     * @param string $datetime  Y-m-d H:i:s of the overtime hour being priced
     * @param string $day_type  working_day | weekly_off | public_holiday
     *
     * @return array|null
     */
    public function resolve_overtime_rule($staff_id, $datetime, $day_type = 'working_day')
    {
        $rules = $this->get_overtime_rules(true);

        if (!$rules) {
            return null;
        }

        $date = date('Y-m-d', strtotime($datetime));
        $time = date('H:i:s', strtotime($datetime));

        $departments = $this->staff_department_ids($staff_id);
        $position    = $this->staff_job_position($staff_id);

        foreach ($rules as $rule) {
            if ($rule['day_type'] !== 'any' && $rule['day_type'] !== $day_type) {
                continue;
            }

            if (!empty($rule['effective_from']) && $date < $rule['effective_from']) {
                continue;
            }

            if (!empty($rule['effective_to']) && $date > $rule['effective_to']) {
                continue;
            }

            if (!$this->scope_matches($rule['departments'], $departments)) {
                continue;
            }

            if (!empty($rule['job_positions']) && !$this->scope_matches($rule['job_positions'], [$position])) {
                continue;
            }

            if (!$this->time_in_window($time, $rule['from_time'], $rule['to_time'])) {
                continue;
            }

            // Rules are already ordered by priority, so the first match wins.
            return $rule;
        }

        return null;
    }

    /**
     * Is a time inside a rule's window? Handles a window that crosses midnight,
     * which is how a night premium is expressed (e.g. 22:00 to 06:00).
     *
     * @param string      $time
     * @param string|null $from
     * @param string|null $to
     *
     * @return bool
     */
    private function time_in_window($time, $from, $to)
    {
        if (empty($from) || empty($to)) {
            // No window configured means the rule applies at any time.
            return true;
        }

        if ($from <= $to) {
            return $time >= $from && $time <= $to;
        }

        // Crosses midnight: inside if after the start OR before the end.
        return $time >= $from || $time <= $to;
    }

    /**
     * @param string|null $csv    Configured scope, empty means everyone
     * @param array       $actual
     *
     * @return bool
     */
    private function scope_matches($csv, $actual)
    {
        if (empty($csv)) {
            return true;
        }

        $allowed = array_filter(array_map('intval', explode(',', $csv)));

        if (!$allowed) {
            return true;
        }

        foreach ($actual as $value) {
            if (in_array((int) $value, $allowed, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param int $staff_id
     *
     * @return array
     */
    private function staff_department_ids($staff_id)
    {
        $this->db->select('departmentid');
        $this->db->where('staffid', (int) $staff_id);
        $rows = $this->db->get(db_prefix() . 'staff_departments')->result_array();

        return array_map(function ($r) { return (int) $r['departmentid']; }, $rows);
    }

    /**
     * @param int $staff_id
     *
     * @return int
     */
    private function staff_job_position($staff_id)
    {
        $this->db->select('job_position');
        $this->db->where('staffid', (int) $staff_id);
        $row = $this->db->get(db_prefix() . 'staff')->row();

        return $row ? (int) $row->job_position : 0;
    }

    /* ================================================================ comp-off */

    /**
     * The single comp-off configuration row.
     *
     * @return object|null
     */
    public function get_comp_off_setting()
    {
        if (!$this->db->table_exists($this->comp_setting_table)) {
            return null;
        }

        $this->db->order_by('id', 'ASC');
        $this->db->limit(1);

        return $this->db->get($this->comp_setting_table)->row();
    }

    /**
     * @param array $data
     *
     * @return bool
     */
    public function save_comp_off_setting($data)
    {
        $existing = $this->get_comp_off_setting();

        $ratio = (float) ($data['accrual_ratio'] ?? 1);

        // A zero ratio would accrue nothing while appearing configured.
        if ($ratio <= 0) {
            return false;
        }

        $row = [
            'active'                 => !empty($data['active']) ? 1 : 0,
            'accrual_ratio'          => $ratio,
            'expiry_days'            => max(0, (int) ($data['expiry_days'] ?? 0)),
            'election_mode'          => ($data['election_mode'] ?? 'employee') === 'employer' ? 'employer' : 'employee',
            'exclude_from_overtime'  => !empty($data['exclude_from_overtime']) ? 1 : 0,
            'min_minutes_to_accrue'  => max(0, (int) ($data['min_minutes_to_accrue'] ?? 0)),
            'earn_on_working_day'    => !empty($data['earn_on_working_day']) ? 1 : 0,
            'earn_on_weekly_off'     => !empty($data['earn_on_weekly_off']) ? 1 : 0,
            'earn_on_public_holiday' => !empty($data['earn_on_public_holiday']) ? 1 : 0,
            'note'                   => $data['note'] ?? null,
            'updated_at'             => date('Y-m-d H:i:s'),
            'updated_by'             => get_staff_user_id() ?: null,
        ];

        if ($existing) {
            $this->db->where('id', $existing->id);
            $this->db->update($this->comp_setting_table, $row);

            return true;
        }

        $this->db->insert($this->comp_setting_table, $row);

        return (bool) $this->db->insert_id();
    }

    /**
     * Add a comp-off movement.
     *
     * 'earned' and 'adjustment' are stored positive, 'taken' and 'expired' negative,
     * so the balance is a plain SUM.
     *
     * @param array $data staff_id, entry_type, hours, entry_date, expires_on, ref_type,
     *                    ref_id, note, approved_by
     *
     * @return int|false
     */
    public function add_comp_off_entry($data)
    {
        $type  = $data['entry_type'] ?? 'adjustment';
        $hours = abs((float) ($data['hours'] ?? 0));

        if ($hours <= 0) {
            return false;
        }

        if (in_array($type, ['taken', 'expired'], true)) {
            $hours = -$hours;
        } elseif ($type === 'adjustment' && isset($data['hours']) && (float) $data['hours'] < 0) {
            // An adjustment may legitimately be negative.
            $hours = -$hours;
        }

        $entry_date = !empty($data['entry_date']) ? $data['entry_date'] : date('Y-m-d');
        $expires_on = !empty($data['expires_on']) ? $data['expires_on'] : null;

        // Apply the configured expiry when earning and none was given explicitly.
        if ($type === 'earned' && $expires_on === null) {
            $setting = $this->get_comp_off_setting();

            if ($setting && (int) $setting->expiry_days > 0) {
                $expires_on = date('Y-m-d', strtotime($entry_date . ' +' . (int) $setting->expiry_days . ' days'));
            }
        }

        $this->db->insert($this->comp_ledger_table, [
            'staff_id'    => (int) $data['staff_id'],
            'entry_date'  => $entry_date,
            'entry_type'  => $type,
            'hours'       => $hours,
            'expires_on'  => $expires_on,
            'ref_type'    => $data['ref_type'] ?? 'manual',
            'ref_id'      => isset($data['ref_id']) ? (int) $data['ref_id'] : null,
            'note'        => $data['note'] ?? null,
            'approved_by' => isset($data['approved_by']) ? (int) $data['approved_by'] : null,
            'created_by'  => get_staff_user_id() ?: null,
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        return $this->db->insert_id() ?: false;
    }

    /**
     * Comp-off hours available to an employee.
     *
     * @param int $staff_id
     *
     * @return float
     */
    public function get_comp_off_balance($staff_id)
    {
        if (!$this->db->table_exists($this->comp_ledger_table)) {
            return 0.0;
        }

        $this->db->select_sum('hours', 'balance');
        $this->db->where('staff_id', (int) $staff_id);
        $row = $this->db->get($this->comp_ledger_table)->row();

        return $row ? round((float) $row->balance, 2) : 0.0;
    }

    /**
     * @param int $staff_id
     * @param int $limit
     *
     * @return array
     */
    public function get_comp_off_ledger($staff_id, $limit = 100)
    {
        $this->db->where('staff_id', (int) $staff_id);
        $this->db->order_by('entry_date', 'DESC');
        $this->db->order_by('id', 'DESC');
        $this->db->limit($limit);

        return $this->db->get($this->comp_ledger_table)->result_array();
    }

    /* ======================================================== leave entitlement */

    /**
     * @param int $period_year
     * @param int $staff_id
     *
     * @return array
     */
    public function get_entitlements($period_year = null, $staff_id = null)
    {
        if (!$this->db->table_exists($this->entitlement_table)) {
            return [];
        }

        $this->db->select($this->entitlement_table . '.*,'
            . ' CONCAT(' . db_prefix() . 'staff.firstname, " ", ' . db_prefix() . 'staff.lastname) as staff_name');
        $this->db->join(
            db_prefix() . 'staff',
            db_prefix() . 'staff.staffid = ' . $this->entitlement_table . '.staff_id',
            'left'
        );

        if ($period_year) {
            $this->db->where($this->entitlement_table . '.period_year', (int) $period_year);
        }

        if ($staff_id) {
            $this->db->where($this->entitlement_table . '.staff_id', (int) $staff_id);
        }

        $this->db->order_by('staff_name', 'ASC');

        return $this->db->get($this->entitlement_table)->result_array();
    }

    /**
     * @param int $id
     *
     * @return object|null
     */
    public function get_entitlement($id)
    {
        $this->db->where('id', (int) $id);

        return $this->db->get($this->entitlement_table)->row();
    }

    /**
     * Create or update an entitlement, and mirror the opening balance into the ledger.
     *
     * The ledger entry is what makes the balance auditable: the entitlement record says
     * what was granted, the ledger says how the balance got to where it is. The opening
     * entry is rewritten rather than duplicated when the figure is corrected, so a
     * typo does not leave a phantom balance behind.
     *
     * @param array $data
     * @param int   $id
     *
     * @return int|bool
     */
    public function save_entitlement($data, $id = null)
    {
        $staff_id = (int) ($data['staff_id'] ?? 0);
        $year     = (int) ($data['period_year'] ?? date('Y'));

        if ($staff_id <= 0 || $year < 2000) {
            return false;
        }

        if (empty($data['effective_from'])) {
            // The cut-off date is the whole point of the record - without it there is
            // no way to know what the opening balance is true as at.
            return false;
        }

        $row = [
            'staff_id'                 => $staff_id,
            'leave_type_id'            => !empty($data['leave_type_id']) ? (int) $data['leave_type_id'] : null,
            'period_year'              => $year,
            'effective_from'           => to_sql_date($data['effective_from']),
            'opening_balance'          => (float) ($data['opening_balance'] ?? 0),
            'entitlement_days'         => (float) ($data['entitlement_days'] ?? 0),
            'carried_forward'          => (float) ($data['carried_forward'] ?? 0),
            'carry_forward_expires_on' => !empty($data['carry_forward_expires_on'])
                ? to_sql_date($data['carry_forward_expires_on']) : null,
            'note'                     => $data['note'] ?? null,
        ];

        // One entitlement per staff member, per leave type, per year. The table has a
        // UNIQUE key for this, but MySQL does not treat NULLs as equal, so an
        // "all leave types" row would slip past it. Checking here makes both cases
        // behave the same and returns false instead of raising a database error.
        if ($this->entitlement_exists($staff_id, $row['leave_type_id'], $year, $id)) {
            return false;
        }

        if ($id) {
            $row['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', (int) $id);
            $this->db->update($this->entitlement_table, $row);
            $entitlement_id = (int) $id;
        } else {
            $row['created_at'] = date('Y-m-d H:i:s');
            $row['created_by'] = get_staff_user_id() ?: null;

            $this->db->insert($this->entitlement_table, $row);
            $entitlement_id = $this->db->insert_id();

            if (!$entitlement_id) {
                return false;
            }
        }

        $this->sync_opening_ledger($entitlement_id, $row);

        return $id ? true : $entitlement_id;
    }

    /**
     * Is there already an entitlement for this staff member / leave type / year?
     *
     * @param int      $staff_id
     * @param int|null $leave_type_id null means "all leave types"
     * @param int      $year
     * @param int|null $exclude_id    the row being edited, so it does not clash with itself
     *
     * @return bool
     */
    public function entitlement_exists($staff_id, $leave_type_id, $year, $exclude_id = null)
    {
        $this->db->where('staff_id', (int) $staff_id);
        $this->db->where('period_year', (int) $year);

        if ($leave_type_id === null || $leave_type_id === '') {
            $this->db->where('leave_type_id IS NULL', null, false);
        } else {
            $this->db->where('leave_type_id', (int) $leave_type_id);
        }

        if ($exclude_id) {
            $this->db->where('id !=', (int) $exclude_id);
        }

        return (int) $this->db->count_all_results($this->entitlement_table) > 0;
    }

    /**
     * Keep the ledger's opening entries in step with the entitlement record.
     *
     * @param int   $entitlement_id
     * @param array $row
     */
    private function sync_opening_ledger($entitlement_id, $row)
    {
        // Replace rather than add, so correcting a figure does not stack up entries.
        $this->db->where('ref_type', 'entitlement');
        $this->db->where('ref_id', $entitlement_id);
        $this->db->where_in('entry_type', ['opening', 'accrual', 'carry_forward']);
        $this->db->delete($this->leave_ledger_table);

        $entries = [
            'opening'       => (float) $row['opening_balance'],
            'accrual'       => (float) $row['entitlement_days'],
            'carry_forward' => (float) $row['carried_forward'],
        ];

        foreach ($entries as $type => $days) {
            if ($days == 0) {
                continue;
            }

            $this->db->insert($this->leave_ledger_table, [
                'staff_id'      => (int) $row['staff_id'],
                'leave_type_id' => $row['leave_type_id'],
                'entry_date'    => $row['effective_from'],
                'entry_type'    => $type,
                'days'          => $days,
                'ref_type'      => 'entitlement',
                'ref_id'        => $entitlement_id,
                'note'          => 'From entitlement record',
                'created_by'    => get_staff_user_id() ?: null,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    public function delete_entitlement($id)
    {
        $this->db->where('ref_type', 'entitlement');
        $this->db->where('ref_id', (int) $id);
        $this->db->delete($this->leave_ledger_table);

        $this->db->where('id', (int) $id);
        $this->db->delete($this->entitlement_table);

        return $this->db->affected_rows() > 0;
    }

    /**
     * Leave days available to an employee, derived from the ledger.
     *
     * @param int $staff_id
     * @param int $leave_type_id Null totals every type
     *
     * @return float
     */
    public function get_leave_balance($staff_id, $leave_type_id = null)
    {
        if (!$this->db->table_exists($this->leave_ledger_table)) {
            return 0.0;
        }

        $this->db->select_sum('days', 'balance');
        $this->db->where('staff_id', (int) $staff_id);

        if ($leave_type_id !== null) {
            $this->db->where('leave_type_id', (int) $leave_type_id);
        }

        $row = $this->db->get($this->leave_ledger_table)->row();

        return $row ? round((float) $row->balance, 2) : 0.0;
    }

    /**
     * @param array $data staff_id, leave_type_id, entry_type, days, entry_date, ...
     *
     * @return int|false
     */
    public function add_leave_ledger_entry($data)
    {
        $type = $data['entry_type'] ?? 'adjustment';
        $days = abs((float) ($data['days'] ?? 0));

        if ($days <= 0) {
            return false;
        }

        if (in_array($type, ['taken', 'encashed', 'expired'], true)) {
            $days = -$days;
        } elseif ($type === 'adjustment' && isset($data['days']) && (float) $data['days'] < 0) {
            $days = -$days;
        }

        $this->db->insert($this->leave_ledger_table, [
            'staff_id'      => (int) $data['staff_id'],
            'leave_type_id' => !empty($data['leave_type_id']) ? (int) $data['leave_type_id'] : null,
            'entry_date'    => !empty($data['entry_date']) ? $data['entry_date'] : date('Y-m-d'),
            'entry_type'    => $type,
            'days'          => $days,
            'ref_type'      => $data['ref_type'] ?? 'manual',
            'ref_id'        => isset($data['ref_id']) ? (int) $data['ref_id'] : null,
            'note'          => $data['note'] ?? null,
            'created_by'    => get_staff_user_id() ?: null,
            'created_at'    => date('Y-m-d H:i:s'),
        ]);

        return $this->db->insert_id() ?: false;
    }

    /**
     * Leave types from the timesheets module, for the entitlement form.
     *
     * @return array
     */
    public function get_leave_types()
    {
        $table = db_prefix() . 'timesheets_type_of_leave';

        if (!$this->db->table_exists($table)) {
            return [];
        }

        $this->db->order_by('type_name', 'ASC');

        return $this->db->get($table)->result_array();
    }
}
