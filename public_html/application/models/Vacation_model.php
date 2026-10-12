<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Vacation request extras (requirement 13a).
 *
 * The timesheets module already has a full leave/requisition flow with a multi-step
 * approval chain. This does NOT replace it - editing the licensed module would be
 * overwritten on its next release. It adds the four things the module does not do:
 *
 *   1. cumulated (available) days, taken from the ledger built in phase 3 rather than
 *      the module's hand-maintained tbltimesheets_day_off;
 *   2. a server-side balance check, so a request for more days than are available
 *      cannot go through - the module only checked this in the browser;
 *   3. an overlap warning when a colleague in the same department is already on leave
 *      over the chosen period, with that colleague's name;
 *   4. the notice for staff whose balance exceeds the category threshold, because
 *      encashment is not available.
 *
 * Points 2 to 4 hook onto the module's own ts_after_add_leave_request action. That
 * action fires just after the leave row is inserted, so the balance enforcement here
 * is a compensating reversal: an over-balance request is removed again and the
 * submitter told why, which needs no change to vendor code.
 */
class Vacation_model extends App_Model
{
    private $leave_table;

    public function __construct()
    {
        parent::__construct();

        $this->leave_table = db_prefix() . 'timesheets_requisition_leave';

        $this->load->model('attendance_rules_model');
        $this->load->model('hr_masters_model');
    }

    /* =============================================================== balance */

    /**
     * Days available to an employee for a leave type.
     *
     * Reads the ledger balance (SUM over tblattendance_leave_ledger). The leave_type_id
     * is the timesheets type id, which is what the entitlement records store.
     *
     * @param int      $staff_id
     * @param int|null $leave_type_id null totals every type
     *
     * @return float
     */
    public function available_days($staff_id, $leave_type_id = null)
    {
        return $this->attendance_rules_model->get_leave_balance((int) $staff_id, $leave_type_id);
    }

    /**
     * Cumulated (accrued) days for display - the same ledger balance, named as the
     * requirement names it.
     *
     * @param int      $staff_id
     * @param int|null $leave_type_id
     *
     * @return float
     */
    public function cumulated_days($staff_id, $leave_type_id = null)
    {
        return $this->available_days($staff_id, $leave_type_id);
    }

    /**
     * Would a request for this many days be within balance?
     *
     * Only annual-type leave is balance-checked; sick, unpaid and the rest are not
     * drawn from an annual entitlement, so they are always allowed through here.
     *
     * @param int      $staff_id
     * @param int      $leave_type_id
     * @param float    $days
     *
     * @return array ['ok' => bool, 'available' => float, 'requested' => float]
     */
    public function check_balance($staff_id, $leave_type_id, $days)
    {
        // Only annual leave (type 8 in the module) draws from the entitlement ledger.
        // Everything else is not balance-limited here.
        if ((int) $leave_type_id !== 8) {
            return ['ok' => true, 'available' => null, 'requested' => (float) $days];
        }

        $available = $this->available_days($staff_id, 8);

        return [
            'ok'        => (float) $days <= $available + 0.001,
            'available' => $available,
            'requested' => (float) $days,
        ];
    }

    /* =============================================================== overlap */

    /**
     * Colleagues in the same department already on leave over a period.
     *
     * The requirement asks for the overlapping colleague's name shown to the submitter.
     * A staff member can be in several departments here, so the query matches on any
     * shared department. Rejected requests (status 2) are excluded.
     *
     * @param int    $staff_id  the employee the request is for
     * @param string $start     Y-m-d
     * @param string $end       Y-m-d
     * @param int    $exclude_leave_id ignore this leave row, e.g. the one just created
     *
     * @return array [['leave_id','staff_id','name','start','end'], ...]
     */
    public function department_overlaps($staff_id, $start, $end, $exclude_leave_id = 0)
    {
        $staff_id = (int) $staff_id;
        $start    = $this->db->escape_str($start);
        $end      = $this->db->escape_str($end);

        // Departments the subject belongs to.
        $dept_ids = $this->staff_department_ids($staff_id);

        if (count($dept_ids) === 0) {
            return [];
        }

        $dept_list = implode(',', array_map('intval', $dept_ids));

        // Colleagues who share any of those departments, with an approved or pending
        // leave whose period intersects [start, end]. Standard interval overlap:
        // their start <= our end AND their end >= our start.
        $sql = 'SELECT l.id as leave_id, l.staff_id,
                    CONCAT(s.firstname, " ", s.lastname) as name,
                    l.start_time, l.end_time
                FROM ' . $this->leave_table . ' l
                JOIN ' . db_prefix() . 'staff s ON s.staffid = l.staff_id
                WHERE l.rel_type = 1
                  AND l.status != 2
                  AND l.id != ' . (int) $exclude_leave_id . '
                  AND l.staff_id != ' . $staff_id . '
                  AND l.staff_id IN (
                      SELECT DISTINCT staffid FROM ' . db_prefix() . 'staff_departments
                      WHERE departmentid IN (' . $dept_list . ')
                  )
                  AND DATE(l.start_time) <= "' . $end . '"
                  AND DATE(l.end_time) >= "' . $start . '"
                ORDER BY l.start_time ASC';

        return $this->db->query($sql)->result_array();
    }

