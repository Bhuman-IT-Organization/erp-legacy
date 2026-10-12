<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Employee loan applications (requirement 11).
 *
 * An employee applies with a reason, amount, proposed instalment and first
 * instalment date. Service years and outstanding balance are filled in
 * automatically. The schedule - including the last instalment date - is shown before
 * submission and generated for real on approval. The number of approval levels comes
 * from the employee's category, and the approved amount and instalment are set at the
 * level configured to edit them.
 *
 * Access model: an employee sees and creates their own; anyone with HR/payroll rights
 * sees all; approvers act on what is in their queue.
 */
class Loans extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('loan_model');
        $this->load->model('hr_masters_model');
        $this->load->model('approval_flow_model');
    }

    /**
     * May the current user see every loan, or only their own?
     */
    private function can_view_all()
    {
        return is_admin()
            || has_permission('hrp_setting', '', 'view')
            || has_permission('hrm_hr_records', '', 'view')
            || has_permission('staff', '', 'view');
    }

    public function index()
    {
        if ($this->can_view_all()) {
            $data['loans'] = $this->loan_model->get_list();
            $data['scope'] = 'all';
        } else {
            // Everyone may apply for and see their own loans; no special right needed.
            $data['loans'] = $this->loan_model->get_for_staff(get_staff_user_id());
            $data['scope'] = 'own';
        }

        $data['title'] = _l('loan_applications');
        $this->load->view('admin/loans/list', $data);
    }

    public function view($id)
    {
        $loan = $this->loan_model->get($id);

        if (!$loan) {
            show_404();
        }

        if (!$this->can_view_all()
            && (int) $loan->staff_id !== (int) get_staff_user_id()
            && !$this->is_an_approver($id)) {
            access_denied('loans');
        }

        $data['loan']    = $loan;
        $data['chain']   = $this->approval_flow_model->get_chain(Approval_flow_model::TYPE_LOAN, $id);
        $data['current'] = $this->approval_flow_model->current_action(Approval_flow_model::TYPE_LOAN, $id);
        $data['can_act'] = $data['current']
            && $this->approval_flow_model->can_act($data['current'], get_staff_user_id());
        $data['title']   = $loan->reference ?: _l('loan_application');

        $this->load->view('admin/loans/view', $data);
    }

    /**
     * The application form. A manager or HR may apply on behalf of another employee
     * by passing ?staff_id=; otherwise it is the current user's own.
     */
    public function apply($id = '')
    {
        $loan = null;

        if ($id !== '') {
            $loan = $this->loan_model->get($id);

            if (!$loan) {
                show_404();
            }

            // A submitted or decided loan is no longer editable.
            if ($loan->status !== 'draft') {
                redirect(admin_url('loans/view/' . $id));
            }

            if (!$this->can_view_all() && (int) $loan->staff_id !== (int) get_staff_user_id()) {
                access_denied('loans');
            }
        }

        if ($this->input->post()) {
            $this->handle_apply_post($id, $loan);

            return;
        }

        $staff_id = $loan ? (int) $loan->staff_id : (int) get_staff_user_id();

        if ($this->input->get('staff_id') && $this->can_view_all()) {
            $staff_id = (int) $this->input->get('staff_id');
        }

        $data['loan']         = $loan;
        $data['for_staff_id']  = $staff_id;
        $data['can_view_all'] = $this->can_view_all();
        $data['staff']        = $this->can_view_all() ? $this->staff_model->get('', ['active' => 1]) : [];

        // The read-only context the form shows.
        $data['service_years']       = $this->hr_masters_model->service_years($staff_id);
        $data['outstanding_balance'] = $this->loan_model->outstanding_balance($staff_id);
        $data['current_salary']      = $this->loan_model->current_salary($staff_id);

        $category_id     = $this->approval_flow_model->staff_category_id($staff_id);
        $data['policy']  = $this->hr_masters_model->get_loan_policy_for($category_id);

        $data['title'] = $loan ? _l('loan_edit_application') : _l('loan_new_application');
        $this->load->view('admin/loans/apply', $data);
    }

    /**
     * @param string $id
     * @param object $loan
     */
    private function handle_apply_post($id, $loan)
    {
        $post = $this->input->post();

        if ($id !== '' && $loan) {
            $ok = $this->loan_model->update_draft($id, $post);
            $loan_id = (int) $id;
        } else {
            $staff_id = (int) get_staff_user_id();

            if ($this->can_view_all() && !empty($post['staff_id'])) {
                $staff_id = (int) $post['staff_id'];
            }

            $post['staff_id'] = $staff_id;
            $loan_id = $this->loan_model->create($post);
            $ok = (bool) $loan_id;
        }

        if (!$ok) {
            // Almost always an eligibility failure; show why.
            $check = $this->loan_model->check_eligibility(
                $id !== '' ? $loan->staff_id : ($post['staff_id'] ?? get_staff_user_id()),
                $post
            );
            set_alert('warning', implode('. ', $check['errors']) ?: _l('loan_save_failed'));
            redirect(admin_url('loans/apply' . ($id !== '' ? '/' . $id : '')));

            return;
        }

        // "Save and submit" versus "Save draft".
        if ($this->input->post('submit_for_approval')) {
            $result = $this->loan_model->submit($loan_id);

            if ($result['success']) {
                set_alert('success', _l('loan_submitted'));
            } else {
                set_alert('warning', $this->submit_error($result));
            }
        } else {
            set_alert('success', _l('loan_draft_saved'));
        }

        redirect(admin_url('loans/view/' . $loan_id));
    }

    public function submit($id)
    {
        $loan = $this->loan_model->get($id);

        if (!$loan) {
            show_404();
        }

        if (!$this->can_view_all() && (int) $loan->staff_id !== (int) get_staff_user_id()) {
            access_denied('loans');
        }

        $result = $this->loan_model->submit($id);

        if ($result['success']) {
            set_alert('success', _l('loan_submitted'));
        } else {
            set_alert('warning', $this->submit_error($result));
        }

        redirect(admin_url('loans/view/' . $id));
    }

    /**
     * Approve or reject the current level, optionally setting the approved amounts.
     */
    public function act($id)
    {
        if (!$this->input->post()) {
            redirect(admin_url('loans/view/' . $id));
        }

        $loan = $this->loan_model->get($id);

        if (!$loan) {
            show_404();
        }

        $current = $this->approval_flow_model->current_action(Approval_flow_model::TYPE_LOAN, $id);

        if (!$current || !$this->approval_flow_model->can_act($current, get_staff_user_id())) {
            access_denied('loans');
        }

        $decision = $this->input->post('decision');

        // The amounts may only be set from the level that is allowed to, and only when
        // approving. This is the L2/L3 step from the requirement.
        if ($decision === 'approved' && !empty($current['can_edit_amounts'])) {
            $this->loan_model->set_approved_amounts($id, $this->input->post());
        }

        $result = $this->approval_flow_model->act(
            Approval_flow_model::TYPE_LOAN,
            (int) $id,
            $decision,
            (string) $this->input->post('note')
        );

        if ($result['success']) {
            set_alert('success', $decision === 'approved' ? _l('loan_level_approved') : _l('loan_rejected'));
        } else {
            set_alert('warning', _l('loan_action_failed'));
        }

        redirect(admin_url('loans/view/' . $id));
    }

    public function cancel($id)
    {
        $loan = $this->loan_model->get($id);

        if (!$loan) {
            show_404();
        }

        if (!$this->can_view_all() && (int) $loan->staff_id !== (int) get_staff_user_id()) {
            access_denied('loans');
        }

        $this->approval_flow_model->cancel(Approval_flow_model::TYPE_LOAN, $id);

        $this->db->where('id', (int) $id);
        $this->db->update(db_prefix() . 'loan_application', ['status' => 'cancelled']);

        set_alert('success', _l('loan_cancelled'));
        redirect(admin_url('loans/view/' . $id));
    }

    public function delete($id)
    {
        $loan = $this->loan_model->get($id);

        if (!$loan) {
            show_404();
        }

        if (!$this->can_view_all() && (int) $loan->staff_id !== (int) get_staff_user_id()) {
            access_denied('loans');
        }

        if ($this->loan_model->delete_draft($id)) {
            set_alert('success', _l('loan_deleted'));
        } else {
            set_alert('warning', _l('loan_only_drafts_deletable'));
        }

        redirect(admin_url('loans'));
    }

    /**
     * Live schedule preview for the apply form.
     */
    public function preview_schedule()
    {
        if (!$this->input->is_ajax_request()) {
            show_404();
        }

        $preview = $this->loan_model->preview_schedule(
            (float) $this->input->post('amount'),
            (float) $this->input->post('instalment'),
            $this->input->post('first_date') ? to_sql_date($this->input->post('first_date')) : null
        );

        // Hand the current token back so the form keeps working if regeneration is on.
        $preview['csrf_hash'] = $this->security->get_csrf_hash();

        echo json_encode($preview);
    }

    /**
     * Read-only context for the form when the target employee changes.
     */
    public function staff_context()
    {
        if (!$this->input->is_ajax_request() || !$this->can_view_all()) {
            show_404();
        }

        $staff_id = (int) $this->input->post('staff_id');

        echo json_encode([
            'service_years'       => $this->hr_masters_model->service_years($staff_id),
            'outstanding_balance' => $this->loan_model->outstanding_balance($staff_id),
            'current_salary'      => $this->loan_model->current_salary($staff_id),
            'csrf_hash'           => $this->security->get_csrf_hash(),
        ]);
    }

    /**
     * @param int $loan_id
     *
     * @return bool
     */
    private function is_an_approver($loan_id)
    {
        $current = $this->approval_flow_model->current_action(Approval_flow_model::TYPE_LOAN, $loan_id);

        return $current && $this->approval_flow_model->can_act($current, get_staff_user_id());
    }

    /**
     * @param array $result
     *
     * @return string
     */
    private function submit_error($result)
    {
        switch ($result['error'] ?? '') {
            case 'no_flow_configured':
                return _l('loan_no_flow_configured');
            case 'flow_has_no_levels':
                return _l('loan_flow_no_levels');
            case 'approver_not_resolved':
                return _l('loan_approver_not_resolved');
            default:
                return _l('loan_submit_failed');
        }
    }
}
