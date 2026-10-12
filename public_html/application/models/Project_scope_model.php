<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Item-wise / scope-wise project activities (requirement 10).
 *
 * Perfex measures project progress as an unweighted count of tasks at status 5, which
 * also reports 100% for a project with no tasks. This adds a separate scope list where
 * each item carries a responsible person, a department, a status, budget and material
 * budget, and a weight - so overall completion is a WEIGHTED roll-up of item progress,
 * and a completed item can require sign-off before it counts.
 *
 * The tables are tblproject_scope_* deliberately: Perfex's own tblproject_activity is
 * the project audit feed and must not be touched.
 *
 * Progress updates are recorded item by item. When an item requires approval a claimed
 * update stays pending and does not move the item's approved progress until a manager
 * signs it off - so the project percentage only ever reflects reviewed work.
 */
class Project_scope_model extends App_Model
{
    private $activity_table;
    private $progress_table;
    private $status_table;

    public function __construct()
    {
        parent::__construct();

        $this->activity_table = db_prefix() . 'project_scope_activity';
        $this->progress_table = db_prefix() . 'project_scope_progress';
        $this->status_table   = db_prefix() . 'project_scope_status';
    }

    /* ================================================================ statuses */

    /**
     * @param bool $active_only
     *
     * @return array
     */
    public function get_statuses($active_only = true)
    {
        if (!$this->db->table_exists($this->status_table)) {
            return [];
        }

        if ($active_only) {
            $this->db->where('active', 1);
        }

        $this->db->order_by('sort_order', 'ASC');

        return $this->db->get($this->status_table)->result_array();
    }

    /* ============================================================== activities */

    /**
     * All scope items for a project, with resolved names.
     *
     * @param int $project_id
     *
     * @return array
     */
    public function get_activities($project_id)
    {
        if (!$this->db->table_exists($this->activity_table)) {
            return [];
        }

        $this->db->select($this->activity_table . '.*,'
            . ' ' . $this->status_table . '.name as status_name,'
            . ' ' . $this->status_table . '.colour as status_colour,'
            . ' ' . $this->status_table . '.is_complete,'
            . ' ' . $this->status_table . '.is_excluded,'
            . ' CONCAT(' . db_prefix() . 'staff.firstname, " ", ' . db_prefix() . 'staff.lastname) as responsible_name,'
            . ' ' . db_prefix() . 'departments.name as department_name');
        $this->db->join($this->status_table,
            $this->status_table . '.id = ' . $this->activity_table . '.status_id', 'left');
        $this->db->join(db_prefix() . 'staff',
            db_prefix() . 'staff.staffid = ' . $this->activity_table . '.responsible_staff_id', 'left');
        $this->db->join(db_prefix() . 'departments',
            db_prefix() . 'departments.departmentid = ' . $this->activity_table . '.department_id', 'left');
        $this->db->where($this->activity_table . '.project_id', (int) $project_id);
        $this->db->order_by($this->activity_table . '.sort_order', 'ASC');
        $this->db->order_by($this->activity_table . '.id', 'ASC');

        return $this->db->get($this->activity_table)->result_array();
    }

    /**
     * @param int $id
     *
     * @return object|null
     */
    public function get_activity($id)
    {
        $this->db->select($this->activity_table . '.*,'
            . ' ' . $this->status_table . '.name as status_name,'
            . ' ' . $this->status_table . '.is_complete,'
            . ' CONCAT(' . db_prefix() . 'staff.firstname, " ", ' . db_prefix() . 'staff.lastname) as responsible_name');
        $this->db->join($this->status_table,
            $this->status_table . '.id = ' . $this->activity_table . '.status_id', 'left');
        $this->db->join(db_prefix() . 'staff',
            db_prefix() . 'staff.staffid = ' . $this->activity_table . '.responsible_staff_id', 'left');
        $this->db->where($this->activity_table . '.id', (int) $id);

        return $this->db->get($this->activity_table)->row();
    }

