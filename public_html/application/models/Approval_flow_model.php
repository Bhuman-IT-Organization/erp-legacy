<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * A single multi-level approval engine, shared by every request type that needs one.
 *
 * Five features in this programme require approvals - loans, salary increments,
 * vacations, employee document changes and sign-off of completed project activities.
 * Each module in this codebase has its own copy-pasted approval pair
 * (tbltimesheets_approval_setting/_details, tblwh_*, tblmrp_*, tblpur_*, tblfe_*)
 * with the chain serialised into a JSON blob. This is one engine instead of a sixth
 * copy, and the chain is rows rather than JSON so a level can be queried, indexed
 * and reported on.
 *
 * Two deliberate differences from the module pattern:
 *
 * 1. No silent auto-approval. The timesheets engine treats "no approval setting
 *    configured" as "approved", which is how an unconfigured system pays out money
 *    nobody signed for. Here, submitting with no configured flow fails and says so.
 *
 * 2. Approvers are resolved once, when the chain is built, and stored on the action
 *    row. If somebody's reporting line changes midway, a pending approval does not
 *    silently move to a different person's queue.
 */
class Approval_flow_model extends App_Model
{
    /** Request types the engine knows about. Used to validate and to build links. */
    const TYPE_LOAN       = 'loan_application';
    const TYPE_INCREMENT  = 'increment_request';
    const TYPE_VACATION   = 'vacation_request';
    const TYPE_DOCUMENT   = 'document_change';
    const TYPE_ACTIVITY   = 'activity_completion';

    private $flow_table;
    private $level_table;
    private $request_table;
    private $action_table;

    public function __construct()
    {
        parent::__construct();

        $this->flow_table    = db_prefix() . 'approval_flow';
        $this->level_table   = db_prefix() . 'approval_flow_level';
        $this->request_table = db_prefix() . 'approval_request';
        $this->action_table  = db_prefix() . 'approval_action';
    }

    /**
     * @return array request_type => human label
     */
    public function request_types()
    {
        return [
            self::TYPE_LOAN      => _l('approval_type_loan_application'),
            self::TYPE_INCREMENT => _l('approval_type_increment_request'),
            self::TYPE_VACATION  => _l('approval_type_vacation_request'),
            self::TYPE_DOCUMENT  => _l('approval_type_document_change'),
            self::TYPE_ACTIVITY  => _l('approval_type_activity_completion'),
        ];
    }

    public function approver_types()
    {
        return [
            'direct_manager'     => _l('approval_approver_direct_manager'),
            'head_of_department' => _l('approval_approver_head_of_department'),
            'specific_staff'     => _l('approval_approver_specific_staff'),
            'role'               => _l('approval_approver_role'),
        ];
    }

    /* =============================================================== flow CRUD */

    /**
     * @param string|null $request_type
     *
     * @return array
     */
    public function get_flows($request_type = null)
    {
        if (!$this->db->table_exists($this->flow_table)) {
            return [];
        }

        $this->db->select($this->flow_table . '.*, ' . db_prefix() . 'emp_category.name as category_name');
        $this->db->join(
            db_prefix() . 'emp_category',
            db_prefix() . 'emp_category.id = ' . $this->flow_table . '.employee_category_id',
            'left'
        );

        if ($request_type) {
            $this->db->where($this->flow_table . '.request_type', $request_type);
        }

        $this->db->order_by($this->flow_table . '.request_type', 'ASC');
        $this->db->order_by($this->flow_table . '.employee_category_id', 'ASC');

        $flows = $this->db->get($this->flow_table)->result_array();

        foreach ($flows as &$flow) {
            $flow['levels'] = $this->get_levels($flow['id']);
        }

        return $flows;
    }

    /**
     * @param int $id
     *
     * @return object|null
     */
    public function get_flow($id)
    {
        $this->db->where('id', (int) $id);

        return $this->db->get($this->flow_table)->row();
    }

