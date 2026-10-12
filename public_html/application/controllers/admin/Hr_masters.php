<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * HR master data: employee categories, visa types, document types, loan policy,
 * increment split rules, approval flows and per-employee category assignment.
 *
 * This is the screen that makes the rest of the HR extensions work. The application
 * ships no lending limits, no allowance mapping for the salary split and no approval
 * chains, because those are company policy. Loans and increments stay inert until they
 * are entered here, rather than applying an invented rule to somebody's pay.
 *
 * Tabbed with ?group=, matching admin/attendance_rules and the timesheets module.
 */
class Hr_masters extends AdminController
{
    /** Tabs, in display order. */
    private $groups = [
        'categories',
        'visa_types',
        'document_types',
        'loan_policy',
        'increment_split',
        'approval_flows',
        'assignments',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->load->model('hr_masters_model');
        $this->load->model('approval_flow_model');
    }

    /**
     * These rows decide who signs for money and how a salary increase is split, so
     * the gate is the payroll/HR settings one rather than general staff access.
     */
    private function check_permission()
    {
        if (is_admin()) {
            return;
        }

        if (has_permission('hrp_setting', '', 'view')
            || has_permission('timesheet_settings', '', 'view')
            || has_permission('hrm_hr_records', '', 'edit')) {
            return;
        }

        access_denied('hr_masters');
    }

    public function index()
    {
        $this->check_permission();

        $group = $this->input->get('group');

        if (!in_array($group, $this->groups, true)) {
            $group = 'categories';
        }

        $data['group']      = $group;
        $data['groups']     = $this->groups;
        $data['title']      = _l('hr_masters');
        $data['categories'] = $this->hr_masters_model->get_categories();

        switch ($group) {
            case 'visa_types':
                $data['visa_types'] = $this->hr_masters_model->get_visa_types();
                break;

            case 'document_types':
                $data['document_types'] = $this->hr_masters_model->get_document_types();
                $data['staff_fields']   = $this->hr_masters_model->staff_document_fields();
                break;

            case 'loan_policy':
                $data['policies'] = $this->hr_masters_model->get_loan_policies();
                break;

            case 'increment_split':
                $data['split_rules']     = $this->hr_masters_model->get_split_rules();
                $data['allowance_types'] = $this->hr_masters_model->get_allowance_types();

                // Surfaced per category so a split that does not add up to 100 is
                // visible on the screen rather than discovered when pay is wrong.
                $data['split_totals'] = [];
                foreach ($data['categories'] as $category) {
                    $data['split_totals'][$category['id']] = $this->hr_masters_model->split_totals($category['id']);
                }
                break;

            case 'approval_flows':
                $data['flows']          = $this->approval_flow_model->get_flows();
                $data['request_types']  = $this->approval_flow_model->request_types();
                $data['approver_types'] = $this->approval_flow_model->approver_types();
                $data['staff']          = $this->staff_model->get('', ['active' => 1]);
                $data['roles']          = $this->roles_model->get();
                break;

            case 'assignments':
                $data['profiles']   = $this->hr_masters_model->get_staff_profiles();
                $data['visa_types'] = $this->hr_masters_model->get_visa_types(true);
                break;
        }

        $this->load->view('admin/hr_masters/manage', $data);
    }

    /* ============================================================ categories */

    public function save_category()
    {
        $this->check_permission();

        if (!$this->input->post()) {
            redirect(admin_url('hr_masters'));
        }

        $post = $this->input->post();
        $id   = !empty($post['id']) ? (int) $post['id'] : null;
        unset($post['id']);

        if ($this->hr_masters_model->save_category($post, $id)) {
            set_alert('success', $id ? _l('updated_successfully', _l('hr_employee_category'))
                : _l('added_successfully', _l('hr_employee_category')));
        } else {
            set_alert('warning', _l('hr_category_invalid'));
        }

        redirect(admin_url('hr_masters?group=categories'));
    }