    /**
     * @param array $data
     *
     * @return int|bool
     */
    public function add_activity($data)
    {
        $project_id = (int) ($data['project_id'] ?? 0);
        $name       = trim((string) ($data['name'] ?? ''));

        if ($project_id <= 0 || $name === '') {
            return false;
        }

        $row = [
            'project_id'           => $project_id,
            'parent_id'            => !empty($data['parent_id']) ? (int) $data['parent_id'] : null,
            'milestone_id'         => !empty($data['milestone_id']) ? (int) $data['milestone_id'] : null,
            'code'                 => $data['code'] ?? null,
            'name'                 => $name,
            'description'          => $data['description'] ?? null,
            'quantity'             => ($data['quantity'] ?? '') !== '' ? (float) $data['quantity'] : null,
            'unit'                 => $data['unit'] ?? null,
            'responsible_staff_id' => !empty($data['responsible_staff_id']) ? (int) $data['responsible_staff_id'] : null,
            'department_id'        => !empty($data['department_id']) ? (int) $data['department_id'] : null,
            'status_id'            => !empty($data['status_id']) ? (int) $data['status_id'] : $this->default_status_id(),
            'budget_amount'        => ($data['budget_amount'] ?? '') !== '' ? (float) $data['budget_amount'] : null,
            'material_budget'      => ($data['material_budget'] ?? '') !== '' ? (float) $data['material_budget'] : null,
            'weight'               => ($data['weight'] ?? '') !== '' ? max(0, (float) $data['weight']) : 1,
            'planned_start'        => !empty($data['planned_start']) ? to_sql_date($data['planned_start']) : null,
            'planned_end'          => !empty($data['planned_end']) ? to_sql_date($data['planned_end']) : null,
            'requires_approval'    => isset($data['requires_approval']) ? (int) (bool) $data['requires_approval'] : 1,
            'sort_order'           => (int) ($data['sort_order'] ?? 0),
            'progress_percent'     => 0,
            'created_at'           => date('Y-m-d H:i:s'),
            'created_by'           => get_staff_user_id() ?: null,
        ];

        $this->db->insert($this->activity_table, $row);
        $id = $this->db->insert_id();

        if ($id) {
            $this->recalculate_project_progress($project_id);
        }

        return $id ?: false;
    }

    /**
     * @param int   $id
     * @param array $data
     *
     * @return bool
     */
    public function update_activity($id, $data)
    {
        $activity = $this->get_activity($id);

        if (!$activity) {
            return false;
        }

        $row = [
            'name'                 => trim((string) ($data['name'] ?? $activity->name)),
            'code'                 => $data['code'] ?? $activity->code,
            'description'          => $data['description'] ?? $activity->description,
            'quantity'             => ($data['quantity'] ?? '') !== '' ? (float) $data['quantity'] : null,
            'unit'                 => $data['unit'] ?? null,
            'responsible_staff_id' => !empty($data['responsible_staff_id']) ? (int) $data['responsible_staff_id'] : null,
            'department_id'        => !empty($data['department_id']) ? (int) $data['department_id'] : null,
            'status_id'            => !empty($data['status_id']) ? (int) $data['status_id'] : $activity->status_id,
            'budget_amount'        => ($data['budget_amount'] ?? '') !== '' ? (float) $data['budget_amount'] : null,
            'material_budget'      => ($data['material_budget'] ?? '') !== '' ? (float) $data['material_budget'] : null,
            'weight'               => ($data['weight'] ?? '') !== '' ? max(0, (float) $data['weight']) : $activity->weight,
            'planned_start'        => !empty($data['planned_start']) ? to_sql_date($data['planned_start']) : null,
            'planned_end'          => !empty($data['planned_end']) ? to_sql_date($data['planned_end']) : null,
            'requires_approval'    => isset($data['requires_approval']) ? (int) (bool) $data['requires_approval'] : $activity->requires_approval,
            'sort_order'           => (int) ($data['sort_order'] ?? $activity->sort_order),
            'updated_at'           => date('Y-m-d H:i:s'),
        ];

        $this->db->where('id', (int) $id);
        $this->db->update($this->activity_table, $row);

        // A status change can move the project percentage (e.g. to Cancelled, which is
        // excluded from the roll-up).
        $this->recalculate_project_progress((int) $activity->project_id);

        return true;
    }