    /**
     * @param int $staff_id
     *
     * @return array department ids
     */
    private function staff_department_ids($staff_id)
    {
        $this->db->select('departmentid');
        $this->db->where('staffid', (int) $staff_id);
        $rows = $this->db->get(db_prefix() . 'staff_departments')->result_array();

        return array_map('intval', array_column($rows, 'departmentid'));
    }

    /* ======================================================== balance alert */

    /**
     * The category threshold above which a staff member should be notified that their
     * balance is high and encashment is not available. Null means no alert for them.
     *
     * @param int $staff_id
     *
     * @return float|null
     */
    public function balance_alert_threshold($staff_id)
    {
        $category_id = $this->hr_masters_model->get_profile($staff_id);
        $category_id = $category_id ? $category_id->category_id : null;

        if (!$category_id) {
            return null;
        }

        $category = $this->hr_masters_model->get_category($category_id);

        if (!$category || $category->vacation_balance_alert_days === null) {
            return null;
        }

        // The requirement scopes this to staff, not labour; is_labour categories ship
        // with a NULL threshold so this returns null for them anyway.
        return (float) $category->vacation_balance_alert_days;
    }

    /* ======================================================== the leave row */

    /**
     * @param int $leave_id
     *
     * @return object|null
     */
    public function get_leave($leave_id)
    {
        $this->db->where('id', (int) $leave_id);

        return $this->db->get($this->leave_table)->row();
    }

    /**
     * Reverse a leave row the module already inserted.
     *
     * Used when a request breaks the balance rule. Removes the leave, any approval
     * rows the module created for it, and any timesheet marks, so nothing half-formed
     * is left. Mirrors what the module's own delete_requisition does, without calling
     * it (that method is guarded to status 1 only).
     *
     * @param int $leave_id
     */
    public function reverse_leave($leave_id)
    {
        $leave_id = (int) $leave_id;
        $leave    = $this->get_leave($leave_id);

        if (!$leave) {
            return;
        }

        // Approval chain rows keyed by the module's rel_type slug.
        $rel_type = $leave->type_of_leave_text ?: 'leave';
        $this->db->where('rel_id', $leave_id);
        $this->db->where('rel_type', $rel_type);
        $this->db->delete(db_prefix() . 'timesheets_approval_details');

        // Any timesheet marks written for it.
        $this->db->where('relate_id', $leave_id);
        $this->db->where('relate_type', 'leave');
        $this->db->delete(db_prefix() . 'timesheets_timesheet');

        $this->db->where('id', $leave_id);
        $this->db->delete($this->leave_table);
    }

    /* ================================================= ledger on approval */

    /**
     * Record an approved annual-leave as taken in the ledger, so the derived balance
     * falls. Idempotent per leave via the ref, so re-approval does not double-deduct.
     *
     * @param object $leave
     */
    public function record_taken($leave)
    {
        if ((int) $leave->type_of_leave !== 8) {
            return;
        }

        $days = (float) $leave->number_of_leaving_day;

        if ($days <= 0) {
            return;
        }

        // Already recorded?
        $existing = $this->db->where('ref_type', 'requisition_leave')
            ->where('ref_id', (int) $leave->id)
            ->where('entry_type', 'taken')
            ->count_all_results(db_prefix() . 'attendance_leave_ledger');

        if ($existing > 0) {
            return;
        }

        $this->attendance_rules_model->add_leave_ledger_entry([
            'staff_id'      => (int) $leave->staff_id,
            'leave_type_id' => 8,
            'entry_date'    => date('Y-m-d', strtotime($leave->start_time)),
            'entry_type'    => 'taken',
            'days'          => $days,
            'ref_type'      => 'requisition_leave',
            'ref_id'        => (int) $leave->id,
            'note'          => 'Annual leave ' . $leave->subject,
        ]);
    }
}