    public function delete_category($id)
    {
        $this->check_permission();

        $response = $this->hr_masters_model->delete_category($id);

        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('hr_master_in_use'));
        } elseif ($response) {
            set_alert('success', _l('deleted', _l('hr_employee_category')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('hr_employee_category')));
        }

        redirect(admin_url('hr_masters?group=categories'));
    }

    /* ============================================================ visa types */

    public function save_visa_type()
    {
        $this->check_permission();

        if (!$this->input->post()) {
            redirect(admin_url('hr_masters'));
        }

        $post = $this->input->post();
        $id   = !empty($post['id']) ? (int) $post['id'] : null;
        unset($post['id']);

        if ($this->hr_masters_model->save_visa_type($post, $id)) {
            set_alert('success', $id ? _l('updated_successfully', _l('hr_visa_type'))
                : _l('added_successfully', _l('hr_visa_type')));
        } else {
            set_alert('warning', _l('hr_visa_type_invalid'));
        }

        redirect(admin_url('hr_masters?group=visa_types'));
    }

    public function delete_visa_type($id)
    {
        $this->check_permission();

        $response = $this->hr_masters_model->delete_visa_type($id);

        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('hr_master_in_use'));
        } elseif ($response) {
            set_alert('success', _l('deleted', _l('hr_visa_type')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('hr_visa_type')));
        }

        redirect(admin_url('hr_masters?group=visa_types'));
    }

    /* ======================================================== document types */

    public function save_document_type()
    {
        $this->check_permission();

        if (!$this->input->post()) {
            redirect(admin_url('hr_masters'));
        }

        $post = $this->input->post();
        $id   = !empty($post['id']) ? (int) $post['id'] : null;
        unset($post['id']);

        if ($this->hr_masters_model->save_document_type($post, $id)) {
            set_alert('success', $id ? _l('updated_successfully', _l('hr_document_type'))
                : _l('added_successfully', _l('hr_document_type')));
        } else {
            set_alert('warning', _l('hr_document_type_invalid'));
        }

        redirect(admin_url('hr_masters?group=document_types'));
    }

    public function delete_document_type($id)
    {
        $this->check_permission();

        $response = $this->hr_masters_model->delete_document_type($id);

        if (is_array($response) && isset($response['referenced'])) {
            set_alert('warning', _l('hr_master_in_use'));
        } elseif ($response) {
            set_alert('success', _l('deleted', _l('hr_document_type')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('hr_document_type')));
        }

        redirect(admin_url('hr_masters?group=document_types'));
    }

    /* =========================================================== loan policy */

    public function save_loan_policy()
    {
        $this->check_permission();

        if (!$this->input->post()) {
            redirect(admin_url('hr_masters'));
        }

        $post = $this->input->post();
        $id   = !empty($post['id']) ? (int) $post['id'] : null;
        unset($post['id']);

        if ($this->hr_masters_model->save_loan_policy($post, $id)) {
            set_alert('success', $id ? _l('updated_successfully', _l('hr_loan_policy'))
                : _l('added_successfully', _l('hr_loan_policy')));
        } else {
            // Either a duplicate for the same category, or active with no limit set
            // at all - which would look like a control while enforcing nothing.
            set_alert('warning', _l('hr_loan_policy_invalid'));
        }

        redirect(admin_url('hr_masters?group=loan_policy'));
    }

    public function delete_loan_policy($id)
    {
        $this->check_permission();

        if ($this->hr_masters_model->delete_loan_policy($id)) {
            set_alert('success', _l('deleted', _l('hr_loan_policy')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('hr_loan_policy')));
        }

        redirect(admin_url('hr_masters?group=loan_policy'));
    }

    /* ====================================================== increment split */

    public function save_split_rule()
    {
        $this->check_permission();

        if (!$this->input->post()) {
            redirect(admin_url('hr_masters'));
        }

        $post = $this->input->post();
        $id   = !empty($post['id']) ? (int) $post['id'] : null;
        unset($post['id']);

        if ($this->hr_masters_model->save_split_rule($post, $id)) {
            set_alert('success', $id ? _l('updated_successfully', _l('hr_split_rule'))
                : _l('added_successfully', _l('hr_split_rule')));
        } else {
            set_alert('warning', _l('hr_split_rule_invalid'));
        }

        redirect(admin_url('hr_masters?group=increment_split'));
    }

    public function delete_split_rule($id)
    {
        $this->check_permission();

        if ($this->hr_masters_model->delete_split_rule($id)) {
            set_alert('success', _l('deleted', _l('hr_split_rule')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('hr_split_rule')));
        }

        redirect(admin_url('hr_masters?group=increment_split'));
    }

    /* ======================================================== approval flows */

    public function save_flow()
    {
        $this->check_permission();

        if (!$this->input->post()) {
            redirect(admin_url('hr_masters'));
        }

        $post = $this->input->post();
        $id   = !empty($post['id']) ? (int) $post['id'] : null;
        unset($post['id']);

        if ($this->approval_flow_model->save_flow($post, $id)) {
            set_alert('success', $id ? _l('updated_successfully', _l('hr_approval_flow'))
                : _l('added_successfully', _l('hr_approval_flow')));
        } else {
            set_alert('warning', _l('hr_approval_flow_invalid'));
        }

        redirect(admin_url('hr_masters?group=approval_flows'));
    }

    public function delete_flow($id)
    {
        $this->check_permission();

        if ($this->approval_flow_model->delete_flow($id)) {
            set_alert('success', _l('deleted', _l('hr_approval_flow')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('hr_approval_flow')));
        }

        redirect(admin_url('hr_masters?group=approval_flows'));
    }

    public function save_level()
    {
        $this->check_permission();

        if (!$this->input->post()) {
            redirect(admin_url('hr_masters'));
        }

        $post = $this->input->post();
        $id   = !empty($post['id']) ? (int) $post['id'] : null;
        unset($post['id']);

        if ($this->approval_flow_model->save_level($post, $id)) {
            set_alert('success', $id ? _l('updated_successfully', _l('hr_approval_level'))
                : _l('added_successfully', _l('hr_approval_level')));
        } else {
            // A duplicate level number, or an approver type that names nobody.
            set_alert('warning', _l('hr_approval_level_invalid'));
        }

        redirect(admin_url('hr_masters?group=approval_flows'));
    }

    public function delete_level($id)
    {
        $this->check_permission();

        if ($this->approval_flow_model->delete_level($id)) {
            set_alert('success', _l('deleted', _l('hr_approval_level')));
        } else {
            set_alert('warning', _l('problem_deleting', _l('hr_approval_level')));
        }

        redirect(admin_url('hr_masters?group=approval_flows'));
    }

    /* =========================================================== assignments */

    public function save_profile()
    {
        $this->check_permission();

        if (!$this->input->post()) {
            redirect(admin_url('hr_masters'));
        }

        $post     = $this->input->post();
        $staff_id = (int) ($post['staff_id'] ?? 0);
        unset($post['staff_id']);

        if ($staff_id > 0 && $this->hr_masters_model->save_profile($staff_id, $post)) {
            set_alert('success', _l('updated_successfully', _l('hr_employee_profile')));
        } else {
            set_alert('warning', _l('hr_profile_save_failed'));
        }

        redirect(admin_url('hr_masters?group=assignments'));
    }

    /**
     * Set the same category on many employees at once.
     *
     * Without this, classifying a few hundred staff one at a time is the reason the
     * category never gets filled in - and every rule that keys off it stays inert.
     */
    public function bulk_assign_category()
    {
        $this->check_permission();

        if (!$this->input->post()) {
            redirect(admin_url('hr_masters?group=assignments'));
        }

        $category_id = (int) $this->input->post('category_id');
        $staff_ids   = $this->input->post('staff_ids');

        if ($category_id <= 0 || !is_array($staff_ids) || count($staff_ids) === 0) {
            set_alert('warning', _l('hr_bulk_assign_nothing_selected'));
            redirect(admin_url('hr_masters?group=assignments'));
        }

        $saved = 0;

        foreach ($staff_ids as $staff_id) {
            $staff_id = (int) $staff_id;

            if ($staff_id <= 0) {
                continue;
            }

            // Merge rather than overwrite: only the category changes, so a joining
            // date or salary on joining already entered is not wiped out.
            $existing = $this->hr_masters_model->get_profile($staff_id);

            $payload = [
                'category_id'        => $category_id,
                'visa_type_id'       => $existing->visa_type_id ?? null,
                'joining_date'       => !empty($existing->joining_date) ? _d($existing->joining_date) : '',
                'probation_end_date' => !empty($existing->probation_end_date) ? _d($existing->probation_end_date) : '',
                'salary_on_joining'  => $existing->salary_on_joining ?? '',
                'note'               => $existing->note ?? null,
            ];

            if ($this->hr_masters_model->save_profile($staff_id, $payload)) {
                $saved++;
            }
        }

        set_alert('success', $saved . ' ' . _l('hr_employees_assigned'));

        redirect(admin_url('hr_masters?group=assignments'));
    }
}