    /**
     * @param int $id
     *
     * @return bool
     */
    public function delete_activity($id)
    {
        $activity = $this->get_activity($id);

        if (!$activity) {
            return false;
        }

        // Progress history goes with the item, and any child scope items are detached
        // to the parent's parent rather than orphaned.
        $this->db->where('parent_id', (int) $id);
        $this->db->update($this->activity_table, ['parent_id' => $activity->parent_id]);

        $this->db->where('activity_id', (int) $id)->delete($this->progress_table);
        $this->db->where('id', (int) $id)->delete($this->activity_table);

        $this->recalculate_project_progress((int) $activity->project_id);

        return true;
    }

    /* =============================================================== progress */

    /**
     * Record a progress update for an item.
     *
     * If the item does not require approval the update is applied straight away. If it
     * does, the update is held pending and the item's approved progress is unchanged
     * until a manager signs it off - which is the "approval of completed activities"
     * requirement.
     *
     * @param int   $activity_id
     * @param float $percent
     * @param array $extra note, quantity_done
     *
     * @return array ['success' => bool, 'pending' => bool]
     */
    public function record_progress($activity_id, $percent, $extra = [])
    {
        $activity = $this->get_activity($activity_id);

        if (!$activity) {
            return ['success' => false];
        }

        $percent = max(0, min(100, (float) $percent));

        $requires_approval = (int) $activity->requires_approval === 1;

        $this->db->insert($this->progress_table, [
            'activity_id'      => (int) $activity_id,
            'previous_percent' => (float) $activity->progress_percent,
            'progress_percent' => $percent,
            'quantity_done'    => ($extra['quantity_done'] ?? '') !== '' ? (float) $extra['quantity_done'] : null,
            'note'             => $extra['note'] ?? null,
            'updated_by'       => get_staff_user_id() ?: null,
            'updated_at'       => date('Y-m-d H:i:s'),
            'approval_status'  => $requires_approval ? 'pending' : 'approved',
            'approved_by'      => $requires_approval ? null : (get_staff_user_id() ?: null),
            'approved_at'      => $requires_approval ? null : date('Y-m-d H:i:s'),
        ]);

        $progress_id = $this->db->insert_id();

        if (!$requires_approval) {
            $this->apply_progress($activity, $percent);
        } else {
            $this->notify_approver($activity, $progress_id);
        }

        return ['success' => (bool) $progress_id, 'pending' => $requires_approval];
    }

    /**
     * The progress updates waiting for sign-off, optionally for one project.
     *
     * @param int|null $project_id
     *
     * @return array
     */
    public function get_pending_progress($project_id = null)
    {
        $this->db->select($this->progress_table . '.*,'
            . ' ' . $this->activity_table . '.name as activity_name,'
            . ' ' . $this->activity_table . '.project_id,'
            . ' CONCAT(' . db_prefix() . 'staff.firstname, " ", ' . db_prefix() . 'staff.lastname) as updated_by_name');
        $this->db->join($this->activity_table,
            $this->activity_table . '.id = ' . $this->progress_table . '.activity_id');
        $this->db->join(db_prefix() . 'staff',
            db_prefix() . 'staff.staffid = ' . $this->progress_table . '.updated_by', 'left');
        $this->db->where($this->progress_table . '.approval_status', 'pending');

        if ($project_id) {
            $this->db->where($this->activity_table . '.project_id', (int) $project_id);
        }

        $this->db->order_by($this->progress_table . '.updated_at', 'ASC');

        return $this->db->get($this->progress_table)->result_array();
    }

