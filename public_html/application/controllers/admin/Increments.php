<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Salary increment requests (requirement 12).
 *
 * Raised by a direct manager or HR. The form pulls in the automatic context; the
 * approver at the amount-setting level may adjust the figure; on final approval the
 * increment is split onto the salary and the change is recorded. The document goes to
 * finance then the managing director - that ordering is the approval flow, configured
 * as data, not hard-coded here.
 *
 * Gated to HR/payroll/managers - unlike loans, an ordinary employee does not raise
 * their own increment.
 */
class Increments extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('increment_model');
        $this->load->model('hr_masters_model');
        $this->load->model('approval_flow_model');
    }

    /**
     * May raise and see all increments.
     */
    private function can_manage()
    {
        return is_admin()
            || has_permission('hrp_setting', '', 'view')
            || has_permission('hrm_hr_records', '', 'view')
            || has_permission('hrm_hr_records', '', 'edit')
            || has_permission('staff', '', 'view');
    }

    private function guard()
    {
        if (!$this->can_manage()) {
            access_denied('increments');
        }
    }

    public function index()
    {
        $this->guard();

        $data['requests'] = $this->increment_model->get_list();
        $data['title']    = _l('increment_requests');
        $this->load->view('admin/increments/list', $data);
    }

    public function view($id)
    {
        $request = $this->increment_model->get($id);

        if (!$request) {
            show_404();
        }

        // The approver for this request may view it even without the manage right.
        if (!$this->can_manage() && !$this->is_an_approver($id)) {
            access_denied('increments');
        }

        $data['request'] = $request;
        $data['chain']   = $this->approval_flow_model->get_chain(Approval_flow_model::TYPE_INCREMENT, $id);
        $data['current'] = $this->approval_flow_model->current_action(Approval_flow_model::TYPE_INCREMENT, $id);
        $data['can_act'] = $data['current']
            && $this->approval_flow_model->can_act($data['current'], get_staff_user_id());
        $data['history'] = $this->increment_model->increment_history($request->staff_id);
        $data['title']   = $request->reference ?: _l('increment_request');

        $this->load->view('admin/increments/view', $data);
    }

    public function apply_form($id = '')
    {
        $this->guard();

        $request = null;

        if ($id !== '') {
            $request = $this->increment_model->get($id);

            if (!$request) {
                show_404();
            }

            if ($request->status !== 'draft') {
                redirect(admin_url('increments/view/' . $id));
            }
        }

        if ($this->input->post()) {
            $this->handle_post($id, $request);

            return;
        }

        $staff_id = $request ? (int) $request->staff_id : (int) $this->input->get('staff_id');

        $data['request']  = $request;
        $data['for_staff_id'] = $staff_id;
        $data['staff']    = $this->staff_model->get('', ['active' => 1]);
        $data['auto']     = $staff_id > 0 ? $this->increment_model->gather_employee_data($staff_id) : null;
        $data['split']    = $staff_id > 0 && $data['auto']['category_id']
            ? $this->hr_masters_model->split_totals($data['auto']['category_id']) : null;
        $data['title']    = $request ? _l('increment_edit') : _l('increment_new');

        $this->load->view('admin/increments/apply', $data);
    }

    private function handle_post($id, $request)
    {
        $post = $this->input->post();

        if ($id !== '' && $request) {
            $ok = $this->increment_model->update_draft($id, $post);
            $request_id = (int) $id;
        } else {
            $request_id = $this->increment_model->create($post);
            $ok = (bool) $request_id;
        }

        if (!$ok) {
            set_alert('warning', _l('increment_save_failed'));
            redirect(admin_url('increments/apply_form' . ($id !== '' ? '/' . $id : '')));

            return;
        }

        if ($this->input->post('submit_for_approval')) {
            $result = $this->increment_model->submit($request_id);
            if ($result['success']) {
                set_alert('success', _l('increment_submitted'));
            } else {
                set_alert('warning', $this->submit_error($result));
            }
        } else {
            set_alert('success', _l('increment_draft_saved'));
        }

        redirect(admin_url('increments/view/' . $request_id));
    }

    public function submit($id)
    {
        $this->guard();

        $result = $this->increment_model->submit($id);

        if ($result['success']) {
            set_alert('success', _l('increment_submitted'));
        } else {
            set_alert('warning', $this->submit_error($result));
        }

        redirect(admin_url('increments/view/' . $id));
    }

    public function act($id)
    {
        if (!$this->input->post()) {
            redirect(admin_url('increments/view/' . $id));
        }

        $request = $this->increment_model->get($id);

        if (!$request) {
            show_404();
        }

        $current = $this->approval_flow_model->current_action(Approval_flow_model::TYPE_INCREMENT, $id);

        if (!$current || !$this->approval_flow_model->can_act($current, get_staff_user_id())) {
            access_denied('increments');
        }

        $decision = $this->input->post('decision');

        // The amount may be adjusted at the level configured to do so, on approval.
        if ($decision === 'approved'
            && !empty($current['can_edit_amounts'])
            && $this->input->post('approved_amount') !== null
            && $this->input->post('approved_amount') !== '') {
            $this->increment_model->set_approved_amount($id, $this->input->post('approved_amount'));
        }

        $result = $this->approval_flow_model->act(
            Approval_flow_model::TYPE_INCREMENT,
            (int) $id,
            $decision,
            (string) $this->input->post('note')
        );

        if ($result['success']) {
            if ($decision === 'approved' && $result['completed']) {
                // The apply step runs on the completion hook; reflect its outcome.
                $after = $this->increment_model->get($id);
                if ($after->status === 'applied') {
                    set_alert('success', _l('increment_approved_and_applied'));
                } else {
                    set_alert('warning', _l('increment_approved_not_applied_alert'));
                }
            } else {
                set_alert('success', $decision === 'approved'
                    ? _l('increment_level_approved') : _l('increment_rejected'));
            }
        } else {
            set_alert('warning', _l('increment_action_failed'));
        }

        redirect(admin_url('increments/view/' . $id));
    }

    public function cancel($id)
    {
        $this->guard();

        $this->approval_flow_model->cancel(Approval_flow_model::TYPE_INCREMENT, $id);
        $this->db->where('id', (int) $id);
        $this->db->update(db_prefix() . 'increment_request', ['status' => 'cancelled']);

        set_alert('success', _l('increment_cancelled'));
        redirect(admin_url('increments/view/' . $id));
    }

    public function delete($id)
    {
        $this->guard();

        if ($this->increment_model->delete_draft($id)) {
            set_alert('success', _l('increment_deleted'));
        } else {
            set_alert('warning', _l('increment_only_drafts_deletable'));
        }

        redirect(admin_url('increments'));
    }

    /**
     * The automatic context for a chosen employee, for the form.
     */
    public function employee_data()
    {
        if (!$this->input->is_ajax_request() || !$this->can_manage()) {
            show_404();
        }

        $staff_id = (int) $this->input->post('staff_id');
        $auto     = $this->increment_model->gather_employee_data($staff_id);

        $auto['split_ok'] = $auto['category_id']
            ? $this->hr_masters_model->split_totals($auto['category_id'])['ok'] : false;
        $auto['csrf_hash'] = $this->security->get_csrf_hash();

        echo json_encode($auto);
    }

    private function is_an_approver($id)
    {
        $current = $this->approval_flow_model->current_action(Approval_flow_model::TYPE_INCREMENT, $id);

        return $current && $this->approval_flow_model->can_act($current, get_staff_user_id());
    }

    private function submit_error($result)
    {
        switch ($result['error'] ?? '') {
            case 'no_flow_configured':
                return _l('increment_no_flow_configured');
            case 'flow_has_no_levels':
                return _l('increment_flow_no_levels');
            case 'approver_not_resolved':
                return _l('increment_approver_not_resolved');
            default:
                return _l('increment_submit_failed');
        }
    }
}