    /**
     * @param array $data
     * @param int   $id
     *
     * @return int|bool
     */
    public function save_flow($data, $id = null)
    {
        $request_type = $data['request_type'] ?? '';

        if (!array_key_exists($request_type, $this->request_types())) {
            return false;
        }

        if (empty(trim((string) ($data['name'] ?? '')))) {
            return false;
        }

        $category_id = !empty($data['employee_category_id']) ? (int) $data['employee_category_id'] : null;

        // One flow per request type per category. MySQL does not treat NULLs as
        // equal, so the "any category" row would slip past the UNIQUE key.
        if ($this->flow_exists($request_type, $category_id, $id)) {
            return false;
        }

        $row = [
            'request_type'         => $request_type,
            'employee_category_id' => $category_id,
            'name'                 => trim($data['name']),
            'active'               => !empty($data['active']) ? 1 : 0,
            'note'                 => $data['note'] ?? null,
        ];

        if ($id) {
            $row['updated_at'] = date('Y-m-d H:i:s');
            $this->db->where('id', (int) $id);
            $this->db->update($this->flow_table, $row);

            return true;
        }

        $row['created_at'] = date('Y-m-d H:i:s');
        $row['created_by'] = get_staff_user_id() ?: null;

        $this->db->insert($this->flow_table, $row);

        return $this->db->insert_id() ?: false;
    }