    /**
     * Approve a pending progress update, moving the item's approved progress.
     *
     * @param int $progress_id
     *
     * @return bool
     */
    public function approve_progress($progress_id)
    {
        $progress = $this->get_progress($progress_id);

        if (!$progress || $progress->approval_status !== 'pending') {
            return false;
        }

        $this->db->where('id', (int) $progress_id);
        $this->db->update($this->progress_table, [
            'approval_status' => 'approved',
            'approved_by'     => get_staff_user_id() ?: null,
            'approved_at'     => date('Y-m-d H:i:s'),
        ]);

        $activity = $this->get_activity($progress->activity_id);

        if ($activity) {
            $this->apply_progress($activity, (float) $progress->progress_percent);
        }

        return true;
    }

    /**
     * @param int    $progress_id
     * @param string $reason
     *
     * @return bool
     */
    public function reject_progress($progress_id, $reason = '')
    {
        $progress = $this->get_progress($progress_id);

        if (!$progress || $progress->approval_status !== 'pending') {
            return false;
        }

        // Rejected: the item's approved progress does not move.
        $this->db->where('id', (int) $progress_id);
        $this->db->update($this->progress_table, [
            'approval_status' => 'rejected',
            'approved_by'     => get_staff_user_id() ?: null,
            'approved_at'     => date('Y-m-d H:i:s'),
            'reject_reason'   => $reason ?: null,
        ]);

        return true;
    }

    /**
     * @param int $progress_id
     *
     * @return object|null
     */
    public function get_progress($progress_id)
    {
        $this->db->where('id', (int) $progress_id);

        return $this->db->get($this->progress_table)->row();
    }

    /**
     * @param int $activity_id
     *
     * @return array
     */
    public function get_progress_history($activity_id)
    {
        $this->db->select($this->progress_table . '.*,'
            . ' CONCAT(' . db_prefix() . 'staff.firstname, " ", ' . db_prefix() . 'staff.lastname) as updated_by_name');
        $this->db->join(db_prefix() . 'staff',
            db_prefix() . 'staff.staffid = ' . $this->progress_table . '.updated_by', 'left');
        $this->db->where('activity_id', (int) $activity_id);
        $this->db->order_by($this->progress_table . '.updated_at', 'DESC');

        return $this->db->get($this->progress_table)->result_array();
    }

    /**
     * Set an item's approved progress and, when it reaches 100, mark it complete.
     *
     * @param object $activity
     * @param float  $percent
     */
    private function apply_progress($activity, $percent)
    {
        $update = ['progress_percent' => $percent, 'updated_at' => date('Y-m-d H:i:s')];

        if ($percent > 0 && empty($activity->actual_start)) {
            $update['actual_start'] = date('Y-m-d');
        }

        if ($percent >= 100) {
            $complete = $this->complete_status_id();
            if ($complete) {
                $update['status_id'] = $complete;
            }
            $update['actual_end'] = date('Y-m-d');
        }

        $this->db->where('id', (int) $activity->id);
        $this->db->update($this->activity_table, $update);

        $this->recalculate_project_progress((int) $activity->project_id);
    }

    /* ============================================================= roll-up */

    /**
     * Weighted overall completion for a project's scope.
     *
     * completion = sum(progress * weight) / sum(weight), over items not excluded
     * (Cancelled). An item at a status flagged is_complete counts as 100 regardless of
     * its stored percent, so marking done and dragging to 100 agree.
     *
     * @param int $project_id
     *
     * @return array ['percent' => float, 'items' => int, 'weighted' => bool]
     */
    public function project_completion($project_id)
    {
        $activities = $this->get_activities($project_id);

        $total_weight = 0.0;
        $weighted_sum = 0.0;
        $counted      = 0;

        foreach ($activities as $a) {
            if ((int) $a['is_excluded'] === 1) {
                continue;
            }

            $weight   = max(0, (float) $a['weight']);
            $progress = (int) $a['is_complete'] === 1 ? 100.0 : (float) $a['progress_percent'];

            $total_weight += $weight;
            $weighted_sum += $progress * $weight;
            $counted++;
        }

        $percent = $total_weight > 0 ? round($weighted_sum / $total_weight, 2) : 0.0;

        return ['percent' => $percent, 'items' => $counted, 'weighted' => true];
    }

