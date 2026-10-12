<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Item-wise project scope / activity management (requirement 10).
 *
 * The list itself renders inside the project view as a tab (see the project_scope tab
 * registered in my_functions_helper). This controller handles the add / edit / progress
 * / approve actions posted from that tab, plus the cross-project pending-approval queue.
 */
class Project_scope extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('project_scope_model');
        $this->load->model('projects_model');
    }

    /**
     * Editing scope needs project edit rights; recording progress needs at least
     * project view. Approving needs edit.
     */
    private function can_edit($project_id)
    {
        return staff_can('edit', 'projects')
            || (staff_can('edit_own', 'projects') && $this->is_project_member($project_id));
    }

    private function can_view($project_id)
    {
        return staff_can('view', 'projects')
            || (staff_can('view_own', 'projects') && $this->is_project_member($project_id));
    }

    private function is_project_member($project_id)
    {
        return $this->projects_model->is_member((int) $project_id, get_staff_user_id());
    }

    /* ============================================================== activities */

    public function save()
    {
        if (!$this->input->post()) {
            redirect(admin_url('projects/list_projects'));
        }

        $project_id = (int) $this->input->post('project_id');

        if (!$this->can_edit($project_id)) {
            access_denied('projects');
        }

        $id = $this->input->post('id');

        if ($id) {
            $ok = $this->project_scope_model->update_activity((int) $id, $this->input->post());
        } else {
            $ok = (bool) $this->project_scope_model->add_activity($this->input->post());
        }

        set_alert($ok ? 'success' : 'warning',
            $ok ? _l('pscope_saved') : _l('pscope_save_failed'));

        redirect($this->tab_url($project_id));
    }

    public function delete($id, $project_id)
    {
        if (!$this->can_edit($project_id)) {
            access_denied('projects');
        }

        $this->project_scope_model->delete_activity($id);
        set_alert('success', _l('pscope_deleted'));

        redirect($this->tab_url($project_id));
    }

    /* =============================================================== progress */

    public function progress()
    {
        if (!$this->input->post()) {
            redirect(admin_url('projects/list_projects'));
        }

        $activity_id = (int) $this->input->post('activity_id');
        $activity    = $this->project_scope_model->get_activity($activity_id);

        if (!$activity) {
            show_404();
        }

        if (!$this->can_view($activity->project_id)) {
            access_denied('projects');
        }

        $result = $this->project_scope_model->record_progress($activity_id, $this->input->post('progress_percent'), [
            'note'          => $this->input->post('note'),
            'quantity_done' => $this->input->post('quantity_done'),
        ]);

        if ($result['success']) {
            set_alert('success', $result['pending'] ? _l('pscope_progress_pending') : _l('pscope_progress_recorded'));
        } else {
            set_alert('warning', _l('pscope_progress_failed'));
        }

        redirect($this->tab_url($activity->project_id));
    }

    /**
     * Approve or reject a pending progress update.
     */
    public function review_progress()
    {
        if (!$this->input->post()) {
            redirect(admin_url('projects/list_projects'));
        }

        $progress_id = (int) $this->input->post('progress_id');
        $progress    = $this->project_scope_model->get_progress($progress_id);

        if (!$progress) {
            show_404();
        }

        $activity = $this->project_scope_model->get_activity($progress->activity_id);

        if (!$activity || !$this->can_edit($activity->project_id)) {
            access_denied('projects');
        }

        if ($this->input->post('decision') === 'approve') {
            $this->project_scope_model->approve_progress($progress_id);
            set_alert('success', _l('pscope_progress_approved'));
        } else {
            $this->project_scope_model->reject_progress($progress_id, (string) $this->input->post('reason'));
            set_alert('success', _l('pscope_progress_rejected'));
        }

        $redirect = $this->input->post('redirect_to') === 'queue'
            ? admin_url('project_scope/approvals')
            : $this->tab_url($activity->project_id);

        redirect($redirect);
    }

    /**
     * Cross-project queue of scope progress awaiting sign-off.
     */
    public function approvals()
    {
        if (!staff_can('view', 'projects')) {
            access_denied('projects');
        }

        $data['pending'] = $this->project_scope_model->get_pending_progress();
        $data['title']   = _l('pscope_approvals');
        $this->load->view('admin/project_scope/approvals', $data);
    }

    /* ================================================================ helpers */

    private function tab_url($project_id)
    {
        return admin_url('projects/view/' . (int) $project_id . '?group=project_scope');
    }
}