    /**
     * @param string   $request_type
     * @param int|null $category_id
     * @param int|null $exclude_id
     *
     * @return bool
     */
    public function flow_exists($request_type, $category_id, $exclude_id = null)
    {
        $this->db->where('request_type', $request_type);

        if ($category_id === null || $category_id === '') {
            $this->db->where('employee_category_id IS NULL', null, false);
        } else {
            $this->db->where('employee_category_id', (int) $category_id);
        }

        if ($exclude_id) {
            $this->db->where('id !=', (int) $exclude_id);
        }

        return (int) $this->db->count_all_results($this->flow_table) > 0;
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    public function delete_flow($id)
    {
        // Levels go with the flow. Requests already raised keep their own resolved
        // action rows, so history is not damaged by retiring a flow.
        $this->db->where('flow_id', (int) $id);
        $this->db->delete($this->level_table);

        $this->db->where('id', (int) $id);
        $this->db->delete($this->flow_table);

        return $this->db->affected_rows() > 0;
    }

    /* ============================================================== level CRUD */

    /**
     * @param int $flow_id
     *
     * @return array
     */
    public function get_levels($flow_id)
    {
        if (!$this->db->table_exists($this->level_table)) {
            return [];
        }

        $this->db->where('flow_id', (int) $flow_id);
        $this->db->order_by('level_no', 'ASC');

        return $this->db->get($this->level_table)->result_array();
    }

    /**
     * @param int $id
     *
     * @return object|null
     */
    public function get_level($id)
    {
        $this->db->where('id', (int) $id);

        return $this->db->get($this->level_table)->row();
    }

    /**
     * @param array $data
     * @param int   $id
     *
     * @return int|bool
     */
    public function save_level($data, $id = null)
    {
        $flow_id  = (int) ($data['flow_id'] ?? 0);
        $level_no = (int) ($data['level_no'] ?? 0);
        $type     = $data['approver_type'] ?? '';

        if ($flow_id <= 0 || $level_no <= 0 || !array_key_exists($type, $this->approver_types())) {
            return false;
        }

        // A level that names nobody can never be actioned, so refuse it rather than
        // create a chain that silently dead-ends.
        if ($type === 'specific_staff' && empty($data['approver_staff_id'])) {
            return false;
        }

        if ($type === 'role' && empty($data['approver_role_id'])) {
            return false;
        }

        $row = [
            'flow_id'           => $flow_id,
            'level_no'          => $level_no,
            'name'              => trim((string) ($data['name'] ?? '')) ?: null,
            'approver_type'     => $type,
            'approver_staff_id' => $type === 'specific_staff' ? (int) $data['approver_staff_id'] : null,
            'approver_role_id'  => $type === 'role' ? (int) $data['approver_role_id'] : null,
            'can_edit_amounts'  => !empty($data['can_edit_amounts']) ? 1 : 0,
            'sla_days'          => !empty($data['sla_days']) ? (int) $data['sla_days'] : null,
        ];

        if ($this->level_exists($flow_id, $level_no, $id)) {
            return false;
        }

        if ($id) {
            $this->db->where('id', (int) $id);
            $this->db->update($this->level_table, $row);

            return true;
        }

        $this->db->insert($this->level_table, $row);

        return $this->db->insert_id() ?: false;
    }

    /**
     * @param int      $flow_id
     * @param int      $level_no
     * @param int|null $exclude_id
     *
     * @return bool
     */
    public function level_exists($flow_id, $level_no, $exclude_id = null)
    {
        $this->db->where('flow_id', (int) $flow_id);
        $this->db->where('level_no', (int) $level_no);

        if ($exclude_id) {
            $this->db->where('id !=', (int) $exclude_id);
        }

        return (int) $this->db->count_all_results($this->level_table) > 0;
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    public function delete_level($id)
    {
        $this->db->where('id', (int) $id);
        $this->db->delete($this->level_table);

        return $this->db->affected_rows() > 0;
    }

    /* ========================================================= flow resolution */

    /**
     * Which flow governs this request?
     *
     * A flow for the employee's own category wins over the "any category" one. That
     * is the mechanism behind three approval levels for labour and two for staff -
     * it is configuration, not a branch in the code.
     *
     * @param string $request_type
     * @param int    $staff_id
     *
     * @return object|null
     */
    public function resolve_flow($request_type, $staff_id)
    {
        if (!$this->db->table_exists($this->flow_table)) {
            return null;
        }

        $category_id = $this->staff_category_id($staff_id);

        if ($category_id) {
            $this->db->where('request_type', $request_type);
            $this->db->where('employee_category_id', $category_id);
            $this->db->where('active', 1);
            $flow = $this->db->get($this->flow_table)->row();

            if ($flow) {
                return $flow;
            }
        }

        $this->db->where('request_type', $request_type);
        $this->db->where('employee_category_id IS NULL', null, false);
        $this->db->where('active', 1);

        return $this->db->get($this->flow_table)->row();
    }

    /**
     * @param int $staff_id
     *
     * @return int|null
     */
    public function staff_category_id($staff_id)
    {
        $table = db_prefix() . 'emp_profile';

        if (!$this->db->table_exists($table)) {
            return null;
        }

        $this->db->select('category_id');
        $this->db->where('staff_id', (int) $staff_id);
        $row = $this->db->get($table)->row();

        return $row && $row->category_id ? (int) $row->category_id : null;
    }

    /**
     * Turn a configured level into an actual staff id.
     *
     * direct_manager and head_of_department read the same columns the timesheets
     * module already uses for its own chain, so the reporting line has one meaning
     * across the application.
     *
     * @param array $level
     * @param int   $staff_id subject of the request
     *
     * @return int|null
     */
    public function resolve_approver($level, $staff_id)
    {
        switch ($level['approver_type']) {
            case 'specific_staff':
                return !empty($level['approver_staff_id']) ? (int) $level['approver_staff_id'] : null;

            case 'direct_manager':
                $this->db->select('team_manage');
                $this->db->where('staffid', (int) $staff_id);
                $staff = $this->db->get(db_prefix() . 'staff')->row();

                return $staff && !empty($staff->team_manage) ? (int) $staff->team_manage : null;

            case 'head_of_department':
                // A staff member can belong to several departments here, so take the
                // first that actually names a manager rather than assuming index 0.
                $this->db->select(db_prefix() . 'departments.manager_id');
                $this->db->join(
                    db_prefix() . 'departments',
                    db_prefix() . 'departments.departmentid = ' . db_prefix() . 'staff_departments.departmentid'
                );
                $this->db->where(db_prefix() . 'staff_departments.staffid', (int) $staff_id);
                $this->db->where(db_prefix() . 'departments.manager_id >', 0);
                $row = $this->db->get(db_prefix() . 'staff_departments')->row();

                return $row ? (int) $row->manager_id : null;

            case 'role':
                // Any active holder of the role may act. One is stored so the queue has
                // an owner, and can_act() still accepts the others.
                $this->db->select('staffid');
                $this->db->where('role', (int) $level['approver_role_id']);
                $this->db->where('active', 1);
                $row = $this->db->get(db_prefix() . 'staff')->row();

                return $row ? (int) $row->staffid : null;
        }

        return null;
    }

    /* ================================================================== submit */

    /**
     * Raise a request and materialise its approval chain.
     *
     * Everything is checked before a single row is written, so a half-built chain
     * cannot be left behind: a request whose level 2 approver cannot be resolved is
     * refused outright rather than becoming stuck after level 1 approves it.
     *
     * @param string   $request_type
     * @param int      $rel_id
     * @param int      $staff_id     subject of the request
     * @param int|null $submitted_by defaults to the logged in staff member
     *
     * @return array ['success' => bool, 'error' => string, 'detail' => mixed, 'id' => int]
     */
    public function submit($request_type, $rel_id, $staff_id, $submitted_by = null)
    {
        if (!array_key_exists($request_type, $this->request_types())) {
            return ['success' => false, 'error' => 'unknown_request_type'];
        }

        $staff_id     = (int) $staff_id;
        $rel_id       = (int) $rel_id;
        $submitted_by = $submitted_by ? (int) $submitted_by : (get_staff_user_id() ?: null);

        if ($staff_id <= 0 || $rel_id <= 0) {
            return ['success' => false, 'error' => 'invalid_reference'];
        }

        $existing = $this->get_request($request_type, $rel_id);

        if ($existing && $existing->status === 'pending') {
            return ['success' => false, 'error' => 'already_submitted'];
        }

        $flow = $this->resolve_flow($request_type, $staff_id);

        if (!$flow) {
            // Refused rather than auto-approved. Nobody signing for money is a
            // configuration mistake, not a shortcut.
            return ['success' => false, 'error' => 'no_flow_configured'];
        }

        $levels = $this->get_levels($flow->id);

        if (count($levels) === 0) {
            return ['success' => false, 'error' => 'flow_has_no_levels'];
        }

        // Resolve every approver up front.
        $resolved = [];

        foreach ($levels as $level) {
            $approver = $this->resolve_approver($level, $staff_id);

            if (!$approver) {
                return [
                    'success' => false,
                    'error'   => 'approver_not_resolved',
                    'detail'  => [
                        'level'         => $level['level_no'],
                        'approver_type' => $level['approver_type'],
                    ],
                ];
            }

            $resolved[] = ['level' => $level, 'approver_staff_id' => $approver];
        }

        // Re-submission after a rejection replaces the previous chain.
        if ($existing) {
            $this->db->where('approval_request_id', $existing->id);
            $this->db->delete($this->action_table);

            $this->db->where('id', $existing->id);
            $this->db->delete($this->request_table);
        }

        $this->db->insert($this->request_table, [
            'request_type'  => $request_type,
            'rel_id'        => $rel_id,
            'staff_id'      => $staff_id,
            'submitted_by'  => $submitted_by,
            'flow_id'       => (int) $flow->id,
            'total_levels'  => count($resolved),
            'current_level' => (int) $resolved[0]['level']['level_no'],
            'status'        => 'pending',
            'submitted_at'  => date('Y-m-d H:i:s'),
        ]);

        $request_id = $this->db->insert_id();

        if (!$request_id) {
            return ['success' => false, 'error' => 'insert_failed'];
        }

        foreach ($resolved as $entry) {
            $level = $entry['level'];

            $this->db->insert($this->action_table, [
                'approval_request_id' => $request_id,
                'level_no'            => (int) $level['level_no'],
                'level_name'          => $level['name'],
                'approver_staff_id'   => $entry['approver_staff_id'],
                'approver_type'       => $level['approver_type'],
                'can_edit_amounts'    => (int) $level['can_edit_amounts'],
                'due_date'            => !empty($level['sla_days'])
                    ? date('Y-m-d', strtotime('+' . (int) $level['sla_days'] . ' days')) : null,
                'action'              => 'pending',
            ]);
        }

        $this->notify_current_approver($request_id);

        hooks()->do_action('approval_request_submitted', [
            'request_id'   => $request_id,
            'request_type' => $request_type,
            'rel_id'       => $rel_id,
            'staff_id'     => $staff_id,
        ]);

        return ['success' => true, 'id' => $request_id, 'levels' => count($resolved)];
    }

    /* ==================================================================== read */

    /**
     * @param string $request_type
     * @param int    $rel_id
     *
     * @return object|null
     */
    public function get_request($request_type, $rel_id)
    {
        if (!$this->db->table_exists($this->request_table)) {
            return null;
        }

        $this->db->where('request_type', $request_type);
        $this->db->where('rel_id', (int) $rel_id);

        return $this->db->get($this->request_table)->row();
    }

    /**
     * @param int $request_id
     *
     * @return object|null
     */
    public function get_request_by_id($request_id)
    {
        $this->db->where('id', (int) $request_id);

        return $this->db->get($this->request_table)->row();
    }

    /**
     * The full chain with names, for the detail screen.
     *
     * @param string $request_type
     * @param int    $rel_id
     *
     * @return array
     */
    public function get_chain($request_type, $rel_id)
    {
        $request = $this->get_request($request_type, $rel_id);

        if (!$request) {
            return [];
        }

        return $this->get_actions($request->id);
    }

    /**
     * @param int $request_id
     *
     * @return array
     */
    public function get_actions($request_id)
    {
        $this->db->select($this->action_table . '.*,'
            . ' CONCAT(a.firstname, " ", a.lastname) as approver_name,'
            . ' CONCAT(b.firstname, " ", b.lastname) as acted_by_name');
        $this->db->join(db_prefix() . 'staff a', 'a.staffid = ' . $this->action_table . '.approver_staff_id', 'left');
        $this->db->join(db_prefix() . 'staff b', 'b.staffid = ' . $this->action_table . '.acted_by', 'left');
        $this->db->where('approval_request_id', (int) $request_id);
        $this->db->order_by('level_no', 'ASC');

        return $this->db->get($this->action_table)->result_array();
    }

    /**
     * The step awaiting action, or null when nothing is pending.
     *
     * @param string $request_type
     * @param int    $rel_id
     *
     * @return array|null
     */
    public function current_action($request_type, $rel_id)
    {
        $request = $this->get_request($request_type, $rel_id);

        if (!$request || $request->status !== 'pending') {
            return null;
        }

        $this->db->where('approval_request_id', $request->id);
        $this->db->where('level_no', $request->current_level);
        $row = $this->db->get($this->action_table)->row_array();

        return $row ?: null;
    }

    /**
     * May this staff member action the step?
     *
     * Admins are allowed through so a request cannot become permanently stuck when
     * the named approver leaves. Role levels accept any active holder of the role.
     *
     * @param array $action
     * @param int   $staff_id
     *
     * @return bool
     */
    public function can_act($action, $staff_id)
    {
        $staff_id = (int) $staff_id;

        if ($staff_id <= 0) {
            return false;
        }

        if ((int) $action['approver_staff_id'] === $staff_id) {
            return true;
        }

        if (($action['approver_type'] ?? '') === 'role') {
            $level = $this->level_for_action($action);

            if ($level && (int) $level['approver_role_id'] > 0) {
                $this->db->select('staffid');
                $this->db->where('staffid', $staff_id);
                $this->db->where('role', (int) $level['approver_role_id']);
                $this->db->where('active', 1);

                if ($this->db->get(db_prefix() . 'staff')->row()) {
                    return true;
                }
            }
        }

        return $this->is_staff_admin($staff_id);
    }

    /**
     * Is this staff member an administrator?
     *
     * Not is_admin(): like get_staff_full_name() it caches through
     * $CI->app_object_cache, which only exists on the admin controller, so it fatals
     * when the engine runs from the cron, the API or the CLI.
     *
     * @param int $staff_id
     *
     * @return bool
     */
    private function is_staff_admin($staff_id)
    {
        $this->db->select('admin');
        $this->db->where('staffid', (int) $staff_id);
        $this->db->where('active', 1);
        $staff = $this->db->get(db_prefix() . 'staff')->row();

        return $staff && (int) $staff->admin === 1;
    }

    /**
     * @param array $action
     *
     * @return array|null
     */
    private function level_for_action($action)
    {
        $request = $this->get_request_by_id($action['approval_request_id']);

        if (!$request || !$request->flow_id) {
            return null;
        }

        $this->db->where('flow_id', $request->flow_id);
        $this->db->where('level_no', $action['level_no']);

        return $this->db->get($this->level_table)->row_array() ?: null;
    }

    /* ==================================================================== act */

    /**
     * Approve or reject the current level.
     *
     * @param string $request_type
     * @param int    $rel_id
     * @param string $decision approved|rejected
     * @param string $note
     * @param int    $staff_id acting staff member, defaults to the logged in one
     *
     * @return array ['success' => bool, 'error' => string, 'completed' => bool, 'status' => string]
     */
    public function act($request_type, $rel_id, $decision, $note = '', $staff_id = null)
    {
        if (!in_array($decision, ['approved', 'rejected'], true)) {
            return ['success' => false, 'error' => 'invalid_decision'];
        }

        $staff_id = $staff_id ? (int) $staff_id : (get_staff_user_id() ?: 0);
        $request  = $this->get_request($request_type, $rel_id);

        if (!$request) {
            return ['success' => false, 'error' => 'not_submitted'];
        }

        if ($request->status !== 'pending') {
            return ['success' => false, 'error' => 'already_decided'];
        }

        $action = $this->current_action($request_type, $rel_id);

        if (!$action) {
            return ['success' => false, 'error' => 'no_pending_level'];
        }

        if (!$this->can_act($action, $staff_id)) {
            return ['success' => false, 'error' => 'not_your_approval'];
        }

        $this->db->where('id', $action['id']);
        $this->db->update($this->action_table, [
            'action'   => $decision,
            'note'     => $note !== '' ? $note : null,
            'acted_by' => $staff_id,
            'acted_at' => date('Y-m-d H:i:s'),
        ]);

        if ($decision === 'rejected') {
            $this->close_request($request->id, 'rejected');
            $this->notify_submitter($request, 'rejected', $note);

            hooks()->do_action('approval_request_completed', [
                'request_id'   => $request->id,
                'request_type' => $request_type,
                'rel_id'       => $rel_id,
                'staff_id'     => (int) $request->staff_id,
                'status'       => 'rejected',
            ]);

            return ['success' => true, 'completed' => true, 'status' => 'rejected'];
        }

        // Is there a further level?
        $this->db->where('approval_request_id', $request->id);
        $this->db->where('level_no >', (int) $action['level_no']);
        $this->db->where('action', 'pending');
        $this->db->order_by('level_no', 'ASC');
        $next = $this->db->get($this->action_table)->row();

        if ($next) {
            $this->db->where('id', $request->id);
            $this->db->update($this->request_table, ['current_level' => (int) $next->level_no]);

            $this->notify_current_approver($request->id);

            return ['success' => true, 'completed' => false, 'status' => 'pending'];
        }

        $this->close_request($request->id, 'approved');
        $this->notify_submitter($request, 'approved', $note);

        // The features listen on this to do their work: a loan generates its
        // schedule, an increment writes the split, a document updates the master
        // record. Keeping that out of here means the engine stays generic.
        hooks()->do_action('approval_request_completed', [
            'request_id'   => $request->id,
            'request_type' => $request_type,
            'rel_id'       => $rel_id,
            'staff_id'     => (int) $request->staff_id,
            'status'       => 'approved',
        ]);

        return ['success' => true, 'completed' => true, 'status' => 'approved'];
    }

    /**
     * @param string $request_type
     * @param int    $rel_id
     *
     * @return bool
     */
    public function cancel($request_type, $rel_id)
    {
        $request = $this->get_request($request_type, $rel_id);

        if (!$request || $request->status !== 'pending') {
            return false;
        }

        $this->close_request($request->id, 'cancelled');

        return true;
    }

    /**
     * @param int    $request_id
     * @param string $status
     */
    private function close_request($request_id, $status)
    {
        $this->db->where('id', (int) $request_id);
        $this->db->update($this->request_table, [
            'status'       => $status,
            'completed_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /* ================================================================== inbox */

    /**
     * Requests waiting on this staff member, across every request type.
     *
     * @param int $staff_id
     *
     * @return array
     */
    public function get_pending_for($staff_id)
    {
        if (!$this->db->table_exists($this->action_table)) {
            return [];
        }

        $staff_id = (int) $staff_id;

        $this->db->select($this->request_table . '.*,'
            . ' ' . $this->action_table . '.id as action_id,'
            . ' ' . $this->action_table . '.level_no,'
            . ' ' . $this->action_table . '.level_name,'
            . ' ' . $this->action_table . '.due_date,'
            . ' ' . $this->action_table . '.can_edit_amounts,'
            . ' CONCAT(' . db_prefix() . 'staff.firstname, " ", ' . db_prefix() . 'staff.lastname) as staff_name');
        $this->db->join(
            $this->action_table,
            $this->action_table . '.approval_request_id = ' . $this->request_table . '.id'
                . ' AND ' . $this->action_table . '.level_no = ' . $this->request_table . '.current_level'
        );
        $this->db->join(
            db_prefix() . 'staff',
            db_prefix() . 'staff.staffid = ' . $this->request_table . '.staff_id',
            'left'
        );
        $this->db->where($this->request_table . '.status', 'pending');
        $this->db->where($this->action_table . '.action', 'pending');
        $this->db->where($this->action_table . '.approver_staff_id', $staff_id);
        $this->db->order_by($this->request_table . '.submitted_at', 'ASC');

        return $this->db->get($this->request_table)->result_array();
    }

    /**
     * @param int $staff_id
     *
     * @return int
     */
    public function count_pending_for($staff_id)
    {
        return count($this->get_pending_for($staff_id));
    }

    /* ========================================================== notifications */

    /**
     * @param string $request_type
     * @param int    $rel_id
     *
     * @return string admin-relative link
     */
    public function link_for($request_type, $rel_id)
    {
        switch ($request_type) {
            case self::TYPE_LOAN:
                return 'loans/view/' . (int) $rel_id;
            case self::TYPE_INCREMENT:
                return 'increments/view/' . (int) $rel_id;
            case self::TYPE_VACATION:
                return 'timesheets/requisition_detail/' . (int) $rel_id;
            case self::TYPE_DOCUMENT:
                return 'employee_documents/review/' . (int) $rel_id;
            case self::TYPE_ACTIVITY:
                return 'project_activities/review/' . (int) $rel_id;
        }

        return 'approvals';
    }

    /**
     * A staff member's name, read directly.
     *
     * Not get_staff_full_name(): that helper caches through $CI->app_object_cache, a
     * library only loaded on the admin controller, so it fatals when this model is
     * used from the cron, the API or the CLI - all of which reach this code.
     *
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

    /**
     * @param int $request_id
     */
    private function notify_current_approver($request_id)
    {
        $request = $this->get_request_by_id($request_id);

        if (!$request) {
            return;
        }

        $this->db->where('approval_request_id', $request_id);
        $this->db->where('level_no', $request->current_level);
        $action = $this->db->get($this->action_table)->row();

        if (!$action || empty($action->approver_staff_id)) {
            return;
        }

        $types = $this->request_types();
        $label = $types[$request->request_type] ?? $request->request_type;

        add_notification([
            'description' => $label . ' - ' . $this->staff_name($request->staff_id)
                . ' - ' . _l('approval_awaiting_your_approval') . ' (' . _l('approval_level') . ' '
                . $request->current_level . ')',
            'touserid'    => (int) $action->approver_staff_id,
            'link'        => $this->link_for($request->request_type, $request->rel_id),
            'fromcompany' => 1,
        ]);
    }

    /**
     * @param object $request
     * @param string $status
     * @param string $note
     */
    private function notify_submitter($request, $status, $note = '')
    {
        $recipients = array_unique(array_filter([
            (int) $request->staff_id,
            (int) $request->submitted_by,
        ]));

        $types = $this->request_types();
        $label = $types[$request->request_type] ?? $request->request_type;

        foreach ($recipients as $recipient) {
            add_notification([
                'description' => $label . ' - '
                    . ($status === 'approved' ? _l('approval_was_approved') : _l('approval_was_rejected'))
                    . ($note !== '' ? ': ' . $note : ''),
                'touserid'    => $recipient,
                'link'        => $this->link_for($request->request_type, $request->rel_id),
                'fromcompany' => 1,
            ]);
        }
    }
}