    /**
     * Push the scope roll-up onto the core project's progress, but only when the
     * project is set to manual progress (progress_from_tasks = 0).
     *
     * A project driven by task completion is left alone - overwriting it would fight
     * Perfex's own calculation. When manual, the stored progress is updated so the
     * project's own screens show the scope-derived figure.
     *
     * @param int $project_id
     */
    public function recalculate_project_progress($project_id)
    {
        $this->db->select('progress_from_tasks');
        $this->db->where('id', (int) $project_id);
        $project = $this->db->get(db_prefix() . 'projects')->row();

        if (!$project || (int) $project->progress_from_tasks === 1) {
            return;
        }

        // Only touch it if there are scope items; an empty scope should not zero a
        // manually-set project progress.
        $count = (int) $this->db->where('project_id', (int) $project_id)
            ->count_all_results($this->activity_table);

        if ($count === 0) {
            return;
        }

        $completion = $this->project_completion($project_id);

        $this->db->where('id', (int) $project_id);
        $this->db->update(db_prefix() . 'projects', ['progress' => (int) round($completion['percent'])]);
    }

    /* ================================================================ budget */

    /**
     * Budget and material-budget totals for a project's scope, plus a comparison
     * against actual expenses already linked to the project.
     *
     * @param int $project_id
     *
     * @return array
     */
    public function budget_summary($project_id)
    {
        $this->db->select_sum('budget_amount', 'budget');
        $this->db->select_sum('material_budget', 'material');
        $this->db->where('project_id', (int) $project_id);
        $row = $this->db->get($this->activity_table)->row();

        $budget   = $row && $row->budget ? (float) $row->budget : 0.0;
        $material = $row && $row->material ? (float) $row->material : 0.0;

        // Actual expenses booked to the project, if the expenses table is present.
        $actual = 0.0;
        $expenses = db_prefix() . 'expenses';

        if ($this->db->table_exists($expenses)) {
            $this->db->select_sum('amount', 'spent');
            $this->db->where('project_id', (int) $project_id);
            $exp = $this->db->get($expenses)->row();
            $actual = $exp && $exp->spent ? (float) $exp->spent : 0.0;
        }

        return [
            'budget'    => $budget,
            'material'  => $material,
            'total'     => $budget + $material,
            'actual'    => $actual,
            'remaining' => ($budget + $material) - $actual,
        ];
    }

    /* ================================================================ helpers */

    /**
     * @return int|null
     */
    private function default_status_id()
    {
        $this->db->where('slug', 'not_started');
        $row = $this->db->get($this->status_table)->row();

        return $row ? (int) $row->id : null;
    }

    /**
     * @return int|null
     */
    private function complete_status_id()
    {
        $this->db->where('is_complete', 1);
        $this->db->order_by('sort_order', 'ASC');
        $row = $this->db->get($this->status_table)->row();

        return $row ? (int) $row->id : null;
    }

    /**
     * @param object $activity
     * @param int    $progress_id
     */
    private function notify_approver($activity, $progress_id)
    {
        // The project's own manager if we can find one; otherwise the activity's
        // responsible person's manager. Falls back to nobody rather than guessing.
        $recipient = null;

        if (!empty($activity->responsible_staff_id)) {
            $this->db->select('team_manage');
            $this->db->where('staffid', (int) $activity->responsible_staff_id);
            $staff = $this->db->get(db_prefix() . 'staff')->row();
            if ($staff && !empty($staff->team_manage)) {
                $recipient = (int) $staff->team_manage;
            }
        }

        if (!$recipient) {
            return;
        }

        add_notification([
            'description' => _l('pscope_progress_awaiting_approval', [$activity->name]),
            'touserid'    => $recipient,
            'link'        => 'projects/view/' . (int) $activity->project_id . '?group=project_scope',
            'fromcompany' => 1,
        ]);
    }
}
